<?php
include_once "functions.php";

session_start();
include __DIR__ . "/config.php";

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['user'];
$role = $_SESSION['role'];

// Update the query to select data from the payroll_records table instead of employee_payslips
$payslip_query = "SELECT * FROM payroll_records WHERE 1=0";

if (isset($_POST['start_date']) && isset($_POST['end_date'])) {
    $start_date = mysqli_real_escape_string($conn, $_POST['start_date']);
    $end_date = mysqli_real_escape_string($conn, $_POST['end_date']);
$payslip_query = "SELECT DISTINCT * FROM payroll_records WHERE 
    (start_date <= '$end_date' AND end_date >= '$start_date')";
}

$payslip_result = mysqli_query($conn, $payslip_query);
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Holiday & Overtime Payslips</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
       body {
            background-color: #d6eaf8;
            margin: 0;
            font-family: 'Segoe UI', sans-serif;
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
            overflow: hidden;
            box-shadow: 3px 0 10px rgba(0, 0, 0, 0.1);
            z-index: 1030;
        }
        .sidebar-header {
            padding: 20px 15px;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 10px;
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
            margin-bottom: 5px;
        }
        .nav-section-title {
            padding: 8px 20px;
            font-size: 12px;
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
            padding: 12px 20px;
            font-size: 15px;
            transition: all 0.2s;
            border-left: 3px solid transparent;
        }
        .sidebar a i {
            margin-right: 12px;
            width: 24px;
            text-align: center;
            font-size: 18px;
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
            margin-top: 10px;
        }
        .main-content {
            margin-left: 270px;
            padding: 40px 30px;
            transition: all 0.3s;
            box-sizing: border-box;
            overflow: hidden;
            min-height: 100vh;
        }

        .date-form {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .a4-page {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(330px, 1fr));
            gap: 30px;
        }

        .payslip {
            background: #fff;
            border: 1px solid #dee2e6;
            padding: 25px;
            border-radius: 10px;
            font-size: 14px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .payslip p {
            margin-bottom: 6px;
        }

        .print-btn {
            display: block;
            margin: 0 auto 30px;
            padding: 10px 25px;
            font-size: 16px;
            background-color: #198754;
            color: white;
            border: none;
            border-radius: 8px;
        }

        .print-btn:hover {
            background-color: #157347;
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
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }

        /* Sidebar Overlay for Mobile */
        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0,0,0,0.5);
            z-index: 1025;
        }

        /* Media Queries for Responsiveness */
        @media (max-width: 991.98px) {
            /* Styles for tablets and smaller devices */
            .a4-page {
                grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
                gap: 20px;
            }
            
            .payslip {
                padding: 20px;
            }
            
            .print-btn {
                padding: 8px 20px;
                font-size: 15px;
            }
            
            .main-content {
                padding: 30px 20px;
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
            
            .a4-page {
                grid-template-columns: 1fr;
                gap: 15px;
            }
            
            .date-form {
                padding: 15px;
            }
            
            .print-btn {
                width: 100%;
                margin-bottom: 20px;
            }
        }

        @media (max-width: 575.98px) {
            /* Styles for extra small devices */
            .main-content {
                padding: 10px;
            }
            
            .date-form {
                padding: 15px 10px;
            }
            
            .payslip {
                padding: 15px;
                font-size: 13px;
            }
        }

        /* Print media styles */
        @media print {
            .sidebar, 
            .date-form,
            .print-btn,
            .card-header,
            .menu-toggle, 
            .sidebar-overlay {
                display: none !important;
            }

            .main-content {
                margin-left: 0;
                padding: 5px;
                width: 100%;
            }

            .a4-page {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                grid-template-rows: repeat(2, 1fr);
                gap: 5px;
                page-break-after: always;
            }

            .payslip {
                font-size: 14px;
                padding: 5px;
                border: 2px solid #000000;
                box-shadow: none;
                page-break-inside: avoid;
                break-inside: avoid;
                height: 100%;
            }
            
            .payslip p {
                margin-bottom: 2px;
            }
            
            body {
                zoom: 0.7;
            }
            
            /* Every 4 payslips should be on a new page */
            .a4-page > div:nth-child(4n+1) {
                page-break-before: always;
            }
            
            /* First page should not have a page break */
            .a4-page > div:first-child {
                page-break-before: avoid;
            }
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
    
<div class="nav-section">
    <div class="nav-section-title">Main Navigation</div>
    <?php if ($role === 'admin') : ?>
        <a href="dashboard.php" class="<?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>">
            <i class="fas fa-home"></i> Dashboard
        </a>
        <a href="add_user.php" class="<?php echo ($current_page == 'add_user.php') ? 'active' : ''; ?>">
            <i class="fas fa-user-plus"></i> Employees
        </a>
    <?php endif; ?>

    <div class="attendance-group">
        <div class="nav-section-title">
            <i class="fas fa-clipboard-check"></i> Attendance
        </div>
        <a href="employee_attendance_monthly.php" class="<?php echo ($current_page == 'employee_attendance_monthly.php') ? 'active' : ''; ?>">
            Monthly Attendance
        </a>
        <a href="employee_attendance.php" class="<?php echo ($current_page == 'employee_attendance.php') ? 'active' : ''; ?>">
            Weekly Attendance
        </a>
    </div>
</div>


    <div class="nav-section">
        <div class="nav-section-title">Payroll Management</div>
        <a href="reports.php" class="<?php echo ($current_page == 'reports.php') ? 'active' : ''; ?>">
            <i class="fas fa-chart-bar"></i> Deductions
        </a>
        <a href="attendance_summary_report.php" class="<?php echo ($current_page == 'attendance_summary_report.php') ? 'active' : ''; ?>">
            <i class="fas fa-clock"></i> Attendance Summary
        </a>
        <a href="view_payslips.php" class="<?= ($current_page == 'view_payslips.php') ? 'active' : '' ?>">
            <i class="fas fa-file-alt"></i> View Payslips
        </a>
        <a href="payslip_archive.php" class="<?= ($current_page == 'payslip_archive.php') ? 'active' : '' ?>">
            <i class="fas fa-archive"></i> Payslip Archive
        </a>

        
    </div>
    <div class="nav-section">
        <a href="logout.php" class="<?php echo ($current_page == 'logout.php') ? 'active' : ''; ?>">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
    
    <div class="sidebar-footer">
        © <?php echo date('Y'); ?> Multi Axis Handlers & Tech Inc.
    </div>
</div>

<div class="main-content">
    <?php if (isset($_GET['uploaded']) && $_GET['uploaded'] == '1'): ?>
        <div class="alert alert-info text-center mb-3">
            New file uploaded. Please select a payroll period and date range, then click "Generate" to view updated payslips.
        </div>
    <?php endif; ?>
    <div class="date-form">
        <form method="POST" action="<?= htmlspecialchars($current_page) ?>" class="row g-3 justify-content-center">
            <div class="col-md-4">
                <label for="payroll_period" class="form-label">Payroll Period</label>
                <select name="payroll_period" class="form-select" required>
                    <option value="" disabled selected>Select Period</option>
                    <?php
                    $servername = "localhost";
                    $username = "root";
                    $password = "cvsuOJT@2025";
                    $dbname = "multiaxis_payroll_system";

                    // Allowed payroll periods
                    $allowed_periods = ["hourly", "daily", "weekly", "semi-monthly", "monthly", "fixed"];

                    try {
                        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
                        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                        // Fetch distinct payroll periods
                        $stmt = $conn->query("SELECT DISTINCT pay_period FROM payroll_records ORDER BY pay_period ASC");
                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                            if (in_array(strtolower($row['pay_period']), $allowed_periods)) {
                                echo "<option value=\"{$row['pay_period']}\""
                                . ((isset($_POST['payroll_period']) && $_POST['payroll_period'] == $row['pay_period'] && !isset($_GET['uploaded']))
                                    ? ' selected' : '')
                                . ">
                                {$row['pay_period']}
                            </option>";
                            }
                        }
                    } catch (PDOException $e) {
                        echo "<option disabled>Error: " . $e->getMessage() . "</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="col-md-4">
                <label for="start_date" class="form-label">Start Date</label>
                <input type="date" id="start_date" name="start_date" class="form-control" required
                    value="<?php
                        if (isset($_GET['uploaded']) && $_GET['uploaded'] == '1') {
                            echo '';
                        } else {
                            echo isset($_POST['start_date']) ? htmlspecialchars($_POST['start_date']) : '';
                        }
                    ?>">
            </div>
            <div class="col-md-4">
                <label for="end_date" class="form-label">End Date</label>
                <input type="date" id="end_date" name="end_date" class="form-control" required
                    value="<?php
                        if (isset($_GET['uploaded']) && $_GET['uploaded'] == '1') {
                            echo '';
                        } else {
                            echo isset($_POST['end_date']) ? htmlspecialchars($_POST['end_date']) : '';
                        }
                    ?>">
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">Generate</button>
            </div>
        </form>
    </div>

    <button class="print-btn" onclick="generatePDF()">🖨️ Print PDF</button>
    

    <div class="a4-page">
        
    





        <?php
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $start_date = $_POST['start_date'];
            $end_date = $_POST['end_date'];

            // Calculate date difference
            $days = (strtotime($end_date) - strtotime($start_date)) / (60 * 60 * 24) + 1;

            // Auto-detect payroll period
            if ($days <= 7) {
                $selected_period = 'weekly';
            } elseif ($days >= 13 && $days <= 17) {
                $selected_period = 'semi-monthly';
            } elseif ($days >= 28 && $days <= 31) {
                $selected_period = 'fixed';
            } else {
                $selected_period = 'custom';
            }

            try {
                // Fetch payslip data where the date ranges overlap - use DISTINCT to avoid duplicates
               $stmt = $conn->prepare("
                    SELECT 
                        employee_id, 
                        name, 
                        department, 
                        :pay_period as pay_period,
                        MIN(start_date) as start_date, 
                        MAX(end_date) as end_date,
                        SUM(basic_salary) as basic_salary,
                        SUM(overtime_hours) as overtime_hours,
                        SUM(overtime_pay) as overtime_pay,
                        AVG(overtime_rate) as overtime_rate,
                        MAX(sss_no) as sss_no,
                        MAX(philhealth_no) as philhealth_no,
                        MAX(pagibig_no) as pagibig_no,
                        MAX(tin_no) as tin_no,
                        SUM(sss_premium) as sss_premium,
                        SUM(sss_loan) as sss_loan,
                        SUM(pagibig_premium) as pagibig_premium,
                        SUM(pagibig_loan) as pagibig_loan,
                        SUM(philhealth) as philhealth,
                        SUM(cash_advance) as cash_advance,
                        SUM(late_deduction) as late_deduction,
                        SUM(absent_deduction) as absent_deduction,
                        SUM(undertime_deduction) as undertime_deduction,
                        SUM(leave_with_pay) as leave_with_pay,
                        SUM(leave_without_pay) as leave_without_pay
                    FROM payroll_records
                    WHERE pay_period = :pay_period
                    AND start_date <= :end_date 
                    AND end_date >= :start_date
                    GROUP BY employee_id, name, department
                    ORDER BY name ASC
                ");


                
                $stmt->bindParam(':pay_period', $selected_period);
                $stmt->bindParam(':start_date', $start_date);
                $stmt->bindParam(':end_date', $end_date);
                $stmt->execute();
                $payslip_result = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if (count($payslip_result) > 0) {
                    foreach ($payslip_result as $row) {
                    // 🔹 Calculate overtime pay if missing
                    $overtimePay = $row['overtime_pay'];
                    if ((empty($overtimePay) || $overtimePay == 0) && $row['overtime_hours'] > 0 && $row['overtime_rate'] > 0) {
                        $overtimePay = $row['overtime_hours'] * $row['overtime_rate'];
                    }

                    // 🔹 Totals
                    $totalEarnings = $row['basic_salary'] + $overtimePay;
                    $totalDeductions = 
                        $row['sss_premium'] + $row['sss_loan'] + 
                        $row['pagibig_premium'] + $row['pagibig_loan'] + 
                        $row['philhealth'] + $row['cash_advance'] + 
                        $row['late_deduction'] + $row['absent_deduction'] + 
                        $row['undertime_deduction'];
                    $netPay = $totalEarnings - $totalDeductions;
                    ?>
                    
                    <div class="payslip">
                        <p class="text-center fw-bold fs-5">Multi Axis Handlers & Tech Inc</p>
                        <p><strong>Employee ID:</strong> <?= htmlspecialchars($row['employee_id']) ?></p>
                        <p><strong>Employee Name:</strong> <?= htmlspecialchars($row['name']) ?></p>
                        <p><strong>Department:</strong> <?= htmlspecialchars($row['department']) ?></p>
                        <p><strong>Pay Period:</strong> <?= htmlspecialchars($selected_period) ?></p>

                        <!-- 🔹 Show the SELECTED date range, not DB range -->
                        <p><strong>Date Range:</strong> <?= htmlspecialchars($start_date) ?> to <?= htmlspecialchars($end_date) ?></p>
                        <hr>

                        <div class="row">
                            <div class="col-6">
                                <p><strong>Basic Salary:</strong> ₱<?= number_format($row['basic_salary'], 2) ?></p>
                                <p><strong>Overtime Pay:</strong> ₱<?= number_format($overtimePay, 2) ?></p>
                                <p><strong>Overtime Hours:</strong> <?= htmlspecialchars($row['overtime_hours']) ?> hrs</p>
                                <p><strong>Overtime Rate:</strong> ₱<?= number_format($row['overtime_rate'], 2) ?></p>
                            </div>
                            <div class="col-6">
                                <p><strong>SSS No:</strong> <?= htmlspecialchars($row['sss_no']) ?></p>
                                <p><strong>PhilHealth No:</strong> <?= htmlspecialchars($row['philhealth_no']) ?></p>
                                <p><strong>Pag-IBIG No:</strong> <?= htmlspecialchars($row['pagibig_no']) ?></p>
                                <p><strong>TIN No:</strong> <?= htmlspecialchars($row['tin_no']) ?></p>
                                <hr>
                                <p><strong>SSS Premium:</strong> ₱<?= number_format($row['sss_premium'], 2) ?></p>
                                <p><strong>SSS Loan:</strong> ₱<?= number_format($row['sss_loan'], 2) ?></p>
                                <p><strong>Pag-IBIG Premium:</strong> ₱<?= number_format($row['pagibig_premium'], 2) ?></p>
                                <p><strong>Pag-IBIG Loan:</strong> ₱<?= number_format($row['pagibig_loan'], 2) ?></p>
                                <p><strong>PhilHealth:</strong> ₱<?= number_format($row['philhealth'], 2) ?></p>
                                <p><strong>Cash Advance:</strong> ₱<?= number_format($row['cash_advance'], 2) ?></p>
                            </div>
                        </div>

                        <hr>
                        <div class="row">
                            <div class="col-6">
                                <p><strong>Late Deduction:</strong> ₱<?= number_format($row['late_deduction'], 2) ?></p>
                                <p><strong>Absent Deduction:</strong> ₱<?= number_format($row['absent_deduction'], 2) ?></p>
                                <p><strong>Undertime Deduction:</strong> ₱<?= number_format($row['undertime_deduction'], 2) ?></p>
                            </div>
                            <div class="col-6">
                                <p><strong>Leave with Pay:</strong> <?= htmlspecialchars($row['leave_with_pay']) ?> days</p>
                                <p><strong>Leave without Pay:</strong> <?= htmlspecialchars($row['leave_without_pay']) ?> days</p>
                            </div>
                        </div>

                        <hr>
                        <p class="fw-bold">Summary:</p>
                        <p><strong>Total Earnings:</strong> ₱<?= number_format($totalEarnings, 2) ?></p>
                        <p><strong>Total Deductions:</strong> ₱<?= number_format($totalDeductions, 2) ?></p>
                        <p class="fw-bold fs-5"><strong>Net Pay:</strong> ₱<?= number_format($netPay, 2) ?></p>
                    </div>
                    <?php


                        $namesForLog[] = $row['name'];
                    }

    
                } else {
                    echo '<div class="text-center w-100 text-muted fs-5"><p>No payslips available for the selected period.</p></div>';
                }
            } catch (PDOException $e) {
                echo '<div class="text-center w-100 text-danger fs-5"><p>Error: ' . htmlspecialchars($e->getMessage()) . '</p></div>';
            }
        }
        ?>
        
    </div>
</div>

</div>
<script>
document.addEventListener("DOMContentLoaded", function() {
    const startInput = document.querySelector('input[name="start_date"]');
    const endInput = document.querySelector('input[name="end_date"]');
    const periodSelect = document.querySelector('select[name="pay_period"]');

    function updatePeriod() {
        const startDate = new Date(startInput.value);
        const endDate = new Date(endInput.value);

        if (startDate && endDate && !isNaN(startDate) && !isNaN(endDate)) {
            const daysDiff = Math.floor((endDate - startDate) / (1000 * 60 * 60 * 24)) + 1;

            if (daysDiff <= 7) {
                periodSelect.value = "Weekly";
            } else if (daysDiff <= 15) {
                periodSelect.value = "Semi-Monthly";
            } else {
                periodSelect.value = "Fixed"; // Or "Monthly" depending on your naming
            }
        }
    }

    startInput.addEventListener("change", updatePeriod);
    endInput.addEventListener("change", updatePeriod);
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Sidebar toggle functionality for mobile view
    document.addEventListener('DOMContentLoaded', function() {
        const menuToggle = document.getElementById('menuToggle');
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        
        if (menuToggle && sidebar && overlay) {
            menuToggle.addEventListener('click', function() {
                sidebar.classList.toggle('active');
                if (sidebar.classList.contains('active')) {
                    overlay.style.display = 'block';
                } else {
                    overlay.style.display = 'none';
                }
            });
            
            overlay.addEventListener('click', function() {
                sidebar.classList.remove('active');
                overlay.style.display = 'none';
            });
            
            // Close sidebar on window resize if in mobile view
            window.addEventListener('resize', function() {
                if (window.innerWidth > 768) {
                    sidebar.classList.remove('active');
                    overlay.style.display = 'none';
                }
            });

            // steph was here :DDDD
            // debug ko raw angagawin ko dito 
            
            // Handle sidebar links in mobile view
            const sidebarLinks = document.querySelectorAll('.sidebar a');
            sidebarLinks.forEach(link => {
                link.addEventListener('click', function() {
                    if (window.innerWidth <= 768) {
                        setTimeout(() => {
                            sidebar.classList.remove('active');
                            overlay.style.display = 'none';
                        }, 100);
                    }
                });
            });
        }
    });
</script>

<script>
function generatePDF() {
    const startDate = document.querySelector('input[name="start_date"]').value;
    const endDate = document.querySelector('input[name="end_date"]').value;
    const payrollPeriod = document.querySelector('select[name="payroll_period"]').value;
    
    if (!startDate || !endDate || !payrollPeriod) {
        alert('Please select a payroll period, start date, and end date first.');
        return;
    }
    
    // Log the PDF generation activity
    <?php
    if (isset($_SESSION['user'])) {
        $username = $_SESSION['user'];
        $page = basename(__FILE__);
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown IP';
        $timestamp = date('Y-m-d H:i:s');
        
        // This will be logged via AJAX
        echo "
        // Log PDF generation activity
        fetch('log_activity.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'activity=' + encodeURIComponent('Generated PDF payslips for period: ' + payrollPeriod + ' (' + startDate + ' to ' + endDate + ')') + 
                  '&page=" . $page . "' +
                  '&username=" . $username . "' +
                  '&ip_address=" . $ip_address . "' +
                  '&timestamp=" . $timestamp . "'
        }).catch(console.error);
        ";
    }
    ?>
    
    // Redirect to PDF generation script
    window.open('generate_payslip_pdf.php?start_date=' + encodeURIComponent(startDate) + 
                '&end_date=' + encodeURIComponent(endDate) + 
                '&payroll_period=' + encodeURIComponent(payrollPeriod), '_blank');
}
</script>

</body>
</html>
