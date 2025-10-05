<?php
// edit_manual.php
include "config.php";
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

// Validate query string
if (!isset($_GET['id'])) {
    die("No attendance ID provided.");
}
$attendance_id = intval($_GET['id']);

// Fetch record from DB
$stmt = $conn->prepare("SELECT * FROM manual_attendance WHERE id = ?");
$stmt->bind_param("i", $attendance_id);
$stmt->execute();
$result = $stmt->get_result();
$record = $result->fetch_assoc();

if (!$record) {
    die("Attendance record not found.");
}

// Decode attendance_data safely and get the actual "days" array
$attendanceData = [];
$days = [];
if (!empty($record['attendance_data'])) {
    $attendanceData = json_decode($record['attendance_data'], true) ?? [];
    $days = $attendanceData['days'] ?? [];
}

// usedDates array will be the existing date keys (YYYY-MM-DD)
$usedDatesForJs = array_keys($days);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Edit Attendance</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">


    <script>
        // use json_encode to safely pass PHP values to JS
        const startDate = <?= json_encode($record['start_date']) ?>;
        const endDate = <?= json_encode($record['end_date']) ?>;
        // Already-used dates (array of "YYYY-MM-DD" strings)
        let usedDates = <?= json_encode($usedDatesForJs) ?>;

        function addRow() {
            const tbody = document.getElementById("attendanceTableBody");
            const newRow = document.createElement("tr");

            newRow.innerHTML = `
        <td>
            <input type="date" class="form-control work-date" name="work_dates[]"
                   min="${startDate || ''}" max="${endDate || ''}">
            <input type="hidden" name="isSunday[]" value="0">
            <input type="hidden" name="holiday_id[]" value="">
            <input type="hidden" name="holiday_type[]" value="">
            
            <!-- 🔑 default rate placeholders -->
            <input type="hidden" name="regular_rate[]" value="1">
            <input type="hidden" name="overtime_rate[]" value="1.25">
            <input type="hidden" name="restdayholiday_regular[]" value="0">
            <input type="hidden" name="restdayholiday_overtime[]" value="0">
            <input type="hidden" name="restdayholiday_special[]" value="0">
            <input type="hidden" name="restdayspecialholiday_overtime[]" value="0">
        </td>
        <td><input type="number" step="0.5" class="form-control" name="ot_hours[]" placeholder="0"></td>
        <td><input type="number" step="0.5" class="form-control" name="ut_hours[]" placeholder="0"></td>
    `;

            tbody.appendChild(newRow);

            // Duplicate date validation (same as before)
            const dateInput = newRow.querySelector(".work-date");
            dateInput.dataset.prevValue = "";
            dateInput.addEventListener("change", function() {
                const selected = this.value;
                const prev = this.dataset.prevValue || "";
                if (prev) {
                    const idxPrev = usedDates.indexOf(prev);
                    if (idxPrev !== -1) usedDates.splice(idxPrev, 1);
                }

                if (!selected) {
                    this.dataset.prevValue = "";
                    return;
                }

                if (usedDates.includes(selected)) {
                    alert("This date is already used. Please pick another date.");
                    this.value = "";
                    this.dataset.prevValue = "";
                } else {
                    usedDates.push(selected);
                    this.dataset.prevValue = selected;
                }
            });
        }
    </script>
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
    </style>
</head>

