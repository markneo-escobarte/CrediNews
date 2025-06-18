<?php
require_once 'config.php';


if (isLoggedIn()) {
    logAction($_SESSION['user_id'], "User logged out");
    
    
    session_unset();
    session_destroy();
    
    
    redirect('login.php', 'You have been logged out successfully', 'success');
} else {
    
    redirect('index.php');
}
?>