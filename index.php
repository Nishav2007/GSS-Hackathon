<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Astha - Melamchi Water Alert System</title>
    <link rel="stylesheet" href="Astha-theme.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <a href="index.php" class="logo">Astha</a>
            <button class="mobile-menu-toggle" onclick="toggleMobileMenu()">☰</button>
            <ul class="nav-menu" id="navMenu">
                <li><a href="index.php" class="active">Home</a></li>
                <li><a href="features.php">Features</a></li>
                <li><a href="how-it-works.php">How It Works</a></li>
                <li><a href="about.php">About</a></li>
                <li><a href="register.php">Register</a></li>
                <li><a href="login.php">Login</a></li>
                <li><a href="admin-login.php">Admin</a></li>
            </ul>
        </div>
    </nav>

    <section class="hero-aqua">
        <div class="hero-waves"></div>
        <div class="hero-flow-overlay"></div>
        <div class="hero-bubbles"></div>
        <div class="hero-visual-aqua">
            <div class="css-icon icon-drop enhanced"></div>
        </div>
        <div class="hero-content-aqua">
            <h1 class="hero-title-aqua">Never Miss Melamchi Water Again</h1>
            <p class="hero-subtitle-aqua">Real-time water flow monitoring and instant notifications for your area. Stay informed, stay prepared with our intelligent alert system.</p>
            <div class="hero-buttons-aqua">
                <a href="register.php" class="btn-hero-primary">Get Started Free</a>
                <a href="login.php" class="btn-hero-ghost">Login</a>
            </div>
            <div class="hero-trust-indicators">
                <div class="trust-badge">
                    <span class="trust-icon symbol-icon icon-check" aria-hidden="true"></span>
                    <span>42 Locations Covered</span>
                </div>
                <div class="trust-badge">
                    <span class="trust-icon symbol-icon icon-bolt" aria-hidden="true"></span>
                    <span>Real-time Updates</span>
                </div>
                <div class="trust-badge">
                    <span class="trust-icon symbol-icon icon-bell" aria-hidden="true"></span>
                    <span>Instant Alerts</span>
                </div>
            </div>
        </div>
    </section>

    <footer class="footer footer-aqua">
        <div class="container">
            <p>&copy; <?= date('Y') ?> Astha - Melamchi Water Alert System. All rights reserved.</p>
            <p class="footer-subtext">Built for Kathmandu community</p>
        </div>
    </footer>

    <script>
        function toggleMobileMenu() {
            const navMenu = document.getElementById('navMenu');
            navMenu.classList.toggle('active');
        }

        document.addEventListener('click', function(event) {
            const navMenu = document.getElementById('navMenu');
            const toggle = document.querySelector('.mobile-menu-toggle');
            if (!navMenu.contains(event.target) && !toggle.contains(event.target)) {
                navMenu.classList.remove('active');
            }
        });
    </script>
</body>
</html>
