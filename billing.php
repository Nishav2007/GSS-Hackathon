<?php
require_once 'config.php';
requireLogin();

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$location_name = $_SESSION['user_location_name'];
$district = $_SESSION['user_district'];

$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-d');

$stmt = $conn->prepare("
    SELECT wallet_balance_paisa, total_liters_used, billed_blocks, unbilled_liters, service_status
    FROM users WHERE id = ?
");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$userData = $stmt->get_result()->fetch_assoc();

$walletBalance = (int) ($userData['wallet_balance_paisa'] ?? 0);
$totalLitersUsed = (int) ($userData['total_liters_used'] ?? 0);
$billedBlocks = (int) ($userData['billed_blocks'] ?? 0);
$unbilledLiters = (int) ($userData['unbilled_liters'] ?? 0);
$serviceStatus = $userData['service_status'] ?? 'active';
$billedCost = $billedBlocks * WATER_BLOCK_COST_PAISA;

// Wallet ledger
$stmt = $conn->prepare("
    SELECT type, amount_paisa, description, balance_after_paisa, created_at
    FROM wallet_ledger
    WHERE user_id = ? AND DATE(created_at) BETWEEN ? AND ?
    ORDER BY created_at DESC
");
$stmt->bind_param('iss', $user_id, $start_date, $end_date);
$stmt->execute();
$ledger = $stmt->get_result();

// Usage history
$stmt = $conn->prepare("
    SELECT liters, note, created_at
    FROM user_water_usage
    WHERE user_id = ? AND DATE(created_at) BETWEEN ? AND ?
    ORDER BY created_at DESC
");
$stmt->bind_param('iss', $user_id, $start_date, $end_date);
$stmt->execute();
$usageHistory = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Billing History - Astha</title>
    <link rel="stylesheet" href="Astha-theme.css">
</head>
<body>
    <nav class="navbar">
        <div class="navbar-content">
            <a href="index.php" class="logo">💧 Astha</a>
            <ul class="nav-links">
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="history.php">History</a></li>
                <li><a href="billing.php">Billing</a></li>
                <li><a href="topup.php">Topup</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <div class="dashboard-header">
            <h1>Billing & Usage</h1>
            <p><?= htmlspecialchars($location_name) ?>, <?= htmlspecialchars($district) ?></p>
            <span class="status-pill <?= $serviceStatus === 'suspended' ? 'danger' : 'success' ?>">
                <?= $serviceStatus === 'suspended' ? 'Suspended' : 'Active' ?>
            </span>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <h3><?= formatNpr($walletBalance) ?></h3>
                <p>Wallet Balance</p>
            </div>
            <div class="stat-card">
                <h3><?= number_format($totalLitersUsed) ?> L</h3>
                <p>Total Water Used</p>
            </div>
            <div class="stat-card">
                <h3><?= $billedBlocks ?></h3>
                <p>Billed Blocks (1000L)</p>
            </div>
            <div class="stat-card">
                <h3><?= formatNpr($billedCost) ?></h3>
                <p>Total Billed</p>
            </div>
            <div class="stat-card">
                <h3><?= $unbilledLiters ?> L</h3>
                <p>Unbilled Liters</p>
            </div>
        </div>

        <div class="card" style="margin: 2rem 0;">
            <form method="GET" style="display: flex; gap: 1rem; align-items: end; flex-wrap: wrap;">
                <div class="form-group" style="flex: 1; min-width: 150px;">
                    <label for="start_date">Start Date</label>
                    <input type="date" id="start_date" name="start_date" value="<?= htmlspecialchars($start_date) ?>" required>
                </div>
                <div class="form-group" style="flex: 1; min-width: 150px;">
                    <label for="end_date">End Date</label>
                    <input type="date" id="end_date" name="end_date" value="<?= htmlspecialchars($end_date) ?>" required>
                </div>
                <button type="submit" class="btn btn-primary">Filter</button>
                <a href="topup.php" class="btn btn-success">Topup Wallet</a>
            </form>
        </div>

        <div class="table-container">
            <h3 style="margin-bottom: 1rem; color: var(--teal-primary);">Wallet Ledger</h3>
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Description</th>
                        <th>Amount</th>
                        <th>Balance After</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($ledger->num_rows > 0): ?>
                        <?php while ($row = $ledger->fetch_assoc()): ?>
                            <tr>
                                <td><?= date('M d, Y h:i A', strtotime($row['created_at'])) ?></td>
                                <td><?= ucfirst($row['type']) ?></td>
                                <td><?= htmlspecialchars($row['description']) ?></td>
                                <td><?= formatNpr($row['amount_paisa']) ?></td>
                                <td><?= formatNpr($row['balance_after_paisa']) ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5">No wallet activity found for this period.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="table-container">
            <h3 style="margin-bottom: 1rem; color: var(--teal-primary);">Water Usage History</h3>
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Liters Used</th>
                        <th>Note</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($usageHistory->num_rows > 0): ?>
                        <?php while ($row = $usageHistory->fetch_assoc()): ?>
                            <tr>
                                <td><?= date('M d, Y h:i A', strtotime($row['created_at'])) ?></td>
                                <td><?= (int) $row['liters'] ?> L</td>
                                <td><?= htmlspecialchars($row['note'] ?: '-') ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="3">No usage records for this period.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
