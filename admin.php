<?php 
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

$query = "SELECT * FROM user_logs ORDER BY timestamp DESC";
$result = mysqli_query($conn, $query);


// Optional: restrict access to admin only
if ($_SESSION['role'] !== 'admin') {
    echo "Access denied.";
    exit();
}

// Get user logs
$query = "SELECT * FROM user_logs ORDER BY timestamp DESC";
$result = mysqli_query($conn, $query);


// Check if user has admin access
if ($role !== 'admin') {
    header("Location: dashboard.php");
    exit();
}

// Process form submission for adding a new admin
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add new admin
    if (isset($_POST['add_admin'])) {
        $new_username = mysqli_real_escape_string($conn, $_POST['username']);
        $new_password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Basic validation
        if (empty($new_username) || empty($new_password) || empty($confirm_password)) {
            $message = "All fields are required";
            $messageType = "danger";
        } elseif ($new_password !== $confirm_password) {
            $message = "Passwords do not match";
            $messageType = "danger";
        } else {
            // Check if username exists
            $check_query = "SELECT * FROM users WHERE username = '$new_username'";
            $check_result = mysqli_query($conn, $check_query);
            
            if (mysqli_num_rows($check_result) > 0) {
                $message = "Username already exists";
                $messageType = "danger";
            } else {
                // Hash the password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                
                // Insert new admin
                $insert_query = "INSERT INTO users (username, password, role) 
                                VALUES ('$new_username', '$hashed_password', 'admin')";
                
                if (mysqli_query($conn, $insert_query)) {
                    $message = "New admin added successfully";
                    $messageType = "success";
                } else {
                    $message = "Error: " . mysqli_error($conn);
                    $messageType = "danger";
                }
            }
        }
    }
    
    // Process admin deletion
    if (isset($_POST['delete_admin'])) {
        $admin_id = mysqli_real_escape_string($conn, $_POST['admin_id']);
        
        // Prevent self-deletion
        $current_user_query = "SELECT id FROM users WHERE username = '$username'";
        $current_user_result = mysqli_query($conn, $current_user_query);
        $current_user = mysqli_fetch_assoc($current_user_result);
        
        if ($current_user['id'] == $admin_id) {
            $message = "You cannot delete your own account";
            $messageType = "danger";
        } else {
            $delete_query = "DELETE FROM users WHERE id = '$admin_id' AND role = 'admin'";
            
            if (mysqli_query($conn, $delete_query)) {
                $message = "Admin deleted successfully";
                $messageType = "success";
            } else {
                $message = "Error: " . mysqli_error($conn);
                $messageType = "danger";
            }
        }
    }
    
    // Add new regular user
    if (isset($_POST['add_user'])) {
        $new_username = mysqli_real_escape_string($conn, $_POST['username']);
        $new_password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Basic validation
        if (empty($new_username) || empty($new_password) || empty($confirm_password)) {
            $message = "All fields are required";
            $messageType = "danger";
        } elseif ($new_password !== $confirm_password) {
            $message = "Passwords do not match";
            $messageType = "danger";
        } else {
            // Check if username exists
            $check_query = "SELECT * FROM users WHERE username = '$new_username'";
            $check_result = mysqli_query($conn, $check_query);
            
            if (mysqli_num_rows($check_result) > 0) {
                $message = "Username already exists";
                $messageType = "danger";
            } else {
                // Hash the password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                
                // Insert new user
                $insert_query = "INSERT INTO users (username, password, role) 
                                VALUES ('$new_username', '$hashed_password', 'user')";
                
                if (mysqli_query($conn, $insert_query)) {
                    $message = "New user added successfully";
                    $messageType = "success";
                } else {
                    $message = "Error: " . mysqli_error($conn);
                    $messageType = "danger";
                }
            }
        }
    }
    
    // Reset user password
    if (isset($_POST['reset_password'])) {
        $user_id = mysqli_real_escape_string($conn, $_POST['user_id']);
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_new_password'];
        
        // Basic validation
        if (empty($new_password) || empty($confirm_password)) {
            $message = "All fields are required";
            $messageType = "danger";
        } elseif ($new_password !== $confirm_password) {
            $message = "Passwords do not match";
            $messageType = "danger";
        } else {
            // Hash the password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            
            // Update user password
            $update_query = "UPDATE users SET password = '$hashed_password' WHERE id = '$user_id'";
            
            if (mysqli_query($conn, $update_query)) {
                $message = "Password reset successfully";
                $messageType = "success";
            } else {
                $message = "Error: " . mysqli_error($conn);
                $messageType = "danger";
            }
        }
    }
}

