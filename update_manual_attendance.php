<?php
include __DIR__ . "/config.php";
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}
// activity log
$username = $_SESSION['user'];
$activity = "Update employee manual attendance";
$page = basename(__FILE__);
$ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown IP';
$timestamp = date('Y-m-d H:i:s');

$stmt = $conn->prepare("INSERT INTO activity_logs (username, activity, page, ip_address, timestamp) VALUES (?, ?, ?, ?, ?)");
if ($stmt) {
    $stmt->bind_param("sssss", $username, $activity, $page, $ip_address, $timestamp);
    $stmt->execute();
    $stmt->close();
} else {
    // Optionally log error or handle it here
}

$id = intval($_POST['id']);
$ot = $_POST['ot'] ?? [];
$ut = $_POST['ut'] ?? [];
$new_dates = $_POST['new_date'] ?? [];
$new_ots   = $_POST['new_ot'] ?? [];
$new_uts   = $_POST['new_ut'] ?? [];



$data = [];

// Existing OT/UT rows
foreach ($ot as $date => $hours) {
    if (!isset($data[$date])) $data[$date] = [];
    $data[$date]['ot'] = (int)$hours;
}
foreach ($ut as $date => $hours) {
    if (!isset($data[$date])) $data[$date] = [];
    $data[$date]['ut'] = (int)$hours;
}

// Newly added rows
foreach ($new_dates as $i => $date) {
    if (empty($date)) continue; // skip blank
    if (!isset($data[$date])) $data[$date] = [];
    $data[$date]['ot'] = (int)($new_ots[$i] ?? 0);
    $data[$date]['ut'] = (int)($new_uts[$i] ?? 0);
}

// Calculate totals
$ot_hours = 0;
$ut_hours = 0;
foreach ($data as $day) {
    $ot_hours += (int)($day['ot'] ?? 0);
    $ut_hours += (int)($day['ut'] ?? 0);
}
$work_days_count = count($data);
$attendanceDataJson = json_encode($data);

$stmt = $conn->prepare("UPDATE manual_attendance 
                        SET start_date=?, 
                            end_date=?, 
                            attendance_data=?, 
                            ot_hours=?, 
                            ut_hours=?, 
                            work_days_count=? 
                        WHERE id=?");
$stmt->bind_param(
    "sssiiii", 
    $_POST['start_date'], 
    $_POST['end_date'], 
    $attendanceDataJson, 
    $ot_hours, 
    $ut_hours, 
    $work_days_count, 
    $id
);


if ($stmt->execute()) {
    header("Location: manual.php?success=1");
    exit();
} else {
    echo "Error updating record: " . $stmt->error;
}
