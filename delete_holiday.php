<?php
session_start();
include __DIR__ . "/config.php";

if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}
// Log activity
$username = $_SESSION['user'];
$activity = "Delete a holiday";
$page = basename(__FILE__);
$ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown IP';
$timestamp = date('Y-m-d H:i:s');
$stmt = $conn->prepare("INSERT INTO activity_logs (username, activity, page, ip_address, timestamp) VALUES (?, ?, ?, ?, ?)");
if ($stmt) {
    $stmt->bind_param("sssss", $username, $activity, $page, $ip_address, $timestamp);
    $stmt->execute();
    $stmt->close();
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['holiday_date'])) {
    $holiday_date = $_POST['holiday_date'];

    $stmt = $conn->prepare("DELETE FROM mathipms.holidays WHERE holiday_date = ?");
    $stmt->bind_param("s", $holiday_date);

    if ($stmt->execute()) {
        header("Location: add_holiday.php");
        exit();
    } else {
        echo "❌ Error deleting holiday: " . $stmt->error;
    }

    $stmt->close();
} else {
    echo "⚠️ Invalid request.";
}
?>