<body>
    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="confirmRowDeleteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">Confirm Deletion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to remove this date entry?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" id="confirmRowDeleteBtn" class="btn btn-danger">Delete</button>
                </div>
            </div>
        </div>
    </div>
    <div class="container mt-4">
        <div class="card">
            <div class="card-header">
                <h1 class="card-title">Edit Attendance</h1>
                <p class="card-subtitle">Edit and update existing attendance</p>
            </div>

            <div class="card-body">
                <form method="POST" action="update_manual_attendance.php" id="editAttendanceForm">
                    <input type="hidden" name="id" value="<?= $attendance_id ?>">

                    <div class="mb-3">
                        <label class="form-label">Employee ID</label>
                        <input type="text" class="form-control" name="id_no"
                            value="<?= htmlspecialchars($record['id_no']) ?>" readonly>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Department</label>
                        <input type="text" class="form-control" name="department"
                            value="<?= htmlspecialchars($record['department']) ?>" readonly>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Full Name</label>
                        <input type="text" class="form-control" name="name"
                            value="<?= htmlspecialchars($record['name']) ?>" readonly>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Pay Schedule</label>
                        <input type="text" class="form-control" name="pay_schedule"
                            value="<?= htmlspecialchars($record['pay_schedule']) ?>" readonly>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Start Date</label>
                        <input type="date" class="form-control" name="start_date" value="<?= htmlspecialchars($record['start_date']) ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">End Date</label>
                        <input type="date" class="form-control" name="end_date" value="<?= htmlspecialchars($record['end_date']) ?>">
                    </div>

                    <div class="table-responsive mt-3">
                        <table class="table table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Overtime (hours)</th>
                                    <th>Undertime (hours)</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <?php
                            foreach ($days as $d => &$data) {
                                // Detect Sunday
                                $data['is_sunday'] = (date('w', strtotime($d)) == 0) ? 1 : 0;

                                // Default empty holiday values
                                $data['holiday_id'] = '';
                                $data['holiday_type'] = '';

                                // Query holiday table (if exists)
                                $stmt = $conn->prepare("SELECT id, holiday_type FROM holidays WHERE holiday_date = ?");
                                $stmt->bind_param("s", $d);
                                $stmt->execute();
                                $result = $stmt->get_result();
                                if ($row = $result->fetch_assoc()) {
                                    $data['holiday_id'] = $row['id'];
                                    $data['holiday_type'] = $row['holiday_type'];
                                }
                            }
                            unset($data);

                            ?>
                            <tbody id="attendanceTableBody">
                                <?php if (!empty($days)): ?>
                                    <?php foreach ($days as $d => $data):

                                        $ot = $data['ot'] ?? '';
                                        $ut = $data['ut'] ?? '';
                                        $isSunday = $data['is_sunday'] ?? 0;
                                        $holiday = $data['holiday_id'] ?? '';
                                        $holidayType = $data['holiday_type'] ?? '';
                                        $rates = $data['rates'] ?? [];

                                        $badge = '';
                                        if ($isSunday) {
                                            $badge .= '<span class="badge bg-warning text-dark ms-2">Sunday</span>';
                                        }
                                        if (!empty($holiday)) {
                                            $badge .= '<span class="badge bg-danger ms-2">Holiday (' . htmlspecialchars($holidayType) . ')</span>';
                                        }
                                    ?>
                                        <tr>
                                            <td>
                                                <?= htmlspecialchars($d) ?> <?= $badge ?>
                                                <input type="hidden" name="work_dates[]" value="<?= htmlspecialchars($d) ?>">
                                                <input type="hidden" name="isSunday[<?= htmlspecialchars($d) ?>]" value="<?= $isSunday ?>">
                                                <input type="hidden" name="holiday_id[<?= htmlspecialchars($d) ?>]" value="<?= htmlspecialchars($holiday) ?>">
                                                <input type="hidden" name="holiday_type[<?= htmlspecialchars($d) ?>]" value="<?= htmlspecialchars($holidayType) ?>">

                                                <!-- 🔑 store rates for backend -->
                                                <input type="hidden" name="regular_rate[<?= htmlspecialchars($d) ?>]" value="<?= $rates['regular_rate'] ?? 1 ?>">
                                                <input type="hidden" name="overtime_rate[<?= htmlspecialchars($d) ?>]" value="<?= $rates['overtime_rate'] ?? 1.25 ?>">
                                                <input type="hidden" name="restdayholiday_regular[<?= htmlspecialchars($d) ?>]" value="<?= $rates['restdayholiday_regular'] ?? 0 ?>">
                                                <input type="hidden" name="restdayholiday_overtime[<?= htmlspecialchars($d) ?>]" value="<?= $rates['restdayholiday_overtime'] ?? 0 ?>">
                                                <input type="hidden" name="restdayholiday_special[<?= htmlspecialchars($d) ?>]" value="<?= $rates['restdayholiday_special'] ?? 0 ?>">
                                                <input type="hidden" name="restdayspecialholiday_overtime[<?= htmlspecialchars($d) ?>]" value="<?= $rates['restdayspecialholiday_overtime'] ?? 0 ?>">
                                            </td>
                                            <td><input type="number" step="0.5" class="form-control" name="ot_hours[<?= htmlspecialchars($d) ?>]" value="<?= htmlspecialchars($ot) ?>"></td>
                                            <td><input type="number" step="0.5" class="form-control" name="ut_hours[<?= htmlspecialchars($d) ?>]" value="<?= htmlspecialchars($ut) ?>"></td>
                                            <td class="text-center">
                                                <button type="button" class="btn btn-danger btn-sm remove-row-btn">
                                                    <i class="fas fa-trash"></i> Remove
                                                </button>
                                            </td>
                                        </tr>
                                        

                                    <?php endforeach; ?>


                                <?php else: ?>
                                    <tr>
                                        <td colspan="3" class="text-center text-muted">No attendance days found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                        <button type="button" class="btn btn-success" onclick="addRow()">
                            <i class="fas fa-plus"></i> Add Date
                        </button>
                    </div>
                </form>
            </div>

            <div class="card-footer d-flex justify-content-between">
                <a href="manual.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back
                </a>
                <button type="submit" form="editAttendanceForm" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i>Save Changes
                </button>
            </div>
        </div>
    </div>

    <!-- FontAwesome (for icons) -->
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>

    <!-- Bootstrap JS Bundle (Required for Modals, Dropdowns, etc.) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            let rowToDelete = null;
            const modalEl = document.getElementById('confirmRowDeleteModal');
            const modal = new bootstrap.Modal(modalEl);

            // When Remove button is clicked
            document.querySelectorAll('.remove-row-btn').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    rowToDelete = e.target.closest('tr');
                    modal.show();
                });
            });

            // When user confirms deletion
            document.getElementById('confirmRowDeleteBtn').addEventListener('click', () => {
                if (rowToDelete) {
                    rowToDelete.remove();
                    modal.hide();
                }
            });
        });
    </script>

</body>

</html>