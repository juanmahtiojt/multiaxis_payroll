<?php 
session_start();
include __DIR__ . "/config.php";

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['user'];
$role = $_SESSION['role'];
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Help & Support - Multi Axis Payroll System</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            background-color: #d6eaf8;
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
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
            width: calc(100% - 270px);
            transition: all 0.3s;
            box-sizing: border-box;
            min-height: 100vh;
            background-color: #d6eaf8;
            overflow-y: auto;
        }
        
        .back-button {
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1000;
            background: #5dade2;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            text-decoration: none;
            display: inline-block;
        }
        
        .back-button:hover {
            background: #3498db;
            text-decoration: none;
            color: white;
        }
        
        .help-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            padding: 40px;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .help-header {
            text-align: center;
            margin-bottom: 40px;
            padding-bottom: 30px;
            border-bottom: 2px solid #e9ecef;
        }
        
        .help-section {
            margin-bottom: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
            border-top: 4px solid #5dade2;
        }
        
        .help-section h3 {
            color: #2c3e50;
            margin-bottom: 15px;
        }
        
        .help-card {
            background: white;
            border: 1px solid #e9ecef;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .help-icon {
            font-size: 2rem;
            color: #5dade2;
            margin-bottom: 10px;
        }
        
        .step-list {
            counter-reset: step-counter;
        }
        
        .step-item {
            counter-increment: step-counter;
            margin-bottom: 15px;
            padding: 15px;
            background: white;
            border-radius: 8px;
            border-left: 3px solid #28a745;
        }
        
        .step-item::before {
            content: counter(step-counter);
            background: #28a745;
            color: white;
            border-radius: 50%;
            width: 25px;
            height: 25px;
            display: inline-block;
            text-align: center;
            margin-right: 10px;
            font-weight: bold;
        }
        
        .faq-item {
            margin-bottom: 20px;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            overflow: hidden;
        }
        
        .faq-question {
            background: #f8f9fa;
            padding: 15px;
            cursor: pointer;
            font-weight: bold;
        }
        
        .faq-answer {
            padding: 15px;
            background: white;
        }
        
        .video-placeholder {
            background: #f8f9fa;
            border: 2px dashed #dee2e6;
            border-radius: 10px;
            padding: 40px;
            text-align: center;
            margin: 20px 0;
        }
        
        .contact-support {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 15px;
            text-align: center;
            margin-top: 40px;
        }
        
        @media (max-width: 768px) {
            .main-content {
                width: 100%;
                padding: 15px;
            }
            
            .back-button {
                top: 10px;
                left: 10px;
                padding: 8px 15px;
                font-size: 14px;
            }
        }
    </style>
