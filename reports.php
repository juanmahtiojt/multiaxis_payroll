<?php
session_start();
include __DIR__ . "/config.php";

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['user'];
$role = $_SESSION['role'];
$current_page = basename($_SERVER['PHP_SELF']);

$employeeData = $_SESSION['attendance_data']
    ?? $_SESSION['attendance_data_monthly']
    ?? $_SESSION['attendance_data_semi_monthly']
    ?? [];

if (isset($_SESSION['attendance_data'])) {
    $formAction = "save_weekly_att.php";
} elseif (isset($_SESSION['attendance_data_monthly'])) {
    $formAction = "save_monthly_att.php";
} elseif (isset($_SESSION['attendance_data_semi_monthly'])) {
    $formAction = "save_semi-monthly_att.php";
} else {
    $formAction = "employee_attendance_monthly.php"; // fallback
}

$payPeriods = [];
$sql = "SELECT id_no, pay_schedule FROM daily_rate";
$result = $conn->query($sql);

while ($row = $result->fetch_assoc()) {
    $payPeriods[$row['id_no']] = strtolower($row['pay_schedule']);
}

$uploadError = $_SESSION['upload_error'] ?? null;
unset($_SESSION['upload_error']);

// Fetch all rates from the database for different scenarios
function getRates($conn)
{
    // Get Sunday rates
    $sql = "SELECT regular_multiplier, overtime_multiplier FROM sunday_rates LIMIT 1";
    $result = $conn->query($sql);
    $rates = $result->fetch_assoc();

    // Convert to decimal
    $rates['regular_multiplier'] = (float)$rates['regular_multiplier'];
    $rates['overtime_multiplier'] = (float)$rates['overtime_multiplier'];

    // Get holiday rates - fetch all holidays with their rates
    $sql = "SELECT holiday_date, description, holiday_type, 
                  regular_rate, overtime_rate, 
                  restdayholiday_regular, restdayholiday_overtime, 
                  restdayholiday_special, restdayspecialholiday_overtime 
           FROM holidays";
    $result = $conn->query($sql);

    $holidays = [];
    $holidayRates = [
        'Regular' => [],
        'Special' => []
    ];

    while ($row = $result->fetch_assoc()) {
        // Store holiday information with date as key
        if (isset($row['holiday_date'])) {
            $holidayDate = $row['holiday_date'];
            $holidays[$holidayDate] = [
                'type' => $row['holiday_type'],
                'description' => $row['description'] ?? '',
                'rates' => []
            ];

            // Store the specific rates for this holiday date
            foreach (
                [
                    'regular_rate',
                    'overtime_rate',
                    'restdayholiday_regular',
                    'restdayholiday_overtime',
                    'restdayholiday_special',
                    'restdayspecialholiday_overtime'
                ] as $rateKey
            ) {
                if (isset($row[$rateKey]) && $row[$rateKey] !== null) {
                    $holidays[$holidayDate]['rates'][$rateKey] = (float)$row[$rateKey];
                }
            }
        }

        // Store general rates by holiday type (as fallback)
        $type = $row['holiday_type'];
        if (!isset($holidayRates[$type]['regular_rate']) && isset($row['regular_rate'])) {
            $holidayRates[$type]['regular_rate'] = (float)$row['regular_rate'];
        }
        if (!isset($holidayRates[$type]['overtime_rate']) && isset($row['overtime_rate'])) {
            $holidayRates[$type]['overtime_rate'] = (float)$row['overtime_rate'];
        }
        if (!isset($holidayRates[$type]['restdayholiday_regular']) && isset($row['restdayholiday_regular'])) {
            $holidayRates[$type]['restdayholiday_regular'] = (float)$row['restdayholiday_regular'];
        }
        if (!isset($holidayRates[$type]['restdayholiday_overtime']) && isset($row['restdayholiday_overtime'])) {
            $holidayRates[$type]['restdayholiday_overtime'] = (float)$row['restdayholiday_overtime'];
        }
        if (!isset($holidayRates[$type]['restdayholiday_special']) && isset($row['restdayholiday_special'])) {
            $holidayRates[$type]['restdayholiday_special'] = (float)$row['restdayholiday_special'];
        }
        if (!isset($holidayRates[$type]['restdayspecialholiday_overtime']) && isset($row['restdayspecialholiday_overtime'])) {
            $holidayRates[$type]['restdayspecialholiday_overtime'] = (float)$row['restdayspecialholiday_overtime'];
        }
    }

    // Add default rates if not found in database
    if (empty($holidayRates['Regular'])) {
        $holidayRates['Regular'] = [
            'regular_rate' => 1.0,
            'overtime_rate' => 1.3,
            'restdayholiday_regular' => 2.6,
            'restdayholiday_overtime' => 3.38
        ];
    }

    if (empty($holidayRates['Special'])) {
        $holidayRates['Special'] = [
            'regular_rate' => 1.0,
            'overtime_rate' => 1.3,
            'restdayholiday_special' => 1.5,
            'restdayspecialholiday_overtime' => 1.95
        ];
    }

    $rates['holiday_rates'] = $holidayRates;
    return [
        'rates' => $rates,
        'holidays' => $holidays
    ];
}

