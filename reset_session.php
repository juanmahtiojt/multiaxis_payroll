<?php
session_start();

// Update last activity time
if (isset($_SESSION['user'])) {
    $_SESSION['last_activity'] = time();
    echo "success";
} else {
    // If no active session, redirect to login
    echo "expired";
}
?>