// Fetch all admin users
$admin_query = "SELECT id, username, created_at FROM users WHERE role = 'admin' ORDER BY created_at DESC";
$admin_result = mysqli_query($conn, $admin_query);

// Fetch all regular users
$user_query = "SELECT id, username, created_at FROM users WHERE role = 'user' ORDER BY created_at DESC";
$user_result = mysqli_query($conn, $user_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
            overflow: hidden;
            box-shadow: 3px 0 10px rgba(0, 0, 0, 0.1);
            z-index: 1030;
        }
        .sidebar-header {
            padding: 20px 15px;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 10px;
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
            margin-bottom: 5px;
        }
        .nav-section-title {
            padding: 8px 20px;
            font-size: 12px;
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
            padding: 12px 20px;
            font-size: 15px;
            transition: all 0.2s;
            border-left: 3px solid transparent;
        }
        .sidebar a i {
            margin-right: 12px;
            width: 24px;
            text-align: center;
            font-size: 18px;
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
            margin-top: 10px;
        }

        /* Mobile Menu Toggle Button */
        .menu-toggle {
            display: none;
            position: fixed;
            top: 15px;
            right: 15px;
            z-index: 1050;
            background: #2c3e50;
            color: white;
            border: none;
            border-radius: 5px;
            padding: 8px 12px;
            font-size: 18px;
            cursor: pointer;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }

        /* Sidebar Overlay for Mobile */
        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0,0,0,0.5);
            z-index: 1025;
        }

        .main-content {
            margin-left: 270px;
            padding: 30px;
            width: calc(100% - 270px);
            overflow: hidden;
            height: 100vh;
            padding-top: 5px;
            transition: all 0.3s;
        }
        
        .card-custom {
            border-radius: 15px;
            box-shadow: 0 6px 15px rgba(0,0,0,0.1);
            margin-bottom: 25px;
        }
        
        .table-container {
            max-height: 500px;
            overflow-y: auto;
            position: relative;
        }
        
        .sticky-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }
        
        .sticky-table thead {
            position: sticky;
            top: 0;
            z-index: 10;
        }
        
        .sticky-table th {
            background-color: #343a40;
            color: white;
            position: sticky;
            top: 0;
            box-shadow: 0 2px 2px rgba(0,0,0,.1);
        }
        
        .content-wrapper {
            height: calc(100vh - 20px);
            overflow-y: auto;
            padding-right: 10px;
        }
        
        .password-container {
            position: relative;
        }
        
        .toggle-password {
            position: absolute;
            right: 10px;
            top: 10px;
            cursor: pointer;
        }
        
        .nav-tabs .nav-item .nav-link {
            color: #495057;
            font-weight: 500;
        }
        
        .nav-tabs .nav-item .nav-link.active {
            color: #0d6efd;
            border-color: #dee2e6 #dee2e6 #fff;
            font-weight: 600;
        }

        .card-header-flex {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        /* Media Queries for Responsiveness */
        @media (max-width: 991.98px) {
            /* Styles for tablets and smaller devices */
            .card-custom {
                border-radius: 12px;
                margin-bottom: 20px;
            }
            .table-container {
                max-height: 400px;
            }
            .content-wrapper {
                padding-right: 5px;
            }
        }

        @media (max-width: 767.98px) {
            /* Styles for mobile devices */
            .menu-toggle {
                display: block;
            }
            .sidebar {
                transform: translateX(-100%);
            }
            .sidebar.active {
                transform: translateX(0);
                overflow-y: auto;
            }
            .main-content {
                margin-left: 0;
                width: 100%;
                padding: 15px;
            }
            .content-wrapper {
                height: calc(100vh - 10px);
                padding-top: 40px; /* Space for the menu button */
            }
        }

        @media (max-width: 575.98px) {
            /* Styles for extra small devices */
            .main-content {
                padding: 10px;
            }
            .card-custom {
                border-radius: 10px;
                margin-bottom: 15px;
            }
            .table-container {
                max-height: 350px;
            }
        }

        /* Print media styles */
        @media print {
            .sidebar, .menu-toggle, .sidebar-overlay {
                display: none !important;
            }
            .main-content {
                margin-left: 0;
                width: 100%;
            }
            .card-custom {
                box-shadow: none;
                border: 1px solid #ddd;
            }
        }
    </style>
</head>
<body>
    
    <!-- Mobile Menu Toggle Button -->
    <button class="menu-toggle" id="menuToggle">
        <i class="fas fa-bars"></i>
    </button>
    
    <!-- Sidebar Overlay for Mobile -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <img src="my_project/images/MULTI-removebg-preview.png" class="sidebar-logo" alt="Company Logo">
            <div class="company-name">Multi Axis Handlers & Tech Inc</div>
        </div>
        
        <div class="nav-section">
            <div class="nav-section-title">Main Navigation</div>
            <a href="dashboard.php" class="<?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>">
                <i class="fas fa-home"></i> Dashboard
            </a>
            <?php if ($role === 'admin') : ?>
                <a href="add_user.php" class="<?php echo ($current_page == 'add_user.php') ? 'active' : ''; ?>">
                    <i class="fas fa-user-plus"></i> Employees
                </a>
                <a href="admin.php" class="<?php echo ($current_page == 'admin.php') ? 'active' : ''; ?>">
                    <i class="fas fa-users-cog"></i> User Management
                </a>
            <?php endif; ?>
            <a href="employee_attendance.php" class="<?php echo ($current_page == 'employee_attendance.php') ? 'active' : ''; ?>">
                <i class="fas fa-clipboard-check"></i> Attendance
            </a>
        </div>
        
        <div class="nav-section">
            <div class="nav-section-title">Payroll Management</div>
            <a href="reports.php" class="<?php echo ($current_page == 'reports.php') ? 'active' : ''; ?>">
                <i class="fas fa-chart-bar"></i> Deductions
            </a>
            <a href="attendance_summary_report.php" class="<?php echo ($current_page == 'attendance_summary_report.php') ? 'active' : ''; ?>">
                <i class="fas fa-clock"></i> Attendance Summary
            </a>
            <a href="view_payslips.php" class="<?= ($current_page == 'view_payslips.php') ? 'active' : '' ?>">
                <i class="fas fa-file-alt"></i> View Payslips
            </a>
        </div>
        
        <div class="nav-section">
            <a href="logout.php" class="<?php echo ($current_page == 'logout.php') ? 'active' : ''; ?>">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
        
        <div class="sidebar-footer">
            © <?php echo date('Y'); ?> Multi Axis Handlers & Tech Inc.
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="content-wrapper">
            <div class="container-fluid">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-users-cog me-2"></i>User Management</h2>
                </div>
                
                <?php if (!empty($message)) : ?>
                <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>
                
                <!-- Tabs for User/Admin Management -->
                <ul class="nav nav-tabs mb-4" id="userTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="admins-tab" data-bs-toggle="tab" data-bs-target="#admins" type="button" role="tab" aria-controls="admins" aria-selected="true">
                            <i class="fas fa-user-shield me-2"></i>Admins
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="users-tab" data-bs-toggle="tab" data-bs-target="#users" type="button" role="tab" aria-controls="users" aria-selected="false">
                            <i class="fas fa-users me-2"></i>Regular Users
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                    <button class="nav-link" id="logs-tab" data-bs-toggle="tab" data-bs-target="#logs" type="button" role="tab" aria-controls="logs" aria-selected="false">
                        <i class="fas fa-history me-2"></i>User Logs
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="activity-tab" data-bs-toggle="tab" data-bs-target="#activity" type="button" role="tab" aria-controls="activity" aria-selected="false">
                        <i class="fas fa-clipboard-list me-2"></i>Activity Logs
                    </button>
                </li>


                </ul>
                
                <!-- Tab Content -->
                <div class="tab-content" id="userTabsContent">
                    <!-- Admins Tab -->
                    <div class="tab-pane fade show active" id="admins" role="tabpanel" aria-labelledby="admins-tab">
                        <!-- Admin List -->
                        <div class="card card-custom">
                            <div class="card-header bg-primary text-white card-header-flex">
                                <h5 class="mb-0"><i class="fas fa-user-shield me-2"></i>Admin Users</h5>
                                <button type="button" class="btn btn-light" data-bs-toggle="modal" data-bs-target="#addAdminModal">
                                    <i class="fas fa-user-shield me-2"></i>Add New Admin
                                </button>
                            </div>
                            <div class="card-body">
                                <div class="table-container">
                                    <table class="table table-hover sticky-table">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Username</th>
                                                <th>Created At</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            if (mysqli_num_rows($admin_result) > 0) {
                                                while ($row = mysqli_fetch_assoc($admin_result)) {
                                                    // Get current user's ID
                                                    $is_current_user = ($row['username'] == $username);
                                            ?>
                                            <tr>
                                                <td><?php echo $row['id']; ?></td>
                                                <td><?php echo $row['username']; ?><?php echo $is_current_user ? ' (You)' : ''; ?></td>
                                                <td><?php echo date('M d, Y h:i A', strtotime($row['created_at'])); ?></td>
                                                <td>
                                                    <button type="button" class="btn btn-sm btn-warning me-1" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#resetPasswordModal" 
                                                            data-user-id="<?php echo $row['id']; ?>"
                                                            data-username="<?php echo $row['username']; ?>">
                                                        <i class="fas fa-key"></i> Reset Password
                                                    </button>
                                                    
                                                    <?php if (!$is_current_user) : ?>
                                                    <form method="post" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this admin?');">
                                                        <input type="hidden" name="admin_id" value="<?php echo $row['id']; ?>">
                                                        <button type="submit" name="delete_admin" class="btn btn-sm btn-danger">
                                                            <i class="fas fa-trash-alt"></i> Delete
                                                        </button>
                                                    </form>
                                                    <?php else: ?>
                                                    <span class="badge bg-secondary">Current User</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                            <?php
                                                }
                                            } else {
                                            ?>
                                            <tr>
                                                <td colspan="4" class="text-center">No admin users found</td>
                                            </tr>
                                            <?php
                                            }
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- User Logs Tab -->
<div class="tab-pane fade" id="logs" role="tabpanel" aria-labelledby="logs-tab">
    <!-- User Logs List -->
    <div class="card card-custom">
        <div class="card-header bg-secondary text-white card-header-flex">
            <h5 class="mb-0"><i class="fas fa-history me-2"></i>User Activity Logs</h5>
        </div>
        <div class="card-body">
            <div class="table-container">
                <table class="table table-hover sticky-table">
                    <thead>
                        <tr>
                            <th style="width: 50px;">ID</th>
                            <th>Username</th>
                            <th>Activity</th>
                            <th>Timestamp</th>
                            <th>IP Address</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $logs_query = "SELECT * FROM user_logs ORDER BY timestamp DESC";
                        $logs_result = mysqli_query($conn, $logs_query);

                        if (mysqli_num_rows($logs_result) > 0) {
                            while ($log = mysqli_fetch_assoc($logs_result)) {
                        ?>
                        <tr>
                            <td><?php echo $log['id']; ?></td>
                            <td><?php echo htmlspecialchars($log['username']); ?></td>
                            <td><?php echo htmlspecialchars($log['activity_type']); ?></td>
                            <td><?php echo date('M d, Y h:i A', strtotime($log['timestamp'])); ?></td>
                            <td><?php echo htmlspecialchars($log['ip_address']); ?></td>
                        </tr>
                        <?php
                            }
                        } else {
                        ?>
                        <tr>
                            <td colspan="5" class="text-center">No user activity logs found.</td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<div class="tab-pane fade" id="activity" role="tabpanel" aria-labelledby="activity-tab">
    <?php include "activity_log.php"; ?>
</div>


                    
                    <!-- Regular Users Tab -->
                    <div class="tab-pane fade" id="users" role="tabpanel" aria-labelledby="users-tab">
                        <!-- User List -->
                        <div class="card card-custom">
                            <div class="card-header bg-info text-white card-header-flex">
                                <h5 class="mb-0"><i class="fas fa-users me-2"></i>Regular Users</h5>
                                <button type="button" class="btn btn-light" data-bs-toggle="modal" data-bs-target="#addUserModal">
                                    <i class="fas fa-user-plus me-2"></i>Add New User
                                </button>
                            </div>
                            <div class="card-body">
                                <div class="table-container">
                                    <table class="table table-hover sticky-table">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Username</th>
                                                <th>Created At</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            if (mysqli_num_rows($user_result) > 0) {
                                                while ($row = mysqli_fetch_assoc($user_result)) {
                                            ?>
                                            <tr>
                                                <td><?php echo $row['id']; ?></td>
                                                <td><?php echo $row['username']; ?></td>
                                                <td><?php echo date('M d, Y h:i A', strtotime($row['created_at'])); ?></td>
                                                <td>
                                                    <button type="button" class="btn btn-sm btn-warning me-1" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#resetPasswordModal" 
                                                            data-user-id="<?php echo $row['id']; ?>"
                                                            data-username="<?php echo $row['username']; ?>">
                                                        <i class="fas fa-key"></i> Reset Password
                                                    </button>
                                                    
                                                    <form method="post" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this user?');">
                                                        <input type="hidden" name="admin_id" value="<?php echo $row['id']; ?>">
                                                        <button type="submit" name="delete_admin" class="btn btn-sm btn-danger">
                                                            <i class="fas fa-trash-alt"></i> Delete
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                            <?php
                                                }
                                            } else {
                                            ?>
                                            <tr>
                                                <td colspan="4" class="text-center">No regular users found</td>
                                            </tr>
                                            <?php
                                            }
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    
    
    <!-- Add Admin Modal -->
    <div class="modal fade" id="addAdminModal" tabindex="-1" aria-labelledby="addAdminModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="addAdminModalLabel"><i class="fas fa-user-shield me-2"></i>Add New Admin</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="post" action="" id="addAdminForm">
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <div class="password-container">
                                <input type="password" class="form-control" id="password" name="password" required>
                                <i class="toggle-password fas fa-eye" onclick="togglePasswordVisibility('password')"></i>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm Password</label>
                            <div class="password-container">
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                <i class="toggle-password fas fa-eye" onclick="togglePasswordVisibility('confirm_password')"></i>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" form="addAdminForm" name="add_admin" class="btn btn-success">
                        <i class="fas fa-plus-circle me-2"></i>Add Admin
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Add User Modal -->
    <div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="addUserModalLabel"><i class="fas fa-user-plus me-2"></i>Add New User</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="post" action="" id="addUserForm">
                        <div class="mb-3">
                            <label for="user_username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="user_username" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label for="user_password" class="form-label">Password</label>
                            <div class="password-container">
                                <input type="password" class="form-control" id="user_password" name="password" required>
                                <i class="toggle-password fas fa-eye" onclick="togglePasswordVisibility('user_password')"></i>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="user_confirm_password" class="form-label">Confirm Password</label>
                            <div class="password-container">
                                <input type="password" class="form-control" id="user_confirm_password" name="confirm_password" required>
                                <i class="toggle-password fas fa-eye" onclick="togglePasswordVisibility('user_confirm_password')"></i>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" form="addUserForm" name="add_user" class="btn btn-primary">
                        <i class="fas fa-plus-circle me-2"></i>Add User
                    </button>
                </div>
            </div>
        </div>
    </div>
     <div class="tab-content" id="userTabsContent">
    
    <!-- Reset Password Modal -->
    <div class="modal fade" id="resetPasswordModal" tabindex="-1" aria-labelledby="resetPasswordModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title" id="resetPasswordModalLabel"><i class="fas fa-key me-2"></i>Reset Password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="post" action="" id="resetPasswordForm">
                        <input type="hidden" id="reset_user_id" name="user_id">
                        <p>Reset password for user: <strong id="reset_username"></strong></p>
                        <div class="mb-3">
                            <label for="new_password" class="form-label">New Password</label>
                            <div class="password-container">
                                <input type="password" class="form-control" id="new_password" name="new_password" required>
                                <i class="toggle-password fas fa-eye" onclick="togglePasswordVisibility('new_password')"></i>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="confirm_new_password" class="form-label">Confirm New Password</label>
                            <div class="password-container">
                                <input type="password" class="form-control" id="confirm_new_password" name="confirm_new_password" required>
                                <i class="toggle-password fas fa-eye" onclick="togglePasswordVisibility('confirm_new_password')"></i>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" form="resetPasswordForm" name="reset_password" class="btn btn-warning">
                        <i class="fas fa-key me-2"></i>Reset Password
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function togglePasswordVisibility(inputId) {
            const input = document.getElementById(inputId);
            const icon = input.nextElementSibling;
            
            if (input.type === "password") {
                input.type = "text";
                icon.classList.remove("fa-eye");
                icon.classList.add("fa-eye-slash");
            } else {
                input.type = "password";
                icon.classList.remove("fa-eye-slash");
                icon.classList.add("fa-eye");
            }
        }

        // Reset Password Modal
        document.addEventListener('DOMContentLoaded', function() {
            // Setup the reset password modal
            const resetPasswordModal = document.getElementById('resetPasswordModal');
            if (resetPasswordModal) {
                resetPasswordModal.addEventListener('show.bs.modal', function(event) {
                    // Button that triggered the modal
                    const button = event.relatedTarget;
                    
                    // Extract user info from data attributes
                    const userId = button.getAttribute('data-user-id');
                    const username = button.getAttribute('data-username');
                    
                    // Update the modal content
                    document.getElementById('reset_user_id').value = userId;
                    document.getElementById('reset_username').textContent = username;
                });
            }
            
            // Sidebar toggle functionality for mobile view
            const menuToggle = document.getElementById('menuToggle');
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            
            if (menuToggle && sidebar && overlay) {
                menuToggle.addEventListener('click', function() {
                    sidebar.classList.toggle('active');
                    if (sidebar.classList.contains('active')) {
                        overlay.style.display = 'block';
                    } else {
                        overlay.style.display = 'none';
                    }
                });
                
                overlay.addEventListener('click', function() {
                    sidebar.classList.remove('active');
                    overlay.style.display = 'none';
                });
                
                // Close sidebar on window resize if in mobile view
                window.addEventListener('resize', function() {
                    if (window.innerWidth > 768) {
                        sidebar.classList.remove('active');
                        overlay.style.display = 'none';
                    }
                });
                
                // H\andle sidebar links in mobile view
                const sidebarLinks = document.querySelectorAll('.sidebar a');
                sidebarLinks.forEach(link => {
                    link.addEventListener('click', function() {
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