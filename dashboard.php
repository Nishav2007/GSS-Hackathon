<?php
require_once 'config.php';
requireLogin();

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$location_id = $_SESSION['user_location_id'];
$location_name = $_SESSION['user_location_name'];
$district = $_SESSION['user_district'];

// Get current water status (LIVE)
$stmt = $conn->prepare("SELECT water_status, status_updated_at FROM locations WHERE id = ?");
$stmt->bind_param('i', $location_id);
$stmt->execute();
$locationData = $stmt->get_result()->fetch_assoc();
$waterStatus = $locationData['water_status'] ?? 'not_flowing';
$statusUpdated = $locationData['status_updated_at'] ?? null;

// Get latest water event
$stmt = $conn->prepare("
    SELECT arrival_date, arrival_time, created_at 
    FROM water_events 
    WHERE location_id = ? 
    ORDER BY created_at DESC 
    LIMIT 1
");
$stmt->bind_param('i', $location_id);
$stmt->execute();
$latestEvent = $stmt->get_result()->fetch_assoc();

// Determine display priority
$statusClass = 'no-water';
$statusIcon = 'icon-drop-off';
$statusTitle = 'No Recent Water Supply';
$statusMessage = 'You will receive notifications when water arrives';
$timeAgo = '';

if ($waterStatus === 'flowing') {
    // PRIORITY 1: Water is flowing RIGHT NOW
    $statusClass = 'flowing';
    $statusIcon = 'icon-drop pulsing';
    $statusTitle = '💧 Water Flowing Now!';
    $statusMessage = "Melamchi water is currently flowing in {$location_name}";
    if ($statusUpdated) {
        $timeAgo = "Started: " . date('h:i A', strtotime($statusUpdated));
    }
} elseif ($latestEvent) {
    // PRIORITY 2: Recent water event exists
    $statusClass = 'available';
    $statusIcon = 'icon-drop';
    $statusTitle = '💧 Water Available!';
    $arrivalDate = date('M d, Y', strtotime($latestEvent['arrival_date']));
    $arrivalTime = date('h:i A', strtotime($latestEvent['arrival_time']));
    $statusMessage = "Water arrived in {$location_name}";
    $timeAgo = "Last arrival: {$arrivalDate} at {$arrivalTime}";
}

// Count total events for user's location
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM water_events WHERE location_id = ?");
$stmt->bind_param('i', $location_id);
$stmt->execute();
$totalEvents = $stmt->get_result()->fetch_assoc()['total'];

// Count events this month
$stmt = $conn->prepare("
    SELECT COUNT(*) as count 
    FROM water_events 
    WHERE location_id = ? AND MONTH(arrival_date) = MONTH(CURDATE()) AND YEAR(arrival_date) = YEAR(CURDATE())
");
$stmt->bind_param('i', $location_id);
$stmt->execute();
$eventsThisMonth = $stmt->get_result()->fetch_assoc()['count'];

// Count events this week
$stmt = $conn->prepare("
    SELECT COUNT(*) as count 
    FROM water_events 
    WHERE location_id = ? AND arrival_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
");
$stmt->bind_param('i', $location_id);
$stmt->execute();
$eventsThisWeek = $stmt->get_result()->fetch_assoc()['count'];

// Get recent events (last 5)
$stmt = $conn->prepare("
    SELECT arrival_date, arrival_time, created_at 
    FROM water_events 
    WHERE location_id = ? 
    ORDER BY created_at DESC 
    LIMIT 5
");
$stmt->bind_param('i', $location_id);
$stmt->execute();
$recentEvents = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="refresh" content="30">
    <title>Dashboard - Astha</title>
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
            <h1>Welcome, <?= htmlspecialchars($user_name) ?>!</h1>
            <p>Location: <?= htmlspecialchars($location_name) ?>, <?= htmlspecialchars($district) ?></p>
            <span class="live-badge">
                <span class="pulse-dot"></span>
                LIVE
            </span>
        </div>

        <!-- Water Status Card -->
        <div class="status-card <?= $statusClass ?>">
            <div class="<?= $statusIcon ?>"></div>
            <h2><?= $statusTitle ?></h2>
            <p><?= htmlspecialchars($statusMessage) ?></p>
            <?php if ($timeAgo): ?>
                <small><?= htmlspecialchars($timeAgo) ?></small>
            <?php endif; ?>
        </div>

        <!-- Statistics Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3><?= $totalEvents ?></h3>
                <p>Total Events</p>
            </div>
            <div class="stat-card">
                <h3><?= $eventsThisMonth ?></h3>
                <p>This Month</p>
            </div>
            <div class="stat-card">
                <h3><?= $eventsThisWeek ?></h3>
                <p>This Week</p>
            </div>
        </div>

        <!-- Recent Events Table -->
        <?php if ($recentEvents->num_rows > 0): ?>
            <div class="table-container">
                <h3 style="margin-bottom: 1rem; color: var(--teal-primary);">Recent Water Events</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Time Ago</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($event = $recentEvents->fetch_assoc()): ?>
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
                <p style="color: var(--text-secondary);">No water events recorded yet.</p>
            </div>
        <?php endif; ?>

        <!-- Action Buttons -->
        <div style="text-align: center; margin: 2rem 0;">
            <a href="history.php" class="btn btn-primary">View Full History</a>
            <a href="dashboard.php" class="btn btn-secondary" style="margin-left: 1rem;">Refresh Now</a>
        </div>
    </div>

    <script>
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
