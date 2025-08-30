<?php 
include_once "functions.php";
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About - Multi Axis Payroll System</title>
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
        .logo {
            width: 200px;
            height: 200px;
            object-fit: contain;
            margin-bottom: -30px;
            margin-top: -50px;


        }

        .about-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            padding: 40px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .about-header {
            text-align: center;
            margin-bottom: 40px;
            padding-bottom: 30px;
            border-bottom: 2px solid #e9ecef;
        }

        .about-header h1 {
            color: #2c3e50;
            font-size: 2.5rem;
            margin-bottom: 10px;
        }

        .about-header p {
            color: #6c757d;
            font-size: 1.2rem;
        }

        .company-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin-bottom: 40px;
        }

        .info-section {
            background: #f8f9fa;
            padding: 30px;
            border-radius: 10px;
            border-top: 4px solid #5dade2;
        }

        .info-section h3 {
            color: #2c3e50;
            margin-bottom: 15px;
            font-size: 1.5rem;
        }

        .info-section p {
            color: #495057;
            line-height: 1.6;
            margin-bottom: 10px;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin: 40px 0;
        }

        .feature-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 15px;
            text-align: center;
            transition: transform 0.3s;
        }

        .feature-card:hover {
            transform: translateY(-5px);
        }

        .feature-card i {
            font-size: 3rem;
            margin-bottom: 15px;
        }

        .feature-card h4 {
            font-size: 1.3rem;
            margin-bottom: 10px;
        }

        .system-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 40px 0;
        }

        .stat-card {
            background: white;
            border: 1px solid #e9ecef;
            padding: 25px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #5dade2;
        }

        .stat-label {
            color: #6c757d;
            font-size: 0.9rem;
        }

        .contact-info {
            background: #e8f4f8;
            padding: 30px;
            border-radius: 10px;
            margin-top: 40px;
        }

        .contact-info h3 {
            color: #2c3e50;
            margin-bottom: 20px;
        }

        .contact-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .contact-item {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .contact-item i {
            color: #5dade2;
            font-size: 1.2rem;
        }

        @media (max-width: 768px) {
            .company-info {
                grid-template-columns: 1fr;
            }
            
            .main-content {
                margin-left: 0;
                width: 100%;
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
    <!-- Main Content -->
    <div class="main-content">
        <div class="about-container">
            <div class="about-header">
                <img src="my_project\images\MULTI-removebg-preview.png" class="logo" alt="Company Logo">
                <h1><i class="fas fa-info-circle me-3"></i>About Multi Axis Payroll System</h1>
                <p>Comprehensive Employee Management & Payroll Solution</p>
            </div>

            <div class="company-info">
                <div class="info-section">
                    <h3><i class="fas fa-building me-2"></i>Company Overview</h3>
                    <p><strong>Multi Axis Handlers & Tech Inc</strong> is a leading technology solutions provider specializing in innovative handling systems and advanced technological solutions. Established with a commitment to excellence, we serve diverse industries with cutting-edge products and services.</p>
                    <p>Our payroll system represents our dedication to operational excellence and employee welfare, ensuring accurate, timely, and transparent compensation management for our valued workforce.</p>
                </div>

                <div class="info-section">
                    <h3><i class="fas fa-cogs me-2"></i>System Purpose</h3>
                    <p>The Multi Axis Payroll System is designed to streamline and automate the entire payroll process, from attendance tracking to payslip generation. It ensures accuracy, compliance, and efficiency in managing employee compensation while providing comprehensive reporting capabilities.</p>
                    <p>Built with modern web technologies, the system offers secure, scalable, and user-friendly solutions for payroll management across all organizational levels.</p>
                </div>
            </div>

            <h3 class="text-center mb-4"><i class="fas fa-star me-2"></i>Key Features</h3>
            <div class="features-grid">
                <div class="feature-card">
                    <i class="fas fa-clock"></i>
                    <h4>Attendance Management</h4>
                    <p>Track daily attendance, overtime, leaves, and absences with automated calculations</p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-calculator"></i>
                    <h4>Automated Payroll</h4>
                    <p>Calculate salaries, deductions, taxes, and net pay with precision and compliance</p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-file-invoice"></i>
                    <h4>Payslip Generation</h4>
                    <p>Generate detailed, professional payslips with complete earnings and deductions breakdown</p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-chart-line"></i>
                    <h4>Comprehensive Reports</h4>
                    <p>Access detailed reports for attendance, payroll summaries, and financial analytics</p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-shield-alt"></i>
                    <h4>Security & Compliance</h4>
                    <p>Role-based access control with secure data handling and regulatory compliance</p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-mobile-alt"></i>
                    <h4>Responsive Design</h4>
                    <p>Access the system from any device with mobile-friendly responsive interface</p>
                </div>
            </div>

            <h3 class="text-center mb-4"><i class="fas fa-chart-bar me-2"></i>System Capabilities</h3>
            <div class="system-stats">
                <div class="stat-card">
                    <div class="stat-number">100%</div>
                    <div class="stat-label">Automated Calculations</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">24/7</div>
                    <div class="stat-label">System Availability</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">Multi</div>
                    <div class="stat-label">Payroll Frequencies</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">Secure</div>
                    <div class="stat-label">Data Protection</div>
                </div>
            </div>

            <div class="contact-info">
                <h3><i class="fas fa-address-book me-2"></i>Contact Information</h3>
                <div class="contact-grid">
                    <div class="contact-item">
                        <i class="fas fa-map-marker-alt"></i>
                        <span>Multi Axis Handlers & Tech Inc<br>Philippines</span>
                    </div>
                    <div class="contact-item">
                        <i class="fas fa-envelope"></i>
                        <span>hr@multiaxis.com.ph</span>
                    </div>
                    <div class="contact-item">
                        <i class="fas fa-phone"></i>
                        <span>+63 (2) 8-123-4567</span>
                    </div>
                    <div class="contact-item">
                        <i class="fas fa-clock"></i>
                        <span>Mon-Fri: 8:00 AM - 5:00 PM</span>
                    </div>
                </div>
            </div>

            <div class="text-center mt-5">
                <p class="text-muted">
                    <i class="fas fa-heart me-1"></i> 
                    Built with care for our employees and organization
                </p>
                <p class="text-muted">
                    <small>System Version 2.0 | Last Updated: <?php echo date('F Y'); ?></small>
                </p>
            </div>
        </div>
    </div>

    <script>
        // Mobile menu toggle
        document.getElementById('menuToggle').addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('active');
        });
    </script>
</body>
</html>
