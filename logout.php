<?php
/**
 * ASTHA - MELAMCHI WATER ALERT SYSTEM
 * Logout Handler
 */

session_start();
session_unset();
session_destroy();

// Clear session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

header('Location: index.php');
exit;
?>
