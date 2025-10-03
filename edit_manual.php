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
</head>

<body>
    <div class="container mt-4">
        <div class="card shadow">
            <div class="card-header">
                <h1 class="card-title">Edit Attendance</h1>
                <p class="card-subtitle">Update an existing attendance record</p>
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
                                </tr>
                            </thead>
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
</body>

</html>