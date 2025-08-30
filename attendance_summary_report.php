<?php 
include_once "functions.php";

session_start();
include __DIR__ . "/config.php"; 

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['user']; 
$role = $_SESSION['role']; 
$current_page = basename($_SERVER['PHP_SELF']);

// Initialize variables
$departments = [];
$employees = [];
$filterDepartment = '';
$filterStartDate = '';
$filterEndDate = '';
$currentMonth = date('Y-m');
$firstDayOfMonth = date('Y-m-01');
$lastDayOfMonth = date('Y-m-t');

// Default dates if not set
if (empty($filterStartDate)) {
    $filterStartDate = $firstDayOfMonth;
}
if (empty($filterEndDate)) {
    $filterEndDate = $lastDayOfMonth;
}

// Process filter form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['filter'])) {
        $filterDepartment = $_POST['department'] ?? '';
        $filterStartDate = $_POST['start_date'] ?? $firstDayOfMonth;
        $filterEndDate = $_POST['end_date'] ?? $lastDayOfMonth;
    }
}

// Fetch all departments for filter dropdown
$deptQuery = "SELECT DISTINCT department FROM payroll_records ORDER BY department";
$deptResult = $conn->query($deptQuery);
if ($deptResult && $deptResult->num_rows > 0) {
    while ($row = $deptResult->fetch_assoc()) {
        $departments[] = $row['department'];
    }
}

// Build the SQL query with filters
$sql = "SELECT 
            id,
            batch_id,
            employee_id,
            name,
            department,
            pay_period,
            start_date,
            end_date,
            basic_salary,
            overtime_pay,
            overtime_hours,
            overtime_rate,
            rest_day_pay,
            regular_holiday_pay,
            regular_ot_pay,
            special_holiday_pay,
            late_deduction,
            absent_deduction,
            undertime_deduction,
            total_earnings,
            total_deductions,
            net_pay
        FROM 
            payroll_records 
        WHERE 
            1=1";

// Add filters if specified
if (!empty($filterDepartment)) {
    $sql .= " AND department = '" . $conn->real_escape_string($filterDepartment) . "'";
}
if (!empty($filterStartDate)) {
    $sql .= " AND start_date >= '" . $conn->real_escape_string($filterStartDate) . "'";
}
if (!empty($filterEndDate)) {
    $sql .= " AND end_date <= '" . $conn->real_escape_string($filterEndDate) . "'";
}

$sql .= " ORDER BY department, name";

// Execute query
$result = $conn->query($sql);

// Check for query execution errors
if (!$result) {
    $error = "Error: " . $conn->error;
}

// Calculate the total net pay for summary panel
$totalNetPay = 0;
$totalEmployees = 0;
$uniqueEmployeeIds = [];

if (isset($result) && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $totalNetPay += $row['net_pay'];
        if (!in_array($row['employee_id'], $uniqueEmployeeIds)) {
            $uniqueEmployeeIds[] = $row['employee_id'];
            $totalEmployees++;
        }
    }
    
    // Reset result pointer for display table
    $result->data_seek(0);
}