// Get all rates and holidays in one function call
$ratesData = getRates($conn);
$allRates = $ratesData['rates'];
$holidays = $ratesData['holidays'];
$sundayRates = [
    'regular_multiplier' => $allRates['regular_multiplier'],
    'overtime_multiplier' => $allRates['overtime_multiplier']
];
$holidayRates = $allRates['holiday_rates'];

// Fetch daily rate from the database
function getDailyRate($id_no, $conn)
{
    $sql = "SELECT daily_rate FROM daily_rate WHERE id_no = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $id_no);
    $stmt->execute();
    $stmt->bind_result($dailyRate);
    $stmt->fetch();
    $stmt->close();
    return (float)$dailyRate; // Convert to float
}

// Get holiday rate multipliers for a specific date
function getHolidayMultipliers($date, $isSunday, $holidays)
{
    // Default values for regular days
    $regularMultiplier = 1.0;
    $overtimeMultiplier = 1.25;

    if (isset($holidays[$date])) {
        $holidayInfo = $holidays[$date];
        $holidayType = $holidayInfo['type'];
        $specificRates = $holidayInfo['rates'];

        if ($isSunday) {
            // Rest day + holiday combination
            if ($holidayType == 'Regular') {
                // Rest day + Regular holiday
                $regularMultiplier = $specificRates['restdayholiday_regular'] ?? 2.6;
                $overtimeMultiplier = $specificRates['restdayholiday_overtime'] ?? 3.38;
            } else {
                // Rest day + Special holiday
                $regularMultiplier = $specificRates['restdayholiday_special'] ?? 1.5;
                $overtimeMultiplier = $specificRates['restdayspecialholiday_overtime'] ?? 1.95;
            }
        } else {
            // Just a holiday
            if ($holidayType == 'Regular') {
                $regularMultiplier = $specificRates['regular_rate'] ?? 1.0;
                $overtimeMultiplier = $specificRates['overtime_rate'] ?? 1.3;
            } else {
                $regularMultiplier = $specificRates['regular_rate'] ?? 1.0;
                $overtimeMultiplier = $specificRates['overtime_rate'] ?? 1.3;
            }
        }
    } elseif ($isSunday) {
        // Just a Sunday/rest day - use Sunday rates
        global $sundayRates;
        $regularMultiplier = $sundayRates['regular_multiplier'];
        $overtimeMultiplier = $sundayRates['overtime_multiplier'];
    }

    return [
        'regular' => $regularMultiplier,
        'overtime' => $overtimeMultiplier
    ];
}

// Precompute all Saturdays of the year
function getSaturdays($year)
{
    $saturdays = [];
    $date = DateTime::createFromFormat('Y-m-d', "$year-01-01");
    while ($date->format('Y') == $year) {
        if ($date->format('N') == 6) {  // Saturday
            $saturdays[] = $date->format('Y-m-d');
        }
        $date->modify('+1 day');
    }
    return $saturdays;
}

