<?php
/**
 * HOW IT WORKS PAGE - Melamchi Water Alert System
 */
require_once 'config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>How It Works - Astha</title>
    <link rel="stylesheet" href="Astha-theme.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <a href="index.php" class="logo">Astha</a>
            <button class="mobile-menu-toggle" onclick="toggleMobileMenu()">☰</button>
            <ul class="nav-menu" id="navMenu">
                <li><a href="index.php">Home</a></li>
                <li><a href="features.php">Features</a></li>
                <li><a href="how-it-works.php" class="active">How It Works</a></li>
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
            <h1 class="hero-title-aqua">How It Works</h1>
            <p class="hero-subtitle-aqua">Get started in three simple steps and never miss water supply again</p>
        </div>
    </section>

    <!-- Steps Section -->
    <section class="steps-section">
        <div class="steps-container">
            <div class="steps-connector"></div>
            <div class="steps-grid">
                <div class="step-item">
                    <div class="step-number">1</div>
                    <div class="step-icon-wrapper">
                        <div class="css-icon icon-map" style="width: 60px; height: 60px;"></div>
                    </div>
                    <h3>Register Your Location</h3>
                    <p>Sign up for free and select your area from 42 available locations across Kathmandu. It only takes a minute to create your account and choose your location.</p>
                    <div class="step-action">
                        <a href="register.php" class="btn btn-outline" style="margin-top: 1rem;">
                            Start Registration <span class="symbol-icon icon-arrow-right" aria-hidden="true"></span>
                        </a>
                    </div>
                </div>
                <div class="step-item">
                    <div class="step-number">2</div>
                    <div class="step-icon-wrapper">
                        <div class="css-icon icon-email" style="width: 60px; height: 60px;"></div>
                    </div>
                    <h3>Get Instant Alerts</h3>
                    <p>Receive real-time email notifications the moment water arrives in your area. Our system monitors water flow 24/7 and sends you instant alerts.</p>
                    <div class="step-action">
                        <span class="step-badge">Automatic</span>
                    </div>
                </div>
                <div class="step-item">
                    <div class="step-number">3</div>
                    <div class="step-icon-wrapper">
                        <div class="css-icon icon-drop enhanced" style="width: 60px; height: 60px;"></div>
                    </div>
                    <h3>Monitor Live Status</h3>
                    <p>Track water flow status on your dashboard with auto-updates every 30 seconds. Check the current status anytime, anywhere.</p>
                    <div class="step-action">
                        <a href="login.php" class="btn btn-outline" style="margin-top: 1rem;">
                            View Dashboard <span class="symbol-icon icon-arrow-right" aria-hidden="true"></span>
                        </a>
                    </div>
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
