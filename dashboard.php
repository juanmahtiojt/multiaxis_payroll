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
            min-height: 100vh;
            overflow-x: hidden;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            position: relative;
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
            height: 96vh;
            min-height: calc(85vh);
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
            padding: 20px 15px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            margin-bottom: 20px;
            text-align: center;
            transition: transform 0.2s ease;
            border-left: 4px solid;
            height: auto;
            min-height: 120px;
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
            font-size: 22px;
            margin-right: 15px;
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            color: white;
            flex-shrink: 0;
        }
        .panel-text {
            text-align: left;
            flex-grow: 1;
        }
        .panel-label {
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 5px;
            color: #777;
        }
        .panel-count {
            font-size: 22px;
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
        
        /* Responsive Styles */
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
        
        /* Media Queries for Responsiveness */
        @media (max-width: 991.98px) {
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
            .menu-toggle {
                display: block;
            }
            .sidebar {
                width: 0;
                padding: 0;
                overflow-y: auto;
            }
            .sidebar.active {
                width: 270px;
                padding: 0;
            }
            .main-content {
                margin-left: 0;
                width: 100%;
                padding: 15px;
            }
            .container-box {
                padding: 20px 15px;
                border-radius: 8px;
            }
            .welcome-section {
                padding: 20px;
                margin-bottom: 20px;
            }
            .welcome-section h2 {
                font-size: 1.3rem;
            }
            .welcome-section p {
                font-size: 0.9rem !important;
            }
        }
        
        @media (max-width: 575.98px) {
            .main-content {
                padding: 10px;
            }
            .container-box {
                padding: 15px 10px;
            }
            .welcome-section {
                padding: 15px;
                margin-bottom: 15px;
            }
            .panels-container {
                padding: 0 5px;
            }
            .user-count-panel {
                margin-bottom: 15px;
                min-height: 90px;
            }
            .panel-icon {
                width: 35px;
                height: 35px;
                font-size: 16px;
                margin-right: 10px;
            }
            .panel-count {
                font-size: 16px;
            }
            .panel-label {
                font-size: 11px;
            }
        }
        
        /* Overlay for mobile sidebar */
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
        
        @media print {
            .sidebar, .menu-toggle, .sidebar-overlay {
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

.footer {
  flex-shrink: 0;
  background: linear-gradient(135deg, #1e5799 0%, #2989d8 50%, #207cca 100%);
  box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
}

.hover-link {
  transition: all 0.2s ease;
  padding: 3px 0;
  display: inline-block;
}

.hover-link:hover {
  transform: translateX(5px);
  color: #f8f9fa !important;
  text-shadow: 0 0 1px rgba(255,255,255,0.7);
}
</style>

</head>
<body>
    <!-- Mobile Menu Toggle Button -->
    <button class="menu-toggle" id="menuToggle">
        <i class="fas fa-bars"></i>
    </button>
    
    <!-- Sidebar Overlay for Mobile -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    
    <!-- Improved Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <img src="my_project\images\MULTI-removebg-preview.png" class="sidebar-logo" alt="Company Logo">
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
            <a href="employee_attendance_monthly.php" class="<?php echo ($current_page == 'employee_attendance_monthly.php') ? 'active' : ''; ?>">
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
            <a href="about.php" class="<?php echo ($current_page == 'about.php') ? 'active' : ''; ?>">
                <i class="fas fa-info-circle"></i> About
            </a>
            <a href="help.php" class="<?php echo ($current_page == 'help.php') ? 'active' : ''; ?>">
                <i class="fas fa-question-circle"></i> Help & Support
            </a>
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
                <div class="row g-3">
                    <div class="col-lg-4 col-md-6">
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
                    <div class="col-lg-4 col-md-6">
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
                    <div class="col-lg-4 col-md-6">
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
                    <div class="col-lg-4 col-md-6">
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
                    <div class="col-lg-4 col-md-6">
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
                    <div class="col-lg-4 col-md-6">
                        <a href="semi-monthly_employees.php" style="text-decoration: none; display: block;">
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
<!-- Footer Navigation -->
<footer class="footer mt-auto py-4 bg-gradient-primary text-white">
  <div class="container">
    <div class="row">
      <div class="col-lg-4 col-md-6 mb-4 mb-md-0">
        <h5 class="text-uppercase mb-3 fw-bold">Navigation</h5>
        <ul class="list-unstyled">
          <li><a href="dashboard.php" class="text-white text-decoration-none hover-link">Dashboard</a></li>
          <li><a href="add_user.php" class="text-white text-decoration-none hover-link">Employees</a></li>
          <li><a href="employee_attendance.php" class="text-white text-decoration-none hover-link">Attendance</a></li>
          <li><a href="reports.php" class="text-white text-decoration-none hover-link">Deductions</a></li>
        </ul>
      </div>
      
      <div class="col-lg-4 col-md-6 mb-4 mb-md-0">
        <h5 class="text-uppercase mb-3 fw-bold">Reports</h5>
        <ul class="list-unstyled">
          <li><a href="attendance_summary_report.php" class="text-white text-decoration-none hover-link">Summary</a></li>
          <li><a href="view_payslips.php" class="text-white text-decoration-none hover-link">Payslips</a></li>
          <li><a href="add_holiday.php" class="text-white text-decoration-none hover-link">Holidays</a></li>
          <li><a href="enter_payroll.php" class="text-white text-decoration-none hover-link">Payroll</a></li>
        </ul>
      </div>
      
      <div class="col-lg-4 col-md-6 mb-4 mb-md-0">
        <h5 class="text-uppercase mb-3 fw-bold">Admin</h5>
        <ul class="list-unstyled">
          <li><a href="admin.php" class="text-white text-decoration-none hover-link">Admin</a></li>
          <li><a href="add_employee.php" class="text-white text-decoration-none hover-link">Add Employee</a></li>
          <li><a href="upload_excel.php" class="text-white text-decoration-none hover-link">Upload Excel</a></li>
          <li><a href="logout.php" class="text-white text-decoration-none hover-link">Logout</a></li>
        </ul>
      </div>
    </div>
  </div>
</footer>

    </div>

    <!-- JavaScript -->
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
</body>
</html>