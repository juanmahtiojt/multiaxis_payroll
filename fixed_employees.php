<?php 
session_start();
include __DIR__ . "/config.php";  // Include database connection

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['user'];
$role = $_SESSION['role'];

// Set current page to dashboard.php by default
$current_page = 'dashboard.php'; // Make Dashboard active by default

try {
    // Database connection
    $pdo = new PDO("mysql:host=localhost;dbname=multiaxis_payroll_system", 'root', ''); // Change to your actual DB credentials
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Fetch fixed employees data
    $sql = "SELECT id_no, name, department, daily_rate, pay_schedule FROM daily_rate WHERE pay_schedule = 'fixed'";
    $stmt = $pdo->query($sql);

    // Store the data in an array
    $fixedEmployees = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
    exit();
}
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
            background-color: #d6eaf8;
            display: flex;
            height: 100vh;
            overflow: hidden;
        }
        .sidebar {
            width: 250px;
            background-color: #2c3e50;
            color: white;
            height: 100vh;
            padding-top: 20px;
            position: fixed;
            left: 0;
            top: 0;
            transition: 0.3s;
            overflow-y: auto;
        }
        .sidebar a {
            display: block;
            color: white;
            text-decoration: none;
            padding: 15px;
            font-size: 18px;
            transition: 0.3s;
        }
        .sidebar a:hover {
            background-color: #1a252f;
            color: #ffffff !important;
        }
        .sidebar a.active {
            background-color: #d6eaf8;
            color: #000 !important;
        }
        .main-content {
            margin-left: 250px;
            padding: 40px;
            width: calc(100% - 250px);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            height: 100vh;
            transition: 0.3s;
            padding-top: 40px;
            box-sizing: border-box;
            min-height: 100vh;
        }
        .container-box {
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0px 6px 20px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 1400px;
            height: 85vh;
            overflow: auto;
        }
        .user-count-panel {
            color: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0px 4px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
            text-align: center;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .user-count-panel:hover {
            transform: translateY(-5px);
            box-shadow: 0px 8px 25px rgba(0, 0, 0, 0.2);
        }
    </style>
</head>
<body>
    <div class="sidebar" id="sidebar">
        <h1 class="text-center" style="margin: 0; padding: 0; position: relative; top: -50px;">
            <img src="http://localhost/my_project/images/MULTI-removebg-preview.png" style="width: 200px; height: 200px;">
        </h1>
        <p class="text-center" style="margin-top: -90px; color: white; font-size: 23px; font-weight: bold;">
            Multi Axis Handlers & Tech Inc
        </p>
        <a href="dashboard.php" class="<?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>">🏠 Dashboard</a>
        <?php if ($role === 'admin') : ?>
            <a href="add_user.php" class="<?php echo ($current_page == 'add_user.php') ? 'active' : ''; ?>">➕ Employees</a>
        <?php endif; ?>
        <a href="employee_attendance.php" class="<?php echo ($current_page == 'employee_attendance.php') ? 'active' : ''; ?>">📦 Attendance</a>
        <a href="reports.php" class="<?php echo ($current_page == 'reports.php') ? 'active' : ''; ?>">📊 Deductions</a>
        <a href="holiday_overtime.php" class="<?php echo ($current_page == 'holiday_overtime.php') ? 'active' : ''; ?>">⏰ Holiday & Overtime</a>
        <a href="sss_pagibig_philhealth.php" class="<?php echo ($current_page == 'sss_pagibig_philhealth.php') ? 'active' : ''; ?>">💼 SSS, PagIBIG, PhilHealth</a>
        <a href="view_payslips.php" class="<?= ($current_page == 'view_payslips.php') ? 'active' : '' ?>">💸 View Payslips</a>
        <a href="logout.php" class="<?php echo ($current_page == 'logout.php') ? 'active' : ''; ?>">🚪 Logout</a>
    </div>

    <div class="main-content">
        <div class="container-box">
            <h2>Fixed Employees</h2>
            <?php if (count($fixedEmployees) > 0): ?>
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>ID No</th>
                            <th>Name</th>
                            <th>Department</th>
                            <th>Daily Rate</th>
                            <th>15th</th>
                            <th>Pay Schedule</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
    <?php foreach ($fixedEmployees as $employee): ?>
        <tr>
            <td><?= htmlspecialchars($employee['id_no']) ?></td>
            <td><?= htmlspecialchars($employee['name']) ?></td>
            <td><?= htmlspecialchars($employee['department']) ?></td>
            <td><?= htmlspecialchars(number_format($employee['daily_rate'], 2)) ?></td>
            <td><?= htmlspecialchars(number_format($employee['daily_rate'] * 15, 2)) ?></td>
            <td><?= htmlspecialchars($employee['pay_schedule']) ?></td>
            <td>
    <a href="enter_payroll.php?id_no=<?= $employee['id_no'] ?>" class="btn btn-primary btn-sm">
        Enter Payroll
    </a>
</td>

        </tr>
    <?php endforeach; ?>
</tbody>

                </table>
            <?php else: ?>
                <p>No fixed employees found.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
