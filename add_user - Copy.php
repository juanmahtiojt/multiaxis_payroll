<?php
session_start();
include __DIR__ . "/config.php";

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['user'];
$role = $_SESSION['role'];

// Fetch employee data
$employee_query = "SELECT * FROM employees";
$employee_result = mysqli_query($conn, $employee_query);

// Modified query to include all the requested fields
$daily_rate_query = "SELECT dr.id_no, dr.name, dr.department, dr.daily_rate, dr.pay_schedule, 
                    COALESCE(e.sss_no, 'N/A') as sss_no, 
                    COALESCE(e.pagibig_no, 'N/A') as pagibig_no,
                    COALESCE(e.tin_no, 'N/A') as tin_no,
                    COALESCE(e.philhealth_no, 'N/A') as philhealth_no,
                    COALESCE(e.sss_premium, 0) as sss_premium,
                    COALESCE(e.sss_loan, 0) as sss_loan,
                    COALESCE(e.pagibig_premium, 0) as pagibig_premium,
                    COALESCE(e.pagibig_loan, 0) as pagibig_loan,
                    COALESCE(e.philhealth, 0) as philhealth,
                    COALESCE(e.cash_advance, 0) as cash_advance,
                    COALESCE(e.leave_with_pay, 0) as leave_with_pay,
                    COALESCE(e.leave_without_pay, 0) as leave_without_pay,
                    COALESCE(e.available_leave, 0) as available_leave
                    FROM multiaxis_payroll_system.daily_rate dr
                    LEFT JOIN employees e ON dr.id_no = e.id_no";
$daily_rate_result = mysqli_query($conn, $daily_rate_query);

