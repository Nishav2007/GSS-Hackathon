<?php
require_once 'config.php';

// Redirect if already logged in
if (isAdminLoggedIn()) {
    header('Location: admin-panel.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // HARDCODED CHECK - No database query!
    if ($username === 'admin' && $password === 'admin123') {
        session_regenerate_id(true);
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_username'] = 'admin';
        $_SESSION['admin_id'] = 1;
        
        header('Location: admin-panel.php');
        exit;
    } else {
        $error = 'Invalid username or password';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Astha</title>
    <link rel="stylesheet" href="Astha-theme.css">
    <style>
        .admin-warning {
            background: #fef3c7;
            border: 2px solid #f59e0b;
            color: #92400e;
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
        }
        .form-card {
            border-color: var(--teal-dark);
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="navbar-content">
            <a href="index.php" class="logo">💧 Astha</a>
            <ul class="nav-links">
                <li><a href="index.php">Home</a></li>
                <li><a href="login.php">User Login</a></li>
            </ul>
        </div>
    </nav>

    <div class="form-container">
        <div class="form-card">
            <div class="icon-user"></div>
            <h2 class="text-center" style="color: var(--teal-dark); margin-bottom: 1.5rem;">Admin Login</h2>
            
            <div class="admin-warning">
                ⚠️ <strong>Admin Access Only</strong><br>
                This area is restricted to authorized administrators only.
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1rem; background: var(--teal-dark);">Login</button>
            </form>

            <p class="text-center" style="margin-top: 1.5rem;">
                <a href="login.php" class="link">User Login</a>
            </p>
        </div>
    </div>
</body>
</html>
