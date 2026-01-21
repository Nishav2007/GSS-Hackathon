<?php
require_once 'config.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = clean($_POST['name'] ?? '');
    $email = clean($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $location_id = intval($_POST['location_id'] ?? 0);
    
    // Validation
    if (empty($name) || empty($email) || empty($password) || $location_id <= 0) {
        $error = 'All fields are required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters';
    } else {
        // Check duplicate email
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows > 0) {
            $error = 'Email already registered';
        } else {
            // Insert user
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (name, email, password, location_id) VALUES (?, ?, ?, ?)");
            $stmt->bind_param('sssi', $name, $email, $password_hash, $location_id);
            
            if ($stmt->execute()) {
                $success = 'Registration successful! Please login.';
                header('Location: login.php?registered=1');
                exit;
            } else {
                $error = 'Registration failed. Please try again.';
            }
        }
    }
}

// Fetch locations for dropdown
$locations = $conn->query("SELECT id, location_name, district FROM locations ORDER BY location_name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Astha</title>
    <link rel="stylesheet" href="Astha-theme.css">
</head>
<body>
    <nav class="navbar">
        <div class="navbar-content">
            <a href="index.php" class="logo">💧 Astha</a>
            <ul class="nav-links">
                <li><a href="index.php">Home</a></li>
                <li><a href="login.php">Login</a></li>
            </ul>
        </div>
    </nav>

    <div class="form-container">
        <div class="form-card">
            <div class="icon-user"></div>
            <h2 class="text-center" style="color: var(--teal-primary); margin-bottom: 1.5rem;">Create Account</h2>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="name">Full Name</label>
                    <input type="text" id="name" name="name" required value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required minlength="6">
                    <small style="color: var(--text-secondary);">Minimum 6 characters</small>
                </div>

                <div class="form-group">
                    <label for="location_id">Location</label>
                    <input type="text" id="locationSearch" class="search-box" placeholder="Search location...">
                    <select id="location_id" name="location_id" required>
                        <option value="">Select your location</option>
                        <?php while ($loc = $locations->fetch_assoc()): ?>
                            <option value="<?= $loc['id'] ?>" data-name="<?= htmlspecialchars($loc['location_name']) ?>">
                                <?= htmlspecialchars($loc['location_name']) ?> - <?= htmlspecialchars($loc['district']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1rem;">Register</button>
            </form>

            <p class="text-center" style="margin-top: 1.5rem;">
                Already have an account? <a href="login.php" class="link">Login here</a>
            </p>
        </div>
    </div>

    <script>
        // Make select searchable
        const searchBox = document.getElementById('locationSearch');
        const selectElement = document.getElementById('location_id');
        
        searchBox.addEventListener('input', function() {
            const filter = this.value.toLowerCase();
            Array.from(selectElement.options).forEach(option => {
                if (option.value === '') {
                    option.style.display = '';
                    return;
                }
                const text = option.text.toLowerCase();
                option.style.display = text.includes(filter) ? '' : 'none';
            });
        });

        // Show selected location in search box
        selectElement.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            if (selectedOption.value !== '') {
                searchBox.value = selectedOption.text;
            } else {
                searchBox.value = '';
            }
        });
    </script>
</body>
</html>
