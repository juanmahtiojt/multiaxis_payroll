<?php 
session_start();
include __DIR__ . "/config.php";  // Include database connection

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['user'];
$role = $_SESSION['role'];

// Admin/User count
$admin_count_query = "SELECT COUNT(*) AS admin_count FROM users WHERE role = 'admin'";
$user_count_query = "SELECT COUNT(*) AS user_count FROM users WHERE role = 'user'";
$admin_result = mysqli_query($conn, $admin_count_query);
$user_result = mysqli_query($conn, $user_count_query);
$admin_count = mysqli_fetch_assoc($admin_result)['admin_count'];
$user_count = mysqli_fetch_assoc($user_result)['user_count'];

// Employee count
$employee_count_query = "SELECT COUNT(*) AS employee_count FROM daily_rate";
$employee_result = mysqli_query($conn, $employee_count_query);
$employee_count = mysqli_fetch_assoc($employee_result)['employee_count'];

// Holiday count
$holiday_count_query = "SELECT COUNT(*) AS holiday_count FROM holidays";
$holiday_result = mysqli_query($conn, $holiday_count_query);
$holiday_count = mysqli_fetch_assoc($holiday_result)['holiday_count'];

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
    <title>Dashboard</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
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
             overflow: hidden;
            box-shadow: 3px 0 10px rgba(0, 0, 0, 0.1);
            z-index: 100;
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
        
        /* Main Content Area */
        .main-content {
            margin-left: 270px;
            padding: 30px;
            width: calc(100% - 270px);
            transition: all 0.3s;
            box-sizing: border-box;
            min-height: 100vh;
            background-color: #d6eaf8;
        }
        .container-box {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            width: 100%;
            max-width: 1400px;
            height: 90vh;
            overflow: auto;
        }
        .welcome-section {
            background: linear-gradient(135deg, #5dade2 0%, #3498db 100%);
            color: white;
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(52, 152, 219, 0.2);
        }
        
        /* Panel Styles */
        .user-count-panel {
            background-color: #ffffff;
            color: #333;
            padding: 25px 20px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            margin-bottom: 30px;
            text-align: center;
            transition: transform 0.2s ease;
            border-left: 4px solid;
            height: 120px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer; /* Add cursor pointer to indicate clickable */
        }
        .user-count-panel:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.12);
        }
        .panel-content {
            display: flex;
            align-items: center;
            width: 100%;
        }
        .panel-icon {
            font-size: 28px;
            margin-right: 20px;
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            color: white;
        }
        .panel-text {
            text-align: left;
        }
        .panel-label {
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 5px;
            color: #777;
        }
        .panel-count {
            font-size: 24px;
            font-weight: 700;
        }
        
        /* Panel colors */
        .panel-admin {
            border-left-color: #2c3e50;
        }
        .panel-admin .panel-icon {
            background-color: #2c3e50;
        }
        .panel-employee {
            border-left-color: #3498db;
        }
        .panel-employee .panel-icon {
            background-color: #3498db;
        }
        .panel-holiday {
            border-left-color: #e74c3c;
        }
        .panel-holiday .panel-icon {
            background-color: #e74c3c;
        }
        .panel-fixed {
            border-left-color: #6f42c1;
        }
        .panel-fixed .panel-icon {
            background-color: #6f42c1;
        }
        .panel-weekly {
            border-left-color: #17a2b8;
        }
        .panel-weekly .panel-icon {
            background-color: #17a2b8;
        }
        .panel-semi {
            border-left-color: #fd7e14;
        }
        .panel-semi .panel-icon {
            background-color: #fd7e14;
        }
        
        .panels-container {
            padding: 0 15px;
        }
    </style>
