<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Astha - Melamchi Water Alert System</title>
    <link rel="stylesheet" href="Astha-theme.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="navbar-content">
            <a href="index.php" class="logo">💧 Astha</a>
            <button class="mobile-menu-toggle" onclick="toggleMobileMenu()">☰</button>
            <ul class="nav-links" id="navLinks">
                <li><a href="#home">Home</a></li>
                <li><a href="#solution">Solution</a></li>
                <li><a href="login.php">Login</a></li>
                <li><a href="register.php">Register</a></li>
            </ul>
        </div>
    </nav>

    <!-- Hero Section -->
    <section id="home" class="hero">
        <div class="hero-content">
            <h1>Never Miss Melamchi Water Again</h1>
            <p>Get instant notifications when water arrives in your area. Track water flow across 42 locations in Nepal with real-time updates.</p>
            <div class="hero-buttons">
                <a href="register.php" class="btn btn-primary">Get Started Free</a>
                <a href="login.php" class="btn btn-secondary">Login</a>
            </div>
            <div class="hero-stats">
                <div class="stat-item">
                    <strong>42</strong> Locations
                </div>
                <div class="stat-item">
                    <strong>Live</strong> Updates
                </div>
                <div class="stat-item">
                    <strong>24/7</strong> Monitoring
                </div>
            </div>
        </div>
        <div class="hero-visual">
            <div class="water-wave wave-1"></div>
            <div class="water-wave wave-2"></div>
            <div class="water-wave wave-3"></div>
        </div>
    </section>

    <!-- Solution Section -->
    <section id="solution" class="section">
        <h2 class="section-title">Why Choose Astha?</h2>
        <div class="features-grid">
            <div class="card feature-card">
                <div class="icon-email"></div>
                <h3>Instant Alerts</h3>
                <p>Receive email notifications the moment water arrives in your location. Never miss a water supply again.</p>
            </div>
            <div class="card feature-card">
                <div class="icon-drop"></div>
                <h3>Live Status</h3>
                <p>Real-time monitoring with automatic page refresh every 30 seconds. Always know the current water status.</p>
            </div>
            <div class="card feature-card">
                <div class="icon-chart"></div>
                <h3>History Tracking</h3>
                <p>Complete water supply records for your location. Track patterns and plan ahead.</p>
            </div>
            <div class="card feature-card">
                <div class="icon-map"></div>
                <h3>Location-Based</h3>
                <p>Targeted alerts for 42 locations across Nepal. Get updates only for your area.</p>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <p>Built with 💧 for the Kathmandu community</p>
        <p>&copy; 2024 Astha - Melamchi Water Alert System. All rights reserved.</p>
    </footer>

    <script>
        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Mobile menu toggle
        function toggleMobileMenu() {
            const navLinks = document.getElementById('navLinks');
            navLinks.classList.toggle('active');
        }

        // Close mobile menu when clicking outside
        document.addEventListener('click', function(event) {
            const navLinks = document.getElementById('navLinks');
            const toggle = document.querySelector('.mobile-menu-toggle');
            if (!navLinks.contains(event.target) && !toggle.contains(event.target)) {
                navLinks.classList.remove('active');
            }
        });
    </script>
</body>
</html>
