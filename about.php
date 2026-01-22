<?php
/**
 * ABOUT PAGE - Melamchi Water Alert System
 */
require_once 'config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About - Astha</title>
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
                <li><a href="how-it-works.php">How It Works</a></li>
                <li><a href="about.php" class="active">About</a></li>
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
            <h1 class="hero-title-aqua">About Astha</h1>
            <p class="hero-subtitle-aqua">Empowering Kathmandu residents with real-time water supply information</p>
        </div>
    </section>

    <!-- About Content Section -->
    <section class="solution section section-white">
        <div class="container">
            <div style="max-width: 800px; margin: 0 auto; text-align: center;">
                <h2 class="section-title">Our Mission</h2>
                <p class="section-text" style="margin-bottom: 3rem;">
                    Astha is dedicated to solving the water supply challenges faced by Kathmandu residents. 
                    Through innovative technology and real-time monitoring, we ensure that no one misses their 
                    water supply window. Our intelligent alert system keeps you informed, helping you plan 
                    ahead and never miss an opportunity to collect water.
                </p>
                
                <div class="mission-values" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 2rem; margin-top: 3rem;">
                    <div class="value-card">
                        <div class="value-icon"><span class="symbol-icon icon-check" aria-hidden="true"></span></div>
                        <h3 style="color: var(--primary); margin: 1rem 0 0.5rem;">Reliability</h3>
                        <p style="color: var(--text-secondary); font-size: 0.9rem;">Dependable alerts you can trust</p>
                    </div>
                    <div class="value-card">
                        <div class="value-icon"><span class="symbol-icon icon-bolt" aria-hidden="true"></span></div>
                        <h3 style="color: var(--primary); margin: 1rem 0 0.5rem;">Speed</h3>
                        <p style="color: var(--text-secondary); font-size: 0.9rem;">Instant notifications in real-time</p>
                    </div>
                    <div class="value-card">
                        <div class="value-icon"><span class="symbol-icon icon-globe" aria-hidden="true"></span></div>
                        <h3 style="color: var(--primary); margin: 1rem 0 0.5rem;">Coverage</h3>
                        <p style="color: var(--text-secondary); font-size: 0.9rem;">42 locations across Kathmandu</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Trust & Stats Section -->
    <section class="trust-section">
        <div class="trust-container">
            <h2 class="steps-title" style="margin-bottom: 3rem;">Our Impact</h2>
            <div class="stats-row">
                <div class="stat-item">
                    <div class="stat-icon-wrapper">
                        <div class="css-icon icon-map" style="width: 50px; height: 50px; margin: 0 auto;"></div>
                    </div>
                    <span class="stat-number">42</span>
                    <div class="stat-label">Locations</div>
                    <div class="stat-description">Covered Areas</div>
                </div>
                <div class="stat-item">
                    <div class="stat-icon-wrapper">
                        <div class="css-icon icon-chart" style="width: 50px; height: 50px; margin: 0 auto;"></div>
                    </div>
                    <span class="stat-number">24/7</span>
                    <div class="stat-label">Monitoring</div>
                    <div class="stat-description">Always Active</div>
                </div>
                <div class="stat-item">
                    <div class="stat-icon-wrapper">
                        <div class="css-icon icon-drop enhanced" style="width: 50px; height: 50px; margin: 0 auto;"></div>
                    </div>
                    <span class="stat-number">Live</span>
                    <div class="stat-label">Updates</div>
                    <div class="stat-description">Real-time Status</div>
                </div>
                <div class="stat-item">
                    <div class="stat-icon-wrapper">
                        <div class="css-icon icon-wave" style="width: 50px; height: 50px; margin: 0 auto;"></div>
                    </div>
                    <span class="stat-number">Auto</span>
                    <div class="stat-label">Rotation</div>
                    <div class="stat-description">Smart System</div>
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
