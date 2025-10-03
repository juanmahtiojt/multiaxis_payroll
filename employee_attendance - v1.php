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

$employeeData = $_SESSION['attendance_data'] ?? [];
$uploadError = $_SESSION['upload_error'] ?? null;
unset($_SESSION['upload_error']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Attendance</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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

        .main-content {
            margin-left: 270px;
            padding: 30px;
            width: calc(100% - 270px);
            overflow: hidden;
            height: 100vh;
            padding-top: 5px;
            transition: all 0.3s;
        }
        
        .card-custom {
            border-radius: 25px;
            box-shadow: 0 6px 15px rgba(0,0,0,0.1);
        }
        
        /* Enhanced table styles with sticky header */
        .table-container {
            max-height: 600px;
            overflow-y: auto;
            position: relative;
        }
        
        .sticky-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }
        
        .sticky-table thead {
            position: sticky;
            top: 0;
            z-index: 10;
        }
        
        .sticky-table th {
            background-color: #343a40;
            color: white;
            position: sticky;
            top: 0;
            box-shadow: 0 2px 2px rgba(0,0,0,.1);
        }
        
        .sticky-date-header {
            position: sticky;
            top: 0;
            background-color: #e9ecef;
            z-index: 5;
            box-shadow: 0 2px 2px rgba(0,0,0,.1);
        }
        
        .date-heading {
            position: sticky;
            top: 0;
            z-index: 6;
        }
        
        .table-header {
            position: sticky;
            top: 41px; /* Height of the date header */
            z-index: 5;
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
            .card-custom {
                border-radius: 20px;
            }
            .table-container {
                max-height: 500px;
            }
        }

        @media (max-width: 767.98px) {
            /* Styles for mobile devices */
            .menu-toggle {
                display: block;
            }
            .sidebar {
                transform: translateX(-100%);
                z-index: 1030;
                overflow-y: auto;
            }
            .sidebar.active {
                transform: translateX(0);
            }
            .main-content {
                margin-left: 0;
                width: 100%;
                padding: 15px;
                padding-top: 60px; /* Space for menu button */
            }
            .card-custom {
                border-radius: 15px;
            }
        }

        @media (max-width: 575.98px) {
            /* Styles for extra small devices */
            .main-content {
                padding: 10px;
                padding-top: 60px;
            }
            .card-custom {
                border-radius: 10px;
            }
            .table-container {
                max-height: 400px;
            }
            .sticky-table {
                font-size: 0.85rem;
            }
        }

        /* Print media styles */
        @media print {
            .sidebar, .menu-toggle, .sidebar-overlay, .btn, .form-control, .card-header {
                display: none !important;
            }
            .main-content {
                margin: 0;
                padding: 0;
                width: 100%;
            }
            .table-container {
                max-height: none;
                overflow: visible;
            }
            .sticky-table thead {
                position: static;
            }
            .sticky-table th {
                position: static;
            }
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
        <img src="http://localhost/multiaxis_payroll_system/my_project/images/MULTI-removebg-preview.png" class="sidebar-logo" alt="Company Logo">
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
    <a href="employee_attendance_monthly.php" class="<?php echo ($current_page == 'employee_attendance_monthly.php') ? 'active' : ''; ?>">
        <i class="fas fa-clipboard-check"></i> Attendance_Monthly
    </a>
    <a href="employee_attendance.php" class="<?php echo ($current_page == 'employee_attendance.php') ? 'active' : ''; ?>">
        <i class="fas fa-clipboard-check"></i> Attendance_Weekly
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

<!-- Main Content -->
<div class="main-content">
    <div class="container-fluid">
        <div class="card card-custom mb-4">
            <div class="card-body">
                <h4 class="mb-3 text-center"><i class="fas fa-clipboard-check"></i> Employee Attendance Records</h4>

                <!-- Upload and Delete Buttons -->
                <div class="mb-3 d-flex flex-wrap justify-content-between">
                    <a href="upload_excel.php" class="btn btn-success mb-2 mb-sm-0"><i class="fas fa-file-upload"></i> Upload New Attendance Excel</a>
                    <?php if (!empty($employeeData)): ?>
                        <form action="clear_attendance.php" method="POST" onsubmit="return confirm('Are you sure you want to delete all attendance records?');">
                            <button type="submit" class="btn btn-danger"><i class="fas fa-trash"></i> Delete All Attendance</button>
                        </form>
                    <?php endif; ?>
                </div>

                <!-- Upload error -->
                <?php if ($uploadError): ?>
                    <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($uploadError) ?></div>
                <?php endif; ?>

                <!-- Attendance Table with Sticky Headers -->
                <?php if (!empty($employeeData)): ?>
                    <div class="table-container">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped sticky-table">
                                <tbody>
                                    <?php
                                    // Flatten and group by date
                                    $grouped = [];

                                    foreach ($employeeData as $employee) {
                                        foreach ($employee['dates'] as $i => $date) {
                                            $grouped[$date][] = [
                                                'id_no'      => $employee['id_no'],
                                                'department' => $employee['department'],
                                                'name'       => $employee['name'],
                                                'date'       => $date,
                                                'am_in'      => $employee['am_in'][$i] ?? '',
                                                'am_out'     => $employee['am_out'][$i] ?? ''
                                            ];
                                        }
                                    }

                                    // Sort dates
                                    ksort($grouped);

                                    foreach ($grouped as $date => $entries):
                                        $formattedDate = date("F j, Y", strtotime($date));
                                    ?>
                                        <!-- Date Header (sticky) -->
                                        <tr>
                                            <td colspan='8' class='fw-bold bg-light text-center fs-5 sticky-date-header date-heading'>
                                                <i class='fas fa-calendar-day'></i> Attendance for: <?= $formattedDate ?>
                                            </td>
                                        </tr>
                                        
                                        <!-- Table Headers (sticky) -->
                                        <tr class='table-dark table-header'>
                                            <th>ID No.</th>
                                            <th>Department</th>
                                            <th>Name</th>
                                            <th>Date</th>
                                            <th>AM IN</th>
                                            <th>AM OUT</th>
                                            <th>Hours Worked</th>
                                            <th>Absences</th>
                                        </tr>
                                        
                                        <?php foreach ($entries as $row):
                                            $amIn = $row['am_in'];
                                            $amOut = $row['am_out'];
                                            $hoursWorked = '';
                                            $absent = '';

                                            if (!empty($amIn) && !empty($amOut)) {
                                                $inTime = DateTime::createFromFormat('H:i', $amIn);
                                                $outTime = DateTime::createFromFormat('H:i', $amOut);

                                                if ($inTime && $outTime) {
                                                    $interval = $inTime->diff($outTime);
                                                    $workedHours = $interval->h + $interval->i / 60;
                                                    $workedHours -= 1; // deduct 1 hour for lunch
                                                    $hoursWorked = number_format($workedHours, 2) . ' hrs';
                                                } else {
                                                    $absent = 'Absent';
                                                }
                                            } else {
                                                $absent = 'Absent';
                                            }
                                        ?>
                                            <tr>
                                                <td><?= htmlspecialchars($row['id_no']) ?></td>
                                                <td><?= htmlspecialchars($row['department']) ?></td>
                                                <td><?= htmlspecialchars($row['name']) ?></td>
                                                <td><?= htmlspecialchars($row['date']) ?></td>
                                                <td><?= htmlspecialchars($amIn) ?></td>
                                                <td><?= htmlspecialchars($amOut) ?></td>
                                                <td><?= $hoursWorked ?></td>
                                                <td><?= $absent ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info text-center">
                        <i class="fas fa-info-circle"></i> No attendance data available. Please upload a file.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

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