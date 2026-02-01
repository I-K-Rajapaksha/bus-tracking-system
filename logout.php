<?php
/**
 * Logout Script
 */

session_start();

// Log the logout if database is available
if (isset($_SESSION['user_id'])) {
    require_once 'includes/config.php';
    require_once 'includes/database.php';
    require_once 'includes/functions.php';
    
    $db = new Database();
    logAudit($db, 'LOGOUT', 'users', $_SESSION['user_id'], 'User logged out');
}

// Clear session
session_unset();
session_destroy();

// Redirect to login
header('Location: login.php');
exit;
?>
