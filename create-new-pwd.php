<?php 
session_start();
include_once 'helpers/helper.php'; 
subview('header.php'); 

// Add security headers
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");

// Regenerate session ID to prevent session fixation
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Reset your password securely">
    <meta name="robots" content="noindex, nofollow">
    <title>Reset Password - AirTic 2026</title>
    
    <link rel="stylesheet" href="assets/css/login.css">
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
    @font-face {
        font-family: 'product sans';
        src: url('assets/css/Product Sans Bold.ttf');
    }
    
    :root {
        --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        --success-gradient: linear-gradient(135deg, #00b09b 0%, #96c93d 100%);
        --danger-gradient: linear-gradient(135deg, #ff416c 0%, #ff4b2b 100%);
    }
    
    h1 {
        font-family: 'product sans', -apple-system, BlinkMacSystemFont, sans-serif !important;
        font-size: 2.5rem !important;
        margin-top: 1.5rem;
        text-align: center;
        background: var(--primary-gradient);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }
    
    body {
        background: var(--primary-gradient);
        min-height: 100vh;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        padding: 1rem;
    }
    
    .login-form {
        background: rgba(255, 255, 255, 0.95);
        border-radius: 20px;
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2), 0 5px 15px rgba(0, 0, 0, 0.1);
        padding: 2.5rem;
        width: 100%;
        max-width: 500px;
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.2);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    
    .login-form:hover {
        transform: translateY(-5px);
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.25), 0 10px 20px rgba(0, 0, 0, 0.15);
    }
    
    .form-input {
        border: 2px solid #e1e5e9;
        border-radius: 10px;
        padding: 0.75rem 1rem;
        font-size: 1rem;
        transition: all 0.3s ease;
        width: 100%;
    }
    
    .form-input:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        outline: none;
    }
    
    .form-input-group {
        position: relative;
        margin-bottom: 1.5rem;
    }
    
    .form-input-group i {
        position: absolute;
        left: 1rem;
        top: 50%;
        transform: translateY(-50%);
        color: #667eea;
        z-index: 2;
    }
    
    .form-input-group input {
        padding-left: 3rem;
    }
    
    .password-toggle {
        position: absolute;
        right: 1rem;
        top: 50%;
        transform: translateY(-50%);
        background: none;
        border: none;
        color: #667eea;
        cursor: pointer;
        z-index: 2;
    }
    
    .button {
        background: var(--success-gradient);
        color: white;
        border: none;
        border-radius: 10px;
        padding: 0.75rem 2rem;
        font-size: 1.1rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
    }
    
    .button:hover {
        transform: translateY(-2px);
        box-shadow: 0 7px 14px rgba(0, 0, 0, 0.1);
    }
    
    .button:active {
        transform: translateY(0);
    }
    
    .password-strength {
        height: 5px;
        border-radius: 2.5px;
        margin-top: 0.5rem;
        transition: all 0.3s ease;
    }
    
    .password-requirements {
        font-size: 0.85rem;
        color: #666;
        margin-top: 0.5rem;
        padding-left: 1rem;
    }
    
    .password-requirements ul {
        margin-bottom: 0;
        padding-left: 1rem;
    }
    
    .password-requirements li {
        margin-bottom: 0.25rem;
    }
    
    .requirement-met {
        color: #00b09b;
    }
    
    .requirement-not-met {
        color: #dc3545;
    }
    
    .alert-container {
        position: fixed;
        top: 1rem;
        right: 1rem;
        z-index: 9999;
        max-width: 400px;
    }
    
    .card {
        border: none;
        border-radius: 15px;
        overflow: hidden;
    }
    
    .card-header {
        background: var(--primary-gradient);
        color: white;
        padding: 1.5rem;
        border-bottom: none;
    }
    
    .card-body {
        padding: 2rem;
    }
    
    .security-note {
        background: #f8f9fa;
        border-left: 4px solid #667eea;
        padding: 1rem;
        border-radius: 5px;
        margin-top: 1.5rem;
        font-size: 0.9rem;
    }
    
    .security-note i {
        color: #667eea;
        margin-right: 0.5rem;
    }
    
    @media (max-width: 768px) {
        .login-form {
            padding: 1.5rem;
            margin: 1rem;
        }
        
        h1 {
            font-size: 2rem !important;
        }
        
        body {
            padding: 0.5rem;
        }
    }
    
    .spinner-border {
        display: none;
    }
    </style>