$current_page = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Employee Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
            padding: 40px;
            width: calc(100% - 270px);
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            align-items: center;
            height: 100vh;
            transition: 0.3s;
            padding-top: 8px;
            box-sizing: border-box;
            min-height: 100vh;
            overflow: hidden;

        }

        .container-box {
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0px 6px 20px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 1400px;
            height: 98vh;
        }

        .table thead {
            background-color: #007bff;
            color: white;
        }

        .btn-custom {
            background-color: #007bff;
            color: white;
            font-size: 14px;
            padding: 5px 10px;
            border-radius: 5px;
            border: none;
        }

        .btn-custom:hover {
            background-color: #0056b3;
        }

        .table td, .table th {
            vertical-align: middle;
            font-size: 14px;
            padding: 8px;
        }

        .add-btn {
            float: right;
            margin-bottom: 15px;
        }

        .table-responsive {
            max-height: 70vh;
            overflow-y: auto;
        }

        .table thead th {
            position: sticky;
            top: 0;
            z-index: 10;
            background-color: black !important;
            color: white !important;
        }

        .nav-tabs {
            margin-bottom: 20px;
        }

        .tab-content {
            padding: 15px 0;
        }

        @media print {
            .sidebar, .btn, .form-control, .card-header, .nav-tabs {
                display: none !important;
            }
            .main-content {
                margin: 0;
                padding: 0;
            }
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
    <div class="container-box">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3 class="mb-0"><i class="fas fa-users"></i> Employee Management</h3>
            <?php if ($role === 'admin') : ?>
                <a href="add_employee.php" class="btn btn-success add-btn"><i class="fas fa-plus"></i> Add Employee</a>
            <?php endif; ?>
        </div>

        <!-- Tabs for better organization -->
        <ul class="nav nav-tabs" id="employeeTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="basic-tab" data-bs-toggle="tab" data-bs-target="#basic" type="button" role="tab" aria-controls="basic" aria-selected="true">Basic Info</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="gov-ids-tab" data-bs-toggle="tab" data-bs-target="#gov-ids" type="button" role="tab" aria-controls="gov-ids" aria-selected="false">Government IDs</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="deductions-tab" data-bs-toggle="tab" data-bs-target="#deductions" type="button" role="tab" aria-controls="deductions" aria-selected="false">Deductions</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="leaves-tab" data-bs-toggle="tab" data-bs-target="#leaves" type="button" role="tab" aria-controls="leaves" aria-selected="false">Leaves</button>
            </li>
        </ul>

        <div class="tab-content" id="employeeTabContent">
            <!-- Basic Information Tab -->
            <div class="tab-pane fade show active" id="basic" role="tabpanel" aria-labelledby="basic-tab">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Employee ID</th>
                                <th>Name</th>
                                <th>Department</th>
                                <th>Daily Rate</th>
                                <th>Pay Schedule</th>
                                <th>Salary</th>
                                <th>Semi-Monthly</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php 
                        // Reset pointer to beginning
                        mysqli_data_seek($daily_rate_result, 0);
                        while ($daily_rate = mysqli_fetch_assoc($daily_rate_result)) { 
                            $salary = $daily_rate['daily_rate'] * 30;
                            $semi_monthly_salary = $salary / 2;
                        ?>
                            <tr>
                                <td><?= $daily_rate['id_no']; ?></td>
                                <td><?= $daily_rate['name']; ?></td>
                                <td><?= $daily_rate['department']; ?></td>
                                <td><?= number_format($daily_rate['daily_rate'], 2); ?></td>
                                <td><?= ucfirst($daily_rate['pay_schedule']); ?></td>
                                <td><?= number_format($salary, 2); ?></td>
                                <td><?= number_format($semi_monthly_salary, 2); ?></td>
                                <td>
                                    <a href="edit_employee.php?id=<?= $daily_rate['id_no']; ?>" class="btn btn-sm btn-primary"><i class="fas fa-edit"></i> Edit</a>
                                    <a href="delete_daily_rate.php?id=<?= $daily_rate['id_no']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?');"><i class="fas fa-trash"></i> Delete</a>
                                </td>
                            </tr>
                        <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Government IDs Tab -->
            <div class="tab-pane fade" id="gov-ids" role="tabpanel" aria-labelledby="gov-ids-tab">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Employee ID</th>
                                <th>Name</th>
                                <th>SSS No.</th>
                                <th>Pag-IBIG No.</th>
                                <th>TIN No.</th>
                                <th>PhilHealth No.</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php 
                        // Reset pointer to beginning
                        mysqli_data_seek($daily_rate_result, 0);
                        while ($daily_rate = mysqli_fetch_assoc($daily_rate_result)) { 
                        ?>
                            <tr>
                                <td><?= $daily_rate['id_no']; ?></td>
                                <td><?= $daily_rate['name']; ?></td>
                                <td><?= $daily_rate['sss_no']; ?></td>
                                <td><?= $daily_rate['pagibig_no']; ?></td>
                                <td><?= $daily_rate['tin_no']; ?></td>
                                <td><?= $daily_rate['philhealth_no']; ?></td>
                                <td>
                                    <a href="edit_employee_ids.php?id=<?= $daily_rate['id_no']; ?>" class="btn btn-sm btn-primary"><i class="fas fa-edit"></i> Edit</a>
                                </td>
                            </tr>
                        <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Deductions Tab -->
            <div class="tab-pane fade" id="deductions" role="tabpanel" aria-labelledby="deductions-tab">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Employee ID</th>
                                <th>Name</th>
                                <th>SSS Premium</th>
                                <th>SSS Loan</th>
                                <th>Pag-IBIG Premium</th>
                                <th>Pag-IBIG Loan</th>
                                <th>PhilHealth</th>
                                <th>Cash Advance</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php 
                        // Reset pointer to beginning
                        mysqli_data_seek($daily_rate_result, 0);
                        while ($daily_rate = mysqli_fetch_assoc($daily_rate_result)) { 
                        ?>
                            <tr>
                                <td><?= $daily_rate['id_no']; ?></td>
                                <td><?= $daily_rate['name']; ?></td>
                                <td><?= number_format($daily_rate['sss_premium'], 2); ?></td>
                                <td><?= number_format($daily_rate['sss_loan'], 2); ?></td>
                                <td><?= number_format($daily_rate['pagibig_premium'], 2); ?></td>
                                <td><?= number_format($daily_rate['pagibig_loan'], 2); ?></td>
                                <td><?= number_format($daily_rate['philhealth'], 2); ?></td>
                                <td><?= number_format($daily_rate['cash_advance'], 2); ?></td>
                                <td>
                                    <a href="edit_employee_deductions.php?id=<?= $daily_rate['id_no']; ?>" class="btn btn-sm btn-primary"><i class="fas fa-edit"></i> Edit</a>
                                </td>
                            </tr>
                        <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Leaves Tab -->
            <div class="tab-pane fade" id="leaves" role="tabpanel" aria-labelledby="leaves-tab">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Employee ID</th>
                                <th>Name</th>
                                <th>Leave With Pay</th>
                                <th>Leave Without Pay</th>
                                <th>Available Leave</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php 
                        // Reset pointer to beginning
                        mysqli_data_seek($daily_rate_result, 0);
                        while ($daily_rate = mysqli_fetch_assoc($daily_rate_result)) { 
                        ?>
                            <tr>
                                <td><?= $daily_rate['id_no']; ?></td>
                                <td><?= $daily_rate['name']; ?></td>
                                <td><?= $daily_rate['leave_with_pay']; ?></td>
                                <td><?= $daily_rate['leave_without_pay']; ?></td>
                                <td><?= $daily_rate['available_leave']; ?></td>
                                <td>
                                    <a href="edit_employee_leaves.php?id=<?= $daily_rate['id_no']; ?>" class="btn btn-sm btn-primary"><i class="fas fa-edit"></i> Edit</a>
                                </td>
                            </tr>
                        <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>