</head>
<body>

  <!-- Mobile Menu Toggle Button -->
    <button class="menu-toggle" id="menuToggle">
        <i class="fas fa-bars"></i>
    </button>

    
    <!-- Improved Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <img src="my_project\images\MULTI-removebg-preview.png" class="sidebar-logo" alt="Company Logo">
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
            <?php endif; ?>
            <a href="employee_attendance_monthly.php" class="<?php echo ($current_page == 'employee_attendance_monthly.php') ? 'active' : ''; ?>">
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
            <a href="about.php" class="<?php echo ($current_page == 'about.php') ? 'active' : ''; ?>">
                <i class="fas fa-info-circle"></i> About
            </a>
              <a href="help.php" class="<?php echo ($current_page == 'help.php') ? 'active' : ''; ?>">
                <i class="fas fa-question-circle"></i> Help & Support
            </a>
            <a href="logout.php" class="<?php echo ($current_page == 'logout.php') ? 'active' : ''; ?>">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
        
        <div class="sidebar-footer">
            © <?php echo date('Y'); ?> Multi Axis Handlers & Tech Inc.
        </div>
    </div>

    <div class="main-content">
        <div class="help-container">
            <div class="help-header">
                <h1><i class="fas fa-life-ring"></i> Help & Support Center</h1>
                <p>Everything you need to know about using the Multi Axis Payroll System</p>
            </div>

            <!-- Quick Start Guide -->
            <div class="help-section">
                <h3><i class="fas fa-rocket"></i> Quick Start Guide</h3>
                <div class="step-list">
                    <div class="step-item">
                        <strong>Login:</strong> Use your username and password to access the system
                    </div>
                    <div class="step-item">
                        <strong>Dashboard:</strong> Your starting point - shows all available options
                    </div>
                    <div class="step-item">
                        <strong>Navigation:</strong> Use the sidebar menu to move between different sections
                    </div>
                    <div class="step-item">
                        <strong>Save Often:</strong> Always click "Save" after making changes
                    </div>
                </div>
            </div>

            <!-- Employee Management -->
            <div class="help-section">
                <h3><i class="fas fa-users"></i> Employee Management</h3>
                <div class="row">
                    <div class="col-md-6">
                        <div class="help-card">
                            <div class="help-icon"><i class="fas fa-user-plus"></i></div>
                            <h5>Adding New Employees</h5>
                            <ol>
                                <li>Click "Employees" in the sidebar</li>
                                <li>Click "Add Employee"</li>
                                <li>Fill in all required fields (marked with *)</li>
                                <li>Click "Save Employee"</li>
                            </ol>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="help-card">
                            <div class="help-icon"><i class="fas fa-edit"></i></div>
                            <h5>Editing Employee Information</h5>
                            <ol>
                                <li>Go to "Employees" section</li>
                                <li>Find the employee using search</li>
                                <li>Click "Edit" next to their name</li>
                                <li>Make changes and click "Update"</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Attendance Tracking -->
            <div class="help-section">
                <h3><i class="fas fa-clock"></i> Attendance Tracking</h3>
                <div class="row">
                    <div class="col-md-6">
                        <div class="help-card">
                            <h5>Daily Attendance</h5>
                            <ul>
                                <li>Mark employees as Present (✓) or Absent (✗)</li>
                                <li>Record overtime hours if any</li>
                                <li>Add notes for late arrivals or early departures</li>
                                <li>Save at the end of each day</li>
                            </ul>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="help-card">
                            <h5>Monthly Reports</h5>
                            <ul>
                                <li>View attendance summary for any month</li>
                                <li>Check total absences and overtime</li>
                                <li>Export reports to Excel or PDF</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Payroll Processing -->
            <div class="help-section">
                <h3><i class="fas fa-calculator"></i> Payroll Processing</h3>
                <div class="help-card">
                    <h5>Semi-Monthly Payroll Steps</h5>
                    <div class="step-list">
                        <div class="step-item">
                            <strong>Step 1:</strong> Update attendance records for the pay period
                        </div>
                        <div class="step-item">
                            <strong>Step 2:</strong> Review and approve any overtime hours
                        </div>
                        <div class="step-item">
                            <strong>Step 3:</strong> Process payroll for each employee
                        </div>
                        <div class="step-item">
                            <strong>Step 4:</strong> Review calculations before finalizing
                        </div>
                        <div class="step-item">
                            <strong>Step 5:</strong> Generate and distribute payslips
                        </div>
                    </div>
                </div>
            </div>

            <!-- Understanding Payslips -->
            <div class="help-section">
                <h3><i class="fas fa-file-invoice"></i> Understanding Payslips</h3>
                <div class="row">
                    <div class="col-md-4">
                        <div class="help-card">
                            <h6>Earnings</h6>
                            <ul>
                                <li><strong>Basic Pay:</strong> Regular salary</li>
                                <li><strong>Overtime:</strong> Extra hours worked</li>
                                <li><strong>Holiday Pay:</strong> Work on holidays</li>
                            </ul>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="help-card">
                            <h6>Deductions</h6>
                            <ul>
                                <li><strong>SSS:</strong> Social security contribution</li>
                                <li><strong>PhilHealth:</strong> Health insurance</li>
                                <li><strong>Pag-IBIG:</strong> Housing fund</li>
                                <li><strong>Tax:</strong> Income tax</li>
                            </ul>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="help-card">
                            <h6>Net Pay</h6>
                            <p>Take-home pay after all deductions</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Frequently Asked Questions -->
            <div class="help-section">
                <h3><i class="fas fa-question-circle"></i> Frequently Asked Questions</h3>
                
                <div class="faq-item">
                    <div class="faq-question" onclick="toggleFAQ(this)">
                        <i class="fas fa-chevron-right"></i> I can't find an employee. What should I do?
                    </div>
                    <div class="faq-answer" style="display: none;">
                        <p>Try these steps:</p>
                        <ol>
                            <li>Use the search box to search by name or ID</li>
                            <li>Check if the employee is in the correct department</li>
                            <li>Verify the employee was added to the system</li>
                            <li>Check if the employee is marked as "Active"</li>
                        </ol>
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-question" onclick="toggleFAQ(this)">
                        <i class="fas fa-chevron-right"></i> The salary calculation looks wrong. How do I fix it?
                    </div>
                    <div class="faq-answer" style="display: none;">
                        <p>Check these items:</p>
                        <ul>
                            <li>Verify the daily rate is correct</li>
                            <li>Check attendance records for accuracy</li>
                            <li>Review overtime hours entered</li>
                            <li>Confirm all deductions are properly set</li>
                            <li>Recalculate the payroll</li>
                        </ul>
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-question" onclick="toggleFAQ(this)">
                        <i class="fas fa-chevron-right"></i> How do I print payslips?
                    </div>
                    <div class="faq-answer" style="display: none;">
                        <ol>
                            <li>Go to "View Payslips"</li>
                            <li>Select the employee and date range</li>
                            <li>Click "View Payslip"</li>
                            <li>Use Ctrl+P (Windows) or Cmd+P (Mac) to print</li>
                            <li>Or click "Save as PDF" to save digitally</li>
                        </ol>
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-question" onclick="toggleFAQ(this)">
                        <i class="fas fa-chevron-right"></i> What if I make a mistake?
                    </div>
                    <div class="faq-answer" style="display: none;">
                        <p>Don't worry! Most changes can be corrected:</p>
                        <ul>
                            <li>Attendance mistakes: Edit the attendance record</li>
                            <li>Employee details: Edit the employee profile</li>
                            <li>Payroll errors: Recalculate the payroll</li>
                            <li>Always keep backups of important data</li>
                        </ul>
                    </div>
                </div>
            </div>


            <!-- Quick Tips -->
            <div class="help-section">
                <h3><i class="fas fa-lightbulb"></i> Quick Tips</h3>
                <div class="row">
                    <div class="col-md-6">
                        <div class="help-card">
                            <h6><i class="fas fa-save"></i> Save Often</h6>
                            <p>Always click "Save" after making changes to avoid losing work</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="help-card">
                            <h6><i class="fas fa-search"></i> Use Search</h6>
                            <p>Use the search box to quickly find employees or records</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="help-card">
                            <h6><i class="fas fa-calendar"></i> Set Reminders</h6>
                            <p>Set calendar reminders for payroll deadlines</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="help-card">
                            <h6><i class="fas fa-backup"></i> Backup Data</h6>
                            <p>Export reports regularly as backup</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>



    <script>
        function toggleFAQ(element) {
            const answer = element.nextElementSibling;
            const icon = element.querySelector('i');
            
            if (answer.style.display === 'none') {
                answer.style.display = 'block';
                icon.className = 'fas fa-chevron-down';
            } else {
                answer.style.display = 'none';
                icon.className = 'fas fa-chevron-right';
            }
        }

        // Mobile menu toggle
        document.getElementById('menuToggle').addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('active');
        });
    </script>
</body>
</html>
