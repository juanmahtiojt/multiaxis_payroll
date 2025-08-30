<?php
session_start();
include __DIR__ . "/config.php";

if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$message = "";
$edit_mode = false;
$edit_data = null;

// Check if we are in edit mode
if (isset($_GET['edit']) && !empty($_GET['edit'])) {
    $edit_mode = true;
    $edit_date = $_GET['edit'];
    
    // Fetch the holiday data for editing
    $edit_stmt = $conn->prepare("SELECT holiday_date, description, holiday_type, regular_rate, overtime_rate, 
                                restdayholiday_regular, restdayholiday_overtime, restdayholiday_special, 
                                restdayspecialholiday_overtime 
                                FROM multiaxis_payroll_system.holidays WHERE holiday_date = ?");
    $edit_stmt->bind_param("s", $edit_date);
    $edit_stmt->execute();
    $edit_result = $edit_stmt->get_result();
    
    if ($edit_result && $edit_result->num_rows > 0) {
        $edit_data = $edit_result->fetch_assoc();
    } else {
        $message = "Holiday not found.";
        $message_class = "alert-danger";
        $edit_mode = false;
    }
    
    $edit_stmt->close();
}

// Handle form submission for adding or updating
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $holiday_date = trim($_POST['holiday_date']);
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';
    $holiday_type = isset($_POST['holiday_type']) ? trim($_POST['holiday_type']) : 'Regular';
    
    // Convert all rates to decimal format (divide by 100 if submitted as percentages)
    $regular_rate = isset($_POST['regular_rate']) ? (float)trim($_POST['regular_rate']) : 0;
    $overtime_rate = isset($_POST['overtime_rate']) ? (float)trim($_POST['overtime_rate']) : 0;
    $restdayholiday_regular = isset($_POST['restdayholiday_regular']) ? (float)trim($_POST['restdayholiday_regular']) : 0;
    $restdayholiday_overtime = isset($_POST['restdayholiday_overtime']) ? (float)trim($_POST['restdayholiday_overtime']) : 0;
    $restdayholiday_special = isset($_POST['restdayholiday_special']) ? (float)trim($_POST['restdayholiday_special']) : 0;
    $restdayspecialholiday_overtime = isset($_POST['restdayspecialholiday_overtime']) ? (float)trim($_POST['restdayspecialholiday_overtime']) : 0;
    
    // Check if we're updating an existing holiday
    if (isset($_POST['original_date']) && !empty($_POST['original_date'])) {
        $original_date = trim($_POST['original_date']);
        
        // If the date was changed, delete old entry and create new one
        if ($original_date != $holiday_date) {
            $delete_stmt = $conn->prepare("DELETE FROM multiaxis_payroll_system.holidays WHERE holiday_date = ?");
            $delete_stmt->bind_param("s", $original_date);
            $delete_stmt->execute();
            $delete_stmt->close();
            
            $stmt = $conn->prepare("INSERT INTO multiaxis_payroll_system.holidays 
                                  (holiday_date, description, holiday_type, regular_rate, overtime_rate,
                                   restdayholiday_regular, restdayholiday_overtime, restdayholiday_special, 
                                   restdayspecialholiday_overtime) 
                                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssdddddd", $holiday_date, $description, $holiday_type, $regular_rate, $overtime_rate,
                             $restdayholiday_regular, $restdayholiday_overtime, $restdayholiday_special, 
                             $restdayspecialholiday_overtime);
        } else {
            // Just update the existing record
            $stmt = $conn->prepare("UPDATE multiaxis_payroll_system.holidays 
                                  SET description = ?, holiday_type = ?, regular_rate = ?, overtime_rate = ?,
                                  restdayholiday_regular = ?, restdayholiday_overtime = ?, restdayholiday_special = ?,
                                  restdayspecialholiday_overtime = ? 
                                  WHERE holiday_date = ?");
            $stmt->bind_param("ssdddddds", $description, $holiday_type, $regular_rate, $overtime_rate,
                             $restdayholiday_regular, $restdayholiday_overtime, $restdayholiday_special,
                             $restdayspecialholiday_overtime, $holiday_date);
        }
    } else {
        // Insert new holiday
        $stmt = $conn->prepare("INSERT INTO multiaxis_payroll_system.holidays 
                              (holiday_date, description, holiday_type, regular_rate, overtime_rate,
                               restdayholiday_regular, restdayholiday_overtime, restdayholiday_special, 
                               restdayspecialholiday_overtime) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssdddddd", $holiday_date, $description, $holiday_type, $regular_rate, $overtime_rate,
                         $restdayholiday_regular, $restdayholiday_overtime, $restdayholiday_special,
                         $restdayspecialholiday_overtime);
    }

    if ($stmt->execute()) {
        $message = isset($_POST['original_date']) ? "Holiday updated successfully!" : "Holiday date added successfully!";
        $message_class = "alert-success";
        if (isset($_POST['original_date'])) {
            $edit_mode = false;
            $edit_data = null;
        }
    } else {
        $message = "Error: " . $stmt->error;
        $message_class = "alert-danger";
    }

    $stmt->close();
}

// Get holiday list
$query = "SELECT holiday_date, description, holiday_type, regular_rate, overtime_rate, 
          restdayholiday_regular, restdayholiday_overtime, restdayholiday_special, 
          restdayspecialholiday_overtime 
          FROM multiaxis_payroll_system.holidays ORDER BY holiday_date";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Holiday Management | MultiAxis Payroll System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
        }
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
        }
        .logo{
            height: 150px;
            width: auto;
            margin-right: 1rem;

        }
        .page-header {
            display: flex;
            align-items: center;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 15px 20px;
            margin-bottom: 1.5rem;
        }
        .card {
            border: none;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            margin-bottom: 1.5rem;
        }
        .card-header {
            background-color: var(--primary-color);
            color: white;
            font-weight: 600;
            border-bottom: none;
        }
        .rates-section {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>

<div class="page-header">
    <img src="my_project/images/MULTI-removebg-preview.png" alt="Logo" class="logo">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h1><i class="fas fa-calendar-alt me-2"></i> Holiday Management</h1>
                <p class="lead mb-0">MultiAxis Payroll System</p>
            </div>
            <div class="col-md-6 text-md-end">
                <a href="dashboard.php" class="btn btn-light">
                    <i class="fas fa-tachometer-alt me-1"></i> Dashboard
                </a>
            </div>
        </div>
    </div>
</div>

<div class="container">
    <div class="row">
        <div class="col-lg-5">
            <div class="card">
                <div class="card-header">
                    <i class="fas <?= $edit_mode ? 'fa-edit' : 'fa-plus-circle'; ?> me-2"></i>
                    <span><?= $edit_mode ? 'Edit Holiday' : 'Add New Holiday'; ?></span>
                </div>
                <div class="card-body">
                    <?php if ($message): ?>
                        <div class="alert <?= $message_class; ?>" role="alert">
                            <?php if ($message_class === 'alert-success'): ?>
                                <i class="fas fa-check-circle me-2"></i>
                            <?php else: ?>
                                <i class="fas fa-exclamation-circle me-2"></i>
                            <?php endif; ?>
                            <?= $message; ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <?php if ($edit_mode): ?>
                            <input type="hidden" name="original_date" value="<?= htmlspecialchars($edit_data['holiday_date']); ?>">
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <label for="holiday_date" class="form-label">Holiday Date</label>
                            <input type="date" class="form-control" id="holiday_date" name="holiday_date" 
                                value="<?= $edit_mode ? htmlspecialchars($edit_data['holiday_date']) : ''; ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Holiday Description</label>
                            <input type="text" class="form-control" id="description" name="description" 
                                value="<?= $edit_mode ? htmlspecialchars($edit_data['description']) : ''; ?>" 
                                placeholder="e.g., Independence Day">
                        </div>
                        <div class="mb-3">
                            <label for="holiday_type" class="form-label">Holiday Type</label>
                            <select class="form-select" id="holiday_type" name="holiday_type" required>
                                <option value="Regular" <?= ($edit_mode && $edit_data['holiday_type'] === 'Regular') ? 'selected' : ''; ?>>Regular</option>
                                <option value="Special" <?= ($edit_mode && $edit_data['holiday_type'] === 'Special') ? 'selected' : ''; ?>>Special</option>
                            </select>
                        </div>
                        
                        <!-- Standard Rates -->
                        <div class="rates-section">
                            <h5>Standard Rates</h5>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="regular_rate" class="form-label">Regular Rate</label>
                                    <input type="number" class="form-control" id="regular_rate" name="regular_rate" 
                                        value="<?= $edit_mode ? htmlspecialchars($edit_data['regular_rate']) : ''; ?>" 
                                        min="0" max="5" step="0.01" placeholder="e.g., 1.0" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="overtime_rate" class="form-label">Overtime Rate</label>
                                    <input type="number" class="form-control" id="overtime_rate" name="overtime_rate" 
                                        value="<?= $edit_mode ? htmlspecialchars($edit_data['overtime_rate']) : ''; ?>" 
                                        min="0" max="5" step="0.01" placeholder="e.g., 1.3" required>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Rest Day Rates -->
                        <div class="rates-section">
                            <h5>Rest Day Holiday Rates</h5>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="restdayholiday_regular" class="form-label">Regular Rest Day</label>
                                    <input type="number" class="form-control" id="restdayholiday_regular" name="restdayholiday_regular" 
                                        value="<?= $edit_mode ? htmlspecialchars($edit_data['restdayholiday_regular']) : ''; ?>" 
                                        min="0" max="5" step="0.01" placeholder="e.g., 1.6" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="restdayholiday_overtime" class="form-label">Overtime Rest Day</label>
                                    <input type="number" class="form-control" id="restdayholiday_overtime" name="restdayholiday_overtime" 
                                        value="<?= $edit_mode ? htmlspecialchars($edit_data['restdayholiday_overtime']) : ''; ?>" 
                                        min="0" max="5" step="0.01" placeholder="e.g., 1.95" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="restdayholiday_special" class="form-label">Special Rest Day</label>
                                    <input type="number" class="form-control" id="restdayholiday_special" name="restdayholiday_special" 
                                        value="<?= $edit_mode ? htmlspecialchars($edit_data['restdayholiday_special']) : ''; ?>" 
                                        min="0" max="5" step="0.01" placeholder="e.g., 0.5" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="restdayspecialholiday_overtime" class="form-label">Special OT Rest Day</label>
                                    <input type="number" class="form-control" id="restdayspecialholiday_overtime" name="restdayspecialholiday_overtime" 
                                        value="<?= $edit_mode ? htmlspecialchars($edit_data['restdayspecialholiday_overtime']) : ''; ?>" 
                                        min="0" max="5" step="0.01" placeholder="e.g., 0.65" required>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end">
                            <?php if ($edit_mode): ?>
                                <a href="<?= $_SERVER['PHP_SELF']; ?>" class="btn btn-secondary me-2">Cancel</a>
                                <button type="submit" class="btn btn-primary">Update</button>
                            <?php else: ?>
                                <button type="submit" class="btn btn-primary">Add Holiday</button>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <div><i class="fas fa-calendar-check me-2"></i>Holiday List</div>
                    <div><span class="badge bg-secondary"><?= $result ? $result->num_rows : 0 ?> Holidays</span></div>
                </div>
                <div class="card-body p-0">
                    <?php if ($result && $result->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Description</th>
                                        <th>Type</th>
                                        <th>Regular</th>
                                        <th>OT</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($row = $result->fetch_assoc()): ?>
                                        <tr>
                                            <td><?= date('M d, Y', strtotime(htmlspecialchars($row['holiday_date']))); ?></td>
                                            <td><?= htmlspecialchars($row['description']); ?></td>
                                            <td>
                                                <span class="badge bg-<?= $row['holiday_type'] === 'Special' ? 'danger' : 'primary' ?>">
                                                    <?= htmlspecialchars($row['holiday_type']); ?>
                                                </span>
                                            </td>
                                                <td><?= htmlspecialchars(number_format((float)($row['regular_rate'] ?? 0), 2)); ?></td>
                                                <td><?= htmlspecialchars(number_format((float)($row['overtime_rate'] ?? 0), 2)); ?></td>

                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="?edit=<?= htmlspecialchars($row['holiday_date']); ?>" class="btn btn-warning">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <button type="button" class="btn btn-info" data-bs-toggle="modal" data-bs-target="#rateModal<?= str_replace('-', '', $row['holiday_date']); ?>">
                                                        <i class="fas fa-info-circle"></i>
                                                    </button>
                                                    <form method="POST" action="delete_holiday.php" onsubmit="return confirm('Delete this holiday?');" style="display:inline;">
                                                        <input type="hidden" name="holiday_date" value="<?= htmlspecialchars($row['holiday_date']); ?>">
                                                        <button type="submit" class="btn btn-danger">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                                
                                                <!-- Rates Modal -->
                                                <div class="modal fade" id="rateModal<?= str_replace('-', '', $row['holiday_date']); ?>" tabindex="-1" aria-hidden="true">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <div class="modal-header bg-primary text-white">
                                                                <h5 class="modal-title">
                                                                    Rate Details: <?= htmlspecialchars($row['description']); ?>
                                                                </h5>
                                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <div class="row mb-3">
                                                                    <div class="col-6">
                                                                        <label class="fw-bold">Regular Rate:</label>
                                                                        <div><?= number_format((float)($row['regular_rate'] ?? 0), 2); ?></div>
                                                                    </div>
                                                                    <div class="col-6">
                                                                        <label class="fw-bold">Overtime Rate:</label>
                                                                        <div><?= number_format((float)($row['overtime_rate'] ?? 0), 2); ?></div>
                                                                    </div>
                                                                </div>
                                                                <div class="row">
                                                                    <div class="col-6">
                                                                        <label class="fw-bold">Rest Day Regular:</label>
                                                                        <div><?= number_format((float)($row['restdayholiday_regular'] ?? 0), 2); ?></div>
                                                                    </div>
                                                                    <div class="col-6">
                                                                        <label class="fw-bold">Rest Day OT:</label>
                                                                        <div><?= number_format((float)($row['restdayholiday_overtime'] ?? 0), 2); ?></div>
                                                                    </div>
                                                                </div>
                                                                <div class="row mt-3">
                                                                    <div class="col-6">
                                                                        <label class="fw-bold">Special Rest Day:</label>
                                                                        <div><?= number_format((float)($row['restdayholiday_special'] ?? 0), 2); ?></div>
                                                                    </div>
                                                                    <div class="col-6">
                                                                        <label class="fw-bold">Special Rest Day OT:</label>
                                                                        <div><?= number_format((float)($row['restdayspecialholiday_overtime'] ?? 0), 2); ?></div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center p-4">
                            <i class="fas fa-calendar-times text-muted mb-3" style="font-size: 3rem;"></i>
                            <p class="mb-0">No holidays have been added yet.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Set default values based on holiday type
    const holidayTypeSelect = document.getElementById('holiday_type');
    const regularRateInput = document.getElementById('regular_rate');
    const overtimeRateInput = document.getElementById('overtime_rate');
    const restdayholidayRegularInput = document.getElementById('restdayholiday_regular');
    const restdayholidayOvertimeInput = document.getElementById('restdayholiday_overtime');
    const restdayholidaySpecialInput = document.getElementById('restdayholiday_special');
    const restdayspecialholidayOvertimeInput = document.getElementById('restdayspecialholiday_overtime');
    
    holidayTypeSelect.addEventListener('change', function() {
        // Only set default values if fields are empty or not in edit mode
        const isEditMode = document.querySelector('input[name="original_date"]') !== null;
        const shouldSetDefaults = !isEditMode || (
            regularRateInput.value === '0' || 
            regularRateInput.value === ''
        );
        
        if (shouldSetDefaults) {
            if (this.value === 'Regular') {
                // Regular holiday rates (decimal format)
                regularRateInput.value = '1.00';
                overtimeRateInput.value = '1.30';
                restdayholidayRegularInput.value = '1.60';
                restdayholidayOvertimeInput.value = '1.95';
                restdayholidaySpecialInput.value = '0.50';
                restdayspecialholidayOvertimeInput.value = '0.65';
            } else { // Special
                // Special holiday rates (decimal format)
                regularRateInput.value = '0.30';
                overtimeRateInput.value = '0.39';
                restdayholidayRegularInput.value = '0.50';
                restdayholidayOvertimeInput.value = '0.65';
                restdayholidaySpecialInput.value = '0.30';
                restdayspecialholidayOvertimeInput.value = '0.39';
            }
        }
    });
    
    // Set initial default values for new entries
    const isEditMode = document.querySelector('input[name="original_date"]') !== null;
    if (!isEditMode && regularRateInput.value === '') {
        // Default to regular holiday rates
        regularRateInput.value = '1.00';
        overtimeRateInput.value = '1.30';
        restdayholidayRegularInput.value = '1.60';
        restdayholidayOvertimeInput.value = '1.95';
        restdayholidaySpecialInput.value = '0.50';
        restdayspecialholidayOvertimeInput.value = '0.65';
    }
});
</script>
</body>
</html>