// Count total pay periods
$totalPayPeriods = 0;
if (isset($result) && $result->num_rows > 0) {
    $uniqueBatchIds = [];
    $result->data_seek(0);
    while ($row = $result->fetch_assoc()) {
        if (!in_array($row['batch_id'], $uniqueBatchIds)) {
            $uniqueBatchIds[] = $row['batch_id'];
            $totalPayPeriods++;
        }
    }
    $result->data_seek(0);
}
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
            overflow-y: auto;
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
            padding: 30px;
            width: calc(100% - 270px);
            transition: all 0.3s;
            box-sizing: border-box;
            min-height: 100vh;
            background-color: #d6eaf8;
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
        
        /* Additional styles for report */
        .report-header {
            background-color: #eaf2f8;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        
        .filter-form {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        
        .summary-card {
            background: linear-gradient(to right, #a5d6a7, #c8e6c9);
            color: #1b5e20;
            border: none;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .btn-export {
            background-color: #2e86c1;
            color: white;
            border: none;
            transition: all 0.3s;
        }
        
        .btn-export:hover {
            background-color: #1a5276;
            color: white;
        }
        
        /* Make the content scrollable */
        .content-wrapper {
            height: calc(100vh - 20px);
            overflow-y: auto;
            padding-right: 10px;
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

        /* Media Queries for Responsiveness */
        @media (max-width: 991.98px) {
            /* Styles for tablets and smaller devices */
            .summary-card {
                margin-bottom: 15px;
            }
            .report-header h2 {
                font-size: 1.5rem;
            }
        }

        @media (max-width: 767.98px) {
            /* Styles for mobile devices */
            .menu-toggle {
                display: block;
            }
            .sidebar {
                transform: translateX(-100%);
                width: 250px;
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
            .report-header {
                text-align: center;
            }
            .report-header .col-md-6:last-child {
                text-align: center !important;
                margin-top: 15px;
            }
            .sticky-table {
                font-size: 0.875rem;
            }
        }

        @media (max-width: 575.98px) {
            /* Styles for extra small devices */
            .main-content {
                padding: 10px;
            }
            .filter-form, .report-header {
                padding: 15px 10px;
            }
            .filter-form .row {
                row-gap: 10px !important;
            }
            .summary-card .card-title {
                font-size: 1.25rem;
            }
            .summary-card .card-text {
                font-size: 0.875rem;
            }
        }

        /* Print media styles */
        @media print {
            .sidebar, .menu-toggle, .sidebar-overlay {
                display: none !important;
            }
            .main-content {
                margin-left: 0;
                width: 100%;
            }
            .card-custom {
                box-shadow: none;
                height: auto;
            }
            .filter-form, .btn-export {
                display: none !important;
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

    <!-- Main Content -->
    <div class="main-content">
        <div class="content-wrapper">
            <div class="container-fluid">
                <!-- Page Header -->
                <div class="report-header mb-4">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <h2 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Attendance Summary Report</h2>
                            <p class="text-muted mb-0">Comprehensive overview of employee payroll data</p>
                        </div>
                        <div class="col-md-6 text-md-end">
                            <button type="button" class="btn btn-export" onclick="exportToExcel()">
                                <i class="fas fa-file-export me-1"></i> Export to Excel
                            </button>
                       </div>
                    </div>
                </div>
                
                <!-- Filter Form -->
                <div class="card filter-form mb-4">
                    <form method="POST" action="">
                        <div class="row g-3">
                            <div class="col-lg-4 col-md-6">
                                <label for="department" class="form-label">Department</label>
                                <select class="form-select" id="department" name="department">
                                    <option value="">All Departments</option>
                                    <?php foreach ($departments as $dept): ?>
                                        <option value="<?php echo htmlspecialchars($dept); ?>" <?php echo $filterDepartment === $dept ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($dept); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <label for="start_date" class="form-label">Start Date</label>
                                <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo $filterStartDate; ?>">
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <label for="end_date" class="form-label">End Date</label>
                                <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo $filterEndDate; ?>">
                            </div>
                            <div class="col-lg-2 col-md-6 d-flex align-items-end">
                                <button type="submit" name="filter" class="btn btn-primary w-100">
                                    <i class="fas fa-filter me-1"></i> Apply Filter
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Summary Stats -->
                <div class="row mb-4">
                    <div class="col-lg-3 col-md-6 mb-3 mb-lg-0">
                        <div class="card summary-card">
                            <div class="card-body text-center">
                                <h3 class="card-title"><?php echo $totalEmployees; ?></h3>
                                <p class="card-text">Total Employees</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-3 mb-lg-0">
                        <div class="card summary-card">
                            <div class="card-body text-center">
                                <h3 class="card-title"><?php echo $totalPayPeriods; ?></h3>
                                <p class="card-text">Pay Periods</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-3 mb-lg-0">
                        <div class="card summary-card">
                            <div class="card-body text-center">
                                <h3 class="card-title"><?php echo number_format($result->num_rows, 0); ?></h3>
                                <p class="card-text">Total Records</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="card summary-card">
                            <div class="card-body text-center">
                                <h3 class="card-title">₱<?php echo number_format($totalNetPay, 2); ?></h3>
                                <p class="card-text">Total Net Pay</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Main Table -->
                <div class="card card-custom">
                    <div class="card-body">
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php else: ?>
                            <div class="table-container">
                                <table class="table table-striped table-hover sticky-table" id="attendanceTable">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Batch ID</th>
                                            <th>Employee ID</th>
                                            <th>Name</th>
                                            <th>Department</th>
                                            <th>Pay Period</th>
                                            <th>Start Date</th>
                                            <th>End Date</th>
                                            <th>Basic Salary</th>
                                            <th>Overtime Pay</th>
                                            <th>Overtime Hours</th>
                                            <th>Overtime Rate</th>
                                            <th>Rest Day Pay</th>
                                            <th>Reg. Holiday Pay</th>
                                            <th>Reg. OT Pay</th>
                                            <th>Special Holiday Pay</th>
                                            <th>Late Deduction</th>
                                            <th>Absent Deduction</th>
                                            <th>Undertime Deduction</th>
                                            <th>Total Earnings</th>
                                            <th>Total Deductions</th>
                                            <th>Net Pay</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        if (isset($result) && $result->num_rows > 0): 
                                            while ($row = $result->fetch_assoc()): 
                                        ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($row['id']); ?></td>
                                                <td><?php echo htmlspecialchars($row['batch_id']); ?></td>
                                                <td><?php echo htmlspecialchars($row['employee_id']); ?></td>
                                                <td><?php echo htmlspecialchars($row['name']); ?></td>
                                                <td><?php echo htmlspecialchars($row['department']); ?></td>
                                                <td><?php echo htmlspecialchars($row['pay_period']); ?></td>
                                                <td><?php echo htmlspecialchars($row['start_date']); ?></td>
                                                <td><?php echo htmlspecialchars($row['end_date']); ?></td>
                                                <td>₱<?php echo number_format($row['basic_salary'], 2); ?></td>
                                                <td>₱<?php echo number_format($row['overtime_pay'], 2); ?></td>
                                                <td><?php echo htmlspecialchars($row['overtime_hours']); ?></td>
                                                <td>₱<?php echo number_format($row['overtime_rate'], 2); ?></td>
                                                <td>₱<?php echo number_format($row['rest_day_pay'], 2); ?></td>
                                                <td>₱<?php echo number_format($row['regular_holiday_pay'], 2); ?></td>
                                                <td>₱<?php echo number_format($row['regular_ot_pay'], 2); ?></td>
                                                <td>₱<?php echo number_format($row['special_holiday_pay'], 2); ?></td>
                                                <td>₱<?php echo number_format($row['late_deduction'], 2); ?></td>
                                                <td>₱<?php echo number_format($row['absent_deduction'], 2); ?></td>
                                                <td>₱<?php echo number_format($row['undertime_deduction'], 2); ?></td>
                                                <td>₱<?php echo number_format($row['total_earnings'], 2); ?></td>
                                                <td>₱<?php echo number_format($row['total_deductions'], 2); ?></td>
                                                <td>₱<?php echo number_format($row['net_pay'], 2); ?></td>
                                            </tr>
                                        <?php 
                                            endwhile; 
                                        else: 
                                        ?>
                                            <tr>
                                                <td colspan="22" class="text-center">No records found</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript for functionality -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script>
        // Function to export table to Excel
        function exportToExcel() {
            const table = document.getElementById("attendanceTable");
            const wb = XLSX.utils.table_to_book(table, { sheet: "Payroll Data" });
            const dateStr = new Date().toISOString().slice(0, 10);
            XLSX.writeFile(wb, `Payroll_Data_Report_${dateStr}.xlsx`);
        }
        
        // Initialize date range if empty
        document.addEventListener('DOMContentLoaded', function() {
            const startDate = document.getElementById('start_date');
            const endDate = document.getElementById('end_date');
            
            if (!startDate.value) {
                startDate.value = '<?php echo $firstDayOfMonth; ?>';
            }
            
            if (!endDate.value) {
                endDate.value = '<?php echo $lastDayOfMonth; ?>';
            }
            
            // Sidebar toggle functionality for mobile view
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