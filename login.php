<?php
require_once 'config.php';

// Redirect if already logged in
if (isUserLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$registered = isset($_GET['registered']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = clean($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Email and password are required';
    } else {
        // Get user with location
        $stmt = $conn->prepare("
            SELECT u.id, u.name, u.email, u.password, u.location_id, l.location_name, l.district
            FROM users u 
            JOIN locations l ON u.location_id = l.id 
            WHERE u.email = ?
        ");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $error = 'Invalid email or password';
        } else {
            $user = $result->fetch_assoc();
            
            // Verify password
            if (password_verify($password, $user['password'])) {
                // Create session
                session_regenerate_id(true);
                $_SESSION['user_logged_in'] = true;
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_location_id'] = $user['location_id'];
                $_SESSION['user_location_name'] = $user['location_name'];
                $_SESSION['user_district'] = $user['district'];
                
                header('Location: dashboard.php');
                exit;
            } else {
                $error = 'Invalid email or password';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Astha</title>
    <link rel="stylesheet" href="Astha-theme.css">
</head>
<body>
    <nav class="navbar">
        <div class="navbar-content">
            <a href="index.php" class="logo">💧 Astha</a>
            <ul class="nav-links">
                <li><a href="index.php">Home</a></li>
                <li><a href="register.php">Register</a></li>
            </ul>
        </div>
    </nav>

    <div class="form-container">
        <div class="form-card">
            <div class="icon-user"></div>
            <h2 class="text-center" style="color: var(--teal-primary); margin-bottom: 1.5rem;">Login</h2>
            
            <?php if ($registered): ?>
                <div class="alert alert-success">Registration successful! Please login with your credentials.</div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1rem;">Login</button>
            </form>

            <p class="text-center" style="margin-top: 1.5rem;">
                Don't have an account? <a href="register.php" class="link">Register here</a>
            </p>
            
            <p class="text-center" style="margin-top: 0.5rem;">
                <a href="admin-login.php" class="link">Admin Login</a>
            </p>
        </div>
    </div>
</body>
</html>
