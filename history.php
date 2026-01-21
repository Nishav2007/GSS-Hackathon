<?php
require_once 'config.php';
requireLogin();

$user_id = $_SESSION['user_id'];
$location_id = $_SESSION['user_location_id'];
$location_name = $_SESSION['user_location_name'];
$district = $_SESSION['user_district'];

// Date range filter (optional)
$start_date = $_GET['start_date'] ?? date('Y-m-01'); // Default: start of month
$end_date = $_GET['end_date'] ?? date('Y-m-d'); // Default: today

// Get all events for user's location
$stmt = $conn->prepare("
    SELECT arrival_date, arrival_time, created_at 
    FROM water_events 
    WHERE location_id = ? AND arrival_date BETWEEN ? AND ?
    ORDER BY arrival_date DESC, arrival_time DESC
");
$stmt->bind_param('iss', $location_id, $start_date, $end_date);
$stmt->execute();
$events = $stmt->get_result();
$total = $events->num_rows;

// CSV Export
if (isset($_GET['export'])) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="water_history_' . $location_name . '.csv"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Date', 'Time', 'Location']);
    
    // Re-fetch events for export
    $stmt = $conn->prepare("
        SELECT arrival_date, arrival_time 
        FROM water_events 
        WHERE location_id = ? AND arrival_date BETWEEN ? AND ?
        ORDER BY arrival_date DESC, arrival_time DESC
    ");
    $stmt->bind_param('iss', $location_id, $start_date, $end_date);
    $stmt->execute();
    $exportEvents = $stmt->get_result();
    
    while ($row = $exportEvents->fetch_assoc()) {
        fputcsv($output, [
            $row['arrival_date'],
            $row['arrival_time'],
            $location_name
        ]);
    }
    fclose($output);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="refresh" content="60">
    <title>Water History - Astha</title>
    <link rel="stylesheet" href="Astha-theme.css">
</head>
<body>
    <nav class="navbar">
        <div class="navbar-content">
            <a href="index.php" class="logo">💧 Astha</a>
            <ul class="nav-links">
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="history.php">History</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <div class="dashboard-header">
            <h1>Water History</h1>
            <p><?= htmlspecialchars($location_name) ?>, <?= htmlspecialchars($district) ?></p>
        </div>

        <!-- Date Range Filter -->
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
                <a href="history.php?export=1&start_date=<?= urlencode($start_date) ?>&end_date=<?= urlencode($end_date) ?>" class="btn btn-secondary">Export CSV</a>
            </form>
        </div>

        <!-- Event Count Summary -->
        <div class="card" style="margin-bottom: 1rem;">
            <p style="color: var(--text-secondary);">
                Showing <strong><?= $total ?></strong> water event<?= $total !== 1 ? 's' : '' ?> from 
                <strong><?= date('M d, Y', strtotime($start_date)) ?></strong> to 
                <strong><?= date('M d, Y', strtotime($end_date)) ?></strong>
            </p>
        </div>

        <!-- Events Table -->
        <?php if ($total > 0): ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Time Ago</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($event = $events->fetch_assoc()): ?>
                            <tr>
                                <td><?= date('M d, Y', strtotime($event['arrival_date'])) ?></td>
                                <td><?= date('h:i A', strtotime($event['arrival_time'])) ?></td>
                                <td><?= timeAgo($event['created_at']) ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="card text-center" style="margin: 2rem 0;">
                <p style="color: var(--text-secondary);">No water events found for the selected date range.</p>
                <a href="history.php" class="btn btn-primary" style="margin-top: 1rem;">View All Events</a>
            </div>
        <?php endif; ?>

        <!-- Back Button -->
        <div style="text-align: center; margin: 2rem 0;">
            <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
        </div>
    </div>
</body>
</html>
