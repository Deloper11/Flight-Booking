<?php
session_start();
include_once 'helpers/helper.php';
include_once 'config/database.php';

// Check if user is already logged in
if(isset($_SESSION['userId'])) {
    header('Location: dashboard.php');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $passport = trim($_POST['passport']);
    $nationality = $_POST['nationality'];
    $date_of_birth = $_POST['date_of_birth'];
    $gender = $_POST['gender'];
    $terms = isset($_POST['terms']) ? 1 : 0;
    
    // Validate inputs
    $errors = [];
    
    // Full name validation
    if (empty($full_name) || strlen($full_name) < 3) {
        $errors[] = "Full name must be at least 3 characters";
    }
    
    // Email validation
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }
    
    // Phone validation (Bangladeshi)
    if (!preg_match('/^(?:\+88|88)?(01[3-9]\d{8})$/', $phone)) {
        $errors[] = "Invalid Bangladeshi phone number format";
    }
    
    // Password validation
    if (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters";
    }
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = "Password must contain at least one uppercase letter";
    }
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = "Password must contain at least one lowercase letter";
    }
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = "Password must contain at least one number";
    }
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }
    
    // Passport validation
    if (empty($passport)) {
        $errors[] = "Passport number is required";
    }
    
    // Terms validation
    if (!$terms) {
        $errors[] = "You must agree to terms and conditions";
    }
    
    // Check if email already exists
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? OR phone = ?");
        $stmt->execute([$email, $phone]);
        if ($stmt->rowCount() > 0) {
            $errors[] = "Email or phone already registered";
        }
    }
    
    // If no errors, register user
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $verification_code = bin2hex(random_bytes(16));
        $user_id = generateUserID();
        
        try {
            $stmt = $pdo->prepare("INSERT INTO users (
                user_id, full_name, email, phone, password, passport_no, nationality, 
                date_of_birth, gender, verification_code, status, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW())");
            
            $stmt->execute([
                $user_id,
                $full_name,
                $email,
                $phone,
                $hashed_password,
                $passport,
                $nationality,
                $date_of_birth,
                $gender,
                $verification_code
            ]);
            
            // Send welcome email
            sendWelcomeEmail($email, $full_name, $user_id);
            
            // Send SMS verification
            sendVerificationSMS($phone, $verification_code);
            
            // Log user in
            $user = $pdo->lastInsertId();
            $_SESSION['userId'] = $user;
            $_SESSION['userName'] = $full_name;
            $_SESSION['userEmail'] = $email;
            $_SESSION['userType'] = 'customer';
            
            // Redirect to verification page
            header('Location: verify.php?email=' . urlencode($email));
            exit();
            
        } catch (PDOException $e) {
            $errors[] = "Registration failed: " . $e->getMessage();
        }
    }
}

function generateUserID() {
    $prefix = 'ETA';
    $year = date('y');
    $random = strtoupper(substr(md5(uniqid()), 0, 6));
    return $prefix . $year . $random;
}

