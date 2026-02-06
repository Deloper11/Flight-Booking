<?php 
session_start();
include_once 'helpers/helper.php'; 
subview('header.php');

// Security headers
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: strict-origin-when-cross-origin");

// CSRF protection
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Rate limiting setup
if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
    $_SESSION['last_login_attempt'] = 0;
}

// Check if user is already logged in
if(isset($_SESSION['userId'])) {
    header('Location: index.php');
    exit();
}

// Handle cookies for auto-login
$remember_me = false;
$stored_email = '';
if(isset($_COOKIE['airtic_remember']) && isset($_COOKIE['airtic_token'])) {
    require 'helpers/init_conn_db.php';
    
    $token = $_COOKIE['airtic_token'];
    $sql = 'SELECT user_id, username, email FROM Users WHERE remember_token = ?';
    $stmt = mysqli_stmt_init($conn);
    
    if(mysqli_stmt_prepare($stmt, $sql)) {
        mysqli_stmt_bind_param($stmt, 's', $token);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if($row = mysqli_fetch_assoc($result)) {
            // Auto-login with token
            $_SESSION['userId'] = $row['user_id'];
            $_SESSION['userUid'] = $row['username'];
            $_SESSION['userMail'] = $row['email'];
            
            // Regenerate token for security
            $new_token = bin2hex(random_bytes(32));
            $update_sql = 'UPDATE Users SET remember_token = ? WHERE user_id = ?';
            $update_stmt = mysqli_stmt_init($conn);
            
            if(mysqli_stmt_prepare($update_stmt, $update_sql)) {
                mysqli_stmt_bind_param($update_stmt, 'si', $new_token, $row['user_id']);
                mysqli_stmt_execute($update_stmt);
                
                // Update cookie with new token
                setcookie('airtic_token', $new_token, time() + (86400 * 30), "/", "", true, true);
            }
            
            header('Location: index.php?login=auto');
            exit();
        }
        mysqli_stmt_close($stmt);
    }
    mysqli_close($conn);
}

// Handle session messages
$messages = [];
if(isset($_GET['pwd']) && $_GET['pwd'] == 'updated') {
    $messages[] = ['type' => 'success', 'text' => 'Your password has been successfully reset!'];
}

if(isset($_GET['error'])) {
    switch($_GET['error']) {
        case 'invalidcred':
            $messages[] = ['type' => 'error', 'text' => 'Invalid username or email'];
            break;
        case 'wrongpwd':
            $messages[] = ['type' => 'error', 'text' => 'Incorrect password'];
            break;
        case 'sqlerror':
            $messages[] = ['type' => 'error', 'text' => 'Database error. Please try again.'];
            break;
        case 'ratelimit':
            $messages[] = ['type' => 'error', 'text' => 'Too many login attempts. Please wait 15 minutes.'];
            break;
        case 'inactivetoken':
            $messages[] = ['type' => 'error', 'text' => 'Your session has expired. Please login again.'];
            break;
        case 'banned':
            $messages[] = ['type' => 'error', 'text' => 'Your account has been temporarily suspended.'];
            break;
    }
}

