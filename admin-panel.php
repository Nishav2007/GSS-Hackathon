<?php
require_once 'config.php';
requireAdmin();

$success = '';
$error = '';

// Handle water status toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_water'])) {
    $location_id = intval($_POST['location_id'] ?? 0);
    $new_status = $_POST['new_status'] ?? '';
    
    if ($location_id > 0 && in_array($new_status, ['flowing', 'not_flowing'])) {
        // Get location name
        $stmt = $conn->prepare("SELECT location_name FROM locations WHERE id = ?");
        $stmt->bind_param('i', $location_id);
        $stmt->execute();
        $locData = $stmt->get_result()->fetch_assoc();
        $location_name = $locData['location_name'] ?? 'Unknown';
        
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
            
            if ($stmt->get_result()->num_rows === 0) {
                // Create event
                $stmt = $conn->prepare("
                    INSERT INTO water_events (location_id, arrival_date, arrival_time, admin_id) 
                    VALUES (?, CURDATE(), CURTIME(), 1)
                ");
                $stmt->bind_param('i', $location_id);
                $stmt->execute();
                
                // Count users in this location
                $stmt = $conn->prepare("SELECT COUNT(*) as count FROM users WHERE location_id = ?");
                $stmt->bind_param('i', $location_id);
                $stmt->execute();
                $userCount = $stmt->get_result()->fetch_assoc()['count'];
                
                $success = "✅ Water flow started in {$location_name}! ({$userCount} users will be notified)";
            } else {
                $success = "✅ Water status updated for {$location_name}";
            }
        } else {
            $success = "✅ Water flow stopped in {$location_name}";
        }
        
        // Redirect to clear POST data
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
        COUNT(we.id) as total_events
    FROM users u
    JOIN locations l ON u.location_id = l.id
    LEFT JOIN water_events we ON l.id = we.location_id
    GROUP BY u.id
    ORDER BY u.created_at DESC
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

            <div class="table-container">
                <table class="users-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Location</th>
                            <th>District</th>
                            <th>Registered</th>
                            <th>Events Received</th>
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
                                <td><?= date('M d, Y', strtotime($user['created_at'])) ?></td>
                                <td><?= $user['total_events'] ?></td>
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
