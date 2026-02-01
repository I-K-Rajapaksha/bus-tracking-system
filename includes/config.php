<?php
/**
 * Configuration File
 * Bus Arrival and Departure Tracking System
 * Makumbura Multimodal Center
 */

// Prevent direct access
if (!defined('APP_NAME')) {
    define('APP_NAME', 'Bus Tracking System - Makumbura MMC');
}

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');  // Change this to your MySQL username
define('DB_PASS', '');      // Change this to your MySQL password
define('DB_NAME', 'terminal_tracking_system');
define('DB_CHARSET', 'utf8mb4');

// Application Settings
define('SITE_URL', 'http://localhost/Terminal%20POS');
define('TIMEZONE', 'Asia/Colombo');
define('DATE_FORMAT', 'Y-m-d H:i:s');
define('SESSION_TIMEOUT', 3600); // 1 hour in seconds

// Security Settings
define('HASH_ALGO', PASSWORD_BCRYPT);
define('HASH_COST', 10);

// User Roles
define('ROLE_SUPER_ADMIN', 'super_admin');
define('ROLE_TERMINAL_IN', 'terminal_in_operator');
define('ROLE_TERMINAL_OUT', 'terminal_out_operator');
define('ROLE_REPORT_VIEWER', 'report_viewer');

// Set timezone
date_default_timezone_set(TIMEZONE);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Auto-logout on session timeout
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
    session_unset();
    session_destroy();
    header('Location: ' . SITE_URL . '/login.php?timeout=1');
    exit;
}
$_SESSION['last_activity'] = time();
?>
