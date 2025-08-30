<?php
session_start();
include __DIR__ . "/config.php";  // include your DB connection

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

// Set session variable for activity logging
$_SESSION['attendance_deleted'] = true;

// Clear session attendance data
unset($_SESSION['attendance_data']);

header("Location: employee_attendance.php");
exit();
?>
