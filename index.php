<?php
/**
 * Index Page - Redirect to appropriate page
 */

session_start();

// If logged in, go to dashboard, otherwise go to login
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
} else {
    header('Location: login.php');
}
exit;
?>
