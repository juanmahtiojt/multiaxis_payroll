<?php
session_start();
include __DIR__ . "/config.php";  // your DB connection

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['user'];
$activity = "Deleted all monthly attendance records";
$page = basename(__FILE__);
$ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown IP';
$timestamp = date('Y-m-d H:i:s');

// Insert log into activity_logs table
$stmt = $conn->prepare("INSERT INTO activity_logs (username, activity, page, ip_address, timestamp) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("sssss", $username, $activity, $page, $ip_address, $timestamp);
$stmt->execute();
$stmt->close();

// Clear session monthly attendance data
unset($_SESSION['attendance_data_monthly']);

header("Location: employee_attendance_monthly.php?msg=deleted");
exit();
?>
