<?php
session_start();
include_once 'helpers/helper.php';
include_once 'includes/csrf_token.php';

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<?php subview('header.php'); ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password | Airline System 2026</title>
    
    <!-- Modern CSS Framework -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/login.css">
    <link rel="stylesheet" href="assets/css/reset-password.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Roboto:wght@300;400;500&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --secondary-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --glass-bg: rgba(255, 255, 255, 0.1);
            --glass-border: rgba(255, 255, 255, 0.2);
            --shadow-lg: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            min-height: 100vh;
            background: 
                linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7)),
                url('assets/images/plane2.jpg') no-repeat center center fixed;
            background-size: cover;
            background-blend-mode: overlay;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            position: relative;
            overflow-x: hidden;
        }
        
        .bg-animation {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            background: var(--primary-gradient);
            opacity: 0.1;
        }
        
        .reset-container {
            width: 100%;
            max-width: 480px;
            margin: 0 auto;
            animation: fadeIn 0.8s ease-out;
        }
        
        .reset-card {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 24px;
            padding: 40px;
            box-shadow: var(--shadow-lg);
            position: relative;
            overflow: hidden;
            transition: transform 0.3s ease;
        }
        
        .reset-card:hover {
            transform: translateY(-5px);
        }
        
        .reset-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 6px;
            background: var(--primary-gradient);
        }
        
        .reset-header {
            text-align: center;
            margin-bottom: 32px;
        }
        
        .reset-icon {
            width: 80px;
            height: 80px;
            background: var(--secondary-gradient);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 32px;
            color: white;
            box-shadow: 0 10px 20px rgba(118, 75, 162, 0.3);
        }
        
        .reset-title {
            font-family: 'Poppins', sans-serif;
            font-size: 32px;
            font-weight: 700;
            color: white;
            margin-bottom: 8px;
            background: linear-gradient(135deg, #fff 0%, #a5b4fc 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .reset-subtitle {
            color: rgba(255, 255, 255, 0.8);
            font-size: 14px;
            font-weight: 400;
        }
        
        .alert-info-custom {
            background: rgba(56, 189, 248, 0.1);
            border: 1px solid rgba(56, 189, 248, 0.3);
            color: #dbeafe;
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 24px;
            backdrop-filter: blur(10px);
        }
        
        .form-group {
            margin-bottom: 24px;
        }
        
        .input-group {
            position: relative;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
        }
        
        .input-group:focus-within {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            background: rgba(255, 255, 255, 0.15);
        }
        
        .input-group i {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255, 255, 255, 0.7);
            font-size: 18px;
            z-index: 1;
        }
        
        .form-control {
            background: transparent !important;
            border: none !important;
            color: white !important;
            padding: 16px 16px 16px 50px !important;
            height: 52px !important;
            font-size: 15px;
            font-weight: 400;
            border-radius: 12px !important;
        }
        
        .form-control::placeholder {
            color: rgba(255, 255, 255, 0.6) !important;
        }
        
        .form-control:focus {
            box-shadow: none !important;
            background: transparent !important;
        }
        
        .submit-btn {
            width: 100%;
            height: 52px;
            background: var(--primary-gradient);
            border: none;
            border-radius: 12px;
            color: white;
            font-weight: 600;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.4);
        }
        
        .submit-btn:active {
            transform: translateY(0);
        }
        
        .back-to-login {
            text-align: center;
            margin-top: 24px;
            color: rgba(255, 255, 255, 0.7);
        }
        
        .back-to-login a {
            color: #a5b4fc;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }
        
        .back-to-login a:hover {
            color: white;
            text-decoration: underline;
        }
        
        /* Status Messages */
        .alert-message {
            animation: slideDown 0.3s ease-out;
            border-radius: 12px;
            margin-bottom: 24px;
            padding: 16px;
            backdrop-filter: blur(10px);
        }
        
        .alert-success {
            background: rgba(34, 197, 94, 0.15);
            border: 1px solid rgba(34, 197, 94, 0.3);
            color: #bbf7d0;
        }
        
        .alert-error {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #fecaca;
        }
        
        .alert-info {
            background: rgba(59, 130, 246, 0.15);
            border: 1px solid rgba(59, 130, 246, 0.3);
            color: #bfdbfe;
        }
        
        /* Animations */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Responsive Design */
        @media (max-width: 576px) {
            .reset-card {
                padding: 30px 24px;
                border-radius: 20px;
            }
            
            .reset-title {
                font-size: 28px;
            }
            
            .reset-icon {
                width: 70px;
                height: 70px;
                font-size: 28px;
            }
        }
        
        @media (max-width: 400px) {
            body {
                padding: 16px;
            }
            
            .reset-card {
                padding: 24px 20px;
            }
            
            .reset-title {
                font-size: 24px;
            }
        }
        
        /* Loading spinner */
        .spinner {
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s ease-in-out infinite;
            display: none;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="bg-animation"></div>
    
    <div class="reset-container">
        <div class="reset-card">
            <!-- Status Messages -->
            <?php if(isset($_GET['err']) || isset($_GET['mail'])): ?>
                <?php
                $alert_class = '';
                $message = '';
                
                if(isset($_GET['err'])) {
                    switch($_GET['err']) {
                        case 'invalidemail':
                            $alert_class = 'alert-error';
                            $message = 'Invalid email address. Please enter a valid registered email.';
                            break;
                        case 'sqlerr':
                            $alert_class = 'alert-error';
                            $message = 'A database error occurred. Please try again.';
                            break;
                        case 'mailerr':
                            $alert_class = 'alert-error';
                            $message = 'Failed to send email. Please try again later.';
                            break;
                        case 'ratelimit':
                            $alert_class = 'alert-error';
                            $message = 'Too many reset attempts. Please try again in 15 minutes.';
                            break;
                    }
                } elseif(isset($_GET['mail']) && $_GET['mail'] === 'success') {
                    $alert_class = 'alert-success';
                    $message = 'Password reset instructions have been sent to your email. Please check your inbox.';
                }
                ?>
                
                <?php if($message): ?>
                    <div class="alert-message <?php echo $alert_class; ?>">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-info-circle me-3"></i>
                            <span><?php echo htmlspecialchars($message); ?></span>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
            
            <div class="reset-header">
                <div class="reset-icon">
                    <i class="fas fa-key"></i>
                </div>
                <h1 class="reset-title">Reset Password</h1>
                <p class="reset-subtitle">Enter your email to receive reset instructions</p>
            </div>
            
            <div class="alert-info-custom">
                <div class="d-flex align-items-center">
                    <i class="fas fa-info-circle me-3"></i>
                    <div>
                        <strong>Important:</strong> An email with password reset instructions will be sent to your registered email address. The link will expire in 30 minutes.
                    </div>
                </div>
            </div>
            
            <form method="POST" action="includes/reset-request.inc.php" id="resetForm" novalidate>
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                
                <div class="form-group">
                    <div class="input-group">
                        <i class="fas fa-envelope"></i>
                        <input 
                            type="email" 
                            name="user_email" 
                            class="form-control" 
                            placeholder="Enter your registered email address" 
                            required
                            pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$"
                            title="Please enter a valid email address"
                        >
                    </div>
                </div>
                
                <button type="submit" name="reset-req-submit" class="submit-btn" id="submitBtn">
                    <span>Send Reset Link</span>
                    <div class="spinner" id="loadingSpinner"></div>
                </button>
            </form>
            
            <div class="back-to-login">
                Remember your password? <a href="login.php">Back to Login</a>
            </div>
        </div>
    </div>

    <!-- Modern JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('resetForm');
            const submitBtn = document.getElementById('submitBtn');
            const spinner = document.getElementById('loadingSpinner');
            
            // Form validation
            form.addEventListener('submit', function(e) {
                const emailInput = form.querySelector('input[name="user_email"]');
                const emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
                
                if (!emailRegex.test(emailInput.value)) {
                    e.preventDefault();
                    showError('Please enter a valid email address');
                    return;
                }
                
                // Show loading state
                submitBtn.disabled = true;
                spinner.style.display = 'block';
                submitBtn.querySelector('span').textContent = 'Sending...';
            });
            
            // Auto-hide alerts after 5 seconds
            const alerts = document.querySelectorAll('.alert-message');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.opacity = '0';
                    alert.style.transform = 'translateY(-10px)';
                    setTimeout(() => alert.remove(), 300);
                }, 5000);
            });
            
            // Add input validation feedback
            const emailInput = form.querySelector('input[name="user_email"]');
            emailInput.addEventListener('input', function() {
                if (this.validity.valid) {
                    this.style.borderColor = 'rgba(34, 197, 94, 0.5)';
                } else {
                    this.style.borderColor = 'rgba(239, 68, 68, 0.5)';
                }
            });
            
            function showError(message) {
                // Remove existing error alerts
                const existingError = document.querySelector('.alert-error');
                if (existingError) existingError.remove();
                
                // Create new error alert
                const errorDiv = document.createElement('div');
                errorDiv.className = 'alert-message alert-error';
                errorDiv.innerHTML = `
                    <div class="d-flex align-items-center">
                        <i class="fas fa-exclamation-circle me-3"></i>
                        <span>${message}</span>
                    </div>
                `;
                
                form.parentNode.insertBefore(errorDiv, form);
                
                // Auto-remove after 5 seconds
                setTimeout(() => {
                    errorDiv.remove();
                }, 5000);
            }
        });
        
        // Add some interactive background effects
        document.addEventListener('mousemove', function(e) {
            const bg = document.querySelector('.bg-animation');
            const x = e.clientX / window.innerWidth;
            const y = e.clientY / window.innerHeight;
            
            bg.style.transform = `translate(${x * 20}px, ${y * 20}px)`;
        });
    </script>
</body>
</html>

<?php subview('footer.php'); ?>
