<?php
/**
 * ASTHA - MELAMCHI WATER ALERT SYSTEM
 * Configuration File
 * Database connection and helper functions
 */

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'Astha');

// Site Configuration
define('SITE_URL', 'http://localhost/Astha');

// Database Connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset('utf8mb4');

// Start Session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Sanitize input to prevent SQL injection
 * @param string $data Input data
 * @return string Sanitized data
 */
function clean($data) {
    global $conn;
    return $conn->real_escape_string(trim($data));
}

/**
 * Check if user is logged in
 * @return bool
 */
function isUserLoggedIn() {
    return isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true;
}

/**
 * Check if admin is logged in
 * @return bool
 */
function isAdminLoggedIn() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

/**
 * Require user login - redirect if not logged in
 */
function requireLogin() {
    if (!isUserLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

/**
 * Require admin login - redirect if not logged in
 */
function requireAdmin() {
    if (!isAdminLoggedIn()) {
        header('Location: admin-login.php');
        exit;
    }
}

/**
 * Calculate time ago from timestamp
 * @param string $timestamp MySQL timestamp
 * @return string Human-readable time ago
 */
function timeAgo($timestamp) {
    $time = strtotime($timestamp);
    $diff = time() - $time;
    
    if ($diff < 60) return 'Just now';
    if ($diff < 3600) return floor($diff / 60) . ' minutes ago';
    if ($diff < 86400) return floor($diff / 3600) . ' hours ago';
    if ($diff < 604800) return floor($diff / 86400) . ' days ago';
    return date('M d, Y', $time);
}
?>
