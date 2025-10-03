<?php
include __DIR__ . "/config.php";
include __DIR__ . "/functions.php";
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

// Log activity
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
}

$id           = intval($_POST['id']);
$id_no        = $_POST['id_no'] ?? '';
$department   = $_POST['department'] ?? '';
$name         = $_POST['name'] ?? '';
$pay_schedule = $_POST['pay_schedule'] ?? '';
$start_date   = $_POST['start_date'] ?? null;
$end_date     = $_POST['end_date'] ?? null;
$work_days_count = (int)($_POST['work_days_count'] ?? 0);

$ot_hours_arr = $_POST['ot_hours'] ?? [];
$ut_hours_arr = $_POST['ut_hours'] ?? [];

$totalRegular   = 0;
$totalOvertime  = 0;
$totalUndertime = 0;
$attendanceData = [];

foreach ($_POST['work_dates'] as $workDate) {
    $isSunday = ($_POST['isSunday'][$workDate] ?? 0) == 1;

    $holidayInfo = [
        "holiday_id"   => $_POST['holiday_id'][$workDate] ?? null,
        "holiday_type" => $_POST['holiday_type'][$workDate] ?? null
    ];

    $postedMultipliers = [
        "regular_rate"                   => $_POST['regular_rate'][$workDate] ?? 1,
        "overtime_rate"                  => $_POST['overtime_rate'][$workDate] ?? 1.25,
        "restdayholiday_regular"         => $_POST['restdayholiday_regular'][$workDate] ?? 0,
        "restdayholiday_special"         => $_POST['restdayholiday_special'][$workDate] ?? 0,
        "restdayholiday_overtime"        => $_POST['restdayholiday_overtime'][$workDate] ?? 0,
        "restdayspecialholiday_overtime" => $_POST['restdayspecialholiday_overtime'][$workDate] ?? 0,
    ];

    $multipliers = getHolidayMultipliers($workDate, $isSunday, $holidayInfo, $postedMultipliers);

    $otHours = (float)($_POST['ot_hours'][$workDate] ?? 0);
    $utHours = (float)($_POST['ut_hours'][$workDate] ?? 0);

    $daily_rate = getEmployeeRate($conn, $id_no, $pay_schedule);

    $regularPay         = $daily_rate * $multipliers['regular'];
    $overtimePay        = ($daily_rate / 8) * $otHours * $multipliers['overtime'];
    $undertimeDeduction = ($daily_rate / 8) * $utHours;

    $totalRegular   += $regularPay;
    $totalOvertime  += $overtimePay;
    $totalUndertime += $undertimeDeduction;

    $attendanceData[$workDate] = [
        "ot"                  => $otHours,
        "ut"                  => $utHours,
        "is_sunday"           => $isSunday,
        "holiday_id"          => $holidayInfo['holiday_id'],
        "holiday_type"        => $holidayInfo['holiday_type'],
        "multipliers"         => $postedMultipliers,
        "regular_pay"         => round($regularPay, 2),
        "overtime_pay"        => round($overtimePay, 2),
        "undertime_deduction" => round($undertimeDeduction, 2)
    ];
}

$finalAttendance = [
    "days"  => $attendanceData,
    "id_no" => $id_no
];

$attendance_json = json_encode($finalAttendance, JSON_PRETTY_PRINT);

// Calculate totals
$ot_hours_total = array_sum($ot_hours_arr);
$ut_hours_total = array_sum($ut_hours_arr);

$stmt = $conn->prepare("UPDATE manual_attendance
    SET id_no=?, department=?, name=?, pay_schedule=?, 
        start_date=?, end_date=?, work_days_count=?, 
        ot_hours=?, ut_hours=?, attendance_data=? 
    WHERE id=?");

$stmt->bind_param(
    "ssssssiiisi",
    $id_no,
    $department,
    $name,
    $pay_schedule,
    $start_date,
    $end_date,
    $work_days_count,
    $ot_hours_total,
    $ut_hours_total,
    $attendance_json,
    $id
);

if ($stmt->execute()) {
    header("Location: manual.php?msg=updated");
    exit();
} else {
    echo "Error updating record: " . $stmt->error;
}
