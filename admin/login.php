<?php
session_start();

// Redirect if already logged in
if (isset($_SESSION['adminId'])) {
    header('Location: index.php');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require '../helpers/init_conn_db.php';
    
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $remember = isset($_POST['remember']) ? 1 : 0;
    
    // Input validation
    if (empty($username) || empty($password)) {
        $error = 'Please fill in all fields';
    } else {
        // Prepare SQL statement with prepared statement
        $sql = 'SELECT * FROM admin WHERE username = ? OR email = ?';
        $stmt = mysqli_prepare($conn, $sql);
        
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'ss', $username, $username);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            if ($row = mysqli_fetch_assoc($result)) {
                // Verify password
                if (password_verify($password, $row['password'])) {
                    // Check if account is active
                    if ($row['status'] !== 'active') {
                        $error = 'Your account has been deactivated. Contact support.';
                    } else {
                        // Regenerate session ID for security
                        session_regenerate_id(true);
                        
                        // Set session variables
                        $_SESSION['adminId'] = $row['admin_id'];
                        $_SESSION['username'] = $row['username'];
                        $_SESSION['email'] = $row['email'];
                        $_SESSION['role'] = $row['role'];
                        $_SESSION['login_time'] = time();
                        
                        // Set remember me cookie if requested
                        if ($remember) {
                            $token = bin2hex(random_bytes(32));
                            $expiry = time() + (30 * 24 * 60 * 60); // 30 days
                            setcookie('admin_remember', $token, $expiry, '/', '', true, true);
                            
                            // Store token in database
                            $sql_token = 'UPDATE admin SET remember_token = ?, token_expiry = ? WHERE admin_id = ?';
                            $stmt_token = mysqli_prepare($conn, $sql_token);
                            $expiry_date = date('Y-m-d H:i:s', $expiry);
                            mysqli_stmt_bind_param($stmt_token, 'ssi', $token, $expiry_date, $row['admin_id']);
                            mysqli_stmt_execute($stmt_token);
                            mysqli_stmt_close($stmt_token);
                        }
                        
                        // Log login attempt
                        $ip_address = $_SERVER['REMOTE_ADDR'];
                        $user_agent = $_SERVER['HTTP_USER_AGENT'];
                        $log_sql = 'INSERT INTO login_logs (admin_id, ip_address, user_agent, status) VALUES (?, ?, ?, ?)';
                        $log_stmt = mysqli_prepare($conn, $log_sql);
                        $status = 'success';
                        mysqli_stmt_bind_param($log_stmt, 'isss', $row['admin_id'], $ip_address, $user_agent, $status);
                        mysqli_stmt_execute($log_stmt);
                        mysqli_stmt_close($log_stmt);
                        
                        mysqli_stmt_close($stmt);
                        mysqli_close($conn);
                        
                        // Redirect to dashboard
                        header('Location: dashboard.php');
                        exit();
                    }
                } else {
                    // Log failed attempt
                    $ip_address = $_SERVER['REMOTE_ADDR'];
                    $user_agent = $_SERVER['HTTP_USER_AGENT'];
                    $log_sql = 'INSERT INTO login_logs (admin_id, ip_address, user_agent, status) VALUES (?, ?, ?, ?)';
                    $log_stmt = mysqli_prepare($conn, $log_sql);
                    $status = 'failed';
                    mysqli_stmt_bind_param($log_stmt, 'isss', $row['admin_id'], $ip_address, $user_agent, $status);
                    mysqli_stmt_execute($log_stmt);
                    mysqli_stmt_close($log_stmt);
                    
                    $error = 'Invalid username or password';
                }
            } else {
                $error = 'Invalid username or password';
            }
            mysqli_stmt_close($stmt);
        } else {
            $error = 'Database connection error';
        }
        mysqli_close($conn);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | Flight Management System</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="../assets/images/favicon.ico">
    
    <!-- Fonts & Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Styles -->
    <link rel="stylesheet" href="../assets/css/admin-login.css">
    
    <!-- Security Headers -->
    <meta http-equiv="Content-Security-Policy" content="default-src 'self'; script-src 'self' https://cdnjs.cloudflare.com; style-src 'self' https://fonts.googleapis.com https://cdnjs.cloudflare.com; font-src https://fonts.gstatic.com https://cdnjs.cloudflare.com;">
