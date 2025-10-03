<?php
// save_manual_attendance.php
include 'config.php';
include 'functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_no        = $_POST['id_no'] ?? '';
    $department   = $_POST['department'] ?? '';
    $name         = $_POST['name'] ?? '';
    $pay_schedule = $_POST['pay_schedule'] ?? '';
    $start_date   = $_POST['time_in'] ?? null;
    $end_date     = $_POST['time_out'] ?? null;
    $work_days_count = (int)($_POST['work_days_count'] ?? 0);

    $ot_hours_arr = $_POST['ot_hours'] ?? [];
    $ut_hours_arr = $_POST['ut_hours'] ?? [];

    // Calculate totals
    $ot_hours_total = array_sum($ot_hours_arr);
    $ut_hours_total = array_sum($ut_hours_arr);

    // Build JSON structure
    $attendance = [
        "id_no" => $id_no,
        "days" => []
    ];

    $attendanceData = [];
    $totalRegular   = 0;
    $totalOvertime  = 0;
    $totalUndertime = 0;

    foreach ($_POST['work_dates'] as $workDate) {
        $isSunday = ($_POST['isSunday'][$workDate] ?? 0) == 1;

        // Build holiday info from form
        $holidayInfo = [
            "holiday_id"   => $_POST['holiday_id'][$workDate] ?? null,
            "holiday_type" => $_POST['holiday_type'][$workDate] ?? null
        ];

        // Get multipliers from posted hidden inputs
        $postedMultipliers = [
            "regular_rate"                   => $_POST['regular_rate'][$workDate] ?? 1,
            "overtime_rate"                  => $_POST['overtime_rate'][$workDate] ?? 1.25,
            "restdayholiday_regular"         => $_POST['restdayholiday_regular'][$workDate] ?? 0,
            "restdayholiday_special"         => $_POST['restdayholiday_special'][$workDate] ?? 0,
            "restdayholiday_overtime"        => $_POST['restdayholiday_overtime'][$workDate] ?? 0,
            "restdayspecialholiday_overtime" => $_POST['restdayspecialholiday_overtime'][$workDate] ?? 0,
        ];

        // Get effective multipliers
        $multipliers = getHolidayMultipliers($workDate, $isSunday, $holidayInfo, $postedMultipliers);

        $otHours = (float)($_POST['ot_hours'][$workDate] ?? 0);
        $utHours = (float)($_POST['ut_hours'][$workDate] ?? 0);
        
        $daily_rate = getEmployeeRate($conn, $id_no, $pay_schedule);

        // Salary computation
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
        "days" => $attendanceData,
        "id_no" => $id_no
    ];

    $attendance_json = json_encode($finalAttendance, JSON_PRETTY_PRINT);


    if ($id_no && $name && $work_days_count > 0) {
        $check = $conn->prepare("SELECT id FROM manual_attendance WHERE id_no = ?");
        $check->bind_param("s", $id_no);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $check->close();
            $conn->close();
            header("Location: manual.php?msg=duplicate");
            exit();
        }
        $check->close();

        $stmt = $conn->prepare("
            INSERT INTO manual_attendance 
            (id_no, department, name, pay_schedule, start_date, end_date, work_days_count, ot_hours, ut_hours, attendance_data) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->bind_param(
            "ssssssiiis",
            $id_no,
            $department,
            $name,
            $pay_schedule,
            $start_date,
            $end_date,
            $work_days_count,
            $ot_hours_total,
            $ut_hours_total,
            $attendance_json
        );

        $stmt->execute();
        $stmt->close();
        $conn->close();

        header("Location: manual.php?msg=success");
        exit();
    } else {
        header("Location: manual.php?msg=error");
        exit();
    }
} else {
    header("Location: manual.php");
    exit();
}
