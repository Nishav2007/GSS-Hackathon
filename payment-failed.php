<?php
require_once 'config.php';
requireLogin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Failed - Astha</title>
    <link rel="stylesheet" href="Astha-theme.css">
</head>
<body>
    <nav class="navbar">
        <div class="navbar-content">
            <a href="index.php" class="logo">💧 Astha</a>
            <ul class="nav-links">
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="topup.php">Topup</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="form-container">
        <div class="form-card text-center">
            <div class="icon-drop-off"></div>
            <h2 style="color: var(--danger-red);">Payment Failed</h2>
            <p>The payment was not completed. Please try again.</p>
            <p style="color: var(--text-secondary); font-size: 0.9rem;">Khalti callback did not confirm completion.</p>
            <a href="topup.php" class="btn btn-primary" style="margin-top: 1rem;">Try Again</a>
        </div>
    </div>
</body>
</html>
