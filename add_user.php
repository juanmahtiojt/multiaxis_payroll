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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
        .sidebar.logo-link {
            margin: 0;
        }

        /* Main Content Area */
        .main-content {
            margin-left: 270px;
            padding: 40px;
            width: calc(100% - 270px);
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            align-items: center;
            height: 100vh;
            transition: all 0.3s;
            padding-top: 8px;
            box-sizing: border-box;
            min-height: 100vh;
            overflow: hidden;
            margin-top: 15px;
            background-color: #d6eaf8;
        }

        .container-box {
            background: white;
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0px 6px 20px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 1400px;
            height: 98vh;
            overflow: hidden;
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
        .logo-link a:hover:not(.active) {
            background: none;
            color: inherit;
            border-left: none;
            border-left-color: none;

        }

        /* Media Queries for Responsiveness */
        @media (max-width: 991.98px) {
            /* Styles for tablets and smaller devices */
            .container-box {
                padding: 30px 20px;
            }
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
            .nav-tabs .nav-link {
                padding: 8px 12px;
                font-size: 14px;
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
            }
            .container-box {
                padding: 20px 15px;
                border-radius: 12px;
                height: calc(100vh - 30px);
            }
            .table-responsive {
                max-height: 60vh;
            }
            .add-btn {
                margin-bottom: 10px;
            }
            h3 {
                font-size: 1.5rem;
            }
            .nav-tabs {
                overflow-x: auto;
                flex-wrap: nowrap;
                margin-bottom: 15px;
            }
            .nav-tabs .nav-link {
                white-space: nowrap;
                padding: 6px 10px;
                font-size: 13px;
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
            .table td, .table th {
                font-size: 12px;
                padding: 6px 4px;
            }
            .btn-sm {
                padding: 0.2rem 0.4rem;
                font-size: 0.75rem;
            }
        }

        /* Print media styles */
        @media print {
            .sidebar, .menu-toggle, .sidebar-overlay, .btn, .form-control, .card-header, .nav-tabs {
                display: none !important;
            }
            .main-content {
                margin-left: 0;
                width: 100%;
                padding: 0;
            }
            .container-box {
                box-shadow: none;
                padding: 0;
                height: auto;
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
        <div class="logo-link">
        <a href="dashboard.php" >
        <img src="my_project/images/MULTI-removebg-preview.png" class="sidebar-logo" alt="Company Logo">
        </a>
        </div>
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
        <?php if (isset($_GET['status']) && $_GET['status'] === 'deleted'): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <strong>Success!</strong> Employee deleted successfully.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>


        <!-- Tabs for better organization -->
        <div class="mb-3">
            <input type="text" id="employeeSearch" class="form-control" placeholder="Search employee by name, ID, or department...">
        </div>

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
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const searchInput = document.getElementById('employeeSearch');
        const tables = document.querySelectorAll('.tab-pane table');

        searchInput.addEventListener('input', function () {
            const query = this.value.toLowerCase();

            tables.forEach(table => {
                const rows = table.querySelectorAll('tbody tr');
                rows.forEach(row => {
                    const rowText = row.textContent.toLowerCase();
                    row.style.display = rowText.includes(query) ? '' : 'none';
                });
            });
        });
    });
    </script>
</body>
</html>