$saturdays = getSaturdays(date('Y'));
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Deductions</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background-color: #f5f7fa;
            display: flex;
            height: 100vh;
            overflow: hidden;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        /* Improved Sidebar Styles */
        .sidebar {
            width: 270px;
            background: linear-gradient(180deg, #2c3e50 0%, #1a252f 100%);
            color: white;
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            transition: all 0.3s;
            overflow-y: auto;
            box-shadow: 3px 0 10px rgba(0, 0, 0, 0.1);
            z-index: 1030;
            transform: translateX(0);
        }

        .sidebar-header {
            padding: 20px 15px;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 2px;
        }

        .sidebar-logo {
            width: 200px;
            height: 200px;
            object-fit: contain;
            margin-bottom: -30px;
            margin-top: -50px;
        }

        .company-name {
            font-size: 20px;
            font-weight: 600;
            color: white;
            margin-bottom: -10px;
            opacity: 0.95;
            line-height: 1.3;
        }

        .nav-section {
            margin-bottom: 2px;
        }

        .nav-section-title {
            padding: 8px 20px;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: rgba(255, 255, 255, 0.5);
            font-weight: 600;
        }

        .sidebar a {
            display: flex;
            align-items: center;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            padding: 10px 20px;
            font-size: 13px;
            transition: all 0.2s;
            border-left: 3px solid transparent;
        }

        .sidebar a i {
            margin-right: 12px;
            width: 24px;
            text-align: center;
            font-size: 15px;
        }

        .sidebar a:hover {
            background-color: rgba(255, 255, 255, 0.08);
            color: white;
            border-left-color: rgba(93, 173, 226, 0.5);
        }

        .sidebar a.active {
            background-color: rgba(93, 173, 226, 0.15);
            color: white;
            border-left-color: #5dade2;
            font-weight: 500;
        }

        .sidebar-footer {
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding: 15px;
            font-size: 12px;
            text-align: center;
            color: rgba(255, 255, 255, 0.5);
            margin-top: 2px;
        }

        /* Mobile Menu Toggle Button */
        .menu-toggle {
            display: none;
            position: fixed;
            top: 15px;
            right: 15px;
            z-index: 1050;
            background: #2c3e50;
            color: white;
            border: none;
            border-radius: 5px;
            padding: 8px 12px;
            font-size: 18px;
            cursor: pointer;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }

        /* Sidebar Overlay for Mobile */
        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1025;
        }

        .main-content {
            margin-left: 270px;
            padding: 30px;
            width: calc(100% - 270px);
            transition: all 0.3s;
            box-sizing: border-box;
            min-height: 100vh;
            background-color: #d6eaf8;
            overflow-x: hidden;
            overflow-y: auto;
        }

        .container-box {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            width: 100%;
            max-width: 1400px;
            height: auto;
            min-height: calc(100vh - 60px);
            overflow: auto;
        }

        .card-custom {
            border-radius: 15px;
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.1);
            background-color: white;
        }

        .table-container {
            max-height: 85vh;
            overflow-y: auto;
        }

        .table th,
        .table td {
            vertical-align: middle;
        }

        .table-container th,
        .table-container thead th {
            background-color: #000;
            color: white;
            position: sticky;
            top: 0;
            z-index: 1;
        }

        .tooltip-info {
            cursor: pointer;
            color: #007bff;
        }

        /* Media Queries for Responsiveness */
        @media (max-width: 991.98px) {

            /* Styles for tablets and smaller devices */
            .user-count-panel {
                min-height: 100px;
                padding: 15px 10px;
            }

            .panel-icon {
                width: 40px;
                height: 40px;
                font-size: 18px;
            }

            .panel-count {
                font-size: 18px;
            }

            .panel-label {
                font-size: 12px;
            }

            .welcome-section h2 {
                font-size: 1.5rem;
            }

            .welcome-section p {
                font-size: 1rem !important;
            }
        }

        @media (max-width: 767.98px) {

            /* Styles for mobile devices */
            .menu-toggle {
                display: block;
            }

            .sidebar {
                transform: translateX(-100%);
                overflow-y: auto;
            }

            .sidebar.active {
                transform: translateX(0);
                overflow-y: auto;
            }

            .main-content {
                margin-left: 0;
                width: 100%;
                padding: 15px;
            }

            .container-box {
                padding: 20px 15px;
            }

            .table-responsive {
                font-size: 0.85rem;
            }
        }

        @media (max-width: 575.98px) {

            /* Styles for extra small devices */
            .main-content {
                padding: 10px;
            }

            .container-box {
                padding: 15px 10px;
            }

            .card-custom {
                padding: 10px;
            }

            .table-responsive {
                font-size: 0.75rem;
            }
        }

        /* Print media styles */
        @media print {

            .sidebar,
            .menu-toggle,
            .sidebar-overlay {
                display: none !important;
            }

            .main-content {
                margin-left: 0;
                width: 100%;
            }

            .container-box {
                box-shadow: none;
                height: auto;
            }
        }

        /* Modal styles */
        .modal-confirm {
            color: #636363;
        }

        .modal-confirm .modal-content {
            padding: 20px;
            border-radius: 5px;
            border: none;
        }

        .modal-confirm .modal-header {
            border-bottom: none;
            position: relative;
        }

        .modal-confirm .modal-title {
            text-align: center;
            font-size: 26px;
            margin: 30px 0 -15px;
        }

        .modal-confirm .modal-footer {
            border: none;
            text-align: center;
            border-radius: 5px;
            font-size: 13px;
        }

        .modal-confirm .icon-box {
            color: #fff;
            position: absolute;
            margin: 0 auto;
            left: 0;
            right: 0;
            top: -70px;
            width: 95px;
            height: 95px;
            border-radius: 50%;
            z-index: 9;
            background: #82ce34;
            padding: 15px;
            text-align: center;
            box-shadow: 0px 2px 2px rgba(0, 0, 0, 0.1);
        }

        .modal-confirm .icon-box i {
            font-size: 58px;
            position: relative;
            top: 3px;
        }
    </style>
