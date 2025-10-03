<?php
session_start();
include __DIR__ . "/config.php";  // include your DB connection

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

// Optional: flag for activity logging
$_SESSION['attendance_deleted'] = true;

// Clear all attendance session data
unset($_SESSION['attendance_data']);
unset($_SESSION['attendance_data_monthly']);
unset($_SESSION['attendance_data_semi_monthly']);

// Redirect back to upload modal page
header("Location: upload_excel_monthly.php");
exit();
