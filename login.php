<?php
session_start();
include __DIR__ . "/config.php";

// Initialize error message
$error = "";

// Create CSRF token if not exists - MOVED TO THE TOP
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Security enhancements: URL Bypass Prevention
// Check if already logged in, redirect to appropriate page based on role
if (isset($_SESSION['user']) && !empty($_SESSION['user'])) {
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
        header("Location: dashboard.php");
    } else {
        header("Location: employee_attendance.php");
    }
    exit();
}

// Session timeout settings
$session_timeout = 60; // 1 minute in seconds
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $session_timeout)) {
    // Session expired
    session_unset();
    session_destroy();
    // No need to set error message here as we're redirecting to login anyway
}
// Update last activity time stamp
$_SESSION['last_activity'] = time();

// Initialize login attempts tracking
if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
    $_SESSION['first_attempt_time'] = 0;
}

// Check for too many failed attempts (lockout)
$max_attempts = 3; // Maximum allowed attempts
$lockout_time = 1800; // 1800 seconds lockout time

if ($_SESSION['login_attempts'] >= $max_attempts) {
    // Check if lockout period has passed
    if (time() - $_SESSION['first_attempt_time'] < $lockout_time) {
        $remaining = $lockout_time - (time() - $_SESSION['first_attempt_time']);
        $error = "Too many failed login attempts. Please try again in " . ceil($remaining / 60) . " minutes.";
    } else {
        // Reset attempts if lockout period has passed
        $_SESSION['login_attempts'] = 0;
    }
}

// Process login submission
if (isset($_POST['login']) && $_SESSION['login_attempts'] < $max_attempts) {
    // Check if CSRF token is valid
    if (
        !isset($_SESSION['csrf_token'], $_POST['csrf_token']) ||
        !hash_equals((string)$_SESSION['csrf_token'], (string)$_POST['csrf_token'])
    ) {
        $error = "Invalid request. Please try again.";
    } else {
        // Sanitize inputs
        $username = filter_input(
            INPUT_POST,
            'username',
            FILTER_UNSAFE_RAW,
            FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH
        );

        $password = $_POST['password']; // Don't sanitize password before verification

        if (empty($username) || empty($password)) {
            $error = "Username and password are required";
        } else {
            // Record first attempt time if this is the first attempt
            if ($_SESSION['login_attempts'] == 0) {
                $_SESSION['first_attempt_time'] = time();
            }
            // Continue login logic...
  

            // Prepare secure query
            $query = "SELECT * FROM users WHERE username = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "s", $username);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $row = mysqli_fetch_assoc($result);
            
            // Implement secure password verification
            if ($row) {
                // Check if password is stored in plaintext (legacy) or hashed
                if (password_verify($password, $row['password']) || ($password === $row['password'])) {
                    // Update to secure hash if it's not already (for legacy systems)
                    if ($password === $row['password']) {
                        // Legacy plain password matched - upgrade to secure hash
                        $secure_hash = password_hash($password, PASSWORD_BCRYPT);
                        $update_query = "UPDATE users SET password = ? WHERE username = ?";
                        $update_stmt = mysqli_prepare($conn, $update_query);
                        mysqli_stmt_bind_param($update_stmt, "ss", $secure_hash, $username);
                        mysqli_stmt_execute($update_stmt);
                    }
                    
                    // Successful login - reset attempts
                    $_SESSION['login_attempts'] = 0;
                    
                    // Regenerate session ID to prevent session fixation
                    session_regenerate_id(true);
                    
                    // Set session variables
                    $_SESSION['user'] = $row['username'];
                    $_SESSION['role'] = $row['role'];
                    $_SESSION['last_activity'] = time();
                    $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR']; // For additional security checks
                    
                    // Optional: Log successful login
                    log_activity($conn, $username, 'login_success');
                    
                    // Redirect based on user role
                    if ($row['role'] === 'admin') {
                        header("Location: dashboard.php");
                    } else {
                        header("Location: employee_attendance.php");
                    }
                    exit();
                } else {
                    // Failed login - increment attempts
                    $_SESSION['login_attempts']++;
                    $error = "Invalid username or password!";
                    
                    // Optional: Log failed attempt
                    log_activity($conn, $username, 'login_failure');
                }
            } else {
                // User not found - still increment attempts to prevent username enumeration
                $_SESSION['login_attempts']++;
                $error = "Invalid username or password!";
            }
        }
    }
} elseif (isset($_POST['login']) && $_SESSION['login_attempts'] >= $max_attempts) {
    $remaining = $lockout_time - (time() - $_SESSION['first_attempt_time']);
    $error = "Too many failed login attempts. Please try again in " . ceil($remaining / 60) . " minutes.";
}