</head>
<body>
    <!-- Improved Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <img src="http://localhost/my_project/images/MULTI-removebg-preview.png" class="sidebar-logo" alt="Company Logo">
            <div class="company-name">Multi Axis Handlers & Tech Inc</div>
        </div>
        
                
        <div class="nav-section">
            <div class="nav-section-title">Main Navigation</div>
            <a href="dashboard.php" class="<?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>">
                <i class="fas fa-home"></i> Dashboard
            </a>
            <?php if ($role === 'admin') : ?>
                <a href="add_user.php" class="<?php echo ($current_page == 'add_user.php') ? 'active' : ''; ?>">
                    <i class="fas fa-user-plus"></i> Employees
                </a>
            <?php endif; ?>
            <a href="employee_attendance.php" class="<?php echo ($current_page == 'employee_attendance.php') ? 'active' : ''; ?>">
                <i class="fas fa-clipboard-check"></i> Attendance
            </a>
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
        <div class="container-box">
            <div class="welcome-section">
                <h2>
                    <i class="fas fa-user-circle me-2"></i>
                    Welcome, <?php echo htmlspecialchars($username); ?>!
                </h2>
                <p class="mb-0 fs-5">
                    <i class="fas fa-circle-check me-2"></i>
                    You are now logged in to Multi Axis Payroll System
                </p>
            </div>

            <div class="panels-container">
                <div class="row">
                    <div class="col-md-4">
                        <!-- Admin panel with link to admin.php -->
                        <a href="admin.php" style="text-decoration: none; display: block;">
                            <div class="user-count-panel panel-admin">
                                <div class="panel-content">
                                    <div class="panel-icon">
                                        <i class="fas fa-user-shield"></i>
                                    </div>
                                    <div class="panel-text">
                                        <div class="panel-label">ADMINISTRATORS</div>
                                        <div class="panel-count"><?php echo $admin_count; ?></div>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="col-md-4">
                        <!-- Employees panel with link to employees.php -->
                        <a href="add_user.php" style="text-decoration: none; display: block;">
                            <div class="user-count-panel panel-employee">
                                <div class="panel-content">
                                    <div class="panel-icon">
                                        <i class="fas fa-briefcase"></i>
                                    </div>
                                    <div class="panel-text">
                                        <div class="panel-label">EMPLOYEES</div>
                                        <div class="panel-count"><?php echo $employee_count; ?></div>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="col-md-4">
                        <a href="add_holiday.php" style="text-decoration: none; display: block;">
                            <div class="user-count-panel panel-holiday">
                                <div class="panel-content">
                                    <div class="panel-icon">
                                        <i class="fas fa-calendar-alt"></i>
                                    </div>
                                    <div class="panel-text">
                                        <div class="panel-label">HOLIDAYS</div>
                                        <div class="panel-count"><?php echo $holiday_count; ?></div>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <a href="enter_payroll.php" style="text-decoration: none; display: block;">
                            <div class="user-count-panel panel-fixed">
                                <div class="panel-content">
                                    <div class="panel-icon">
                                        <i class="fas fa-user-check"></i>
                                    </div>
                                    <div class="panel-text">
                                        <div class="panel-label">FIXED EMPLOYEES</div>
                                        <div class="panel-count"><?php echo $fixed_employee_count; ?></div>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="col-md-4">
                        <a href="weekly_employees.php" style="text-decoration: none; display: block;">
                            <div class="user-count-panel panel-weekly">
                                <div class="panel-content">
                                    <div class="panel-icon">
                                        <i class="fas fa-calendar-week"></i>
                                    </div>
                                    <div class="panel-text">
                                        <div class="panel-label">WEEKLY EMPLOYEES</div>
                                        <div class="panel-count"><?php echo $weekly_employee_count; ?></div>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="col-md-4">
                        <a href="semi_monthly_employees.php" style="text-decoration: none; display: block;">
                            <div class="user-count-panel panel-semi">
                                <div class="panel-content">
                                    <div class="panel-icon">
                                        <i class="fas fa-calendar"></i>
                                    </div>
                                    <div class="panel-text">
                                        <div class="panel-label">SEMI-MONTHLY EMPLOYEES</div>
                                        <div class="panel-count"><?php echo $semi_employee_count; ?></div>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Optional JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>