if(isset($_GET['success'])) {
    $messages[] = ['type' => 'success', 'text' => 'Registration successful! Please login.'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Login to AirTic 2026 - Secure flight booking platform">
    <meta name="robots" content="noindex, nofollow">
    <title>Login - AirTic 2026</title>
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Animate.css -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    
    <style>
    :root {
        --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        --secondary-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        --success-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        --warning-gradient: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
    }
    
    @font-face {
        font-family: 'product sans';
        src: url('assets/css/Product Sans Bold.ttf');
    }
    
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }
    
    body {
        font-family: 'Poppins', sans-serif;
        background: linear-gradient(rgba(0, 0, 0, 0.8), rgba(0, 0, 0, 0.9)), 
                    url('assets/images/plane2.jpg') no-repeat center center fixed;
        background-size: cover;
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
        color: #333;
    }
    
    .login-container {
        width: 100%;
        max-width: 480px;
        animation: fadeInUp 0.8s ease;
    }
    
    .login-card {
        background: rgba(255, 255, 255, 0.95);
        border-radius: 25px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        overflow: hidden;
        border: 1px solid rgba(255, 255, 255, 0.2);
        backdrop-filter: blur(10px);
    }
    
    .login-header {
        background: var(--primary-gradient);
        padding: 50px 40px;
        text-align: center;
        position: relative;
        overflow: hidden;
    }
    
    .login-header::before {
        content: '';
        position: absolute;
        top: -50%;
        left: -50%;
        width: 200%;
        height: 200%;
        background: radial-gradient(circle, rgba(255,255,255,0.1) 1px, transparent 1px);
        background-size: 30px 30px;
        transform: rotate(15deg);
        opacity: 0.3;
    }
    
    .brand-logo {
        width: 100px;
        height: 100px;
        margin-bottom: 20px;
        filter: drop-shadow(0 4px 12px rgba(0, 0, 0, 0.3));
        animation: pulse 2s infinite;
    }
    
    @keyframes pulse {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.05); }
    }
    
    .login-title {
        font-family: 'product sans', 'Montserrat', sans-serif;
        font-size: 2.8rem;
        font-weight: 800;
        color: white;
        margin-bottom: 10px;
        text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        position: relative;
        z-index: 2;
    }
    
    .login-subtitle {
        color: rgba(255, 255, 255, 0.9);
        font-size: 1.1rem;
        position: relative;
        z-index: 2;
    }
    
    .login-body {
        padding: 50px 40px;
    }
    
    /* Alert Messages */
    .alert-container {
        margin-bottom: 25px;
    }
    
    .alert-custom {
        border-radius: 15px;
        border: none;
        padding: 15px 20px;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 12px;
        animation: slideInDown 0.3s ease;
    }
    
    .alert-custom i {
        font-size: 1.2rem;
    }
    
    .alert-success {
        background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
        color: #155724;
        border-left: 5px solid #28a745;
    }
    
    .alert-error {
        background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
        color: #721c24;
        border-left: 5px solid #dc3545;
    }
    
    /* Form Styles */
    .form-group-custom {
        margin-bottom: 25px;
        position: relative;
    }
    
    .form-label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        color: #2d3748;
        font-size: 1rem;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .form-label i {
        color: #667eea;
    }
    
    .input-group-custom {
        position: relative;
    }
    
    .form-input {
        width: 100%;
        padding: 18px 20px 18px 55px;
        border: 2px solid #e2e8f0;
        border-radius: 15px;
        font-size: 1rem;
        font-family: 'Poppins', sans-serif;
        transition: all 0.3s ease;
        background: white;
        color: #2d3748;
    }
    
    .form-input:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        outline: none;
    }
    
    .form-input::placeholder {
        color: #a0aec0;
    }
    
    .input-icon {
        position: absolute;
        left: 20px;
        top: 50%;
        transform: translateY(-50%);
        color: #667eea;
        font-size: 1.2rem;
        z-index: 2;
    }
    
    .password-toggle {
        position: absolute;
        right: 20px;
        top: 50%;
        transform: translateY(-50%);
        background: none;
        border: none;
        color: #667eea;
        cursor: pointer;
        z-index: 2;
        font-size: 1.2rem;
    }
    
    /* Remember Me & Forgot Password */
    .form-options {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
        flex-wrap: wrap;
        gap: 15px;
    }
    
    .remember-me {
        display: flex;
        align-items: center;
        gap: 8px;
        cursor: pointer;
    }
    
    .checkbox-custom {
        width: 20px;
        height: 20px;
        border: 2px solid #667eea;
        border-radius: 5px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s ease;
    }
    
    .checkbox-custom.checked {
        background: #667eea;
    }
    
    .checkbox-custom.checked::after {
        content: '✓';
        color: white;
        font-size: 14px;
        font-weight: bold;
    }
    
    .remember-text {
        font-size: 0.95rem;
        color: #4a5568;
        user-select: none;
    }
    
    .forgot-link {
        color: #667eea;
        text-decoration: none;
        font-weight: 500;
        font-size: 0.95rem;
        transition: all 0.3s ease;
    }
    
    .forgot-link:hover {
        color: #764ba2;
        text-decoration: underline;
    }
    
    /* Buttons */
    .login-btn {
        background: var(--success-gradient);
        color: white;
        border: none;
        border-radius: 15px;
        padding: 18px 30px;
        font-size: 1.1rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        width: 100%;
        text-transform: uppercase;
        letter-spacing: 1px;
        font-family: 'Montserrat', sans-serif;
        margin-bottom: 25px;
        position: relative;
        overflow: hidden;
    }
    
    .login-btn:hover:not(:disabled) {
        transform: translateY(-3px);
        box-shadow: 0 10px 30px rgba(79, 172, 254, 0.3);
    }
    
    .login-btn:active:not(:disabled) {
        transform: translateY(-1px);
    }
    
    .login-btn:disabled {
        background: #cbd5e1;
        cursor: not-allowed;
        transform: none;
        box-shadow: none;
    }
    
    .spinner-border {
        display: none;
        margin-right: 10px;
    }
    
    /* Alternative Login */
    .divider {
        text-align: center;
        margin: 30px 0;
        position: relative;
    }
    
    .divider::before {
        content: '';
        position: absolute;
        top: 50%;
        left: 0;
        right: 0;
        height: 1px;
        background: #e2e8f0;
        z-index: 1;
    }
    
    .divider-text {
        display: inline-block;
        background: white;
        padding: 0 20px;
        color: #718096;
        font-size: 0.9rem;
        position: relative;
        z-index: 2;
    }
    
    .social-login {
        display: flex;
        gap: 15px;
        margin-bottom: 30px;
    }
    
    .social-btn {
        flex: 1;
        padding: 15px;
        border: 2px solid #e2e8f0;
        border-radius: 15px;
        background: white;
        color: #4a5568;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
    }
    
    .social-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }
    
    .social-btn.google {
        border-color: #db4437;
        color: #db4437;
    }
    
    .social-btn.facebook {
        border-color: #4267B2;
        color: #4267B2;
    }
    
    /* Register Link */
    .register-section {
        text-align: center;
        padding-top: 25px;
        border-top: 2px dashed #e2e8f0;
    }
    
    .register-text {
        color: #718096;
        margin-bottom: 15px;
        font-size: 1rem;
    }
    
    .register-btn {
        background: var(--warning-gradient);
        color: white;
        border: none;
        border-radius: 15px;
        padding: 15px 30px;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 10px;
    }
    
    .register-btn:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 30px rgba(250, 112, 154, 0.3);
    }
    
    /* Security Badge */
    .security-badge {
        background: #f8fafc;
        border-radius: 10px;
        padding: 15px;
        margin-top: 25px;
        text-align: center;
        border: 1px solid #e2e8f0;
    }
    
    .security-badge i {
        color: #10b981;
        font-size: 1.5rem;
        margin-bottom: 8px;
        display: block;
    }
    
    .security-text {
        font-size: 0.85rem;
        color: #64748b;
        margin: 0;
    }
    
    /* Animations */
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    @keyframes slideInDown {
        from {
            opacity: 0;
            transform: translateY(-20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    @keyframes shake {
        0%, 100% { transform: translateX(0); }
        25% { transform: translateX(-5px); }
        75% { transform: translateX(5px); }
    }
    
    .shake {
        animation: shake 0.5s ease;
    }
    
    /* Responsive Design */
    @media (max-width: 576px) {
        .login-container {
            max-width: 100%;
        }
        
        .login-header {
            padding: 40px 30px;
        }
        
        .login-body {
            padding: 40px 30px;
        }
        
        .login-title {
            font-size: 2.2rem;
        }
        
        .brand-logo {
            width: 80px;
            height: 80px;
        }
        
        .form-options {
            flex-direction: column;
            align-items: flex-start;
            gap: 15px;
        }
        
        .social-login {
            flex-direction: column;
        }
    }
    
    @media (max-width: 768px) {
        body {
            padding: 10px;
        }
        
        .login-btn, .register-btn {
            padding: 15px 25px;
        }
    }
    
    /* Print Styles */
    @media print {
        .login-card {
            box-shadow: none;
            border: 1px solid #ddd;
        }
        
        .login-btn, .register-btn, .social-btn {
            display: none;
        }
    }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <!-- Header -->
            <div class="login-header">
                <img src="assets/images/airtic.png" alt="AirTic Logo" class="brand-logo">
                <h1 class="login-title">Welcome Back</h1>
                <p class="login-subtitle">Sign in to access your AirTic 2026 account</p>
            </div>
            
            <!-- Body -->
            <div class="login-body">
                <!-- Messages -->
                <?php if (!empty($messages)): ?>
                <div class="alert-container">
                    <?php foreach ($messages as $message): ?>
                    <div class="alert-custom <?php echo $message['type'] === 'success' ? 'alert-success' : 'alert-error'; ?>">
                        <i class="fas <?php echo $message['type'] === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
                        <span><?php echo htmlspecialchars($message['text']); ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                
                <!-- Login Form -->
                <form method="POST" action="includes/login.inc.php" id="loginForm" novalidate>
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input type="hidden" name="user_agent" value="<?php echo htmlspecialchars($_SERVER['HTTP_USER_AGENT']); ?>">
                    <input type="hidden" name="ip_address" value="<?php echo $_SERVER['REMOTE_ADDR']; ?>">
                    
                    <!-- Username/Email -->
                    <div class="form-group-custom">
                        <label for="user_id" class="form-label">
                            <i class="fas fa-user"></i> Username or Email
                        </label>
                        <div class="input-group-custom">
                            <i class="input-icon fas fa-user-circle"></i>
                            <input type="text" name="user_id" id="user_id" class="form-input" 
                                   placeholder="Enter your username or email" required
                                   value="<?php echo isset($_COOKIE['airtic_remember']) ? htmlspecialchars($_COOKIE['airtic_remember']) : ''; ?>"
                                   autocomplete="username">
                        </div>
                    </div>
                    
                    <!-- Password -->
                    <div class="form-group-custom">
                        <label for="user_pass" class="form-label">
                            <i class="fas fa-lock"></i> Password
                        </label>
                        <div class="input-group-custom">
                            <i class="input-icon fas fa-key"></i>
                            <input type="password" name="user_pass" id="user_pass" class="form-input" 
                                   placeholder="Enter your password" required
                                   autocomplete="current-password">
                            <button type="button" class="password-toggle" id="togglePassword">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Options -->
                    <div class="form-options">
                        <div class="remember-me" id="rememberMe">
                            <div class="checkbox-custom <?php echo isset($_COOKIE['airtic_remember']) ? 'checked' : ''; ?>" 
                                 id="rememberCheckbox"></div>
                            <span class="remember-text">Remember me</span>
                            <input type="checkbox" name="remember_me" id="remember_me" 
                                   style="display: none;" <?php echo isset($_COOKIE['airtic_remember']) ? 'checked' : ''; ?>>
                        </div>
                        <a href="reset-pwd.php" class="forgot-link">
                            <i class="fas fa-key"></i> Forgot Password?
                        </a>
                    </div>
                    
                    <!-- Login Button -->
                    <button type="submit" name="login_but" class="login-btn" id="loginBtn">
                        <span class="spinner-border spinner-border-sm" id="spinner" role="status" aria-hidden="true"></span>
                        <span id="btn-text">
                            <i class="fas fa-sign-in-alt"></i> Sign In
                        </span>
                    </button>
                    
                    <!-- Alternative Login -->
                    <div class="divider">
                        <span class="divider-text">Or continue with</span>
                    </div>
                    
                    <div class="social-login">
                        <button type="button" class="social-btn google">
                            <i class="fab fa-google"></i> Google
                        </button>
                        <button type="button" class="social-btn facebook">
                            <i class="fab fa-facebook-f"></i> Facebook
                        </button>
                    </div>
                </form>
                
                <!-- Register Section -->
                <div class="register-section">
                    <p class="register-text">Don't have an account?</p>
                    <a href="register.php" class="register-btn">
                        <i class="fas fa-user-plus"></i> Create Account
                    </a>
                </div>
                
                <!-- Security Badge -->
                <div class="security-badge">
                    <i class="fas fa-shield-alt"></i>
                    <p class="security-text">Your login is secured with 256-bit SSL encryption</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Password toggle
        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('user_pass');
        const passwordIcon = togglePassword.querySelector('i');
        
        togglePassword.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            passwordIcon.classList.toggle('fa-eye');
            passwordIcon.classList.toggle('fa-eye-slash');
        });
        
        // Remember me checkbox
        const rememberCheckbox = document.getElementById('rememberCheckbox');
        const rememberInput = document.getElementById('remember_me');
        const rememberMeDiv = document.getElementById('rememberMe');
        
        rememberMeDiv.addEventListener('click', function() {
            const isChecked = rememberInput.checked;
            rememberInput.checked = !isChecked;
            rememberCheckbox.classList.toggle('checked');
        });
        
        // Form validation
        const loginForm = document.getElementById('loginForm');
        const loginBtn = document.getElementById('loginBtn');
        const btnText = document.getElementById('btn-text');
        const spinner = document.getElementById('spinner');
        
        loginForm.addEventListener('submit', function(e) {
            const username = document.getElementById('user_id').value.trim();
            const password = document.getElementById('user_pass').value;
            
            // Basic validation
            if (!username || !password) {
                e.preventDefault();
                showError('Please fill in all fields');
                loginForm.classList.add('shake');
                setTimeout(() => loginForm.classList.remove('shake'), 500);
                return;
            }
            
            // Email format validation if email is entered
            if (username.includes('@')) {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(username)) {
                    e.preventDefault();
                    showError('Please enter a valid email address');
                    return;
                }
            }
            
            // Show loading state
            loginBtn.disabled = true;
            btnText.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Signing In...';
            spinner.style.display = 'inline-block';
        });
        
        // Show error function
        function showError(message) {
            // Remove existing error alerts
            const existingAlerts = document.querySelectorAll('.alert-error');
            existingAlerts.forEach(alert => alert.remove());
            
            // Create new error alert
            const alertDiv = document.createElement('div');
            alertDiv.className = 'alert-custom alert-error';
            alertDiv.innerHTML = `
                <i class="fas fa-exclamation-circle"></i>
                <span>${message}</span>
            `;
            
            // Add to container
            const alertContainer = document.querySelector('.alert-container');
            if (alertContainer) {
                alertContainer.prepend(alertDiv);
            } else {
                const loginBody = document.querySelector('.login-body');
                const alertDivContainer = document.createElement('div');
                alertDivContainer.className = 'alert-container';
                alertDivContainer.appendChild(alertDiv);
                loginBody.insertBefore(alertDivContainer, loginBody.firstChild);
            }
            
            // Auto remove after 5 seconds
            setTimeout(() => {
                alertDiv.remove();
            }, 5000);
        }
        
        // Auto-hide success messages
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert-custom');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.opacity = '0';
                    alert.style.transform = 'translateY(-20px)';
                    alert.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                    
                    setTimeout(() => {
                        if (alert.parentNode) {
                            alert.parentNode.removeChild(alert);
                        }
                    }, 300);
                }, 3000);
            });
        }, 5000);
        
        // Add focus effects
        const inputs = document.querySelectorAll('.form-input');
        inputs.forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.parentElement.style.transform = 'scale(1.02)';
                this.parentElement.parentElement.style.transition = 'transform 0.3s ease';
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.parentElement.style.transform = 'scale(1)';
            });
        });
        
        // Handle social login buttons
        const socialButtons = document.querySelectorAll('.social-btn');
        socialButtons.forEach(button => {
            button.addEventListener('click', function() {
                const type = this.classList.contains('google') ? 'Google' : 'Facebook';
                alert(`${type} login integration coming soon!`);
            });
        });
        
        // Check for rate limiting
        const loginAttempts = <?php echo $_SESSION['login_attempts']; ?>;
        const lastAttempt = <?php echo $_SESSION['last_login_attempt']; ?>;
        const currentTime = Math.floor(Date.now() / 1000);
        
        if (loginAttempts >= 5 && (currentTime - lastAttempt) < 900) { // 15 minutes
            loginBtn.disabled = true;
            btnText.textContent = 'Account Locked (15 min)';
            showError('Too many failed attempts. Please wait 15 minutes.');
        }
        
        // Auto-focus username field
        document.getElementById('user_id').focus();
        
        // Handle browser back/forward cache
        window.addEventListener('pageshow', function(event) {
            if (event.persisted) {
                window.location.reload();
            }
        });
    });
    </script>
</body>
</html>

<?php subview('footer.php'); ?>