</head>
<body>
    <!-- Background Animation -->
    <div class="background-animation">
        <div class="floating-plane"></div>
        <div class="floating-cloud cloud-1"></div>
        <div class="floating-cloud cloud-2"></div>
        <div class="floating-cloud cloud-3"></div>
        <div class="particles"></div>
    </div>

    <!-- Main Container -->
    <div class="login-container">
        <!-- Left Panel -->
        <div class="login-left">
            <div class="brand-section">
                <div class="brand-logo">
                    <i class="fas fa-plane-departure"></i>
                </div>
                <h1>Flight Admin</h1>
                <p class="brand-tagline">2026 Enterprise Edition</p>
                
                <div class="features-list">
                    <div class="feature-item">
                        <i class="fas fa-shield-alt"></i>
                        <div>
                            <h4>Enhanced Security</h4>
                            <p>Multi-factor authentication & encryption</p>
                        </div>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-chart-line"></i>
                        <div>
                            <h4>Advanced Analytics</h4>
                            <p>Real-time flight & revenue insights</p>
                        </div>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-bolt"></i>
                        <div>
                            <h4>Lightning Fast</h4>
                            <p>Optimized for speed and performance</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="system-info">
                <p><i class="fas fa-info-circle"></i> System: v4.2.1 | Last Updated: Jan 2026</p>
            </div>
        </div>

        <!-- Right Panel - Login Form -->
        <div class="login-right">
            <div class="login-card">
                <div class="login-header">
                    <h2>Welcome Back</h2>
                    <p>Sign in to your admin dashboard</p>
                    
                    <!-- Alert Messages -->
                    <?php if (isset($_GET['pwd']) && $_GET['pwd'] == 'updated'): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i>
                            <span>Password has been reset successfully!</span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle"></i>
                            <span><?php echo htmlspecialchars($error); ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($_GET['error'])): ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            <span>
                                <?php 
                                switch ($_GET['error']) {
                                    case 'invalidcred':
                                        echo 'Invalid credentials. Please try again.';
                                        break;
                                    case 'wrongpwd':
                                        echo 'Incorrect password. Please try again.';
                                        break;
                                    case 'sqlerror':
                                        echo 'System error. Please contact support.';
                                        break;
                                    case 'destless':
                                        echo 'Schedule conflict detected.';
                                        break;
                                    default:
                                        echo 'An error occurred. Please try again.';
                                }
                                ?>
                            </span>
                        </div>
                    <?php endif; ?>
                </div>

                <form method="POST" action="" class="login-form" id="loginForm">
                    <div class="input-group">
                        <label for="username">
                            <i class="fas fa-user"></i>
                            <span>Username or Email</span>
                        </label>
                        <input 
                            type="text" 
                            id="username" 
                            name="username" 
                            placeholder="Enter your username or email"
                            required
                            autocomplete="username"
                            autofocus
                        >
                        <div class="input-hint">Use your registered email or username</div>
                    </div>

                    <div class="input-group">
                        <label for="password">
                            <i class="fas fa-lock"></i>
                            <span>Password</span>
                        </label>
                        <div class="password-wrapper">
                            <input 
                                type="password" 
                                id="password" 
                                name="password" 
                                placeholder="Enter your password"
                                required
                                autocomplete="current-password"
                            >
                            <button type="button" class="toggle-password" id="togglePassword">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="password-strength" id="passwordStrength"></div>
                    </div>

                    <div class="form-options">
                        <div class="remember-me">
                            <input type="checkbox" id="remember" name="remember">
                            <label for="remember">Remember me for 30 days</label>
                        </div>
                        <a href="forgot-password.php" class="forgot-password">
                            Forgot Password?
                        </a>
                    </div>

                    <button type="submit" class="login-button" id="loginButton">
                        <span class="button-text">Sign In</span>
                        <i class="fas fa-arrow-right"></i>
                    </button>

                    <div class="divider">
                        <span>or continue with</span>
                    </div>

                    <div class="social-login">
                        <button type="button" class="social-button google" disabled>
                            <i class="fab fa-google"></i>
                            <span>Google</span>
                        </button>
                        <button type="button" class="social-button microsoft" disabled>
                            <i class="fab fa-microsoft"></i>
                            <span>Microsoft</span>
                        </button>
                        <button type="button" class="social-button sso" onclick="window.location.href='sso-login.php'">
                            <i class="fas fa-id-card"></i>
                            <span>SSO</span>
                        </button>
                    </div>

                    <div class="login-footer">
                        <p>Don't have an account? <a href="request-access.php">Request Access</a></p>
                        <p class="security-note">
                            <i class="fas fa-shield-alt"></i>
                            Secure connection • Encrypted with SSL/TLS
                        </p>
                    </div>
                </form>
            </div>
            
            <!-- Quick Stats -->
            <div class="quick-stats">
                <div class="stat">
                    <i class="fas fa-plane"></i>
                    <div>
                        <h3>24/7</h3>
                        <p>System Uptime</p>
                    </div>
                </div>
                <div class="stat">
                    <i class="fas fa-users"></i>
                    <div>
                        <h3>0</h3>
                        <p>Active Sessions</p>
                    </div>
                </div>
                <div class="stat">
                    <i class="fas fa-bell"></i>
                    <div>
                        <h3>12</h3>
                        <p>Pending Alerts</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="../assets/js/login.js"></script>
    <script>
    // Password toggle functionality
    document.getElementById('togglePassword').addEventListener('click', function() {
        const passwordInput = document.getElementById('password');
        const icon = this.querySelector('i');
        
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            passwordInput.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    });
    
    // Form submission enhancement
    document.getElementById('loginForm').addEventListener('submit', function(e) {
        const button = document.getElementById('loginButton');
        const originalText = button.querySelector('.button-text').textContent;
        
        // Show loading state
        button.disabled = true;
        button.querySelector('.button-text').textContent = 'Authenticating...';
        button.classList.add('loading');
        
        // Simulate network delay for UX
        setTimeout(() => {
            if (!this.checkValidity()) {
                button.disabled = false;
                button.querySelector('.button-text').textContent = originalText;
                button.classList.remove('loading');
            }
        }, 1000);
    });
    
    // Password strength indicator
    document.getElementById('password').addEventListener('input', function(e) {
        const strengthBar = document.getElementById('passwordStrength');
        const password = e.target.value;
        
        if (password.length === 0) {
            strengthBar.style.width = '0%';
            strengthBar.className = 'password-strength';
            return;
        }
        
        let strength = 0;
        if (password.length >= 8) strength += 25;
        if (/[A-Z]/.test(password)) strength += 25;
        if (/[0-9]/.test(password)) strength += 25;
        if (/[^A-Za-z0-9]/.test(password)) strength += 25;
        
        strengthBar.style.width = strength + '%';
        strengthBar.className = 'password-strength';
        
        if (strength < 50) {
            strengthBar.classList.add('weak');
        } else if (strength < 75) {
            strengthBar.classList.add('medium');
        } else {
            strengthBar.classList.add('strong');
        }
    });
    
    // Auto focus on username field
    document.addEventListener('DOMContentLoaded', function() {
        const usernameField = document.getElementById('username');
        if (usernameField) {
            usernameField.focus();
        }
        
        // Check for saved credentials
        if (localStorage.getItem('rememberedUsername')) {
            document.getElementById('username').value = localStorage.getItem('rememberedUsername');
            document.getElementById('remember').checked = true;
        }
    });
    
    // Remember username
    document.getElementById('remember').addEventListener('change', function(e) {
        const username = document.getElementById('username').value;
        if (e.target.checked && username) {
            localStorage.setItem('rememberedUsername', username);
        } else {
            localStorage.removeItem('rememberedUsername');
        }
    });
    </script>
</body>
</html>