// Optional: Function to log activities
function log_activity($conn, $username, $activity_type) {
    // Check if activity_logs table exists first
    $check_table_query = "SHOW TABLES LIKE 'user_logs'";
    $table_result = mysqli_query($conn, $check_table_query);
    
    // Only proceed if table exists or we can create it
    if (mysqli_num_rows($table_result) > 0) {
        $log_query = "INSERT INTO user_logs (username, activity_type, timestamp, ip_address) VALUES (?, ?, NOW(), ?)";
        $log_stmt = mysqli_prepare($conn, $log_query);
        
        // Check if prepare was successful
        if ($log_stmt) {
            $ip = $_SERVER['REMOTE_ADDR'];
            mysqli_stmt_bind_param($log_stmt, "sss", $username, $activity_type, $ip);
            mysqli_stmt_execute($log_stmt);
            mysqli_stmt_close($log_stmt);
        } else {
            // Handle prepare failure silently - don't disrupt login flow
            error_log("Failed to prepare activity log statement: " . mysqli_error($conn));
        }
    } else {
        // Table doesn't exist, we could create it or just log the error
        error_log("Activity logs table doesn't exist");
        
        // Optionally, create the table - uncomment if needed
        
        $create_table_query = "CREATE TABLE user_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(255) NOT NULL,
            activity_type VARCHAR(50) NOT NULL,
            timestamp DATETIME NOT NULL,
            ip_address VARCHAR(45) NOT NULL
        )";
        mysqli_query($conn, $create_table_query);
        
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Payroll System</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap');

        body {
            background: linear-gradient(135deg, #74ebd5, #ACB6E5);
            height: 100vh;
            margin: 0;
            font-family: 'Poppins', sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
        }

        .login-box {
            background: #ffffff;
            padding: 50px;
            width: 100%;
            max-width: 480px;
            border-radius: 25px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            animation: fadeIn 0s ease;
            transition: all 0.3s ease-in-out;
        }

        @keyframes fadeIn {
            from {opacity: 0; transform: translateY(20px);}
            to {opacity: 1; transform: translateY(0);}
        }

        .login-box:hover {
            transform: scale(1.02);
            box-shadow: 0 0 30px rgba(0, 123, 255, 0.3);
        }

        .login-box h3 {
            font-size: 36px;
            font-weight: 700;
            color: #000000;
            margin-bottom: 30px;
            text-align: center;
            letter-spacing: 1px;
        }

        .form-floating-with-icon {
            position: relative;
        }

        .form-floating-with-icon input {
            padding-left: 2.5rem;
            padding-right: 3rem;
            padding-top: 1.2rem;
        }

        .form-floating-with-icon label {
            position: absolute;
            top: 50%;
            left: 0.75rem;
            transform: translateY(-50%);
            transition: all 0.2s ease;
            color: #888;
            font-size: 1rem;
            pointer-events: none;
            display: flex;
            align-items: center;
            background: white;
            padding: 0 0.25rem;
        }

        .form-floating-with-icon input:focus + label,
        .form-floating-with-icon input:not(:placeholder-shown) + label {
            top: 0.2rem;
            font-size: 0.75rem;
            color: #007bff;
            transform: none;
        }

        .form-floating-with-icon .toggle-password {
            position: absolute;
            top: 50%;
            right: 1rem;
            transform: translateY(-50%);
            cursor: pointer;
            color: #007bff;
            font-size: 1.1rem;
        }

        .form-control {
            border-radius: 12px;
            transition: box-shadow 0.3s ease;
        }

        .form-control:focus {
            box-shadow: 0 0 8px rgba(0, 123, 255, 0.5);
            border-color: #007bff;
        }

        .btn-custom {
            background: #007bff;
            color: #fff;
            font-weight: bold;
            border-radius: 14px;
            padding: 14px;
            font-size: 17px;
            transition: all 0.3s ease;
        }

        .btn-custom:hover {
            background: #0056b3;
            box-shadow: 0 0 15px rgba(0, 123, 255, 0.6);
            transform: translateY(-2px);
        }

        /* Fixed Position Popup */
        .alert-top {
            position: fixed;
            top: 15%;
            left: 50%;
            transform: translateX(-50%);
            z-index: 9999;
            width: 100%;
            max-width: 500px;
            padding: 15px;
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            border-radius: 8px;
            text-align: center;
            display: none;
            animation: fadeIn 0.5s ease, fadeOut 2s 2.5s forwards;
        }

        @keyframes fadeIn {
            from {opacity: 0; transform: translateX(-50%) translateY(-10px);}
            to {opacity: 1; transform: translateX(-50%) translateY(0);}
        }

        @keyframes fadeOut {
            from {opacity: 1;}
            to {opacity: 0;}
        }

        /* REMOVE browser's default eye icon in password input */
        input::-ms-reveal,
        input::-ms-clear,
        input::-webkit-credentials-auto-fill-button {
            display: none !important;
        }

        /* Attempts remaining indicator */
        .attempts-indicator {
            text-align: center;
            margin-top: 15px;
            font-size: 0.9rem;
            color: #6c757d;
        }
        
        .attempts-indicator.warning {
            color: #dc3545;
        }
    </style>
</head>
<body>

<!-- Error Popup outside the form -->
<div id="errorPopup" class="alert-top">
    <i class="fas fa-exclamation-circle me-2"></i><span id="errorMessage"><?php echo $error; ?></span>
</div>

<div class="login-box">
    <h3><i class="fas fa-user-tie"></i> Payroll Login</h3>

    <form method="post" novalidate>
        <!-- Hidden CSRF token -->
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
        
        <!-- Username -->
        <div class="form-floating-with-icon mb-4">
            <input type="text" class="form-control" id="username" name="username" placeholder="" 
                   autocomplete="username" required>
            <label for="username">
                <i class="fas fa-user me-2"></i>Username
            </label>
        </div>

        <!-- Password -->
        <div class="form-floating-with-icon mb-4">
            <input type="password" class="form-control" id="passwordInput" name="password" placeholder="" 
                   autocomplete="current-password" required>
            <label for="password">
                <i class="fas fa-lock me-2"></i>Password
            </label>
            <span class="toggle-password" onclick="togglePassword()">
                <i class="fas fa-eye" id="toggleIcon"></i>
            </span>
        </div>

        <button type="submit" name="login" class="btn btn-custom w-100">
            <i class="fas fa-sign-in-alt me-2"></i>Login
        </button>
        
        <?php if ($_SESSION['login_attempts'] > 0 && $_SESSION['login_attempts'] < $max_attempts): ?>
        <!-- Show attempts remaining -->
        <div class="attempts-indicator <?php echo ($_SESSION['login_attempts'] >= $max_attempts - 1) ? 'warning' : ''; ?>">
            <?php echo $max_attempts - $_SESSION['login_attempts']; ?> login attempts remaining
        </div>
        <?php endif; ?>
    </form>
</div>

<script>
    function togglePassword() {
        const passwordInput = document.getElementById("passwordInput");
        const toggleIcon = document.getElementById("toggleIcon");
        if (passwordInput.type === "password") {
            passwordInput.type = "text";
            toggleIcon.classList.replace("fa-eye", "fa-eye-slash");
        } else {
            passwordInput.type = "password";
            toggleIcon.classList.replace("fa-eye-slash", "fa-eye");
        }
    }

    // Show error popup if error exists
    <?php if (!empty($error)) : ?>
        const errorPopup = document.getElementById("errorPopup");
        const errorMessage = document.getElementById("errorMessage");
        errorPopup.style.display = 'block';
        errorMessage.textContent = "<?php echo $error; ?>";
        
        // Auto-hide after 5 seconds
        setTimeout(() => {
            errorPopup.style.display = 'none';
        }, 5000);
    <?php endif; ?>
    
    // Additional security: Disable back button after logout
    if (window.history && window.history.pushState) {
        window.addEventListener('load', function() {
            window.history.pushState('forward', null, '');
            window.onpopstate = function() {
                window.history.pushState('forward', null, '');
            };
        });
    }
</script>

</body>
</html>