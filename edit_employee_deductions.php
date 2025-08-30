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
    header("Location: dashboard.php");
    exit();
}

$id = isset($_GET['id']) ? $_GET['id'] : '';
$success_message = '';
$error_message = '';

if (empty($id)) {
    header("Location: add_user.php");
    exit();
}

// Fetch employee data
$query = "SELECT dr.id_no, dr.name, dr.department, dr.daily_rate, 
          e.sss_premium, e.sss_loan, e.pagibig_premium, e.pagibig_loan, 
          e.philhealth, e.cash_advance
          FROM multiaxis_payroll_system.daily_rate dr
          LEFT JOIN employees e ON dr.id_no = e.id_no
          WHERE dr.id_no = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "s", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) == 0) {
    header("Location: add_user.php");
    exit();
}

$employee = mysqli_fetch_assoc($result);

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $sss_premium = mysqli_real_escape_string($conn, $_POST['sss_premium']);
    $sss_loan = mysqli_real_escape_string($conn, $_POST['sss_loan']);
    $pagibig_premium = mysqli_real_escape_string($conn, $_POST['pagibig_premium']);
    $pagibig_loan = mysqli_real_escape_string($conn, $_POST['pagibig_loan']);
    $philhealth = mysqli_real_escape_string($conn, $_POST['philhealth']);
    $cash_advance = mysqli_real_escape_string($conn, $_POST['cash_advance']);
    
    // Check if employee exists in employees table
    $check_query = "SELECT id_no FROM employees WHERE id_no = ?";
    $check_stmt = mysqli_prepare($conn, $check_query);
    mysqli_stmt_bind_param($check_stmt, "s", $id);
    mysqli_stmt_execute($check_stmt);
    $check_result = mysqli_stmt_get_result($check_stmt);
    
    if (mysqli_num_rows($check_result) > 0) {
        // Update existing record
        $update_query = "UPDATE employees SET 
                        sss_premium = ?, sss_loan = ?, pagibig_premium = ?, 
                        pagibig_loan = ?, philhealth = ?, cash_advance = ?
                        WHERE id_no = ?";
        $update_stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($update_stmt, "dddddds", $sss_premium, $sss_loan, $pagibig_premium, 
                              $pagibig_loan, $philhealth, $cash_advance, $id);
        
        if (mysqli_stmt_execute($update_stmt)) {
            $success_message = "Deductions updated successfully!";
        } else {
            $error_message = "Error updating deductions: " . mysqli_error($conn);
        }
    } else {
        // Insert new record
        $insert_query = "INSERT INTO employees (id_no, sss_premium, sss_loan, pagibig_premium, 
                        pagibig_loan, philhealth, cash_advance) 
                        VALUES (?, ?, ?, ?, ?, ?, ?)";
        $insert_stmt = mysqli_prepare($conn, $insert_query);
        mysqli_stmt_bind_param($insert_stmt, "sdddddd", $id, $sss_premium, $sss_loan, $pagibig_premium, 
                              $pagibig_loan, $philhealth, $cash_advance);
        
        if (mysqli_stmt_execute($insert_stmt)) {
            $success_message = "Deductions added successfully!";
        } else {
            $error_message = "Error adding deductions: " . mysqli_error($conn);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Employee Deductions</title>
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
        
        .input-group-text {
            background-color: #f4f6f9;
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
        }
    </style>
</head>
<body>

<!-- Main Content -->
<div class="main-content">
    <div class="container-box">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3><i class="fas fa-file-invoice-dollar"></i> Edit Employee Deductions</h3>
            <a href="add_user.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
        </div>

        <?php if (!empty($success_message)) : ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= $success_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($error_message)) : ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= $error_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <strong>Employee Information</strong>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 col-sm-6 mb-2">
                        <p><strong>Employee ID:</strong> <?= $employee['id_no']; ?></p>
                    </div>
                    <div class="col-md-4 col-sm-6 mb-2">
                        <p><strong>Name:</strong> <?= $employee['name']; ?></p>
                    </div>
                    <div class="col-md-4 col-sm-6 mb-2">
                        <p><strong>Department:</strong> <?= $employee['department']; ?></p>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4 col-sm-6">
                        <p><strong>Daily Rate:</strong> ₱<?= number_format($employee['daily_rate'], 2); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <form method="POST" action="">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <div class="form-group">
                        <label for="sss_premium" class="form-label">SSS Premium</label>
                        <div class="input-group">
                            <span class="input-group-text">₱</span>
                            <input type="number" class="form-control" id="sss_premium" name="sss_premium" value="<?= isset($employee['sss_premium']) ? $employee['sss_premium'] : 0; ?>" step="0.01" min="0">
                        </div>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="form-group">
                        <label for="sss_loan" class="form-label">SSS Loan</label>
                        <div class="input-group">
                            <span class="input-group-text">₱</span>
                            <input type="number" class="form-control" id="sss_loan" name="sss_loan" value="<?= isset($employee['sss_loan']) ? $employee['sss_loan'] : 0; ?>" step="0.01" min="0">
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <div class="form-group">
                        <label for="pagibig_premium" class="form-label">Pag-IBIG Premium</label>
                        <div class="input-group">
                            <span class="input-group-text">₱</span>
                            <input type="number" class="form-control" id="pagibig_premium" name="pagibig_premium" value="<?= isset($employee['pagibig_premium']) ? $employee['pagibig_premium'] : 0; ?>" step="0.01" min="0">
                        </div>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="form-group">
                        <label for="pagibig_loan" class="form-label">Pag-IBIG Loan</label>
                        <div class="input-group">
                            <span class="input-group-text">₱</span>
                            <input type="number" class="form-control" id="pagibig_loan" name="pagibig_loan" value="<?= isset($employee['pagibig_loan']) ? $employee['pagibig_loan'] : 0; ?>" step="0.01" min="0">
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <div class="form-group">
                        <label for="philhealth" class="form-label">PhilHealth</label>
                        <div class="input-group">
                            <span class="input-group-text">₱</span>
                            <input type="number" class="form-control" id="philhealth" name="philhealth" value="<?= isset($employee['philhealth']) ? $employee['philhealth'] : 0; ?>" step="0.01" min="0">
                        </div>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="form-group">
                        <label for="cash_advance" class="form-label">Cash Advance</label>
                        <div class="input-group">
                            <span class="input-group-text">₱</span>
                            <input type="number" class="form-control" id="cash_advance" name="cash_advance" value="<?= isset($employee['cash_advance']) ? $employee['cash_advance'] : 0; ?>" step="0.01" min="0">
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-group mt-0 d-flex justify-content-end">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Save Changes
                </button>
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