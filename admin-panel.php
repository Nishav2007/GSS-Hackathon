<?php
require_once 'config.php';
requireAdmin();

$success = '';
$error = '';

function getWalletWarningLevel($balancePaisa) {
    if ($balancePaisa < WALLET_SUSPEND_THRESHOLD_PAISA) {
        return 'suspended';
    }
    if ($balancePaisa <= -90000) {
        return 'below_900';
    }
    if ($balancePaisa < 0) {
        return 'below_zero';
    }
    return 'none';
}

function walletCooldownActive($lastTopupAt) {
    if (empty($lastTopupAt)) {
        return false;
    }
    $last = strtotime($lastTopupAt);
    return $last && $last >= strtotime('-7 days');
}

// Handle water status toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_water'])) {
    $location_id = intval($_POST['location_id'] ?? 0);
    $new_status = $_POST['new_status'] ?? '';
    
    if ($location_id > 0 && in_array($new_status, ['flowing', 'not_flowing'])) {
        // Get current status and location name
        $stmt = $conn->prepare("SELECT location_name, water_status FROM locations WHERE id = ?");
        $stmt->bind_param('i', $location_id);
        $stmt->execute();
        $locData = $stmt->get_result()->fetch_assoc();
        $location_name = $locData['location_name'] ?? 'Unknown';
        $current_status = $locData['water_status'] ?? 'not_flowing';
        
        // Update water status
        $stmt = $conn->prepare("UPDATE locations SET water_status = ?, status_updated_at = NOW() WHERE id = ?");
        $stmt->bind_param('si', $new_status, $location_id);
        $stmt->execute();
        
        if ($new_status === 'flowing') {
            // Create water event (if not created in last hour - prevent duplicates)
            $stmt = $conn->prepare("
                SELECT id FROM water_events 
                WHERE location_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
            ");
            $stmt->bind_param('i', $location_id);
            $stmt->execute();
            
            $createdEvent = false;
            if ($stmt->get_result()->num_rows === 0) {
                // Create event
                $stmt = $conn->prepare("
                    INSERT INTO water_events (location_id, arrival_date, arrival_time, admin_id) 
                    VALUES (?, CURDATE(), CURTIME(), 1)
                ");
                $stmt->bind_param('i', $location_id);
                $stmt->execute();
                $createdEvent = true;
                
                // Count users in this location
                $stmt = $conn->prepare("SELECT COUNT(*) as count FROM users WHERE location_id = ?");
                $stmt->bind_param('i', $location_id);
                $stmt->execute();
                $userCount = $stmt->get_result()->fetch_assoc()['count'];
                
                $success = "✅ Water flow started in {$location_name}! ({$userCount} users will be notified)";
            } else {
                $success = "✅ Water status updated for {$location_name}";
            }

            // Notify all users when switching not_flowing -> flowing (once per ON transition)
            if ($current_status !== 'flowing') {
                $stmt = $conn->prepare("SELECT name, email FROM users WHERE location_id = ?");
                $stmt->bind_param('i', $location_id);
                $stmt->execute();
                $users = $stmt->get_result();

                $subject = "Water Flow Alert: {$location_name}";
                $message = "
                    <p>Dear user,</p>
                    <p>Melamchi water is now flowing in <strong>{$location_name}</strong>.</p>
                    <p>Please collect water as soon as possible.</p>
                    <p>Regards,<br>Astha Water Alerts</p>
                ";

                while ($user = $users->fetch_assoc()) {
                    sendEmail($user['email'], $user['name'], $subject, $message);
                }

                if (!$createdEvent) {
                    $success = "✅ Water flow started in {$location_name}! (Users notified)";
                }
            }
        } else {
            $success = "✅ Water flow stopped in {$location_name}";
        }
        
        // Redirect to clear POST data
        header("Location: admin-panel.php?success=" . urlencode($success));
        exit;
    }
}

// Handle user water usage entry
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_usage'])) {
    $usage_user_id = intval($_POST['usage_user_id'] ?? 0);
    $liters = intval($_POST['liters'] ?? 0);
    $note = trim($_POST['note'] ?? '');

    if ($usage_user_id <= 0 || $liters <= 0) {
        $error = 'Please select a user and enter valid liters.';
    } else {
        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare("
                SELECT id, name, email, wallet_balance_paisa, service_status, total_liters_used, billed_blocks, unbilled_liters,
                       last_topup_at, last_wallet_warning_level
                FROM users WHERE id = ? FOR UPDATE
            ");
            $stmt->bind_param('i', $usage_user_id);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();

            if (!$user) {
                throw new Exception('User not found.');
            }

            if ($user['service_status'] === 'suspended') {
                throw new Exception('User is suspended. Resume service before adding usage.');
            }

            // Insert usage record
            $stmt = $conn->prepare("INSERT INTO user_water_usage (user_id, liters, note, admin_id) VALUES (?, ?, ?, 1)");
            $stmt->bind_param('iis', $usage_user_id, $liters, $note);
            $stmt->execute();
            $usage_id = $stmt->insert_id;

            $newTotalLiters = $user['total_liters_used'] + $liters;
            $newUnbilled = $user['unbilled_liters'] + $liters;
            $blocksToBill = intdiv($newUnbilled, WATER_BLOCK_LITERS);
            $remainingLiters = $newUnbilled % WATER_BLOCK_LITERS;
            $deductPaisa = $blocksToBill * WATER_BLOCK_COST_PAISA;
            $newBalance = $user['wallet_balance_paisa'] - $deductPaisa;
            $newBilledBlocks = $user['billed_blocks'] + $blocksToBill;
            $newServiceStatus = $user['service_status'];

            if ($newBalance < WALLET_SUSPEND_THRESHOLD_PAISA) {
                $newServiceStatus = 'suspended';
            }

            $stmt = $conn->prepare("
                UPDATE users
                SET wallet_balance_paisa = ?, total_liters_used = ?, billed_blocks = ?, unbilled_liters = ?, service_status = ?
                WHERE id = ?
            ");
            $stmt->bind_param('iiissi', $newBalance, $newTotalLiters, $newBilledBlocks, $remainingLiters, $newServiceStatus, $usage_user_id);
            $stmt->execute();

            if ($deductPaisa > 0) {
                $desc = "Water usage billing: {$blocksToBill} block(s) (" . ($blocksToBill * WATER_BLOCK_LITERS) . " liters)";
                $stmt = $conn->prepare("
                    INSERT INTO wallet_ledger (user_id, type, amount_paisa, description, ref_type, ref_id, balance_after_paisa)
                    VALUES (?, 'deduction', ?, ?, 'usage', ?, ?)
                ");
                $negativeDeduct = -$deductPaisa;
                $stmt->bind_param('iisii', $usage_user_id, $negativeDeduct, $desc, $usage_id, $newBalance);
                $stmt->execute();
            }

            // Wallet warning emails (threshold crossing only, 1 week cooldown after topup)
            $previousLevel = $user['last_wallet_warning_level'];
            $newLevel = getWalletWarningLevel($newBalance);
            $cooldown = walletCooldownActive($user['last_topup_at']);

            $shouldSend = !$cooldown && $newLevel !== $previousLevel;
            $allowedTransitions = [
                'none' => ['below_zero', 'below_900', 'suspended'],
                'below_zero' => ['below_900', 'suspended'],
                'below_900' => ['suspended'],
                'suspended' => [],
            ];

            if ($shouldSend && in_array($newLevel, $allowedTransitions[$previousLevel] ?? [], true)) {
                $subject = 'Astha Wallet Alert';
                $body = '';

                if ($newLevel === 'below_zero') {
                    $subject = 'Astha Wallet Warning: Balance Below 0';
                    $body = "<p>Your wallet balance is now below 0.</p><p>Please top up to continue uninterrupted service.</p>";
                } elseif ($newLevel === 'below_900') {
                    $subject = 'Astha Wallet Warning: Balance Below -900';
                    $body = "<p>Your wallet balance has reached -900 or below.</p><p>Please top up immediately to avoid service suspension.</p>";
                } elseif ($newLevel === 'suspended') {
                    $subject = 'Astha Service Suspended: Balance Below -1000';
                    $body = "<p>Your wallet balance is below -1000.</p><p>Your service has been suspended. Please top up to restore service.</p>";
                }

                $body .= "<p>Current balance: <strong>" . formatNpr($newBalance) . "</strong></p>";
                $body .= "<p>Top up here: <a href=\"" . TOPUP_URL . "\">" . TOPUP_URL . "</a></p>";
                $body .= "<p>Regards,<br>Astha Water Alerts</p>";

                if (sendEmail($user['email'], $user['name'], $subject, $body)) {
                    $stmt = $conn->prepare("
                        UPDATE users SET last_wallet_warning_level = ?, last_wallet_email_sent_at = NOW()
                        WHERE id = ?
                    ");
                    $stmt->bind_param('si', $newLevel, $usage_user_id);
                    $stmt->execute();
                }
            }

            $conn->commit();
            $success = "✅ Usage recorded for {$user['name']} ({$liters} liters).";
            header("Location: admin-panel.php?success=" . urlencode($success));
            exit;
        } catch (Exception $e) {
            $conn->rollback();
            $error = $e->getMessage();
        }
    }
}

// Handle user suspension toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_user_status'])) {
    $user_id = intval($_POST['user_id'] ?? 0);
    $new_status = $_POST['new_status'] ?? '';
    if ($user_id > 0 && in_array($new_status, ['active', 'suspended'], true)) {
        $stmt = $conn->prepare("UPDATE users SET service_status = ? WHERE id = ?");
        $stmt->bind_param('si', $new_status, $user_id);
        $stmt->execute();
        $success = "✅ User status updated.";
        header("Location: admin-panel.php?success=" . urlencode($success));
        exit;
    }
}

// Display success message from URL
if (isset($_GET['success'])) {
    $success = $_GET['success'];
}

// Get statistics
$totalLocations = $conn->query("SELECT COUNT(*) as count FROM locations")->fetch_assoc()['count'];
$totalUsers = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
$activeLocations = $conn->query("SELECT COUNT(*) as count FROM locations WHERE water_status = 'flowing'")->fetch_assoc()['count'];
$eventsToday = $conn->query("SELECT COUNT(*) as count FROM water_events WHERE arrival_date = CURDATE()")->fetch_assoc()['count'];

// Get all locations for water flow control
$locations = $conn->query("
    SELECT 
        l.id,
        l.location_name,
        l.district,
        l.water_status,
        l.status_updated_at,
        COUNT(DISTINCT u.id) as user_count,
        COUNT(DISTINCT CASE WHEN we.arrival_date = CURDATE() THEN we.id END) as events_today
    FROM locations l
    LEFT JOIN users u ON l.id = u.location_id
    LEFT JOIN water_events we ON l.id = we.location_id
    GROUP BY l.id
    ORDER BY l.location_name
");

// Get all users for user management
$allUsers = $conn->query("
    SELECT 
        u.id,
        u.name,
        u.email,
        l.location_name,
        l.district,
        u.created_at,
        u.wallet_balance_paisa,
        u.service_status,
        u.total_liters_used,
        COUNT(we.id) as total_events
    FROM users u
    JOIN locations l ON u.location_id = l.id
    LEFT JOIN water_events we ON l.id = we.location_id
    GROUP BY u.id
    ORDER BY u.created_at DESC
");

$userOptions = $conn->query("
    SELECT id, name, email, wallet_balance_paisa, service_status
    FROM users
    ORDER BY name ASC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="refresh" content="30">
    <title>Admin Panel - Astha</title>
    <link rel="stylesheet" href="Astha-theme.css">
</head>
<body>
    <nav class="navbar">
        <div class="navbar-content">
            <a href="index.php" class="logo">💧 Astha</a>
            <ul class="nav-links">
                <li><a href="admin-panel.php">Admin Panel</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <div class="dashboard-header">
            <h1>Admin Control Panel</h1>
            <p>Welcome, <?= htmlspecialchars($_SESSION['admin_username']) ?></p>
            <span class="live-badge">
                <span class="pulse-dot"></span>
                LIVE
            </span>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- Statistics Dashboard -->
        <div class="admin-stats">
            <div class="admin-stat-card">
                <h3><?= $totalLocations ?></h3>
                <p>Total Locations</p>
            </div>
            <div class="admin-stat-card">
                <h3><?= $totalUsers ?></h3>
                <p>Total Users</p>
            </div>
            <div class="admin-stat-card">
                <h3><?= $activeLocations ?></h3>
                <p>Active Water</p>
            </div>
            <div class="admin-stat-card">
                <h3><?= $eventsToday ?></h3>
                <p>Events Today</p>
            </div>
        </div>

        <!-- Tab Navigation -->
        <div class="tabs">
            <button class="tab-button active" onclick="switchTab('water-control')">Water Flow Control</button>
            <button class="tab-button" onclick="switchTab('user-management')">User Management</button>
        </div>

        <!-- Tab 1: Water Flow Control -->
        <div id="water-control" class="tab-content active">
            <div class="card" style="margin: 2rem 0;">
                <input type="text" id="locationSearch" class="search-box" placeholder="Search locations...">
            </div>

            <div class="location-grid">
                <?php while ($loc = $locations->fetch_assoc()): ?>
                    <?php
                    $isFlowing = $loc['water_status'] === 'flowing';
                    $cardClass = $isFlowing ? 'location-card flowing' : 'location-card not-flowing';
                    ?>
                    <div class="<?= $cardClass ?>" data-location="<?= htmlspecialchars(strtolower($loc['location_name'])) ?>">
                        <div class="location-header">
                            <h3><?= htmlspecialchars($loc['location_name']) ?></h3>
                            <span class="district"><?= htmlspecialchars($loc['district']) ?></span>
                        </div>
                        
                        <div class="location-stats">
                            <div class="stat">
                                <span class="icon-user-small"></span>
                                <span><?= $loc['user_count'] ?> users</span>
                            </div>
                            <div class="stat">
                                <span class="icon-drop-small"></span>
                                <span><?= $loc['events_today'] ?> events today</span>
                            </div>
                        </div>
                        
                        <div class="status-badge <?= $isFlowing ? 'flowing' : 'not-flowing' ?>">
                            <?= $isFlowing ? '💧 FLOWING' : '❌ NOT FLOWING' ?>
                        </div>
                        
                        <?php if ($loc['status_updated_at']): ?>
                            <small style="display: block; color: var(--text-secondary); margin: 0.5rem 0;">
                                Last updated: <?= date('h:i A', strtotime($loc['status_updated_at'])) ?>
                            </small>
                        <?php endif; ?>
                        
                        <form method="POST" onsubmit="return confirmToggle(<?= $loc['id'] ?>, '<?= htmlspecialchars(addslashes($loc['location_name'])) ?>', <?= $isFlowing ? 'false' : 'true' ?>)">
                            <input type="hidden" name="toggle_water" value="1">
                            <input type="hidden" name="location_id" value="<?= $loc['id'] ?>">
                            <input type="hidden" name="new_status" value="<?= $isFlowing ? 'not_flowing' : 'flowing' ?>">
                            <button type="submit" class="btn <?= $isFlowing ? 'btn-danger' : 'btn-success' ?>" style="width: 100%;">
                                <?= $isFlowing ? 'Turn OFF' : 'Turn ON' ?>
                            </button>
                        </form>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>

        <!-- Tab 2: User Management -->
        <div id="user-management" class="tab-content">
            <div class="card" style="margin: 2rem 0;">
                <input type="text" id="userSearch" class="search-box" placeholder="Search users by name, email, or location...">
            </div>

            <div class="card" style="margin: 2rem 0;">
                <h3 style="margin-bottom: 1rem; color: var(--teal-primary);">Add Water Usage (Per User)</h3>
                <form method="POST" style="display: grid; gap: 1rem; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); align-items: end;">
                    <div class="form-group">
                        <label for="usage_user_id">Select User</label>
                        <select id="usage_user_id" name="usage_user_id" required>
                            <option value="">Choose user</option>
                            <?php while ($u = $userOptions->fetch_assoc()): ?>
                                <option value="<?= $u['id'] ?>">
                                    <?= htmlspecialchars($u['name']) ?> (<?= htmlspecialchars($u['email']) ?>)
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="liters">Liters Used</label>
                        <input type="number" id="liters" name="liters" min="1" required>
                    </div>
                    <div class="form-group">
                        <label for="note">Note (optional)</label>
                        <input type="text" id="note" name="note" placeholder="Manual entry or reason">
                    </div>
                    <button type="submit" name="add_usage" class="btn btn-primary" style="height: 48px;">Add Usage</button>
                </form>
            </div>

            <div class="table-container">
                <table class="users-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Location</th>
                            <th>District</th>
                            <th>Balance</th>
                            <th>Status</th>
                            <th>Liters Used</th>
                            <th>Registered</th>
                            <th>Events Received</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($user = $allUsers->fetch_assoc()): ?>
                            <tr>
                                <td><?= $user['id'] ?></td>
                                <td><?= htmlspecialchars($user['name']) ?></td>
                                <td><?= htmlspecialchars($user['email']) ?></td>
                                <td><?= htmlspecialchars($user['location_name']) ?></td>
                                <td><?= htmlspecialchars($user['district']) ?></td>
                                <td><?= formatNpr($user['wallet_balance_paisa']) ?></td>
                                <td>
                                    <span class="status-pill <?= $user['service_status'] === 'suspended' ? 'danger' : 'success' ?>">
                                        <?= $user['service_status'] === 'suspended' ? 'Suspended' : 'Active' ?>
                                    </span>
                                </td>
                                <td><?= (int) $user['total_liters_used'] ?> L</td>
                                <td><?= date('M d, Y', strtotime($user['created_at'])) ?></td>
                                <td><?= $user['total_events'] ?></td>
                                <td>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="toggle_user_status" value="1">
                                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                        <input type="hidden" name="new_status" value="<?= $user['service_status'] === 'suspended' ? 'active' : 'suspended' ?>">
                                        <button type="submit" class="btn <?= $user['service_status'] === 'suspended' ? 'btn-success' : 'btn-danger' ?>" style="padding: 0.5rem 1rem;">
                                            <?= $user['service_status'] === 'suspended' ? 'Resume' : 'Suspend' ?>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        // Tab switching
        function switchTab(tabName) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            document.querySelectorAll('.tab-button').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Show selected tab
            document.getElementById(tabName).classList.add('active');
            event.target.classList.add('active');
        }

        // Location search
        document.getElementById('locationSearch').addEventListener('input', function() {
            const filter = this.value.toLowerCase();
            document.querySelectorAll('.location-card').forEach(card => {
                const name = card.dataset.location;
                card.style.display = name.includes(filter) ? '' : 'none';
            });
        });

        // User search
        document.getElementById('userSearch').addEventListener('input', function() {
            const filter = this.value.toLowerCase();
            document.querySelectorAll('.users-table tbody tr').forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(filter) ? '' : 'none';
            });
        });

        // Confirmation dialog for water toggle
        function confirmToggle(locationId, locationName, turningOn) {
            if (turningOn) {
                return confirm(`Turn ON water flow for ${locationName}?\n\nThis will:\n- Update water status to FLOWING\n- Create a new water event\n- Show on all user dashboards\n\nContinue?`);
            } else {
                return confirm(`Turn OFF water flow for ${locationName}?`);
            }
        }

        // Countdown timer in title
        let seconds = 30;
        const originalTitle = document.title;
        setInterval(() => {
            seconds--;
            if (seconds <= 0) seconds = 30;
            document.title = `(${seconds}s) ${originalTitle}`;
        }, 1000);
    </script>
</body>
</html>