function sendWelcomeEmail($email, $name, $user_id) {
    $subject = "Welcome to ETA Tour & Travel - Your Account Created";
    $message = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #1a365d 0%, #2d3748 100%); color: white; padding: 30px; text-align: center; }
            .content { padding: 30px; background: #f8f9fa; }
            .details { background: white; border: 2px solid #e2e8f0; border-radius: 10px; padding: 20px; margin: 20px 0; }
            .footer { background: #2d3748; color: white; padding: 20px; text-align: center; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>Welcome to ETA Tour & Travel!</h2>
            </div>
            <div class='content'>
                <h3>Dear $name,</h3>
                <p>Thank you for registering with ETA Tour & Travel. Your account has been successfully created.</p>
                
                <div class='details'>
                    <h4>Account Details:</h4>
                    <p><strong>User ID:</strong> $user_id</p>
                    <p><strong>Email:</strong> $email</p>
                    <p><strong>Status:</strong> Active</p>
                </div>
                
                <p>You can now book flights, manage your bookings, and enjoy exclusive member benefits.</p>
                <p>For verification, please check your SMS for the verification code.</p>
            </div>
            <div class='footer'>
                <p>© 2026 ETA Tour & Travel Agency | IATA Accredited Agent</p>
                <p>24/7 Support: +880 9658 001016 | Email: support@etatourtravel.com</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: ETA Tour & Travel <noreply@etatourtravel.com>" . "\r\n";
    
    mail($email, $subject, $message, $headers);
}

function sendVerificationSMS($phone, $code) {
    $message = "Your ETA Tour & Travel verification code: $code. Valid for 10 minutes.";
    
    // SMS API integration would go here
    // Using Twilio, SMS Gateway, etc.
    
    return true;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - ETA Tour & Travel 2026</title>
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary: #1a365d;
            --secondary: #2d3748;
            --accent: #2b6cb0;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
            --info: #3b82f6;
            --dark: #111827;
            --light: #f9fafb;
            --gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .register-container {
            background: white;
            border-radius: 25px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.15);
            overflow: hidden;
            max-width: 1200px;
            width: 100%;
            display: flex;
            min-height: 800px;
        }
        
        .register-left {
            flex: 1;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            padding: 50px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }
        
        .register-left::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 1px, transparent 1px);
            background-size: 50px 50px;
            opacity: 0.3;
        }
        
        .register-left-content {
            position: relative;
            z-index: 2;
        }
        
        .logo {
            width: 120px;
            height: 120px;
            background: white;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        
        .logo img {
            width: 80px;
            height: 80px;
        }
        
        .register-title {
            font-size: 2.8rem;
            font-weight: 800;
            margin-bottom: 15px;
            line-height: 1.2;
        }
        
        .register-subtitle {
            font-size: 1.1rem;
            opacity: 0.9;
            margin-bottom: 40px;
            line-height: 1.6;
        }
        
        .features-list {
            list-style: none;
            margin-top: 40px;
        }
        
        .features-list li {
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
            font-size: 1rem;
        }
        
        .features-list i {
            width: 40px;
            height: 40px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }
        
        .register-right {
            flex: 1.5;
            padding: 50px;
            overflow-y: auto;
            max-height: 800px;
        }
        
        .form-header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .form-header h2 {
            font-size: 2.2rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 10px;
        }
        
        .form-header p {
            color: #6b7280;
            font-size: 1rem;
        }
        
        .progress-bar {
            display: flex;
            justify-content: space-between;
            margin-bottom: 40px;
            position: relative;
        }
        
        .progress-bar::before {
            content: '';
            position: absolute;
            top: 20px;
            left: 0;
            right: 0;
            height: 4px;
            background: #e5e7eb;
            z-index: 1;
        }
        
        .progress-step {
            position: relative;
            z-index: 2;
            text-align: center;
        }
        
        .step-number {
            width: 45px;
            height: 45px;
            background: #e5e7eb;
            color: #6b7280;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            margin: 0 auto 10px;
            border: 4px solid white;
            transition: all 0.3s ease;
        }
        
        .progress-step.active .step-number {
            background: var(--accent);
            color: white;
            transform: scale(1.1);
        }
        
        .step-label {
            font-size: 0.9rem;
            color: #6b7280;
            font-weight: 500;
        }
        
        .progress-step.active .step-label {
            color: var(--accent);
            font-weight: 600;
        }
        
        .form-step {
            display: none;
            animation: fadeIn 0.5s ease;
        }
        
        .form-step.active {
            display: block;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--secondary);
            font-size: 0.95rem;
        }
        
        .form-control {
            width: 100%;
            padding: 15px 20px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: white;
        }
        
        .form-control:focus {
            border-color: var(--accent);
            outline: none;
            box-shadow: 0 0 0 3px rgba(43, 108, 176, 0.1);
        }
        
        .form-control.error {
            border-color: var(--danger);
        }
        
        .input-with-icon {
            position: relative;
        }
        
        .input-icon {
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            font-size: 1.2rem;
        }
        
        .input-with-icon .form-control {
            padding-left: 55px;
        }
        
        .row {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .row .form-group {
            flex: 1;
            margin-bottom: 0;
        }
        
        .password-strength {
            margin-top: 10px;
        }
        
        .strength-bar {
            height: 6px;
            background: #e5e7eb;
            border-radius: 3px;
            overflow: hidden;
            margin-top: 5px;
        }
        
        .strength-fill {
            height: 100%;
            width: 0%;
            transition: all 0.3s ease;
        }
        
        .strength-text {
            font-size: 0.85rem;
            color: #6b7280;
            margin-top: 5px;
        }
        
        .checkbox-group {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .checkbox-group input[type="checkbox"] {
            width: 20px;
            height: 20px;
            margin-top: 3px;
        }
        
        .checkbox-group label {
            font-size: 0.9rem;
            color: #6b7280;
            line-height: 1.5;
        }
        
        .checkbox-group a {
            color: var(--accent);
            text-decoration: none;
            font-weight: 500;
        }
        
        .checkbox-group a:hover {
            text-decoration: underline;
        }
        
        .form-actions {
            display: flex;
            gap: 15px;
            margin-top: 40px;
        }
        
        .btn {
            padding: 16px 30px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 1rem;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--accent) 0%, #2c5282 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(43, 108, 176, 0.3);
        }
        
        .btn-secondary {
            background: white;
            color: var(--primary);
            border: 2px solid var(--primary);
        }
        
        .btn-secondary:hover {
            background: var(--primary);
            color: white;
            transform: translateY(-3px);
        }
        
        .error-message {
            color: var(--danger);
            font-size: 0.9rem;
            margin-top: 5px;
            display: none;
        }
        
        .password-toggle {
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #9ca3af;
            cursor: pointer;
            font-size: 1.2rem;
        }
        
        .login-link {
            text-align: center;
            margin-top: 30px;
            color: #6b7280;
        }
        
        .login-link a {
            color: var(--accent);
            text-decoration: none;
            font-weight: 600;
        }
        
        .login-link a:hover {
            text-decoration: underline;
        }
        
        .alert {
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            display: none;
        }
        
        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        
        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        
        @media (max-width: 992px) {
            .register-container {
                flex-direction: column;
                max-height: none;
            }
            
            .register-left {
                padding: 30px;
            }
            
            .register-right {
                padding: 30px;
                max-height: none;
            }
            
            .row {
                flex-direction: column;
                gap: 20px;
            }
        }
        
        @media (max-width: 768px) {
            .register-title {
                font-size: 2.2rem;
            }
            
            .form-header h2 {
                font-size: 1.8rem;
            }
            
            .progress-bar {
                flex-direction: column;
                gap: 20px;
            }
            
            .progress-bar::before {
                display: none;
            }
            
            .form-actions {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
            }
        }
        
        .gender-group {
            display: flex;
            gap: 20px;
            margin-top: 10px;
        }
        
        .gender-option {
            flex: 1;
        }
        
        .gender-option input[type="radio"] {
            display: none;
        }
        
        .gender-option label {
            display: block;
            padding: 15px;
            text-align: center;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 500;
        }
        
        .gender-option input[type="radio"]:checked + label {
            border-color: var(--accent);
            background: rgba(43, 108, 176, 0.1);
            color: var(--accent);
        }
        
        .country-select {
            width: 100%;
            padding: 15px 20px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 1rem;
            background: white;
            cursor: pointer;
        }
        
        .country-select:focus {
            border-color: var(--accent);
            outline: none;
        }
        
        .avatar-upload {
            text-align: center;
            margin: 20px 0;
        }
        
        .avatar-preview {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: #f3f4f6;
            margin: 0 auto 20px;
            overflow: hidden;
            border: 3px solid #e5e7eb;
            position: relative;
        }
        
        .avatar-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .avatar-upload-btn {
            display: inline-block;
            padding: 10px 25px;
            background: var(--primary);
            color: white;
            border-radius: 25px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .avatar-upload-btn:hover {
            background: var(--secondary);
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="register-container">
        <!-- Left Section -->
        <div class="register-left">
            <div class="register-left-content">
                <div class="logo">
                    <i class="fas fa-plane fa-3x" style="color: #1a365d;"></i>
                </div>
                
                <h1 class="register-title">Join ETA Tour & Travel</h1>
                <p class="register-subtitle">
                    Register today and unlock exclusive travel benefits, special discounts, 
                    and 24/7 customer support for all your flight bookings.
                </p>
                
                <ul class="features-list">
                    <li>
                        <i class="fas fa-check-circle"></i>
                        <span>Book flights to 195+ countries</span>
                    </li>
                    <li>
                        <i class="fas fa-check-circle"></i>
                        <span>Exclusive member-only discounts</span>
                    </li>
                    <li>
                        <i class="fas fa-check-circle"></i>
                        <span>24/7 multilingual support</span>
                    </li>
                    <li>
                        <i class="fas fa-check-circle"></i>
                        <span>SSLCommerz secure payments</span>
                    </li>
                    <li>
                        <i class="fas fa-check-circle"></i>
                        <span>Priority check-in & boarding</span>
                    </li>
                    <li>
                        <i class="fas fa-check-circle"></i>
                        <span>Earn travel points on every booking</span>
                    </li>
                </ul>
            </div>
        </div>
        
        <!-- Right Section -->
        <div class="register-right">
            <div class="form-header">
                <h2>Create Your Account</h2>
                <p>Fill in your details to start your journey with us</p>
            </div>
            
            <div class="alert alert-error" id="errorAlert">
                <i class="fas fa-exclamation-circle"></i> <span id="errorMessage"></span>
            </div>
            
            <div class="alert alert-success" id="successAlert">
                <i class="fas fa-check-circle"></i> Registration successful! Redirecting...
            </div>
            
            <div class="progress-bar">
                <div class="progress-step active">
                    <div class="step-number">1</div>
                    <div class="step-label">Personal Info</div>
                </div>
                <div class="progress-step">
                    <div class="step-number">2</div>
                    <div class="step-label">Account Details</div>
                </div>
                <div class="progress-step">
                    <div class="step-number">3</div>
                    <div class="step-label">Verification</div>
                </div>
            </div>
            
            <form id="registerForm" method="POST" action="">
                <!-- Step 1: Personal Information -->
                <div class="form-step active" id="step1">
                    <div class="avatar-upload">
                        <div class="avatar-preview">
                            <img src="https://ui-avatars.com/api/?name=New+User&background=1a365d&color=fff&size=120" 
                                 alt="Avatar Preview" id="avatarPreview">
                        </div>
                        <div class="avatar-upload-btn" onclick="document.getElementById('avatarInput').click()">
                            <i class="fas fa-camera"></i> Upload Photo
                        </div>
                        <input type="file" id="avatarInput" accept="image/*" style="display: none;" 
                               onchange="previewAvatar(event)">
                    </div>
                    
                    <div class="row">
                        <div class="form-group">
                            <label class="form-label">Full Name *</label>
                            <div class="input-with-icon">
                                <i class="fas fa-user input-icon"></i>
                                <input type="text" class="form-control" id="full_name" name="full_name" 
                                       placeholder="Enter your full name" required>
                            </div>
                            <div class="error-message" id="nameError"></div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Date of Birth *</label>
                            <div class="input-with-icon">
                                <i class="fas fa-calendar input-icon"></i>
                                <input type="date" class="form-control" id="date_of_birth" name="date_of_birth" 
                                       max="<?php echo date('Y-m-d', strtotime('-18 years')); ?>" required>
                            </div>
                            <div class="error-message" id="dobError"></div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="form-group">
                            <label class="form-label">Email Address *</label>
                            <div class="input-with-icon">
                                <i class="fas fa-envelope input-icon"></i>
                                <input type="email" class="form-control" id="email" name="email" 
                                       placeholder="you@example.com" required>
                            </div>
                            <div class="error-message" id="emailError"></div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Phone Number *</label>
                            <div class="input-with-icon">
                                <i class="fas fa-phone input-icon"></i>
                                <input type="tel" class="form-control" id="phone" name="phone" 
                                       placeholder="01XXXXXXXXX" pattern="[0-9]{11}" required>
                            </div>
                            <div class="error-message" id="phoneError"></div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Gender *</label>
                        <div class="gender-group">
                            <div class="gender-option">
                                <input type="radio" id="male" name="gender" value="male" checked>
                                <label for="male">Male</label>
                            </div>
                            <div class="gender-option">
                                <input type="radio" id="female" name="gender" value="female">
                                <label for="female">Female</label>
                            </div>
                            <div class="gender-option">
                                <input type="radio" id="other" name="gender" value="other">
                                <label for="other">Other</label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="form-group">
                            <label class="form-label">Passport Number *</label>
                            <div class="input-with-icon">
                                <i class="fas fa-passport input-icon"></i>
                                <input type="text" class="form-control" id="passport" name="passport" 
                                       placeholder="A12345678" required>
                            </div>
                            <div class="error-message" id="passportError"></div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Nationality *</label>
                            <div class="input-with-icon">
                                <i class="fas fa-globe input-icon"></i>
                                <select class="country-select" id="nationality" name="nationality" required>
                                    <option value="">Select Country</option>
                                    <option value="BD">Bangladesh</option>
                                    <option value="IN">India</option>
                                    <option value="US">United States</option>
                                    <option value="GB">United Kingdom</option>
                                    <option value="AE">United Arab Emirates</option>
                                    <option value="SA">Saudi Arabia</option>
                                    <option value="CA">Canada</option>
                                    <option value="AU">Australia</option>
                                    <option value="SG">Singapore</option>
                                    <option value="MY">Malaysia</option>
                                </select>
                            </div>
                            <div class="error-message" id="nationalityError"></div>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary" onclick="nextStep()">
                            Next Step <i class="fas fa-arrow-right"></i>
                        </button>
                    </div>
                </div>
                
                <!-- Step 2: Account Details -->
                <div class="form-step" id="step2">
                    <div class="form-group">
                        <label class="form-label">Password *</label>
                        <div class="input-with-icon">
                            <i class="fas fa-lock input-icon"></i>
                            <input type="password" class="form-control" id="password" name="password" 
                                   placeholder="Create a strong password" required>
                            <button type="button" class="password-toggle" onclick="togglePassword('password')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="password-strength">
                            <div class="strength-bar">
                                <div class="strength-fill" id="passwordStrength"></div>
                            </div>
                            <div class="strength-text" id="passwordStrengthText">Password strength</div>
                        </div>
                        <div class="error-message" id="passwordError"></div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Confirm Password *</label>
                        <div class="input-with-icon">
                            <i class="fas fa-lock input-icon"></i>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                   placeholder="Confirm your password" required>
                            <button type="button" class="password-toggle" onclick="togglePassword('confirm_password')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="error-message" id="confirmPasswordError"></div>
                    </div>
                    
                    <div class="checkbox-group">
                        <input type="checkbox" id="newsletter" name="newsletter" checked>
                        <label for="newsletter">
                            I want to receive travel deals, promotions, and updates from ETA Tour & Travel via email
                        </label>
                    </div>
                    
                    <div class="checkbox-group">
                        <input type="checkbox" id="terms" name="terms" required>
                        <label for="terms">
                            I agree to the <a href="terms.php" target="_blank">Terms of Service</a> and 
                            <a href="privacy.php" target="_blank">Privacy Policy</a> *
                        </label>
                        <div class="error-message" id="termsError"></div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary" onclick="prevStep()">
                            <i class="fas fa-arrow-left"></i> Back
                        </button>
                        <button type="button" class="btn btn-primary" onclick="nextStep()">
                            Create Account <i class="fas fa-user-plus"></i>
                        </button>
                    </div>
                </div>
                
                <!-- Step 3: Verification -->
                <div class="form-step" id="step3">
                    <div class="text-center mb-4">
                        <i class="fas fa-envelope fa-4x" style="color: var(--accent); margin-bottom: 20px;"></i>
                        <h3>Verify Your Email</h3>
                        <p class="text-muted">We've sent a verification code to your email address</p>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Verification Code</label>
                        <div class="otp-inputs">
                            <input type="text" class="otp-input" maxlength="1" data-index="1">
                            <input type="text" class="otp-input" maxlength="1" data-index="2">
                            <input type="text" class="otp-input" maxlength="1" data-index="3">
                            <input type="text" class="otp-input" maxlength="1" data-index="4">
                            <input type="text" class="otp-input" maxlength="1" data-index="5">
                            <input type="text" class="otp-input" maxlength="1" data-index="6">
                        </div>
                        <input type="hidden" id="verificationCode">
                        <div class="error-message" id="otpError"></div>
                    </div>
                    
                    <div class="resend-otp text-center mt-3">
                        <p>Didn't receive the code? <a href="#" id="resendOtp">Resend Code</a> (59s)</p>
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary" onclick="prevStep()">
                            <i class="fas fa-arrow-left"></i> Back
                        </button>
                        <button type="submit" class="btn btn-primary" id="submitBtn">
                            Verify & Complete <i class="fas fa-check-circle"></i>
                        </button>
                    </div>
                </div>
            </form>
            
            <div class="login-link">
                Already have an account? <a href="login.php">Sign In</a>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        let currentStep = 1;
        let totalSteps = 3;
        let verificationCode = '';
        
        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            // Generate verification code
            generateVerificationCode();
            
            // Setup event listeners
            setupEventListeners();
            
            // Initialize OTP inputs
            initOtpInputs();
            
            // Update avatar based on name
            document.getElementById('full_name').addEventListener('input', updateAvatar);
        });
        
        // Switch to next step
        function nextStep() {
            if (!validateStep(currentStep)) {
                return;
            }
            
            if (currentStep < totalSteps) {
                document.getElementById(`step${currentStep}`).classList.remove('active');
                document.querySelectorAll('.progress-step')[currentStep - 1].classList.remove('active');
                
                currentStep++;
                
                document.getElementById(`step${currentStep}`).classList.add('active');
                document.querySelectorAll('.progress-step')[currentStep - 1].classList.add('active');
                
                // If moving to step 3, send verification code
                if (currentStep === 3) {
                    sendVerificationCode();
                }
            }
        }
        
        // Switch to previous step
        function prevStep() {
            if (currentStep > 1) {
                document.getElementById(`step${currentStep}`).classList.remove('active');
                document.querySelectorAll('.progress-step')[currentStep - 1].classList.remove('active');
                
                currentStep--;
                
                document.getElementById(`step${currentStep}`).classList.add('active');
                document.querySelectorAll('.progress-step')[currentStep - 1].classList.add('active');
            }
        }
        
        // Validate current step
        function validateStep(step) {
            let isValid = true;
            
            switch(step) {
                case 1:
                    const name = document.getElementById('full_name');
                    const email = document.getElementById('email');
                    const phone = document.getElementById('phone');
                    const passport = document.getElementById('passport');
                    const nationality = document.getElementById('nationality');
                    const dob = document.getElementById('date_of_birth');
                    
                    // Validate name
                    if (!name.value.trim() || name.value.trim().length < 3) {
                        showError('nameError', 'Name must be at least 3 characters');
                        isValid = false;
                    } else {
                        hideError('nameError');
                    }
                    
                    // Validate email
                    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if (!emailRegex.test(email.value)) {
                        showError('emailError', 'Please enter a valid email address');
                        isValid = false;
                    } else {
                        hideError('emailError');
                    }
                    
                    // Validate phone
                    const phoneRegex = /^(?:\+88|88)?(01[3-9]\d{8})$/;
                    if (!phoneRegex.test(phone.value)) {
                        showError('phoneError', 'Please enter a valid Bangladeshi phone number');
                        isValid = false;
                    } else {
                        hideError('phoneError');
                    }
                    
                    // Validate passport
                    if (!passport.value.trim()) {
                        showError('passportError', 'Passport number is required');
                        isValid = false;
                    } else {
                        hideError('passportError');
                    }
                    
                    // Validate nationality
                    if (!nationality.value) {
                        showError('nationalityError', 'Please select your nationality');
                        isValid = false;
                    } else {
                        hideError('nationalityError');
                    }
                    
                    // Validate date of birth (must be 18+)
                    const birthDate = new Date(dob.value);
                    const today = new Date();
                    const age = today.getFullYear() - birthDate.getFullYear();
                    const monthDiff = today.getMonth() - birthDate.getMonth();
                    
                    if (age < 18 || (age === 18 && monthDiff < 0)) {
                        showError('dobError', 'You must be at least 18 years old');
                        isValid = false;
                    } else {
                        hideError('dobError');
                    }
                    break;
                    
                case 2:
                    const password = document.getElementById('password');
                    const confirmPassword = document.getElementById('confirm_password');
                    const terms = document.getElementById('terms');
                    
                    // Validate password
                    const passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/;
                    if (!passwordRegex.test(password.value)) {
                        showError('passwordError', 'Password must be at least 8 characters with uppercase, lowercase, and number');
                        isValid = false;
                    } else {
                        hideError('passwordError');
                    }
                    
                    // Validate password confirmation
                    if (password.value !== confirmPassword.value) {
                        showError('confirmPasswordError', 'Passwords do not match');
                        isValid = false;
                    } else {
                        hideError('confirmPasswordError');
                    }
                    
                    // Validate terms
                    if (!terms.checked) {
                        showError('termsError', 'You must agree to terms and conditions');
                        isValid = false;
                    } else {
                        hideError('termsError');
                    }
                    break;
                    
                case 3:
                    // Validate OTP
                    if (!validateOtp()) {
                        showError('otpError', 'Invalid verification code');
                        isValid = false;
                    } else {
                        hideError('otpError');
                    }
                    break;
            }
            
            return isValid;
        }
        
        // Setup event listeners
        function setupEventListeners() {
            // Password strength indicator
            document.getElementById('password').addEventListener('input', updatePasswordStrength);
            
            // Resend OTP
            document.getElementById('resendOtp').addEventListener('click', function(e) {
                e.preventDefault();
                sendVerificationCode();
            });
            
            // Form submission
            document.getElementById('registerForm').addEventListener('submit', function(e) {
                e.preventDefault();
                if (validateStep(3)) {
                    submitRegistration();
                }
            });
            
            // Real-time validation
            document.querySelectorAll('#step1 input, #step1 select').forEach(input => {
                input.addEventListener('blur', function() {
                    validateField(this);
                });
            });
        }
        
        // Validate individual field
        function validateField(field) {
            const fieldId = field.id;
            const value = field.value;
            
            switch(fieldId) {
                case 'email':
                    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if (!emailRegex.test(value)) {
                        showError('emailError', 'Invalid email format');
                    } else {
                        hideError('emailError');
                    }
                    break;
                    
                case 'phone':
                    const phoneRegex = /^(?:\+88|88)?(01[3-9]\d{8})$/;
                    if (!phoneRegex.test(value)) {
                        showError('phoneError', 'Invalid phone number');
                    } else {
                        hideError('phoneError');
                    }
                    break;
            }
        }
        
        // Update password strength indicator
        function updatePasswordStrength() {
            const password = document.getElementById('password').value;
            const strengthBar = document.getElementById('passwordStrength');
            const strengthText = document.getElementById('passwordStrengthText');
            
            let strength = 0;
            let color = '#ef4444';
            let text = 'Weak';
            
            // Length check
            if (password.length >= 8) strength += 25;
            // Lowercase check
            if (/[a-z]/.test(password)) strength += 25;
            // Uppercase check
            if (/[A-Z]/.test(password)) strength += 25;
            // Number check
            if (/[0-9]/.test(password)) strength += 25;
            
            // Set color and text based on strength
            if (strength >= 75) {
                color = '#10b981';
                text = 'Strong';
            } else if (strength >= 50) {
                color = '#f59e0b';
                text = 'Medium';
            }
            
            strengthBar.style.width = strength + '%';
            strengthBar.style.background = color;
            strengthText.textContent = text;
            strengthText.style.color = color;
        }
        
        // Toggle password visibility
        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const toggle = field.nextElementSibling;
            
            if (field.type === 'password') {
                field.type = 'text';
                toggle.innerHTML = '<i class="fas fa-eye-slash"></i>';
            } else {
                field.type = 'password';
                toggle.innerHTML = '<i class="fas fa-eye"></i>';
            }
        }
        
        // Generate verification code
        function generateVerificationCode() {
            verificationCode = Math.floor(100000 + Math.random() * 900000).toString();
            document.getElementById('verificationCode').value = verificationCode;
        }
        
        // Send verification code
        function sendVerificationCode() {
            const email = document.getElementById('email').value;
            generateVerificationCode();
            
            // Simulate sending email (in production, this would be an API call)
            console.log(`Verification code sent to ${email}: ${verificationCode}`);
            
            // Start OTP timer
            startOtpTimer();
            
            // Show success message
            showAlert('successAlert', `Verification code sent to ${email}`);
        }
        
        // Start OTP timer
        function startOtpTimer() {
            const resendLink = document.getElementById('resendOtp');
            let timeLeft = 59;
            
            const timer = setInterval(() => {
                resendLink.parentElement.innerHTML = 
                    `Didn't receive the code? <a href="#" id="resendOtp">Resend Code</a> (${timeLeft}s)`;
                
                if (timeLeft <= 0) {
                    clearInterval(timer);
                    resendLink.parentElement.innerHTML = 
                        `Didn't receive the code? <a href="#" id="resendOtp">Resend Code</a>`;
                    
                    // Re-attach event listener
                    document.getElementById('resendOtp').addEventListener('click', function(e) {
                        e.preventDefault();
                        sendVerificationCode();
                    });
                }
                
                timeLeft--;
            }, 1000);
        }
        
        // Initialize OTP inputs
        function initOtpInputs() {
            document.querySelectorAll('.otp-input').forEach(input => {
                input.addEventListener('input', function(e) {
                    const value = e.target.value;
                    const index = parseInt(this.dataset.index);
                    
                    // Move to next input
                    if (value && index < 6) {
                        const nextInput = document.querySelector(`.otp-input[data-index="${index + 1}"]`);
                        if (nextInput) nextInput.focus();
                    }
                    
                    // Auto-submit when all inputs filled
                    if (index === 6) {
                        validateOtp();
                    }
                });
                
                input.addEventListener('keydown', function(e) {
                    if (e.key === 'Backspace' && !this.value && index > 1) {
                        const prevInput = document.querySelector(`.otp-input[data-index="${index - 1}"]`);
                        if (prevInput) {
                            prevInput.value = '';
                            prevInput.focus();
                        }
                    }
                });
            });
        }
        
        // Validate OTP
        function validateOtp() {
            let enteredOtp = '';
            document.querySelectorAll('.otp-input').forEach(input => {
                enteredOtp += input.value;
            });
            
            return enteredOtp === verificationCode;
        }
        
        // Submit registration
        function submitRegistration() {
            const btn = document.getElementById('submitBtn');
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            btn.disabled = true;
            
            // Collect form data
            const formData = new FormData(document.getElementById('registerForm'));
            
            // Submit via AJAX
            fetch('register_process.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('successAlert', data.message);
                    setTimeout(() => {
                        window.location.href = 'dashboard.php';
                    }, 2000);
                } else {
                    showAlert('errorAlert', data.message);
                    btn.innerHTML = 'Verify & Complete <i class="fas fa-check-circle"></i>';
                    btn.disabled = false;
                }
            })
            .catch(error => {
                showAlert('errorAlert', 'Registration failed. Please try again.');
                btn.innerHTML = 'Verify & Complete <i class="fas fa-check-circle"></i>';
                btn.disabled = false;
            });
        }
        
        // Preview avatar
        function previewAvatar(event) {
            const reader = new FileReader();
            const preview = document.getElementById('avatarPreview');
            
            reader.onload = function() {
                preview.src = reader.result;
            }
            
            reader.readAsDataURL(event.target.files[0]);
        }
        
        // Update avatar based on name
        function updateAvatar() {
            const name = document.getElementById('full_name').value;
            if (name) {
                const preview = document.getElementById('avatarPreview');
                const initials = name.split(' ').map(n => n[0]).join('').toUpperCase();
                preview.src = `https://ui-avatars.com/api/?name=${encodeURIComponent(initials)}&background=1a365d&color=fff&size=120`;
            }
        }
        
        // Show error message
        function showError(elementId, message) {
            const element = document.getElementById(elementId);
            element.textContent = message;
            element.style.display = 'block';
            
            const input = document.querySelector(`[name="${elementId.replace('Error', '')}"]`);
            if (input) {
                input.classList.add('error');
            }
        }
        
        // Hide error message
        function hideError(elementId) {
            const element = document.getElementById(elementId);
            element.style.display = 'none';
            
            const input = document.querySelector(`[name="${elementId.replace('Error', '')}"]`);
            if (input) {
                input.classList.remove('error');
            }
        }
        
        // Show alert
        function showAlert(elementId, message) {
            const element = document.getElementById(elementId);
            element.querySelector('span').textContent = message;
            element.style.display = 'block';
            
            setTimeout(() => {
                element.style.display = 'none';
            }, 5000);
        }
    </script>
</body>
</html>

<?php subview('footer.php'); ?>
