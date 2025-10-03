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

// --- Fetch employee rate and deductions ---
$employeeRate = getDailyRateForAttendance($conn, $id_no, $pay_schedule);

$employeeDeductions = getEmployeeDeductions($conn, $id_no);

$daily_rate = $employeeRate['daily_rate'] ?? 0;

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

$sundayRates = [
    'regular_multiplier'  => $allRates['sunday_regular'] ?? 1.3,
    'overtime_multiplier' => $allRates['sunday_overtime'] ?? 1.69
];

// --- Initialize payroll totals ---
$basicSalary        = 0;
$overtimePayTotal   = 0;
$overtimeHoursTotal = 0;
$restDayPay         = 0;
$regularHolidayPay  = 0;
$regularOtPay       = 0;
$specialHolidayPay  = 0;
$undertimeDeduction = 0;

$attendanceCalculated = ['id_no' => $id_no, 'days' => []];

// --- Loop through each work date ---
foreach ($work_dates as $workDate) {
    $dayData   = $attendanceData['days'][$workDate] ?? [];
    $isSunday  = $dayData['is_sunday'] ?? false;
    $otHours   = (float)($dayData['ot'] ?? 0);
    $utHours   = (float)($dayData['ut'] ?? 0);
    $holiday_id   = $dayData['holiday_id'] ?? null;
    $holiday_type = $dayData['holiday_type'] ?? '';

    // Get multipliers
    $multipliers = getHolidayMultipliers(
        $workDate,
        $isSunday,
        ['holiday_type' => $holiday_type],
        $sundayRates
    );

    // Daily pay calculations
    $dailyBasic = $daily_rate * ($multipliers['regular'] ?? 1);
    $dailyOt    = ($daily_rate / 8) * $otHours * ($multipliers['overtime'] ?? 1.25);
    $dailyUt    = ($daily_rate / 8) * $utHours;

    $basicSalary        += $dailyBasic;
    $overtimePayTotal   += $dailyOt;
    $overtimeHoursTotal += $otHours;
    $undertimeDeduction += $dailyUt;

    // Rest day pay
    if ($isSunday) {
        $restDayPay += $dailyBasic * ($multipliers['restdayholiday_regular'] ?? 0);
    }

    // Holiday pay
    if ($holiday_type === 'Regular') {
        $regularHolidayPay += $dailyBasic * ($multipliers['regular_rate'] ?? 1);
        $regularOtPay      += $dailyOt * ($multipliers['restdayholiday_overtime'] ?? 1.25);
    } elseif ($holiday_type === 'Special') {
        $specialHolidayPay += $dailyBasic * ($multipliers['restdayholiday_special'] ?? 1.5);
    }

    // Store in new JSON
    $attendanceCalculated['days'][$workDate] = [
        'ot'                  => $otHours,
        'ut'                  => $utHours,
        'is_sunday'           => $isSunday,
        'holiday_id'          => $holiday_id,
        'holiday_type'        => $holiday_type,
        'multipliers'         => $multipliers,
        'regular_pay'         => $dailyBasic,
        'overtime_pay'        => $dailyOt,
        'undertime_deduction' => $dailyUt
    ];
}

$attendanceJsonEncoded = json_encode($attendanceCalculated, JSON_PRETTY_PRINT);


// Total earnings & deductions
$totalEarnings   = $basicSalary + $overtimePayTotal + $restDayPay + $regularHolidayPay + $regularOtPay + $specialHolidayPay;
$totalDeductions = $sss_premium + $sss_loan + $pagibig_premium + $pagibig_loan + $philhealth + $cash_advance + $undertimeDeduction;
$netPay          = $totalEarnings - $totalDeductions;

// Encode attendance JSON
$attendanceJsonEncoded = json_encode($attendanceData, JSON_PRETTY_PRINT);

// Optional batch ID
$batchId = 'PAY-' . date('YmdHis');
// Optional: precompute overtime rate
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
$absent_deduction     = 0.00;
$archived             = 0;
$thirteenth_month_pay = 0.00;

// Overtime rate precomputed
$overtimeRate = $multipliers['overtime'] ?? 1.25;
// List of columns you're inserting
// $columns_array = [
//     'batch_id','employee_id','name','department','pay_period','start_date','end_date',
//     'basic_salary','overtime_pay','overtime_hours','overtime_rate','rest_day_pay',
//     'regular_holiday_pay','regular_ot_pay','special_holiday_pay',
//     'late_deduction','absent_deduction','undertime_deduction',
//     'total_earnings','total_deductions','net_pay','archived',
//     'sss_no','pagibig_no','tin_no','philhealth_no',
//     'thirteenth_month_pay','sss_premium','sss_loan','pagibig_premium','pagibig_loan',
//     'philhealth','cash_advance','leave_with_pay','leave_without_pay','available_leave'
// ];

// // List of values you're binding
// $values_array = [
//     $batchId,
//     $id_no,
//     $name,
//     $department,
//     $pay_schedule,
//     $start_date,
//     $end_date,
//     $basicSalary,
//     $overtimePayTotal,
//     $overtimeHoursTotal,
//     $overtimeRate,
//     $restDayPay,
//     $regularHolidayPay,
//     $regularOtPay,
//     $specialHolidayPay,
//     $late_deduction,
//     $absent_deduction,
//     $undertimeDeduction,
//     $totalEarnings,
//     $totalDeductions,
//     $netPay,
//     $archived,
//     $sss_no,
//     $pagibig_no,
//     $tin_no,
//     $philhealth_no,
//     $thirteenth_month_pay,
//     $sss_premium,
//     $sss_loan,
//     $pagibig_premium,
//     $pagibig_loan,
//     $philhealth,
//     $cash_advance,
//     $leave_with_pay,
//     $leave_without_pay,
//     $available_leave
// ];
// echo "<pre>";
// var_dump([
//     $batchId,
//     $id_no,
//     $name,
//     $department,
//     $pay_schedule,
//     $start_date,
//     $end_date,
//     $basicSalary,
//     $overtimePayTotal,
//     $overtimeHoursTotal,
//     $overtimeRate,
//     $restDayPay,
//     $regularHolidayPay,
//     $regularOtPay,
//     $specialHolidayPay,
//     $late_deduction,
//     $absent_deduction,
//     $undertimeDeduction,
//     $totalEarnings,
//     $totalDeductions,
//     $netPay,
//     $archived,
//     $sss_no,
//     $pagibig_no,
//     $tin_no,
//     $philhealth_no,
//     $thirteenth_month_pay,
//     $sss_premium,
//     $sss_loan,
//     $pagibig_premium,
//     $pagibig_loan,
//     $philhealth,
//     $cash_advance,
//     $leave_with_pay,
//     $leave_without_pay,
//     $available_leave
// ]);
// echo "</pre>";


// // Debug counts
// echo "Columns: " . count($columns_array) . "<br>";
// echo "Values: " . count($values_array) . "<br>";


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
    header("Location: manual.php?msg=success");
    exit();
} else {
    $error = $stmt->error;

    $stmt->close();
    die("Database Error: $error");
}


$conn->close();
