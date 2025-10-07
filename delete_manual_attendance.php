<?php
include 'config.php';
// Log activity
$username = $_SESSION['user'];
$activity = "Permanently delete a manual attendance";
$page = basename(__FILE__);
$ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown IP';
$timestamp = date('Y-m-d H:i:s');
$stmt = $conn->prepare("INSERT INTO activity_logs (username, activity, page, ip_address, timestamp) VALUES (?, ?, ?, ?, ?)");
if ($stmt) {
    $stmt->bind_param("sssss", $username, $activity, $page, $ip_address, $timestamp);
    $stmt->execute();
    $stmt->close();
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? 0;

    if ($id > 0) {
        $stmt = $conn->prepare("DELETE FROM manual_attendance WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            header("Location: manual.php?msg=deleted");
            exit;
        } else {
            header("Location: manual.php?msg=delete_error");
            exit;
        }
        $stmt->close();
    } else {
        header("Location: manual.php?msg=delete_error");
        exit;
    }
}
?>
