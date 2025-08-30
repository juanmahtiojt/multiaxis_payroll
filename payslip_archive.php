<?php
include_once "functions.php";
session_start();
include __DIR__ . "/config.php";

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['user'];
$role = $_SESSION['role'];
$current_page = basename($_SERVER['PHP_SELF']);

// Handle archive actions
if (isset($_POST['action'])) {
    $username = $_SESSION['user'];
    $page = basename(__FILE__);
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown IP';
    $timestamp = date('Y-m-d H:i:s');
    
    if ($_POST['action'] == 'archive' && isset($_POST['payslip_ids'])) {
        $payslip_ids = $_POST['payslip_ids'];
        $count = count($payslip_ids);
        
        // Log the archive action
        $activity = "Archived $count payslip(s)";
        $stmt = $conn->prepare("INSERT INTO activity_logs (username, activity, page, ip_address, timestamp) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $username, $activity, $page, $ip_address, $timestamp);
        $stmt->execute();
        $stmt->close();
        
        foreach ($payslip_ids as $id) {
            $stmt = $conn->prepare("UPDATE payroll_records SET archived = 1 WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
        }
    } elseif ($_POST['action'] == 'restore' && isset($_POST['payslip_ids'])) {
        $payslip_ids = $_POST['payslip_ids'];
        $count = count($payslip_ids);
        
        // Log the restore action
        $activity = "Restored $count payslip(s)";
        $stmt = $conn->prepare("INSERT INTO activity_logs (username, activity, page, ip_address, timestamp) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $username, $activity, $page, $ip_address, $timestamp);
        $stmt->execute();
        $stmt->close();
        
        foreach ($payslip_ids as $id) {
            $stmt = $conn->prepare("UPDATE payroll_records SET archived = 0 WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
        }
    } elseif ($_POST['action'] == 'delete' && isset($_POST['payslip_ids'])) {
        $payslip_ids = $_POST['payslip_ids'];
        $count = count($payslip_ids);
        
        // Log the delete action
        $activity = "Deleted $count payslip(s) permanently";
        $stmt = $conn->prepare("INSERT INTO activity_logs (username, activity, page, ip_address, timestamp) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $username, $activity, $page, $ip_address, $timestamp);
        $stmt->execute();
        $stmt->close();
        
        foreach ($payslip_ids as $id) {
            $stmt = $conn->prepare("DELETE FROM payroll_records WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
        }
    } elseif ($_POST['action'] == 'log_print' && isset($_POST['activity'])) {
        // Log print actions
        $activity = $_POST['activity'];
        $stmt = $conn->prepare("INSERT INTO activity_logs (username, activity, page, ip_address, timestamp) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $username, $activity, $page, $ip_address, $timestamp);
        $stmt->execute();
        $stmt->close();
        
        // Return success response for AJAX
        http_response_code(200);
        exit();
    }
}

// Fetch archived payslips grouped by generation date
$archive_query = "SELECT 
                    DATE(COALESCE(pr.created_ats, pr.created_at)) as generation_date,
                    DATE_FORMAT(DATE(COALESCE(pr.created_ats, pr.created_at)), '%Y-%m-%d') as formatted_date,
                    COUNT(*) as payslip_count,
                    GROUP_CONCAT(pr.id) as payslip_ids
                  FROM payroll_records pr 
                  WHERE pr.archived = 1 
                  GROUP BY DATE(COALESCE(pr.created_ats, pr.created_at)), DATE_FORMAT(DATE(COALESCE(pr.created_ats, pr.created_at)), '%Y-%m-%d')
                  ORDER BY DATE(COALESCE(pr.created_ats, pr.created_at)) DESC";

$archive_result = mysqli_query($conn, $archive_query);


// Fetch active payslips grouped by generation date
$active_query = "SELECT 
                    DATE(COALESCE(pr.created_ats, pr.created_at)) as generation_date,
                    DATE_FORMAT(DATE(COALESCE(pr.created_ats, pr.created_at)), '%Y-%m-%d') as formatted_date,
                    COUNT(*) as payslip_count,
                    GROUP_CONCAT(pr.id) as payslip_ids
                  FROM payroll_records pr 
                  WHERE pr.archived = 0 
                  GROUP BY DATE(COALESCE(pr.created_ats, pr.created_at)), DATE_FORMAT(DATE(COALESCE(pr.created_ats, pr.created_at)), '%Y-%m-%d')
                  ORDER BY DATE(COALESCE(pr.created_ats, pr.created_at)) DESC";

$active_result = mysqli_query($conn, $active_query);

// Function to get payslips for a specific date
function getPayslipsByDate($conn, $date, $archived = 0) {
    $query = "SELECT pr.*, 
              DATE_FORMAT(COALESCE(pr.created_ats, pr.created_at), '%Y-%m-%d %H:%i') as formatted_date 
              FROM payroll_records pr 
              WHERE DATE(COALESCE(pr.created_ats, pr.created_at)) = ? AND pr.archived = ?
              ORDER BY pr.name ASC";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("si", $date, $archived);
    $stmt->execute();
    return $stmt->get_result();
}


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payslip Archive - Multi Axis Handlers & Tech Inc</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background-color: #d6eaf8;
            margin: 0;
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
        .main-content {
            margin-left: 270px;
            padding: 30px;
            transition: all 0.3s;
            box-sizing: border-box;
            overflow: hidden;
            min-height: 100vh;
        }

        .archive-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            overflow: hidden;
        }

        .archive-header {
            background: #2c3e50;
            color: white;
            padding: 15px 20px;
            font-size: 18px;
            font-weight: 600;
        }

        .archive-body {
            padding: 20px;
        }

        .payslip-card {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            background: #f8f9fa;
            transition: all 0.3s;
        }

        .payslip-card:hover {
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }

        .payslip-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 10px;
            margin-bottom: 10px;
        }

        .info-item {
            font-size: 14px;
        }

        .info-item strong {
            color: #2c3e50;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn-sm {
            padding: 5px 10px;
            font-size: 12px;
        }

        .empty-state {
            text-align: center;
            padding: 40px;
            color: #6c757d;
        }

        .empty-state i {
            font-size: 48px;
            margin-bottom: 15px;
            color: #dee2e6;
        }

        .stats-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }

        .stat-item {
            text-align: center;
            padding: 15px;
            border-radius: 8px;
            background: #f8f9fa;
        }

        .stat-number {
            font-size: 24px;
            font-weight: bold;
            color: #2c3e50;
        }

        .stat-label {
            font-size: 14px;
            color: #6c757d;
        }

        .filter-section {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
        }

        .checkbox-group input[type="checkbox"] {
            margin-right: 5px;
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 15px;
            }
            
            .sidebar {
                transform: translateX(-100%);
            }
            
            .sidebar.active {
                transform: translateX(0);
            }
        }
    </style>
</head>
<body>

<!-- Sidebar Overlay for Mobile -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- Mobile Menu Toggle Button -->
<button class="menu-toggle" id="menuToggle">
    <i class="fas fa-bars"></i>
</button>

<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <img src="my_project/images/MULTI-removebg-preview.png" class="sidebar-logo" alt="Company Logo">
        <div class="company-name">Multi Axis Handlers & Tech Inc</div>
    </div>
    
<div class="nav-section">
    <div class="nav-section-title">Main Navigation</div>
    <?php if ($role === 'admin') : ?>
        <a href="dashboard.php" class="<?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>">
            <i class="fas fa-home"></i> Dashboard
        </a>
        <a href="add_user.php" class="<?php echo ($current_page == 'add_user.php') ? 'active' : ''; ?>">
            <i class="fas fa-user-plus"></i> Employees
        </a>
    <?php endif; ?>

    <div class="attendance-group">
        <div class="nav-section-title">
            <i class="fas fa-clipboard-check"></i> Attendance
        </div>
        <a href="employee_attendance_monthly.php" class="<?php echo ($current_page == 'employee_attendance_monthly.php') ? 'active' : ''; ?>">
            Monthly Attendance
        </a>
        <a href="employee_attendance.php" class="<?php echo ($current_page == 'employee_attendance.php') ? 'active' : ''; ?>">
            Weekly Attendance
        </a>
    </div>
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
        <a href="payslip_archive_complete.php" class="<?= ($current_page == 'payslip_archive_complete.php') ? 'active' : '' ?>">
            <i class="fas fa-archive"></i> Payslip Archive
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

<div class="main-content">
    <div class="container-fluid">
        <h1 class="mb-4">Payslip Archive</h1>
        
        <!-- Statistics -->
        <div class="stats-card">
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-number">
                        <?php 
                        $total_active = 0;
                        mysqli_data_seek($active_result, 0);
                        while($row = mysqli_fetch_assoc($active_result)) {
                            $total_active += $row['payslip_count'];
                        }
                        mysqli_data_seek($active_result, 0);
                        echo $total_active;
                        ?>
                    </div>
                    <div class="stat-label">Active Payslips</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">
                        <?php 
                        $total_archived = 0;
                        mysqli_data_seek($archive_result, 0);
                        while($row = mysqli_fetch_assoc($archive_result)) {
                            $total_archived += $row['payslip_count'];
                        }
                        mysqli_data_seek($archive_result, 0);
                        echo $total_archived;
                        ?>
                    </div>
                    <div class="stat-label">Archived Payslips</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php echo $total_active + $total_archived; ?></div>
                    <div class="stat-label">Total Payslips</div>
                </div>
            </div>
        </div>

        <!-- Active Payslips Section -->
        <div class="archive-card">
            <div class="archive-header">
                <i class="fas fa-file-alt"></i> Active Payslips by Generation Date
            </div>
            <div class="archive-body">
                <?php if (mysqli_num_rows($active_result) > 0): ?>
                 <?php while ($date_group = mysqli_fetch_assoc($active_result)): ?>
                    <?php 
                        // Define safe formatted date for group
                        $formatted_date = !empty($date_group['generation_date']) 
                            ? date('F j, Y', strtotime($date_group['generation_date'])) 
                            : "N/A";
                    ?>
                    <div class="mb-4">
                        <h5 class="text-primary mb-3">
                            <i class="fas fa-calendar-alt"></i> 
                            Generated on: <?= $formatted_date ?>
                            <span class="badge bg-secondary ms-2"><?= $date_group['payslip_count']; ?> payslips</span>
                        </h5>
                        
                        <form method="POST" action="">
                            <input type="hidden" name="action" value="archive">
                            <div class="mb-3">
                                <div class="checkbox-group mb-2">
                                    <input type="checkbox" 
                                        id="selectAllActive<?= $date_group['generation_date']; ?>" 
                                        onchange="selectAll(this, 'payslip_ids[]')">
                                    <label for="selectAllActive<?= $date_group['generation_date']; ?>" class="fw-bold">
                                        <i class="fas fa-check-square"></i> Select All for This Date
                                    </label>
                                </div>
                                <button type="submit" 
                                        class="btn btn-primary btn-sm" 
                                        onclick="return confirm('Archive all payslips for <?= $formatted_date ?>?')">
                                    <i class="fas fa-archive"></i> Archive All for This Date
                                </button>
                                <a href="generate_archive_payslip_pdf.php?generation_date=<?= $date_group['generation_date']; ?>&archived=0" 
                                class="btn btn-success btn-sm" target="_blank" 
                                onclick="logPrintAction('batch', '<?= $date_group['generation_date']; ?>', <?= $date_group['payslip_count']; ?>)">
                                    <i class="fas fa-file-pdf"></i> Generate PDF Report
                                </a>
                            </div>
                            
                            <div class="row">
                                <?php 
                                $payslips_for_date = getPayslipsByDate($conn, $date_group['generation_date'], 0);
                                while ($payslip = mysqli_fetch_assoc($payslips_for_date)): 
                                ?>
                                    <div class="col-md-6 col-lg-4 mb-3">
                                        <div class="payslip-card">
                                            <div class="checkbox-group">
                                                <input type="checkbox" name="payslip_ids[]" value="<?= $payslip['id']; ?>">
                                                <label>Select <?= htmlspecialchars($payslip['name']); ?></label>
                                            </div>
                                            
                                            <div class="payslip-info">
                                                <div class="info-item">
                                                    <strong>Employee:</strong> <?= htmlspecialchars($payslip['name']); ?>
                                                </div>
                                                <div class="info-item">
                                                    <strong>Period:</strong> <?= ucfirst(htmlspecialchars($payslip['pay_period'])); ?>
                                                </div>
                                                <div class="info-item">
                                                    <strong>Date Range:</strong> 
                                                    <?= date('M d, Y', strtotime($payslip['start_date'])); ?> - 
                                                    <?= date('M d, Y', strtotime($payslip['end_date'])); ?>
                                                </div>
                                                <div class="info-item">
                                                    <strong>Net Pay:</strong> ₱<?= number_format(
                                                        $payslip['basic_salary'] 
                                                        - ($payslip['sss_premium'] 
                                                        + $payslip['sss_loan'] 
                                                        + $payslip['pagibig_premium'] 
                                                        + $payslip['pagibig_loan'] 
                                                        + $payslip['philhealth'] 
                                                        + $payslip['cash_advance']), 
                                                        2
                                                    ); ?>
                                                </div>
                                                <div class="info-item">
                                                    <strong>Created:</strong> <?= $payslip['formatted_date']; ?>
                                                </div>
                                            </div>
                                            
                                            <div class="action-buttons">
                                                <a href="generate_individual_payslip_pdf.php?id=<?= $payslip['id']; ?>" 
                                                class="btn btn-outline-success btn-sm" target="_blank">
                                                    <i class="fas fa-file-pdf"></i> Individual PDF
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        </form>
                    </div>
                <?php endwhile; ?>

                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-folder-open"></i>
                        <p>No active payslips to archive</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Archived Payslips Section -->
        <div class="archive-card">
            <div class="archive-header">
                <i class="fas fa-archive"></i> Archived Payslips by Generation Date
            </div>
            <div class="archive-body">
                <?php if (mysqli_num_rows($archive_result) > 0): ?>
                    <?php while ($date_group = mysqli_fetch_assoc($archive_result)): ?>
                        <div class="mb-4">
                            <h5 class="text-primary mb-3">
                                <i class="fas fa-calendar-alt"></i> 
                                Generated on: <?php echo date('F j, Y', strtotime($date_group['generation_date'])); ?>
                                <span class="badge bg-secondary ms-2"><?php echo $date_group['payslip_count']; ?> payslips</span>
                            </h5>
                            
                            <form method="POST" action="">
                                <div class="mb-3">
                                    <button type="submit" name="action" value="restore" class="btn btn-warning btn-sm" onclick="return confirm('Restore payslips for <?php echo $date_group['formatted_date']; ?>?')">
                                        <i class="fas fa-undo"></i> Restore / Restore All
                                    </button>
                                    <button type="submit" name="action" value="delete" class="btn btn-danger btn-sm" onclick="return confirm('Permanently delete all payslips for <?php echo $date_group['formatted_date']; ?>?')">
                                        <i class="fas fa-trash"></i> Delete All for This Date
                                    </button>
                                    <a href="generate_archive_payslip_pdf.php?generation_date=<?php echo $date_group['generation_date']; ?>&archived=1" 
                                       class="btn btn-success btn-sm" target="_blank" onclick="logPrintAction('batch', '<?php echo $date_group['generation_date']; ?>', <?php echo $date_group['payslip_count']; ?>)">
                                        <i class="fas fa-file-pdf"></i> Generate PDF Report
                                    </a>
                                </div>
                                
                                <div class="row">
                                    <?php 
                                    $payslips_for_date = getPayslipsByDate($conn, $date_group['generation_date'], 1);
                                    while ($payslip = mysqli_fetch_assoc($payslips_for_date)): 
                                    ?>
                                        <div class="col-md-6 col-lg-4 mb-3">
                                            <div class="payslip-card">
                                                <div class="checkbox-group">
                                                    <input type="checkbox" name="payslip_ids[]" value="<?php echo $payslip['id']; ?>">
                                                    <label>Select <?php echo htmlspecialchars($payslip['name']); ?></label>
                                                </div>
                                                
                                                <div class="payslip-info">
                                                    <div class="info-item">
                                                        <strong>Employee:</strong> <?php echo htmlspecialchars($payslip['name']); ?>
                                                    </div>
                                                    <div class="info-item">
                                                        <strong>Period:</strong> <?php echo ucfirst(htmlspecialchars($payslip['pay_period'])); ?>
                                                    </div>
                                                    <div class="info-item">
                                                        <strong>Date Range:</strong> <?php echo date('M d, Y', strtotime($payslip['start_date'])); ?> - <?php echo date('M d, Y', strtotime($payslip['end_date'])); ?>
                                                    </div>
                                                    <div class="info-item">
                                                        <strong>Net Pay:</strong> ₱<?php echo number_format($payslip['basic_salary'] - ($payslip['sss_premium'] + $payslip['sss_loan'] + $payslip['pagibig_premium'] + $payslip['pagibig_loan'] + $payslip['philhealth'] + $payslip['cash_advance']), 2); ?>
                                                    </div>
                                                    <div class="info-item">
                                                        <strong>Archived:</strong> <?php echo $payslip['formatted_date']; ?>
                                                    </div>
                                                </div>
                                                
                                                <div class="action-buttons">
                                                    <a href="generate_individual_payslip_pdf.php?id=<?php echo $payslip['id']; ?>" 
                                                       class="btn btn-outline-success btn-sm" target="_blank">
                                                        <i class="fas fa-file-pdf"></i> Individual PDF
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endwhile; ?>
                                </div>
                            </form>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-archive"></i>
                        <p>No archived payslips found</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Select all checkboxes
    function selectAll(source, name) {
        checkboxes = document.getElementsByName(name);
        for(var i=0, n=checkboxes.length;i<n;i++) {
            checkboxes[i].checked = source.checked;
        }
    }

    // Log print actions
    function logPrintAction(type, identifier, count) {
        const username = '<?php echo $_SESSION['user']; ?>';
        const page = '<?php echo basename(__FILE__); ?>';
        const ip_address = '<?php echo $_SERVER['REMOTE_ADDR'] ?? 'Unknown IP'; ?>';
        
        let activity = '';
        if (type === 'batch') {
            activity = `Generated batch PDF report for ${count} payslip(s) dated ${identifier}`;
        } else if (type === 'individual') {
            activity = `Generated individual PDF payslip for employee: ${identifier}`;
        }
        
        // Send AJAX request to log the print action
        fetch('payslip_archive.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=log_print&activity=${encodeURIComponent(activity)}`
        });
    }

    // Sidebar toggle functionality for mobile view
    document.addEventListener('DOMContentLoaded', function() {
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
            
            window.addEventListener('resize', function() {
                if (window.innerWidth > 768) {
                    sidebar.classList.remove('active');
                    overlay.style.display = 'none';
                }
            });
        }
    });
</script>

</body>
</html>
<?php
// Close database connection
mysqli_close($conn);
?>
