<?php
// PHP processing code at the top - only one session_start()
include_once "functions.php";

session_start();
include __DIR__ . "/config.php";
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}
$message = "";
$messageType = "";

// // Handle form submission
// if ($_SERVER['REQUEST_METHOD'] === 'POST') {
//     $id_no = trim($_POST['id_no']);
//     $name = trim($_POST['name']);
//     $department = trim($_POST['department']);
//     $daily_rate = floatval($_POST['daily_rate']);

//     if ($id_no && $name && $department && $daily_rate > 0) {
//         $stmt = $conn->prepare("INSERT INTO mathipms.daily_rate (id_no, name, department, daily_rate) VALUES (?, ?, ?, ?)");
//         $stmt->bind_param("sssd", $id_no, $name, $department, $daily_rate);

//         if ($stmt->execute()) {
//             $message = "Employee added successfully!";
//             $messageType = "success";

//             // ✅ Add activity log here
//             log_activity($conn, $_SESSION['user'], "Added employee: $name ($id_no)", "admin.php");
//         } else {
//             $message = "Error: " . $stmt->error;
//             $messageType = "danger";
//         }

//         $stmt->close();
//     } else {
//         $message = "Please fill in all fields correctly.";
//         $messageType = "warning";
//     }
// }

if (isset($_GET['id_no'])) {
    header('Content-Type: application/json');
    $id_no = $_GET['id_no'];

    $stmt = $conn->prepare("SELECT name, department, pay_schedule 
                            FROM daily_rate 
                            WHERE id_no = ?");
    $stmt->bind_param("s", $id_no);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        echo json_encode($row);
    } else {
        echo json_encode(null);
    }
    exit; // ⛔ stop here so the HTML form doesn't get printed
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Employee | MultiAxis Payroll System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <style>
        body {
            background-color: #f0f6fc;
            /* Original background color */
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            color: #1e293b;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 650px;
            width: 100%;
        }

        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0px 6px 20px rgba(0, 0, 0, 0.15);
            overflow: hidden;
        }

        .card-header {
            background-color: white;
            border-bottom: 1px solid #e2e8f0;
            padding: 1.5rem;
        }

        .card-title {
            font-weight: 600;
            color: #0f172a;
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
            text-align: center;
        }

        .card-subtitle {
            color: #475569;
            font-size: 0.9rem;
            text-align: center;
            margin-bottom: 0;
        }

        .card-body {
            padding: 1.5rem;
            background-color: white;
        }

        .form-label {
            font-weight: 500;
            margin-bottom: 0.5rem;
            color: #334155;
        }

        .form-control {
            padding: 0.65rem 1rem;
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            transition: all 0.2s;
        }

        .form-control:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 0.25rem rgba(0, 123, 255, 0.15);
        }

        .form-text {
            color: #64748b;
            font-size: 0.875rem;
        }

        .btn-primary {
            background-color: #007bff;
            border: none;
            padding: 0.65rem 1.5rem;
            font-weight: 500;
            border-radius: 0.5rem;
            transition: all 0.2s ease;
        }

        .btn-primary:hover {
            background-color: #0056b3;
        }

        .btn-secondary {
            background-color: #f1f5f9;
            border: 1px solid #e2e8f0;
            color: #475569;
            padding: 0.65rem 1.5rem;
            font-weight: 500;
            border-radius: 0.5rem;
        }

        .btn-secondary:hover {
            background-color: #e2e8f0;
            color: #334155;
        }

        .card-footer {
            background-color: white;
            border-top: 1px solid #e2e8f0;
            padding: 1.25rem 1.5rem;
        }

        .alert {
            border-radius: 0.5rem;
            padding: 1rem;
            border: none;
            margin-bottom: 1.5rem;
        }

        .alert-success {
            background-color: #dcfce7;
            color: #16a34a;
        }

        .alert-danger {
            background-color: #fee2e2;
            color: #dc2626;
        }

        .alert-warning {
            background-color: #ffedd5;
            color: #ea580c;
        }

        .alert-info {
            background-color: #e0f2fe;
            color: #0284c7;
        }

        .input-group-text {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-right: none;
            color: #64748b;
        }

        .has-icon .form-control {
            border-left: none;
            padding-left: 0.5rem;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h1 class="card-title">Add Attendance</h1>
                <p class="card-subtitle">Create an attendance record in the payroll system</p>
            </div>

            <?php if ($message): ?>
                <div class="mx-4 mt-4 mb-0">
                    <div class="alert alert-<?= $messageType ?>">
                        <div class="d-flex align-items-center">
                            <?php if ($messageType === "success"): ?>
                                <i class="fas fa-check-circle me-2"></i>
                            <?php elseif ($messageType === "danger"): ?>
                                <i class="fas fa-exclamation-circle me-2"></i>
                            <?php else: ?>
                                <i class="fas fa-exclamation-triangle me-2"></i>
                            <?php endif; ?>
                            <?= $message ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <div class="card-body">
                <form method="POST" action="" id="addEmployeeForm">

                    <div class="mb-3">
                        <label for="id_no" class="form-label">Employee ID</label>
                        <div class="input-group has-icon">
                            <span class="input-group-text"><i class="fas fa-id-card"></i></span>
                            <input type="text" class="form-control" id="id_no" name="id_no" required
                                placeholder="Enter employee ID">
                        </div>
                        <!-- <div class="form-text">Unique identifier for the employee in the system</div> -->
                    </div>

                    <div class="mb-3">
                        <label for="department" class="form-label">Department</label>
                        <div class="input-group has-icon">
                            <span class="input-group-text"><i class="fas fa-building"></i></span>
                            <input type="text" class="form-control" id="department" name="department" required
                                placeholder="Employee Department" readonly>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="name" class="form-label">Full Name</label>
                        <div class="input-group has-icon">
                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                            <input type="text" class="form-control" id="name" name="name" required
                                placeholder="Employee Name" readonly>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="pay_schedule" class="form-label">Pay Period</label>
                        <div class="input-group has-icon">
                            <span class="input-group-text"><i class="fa-solid fa-receipt"></i></span>
                            <input type="text" class="form-control" id="pay_schedule" name="pay_schedule" required
                                placeholder="Employee Pay Period" readonly>
                        </div>
                    </div>
                    <!-- <input type="text" id="id_no" name="id_no" placeholder="Enter employee ID">
                    <input type="text" id="name" readonly>
                    <input type="text" id="department" readonly>
                    <input type="text" id="pay_schedule" readonly> -->


                    <label for="time_in" class="form-label">Date Range:</label>

                    <div class="mb-3">
                        <label for="time_in" class="form-label">Start Date</label>
                        <div class="input-group has-icon">
                            <span class="input-group-text"><i class="fa-solid fa-calendar"></i></span>
                            <input type="date" class="form-control" id="time_in" name="time_in" value=""
                                placeholder="dd-mm-yyy">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="time_out" class="form-label">End Date</label>
                        <div class="input-group has-icon">
                            <span class="input-group-text"><i class="fa-solid fa-calendar"></i></span>
                            <input type="date" class="form-control" id="time_out" name="time_out" value=""
                                placeholder="dd-mm-yyy">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="date" class="form-label">Date</label>
                        <div class="input-group has-icon">
                            <span class="input-group-text"><i class="fa-solid fa-calendar"></i></span>
                            <input type="text" class="form-control" id="selected-days" name="selected-days"
                                placeholder="Select dates">
                        </div>
                    </div>

                    <div class="table-responsive mt-3">
                        <table class="table table-bordered" id="ot-ut-table">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Overtime (hours)</th>
                                    <th>Undertime (hours)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="3" class="text-center">No dates selected</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                </form>
            </div>

            <div class="card-footer d-flex justify-content-between">
                <a href="manual_attendance.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back
                </a>
                <button type="submit" form="" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Add Attendance
                </button>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!--<script>
        // Form validation
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('');
            form.addEventListener('submit', function(event) {
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
    </script>-->

    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

    <script>
        /** -------- PAY PERIOD OPTIONS -------- */
        function getPeriodOptions(type) {
            let today = new Date();
            let year = today.getFullYear();
            let month = today.getMonth();
            let options = [];

            if (type === "monthly") {
                for (let i = 0; i < 3; i++) {
                    let start = new Date(year, month + i, 1);
                    let end = new Date(year, month + i + 1, 0);
                    options.push({
                        label: start.toLocaleString('default', {
                            month: 'long'
                        }) + " " + year,
                        start: start.toISOString().split("T")[0],
                        end: end.toISOString().split("T")[0]
                    });
                }
            } else if (type === "semi-monthly") {
                for (let i = 0; i < 2; i++) {
                    let m = month + i;
                    let start1 = new Date(year, m, 1);
                    let end1 = new Date(year, m, 15);
                    let start2 = new Date(year, m, 16);
                    let end2 = new Date(year, m + 1, 0);

                    options.push({
                        label: `${start1.toLocaleString('default', { month: 'long' })} 1–15, ${year}`,
                        start: start1.toISOString().split("T")[0],
                        end: end1.toISOString().split("T")[0]
                    });
                    options.push({
                        label: `${start2.toLocaleString('default', { month: 'long' })} 16–${end2.getDate()}, ${year}`,
                        start: start2.toISOString().split("T")[0],
                        end: end2.toISOString().split("T")[0]
                    });
                }
            } else if (type === "weekly") {
                let monday = new Date(today);
                monday.setDate(today.getDate() - (today.getDay() === 0 ? 6 : today.getDay() - 1));
                for (let i = 0; i < 4; i++) {
                    let start = new Date(monday);
                    start.setDate(monday.getDate() + (i * 7));
                    let end = new Date(start);
                    end.setDate(start.getDate() + 6);
                    options.push({
                        label: `${start.toISOString().split("T")[0]} to ${end.toISOString().split("T")[0]}`,
                        start: start.toISOString().split("T")[0],
                        end: end.toISOString().split("T")[0]
                    });
                }
            }

            return options;
        }

        function populatePayPeriods(type) {
            let select = document.getElementById("pay_schedule_select");
            select.innerHTML = '<option value="">Select pay period</option>';
            let periods = getPeriodOptions(type);
            periods.forEach((p, idx) => {
                let opt = document.createElement("option");
                opt.value = idx;
                opt.textContent = p.label;
                opt.dataset.start = p.start;
                opt.dataset.end = p.end;
                select.appendChild(opt);
            });
        }

        /** -------- EMPLOYEE FETCH -------- */
        document.getElementById('id_no').addEventListener('keydown', function (e) {
            if (e.key === "Enter") {
                e.preventDefault(); // ⛔ stop form from submitting immediately
                const empId = this.value.trim();
                console.log("Enter pressed with ID:", empId);

                if (!empId) return;

                fetch('get_employee.php?id_no=' + encodeURIComponent(empId))
                    .then(r => r.text())
                    .then(txt => {
                        console.log("Raw response:", txt); // 👀 debug
                        try {
                            const json = JSON.parse(txt);
                            if (json.success) {
                                document.getElementById('name').value = json.data.name ?? '';
                                document.getElementById('department').value = json.data.department ?? '';
                                document.getElementById('pay_schedule').value = json.data.pay_schedule ?? '';
                            } else {
                                document.getElementById('name').value = '';
                                document.getElementById('department').value = '';
                                document.getElementById('pay_schedule').value = '';
                                alert(json.message || "Employee not found!");
                            }
                        } catch (e) {
                            console.error("Invalid JSON:", e);
                        }
                    })
                    .catch(err => console.error("Fetch error:", err));
            }
        });

        function formatWithSept(date) {
    const day = ("0" + date.getDate()).slice(-2);
    const monthNames = ["Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sept","Oct","Nov","Dec"];
    const month = monthNames[date.getMonth()];
    const year = date.getFullYear().toString().slice(-2);
    return `${day}-${month}-${year}`;
}

/** -------- START DATE -------- */
flatpickr("#time_in", {
    dateFormat: "Y-m-d",  // stored in DB
    altInput: true,       // show friendly text
    onValueUpdate: function(selectedDates, dateStr, instance) {
        if (selectedDates.length > 0) {
            instance.altInput.value = formatWithSept(selectedDates[0]);
        }
    }
});

/** -------- END DATE -------- */
flatpickr("#time_out", {
    dateFormat: "Y-m-d",
    altInput: true,
    onValueUpdate: function(selectedDates, dateStr, instance) {
        if (selectedDates.length > 0) {
            instance.altInput.value = formatWithSept(selectedDates[0]);
        }
    }
});

/** -------- OT/UT MULTIPLE DATES -------- */
flatpickr("#selected-days", {
    mode: "multiple",
    dateFormat: "Y-m-d", // DB saves ISO
    altInput: true,
    onValueUpdate: function(selectedDates, dateStr, instance) {
        let tbody = document.querySelector("#ot-ut-table tbody");
        tbody.innerHTML = "";

        if (selectedDates.length === 0) {
            tbody.innerHTML = '<tr><td colspan="3" class="text-center">No dates selected</td></tr>';
            return;
        }

        selectedDates.forEach(date => {
            const formatted = formatWithSept(date);
            const iso = date.toISOString().split("T")[0]; // 👈 DB value (YYYY-MM-DD)

            let row = `
                <tr>
                    <td>
                        <input type="hidden" name="work_dates[]" value="${iso}">
                        ${formatted}
                    </td>
                    <td><input type="number" step="0.5" class="form-control" name="ot_hours[${iso}]" placeholder="0"></td>
                    <td><input type="number" step="0.5" class="form-control" name="ut_hours[${iso}]" placeholder="0"></td>
                </tr>
            `;
            tbody.insertAdjacentHTML("beforeend", row);
        });

        // also force altInput box to show our custom format
        if (selectedDates.length > 0) {
            instance.altInput.value = selectedDates.map(formatWithSept).join(", ");
        }
    }
});



    </script>


</body>

</html>