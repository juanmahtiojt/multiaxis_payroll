<?php
include "config.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Only process POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed');
}

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    http_response_code(401);
    exit('Unauthorized');
}

// Get POST data
$activity = isset($_POST['activity']) ? mysqli_real_escape_string($conn, $_POST['activity']) : '';
$page = isset($_POST['page']) ? mysqli_real_escape_string($conn, $_POST['page']) : '';
$username = isset($_POST['username']) ? mysqli_real_escape_string($conn, $_POST['username']) : '';
$ip_address = isset($_POST['ip_address']) ? mysqli_real_escape_string($conn, $_POST['ip_address']) : '';
$timestamp = isset($_POST['timestamp']) ? mysqli_real_escape_string($conn, $_POST['timestamp']) : date('Y-m-d H:i:s');

// Validate required fields
if (empty($activity) || empty($username)) {
    http_response_code(400);
    exit('Missing required fields');
}

// Insert activity log
$sql = "INSERT INTO activity_logs (username, activity, page, ip_address, timestamp) 
        VALUES (?, ?, ?, ?, ?)";

$stmt = mysqli_prepare($conn, $sql);
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "sssss", $username, $activity, $page, $ip_address, $timestamp);
    
    if (mysqli_stmt_execute($stmt)) {
        http_response_code(200);
        echo 'Activity logged successfully';
    } else {
        http_response_code(500);
        echo 'Failed to log activity';
    }
    
    mysqli_stmt_close($stmt);
} else {
    http_response_code(500);
    echo 'Database error';
}

mysqli_close($conn);
?>
