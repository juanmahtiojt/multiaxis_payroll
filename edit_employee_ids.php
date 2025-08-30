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
$query = "SELECT dr.id_no, dr.name, dr.department, 
          e.sss_no, e.pagibig_no, e.tin_no, e.philhealth_no 
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
    $sss_no = mysqli_real_escape_string($conn, $_POST['sss_no']);
    $pagibig_no = mysqli_real_escape_string($conn, $_POST['pagibig_no']);
    $tin_no = mysqli_real_escape_string($conn, $_POST['tin_no']);
    $philhealth_no = mysqli_real_escape_string($conn, $_POST['philhealth_no']);
    
    // Check if employee exists in employees table
    $check_query = "SELECT id_no FROM employees WHERE id_no = ?";
    $check_stmt = mysqli_prepare($conn, $check_query);
    mysqli_stmt_bind_param($check_stmt, "s", $id);
    mysqli_stmt_execute($check_stmt);
    $check_result = mysqli_stmt_get_result($check_stmt);
    
    if (mysqli_num_rows($check_result) > 0) {
        // Update existing record
        $update_query = "UPDATE employees SET 
                        sss_no = ?, pagibig_no = ?, tin_no = ?, philhealth_no = ?
                        WHERE id_no = ?";
        $update_stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($update_stmt, "sssss", $sss_no, $pagibig_no, $tin_no, $philhealth_no, $id);
        
        if (mysqli_stmt_execute($update_stmt)) {
            $success_message = "Government IDs updated successfully!";
        } else {
            $error_message = "Error updating Government IDs: " . mysqli_error($conn);
        }
    } else {
        // Insert new record
        $insert_query = "INSERT INTO employees (id_no, sss_no, pagibig_no, tin_no, philhealth_no) 
                        VALUES (?, ?, ?, ?, ?)";
        $insert_stmt = mysqli_prepare($conn, $insert_query);
        mysqli_stmt_bind_param($insert_stmt, "sssss", $id, $sss_no, $pagibig_no, $tin_no, $philhealth_no);
        
        if (mysqli_stmt_execute($insert_stmt)) {
            $success_message = "Government IDs added successfully!";
        } else {
            $error_message = "Error adding Government IDs: " . mysqli_error($conn);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Government IDs</title>
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
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .header .logo {
            width: 120px;
            height: auto;
            margin-bottom: 10px;
        }

        .header .company-name {
            font-size: 1.5rem;
            font-weight: 600;
            color: #2c3e50;
        }

        .container-box {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0px 6px 20px rgba(0, 0, 0, 0.15);
            width: 100%;
            max-width: 900px;
            margin: 0 auto;
        }

        .form-group {
            margin-bottom: 15px;
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

        .navbar {
            background-color: #2c3e50;
            padding: 12px 20px;
            margin-bottom: 20px;
            border-radius: 10px;
        }

        .navbar .navbar-brand {
            color: white;
            font-weight: 600;
        }

        .navbar .nav-link {
            color: rgba(255, 255, 255, 0.8);
        }

        .navbar .nav-link:hover {
            color: white;
        }

        .navbar .nav-link.active {
            color: white;
            font-weight: 500;
        }

        .navbar-toggler {
            border-color: rgba(255, 255, 255, 0.5);
        }

        .navbar-toggler-icon {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba%28255, 255, 255, 0.75%29' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
        }

        .card {
            margin-bottom: 20px;
            border: none;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .card-footer {
            background-color: rgba(0, 0, 0, 0.03);
            padding: 15px;
        }

        /* Responsive styles */
        @media (max-width: 767px) {
            body {
                padding: 10px;
            }
            
            .container-box {
                padding: 15px;
            }
            
            .header .logo {
                width: 100px;
            }
            
            .header .company-name {
                font-size: 1.2rem;
            }
            
            h3 {
                font-size: 1.3rem;
            }
            
            .btn-responsive {
                width: 100%;
                margin-bottom: 10px;
                padding: 8px;
            }
            
            .navbar {
                padding: 8px 15px;
            }
            
            .card-header, .card-body, .card-footer {
                padding: 12px;
            }
        }
        
        @media (min-width: 768px) and (max-width: 991px) {
            .container-box {
                max-width: 90%;
            }
        }
        
        .footer {
            text-align: center;
            margin-top: 20px;
            padding: 10px;
            font-size: 0.9rem;
            color: #7f8c8d;
        }
        
        /* Style for nav buttons */
        .nav-buttons {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        @media (max-width: 576px) {
            .nav-buttons {
                flex-direction: column;
                gap: 10px;
            }
            
            .nav-buttons .btn {
                width: 100%;
            }
            
            .title-section {
                text-align: center;
                margin-bottom: 15px;
            }
        }
    </style>
</head>
<body>

<!-- Main Content -->
<div class="container">
    <div class="container-box">
        <div class="nav-buttons">
            <div class="title-section">
                <h3><i class="fas fa-id-card"></i> Edit Government IDs</h3>
            </div>
            <div>
                <a href="add_user.php" class="btn btn-secondary btn-responsive"><i class="fas fa-arrow-left"></i> Back</a>
            </div>
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
                    <div class="col-md-4 col-sm-12 mb-2">
                        <p class="mb-1"><strong>Employee ID:</strong></p>
                        <p><?= $employee['id_no']; ?></p>
                    </div>
                    <div class="col-md-4 col-sm-12 mb-2">
                        <p class="mb-1"><strong>Name:</strong></p>
                        <p><?= $employee['name']; ?></p>
                    </div>
                    <div class="col-md-4 col-sm-12 mb-2">
                        <p class="mb-1"><strong>Department:</strong></p>
                        <p><?= $employee['department']; ?></p>
                    </div>
                </div>
            </div>
        </div>

        <form action="" method="POST">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <strong>Government ID Information</strong>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-lg-6 col-md-12">
                            <div class="form-group mb-3">
                                <label for="sss_no" class="form-label">SSS Number</label>
                                <input type="text" class="form-control" id="sss_no" name="sss_no" value="<?= $employee['sss_no'] ?? ''; ?>" placeholder="Enter SSS Number">
                            </div>
                        </div>
                        <div class="col-lg-6 col-md-12">
                            <div class="form-group mb-3">
                                <label for="pagibig_no" class="form-label">Pag-IBIG Number</label>
                                <input type="text" class="form-control" id="pagibig_no" name="pagibig_no" value="<?= $employee['pagibig_no'] ?? ''; ?>" placeholder="Enter Pag-IBIG Number">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-6 col-md-12">
                            <div class="form-group mb-3">
                                <label for="tin_no" class="form-label">TIN Number</label>
                                <input type="text" class="form-control" id="tin_no" name="tin_no" value="<?= $employee['tin_no'] ?? ''; ?>" placeholder="Enter TIN Number">
                            </div>
                        </div>
                        <div class="col-lg-6 col-md-12">
                            <div class="form-group mb-3">
                                <label for="philhealth_no" class="form-label">PhilHealth Number</label>
                                <input type="text" class="form-control" id="philhealth_no" name="philhealth_no" value="<?= $employee['philhealth_no'] ?? ''; ?>" placeholder="Enter PhilHealth Number">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer text-center text-md-end">
                    <button type="submit" class="btn btn-primary btn-responsive"><i class="fas fa-save"></i> Save Changes</button>
                </div>
            </div>
        </form>
    </div>
    
    </div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>