<?php
session_start();
include __DIR__ . "/config.php";
include_once "functions.php";

if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Log activity
$username = $_SESSION['user'];
$activity = "Delete an employee";
$page = basename(__FILE__);
$ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown IP';
$timestamp = date('Y-m-d H:i:s');
$stmt = $conn->prepare("INSERT INTO activity_logs (username, activity, page, ip_address, timestamp) VALUES (?, ?, ?, ?, ?)");
if ($stmt) {
    $stmt->bind_param("sssss", $username, $activity, $page, $ip_address, $timestamp);
    $stmt->execute();
    $stmt->close();
}

if (isset($_GET['id'])) {
    $employee_id = $_GET['id'];

    // Optional: fetch employee name before delete for logging
    $emp_name = "Unknown";
    $result = $conn->query("SELECT name FROM employees WHERE employee_id = '$employee_id'");
    if ($row = $result->fetch_assoc()) {
        $emp_name = $row['name'];
    }

    $delete_query = "DELETE FROM employees WHERE employee_id = '$employee_id'";
    if (mysqli_query($conn, $delete_query)) {
        log_activity($conn, $_SESSION['user'], "Deleted employee: $emp_name (ID: $employee_id)", "add_user.php");

        // ✅ Redirect back to refresh list
        header("Location: add_user.php?status=deleted");
        exit();
    } else {
        echo "Error: " . mysqli_error($conn);
    }
} else {
    echo "Invalid employee ID.";
    exit();
}
