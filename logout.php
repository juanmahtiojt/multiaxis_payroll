<?php
session_start();

// Log the logout event if you have logging enabled
if (isset($_SESSION['user'])) {
    include_once __DIR__ . "/config.php";
    
    if (function_exists('log_activity') && isset($conn)) {
        log_activity($conn, $_SESSION['user'], 'logout');
    }
}
date_default_timezone_set('Asia/Manila'); // or your correct timezone

// Capture data before session is destroyed
$username = $_SESSION['user'] ?? 'Unknown';
$ip_address = $_SERVER['REMOTE_ADDR'];
$timestamp = date('Y-m-d H:i:s');

// Insert logout log
if ($username !== 'Unknown') {
    $stmt = $conn->prepare("INSERT INTO user_logs (username, activity_type, timestamp, ip_address) VALUES (?, 'logout', ?, ?)");
    $stmt->bind_param("sss", $username, $timestamp, $ip_address);
    $stmt->execute();
    $stmt->close();
}
// Clear all session variables
$_SESSION = array();

// Destroy the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Redirect to login page
header("Location: login.php");
exit();
?>