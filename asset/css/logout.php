<?php
require_once 'config.php';

// Check if user is logged in
if (isLoggedIn()) {
    // Log the action
    logAction($_SESSION['user_id'], "User logged out");
    
    // Destroy session
    session_unset();
    session_destroy();
    
    // Redirect to login page
    redirect('login.php', 'You have been logged out successfully', 'success');
} else {
    // If not logged in, redirect to home page
    redirect('index.php');
}
?>