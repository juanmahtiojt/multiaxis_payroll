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
    <title>Attendance Summary</title>
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
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.1);
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
            box-shadow: 0 2px 2px rgba(0, 0, 0, .1);
        }

        /* Additional styles for report */
        .report-header {
            background-color: #eaf2f8;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }

        .filter-form {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }

        .summary-card {
            background: linear-gradient(to right, #a5d6a7, #c8e6c9);
            color: #1b5e20;
            border: none;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .add-btn {
            color: #ffffffff;
            float: right;
            margin-bottom: 15px;
        }

        .add-btn:hover {
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

         /* Buttons container */
    .btn-group {
      display: flex;
      gap: 8px;
      justify-content: center;
    }

    /* Base button styles */
    .btn {
      padding: 6px 12px;
      font-size: 0.875rem;
      font-weight: 600;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      transition: background-color 0.3s ease, color 0.3s ease;
      min-width: 60px;
      text-align: center;
      user-select: none;
    }

    /* View button */
    .btn-view {
      background-color: #17a2b8;
      color: #fff;
    }
    .btn-view:hover,
    .btn-view:focus {
      background-color: #138496;
      outline: none;
    }

    /* Edit button */
    .btn-edit {
      background-color: #007bff;
      color: #fff;
    }
    .btn-edit:hover,
    .btn-edit:focus {
      background-color: #0056b3;
      outline: none;
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

            .filter-form,
            .report-header {
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

            .sidebar,
            .menu-toggle,
            .sidebar-overlay {
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

            .filter-form,
            .btn-export {
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

        <!-- MAIN NAVIGATION -->
        <div class="nav-section">
            <div class="nav-section-title">Main</div>
            <a href="dashboard.php" class="<?= ($current_page == 'dashboard.php') ? 'active' : '' ?>">
                <i class="fas fa-home"></i> Dashboard
            </a>
            <?php if ($role === 'admin'): ?>
                <a href="add_user.php" class="<?= ($current_page == 'add_user.php') ? 'active' : '' ?>">
                    <i class="fas fa-user-plus"></i> Employees
                </a>
            <?php endif; ?>
        </div>

        <!-- ATTENDANCE -->
        <div class="nav-section">
            <div class="nav-section-title">Attendance</div>
            <a href="upload_excel_monthly.php"
                class="<?= in_array($current_page, ['employee_attendace.php', 'employee_attendace_monthly.php', 'employee_attendace_semi-monthly.php']) ? 'active' : '' ?>">
                <i class="fas fa-calendar-alt"></i> Upload Attendance
            </a>
            <!-- <a href="employee_attendance.php" class="<?= ($current_page == 'employee_attendance.php') ? 'active' : '' ?>">
                <i class="fas fa-calendar-week"></i> Weekly Attendance
            </a> -->
            <a href="manual_attendance.php" class="<?= ($current_page == 'manual_attendance.php') ? 'active' : '' ?>">
                <i class="fas fa-calendar-week"></i> Manual Attendance
            </a>
            <a href="attendance_summary_report.php"
                class="<?= ($current_page == 'attendance_summary_report.php') ? 'active' : '' ?>">
                <i class="fas fa-clipboard-list"></i> Attendance Summary
            </a>
        </div>

        <!-- PAYROLL -->
        <div class="nav-section">
            <div class="nav-section-title">Payroll</div>
            <a href="payroll.php" class="<?= ($current_page == 'payroll.php') ? 'active' : '' ?>">
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

    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="content-wrapper">
            <div class="container-fluid">
                <!-- Page Header -->
                <div class="report-header mb-4">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <h2 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Manual Attendance</h2>
                            <p class="text-muted mb-0">" "</p>
                        </div>
                        <div class="col-md-6 text-md-end">
                            <a href="add_attendance.php" class="btn btn-success add-btn"><i class="fas fa-plus"></i> Add
                                Attendance</a>
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
                                <input type="date" class="form-control" id="start_date" name="start_date"
                                    value="<?php echo $filterStartDate; ?>">
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <label for="end_date" class="form-label">End Date</label>
                                <input type="date" class="form-control" id="end_date" name="end_date"
                                    value="<?php echo $filterEndDate; ?>">
                            </div>
                            <div class="col-lg-2 col-md-6 d-flex align-items-end">
                                <button type="submit" name="filter" class="btn btn-primary w-100">
                                    <i class="fas fa-filter me-1"></i> Apply Filter
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Attendance Table with Sticky Headers -->
                <?php if (!empty($employeeData)): ?>
                    <div class="table-container">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped sticky-table">
                                <tbody>
                                    <?php
                                    // Flatten and group by date
                                    $grouped = [];
                                    $employeeData = array_filter($employeeData, function ($employee) use ($payPeriods) {
                                        $id = $employee['id_no'] ?? null;
                                        return isset($payPeriods[$id]) && $payPeriods[$id] === 'fixed';
                                    });


                                    foreach ($employeeData as $employee) {
                                        foreach ($employee['dates'] as $i => $date) {
                                            $grouped[$date][] = [
                                                'id_no' => $employee['id_no'],
                                                'department' => $employee['department'],
                                                'name' => $employee['name'],
                                                'date' => $date,
                                                'am_in' => $employee['am_in'][$i] ?? '',
                                                'am_out' => $employee['am_out'][$i] ?? ''
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
                                            <td colspan='8'
                                                class='fw-bold bg-light text-center fs-5 sticky-date-header date-heading'>
                                                <i class='fas fa-calendar-day'></i> Attendance for: <?= $formattedDate ?>
                                            </td>
                                        </tr>

                                        <!-- Table Headers (sticky) -->
                                        <tr class='table-dark table-header'>
                                            <th>ID No.</th>
                                            <th>Department</th>
                                            <th>Name</th>
                                            <th>Date</th>
                                            <th>Absences</th>
                                            <td>
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

            <!-- Submit Payroll Confirmation Modal -->
            <div class="modal fade" id="submitModal" tabindex="-1" aria-labelledby="submitModalLabel"
                aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="submitModalLabel">
                                <i class="fas fa-exclamation-triangle text-warning me-2"></i>Confirm Submit Payroll
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p>Are you sure you want to submit the payroll? This action cannot be undone and will
                                process the attendance records for the selected employee.</p>

                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                <i class="fas fa-times me-1"></i>Cancel
                            </button>
                            <button type="button" class="btn btn-primary" id="confirmSubmit">
                                <i class="fas fa-check me-1"></i>Submit Payroll
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- JavaScript for functionality -->
            <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
            <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
            <script>

                // Form validation
                document.addEventListener('DOMContentLoaded', function () {
                    const form = document.getElementById('addEmployeeForm');
                    form.addEventListener('submit', function (event) {
                        let isValid = true;
                        const idNo = document.getElementById('id_no').value.trim();
                        const name = document.getElementById('name').value.trim();
                        const department = document.getElementById('department').value.trim();
                        const dailyRate = parseFloat(document.getElementById('daily_rate').value);

                        if (!idNo || !name || !department || isNaN(dailyRate) || dailyRate <= 0) {
                            isValid = false;
                        }

                        if (!isValid) {
                            event.preventDefault();
                            alert('Please fill in all fields correctly.');
                        }
                    });
                });

                // Initialize date range if empty
                document.addEventListener('DOMContentLoaded', function () {
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
                        menuToggle.addEventListener('click', function () {
                            sidebar.classList.toggle('active');
                            if (sidebar.classList.contains('active')) {
                                overlay.style.display = 'block';
                            } else {
                                overlay.style.display = 'none';
                            }
                        });

                        overlay.addEventListener('click', function () {
                            sidebar.classList.remove('active');
                            overlay.style.display = 'none';
                        });

                        // Close sidebar on window resize if in mobile view
                        window.addEventListener('resize', function () {
                            if (window.innerWidth > 768) {
                                sidebar.classList.remove('active');
                                overlay.style.display = 'none';
                            }
                        });

                        // Handle sidebar links in mobile view
                        const sidebarLinks = document.querySelectorAll('.sidebar a');
                        sidebarLinks.forEach(link => {
                            link.addEventListener('click', function () {
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

                // Optional JavaScript to handle the submit action (e.g., show success message or integrate with backend)
                document.getElementById('confirmSubmit').addEventListener('click', function () {
                    // Example: Show a success alert (replace with actual submission logic)
                    alert('Payroll submitted successfully!');

                    // Close the modal
                    const modal = bootstrap.Modal.getInstance(document.getElementById('submitModal'));
                    modal.hide();

                    // Optional: Disable the button after submission to prevent double-clicks
                    // this.disabled = true;
                    // this.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Submitted';
                });

                // Optional: Handle multiple modals or dynamic employee names (if needed)
                // You can pass employee data via data attributes on the trigger buttons
                document.querySelectorAll('[data-bs-toggle="modal"]').forEach(button => {
                    button.addEventListener('click', function () {
                        const employeeName = this.getAttribute('aria-label') || 'the employee';
                        const modalBody = document.querySelector('#submitModal .modal-body p');
                        if (modalBody) {
                            modalBody.innerHTML = `Are you sure you want to submit the payroll for <strong>${employeeName}</strong>? This action cannot be undone and will process the attendance records.`;
                        }
                    });
                });
            </script>
</body>

</html>