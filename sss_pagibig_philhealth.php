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

$employeeData = $_SESSION['employee_deductions'] ?? [];
$uploadError = $_SESSION['upload_error'] ?? null;
unset($_SESSION['upload_error']);

// Create a function to fetch the deductions for an employee
function getDeductions($id_no, $conn) {
    $sql = "SELECT sss, pagibig, philhealth FROM employee_deductions WHERE id_no = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $id_no);
    $stmt->execute();
    $stmt->bind_result($sss, $pagibig, $philhealth);
    $stmt->fetch();
    $stmt->close();
    return ['sss' => $sss, 'pagibig' => $pagibig, 'philhealth' => $philhealth];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>SSS, PagIBIG, PhilHealth Deductions</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
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
            transition: all 0.3s;
            box-sizing: border-box;
            overflow: hidden;
        }
        
        .card-custom {
            border-radius: 15px;
            box-shadow: 0 6px 15px rgba(0,0,0,0.1);
            background-color: white;
        }
        
        .table-container {
            max-height: 85vh;
            overflow-y: auto;
        }
        
        .table th, .table td {
            vertical-align: middle;
        }
        
        .table-container th, .table-container thead th {
            background-color: #000;
            color: white;
            position: sticky;
            top: 0;
            z-index: 1;
        }
        
        @media print {
            .sidebar, .btn, .form-control, .card-header {
                display: none !important;
            }
            .main-content {
                margin: 0;
                padding: 0;
                width: 100%;
            }
        }
    </style>
</head>
<body>

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
        <a href="holiday_overtime.php" class="<?php echo ($current_page == 'holiday_overtime.php') ? 'active' : ''; ?>">
            <i class="fas fa-clock"></i> Holiday & Overtime
        </a>
        <a href="sss_pagibig_philhealth.php" class="<?php echo ($current_page == 'sss_pagibig_philhealth.php') ? 'active' : ''; ?>">
            <i class="fas fa-file-invoice-dollar"></i> Government Benefits
        </a>
        <a href="view_payslips.php" class="<?php echo ($current_page == 'view_payslips.php') ? 'active' : ''; ?>">
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
                <h4 class="mb-3 text-center">SSS, PagIBIG, PhilHealth Deductions</h4>

                <!-- Deductions Table -->
                <?php if (!empty($employeeData)): ?>
                    <div class="table-container">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Employee Name</th>
                                    <th>SSS Deduction</th>
                                    <th>Pag-IBIG Deduction</th>
                                    <th>PhilHealth Deduction</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Loop through each employee and display deductions
                                foreach ($employeeData as $employee):
                                    // Fetch deductions for the current employee
                                    $deductions = getDeductions($employee['id_no'], $conn);
                                    ?>
                                    <tr>
                                        <td><?= htmlspecialchars($employee['name']) ?></td>
                                        <td><?= number_format($deductions['sss'], 2) ?></td>
                                        <td><?= number_format($deductions['pagibig'], 2) ?></td>
                                        <td><?= number_format($deductions['philhealth'], 2) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p>No deduction data available. Please upload a file.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>