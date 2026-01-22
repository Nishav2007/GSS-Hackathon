<?php
/**
 * ASTHA - MELAMCHI WATER ALERT SYSTEM
 * Configuration File
 * Database connection and helper functions
 */

// Load local config (ignored by git)
$localConfig = __DIR__ . '/local-config.php';
if (file_exists($localConfig)) {
    require_once $localConfig;
}

// Load local environment variables from .env (if present)
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0) {
            continue;
        }
        $parts = explode('=', $line, 2);
        if (count($parts) !== 2) {
            continue;
        }
        $key = trim($parts[0]);
        $value = trim($parts[1]);
        if ($key !== '' && getenv($key) === false) {
            putenv($key . '=' . $value);
            $_ENV[$key] = $value;
        }
    }
}

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'Astha');

// Site Configuration
define('SITE_URL', getenv('APP_BASE_URL') ?: 'http://localhost/Astha');
define('TOPUP_URL', getenv('TOPUP_URL') ?: 'https://nishavmansinghpradhan.com/Astha');

// Wallet Configuration
define('WATER_BLOCK_LITERS', 1000);
define('WATER_BLOCK_COST_PAISA', 3200); // Rs 32 = 3200 paisa
define('WALLET_SUSPEND_THRESHOLD_PAISA', -100000); // -Rs 1000

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

// PHPMailer Autoload (if installed via Composer)
$autoloadPath = __DIR__ . '/vendor/autoload.php';
if (file_exists($autoloadPath)) {
    require_once $autoloadPath;
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
 * Get base URL for absolute redirects and callbacks
 * @return string
 */
function getBaseUrl() {
    $env = getenv('APP_BASE_URL');
    if ($env && filter_var($env, FILTER_VALIDATE_URL)) {
        return rtrim($env, '/');
    }
    return rtrim(SITE_URL, '/');
}

/**
 * Convert NPR to paisa
 * @param float $npr
 * @return int
 */
function nprToPaisa($npr) {
    return (int) round(((float) $npr) * 100);
}

/**
 * Format paisa to NPR string
 * @param int $paisa
 * @return string
 */
function formatNpr($paisa) {
    return 'Rs ' . number_format(((int) $paisa) / 100, 2, '.', '');
}

/**
 * Get Khalti base API URL
 * @return string
 */
function getKhaltiApiBase() {
    $env = strtolower(getenv('KHALTI_ENV') ?: 'sandbox');
    return $env === 'live'
        ? 'https://khalti.com/api/v2/epayment/'
        : 'https://dev.khalti.com/api/v2/epayment/';
}

/**
 * Send email via SMTP (Hostinger)
 * @return bool
 */
function sendEmail($toEmail, $toName, $subject, $htmlBody, $textBody = '') {
    if (!class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
        error_log('PHPMailer not installed. Run: composer require phpmailer/phpmailer');
        return false;
    }

    try {
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = getenv('SMTP_HOST') ?: 'smtp.hostinger.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = getenv('SMTP_USER') ?: '';
        $mail->Password   = getenv('SMTP_PASS') ?: '';
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = (int) (getenv('SMTP_PORT') ?: 587);

        $fromEmail = getenv('SMTP_FROM_EMAIL') ?: 'gooddream@nishavmansinghpradhan.com';
        $fromName = getenv('SMTP_FROM_NAME') ?: 'Astha Water Alerts';

        $mail->setFrom($fromEmail, $fromName);
        $mail->addAddress($toEmail, $toName);
        $mail->addReplyTo($fromEmail, $fromName);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $htmlBody;
        $mail->AltBody = $textBody ?: strip_tags($htmlBody);

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('Mail error: ' . $e->getMessage());
        return false;
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
