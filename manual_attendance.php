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

if ($message === 'success') {
    $messageText = 'Attendance record saved successfully!';
    $messageClass = 'success';
} elseif ($message === 'error') {
    $messageText = 'Failed to save attendance record. Please try again.';
    $messageClass = 'danger';
}

// Fetch attendance records
$sql = "SELECT id, id_no, department, name, pay_schedule, work_days_count, ot_hours, ut_hours, created_at 
        FROM manual_attendance ORDER BY created_at DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Manual Attendance Records</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<style>
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

    .container {
        margin-left: 270px;
        padding: 30px;
        width: calc(100% - 270px);
        transition: all 0.3s;
        box-sizing: border-box;
        min-height: 100vh;
        background-color: #d6eaf8;
    }
</style>

<body>

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

    

    <!-- Main Content -->
    <!-- Confirmation Modal -->
    <div class="modal fade" id="confirmModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Submission</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to save this row?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="confirmSaveBtn">Yes, Save</button>
                </div>
            </div>
        </div>
    </div>


    <div class="container mt-5">
        <div class="card shadow">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="mb-0">Manual Attendance Records</h3>
                <a href="add_attendance.php" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus"></i> Add Attendance
                </a>
            </div>

            <?php if ($messageText): ?>
                <div class="alert alert-<?= $messageClass ?> m-3">
                    <?= htmlspecialchars($messageText) ?>
                </div>
            <?php endif; ?>
            <?php if (isset($_GET['msg'])): ?>
                <?php if ($_GET['msg'] === 'success'): ?>
                    <div class="alert alert-success">
                        ✅ Data saved successfully!
                    </div>
                <?php elseif ($_GET['msg'] === 'error'): ?>
                    <div class="alert alert-danger">
                        ⚠️ Error: Please fill in all required fields.
                    </div>
                <?php elseif ($_GET['msg'] === 'duplicate'): ?>
                    <div class="alert alert-warning">
                        ⚠️ Duplicate Entry: This ID Number already exists.
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
                            <th>Work Days</th>
                            <th>OT Hours</th>
                            <th>UT Hours</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <!-- <td><?= $row['id'] ?></td> -->
                                    <td><?= htmlspecialchars($row['id_no']) ?></td>
                                    <td><?= htmlspecialchars($row['name']) ?></td>
                                    <td><?= htmlspecialchars($row['department']) ?></td>
                                    <td><?= htmlspecialchars($row['pay_schedule']) ?></td>
                                    <td><?= (int)$row['work_days_count'] ?></td>
                                    <td><?= (int)$row['ot_hours'] ?></td>
                                    <td><?= (int)$row['ut_hours'] ?></td>
                                    <!-- <td><?= $row['created_at'] ?></td> -->
                                    <td>
                                        <button id="edit-<?= $row['id'] ?>" onclick="enableEdit(<?= $row['id'] ?>)">Edit</button>
                                        <button id="save-<?= $row['id'] ?>" onclick="openConfirmModal(<?= $row['id'] ?>)">Submit</button>
                                    </td>

                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="text-center text-muted">No attendance records found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
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

                
            </script>
</body>

</html>