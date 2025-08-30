<?php
// enter_payroll.php
$host = "localhost";
$username = "root";
$password = "cvsuOJT@2025"; // Change this if you have a password
$dbname = "multiaxis_payroll_system";

$conn = new mysqli($host, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get employee info if ID is passed
$employee = null;
$employee_details = null;
if (isset($_GET['id_no'])) {
    $id_no = $_GET['id_no'];
    
    // Get rate info from daily_rate table
    $stmt = $conn->prepare("SELECT * FROM daily_rate WHERE id_no = ? AND pay_schedule = 'semi-monthly'");
    $stmt->bind_param("s", $id_no);
    $stmt->execute();
    $employee = $stmt->get_result()->fetch_assoc();
    
    // Get employee details from employees table
    $stmt = $conn->prepare("SELECT * FROM employees WHERE id_no = ?");
    $stmt->bind_param("s", $id_no);
    $stmt->execute();
    $employee_details = $stmt->get_result()->fetch_assoc();
}

$current_page = basename($_SERVER['PHP_SELF']);
$role = 'admin'; // Hardcoded for demo, adjust to use session logic in production

// Handle new weekly employee form
$success = "";
$error = "";
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_weekly_employee'])) {
    $id_no = $_POST["id_no"];
    $name = $_POST["name"];
    $department = $_POST["department"];
    $daily_rate = $_POST["daily_rate"];

    if (empty($id_no) || empty($name) || empty($department) || empty($daily_rate)) {
        $error = "All fields are required.";
    } else {
        $stmt = $conn->prepare("INSERT INTO daily_rate (id_no, name, department, daily_rate, pay_schedule) VALUES (?, ?, ?, ?, 'weekly')");
        $stmt->bind_param("sssd", $id_no, $name, $department, $daily_rate);

        if ($stmt->execute()) {
            $success = "Weekly employee added successfully!";
        } else {
            $error = "Error: " . $stmt->error;
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enter Payroll - Multi Axis Handlers & Tech Inc</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
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
            position: absolute;
            bottom: 0;
            width: 100%;
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

        th, td {
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
            
            table th, table td {
                padding: 8px 5px;
                font-size: 0.9em;
            }
            
            .submit-btn {
                width: 100%;
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
                padding: 10px;
            }
            
            .container-box {
                box-shadow: none;
                height: auto;
                padding: 0;
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
    
            <a href="enter_semimonthly_payroll.php" class="<?php echo ($current_page == 'enter_semimonthly_payroll.php') ? 'active' : ''; ?>">
                <i class="fas fa-dollar-sign"></i> Payroll
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
            <a href="login.php" class="<?php echo ($current_page == 'login.php') ? 'active' : ''; ?>">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
        
        <div class="sidebar-footer">
            © <?php echo date('Y'); ?> Multi Axis Handlers & Tech Inc.
        </div>
    </div>

    <div class="main-content">
        <div class="container-box">
            <h2><i class="fas fa-money-bill-wave"></i> Payroll Semi-monthly Employees</h2>
            
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr class="bg-primary text-white">
                            <th>ID No.</th>
                            <th>Name</th>
                            <th>Department</th>
                            <th>Pay Schedule</th>
                            <th>Semi-Monthly Rate</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    $id_no = $_GET['id_no'] ?? null;

                    if ($id_no) {
                        // Use prepared statements to prevent SQL injection
                        $stmt = $conn->prepare("SELECT * FROM daily_rate WHERE pay_schedule = 'semi-monthly' AND id_no = ?");
                        $stmt->bind_param("s", $id_no);
                        $stmt->execute();
                        $result = $stmt->get_result();

                        while ($row = $result->fetch_assoc()) {
                            $semi_monthly_rate = $row['daily_rate'] * 15;
                            echo "<tr>
                                    <td>{$row['id_no']}</td>
                                    <td>{$row['name']}</td>
                                    <td>{$row['department']}</td>
                                    <td>" . ucfirst($row['pay_schedule']) . "</td>
                                    <td>" . number_format($semi_monthly_rate, 2) . "</td>
                                   
                                </tr>";
                        }
                        $stmt->close();
                    } else {
                        echo "<tr><td colspan='6'>No employee selected.</td></tr>";
                    }
                    ?>

                    </tbody>
                </table>
            </div>

            <?php if ($employee): ?>
                <h3 class="mt-4 mb-3"><i class="fas fa-user-edit"></i> Payroll Details for <?= $employee['name'] ?></h3>
                <form action="submit_payroll.php" method="post">
                    <div class="form-section">
                        <div class="form-group">
                            <label>ID No.</label>
                            <input type="text" name="id_no" value="<?= $employee['id_no'] ?>" readonly class="prefilled form-control">
                        </div>
                        <div class="form-group">
                            <label>Name</label>
                            <input type="text" name="name" value="<?= $employee['name'] ?>" readonly class="prefilled form-control">
                        </div>
                        <div class="form-group">
                            <label>Department</label>
                            <input type="text" name="department" value="<?= $employee['department'] ?>" readonly class="prefilled form-control">
                        </div>
                        
                        <?php
                        $semi_monthly_rate = $employee['daily_rate'] * 15;
                        ?>
                        
                        <div class="form-group">
                            <label>Semi-Monthly Rate</label>
                            <input type="text" name="semi_monthly_rate" value="<?= number_format($semi_monthly_rate, 2) ?>" readonly class="prefilled form-control">
                        </div>
                        <input type="hidden" name="daily_rate" value="<?= $employee['daily_rate'] ?>">

                        <div class="form-group">
                            <label>Payroll Period</label>
                            <input type="text" name="payroll_period" value="15th" class="form-control">
                        </div>

                        <div class="form-group">
                            <label>Start Date</label>
                            <input type="date" name="start_date" required class="form-control">
                        </div>

                        <div class="form-group">
                            <label>End Date</label>
                            <input type="date" name="end_date" required class="form-control">
                        </div>

                        <?php 
                        // Employee specific information fields
                        $employee_fields = [
                            "sss_no" => "SSS No.",
                            "pagibig_no" => "Pag-ibig No.",
                            "tin_no" => "TIN No.",
                            "philhealth_no" => "PhilHealth No."
                        ];

                        foreach ($employee_fields as $field => $label) {
                            $value = $employee_details ? $employee_details[$field] : '';
                            echo "<div class='form-group'>
                                    <label>$label</label>
                                    <input type='text' name='$field' value='$value' readonly class='prefilled form-control'>
                                </div>";
                        }
                        
                        // Basic pay is equal to semi-monthly rate
                        echo "<div class='form-group'>
                                <label>Basic</label>
                                <input type='number' name='basic' step='0.01' value='$semi_monthly_rate' readonly class='prefilled form-control'>
                            </div>";
                                                  // Basic pay is equal to semi-monthly rate
                        echo "<div class='form-group'>
                                <label>Basic</label>
                                <input type='number' name='basic' step='0.01' value='$semi_monthly_rate' readonly class='prefilled form-control'>
                            </div>";
                        echo "
                            <style>
                            /* Chrome, Edge, Safari */
                            input[type=number]::-webkit-inner-spin-button,
                            input[type=number]::-webkit-outer-spin-button {
                                -webkit-appearance: none;
                                margin: 0;
                            }

                            /* Firefox */
                            input[type=number] {
                                -moz-appearance: textfield;
                            }
                            </style>
                            ";
                        
                        // Fixed rates for overtime and Sunday pay
                        // These are hardcoded for demo purposes, adjust as needed
                        $fixed_regular_rate = 125.00;
                        $fixed_sunday_rate = 150.00;

                        
                        // Overtime and other pay fields
                        $pay_fields = [
                            "overtime_pay" => "Overtime Pay",
                            "hours" => "Hours",
                            "rate" => "Rate",
                            "rest_day_pay" => "Rest Day Pay (SUNDAY)",
                            "regular_holiday" => "Regular Holiday",
                            "regular_ot" => "Regular OT",
                            "special_holiday_pay" => "Special Holiday Pay",
                            "thirteenth_month" => "13th Month Pay"
                        ];

                        foreach ($pay_fields as $field => $label) {
                            echo "<div class='form-group'>
                                    <label>$label</label>
                                    <input type='number' name='$field' step='0.01' class='form-control'>
                                </div>";
                        }
                        
                        // Deduction fields - pre-populated from employees table
                        $deduction_fields = [
                            "sss_premium" => "SSS Premium",
                            "sss_loan" => "SSS Loan",
                            "pagibig_premium" => "Pag-ibig Premium",
                            "pagibig_loan" => "Pag-ibig Loan",
                            "philhealth" => "PhilHealth",
                            "cash_advance" => "Cash Advance"
                        ];

                        foreach ($deduction_fields as $field => $label) {
                            $value = $employee_details ? $employee_details[$field] : '0.00';
                            echo "<div class='form-group'>
                                    <label>$label</label>
                                    <input type='number' name='$field' step='0.01' value='$value' readonly class='prefilled form-control'>
                                </div>";
                        }
                        
                        // Time-related deductions
                        $time_fields = [
                            "late" => "Late",
                            "absent" => "Absent",
                            "undertime" => "Undertime"
                        ];

                        foreach ($time_fields as $field => $label) {
                            echo "<div class='form-group'>
                                    <label>$label</label>
                                    <input type='number' name='$field' step='0.01' class='form-control'>
                                </div>";
                        }
                        
                        // Leave fields - pre-populated from employees table
                        $leave_fields = [
                            "leave_with_pay" => "Leave w/ PAY",
                            "leave_without_pay" => "Leave w/o PAY",
                            "available_leave" => "No. of Available Leave Credit"
                        ];

                        foreach ($leave_fields as $field => $label) {
                            $db_field = ($field == "available_leave") ? "available_leave" : $field;
                            $value = $employee_details ? $employee_details[$db_field] : '0';
                            echo "<div class='form-group'>
                                    <label>$label</label>
                                    <input type='number' name='$field' step='0.01' value='$value' readonly class='prefilled form-control'>
                                </div>";
                        }
                        ?>
                    </div>
                    <button type="submit" class="submit-btn btn btn-primary">
                        <i class="fas fa-save"></i> Submit Payroll
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <!-- JavaScript for Mobile Sidebar Toggle -->
    <script>
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