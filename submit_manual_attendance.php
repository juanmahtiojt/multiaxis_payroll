<?php
// submit_manual_attendance.php
session_start();
include 'config.php';
include 'functions.php';


$id = $_POST['id'] ?? 0;

// Fetch the row from the DB
$stmt = $conn->prepare("SELECT * FROM manual_attendance WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$stmt->close();

if (!$row) {
    header("Location: manual.php?msg=error");
    exit();
}

// Decode attendance JSON
$attendanceData = json_decode($row['attendance_data'], true);
$work_dates = array_keys($attendanceData['days'] ?? []);

$id_no        = $row['id_no'];
$name         = $row['name'];
$department   = $row['department'];
$pay_schedule = $row['pay_schedule'];
$start_date   = $row['start_date'];
$end_date     = $row['end_date'];
$attendanceData = json_decode($row['attendance_data'], true);
$work_dates   = array_keys($attendanceData['days'] ?? []);

$daily_rate = getDailyRateForAttendance($conn, $id_no, $pay_schedule);

// DEBUG
echo "ID No: $id_no<br>";
echo "Pay Schedule: $pay_schedule<br>";
echo "Daily Rate: $daily_rate<br>";
if ($daily_rate == 0) {
    echo "⚠️ WARNING: Daily rate is 0! Check if this employee/pay_schedule exists in daily_rate table<br>";
}

$employeeDeductions = getEmployeeDeductions($conn, $id_no);



$sss_no          = $employeeDeductions['sss_no'] ?? '';
$pagibig_no      = $employeeDeductions['pagibig_no'] ?? '';
$tin_no          = $employeeDeductions['tin_no'] ?? '';
$philhealth_no   = $employeeDeductions['philhealth_no'] ?? '';

$sss_premium     = (float)($employeeDeductions['sss_premium'] ?? 0);
$sss_loan        = (float)($employeeDeductions['sss_loan'] ?? 0);
$pagibig_premium = (float)($employeeDeductions['pagibig_premium'] ?? 0);
$pagibig_loan    = (float)($employeeDeductions['pagibig_loan'] ?? 0);
$philhealth      = (float)($employeeDeductions['philhealth'] ?? 0);
$cash_advance    = (float)($employeeDeductions['cash_advance'] ?? 0);

$leave_with_pay    = (int)($employeeDeductions['leave_with_pay'] ?? 0);
$leave_without_pay = (int)($employeeDeductions['leave_without_pay'] ?? 0);
$available_leave   = (int)($employeeDeductions['available_leave'] ?? 0);

// --- Fetch rates and holidays ---
$ratesData = getRates($conn);
$allRates  = $ratesData['rates'] ?? [];
$holidays  = $ratesData['holidays'] ?? [];

// ✅ FIXED: Create proper multipliers array with ALL needed keys
$defaultMultipliers = [
    'regular_rate'  => 1.0,
    'overtime_rate' => 1.25,
    'sunday_regular' => 1.3,
    'sunday_overtime' => 1.69,
    'restdayholiday_regular' => 2.0,
    'restdayholiday_overtime' => 2.6,
    'restdayholiday_special' => 1.3,
    'restdayspecialholiday_overtime' => 1.69
];

// --- Initialize payroll totals ---
$basicSalary        = $daily_rate * 6;  // ✅ basic_salary = daily_rate * 8 (saved to DB)
$dailyBasic   = 0; // only Mon-Sat
$sundayBasic  = 0; // separate Sundays
$sundayOtPay        = 0;
$overtimePayTotal   = 0;
$overtimeHoursTotal = 0;
$restDayPay         = 0;
$regularHolidayPay  = 0;
$regularOtPay       = 0;
$specialHolidayPay  = 0;
$undertimeDeduction = 0;
$work_days_count    = 0;

$attendanceCalculated = ['id_no' => $id_no, 'days' => []];

// 
// Initialize overtime category totals
$otNormalTotal   = 0;
$otSundayTotal   = 0;
$otRestdayTotal  = 0;
$otRegularHolidayTotal = 0;
$otSpecialHolidayTotal = 0;

foreach ($work_dates as $workDate) {
    $dayData   = $attendanceData['days'][$workDate] ?? [];
    $isSunday  = $dayData['is_sunday'] ?? false;
    $otHours   = (float)($dayData['ot'] ?? 0);
    $utHours   = (float)($dayData['ut'] ?? 0);
    $holiday_id   = $dayData['holiday_id'] ?? null;
    $holiday_type = $dayData['holiday_type'] ?? '';

    $work_days_count++;

    $multipliers = getHolidayMultipliers(
        $workDate,
        $isSunday,
        ['holiday_type' => $holiday_type],
        $defaultMultipliers
    );

    // Compute base pay and OT pay
    $dailyPayWithMultiplier = $daily_rate * ($multipliers['regular_rate'] ?? 1);
    $dailyOt = ($daily_rate / 8) * $otHours * ($multipliers['overtime_rate'] ?? 1.25);
    $dailyUt = 50 * $utHours;


    // Totals
    $dailyBasic         += $daily_rate;
    $overtimePayTotal   += $dailyOt;
    $overtimeHoursTotal += $otHours;
    $undertimeDeduction += $dailyUt;

    // --- SEPARATE OVERTIME PAY CATEGORIES ---
    if (!empty($holiday_type)) {
        if ($holiday_type === 'Regular') {
            $otRegularHolidayTotal += $dailyOt;
        } elseif ($holiday_type === 'Special') {
            $otSpecialHolidayTotal += $dailyOt;
        }
    } elseif ($isSunday) {
        $otSundayTotal += $dailyOt;
         $sundayOtPay   += $dailyOt;
    } elseif (isset($dayData['is_restday']) && $dayData['is_restday'] === true) {
        $otRestdayTotal += $dailyOt;
    } else {
        $otNormalTotal += $dailyOt;
    }

    // --- Premiums / Adjustments ---
    if ($isSunday && empty($holiday_type)) {
        $restDayPay += $daily_rate * (($multipliers['regular'] ?? 1.3) - 1) + $daily_rate;
        $dailyBasic -= $daily_rate;
    }

    if ($holiday_type === 'Regular') {
        $regularHolidayPay += $daily_rate * (($multipliers['regular'] ?? 2) - 1);
        $regularOtPay      += $dailyOt * (($multipliers['overtime'] ?? 2.6) / 1.25 - 1);
        $dailyBasic -= $daily_rate;
    } elseif ($holiday_type === 'Special') {
        $specialHolidayPay += $daily_rate * (($multipliers['regular'] ?? 1.3) - 1);
    }

    // Store JSON
    $attendanceCalculated['days'][$workDate] = [
        'ot'                  => $otHours,
        'ut'                  => $utHours,
        'is_sunday'           => $isSunday,
        'holiday_id'          => $holiday_id,
        'holiday_type'        => $holiday_type,
        'multipliers'         => $multipliers,
        'regular_pay'         => $dailyPayWithMultiplier,
        'overtime_pay'        => $dailyOt,
        'undertime_deduction' => $dailyUt
    ];
}

$attendanceJsonEncoded = json_encode($attendanceCalculated, JSON_PRETTY_PRINT);

// Compute totals as usual
$totalEarnings   = $dailyBasic + $otNormalTotal + $regularHolidayPay + $restDayPay +  $sundayOtPay +$regularOtPay + $specialHolidayPay;
$totalDeductions = $sss_premium + $sss_loan + $pagibig_premium + $pagibig_loan + $philhealth + $cash_advance + $undertimeDeduction;
$netPay          = $totalEarnings - $totalDeductions;
$absent_deduction = $basicSalary - $dailyBasic;
$overtimePayTotal = $otNormalTotal + $sundayOtPay;


// DEBUG OUTPUT (REMOVE AFTER TESTING)
// juannamary :)
// echo "<pre>";
// echo "Work Days: $work_days_count\n";
// echo "Daily Rate: $daily_rate\n";
// echo "Basic Salary (rate*6): $basicSalary\n";
// echo "Daily Basic (rate*days): $dailyBasic\n";
// echo "Overtime Pay: $otNormalTotal $overtimePayTotal\n";
// echo "Undertime Deduction: $undertimeDeduction\n";
// echo "Absent Deduction: $absent_deduction\n";
// echo "Rest Day Pay: $restDayPay\n";
// echo "Rest OT Pay: $sundayOtPay\n";
// echo "Regular Holiday Pay: $regularHolidayPay\n";
// echo "Regular OT Pay:  $regularOtPay\n";
// echo "Special Holiday Pay: $specialHolidayPay\n";
// echo "Total Earnings: $totalEarnings\n";
// echo "Total Deduction: $totalDeductions\n";
// echo "Net Pay: $netPay\n";
// echo "</pre>";
// die();


$batchId = 'PAY-' . date('YmdHis');

// --- Duplication Checker (same id_no + start_date + end_date) ---
$checkStmt = $conn->prepare("
    SELECT COUNT(*) as cnt 
    FROM payroll_records 
    WHERE employee_id = ? AND start_date = ? AND end_date = ?
");
$checkStmt->bind_param("sss", $row['id_no'], $row['start_date'], $row['end_date']);
$checkStmt->execute();
$checkResult = $checkStmt->get_result();
$check = $checkResult->fetch_assoc();
$checkStmt->close();

if ($check['cnt'] > 0) {
    // Duplicate found
    header("Location: manual.php?msg=duplicate");
    exit();
}

// --- Insert into payroll_records ---
$stmt = $conn->prepare("
    INSERT INTO payroll_records
    (batch_id, employee_id, name, department, pay_period, start_date, end_date,
     basic_salary, overtime_pay, overtime_hours, overtime_rate, rest_day_pay,
     regular_holiday_pay, regular_ot_pay, special_holiday_pay,
     late_deduction, absent_deduction, undertime_deduction,
     total_earnings, total_deductions, net_pay, archived,
     sss_no, pagibig_no, tin_no, philhealth_no,
     thirteenth_month_pay, sss_premium, sss_loan, pagibig_premium, pagibig_loan,
     philhealth, cash_advance, leave_with_pay, leave_without_pay, available_leave)
    VALUES ( ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

// Prepare default values for missing columns
$late_deduction       = 0.00;
$archived             = 0;
$thirteenth_month_pay = 0.00;

// Overtime rate precomputed
$overtimeRate = $multipliers['overtime'] ?? 1.25;

// Bind parameters: s = string, d = double/decimal, i = integer
$stmt->bind_param(
    "sssssssddddddddddddddissssdddddddiii",
    $batchId,
    $id_no,
    $name,
    $department,
    $pay_schedule,
    $start_date,
    $end_date,
    $basicSalary,
    $overtimePayTotal,
    $overtimeHoursTotal,
    $overtimeRate,
    $restDayPay,
    $regularHolidayPay,
    $regularOtPay,
    $specialHolidayPay,
    $late_deduction,
    $absent_deduction,
    $undertimeDeduction,
    $totalEarnings,
    $totalDeductions,
    $netPay,
    $archived,
    $sss_no,
    $pagibig_no,
    $tin_no,
    $philhealth_no,
    $thirteenth_month_pay,
    $sss_premium,
    $sss_loan,
    $pagibig_premium,
    $pagibig_loan,
    $philhealth,
    $cash_advance,
    $leave_with_pay,
    $leave_without_pay,
    $available_leave
);

// Execute and check
if ($stmt->execute()) {
    $stmt->close();

    $archiveStmt = $conn->prepare("UPDATE manual_attendance SET archived = 1 WHERE id = ?");
    $archiveStmt->bind_param("i", $id);
    $archiveStmt->execute();
    $archiveStmt->close();

    header("Location: manual.php?msg=success");
    exit();
}
 else {
    $error = $stmt->error;

    $stmt->close();
    die("Database Error: $error");
}


$conn->close();
