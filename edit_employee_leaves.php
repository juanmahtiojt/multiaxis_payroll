<?php
session_start();
include __DIR__ . "/config.php";

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['user'];
$role = $_SESSION['role'];

// Check if user has permission
if ($role !== 'admin') {
    header("Location: add_user.php");
    exit();
}

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: add_user.php");
    exit();
}

$id = mysqli_real_escape_string($conn, $_GET['id']);
$message = '';
$error = '';

// Fetch employee data
$query = "SELECT e.*, dr.name, dr.department, dr.daily_rate 
          FROM employees e
          JOIN daily_rate dr ON e.id_no = dr.id_no
          WHERE e.id_no = '$id'";
$result = mysqli_query($conn, $query);

if (mysqli_num_rows($result) == 0) {
    header("Location: add_user.php");
    exit();
}

$employee = mysqli_fetch_assoc($result);

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_leaves'])) {
    // Get form data
    $leave_with_pay = mysqli_real_escape_string($conn, $_POST['leave_with_pay']);
    $leave_without_pay = mysqli_real_escape_string($conn, $_POST['leave_without_pay']);
    $available_leave = mysqli_real_escape_string($conn, $_POST['available_leave']);
    
    // Validate inputs
    if (!is_numeric($leave_with_pay) || !is_numeric($leave_without_pay) || !is_numeric($available_leave)) {
        $error = "All leave fields must be numeric values.";
    } else {
        // Update employee leave information
        $update_query = "UPDATE employees SET 
                        leave_with_pay = '$leave_with_pay',
                        leave_without_pay = '$leave_without_pay',
                        available_leave = '$available_leave'
                        WHERE id_no = '$id'";
                        
        if (mysqli_query($conn, $update_query)) {
            $message = "Employee leave information updated successfully.";
        } else {
            $error = "Error updating leave information: " . mysqli_error($conn);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Employee Leaves</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background-color: #d6eaf8;
            font-family: 'Segoe UI', sans-serif;
            min-height: 100vh;
            padding: 20px;
            display: flex;
            justify-content: center;
            align-items: flex-start;
        }

        .main-content {
            width: 100%;
            max-width: 800px;
            padding: 15px;
            box-sizing: border-box;
        }

        .container-box {
            background: white;
            padding: 25px;
            border-radius: 20px;
            box-shadow: 0px 6px 20px rgba(0, 0, 0, 0.2);
            width: 100%;
            height: auto;
        }

        .form-label {
            font-weight: 500;
        }

        .btn-custom {
            background-color: #007bff;
            color: white;
            font-size: 14px;
            padding: 8px 15px;
            border-radius: 5px;
            border: none;
        }

        .btn-custom:hover {
            background-color: #0056b3;
        }
        
        .employee-info {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        .alert {
            margin-bottom: 20px;
        }

        @media (max-width: 768px) {
            .container-box {
                padding: 20px;
            }
            
            .employee-info {
                padding: 12px;
            }
            
            .main-content {
                padding: 10px;
            }
            
            body {
                padding: 10px;
            }
        }

        @media (max-width: 576px) {
            .container-box {
                padding: 15px;
            }
            
            h3 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>

<!-- Main Content -->
<div class="main-content">
    <div class="container-box">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3><i class="fas fa-calendar-alt"></i> Edit Employee Leaves</h3>
            <a href="add_user.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
        </div>
        
        <?php if ($message) : ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= $message ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($error) : ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= $error ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <div class="employee-info">
            <div class="row">
                <div class="col-md-6 col-sm-12 mb-2">
                    <p><strong>Employee ID:</strong> <?= $employee['id_no'] ?></p>
                    <p><strong>Name:</strong> <?= $employee['name'] ?></p>
                </div>
                <div class="col-md-6 col-sm-12">
                    <p><strong>Department:</strong> <?= $employee['department'] ?></p>
                    <p><strong>Daily Rate:</strong> ₱<?= number_format($employee['daily_rate'], 2) ?></p>
                </div>
            </div>
        </div>
        
        <form method="POST" action="">
            <div class="mb-3">
                <label for="leave_with_pay" class="form-label">Leave With Pay</label>
                <input type="number" class="form-control" id="leave_with_pay" name="leave_with_pay" value="<?= $employee['leave_with_pay'] ?? 0 ?>" min="0" step="1" required>
                <div class="form-text">Number of days the employee has used leave with pay.</div>
            </div>
            
            <div class="mb-3">
                <label for="leave_without_pay" class="form-label">Leave Without Pay</label>
                <input type="number" class="form-control" id="leave_without_pay" name="leave_without_pay" value="<?= $employee['leave_without_pay'] ?? 0 ?>" min="0" step="1" required>
                <div class="form-text">Number of days the employee has taken unpaid leave.</div>
            </div>
            
            <div class="mb-3">
                <label for="available_leave" class="form-label">Available Leave</label>
                <input type="number" class="form-control" id="available_leave" name="available_leave" value="<?= $employee['available_leave'] ?? 0 ?>" min="0" step="1" required>
                <div class="form-text">Number of paid leave days currently available to the employee.</div>
            </div>
            
            <hr class="my-4">
            
            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                <a href="add_user.php" class="btn btn-secondary me-md-2">Cancel</a>
                <button type="submit" name="update_leaves" class="btn btn-primary">Update Leave Information</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Auto-dismiss alerts after 5 seconds
    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(function() {
            var alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                var bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
    });
</script>
</body>
</html>