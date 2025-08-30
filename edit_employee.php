<?php
session_start();
include __DIR__ . "/config.php";

if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$message = "";
$original_id = "";

if (!isset($_GET['id'])) {
    header("Location: employee_attendance.php");
    exit();
}

$id_no = $_GET['id'];
$original_id = $id_no; // Store the original ID for reference

// Fetch employee data
$employee_query = "SELECT * FROM multiaxis_payroll_system.daily_rate WHERE id_no = ?";
$stmt = $conn->prepare($employee_query);
$stmt->bind_param("s", $id_no);
$stmt->execute();
$result = $stmt->get_result();
$employee = $result->fetch_assoc();
$stmt->close();

if (!$employee) {
    $message = "❌ Employee not found!";
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_id_no = trim($_POST['id_no']);
    $name = trim($_POST['name']);
    $department = trim($_POST['department']);
    $daily_rate = floatval($_POST['daily_rate']);
    $pay_schedule = trim($_POST['pay_schedule']);

    if ($new_id_no && $name && $department && $daily_rate > 0 && $pay_schedule) {
        // Check if the ID is being changed and if the new ID already exists
        if ($new_id_no !== $original_id) {
            $check_query = "SELECT COUNT(*) as count FROM multiaxis_payroll_system.daily_rate WHERE id_no = ?";
            $check_stmt = $conn->prepare($check_query);
            $check_stmt->bind_param("s", $new_id_no);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            $row = $check_result->fetch_assoc();
            $check_stmt->close();
            
            if ($row['count'] > 0) {
                $message = "❌ Employee ID already exists. Please choose a different ID.";
            } else {
                // ID is being changed and is unique, perform update with new ID
                $update_query = "UPDATE multiaxis_payroll_system.daily_rate 
                                SET id_no = ?, name = ?, department = ?, daily_rate = ?, pay_schedule = ? 
                                WHERE id_no = ?";
                $stmt = $conn->prepare($update_query);
                $stmt->bind_param("sssdss", $new_id_no, $name, $department, $daily_rate, $pay_schedule, $original_id);
                
                if ($stmt->execute()) {
                    $message = "✅ Employee updated successfully!";
                    // Update the ID in the URL for consistency
                    echo "<script>window.history.replaceState(null, null, '?id=" . $new_id_no . "');</script>";
                    // Update the original ID and employee data for display
                    $original_id = $new_id_no;
                    $employee['id_no'] = $new_id_no;
                    $employee['name'] = $name;
                    $employee['department'] = $department;
                    $employee['daily_rate'] = $daily_rate;
                    $employee['pay_schedule'] = $pay_schedule;
                } else {
                    $message = "❌ Error updating employee: " . $stmt->error;
                }
                $stmt->close();
            }
        } else {
            // ID is not being changed, perform regular update
            $update_query = "UPDATE multiaxis_payroll_system.daily_rate 
                            SET name = ?, department = ?, daily_rate = ?, pay_schedule = ? 
                            WHERE id_no = ?";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param("ssdss", $name, $department, $daily_rate, $pay_schedule, $original_id);
            
            if ($stmt->execute()) {
                $message = "✅ Employee updated successfully!";
                // Refresh data
                $employee['name'] = $name;
                $employee['department'] = $department;
                $employee['daily_rate'] = $daily_rate;
                $employee['pay_schedule'] = $pay_schedule;
            } else {
                $message = "❌ Error updating employee: " . $stmt->error;
            }
            $stmt->close();
        }
    } else {
        $message = "⚠️ Please fill in all fields correctly.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Employee</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        html, body {
            height: 100%;
            width: 100%;
            margin: 0;
            padding: 0;
        }
        body {
            background-color: #d6eaf8;
            font-family: 'Segoe UI', sans-serif;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            position: relative;
        }
        .main-content {
            width: 100%;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }
        .container-box {
            background: white;
            padding: 25px;
            border-radius: 20px;
            box-shadow: 0px 6px 20px rgba(0, 0, 0, 0.2);
            width: 100%;
            margin: 0 auto;
        }
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            font-weight: 600;
        }
        
        .btn-primary {
            background-color: #3498db;
            border-color: #3498db;
        }
        
        .btn-primary:hover {
            background-color: #2980b9;
            border-color: #2980b9;
        }
        
        .btn-secondary {
            background-color: #95a5a6;
            border-color: #95a5a6;
        }
        
        .btn-secondary:hover {
            background-color: #7f8c8d;
            border-color: #7f8c8d;
        }
        
        .input-group-text {
            background-color: #f4f6f9;
        }
        
        .form-title {
            text-align: center;
            margin-bottom: 25px;
            font-size: calc(1.2rem + 1vw);
            font-weight: 600;
            color: #2c3e50;
        }
        
        .btn-group-responsive {
            display: flex;
            justify-content: space-between;
        }
        
        @media (max-width: 768px) {
            .container-box {
                padding: 15px;
            }
            
            .main-content {
                padding: 10px;
                width: 95%;
            }
            
            .row {
                margin-left: -5px;
                margin-right: -5px;
            }
            
            .col-md-6 {
                padding-left: 5px;
                padding-right: 5px;
            }
            
            .btn-group-responsive {
                flex-direction: column;
                gap: 10px;
            }
            
            .btn-group-responsive .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>

<div class="main-content">
    <div class="container-box">
        <h2 class="form-title">✏️ Edit Employee</h2>
        <?php if ($message): ?>
            <div class="alert alert-info"><?= $message; ?></div>
        <?php endif; ?>

        <?php if ($employee): ?>
        <form method="POST" action="">
            <div class="form-group mb-3">
                <label class="form-label">Employee ID</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-id-card"></i></span>
                    <input type="text" class="form-control" name="id_no" value="<?= $employee['id_no']; ?>" required>
                </div>
                <small class="text-muted">Note: Changing an employee ID will update all associated records.</small>
            </div>

            <div class="form-group mb-3">
                <label class="form-label">Full Name</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                    <input type="text" class="form-control" name="name" value="<?= htmlspecialchars($employee['name']); ?>" required>
                </div>
            </div>

            <div class="form-group mb-3">
                <label class="form-label">Department</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-building"></i></span>
                    <input type="text" class="form-control" name="department" value="<?= htmlspecialchars($employee['department']); ?>" required>
                </div>
            </div>

            <div class="form-group mb-3">
                <label class="form-label">Daily Rate</label>
                <div class="input-group">
                 <span class="input-group-text"><i class="fas fa-peso-sign"></i></span>
                    <input type="number" step="0.01" class="form-control" name="daily_rate" value="<?= $employee['daily_rate']; ?>" required>
                </div>
            </div>

            <div class="form-group mb-3">
                <label class="form-label">Pay Schedule</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>
                    <select name="pay_schedule" class="form-select" required>
                        <option value="semi-monthly" <?= $employee['pay_schedule'] === 'semi-monthly' ? 'selected' : '' ?>>Semi-Monthly</option>
                        <option value="monthly" <?= $employee['pay_schedule'] === 'monthly' ? 'selected' : '' ?>>Monthly</option>
                        <option value="weekly" <?= $employee['pay_schedule'] === 'weekly' ? 'selected' : '' ?>>Weekly</option>
                        <option value="daily" <?= $employee['pay_schedule'] === 'daily' ? 'selected' : '' ?>>Daily</option>
                        <option value="fixed" <?= $employee['pay_schedule'] === 'fixed' ? 'selected' : '' ?>>Fixed (Semi-Monthly)</option>
                    </select>
                </div>
            </div>

            <div class="btn-group-responsive mt-4">
                <a href="add_user.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update Employee</button>
            </div>
        </form>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>