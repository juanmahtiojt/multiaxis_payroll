<?php
// manual_attendance.php
include 'config.php';

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

// Handle optional message (success/error)
$message = $_GET['msg'] ?? '';
$messageText = '';
$messageClass = '';

// if ($message === 'success') {
//     $messageText = 'Attendance record saved successfully!';
//     $messageClass = 'success';
// } elseif ($message === 'error') {
//     $messageText = 'Failed to save attendance record. Please try again.';
//     $messageClass = 'danger';
// }

// Fetch attendance records
$sql = "SELECT id, id_no, department, name, pay_schedule, start_date, end_date,  work_days_count, ot_hours, ut_hours, created_at 
        FROM manual_attendance WHERE archived = 0  ORDER BY created_at DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Manual Attendance Records</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">


</head>
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

    /* .container {
        margin-left: 270px;
        padding: 30px;
        width: calc(100% - 270px);
        transition: all 0.3s;
        box-sizing: border-box;
        min-height: 100vh;
        background-color: #d6eaf8;
    } */

    .report-header {
        background-color: #eaf2f8;
        padding: 15px;
        border-radius: 10px;
        margin-bottom: 20px;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
    }

    .custom-alert {
        position: fixed;
        margin-top: 15px;
        right: 30px;
        transform: translateX(-10%);
        width: 400px;
        height: 80px;
        /* Control size */
        z-index: 1050;
        /* Keeps it on top */
        border-radius: 12px;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
        animation: fadeInDown 0.4s ease;
    }


    @keyframes fadeInDown {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
</style>

<body class="bg-light">

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

        <!-- MAIN NAVIGATION -->
        <div class="nav-section">
            <div class="nav-section-title">Main</div>
            <a href="dashboard.php" class="<?= ($current_page == 'dashboard.php') ? 'active' : '' ?>">
                <i class="fas fa-home"></i> Dashboard
            </a>
            <?php if ($role === 'admin') : ?>
                <a href="add_user.php" class="<?= ($current_page == 'add_user.php') ? 'active' : '' ?>">
                    <i class="fas fa-user-plus"></i> Employees
                </a>
            <?php endif; ?>
        </div>

        <!-- ATTENDANCE -->
        <div class="nav-section">
            <div class="nav-section-title">Attendance</div>
            <a href="upload_excel_monthly.php" class="<?= in_array($current_page, ['employee_attendace.php', 'employee_attendace_monthly.php', 'employee_attendace_semi-monthly.php']) ? 'active' : '' ?>">
                <i class="fas fa-calendar-alt"></i> Upload Attendance
            </a>
            <a href="manual.php" class="<?= ($current_page == 'manual.php') ? 'active' : '' ?>">
                <i class="fas fa-calendar-week"></i> Manual Attendance
            </a>
            <a href="attendance_summary_report.php" class="<?= ($current_page == 'attendance_summary_report.php') ? 'active' : '' ?>">
                <i class="fas fa-clipboard-list"></i> Attendance Summary
            </a>
        </div>

        <!-- PAYROLL -->
        <div class="nav-section">
            <div class="nav-section-title">Payroll</div>
            <a href="payroll.php"
                class="<?= in_array($current_page, ['payroll.php', 'enter_payroll.php', 'weekly_employees.php', 'semi-monthly_employees.php', 'enter_weekly_payroll.php', 'enter_payroll.php', 'enter_semimonthly_payroll.php']) ? 'active' : '' ?>">
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
    <!-- Confirmation Modal -->
    <div class="modal fade" id="confirmModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">Confirm Submission</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to submit this row in payroll?
                    <br>This row will be archive.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success" id="confirmSubmitBtn">Confirm</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-labelledby="confirmDeleteLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="confirmDeleteLabel">Confirm Deletion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to permanently delete this record?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" id="confirmDeleteBtn" class="btn btn-danger">Delete</button>
                </div>
            </div>
        </div>
    </div>



    <dive class="main-content">
        <div class="container mt-5">
            <div class="card shadow">
                <!-- Page Header -->
                <div class="report-header mb-4">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <h2 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Manual Attendance</h2>
                            <p class="text-muted mb-0"></p>
                        </div>
                        <div class="col-md-6 text-md-end">
                            <a href="add_attendance.php" class="btn btn-success add-btn"><i class="fas fa-plus"></i> Add
                                Attendance</a>
                        </div>
                    </div>
                </div>

                <!-- <?php if ($messageText): ?>
                    <div class="alert alert-<?= $messageClass ?> m-3">
                        <?= htmlspecialchars($messageText) ?>
                    </div>
                <?php endif; ?> -->
                <?php if (isset($_GET['msg'])): ?>
                    <?php
                    $msgType = $_GET['msg'];
                    $alerts = [
                        'success'      => ['class' => 'success', 'icon' => 'bi-check-circle-fill', 'text' => 'Data saved successfully!'],
                        'error'        => ['class' => 'danger',  'icon' => 'bi-exclamation-triangle-fill', 'text' => 'Error: Please fill in all required fields.'],
                        'duplicate'    => ['class' => 'warning', 'icon' => 'bi-exclamation-circle-fill', 'text' => 'Duplicate Entry: This ID Number already exists.'],
                        'updated'      => ['class' => 'info',    'icon' => 'bi-pencil-square', 'text' => 'Attendance successfully updated.'],
                        'deleted'      => ['class' => 'danger',  'icon' => 'bi-trash-fill', 'text' => 'Record permanently deleted.'],
                        'delete_error' => ['class' => 'warning', 'icon' => 'bi-x-circle-fill', 'text' => 'Failed to delete record. Please try again.'],
                    ];

                    if (array_key_exists($msgType, $alerts)):
                        $a = $alerts[$msgType];
                    ?>
                        <div class="custom-alert alert alert-<?php echo $a['class']; ?> shadow-sm fade show d-flex align-items-center justify-content-between" role="alert">
                            <div>
                                <i class="bi <?php echo $a['icon']; ?> me-2 fs-5"></i>
                                <strong><?php echo $a['text']; ?></strong>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>



                <div class="table-responsive p-3">
                    <table class="table table-bordered table-striped">
                        <thead class="table-light">
                            <tr>
                                <th>ID No</th>
                                <th>Name</th>
                                <th>Department</th>
                                <th>Pay Schedule</th>
                                <th>Start Date</th>
                                <th>End Date</th>
                                <th>Work Days</th>
                                <th>OT Hours</th>
                                <th>UT Hours</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result && $result->num_rows > 0): ?>
                                <?php while ($row = $result->fetch_assoc()): ?>
                                    <!-- <form id="form-<?= $row['id'] ?>" method="POST" action="submit_manual_attendance.php"> -->
                                    <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                    <tr>
                                        <td><?= htmlspecialchars($row['id_no']) ?></td>
                                        <td><?= htmlspecialchars($row['name']) ?></td>
                                        <td><?= htmlspecialchars($row['department']) ?></td>
                                        <td><?= htmlspecialchars($row['pay_schedule']) ?></td>
                                        <td><?= $row['start_date'] ?></td>
                                        <td><?= $row['end_date'] ?></td>
                                        <td><?= (int) $row['work_days_count'] ?></td>
                                        <td><?= (int) $row['ot_hours'] ?></td>
                                        <td><?= (int) $row['ut_hours'] ?></td>
                                        <td>
                                            <!-- Edit -->
                                            <a href="edit_manual.php?id=<?= $row['id'] ?>" class="btn btn-primary btn-sm">
                                                <i class="fas fa-edit"></i> Edit
                                            </a>

                                            <!-- Submit -->
                                            <form id="form-<?= $row['id'] ?>" method="POST" action="submit_manual_attendance.php" style="display:inline;">
                                                <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                                <button type="button" id="save-<?= $row['id'] ?>" class="btn btn-success btn-sm">
                                                    <i class="fas fa-save"></i> Submit
                                                </button>
                                            </form>

                                            <!-- Delete -->
                                            <button type="button"
                                                class="btn btn-danger btn-sm delete-btn"
                                                data-id="<?= $row['id'] ?>">
                                                <i class="fas fa-trash"></i> Remove
                                            </button>

                                        </td>
                                    </tr>
                                    <!-- </form> -->
                                <?php endwhile; ?>

                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        </div>

        <!-- FontAwesome (for icons) -->
        <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>

        <!-- Bootstrap JS Bundle (Required for Modals, Dropdowns, etc.) -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
        <script>
            setTimeout(() => {
                const alertEl = document.querySelector('.custom-alert');
                if (alertEl) {
                    alertEl.classList.remove('show');
                    setTimeout(() => alertEl.remove(), 400);
                }
            }, 4000);
        </script>


        <script>
            let currentForm = null;

            document.querySelectorAll('button[id^="save-"]').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const rowId = this.id.replace('save-', '');
                    currentForm = document.getElementById('form-' + rowId);
                    const modal = new bootstrap.Modal(document.getElementById('confirmModal'));
                    modal.show();
                });
            });

            document.getElementById('confirmSubmitBtn').addEventListener('click', function() {
                if (currentForm) {
                    currentForm.submit(); // Submit the selected row
                }
            });

            document.addEventListener("DOMContentLoaded", () => {
                const alertBox = document.querySelector(".alert");
                if (alertBox) {
                    setTimeout(() => {
                        alertBox.style.display = "none";
                    }, 4000); // hide after 4 seconds
                }
            });

            let rowToSave = null; // store which row user wants to save

            function openConfirmModal(id) {
                document.getElementById("confirm-id").value = id;
                var modal = new bootstrap.Modal(document.getElementById("confirmModal"));
                modal.show();
            }


            document.getElementById("confirmSaveBtn").addEventListener("click", function() {
                if (rowToSave !== null) {
                    saveRow(rowToSave); // ✅ call your save function
                    rowToSave = null;
                    bootstrap.Modal.getInstance(document.getElementById('confirmModal')).hide();
                }
            });
        </script>
        <script>
            document.addEventListener("DOMContentLoaded", () => {
                let deleteId = null;
                const modalEl = document.getElementById('confirmDeleteModal');
                const modal = new bootstrap.Modal(modalEl);

                // When user clicks Remove
                document.querySelectorAll('.delete-btn').forEach(btn => {
                    btn.addEventListener('click', () => {
                        deleteId = btn.getAttribute('data-id');
                        modal.show();
                    });
                });

                // When user confirms deletion
                document.getElementById('confirmDeleteBtn').addEventListener('click', () => {
                    if (deleteId) {
                        // Send POST request to PHP
                        fetch('delete_manual_attendance.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded'
                                },
                                body: `id=${deleteId}`
                            })
                            .then(res => res.json())
                            .then(data => {
                                modal.hide();
                                if (data.status === 'success') {
                                    window.location.href = 'manual.php?msg=deleted';
                                } else {
                                    alert('Failed to delete record.');
                                }
                            });

                    }
                });
            });
        </script>



</body>

</html>