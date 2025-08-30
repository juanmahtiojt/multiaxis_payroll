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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_no = trim($_POST['id_no']);
    $name = trim($_POST['name']);
    $department = trim($_POST['department']);
    $daily_rate = floatval($_POST['daily_rate']);
    
    if ($id_no && $name && $department && $daily_rate > 0) {
        $stmt = $conn->prepare("INSERT INTO multiaxis_payroll_system.daily_rate (id_no, name, department, daily_rate) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sssd", $id_no, $name, $department, $daily_rate);
        
        if ($stmt->execute()) {
            $message = "Employee added successfully!";
            $messageType = "success";

            // ✅ Add activity log here
            log_activity($conn, $_SESSION['user'], "Added employee: $name ($id_no)", "admin.php");
        } else {
            $message = "Error: " . $stmt->error;
            $messageType = "danger";
        }

        $stmt->close();
    } else {
        $message = "Please fill in all fields correctly.";
        $messageType = "warning";
    }
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
    <style>
        body {
            background-color: #f0f6fc; /* Original background color */
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
                <h1 class="card-title">Add New Employee</h1>
                <p class="card-subtitle">Create a new employee record in the payroll system</p>
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
                            <input type="text" class="form-control" id="id_no" name="id_no" required placeholder="Enter employee ID">
                        </div>
                        <div class="form-text">Unique identifier for the employee in the system</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="name" class="form-label">Full Name</label>
                        <div class="input-group has-icon">
                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                            <input type="text" class="form-control" id="name" name="name" required placeholder="Enter employee's full name">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="department" class="form-label">Department</label>
                        <div class="input-group has-icon">
                            <span class="input-group-text"><i class="fas fa-building"></i></span>
                            <input type="text" class="form-control" id="department" name="department" required placeholder="Enter department name">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="daily_rate" class="form-label">Daily Rate</label>
                        <div class="input-group has-icon">
                            <span class="input-group-text"><i class="fas fa-dollar-sign"></i></span>
                            <input type="number" step="0.01" min="0" class="form-control" id="daily_rate" name="daily_rate" required placeholder="0.00">
                        </div>
                        <div class="form-text">Daily salary rate in local currency</div>
                    </div>
                </form>
            </div>
            
            <div class="card-footer d-flex justify-content-between">
                <a href="add_user.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back
                </a>
                <button type="submit" form="addEmployeeForm" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Add Employee
                </button>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Form validation
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('addEmployeeForm');
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
    </script>
</body>
</html>