</head>

<body>

    <!-- Sidebar Overlay for Mobile -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    
    <!-- Mobile Menu Toggle Button -->
    <button class="menu-toggle" id="menuToggle">
        <i class="fas fa-bars"></i>
    </button>
    
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <img src="my_project/images/MULTI-removebg-preview.png" class="sidebar-logo" alt="Company Logo">
            <div class="company-name">Multi Axis Handlers & Tech Inc</div>
        </div>

        <!-- MAIN NAVIGATION -->
        <div class="nav-section">
            <div class="nav-section-title">Main</div>
            <a href="dashboard.php" class="<?= ($current_page == 'dashboard.php') ? 'active' : '' ?>">
                <i class="fas fa-home"></i> Dashboard
            </a>
            <?php if ($role === 'admin') : ?>
                <a href="add_user.php" class="<?= ($current_page == 'add_user.php') ? 'active' : '' ?>">
                    <i class="fas fa-user-plus"></i> Employees
                </a>
            <?php endif; ?>
        </div>

        <!-- ATTENDANCE -->
        <div class="nav-section">
            <div class="nav-section-title">Attendance</div>
            <a href="upload_excel_monthly.php" class="<?= in_array($current_page, ['employee_attendace.php', 'employee_attendace_monthly.php', 'employee_attendace_semi-monthly.php']) ? 'active' : '' ?>">
                <i class="fas fa-calendar-alt"></i> Upload Attendance
            </a>
            <a href="manual.php" class="<?= ($current_page == 'manual.php') ? 'active' : '' ?>">
                <i class="fas fa-calendar-week"></i> Manual Attendance
            </a>
            <a href="attendance_summary_report.php" class="<?= ($current_page == 'attendance_summary_report.php') ? 'active' : '' ?>">
                <i class="fas fa-clipboard-list"></i> Attendance Summary
            </a>
        </div>

        <!-- PAYROLL -->
        <div class="nav-section">
            <div class="nav-section-title">Payroll</div>
            <a href="payroll.php"
                class="<?= in_array($current_page, ['payroll.php', 'enter_payroll.php', 'weekly_employees.php', 'semi-monthly_employees.php', 'enter_weekly_payroll.php', 'enter_payroll.php','enter_semimonthly_payroll.php']) ? 'active' : '' ?>">
                <i class="fas fa-money-bill-wave"></i> Payroll
            </a>
            <a href="reports.php" class="<?php echo ($current_page == 'reports.php') ? 'active' : ''; ?>">
                <i class="fas fa-chart-bar"></i> Deductions
            </a>
            <a href="view_payslips.php" class="<?= ($current_page == 'view_payslips.php') ? 'active' : '' ?>">
                <i class="fas fa-file-invoice-dollar"></i> View Payslips
            </a>
            <a href="payslip_archive.php" class="<?= ($current_page == 'payslip_archive.php') ? 'active' : '' ?>">
                <i class="fas fa-archive"></i> Payslip Archive
            </a>
        </div>

        <!-- OTHER -->
        <div class="nav-section">
            <div class="nav-section-title">Other</div>
            <a href="about.php" class="<?= ($current_page == 'about.php') ? 'active' : '' ?>">
                <i class="fas fa-info-circle"></i> About
            </a>
            <a href="logout.php" class="<?= ($current_page == 'logout.php') ? 'active' : '' ?>">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
        <div class="sidebar-footer">
            © <?php echo date('Y'); ?> Multi Axis Handlers & Tech Inc.
        </div>


    </div>

    <div class="main-content">
        <div class="container-fluid">
            <div class="card card-custom mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="mb-0 text-center">📦 Employee Attendance Records</h4>

                        <?php if (!empty($employeeData)): ?>
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#confirmSaveModal">
                                <i class="fas fa-save"></i> Save Attendance Records
                            </button>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($employeeData)): ?>
                        <div class="table-container">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>ID No.</th>
                                            <th>Department</th>
                                            <th>Name</th>
                                            <th>Date</th>
                                            <th>Hours Worked</th>
                                            <th>Daily Rate (₱)</th>
                                            <th>Undertime (₱)</th>
                                            <th>Overtime (₱)</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php

                                        // Modified grouping logic to handle duplicates
                                        $groupedByEmployee = [];
                                        $processedDates = []; // Track processed dates for each employee
                                        $payPeriods = [];
                                        $sql = "SELECT id_no, pay_schedule FROM daily_rate";
                                        $result = $conn->query($sql);

                                        while ($row = $result->fetch_assoc()) {
                                            $payPeriods[$row['id_no']] = strtolower(trim($row['pay_schedule']));
                                        }

                                        // Determine the current required pay period
                                        if (isset($_SESSION['attendance_data_monthly'])) {
                                            $requiredPeriod = 'fixed'; // alias monthly → fixed
                                        } elseif (isset($_SESSION['attendance_data_semi_monthly'])) {
                                            $requiredPeriod = 'semi-monthly';
                                        } elseif (isset($_SESSION['attendance_data'])) {
                                            $requiredPeriod = 'weekly';
                                        } else {
                                            $requiredPeriod = null; // fallback
                                        }

                                        $employeeData = array_filter($employeeData, function ($employee) use ($payPeriods, $requiredPeriod) {
                                            $id = $employee['id_no'] ?? null;
                                            if (!$id || !isset($payPeriods[$id]) || !$requiredPeriod) {
                                                return false;
                                            }

                                            $period = strtolower(trim($payPeriods[$id]));

                                            // alias monthly → fixed
                                            if ($period === 'monthly') {
                                                $period = 'fixed';
                                            }
                                            if ($period === 'semi monthly') {
                                                $period = 'semi-monthly';
                                            }

                                            return $period === $requiredPeriod;
                                        });





                                        foreach ($employeeData as $employee) {
                                            if (isset($employee['dates'])) {
                                                foreach ($employee['dates'] as $i => $date) {
                                                    $employeeKey = $employee['name'];
                                                    $dateKey = $date;
                                                    $uniqueKey = $employeeKey . '_' . $dateKey;

                                                    // Get time values
                                                    $amIn = $employee['am_in'][$i] ?? '';
                                                    $amOut = $employee['am_out'][$i] ?? '';

                                                    // If we haven't processed this date for this employee yet, or
                                                    // if this entry has both am_in and am_out and the previous one didn't
                                                    if (
                                                        !isset($processedDates[$employeeKey][$dateKey]) ||
                                                        ((!empty($amIn) && !empty($amOut)) &&
                                                            (empty($processedDates[$employeeKey][$dateKey]['am_in']) ||
                                                                empty($processedDates[$employeeKey][$dateKey]['am_out'])))
                                                    ) {

                                                        // Store this entry's details
                                                        $processedDates[$employeeKey][$dateKey] = [
                                                            'am_in' => $amIn,
                                                            'am_out' => $amOut
                                                        ];

                                                        // Either create or update the entry
                                                        $entryData = [
                                                            'id_no'      => $employee['id_no'],
                                                            'department' => $employee['department'],
                                                            'name'       => $employee['name'],
                                                            'date'       => $date,
                                                            'am_in'      => $amIn,
                                                            'am_out'     => $amOut
                                                        ];

                                                        // Remove any previous entry for this date if it exists
                                                        if (isset($groupedByEmployee[$employeeKey])) {
                                                            foreach ($groupedByEmployee[$employeeKey] as $key => $entry) {
                                                                if ($entry['date'] === $dateKey) {
                                                                    unset($groupedByEmployee[$employeeKey][$key]);
                                                                }
                                                            }
                                                        }

                                                        // Add the new entry
                                                        $groupedByEmployee[$employeeKey][] = $entryData;
                                                    }
                                                }
                                            }
                                        }

                                        // Reindex arrays after potentially removing elements
                                        foreach ($groupedByEmployee as $employeeKey => $entries) {
                                            $groupedByEmployee[$employeeKey] = array_values($entries);
                                        }

                                        ksort($groupedByEmployee);
                                        foreach ($groupedByEmployee as $employeeName => $entries):
                                            $dailyRate = getDailyRate($entries[0]['id_no'], $conn);

                                            usort($entries, function ($a, $b) {
                                                return strcmp($a['date'], $b['date']);
                                            });

                                            $totalWage = 0;
                                            $totalUndertime = 0;
                                            $totalOvertime = 0;
                                            $totalDaysPresent = 0;
                                            $totalAbsences = 0;

                                            foreach ($entries as $row):
                                                $amIn = $row['am_in'];
                                                $amOut = $row['am_out'];
                                                $hoursWorked = '';
                                                $status = '';
                                                $undertime = '';
                                                $overtime = '';
                                                $overtimeAmount = 0;
                                                $undertimeAmount = 0;

                                                $dateCheck = $row['date'];
                                                $dateObj = DateTime::createFromFormat('Y-m-d', $dateCheck);
                                                $isSunday = $dateObj && $dateObj->format('N') == 7;
                                                $isHoliday = isset($holidays[$dateCheck]);
                                                $holidayInfo = $isHoliday ? $holidays[$dateCheck] : null;
                                                $holidayType = $isHoliday ? $holidayInfo['type'] : '';
                                                $holidayDesc = $isHoliday ? $holidayInfo['description'] : '';
                                                $isSaturday = in_array($dateCheck, $saturdays);

                                                // Get multipliers based on date and day type
                                                $multipliers = getHolidayMultipliers($dateCheck, $isSunday, $holidays);
                                                $regularMultiplier = $multipliers['regular'];
                                                $overtimeMultiplier = $multipliers['overtime'];

                                                // Set the basic daily rate
                                                $baseDailyRate = $dailyRate;
                                                $dailyWage = $baseDailyRate * $regularMultiplier; // Apply multiplier immediately

                                                if (!empty($amIn) && !empty($amOut)) {
                                                    $inTime = DateTime::createFromFormat('H:i', $amIn);
                                                    $outTime = DateTime::createFromFormat('H:i', $amOut);

                                                    if ($inTime && $outTime) {
                                                        $interval = $inTime->diff($outTime);
                                                        $totalMinutes = ($interval->d * 24 * 60) + ($interval->h * 60) + $interval->i;

                                                        // Subtract 1 hour for lunch if not Saturday, Sunday, or holiday
                                                        if (!$isSaturday && !$isHoliday && !$isSunday) {
                                                            $totalMinutes -= 60;
                                                        }

                                                        // Subtract 1 hour if it's a holiday
                                                        if ($isHoliday) {
                                                            $totalMinutes -= 60;
                                                        }
                                                        $totalMinutes = max(0, $totalMinutes);

                                                        $totalMinutes = max(0, $totalMinutes);

                                                        // Convert to hours and minutes
                                                        $hoursPart = floor($totalMinutes / 60);
                                                        $minutesPart = $totalMinutes % 60;

                                                        // Display as "7 hrs 20 mins"
                                                        $hoursWorked = "{$hoursPart}hrs {$minutesPart}mins";

                                                        // You can still keep decimal version if needed for undertime/overtime
                                                        $workedHours = $totalMinutes / 60;

                                                        if ($workedHours > 0) {
                                                            // Set status based on day type
                                                            if ($isSunday && $isHoliday) {
                                                                $status = 'Rest Day + ' . ($holidayType == 'Regular' ? 'Regular Holiday' : 'Special Holiday');
                                                                if ($holidayDesc) {
                                                                    $status .= ' (' . $holidayDesc . ')';
                                                                }
                                                            } elseif ($isSunday) {
                                                                $status = 'Rest Day (Worked)';
                                                            } elseif ($isHoliday) {
                                                                $status = $holidayType . ' Holiday';
                                                                if ($holidayDesc) {
                                                                    $status .= ' (' . $holidayDesc . ', Worked)';
                                                                }
                                                            } else {
                                                                $status = 'Present';
                                                            }
                                                            // Calculate undertime or overtime
                                                            if ($workedHours < 8) {
                                                                // Calculate undertime in minutes
                                                                $undertimeMinutes = (8 - $workedHours) * 60;

                                                                // Round undertime to 30-minute increments if at least 11 minutes
                                                                if ($undertimeMinutes >= 11) {
                                                                    $roundedUndertime = ceil($undertimeMinutes / 30) * 0.5; // Convert to hours in 0.5 increments

                                                                    // KEY CHANGE: Always use base daily rate for undertime deductions
                                                                    // regardless of holiday status
                                                                    $undertimeAmount = $roundedUndertime * ($baseDailyRate / 8);
                                                                } else {
                                                                    $undertimeAmount = 0; // No undertime charge if less than 11 minutes
                                                                }

                                                                $undertime = '₱' . number_format($undertimeAmount, 2);
                                                                $overtime = '₱0.00';
                                                            } elseif ($workedHours > 8) {
                                                                // FIXED: Only count full hours for overtime, ignoring partial hours
                                                                $overtimeHours = floor($workedHours - 8); // Use floor() to only count complete hours

                                                                if ($overtimeHours >= 1) {
                                                                    // Calculate overtime pay only for full hours worked beyond 8
                                                                    $overtimeAmount = $overtimeHours * ($baseDailyRate / 8) * $overtimeMultiplier;
                                                                    $overtime = '₱' . number_format($overtimeAmount, 2);
                                                                } else {
                                                                    // If less than one full hour of overtime, no overtime pay
                                                                    $overtimeAmount = 0;
                                                                    $overtime = '₱0.00';
                                                                }

                                                                $undertime = '₱0.00';
                                                            } else {
                                                                $undertime = '₱0.00';
                                                                $overtime = '₱0.00';
                                                            }

                                                            // Accumulate totals
                                                            $totalWage += $dailyWage;
                                                            $totalUndertime += $undertimeAmount;
                                                            $totalOvertime += $overtimeAmount;
                                                            $totalDaysPresent++;
                                                        } else {
                                                            // No hours worked = absent
                                                            if ($isSunday) {
                                                                $status = 'Rest Day';
                                                                $dailyWage = 0; // No pay for rest day unless worked
                                                            } elseif ($isHoliday) {
                                                                $status = $holidayType . ' Holiday';
                                                                if ($holidayDesc) {
                                                                    $status .= ' (' . $holidayDesc . ')';
                                                                }

                                                                if ($holidayType == 'Regular') {
                                                                    // Regular holiday still pays even if not worked - use 100% of base rate
                                                                    $totalWage += $baseDailyRate;
                                                                    $dailyWage = $baseDailyRate; // Regular holiday gets 100% of daily rate
                                                                } else {
                                                                    $dailyWage = 0; // Special holiday doesn't pay if not worked
                                                                }
                                                            } else {
                                                                $status = 'Absent';
                                                                $dailyWage = 0; // No pay for absent days
                                                                $totalAbsences++;
                                                            }
                                                        }
                                                    } else {
                                                        // Invalid time format
                                                        if ($isSunday) {
                                                            $status = 'Rest Day';
                                                            $dailyWage = 0; // No pay for rest day unless worked
                                                        } elseif ($isHoliday) {
                                                            $status = $holidayType . ' Holiday';
                                                            if ($holidayDesc) {
                                                                $status .= ' (' . $holidayDesc . ')';
                                                            }

                                                            if ($holidayType == 'Regular') {
                                                                // Regular holiday still pays even if not worked - use base rate
                                                                $totalWage += $baseDailyRate;
                                                                $dailyWage = $baseDailyRate;
                                                            } else {
                                                                $dailyWage = 0; // No pay for special holiday unless worked
                                                            }
                                                        } else {
                                                            $status = 'Absent';
                                                            $dailyWage = 0;
                                                            $totalAbsences++;
                                                        }
                                                    }
                                                } else {
                                                    // No time entries
                                                    if ($isHoliday && $holidayType === 'Regular') {
                                                        $status = 'Regular Holiday';
                                                        if ($holidayDesc) {
                                                            $status .= ' (' . $holidayDesc . ')';
                                                        }

                                                        // Regular holiday pays base rate even if absent
                                                        $dailyWage = $baseDailyRate;
                                                        $totalWage += $dailyWage;
                                                    } elseif ($isHoliday) {
                                                        $status = 'Special Holiday';
                                                        if ($holidayDesc) {
                                                            $status .= ' (' . $holidayDesc . ')';
                                                        }
                                                        $dailyWage = 0; // No pay for special holiday unless worked
                                                    } elseif ($isSunday) {
                                                        $status = 'Rest Day';
                                                        $dailyWage = 0; // No pay for rest day unless worked
                                                    } else {
                                                        $status = 'Absent';
                                                        $dailyWage = 0; // No pay for absent days
                                                        $totalAbsences++;
                                                    }
                                                }
                                        ?>
                                                <tr>
                                                    <td><?= $row['id_no']; ?></td>
                                                    <td><?= $row['department']; ?></td>
                                                    <td><?= $row['name']; ?></td>
                                                    <td><?= $row['date']; ?></td>
                                                    <td><?= $hoursWorked; ?></td>
                                                    <td><?= '₱' . number_format($dailyWage, 2); ?><?php if ($regularMultiplier > 1): ?> <i class="fas fa-info-circle tooltip-info" title="Rate multiplier: <?= number_format($regularMultiplier, 2) ?>x"></i><?php endif; ?></td>
                                                    <td><?= $undertime; ?></td>
                                                    <td><?= $overtime; ?><?php if ($overtimeAmount > 0): ?> <i class="fas fa-info-circle tooltip-info" title="OT multiplier: <?= number_format($overtimeMultiplier, 2) ?>x"></i><?php endif; ?></td>
                                                    <td><?= $status; ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                            <tr class="table-primary fw-bold">
                                                <td colspan="4" class="text-end">TOTAL for <?= htmlspecialchars($employeeName); ?>:</td>
                                                <td><?= $totalDaysPresent . ' days'; ?></td>
                                                <td><?= '₱' . number_format($totalWage, 2); ?></td>
                                                <td><?= '₱' . number_format($totalUndertime, 2); ?></td>
                                                <td><?= '₱' . number_format($totalOvertime, 2); ?></td>
                                                <td><?= $totalAbsences . ' days'; ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p>No attendance data available.</p>
                        <?php endif; ?>

                        </div>
                </div>
            </div>
        </div>

        <!-- Confirmation Modal -->
        <div class="modal fade" id="confirmSaveModal" tabindex="-1" aria-labelledby="confirmSaveModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header border-0">
                        <h5 class="modal-title w-100 text-center" id="confirmSaveModalLabel">Save Attendance Records</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body text-center pt-0">
                        <i class="fas fa-question-circle fa-4x text-warning mb-3"></i>
                        <p>Are you sure you want to save these attendance records? This action cannot be undone.</p>
                    </div>
                    <div class="modal-footer justify-content-center border-0">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>

                        <form method="post" action="<?= $formAction ?>">
                            <input type="hidden" name="save_attendance" value="1">
                            <input type="hidden" name="source"
                                value="<?php
                                        if (isset($_SESSION['attendance_data_monthly'])) {
                                            echo 'employee_attendance_monthly.php';
                                        } elseif (isset($_SESSION['attendance_data_semi_monthly'])) {
                                            echo 'employee_attendance_semi-monthly.php';
                                        } elseif (isset($_SESSION['attendance_data'])) {
                                            echo 'employee_attendance.php'; // weekly
                                        } else {
                                            echo 'employee_attendance.php';
                                        }
                                        ?>">

                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-save"></i> Save Records
                            </button>
                        </form>

                    </div>
                </div>
            </div>
        </div>


        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Initialize tooltips
                const tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'));
                tooltipTriggerList.map(function(tooltipTriggerEl) {
                    return new bootstrap.Tooltip(tooltipTriggerEl);
                });

                // Sidebar toggle functionality
                const menuToggle = document.getElementById('menuToggle');
                const sidebar = document.getElementById('sidebar');
                const sidebarOverlay = document.getElementById('sidebarOverlay');

                // Toggle sidebar when menu button is clicked
                if (menuToggle) {
                    menuToggle.addEventListener('click', function() {
                        sidebar.classList.toggle('active');
                        sidebarOverlay.style.display = sidebar.classList.contains('active') ? 'block' : 'none';
                    });
                }

                // Close sidebar when clicking on overlay
                if (sidebarOverlay) {
                    sidebarOverlay.addEventListener('click', function() {
                        sidebar.classList.remove('active');
                        sidebarOverlay.style.display = 'none';
                    });
                }

                // Close sidebar when window is resized to desktop size
                window.addEventListener('resize', function() {
                    if (window.innerWidth > 767.98) {
                        sidebar.classList.remove('active');
                        sidebarOverlay.style.display = 'none';
                    }
                });
            });
        </script>
</body>

</html>