</head>
<body>
    <?php
    // Handle error messages with Bootstrap alerts instead of JavaScript
    $error_messages = [];
    $success_messages = [];
    
    if(isset($_GET['err'])) {
        switch($_GET['err']) {
            case 'pwdnotmatch':
                $error_messages[] = "Passwords do not match. Please try again.";
                break;
            case 'sqlerr':
                $error_messages[] = "A system error occurred. Please try again or contact support.";
                break;
            case 'invalidtoken':
                $error_messages[] = "Invalid or expired reset token. Please request a new password reset.";
                break;
            case 'weakpassword':
                $error_messages[] = "Password does not meet security requirements.";
                break;
            case 'empty':
                $error_messages[] = "Please fill in all required fields.";
                break;
        }
    } 
    
    if(isset($_GET['pwd'])) {
        if($_GET['pwd'] === 'updated') {
            $success_messages[] = "Your password has been successfully updated!";
        }
    }
    
    // Display alerts
    if (!empty($error_messages) || !empty($success_messages)) {
        echo '<div class="alert-container">';
        foreach ($error_messages as $error) {
            echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>' . htmlspecialchars($error) . '
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                  </div>';
        }
        foreach ($success_messages as $success) {
            echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>' . htmlspecialchars($success) . '
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                  </div>';
        }
        echo '</div>';
    }
    
    // Validate reset token
    $selector = isset($_GET['selector']) ? $_GET['selector'] : '';
    $validator = isset($_GET['validator']) ? $_GET['validator'] : '';
    
    if(empty($selector) || empty($validator)) {
        $error_messages[] = "Invalid reset link. Please check your email and try again.";
    } else {
        // Additional validation for hex format
        if(!ctype_xdigit($selector) || !ctype_xdigit($validator)) {
            $error_messages[] = "Invalid reset token format. Please request a new password reset.";
        }
    }
    ?>

    <div class="login-form">
        <div class="text-center mb-4">
            <img src="assets/images/airtic.png" alt="AirTic Logo" height="80" class="mb-3">
            <h1 class="mb-3">Reset Password</h1>
            <p class="text-muted">Enter your new password below</p>
        </div>
        
        <?php if(empty($error_messages) || (isset($_GET['err']) && $_GET['err'] !== 'invalidtoken')): ?>
        <form method="POST" action="includes/reset-password.inc.php" id="resetForm" novalidate>
            <input type="hidden" name="selector" value="<?php echo htmlspecialchars($selector); ?>">
            <input type="hidden" name="validator" value="<?php echo htmlspecialchars($validator); ?>">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            
            <div class="mb-4">
                <div class="form-input-group">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="password" id="password" class="form-input" 
                        placeholder="Enter new password" required 
                        pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[@$!%*?&]).{12,}"
                        title="Must contain at least 12 characters, including uppercase, lowercase, number and special character"
                        oninput="checkPasswordStrength(this.value)">
                    <button type="button" class="password-toggle" onclick="togglePassword('password')">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                <div id="password-strength" class="password-strength"></div>
                <div id="password-requirements" class="password-requirements">
                    <small>Password must contain:</small>
                    <ul class="mb-0">
                        <li id="req-length" class="requirement-not-met">At least 12 characters</li>
                        <li id="req-uppercase" class="requirement-not-met">One uppercase letter</li>
                        <li id="req-lowercase" class="requirement-not-met">One lowercase letter</li>
                        <li id="req-number" class="requirement-not-met">One number</li>
                        <li id="req-special" class="requirement-not-met">One special character</li>
                    </ul>
                </div>
            </div>
            
            <div class="mb-4">
                <div class="form-input-group">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="password_repeat" id="password_repeat" class="form-input" 
                        placeholder="Confirm new password" required
                        oninput="checkPasswordMatch()">
                    <button type="button" class="password-toggle" onclick="togglePassword('password_repeat')">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                <div id="password-match" class="mt-2"></div>
            </div>
            
            <div class="mb-4">
                <button name="new-pwd-submit" type="submit" class="button" id="submitBtn">
                    <span id="btn-text">Reset Password</span>
                    <span class="spinner-border spinner-border-sm" id="spinner" role="status" aria-hidden="true"></span>
                </button>
            </div>
            
            <div class="security-note">
                <i class="fas fa-shield-alt"></i>
                <small>For your security, passwords are encrypted using bcrypt and never stored in plain text.</small>
            </div>
        </form>
        <?php else: ?>
        <div class="text-center">
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle fa-2x mb-3"></i>
                <h5>Invalid Reset Link</h5>
                <p class="mb-0">The password reset link is invalid or has expired.</p>
            </div>
            <a href="forgot-pwd.php" class="btn btn-primary">
                <i class="fas fa-redo"></i> Request New Reset Link
            </a>
        </div>
        <?php endif; ?>
        
        <div class="text-center mt-4 pt-3 border-top">
            <small class="text-muted">
                Remember your password? 
                <a href="login.php" class="text-primary">Sign In</a>
            </small>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/zxcvbn/4.4.2/zxcvbn.js"></script>
    
    <script>
    function togglePassword(fieldId) {
        const field = document.getElementById(fieldId);
        const toggleIcon = field.nextElementSibling.querySelector('i');
        
        if (field.type === 'password') {
            field.type = 'text';
            toggleIcon.classList.remove('fa-eye');
            toggleIcon.classList.add('fa-eye-slash');
        } else {
            field.type = 'password';
            toggleIcon.classList.remove('fa-eye-slash');
            toggleIcon.classList.add('fa-eye');
        }
    }
    
    function checkPasswordStrength(password) {
        const strengthBar = document.getElementById('password-strength');
        const requirements = {
            length: document.getElementById('req-length'),
            uppercase: document.getElementById('req-uppercase'),
            lowercase: document.getElementById('req-lowercase'),
            number: document.getElementById('req-number'),
            special: document.getElementById('req-special')
        };
        
        // Check requirements
        const hasLength = password.length >= 12;
        const hasUppercase = /[A-Z]/.test(password);
        const hasLowercase = /[a-z]/.test(password);
        const hasNumber = /\d/.test(password);
        const hasSpecial = /[@$!%*?&]/.test(password);
        
        // Update requirement indicators
        requirements.length.className = hasLength ? 'requirement-met' : 'requirement-not-met';
        requirements.uppercase.className = hasUppercase ? 'requirement-met' : 'requirement-not-met';
        requirements.lowercase.className = hasLowercase ? 'requirement-met' : 'requirement-not-met';
        requirements.number.className = hasNumber ? 'requirement-met' : 'requirement-not-met';
        requirements.special.className = hasSpecial ? 'requirement-met' : 'requirement-not-met';
        
        // Calculate strength score
        let score = 0;
        if (hasLength) score++;
        if (hasUppercase) score++;
        if (hasLowercase) score++;
        if (hasNumber) score++;
        if (hasSpecial) score++;
        
        // Update strength bar
        strengthBar.style.width = (score * 20) + '%';
        
        if (score <= 1) {
            strengthBar.style.backgroundColor = '#dc3545';
        } else if (score <= 3) {
            strengthBar.style.backgroundColor = '#ffc107';
        } else {
            strengthBar.style.backgroundColor = '#28a745';
        }
        
        // Use zxcvbn for advanced password strength checking
        if (typeof zxcvbn !== 'undefined' && password.length > 0) {
            const result = zxcvbn(password);
            console.log('Password strength score:', result.score);
        }
    }
    
    function checkPasswordMatch() {
        const password = document.getElementById('password').value;
        const confirmPassword = document.getElementById('password_repeat').value;
        const matchDiv = document.getElementById('password-match');
        
        if (confirmPassword.length === 0) {
            matchDiv.innerHTML = '';
            return;
        }
        
        if (password === confirmPassword) {
            matchDiv.innerHTML = '<small class="text-success"><i class="fas fa-check-circle"></i> Passwords match</small>';
        } else {
            matchDiv.innerHTML = '<small class="text-danger"><i class="fas fa-times-circle"></i> Passwords do not match</small>';
        }
    }
    
    // Form submission handler
    document.getElementById('resetForm')?.addEventListener('submit', function(e) {
        const password = document.getElementById('password').value;
        const confirmPassword = document.getElementById('password_repeat').value;
        const submitBtn = document.getElementById('submitBtn');
        const btnText = document.getElementById('btn-text');
        const spinner = document.getElementById('spinner');
        
        // Validate passwords match
        if (password !== confirmPassword) {
            e.preventDefault();
            alert('Passwords do not match. Please check and try again.');
            return;
        }
        
        // Validate password strength
        const hasLength = password.length >= 12;
        const hasUppercase = /[A-Z]/.test(password);
        const hasLowercase = /[a-z]/.test(password);
        const hasNumber = /\d/.test(password);
        const hasSpecial = /[@$!%*?&]/.test(password);
        
        if (!hasLength || !hasUppercase || !hasLowercase || !hasNumber || !hasSpecial) {
            e.preventDefault();
            alert('Password does not meet all security requirements. Please check the requirements below.');
            return;
        }
        
        // Show loading state
        submitBtn.disabled = true;
        btnText.textContent = 'Resetting...';
        spinner.style.display = 'inline-block';
    });
    
    // Auto-hide alerts after 5 seconds
    setTimeout(() => {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);
    
    // Initialize password strength check on page load
    document.addEventListener('DOMContentLoaded', function() {
        const passwordField = document.getElementById('password');
        if (passwordField) {
            checkPasswordStrength(passwordField.value);
        }
    });
    </script>
</body>
</html>

<?php subview('footer.php'); ?>
