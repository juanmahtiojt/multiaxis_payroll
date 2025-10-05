<?php
session_start();
include __DIR__ . "/config.php";  // Include database connection

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['user'];
$role = $_SESSION['role'];


// Fixed employee count
$fixed_employee_query = "SELECT COUNT(*) AS fixed_count FROM daily_rate WHERE pay_schedule = 'fixed'";
$fixed_employee_result = mysqli_query($conn, $fixed_employee_query);
$fixed_employee_count = mysqli_fetch_assoc($fixed_employee_result)['fixed_count'];

// Weekly employee count
$weekly_employee_query = "SELECT COUNT(*) AS weekly_count FROM daily_rate WHERE pay_schedule = 'weekly'";
$weekly_employee_result = mysqli_query($conn, $weekly_employee_query);
$weekly_employee_count = mysqli_fetch_assoc($weekly_employee_result)['weekly_count'];

// Semi-monthly employee count
$semi_employee_query = "SELECT COUNT(*) AS semi_count FROM daily_rate WHERE pay_schedule = 'semi-monthly'";
$semi_employee_result = mysqli_query($conn, $semi_employee_query);
$semi_employee_count = mysqli_fetch_assoc($semi_employee_result)['semi_count'];

$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payroll - Multi Axis Handlers & Tech Inc</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background-color: #d6eaf8;
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
            overflow: hidden;
            box-shadow: 3px 0 10px rgba(0, 0, 0, 0.1);
            z-index: 1030;
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

        /* Main Content Styles */
        .main-content {
            margin-left: 270px;
            padding: 40px;
            width: calc(100% - 270px);
            overflow-y: auto;
            transition: all 0.3s;
            background-color: #d6eaf8;
            min-height: 100vh;
            box-sizing: border-box;
        }

        .container-box {
            background: white;
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0px 6px 20px rgba(0, 0, 0, 0.2);
            max-width: 1400px;
            margin: auto;
            height: auto;
            min-height: calc(100vh - 80px);
            overflow: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }

        th,
        td {
            padding: 10px;
            border: 1px solid #ccc;
            text-align: left;
        }

        .form-section {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }

        .form-group {
            flex: 1 1 300px;
            display: flex;
            flex-direction: column;
        }

        label {
            font-weight: bold;
            margin-bottom: 5px;
        }

        input[type="text"],
        input[type="number"],
        input[type="date"] {
            padding: 8px;
            font-size: 1em;
            border-radius: 6px;
            border: 1px solid #ced4da;
        }

        .prefilled {
            background-color: #f0f0f0;
        }

        .submit-btn {
            margin-top: 20px;
            padding: 10px 20px;
            font-size: 1em;
        }

        h2 {
            color: #2c3e50;
            margin-bottom: 20px;
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

        /* Media Queries for Responsiveness */
        @media (max-width: 991.98px) {

            /* Styles for tablets and smaller devices */
            .container-box {
                padding: 20px;
                border-radius: 15px;
            }

            h2 {
                font-size: 1.5rem;
            }

            .form-group {
                flex: 1 1 250px;
            }
        }

        @media (max-width: 767.98px) {

            /* Styles for mobile devices */
            body {
                overflow-y: auto;
            }

            .menu-toggle {
                display: block;
            }

            .sidebar {
                transform: translateX(-100%);
                overflow-y: auto;
            }

            .sidebar.active {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
                width: 100%;
                padding: 20px;
            }

            .container-box {
                padding: 15px;
                border-radius: 12px;
                box-shadow: 0px 4px 15px rgba(0, 0, 0, 0.15);
            }

            .form-section {
                gap: 15px;
            }

            .form-group {
                flex: 1 1 100%;
            }
        }

        @media (max-width: 575.98px) {

            /* Styles for extra small devices */
            .main-content {
                padding: 10px;
            }

            .container-box {
                padding: 15px 10px;
                border-radius: 10px;
            }

            table th,
            table td {
                padding: 8px 5px;
                font-size: 0.9em;
            }

            .submit-btn {
                width: 100%;
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
                padding: 10px;
            }

            .container-box {
                box-shadow: none;
                height: auto;
                padding: 0;
            }
        }

        /* Payroll Dashboard Wrapper */
        .payroll-dashboard {
            margin-top: 20px;
            justify-content: center;
        }

        /* Card Link */
        .payroll-card {
            text-decoration: none;
            color: inherit;
            display: block;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            max-width: 320px;
            margin: 0 auto;
        }

        /* Card Hover Effect */
        .payroll-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
        }

        /* Card Container */
        .card-content {
            background: #ffffff;
            border-radius: 12px;
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
            height: 130px;
            border: 1px solid #eaeaea;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
            transition: all 0.2s ease;
            margin-top: 10px;
        }

        /* Active Card Highlight */
        .active-card .card-content {
            border: 2px solid #007bff;
            box-shadow: 0 0 15px rgba(0, 123, 255, 0.15);
        }

        /* Icon Circle */
        .card-icon {
            flex-shrink: 0;
            width: 65px;
            height: 65px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            color: #fff;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        /* Icon Colors */
        .bg-fixed {
            background: linear-gradient(135deg, #007bff, #0056b3);
        }

        .bg-weekly {
            background: linear-gradient(135deg, #28a745, #1e7e34);
        }

        .bg-semi {
            background: linear-gradient(135deg, #ffc107, #d39e00);
        }

        /* Text Section */
        .card-info h5 {
            font-size: 17px;
            margin: 0 0 6px;
            font-weight: 600;
            color: #333;
        }

        .card-count {
            font-size: 22px;
            font-weight: bold;
            color: #222;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .card-content {
                height: auto;
                padding: 15px;
                flex-direction: column;
                align-items: center;
                text-align: center;
            }

            .card-icon {
                margin-bottom: 10px;
            }
        }
    </style>
</head>
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
            <h2 class="page-title">Payroll Dashboard</h2>

            <div class="row g-4 payroll-dashboard">
                <!-- Fixed Employees -->
                <div class="col-lg-4 col-md-6">
                    <a href="enter_payroll.php" class="payroll-card <?= ($current_page == 'enter_payroll.php') ? 'active-card' : '' ?>">
                        <div class="card-content">
                            <div class="card-icon bg-fixed">
                                <i class="fas fa-user-check"></i>
                            </div>
                            <div class="card-info">
                                <h5>Fixed Employees</h5>
                                <span class="card-count"><?= $fixed_employee_count; ?></span>
                            </div>
                        </div>
                    </a>
                </div>

                <!-- Weekly Employees -->
                <div class="col-lg-4 col-md-6">
                    <a href="weekly_employees.php" class="payroll-card <?= ($current_page == 'weekly_employees.php') ? 'active-card' : '' ?>">
                        <div class="card-content">
                            <div class="card-icon bg-weekly">
                                <i class="fas fa-calendar-week"></i>
                            </div>
                            <div class="card-info">
                                <h5>Weekly Employees</h5>
                                <span class="card-count"><?= $weekly_employee_count; ?></span>
                            </div>
                        </div>
                    </a>
                </div>

                <!-- Semi-Monthly Employees -->
                <div class="col-lg-4 col-md-6">
                    <a href="semi-monthly_employees.php" class="payroll-card <?= ($current_page == 'semi-monthly_employees.php') ? 'active-card' : '' ?>">
                        <div class="card-content">
                            <div class="card-icon bg-semi">
                                <i class="fas fa-calendar"></i>
                            </div>
                            <div class="card-info">
                                <h5>Semi-Monthly Employees</h5>
                                <span class="card-count"><?= $semi_employee_count; ?></span>
                            </div>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>