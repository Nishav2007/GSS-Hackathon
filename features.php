<?php
/**
 * FEATURES PAGE - Melamchi Water Alert System
 */
require_once 'config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Features - Astha</title>
    <link rel="stylesheet" href="Astha-theme.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <a href="index.php" class="logo">Astha</a>
            <button class="mobile-menu-toggle" onclick="toggleMobileMenu()">☰</button>
            <ul class="nav-menu" id="navMenu">
                <li><a href="index.php">Home</a></li>
                <li><a href="features.php" class="active">Features</a></li>
                <li><a href="how-it-works.php">How It Works</a></li>
                <li><a href="about.php">About</a></li>
                <li><a href="register.php">Register</a></li>
                <li><a href="login.php">Login</a></li>
                <li><a href="admin-login.php">Admin</a></li>
            </ul>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-aqua" style="min-height: 60vh; padding-top: 8rem;">
        <div class="hero-waves"></div>
        <div class="hero-flow-overlay"></div>
        <div class="hero-bubbles"></div>
        <div class="hero-content-aqua">
            <h1 class="hero-title-aqua">Our Solution</h1>
            <p class="hero-subtitle-aqua">Astha provides a comprehensive water alert system for Melamchi, ensuring you never miss water supply in your area through innovative technology and real-time monitoring.</p>
        </div>
    </section>

    <!-- Features Section -->
    <section class="solution section section-white">
        <div class="container">
            <div class="features-intro" style="text-align: center; margin-bottom: 4rem;">
                <h2 class="section-title" style="font-size: clamp(2rem, 4vw, 3rem); margin-bottom: 1rem;">Powerful Features</h2>
                <p class="section-text" style="max-width: 700px; margin: 0 auto;">Everything you need to stay informed about Melamchi water supply</p>
            </div>
            <div class="features-grid-aqua">
                <div class="glass-card feature-card-aqua">
                    <div class="feature-icon-wrapper">
                        <div class="css-icon icon-email"></div>
                    </div>
                    <h3>Instant Alerts</h3>
                    <p>Receive immediate email notifications when water arrives in your specific location. Never miss a water supply again with our real-time alert system.</p>
                    <div class="feature-benefits">
                        <span class="benefit-item"><span class="symbol-icon icon-check" aria-hidden="true"></span> Email Notifications</span>
                        <span class="benefit-item"><span class="symbol-icon icon-check" aria-hidden="true"></span> Instant Delivery</span>
                    </div>
                    <span class="feature-metric">Real-time Delivery</span>
                </div>
                <div class="glass-card feature-card-aqua">
                    <div class="feature-icon-wrapper">
                        <div class="css-icon icon-drop enhanced"></div>
                    </div>
                    <h3>Live Status</h3>
                    <p>Real-time water flow monitoring with auto-updating dashboard every 30 seconds. Stay informed about the current water status in your area.</p>
                    <div class="feature-benefits">
                        <span class="benefit-item"><span class="symbol-icon icon-check" aria-hidden="true"></span> Auto-refresh</span>
                        <span class="benefit-item"><span class="symbol-icon icon-check" aria-hidden="true"></span> Live Updates</span>
                    </div>
                    <span class="feature-metric">30s Updates</span>
                </div>
                <div class="glass-card feature-card-aqua">
                    <div class="feature-icon-wrapper">
                        <div class="css-icon icon-chart"></div>
                    </div>
                    <h3>History Tracking</h3>
                    <p>Complete water supply records to analyze patterns and plan ahead. Track water availability trends and make informed decisions.</p>
                    <div class="feature-benefits">
                        <span class="benefit-item"><span class="symbol-icon icon-check" aria-hidden="true"></span> Complete Records</span>
                        <span class="benefit-item"><span class="symbol-icon icon-check" aria-hidden="true"></span> Pattern Analysis</span>
                    </div>
                    <span class="feature-metric">Full Analytics</span>
                </div>
                <div class="glass-card feature-card-aqua">
                    <div class="feature-icon-wrapper">
                        <div class="css-icon icon-map"></div>
                    </div>
                    <h3>Location-Based</h3>
                    <p>Targeted alerts only for your registered area across 42 locations. Get notifications specific to your neighborhood in Kathmandu.</p>
                    <div class="feature-benefits">
                        <span class="benefit-item"><span class="symbol-icon icon-check" aria-hidden="true"></span> 42 Locations</span>
                        <span class="benefit-item"><span class="symbol-icon icon-check" aria-hidden="true"></span> Precise Alerts</span>
                    </div>
                    <span class="feature-metric">42 Locations</span>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-footer">
        <div class="cta-container">
            <h2 class="cta-title">Ready to Get Started?</h2>
            <p class="cta-text">Join thousands of users who stay informed about Melamchi water supply in real-time</p>
            <div class="cta-buttons">
                <a href="register.php" class="btn-hero-primary">Get Started Free</a>
                <a href="login.php" class="btn-hero-ghost">Login to Dashboard</a>
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
