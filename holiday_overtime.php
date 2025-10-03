<?php
session_start();
include __DIR__ . "/config.php";

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['user'];
$role = $_SESSION['role'];

// Fetch all employee deduction data once
$deductions_query = "SELECT * FROM employee_deductions";
$deductions_result = mysqli_query($conn, $deductions_query);
$deductions = [];
while ($row = mysqli_fetch_assoc($deductions_result)) {
    $deductions[] = $row;
}

$current_page = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Holiday and Overtime - Multi Axis Handlers & Tech Inc</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <style>
        body {
            background-color: #d6eaf8;
            display: flex;
            min-height: 100vh;
            margin: 0;
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
            position: absolute;
            bottom: 0;
            width: 100%;
        }

        .main-content {
            margin-left: 270px;
            padding: 40px;
            width: calc(100% - 270px);
            box-sizing: border-box;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            transition: all 0.3s;
        }

        .table-container {
            width: 100%;
            max-width: 1400px;
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 6px 15px rgba(0,0,0,0.1);
        }

        .table-container h2 {
            font-size: 28px;
            margin-bottom: 30px;
            font-weight: 600;
            color: #2c3e50;
            text-align: center;
        }

        .date-filters .form-control {
            box-shadow: none;
            border-radius: 8px;
        }

        .table-container table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .table-container th {
            background-color: #2c3e50;
            color: #fff;
            font-weight: 500;
            padding: 12px;
            border: 1px solid #dee2e6;
        }

        .table-container td {
            padding: 10px;
            border: 1px solid #dee2e6;
            text-align: center;
        }

        .table-container tbody tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .table-container thead th {
            position: sticky;
            top: 0;
            background-color: #2c3e50;
            z-index: 10;
            border: 3px solid #dee2e6;
        }

        .btn-custom {
            background-color: #3498db;
            color: white;
            border: none;
            padding: 10px 18px;
            border-radius: 8px;
            margin-top: 25px;
            margin-right: 10px;
            transition: background-color 0.3s;
        }

        .btn-custom:hover {
            background-color: #2980b9;
        }

        .d-flex {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 20px;
        }

        @media print {
            .sidebar, .btn-custom, .date-filters {
                display: none !important;
            }
            .main-content {
                margin-left: 0;
                padding: 10px;
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
    <div class="table-container">
        <h2><i class="fas fa-clock"></i> Holiday and Overtime</h2>

        <!-- Date filter -->
        <div class="row date-filters mb-4">
            <div class="col-md-6 mb-3">
                <label class="form-label fw-semibold">Start Date</label>
                <input type="date" id="startDate" class="form-control">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label fw-semibold">End Date</label>
                <input type="date" id="endDate" class="form-control">
            </div>
        </div>

        <!-- Table -->
        <form onsubmit="event.preventDefault();">
            <table id="overtimeTable" class="table table-bordered table-striped" style="display: none;">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Start</th>
                        <th>End</th>
                        <th>Subtotal</th>
                        <th>Reg OT</th>
                        <th>Rest Day</th>
                        <th>RD OT</th>
                        <th>Reg Holi</th>
                        <th>Reg Holi OT</th>
                        <th>Rest + Reg</th>
                        <th>Spec Holi</th>
                        <th>Spec Holi OT</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($deductions as $deduction): ?>
                        <?php
                        $id = $deduction['employee_id'];
                        $subtotal = $deduction['subtotal_minus_deductions'];
                        ?>
                        <tr data-start="<?= $deduction['start_date']; ?>" data-end="<?= $deduction['end_date']; ?>">
                            <td><?= $id ?></td>
                            <td><?= $deduction['employee_name'] ?></td>
                            <td><?= $deduction['start_date'] ?></td>
                            <td><?= $deduction['end_date'] ?></td>
                            <td class="subtotal"><?= number_format($subtotal, 2) ?></td>

                            <?php
                            $fields = ['regular_overtime', 'rest_day', 'rest_day_overtime', 'regular_holiday', 'regular_holiday_overtime', 'work_on_rest_regular_holiday', 'special_non_working_holiday', 'special_holiday_overtime'];
                            $rates = [125, 1040, 169, 1600, 260, 2080, 1040, 169];
                            foreach ($fields as $index => $field): ?>
                                <td><input type="number" class="form-control ot-input" data-rate="<?= $rates[$index] ?>" value="0" min="0" step="any"></td>
                            <?php endforeach; ?>
                            <td class="total-pay"><?= number_format($subtotal, 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <div class="d-flex" style="justify-content: center; gap: 20px; margin-top: 25px;">
                <button type="button" class="btn-custom" onclick="calculateAll()">
                    <i class="fas fa-calculator"></i> Update Total Pay
                </button>
                <button type="button" class="btn-custom" onclick="saveData()">
                    <i class="fas fa-save"></i> Save to Database
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    const table = document.getElementById("overtimeTable");
    const startInput = document.getElementById("startDate");
    const endInput = document.getElementById("endDate");

    function isInRange(start, end, rowStart, rowEnd) {
        const startDate = new Date(start);
        const endDate = new Date(end);
        const rowStartDate = new Date(rowStart);
        const rowEndDate = new Date(rowEnd);
        return (
            (!start || rowEndDate >= startDate) && 
            (!end || rowStartDate <= endDate)
        );
    }

    function filterTable() {
        const startDate = startInput.value;
        const endDate = endInput.value;
        let showTable = false;
        [...table.querySelectorAll("tbody tr")].forEach(row => {
            const rowStart = row.dataset.start;
            const rowEnd = row.dataset.end;
            if (isInRange(startDate, endDate, rowStart, rowEnd)) {
                row.style.display = "";
                showTable = true;
            } else {
                row.style.display = "none";
            }
        });
        table.style.display = showTable ? "table" : "none";
    }

    startInput.addEventListener("change", filterTable);
    endInput.addEventListener("change", filterTable);

    function calculateAll() {
        [...document.querySelectorAll("#overtimeTable tbody tr")].forEach(row => {
            const subtotal = parseFloat(row.querySelector(".subtotal").textContent.replace(/,/g, '')) || 0;
            let total = subtotal;
            row.querySelectorAll(".ot-input").forEach(input => {
                const value = parseFloat(input.value) || 0;
                const rate = parseFloat(input.dataset.rate);
                total += value * rate;
            });
            row.querySelector(".total-pay").textContent = total.toFixed(2);
        });
    }

    function saveData() {
        const startDate = startInput.value;
        const endDate = endInput.value;

        if (!startDate || !endDate) {
            alert("Please select both Start Date and End Date before saving.");
            return;
        }

        // Ask for confirmation only if dates are filled in
        if (!confirm("Are you sure you want to save?")) return;

        const dataToSave = [];
        [...document.querySelectorAll("#overtimeTable tbody tr")].forEach(row => {
            if (row.style.display === "none") return; // Only save visible rows

            const employeeId = row.cells[0].textContent;
            const employeeName = row.cells[1].textContent;
            const rowStartDate = row.cells[2].textContent;
            const rowEndDate = row.cells[3].textContent;
            const subtotal = parseFloat(row.querySelector(".subtotal").textContent.replace(/,/g, '')) || 0;

            const overtimeFields = [
                'regular_overtime', 'rest_day', 'rest_day_overtime',
                'regular_holiday', 'regular_holiday_overtime',
                'work_on_rest_regular_holiday', 'special_non_working_holiday', 'special_holiday_overtime'
            ];

            const overtimeData = {};
            overtimeFields.forEach((field, index) => {
                const inputValue = parseFloat(row.querySelector(`td:nth-child(${index + 6}) input`).value) || 0;
                overtimeData[field] = inputValue;
            });

            dataToSave.push({
                employee_id: employeeId,
                employee_name: employeeName,
                start_date: rowStartDate,
                end_date: rowEndDate,
                subtotal: subtotal,
                overtime_data: overtimeData
            });
        });

        // Send data to the server
        fetch('save_overtime_data.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(dataToSave)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Data saved successfully!');
            } else {
                alert('Failed to save data.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error saving data.');
        });
    }
</script>

</body>
</html>