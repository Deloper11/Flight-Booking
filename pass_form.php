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

// Check if user is logged in
if(!isset($_SESSION['userId'])) {
    header('Location: login.php?error=notloggedin');
    exit();
}

// Check if booking data exists
if(!isset($_POST['book_but'])) {
    header('Location: index.php?error=nobooking');
    exit();
}

// Validate and sanitize input
$flight_id = filter_var($_POST['flight_id'], FILTER_VALIDATE_INT);
$passengers = filter_var($_POST['passengers'], FILTER_VALIDATE_INT);
$price = filter_var($_POST['price'], FILTER_VALIDATE_FLOAT);
$class = htmlspecialchars($_POST['class']);
$type = htmlspecialchars($_POST['type']);

if(!$flight_id || !$passengers || !$price || !$class || !$type) {
    header('Location: index.php?error=invalidbooking');
    exit();
}

// Validate passenger count (1-9)
if($passengers < 1 || $passengers > 9) {
    header('Location: index.php?error=invalidpassengers');
    exit();
}

// Store booking data in session for persistence
$_SESSION['booking_data'] = [
    'flight_id' => $flight_id,
    'passengers' => $passengers,
    'price' => $price,
    'class' => $class,
    'type' => $type,
    'ret_date' => isset($_POST['ret_date']) ? $_POST['ret_date'] : null
];

// Fetch flight details for display
require 'helpers/init_conn_db.php';

$stmt = mysqli_stmt_init($conn);
$sql = 'SELECT * FROM Flight WHERE flight_id = ?';
if(mysqli_stmt_prepare($stmt, $sql)) {
    mysqli_stmt_bind_param($stmt, 'i', $flight_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $flight = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
}

if(!$flight) {
    header('Location: index.php?error=flightnotfound');
    exit();
}

// Handle error messages
$error_messages = [];
if(isset($_GET['error'])) {
    switch($_GET['error']) {
        case 'invdate':
            $error_messages[] = 'Please enter a valid date of birth';
            break;
        case 'moblen':
            $error_messages[] = 'Please enter a valid 10-digit mobile number';
            break;
        case 'sqlerror':
            $error_messages[] = 'Database error occurred. Please try again.';
            break;
        case 'invalidname':
            $error_messages[] = 'Please enter valid names (letters and spaces only)';
            break;
        case 'futurebirth':
            $error_messages[] = 'Date of birth cannot be in the future';
            break;
        case 'underage':
            $error_messages[] = 'Passenger must be at least 1 year old';
            break;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Passenger Details - AirTic 2026">
    <title>Passenger Details - AirTic 2026</title>
    
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
        --primary-color: #4361ee;
        --secondary-color: #3a0ca3;
        --accent-color: #4cc9f0;
        --success-color: #4ade80;
        --warning-color: #f59e0b;
        --danger-color: #ef4444;
        --dark-color: #1e293b;
        --light-color: #f8fafc;
        --gradient-primary: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
        --gradient-success: linear-gradient(135deg, var(--success-color) 0%, #10b981 100%);
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
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        min-height: 100vh;
        color: var(--dark-color);
        padding-bottom: 50px;
    }
    
    .container-main {
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px;
    }
    
    .booking-header {
        background: rgba(255, 255, 255, 0.95);
        border-radius: 25px;
        padding: 40px;
        margin-bottom: 30px;
        box-shadow: 0 15px 40px rgba(0, 0, 0, 0.1);
        border: 2px solid rgba(255, 255, 255, 0.2);
        backdrop-filter: blur(10px);
        animation: fadeInDown 0.8s ease;
    }
    
    .booking-title {
        font-family: 'product sans', 'Montserrat', sans-serif;
        font-size: 2.8rem;
        font-weight: 800;
        color: var(--primary-color);
        margin-bottom: 15px;
        text-align: center;
    }
    
    .booking-subtitle {
        text-align: center;
        color: #64748b;
        font-size: 1.1rem;
        margin-bottom: 30px;
    }
    
    .flight-summary {
        background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
        border-radius: 20px;
        padding: 25px;
        margin-bottom: 30px;
        border: 2px solid rgba(67, 97, 238, 0.1);
    }
    
    .flight-info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 20px;
    }
    
    .flight-info-item {
        text-align: center;
        padding: 15px;
        background: white;
        border-radius: 15px;
        border: 2px solid #e2e8f0;
    }
    
    .info-label {
        font-size: 0.9rem;
        color: #64748b;
        text-transform: uppercase;
        font-weight: 600;
        margin-bottom: 8px;
    }
    
    .info-value {
        font-size: 1.3rem;
        font-weight: 700;
        color: var(--dark-color);
    }
    
    .info-value-large {
        font-size: 1.8rem;
        font-weight: 800;
        color: var(--primary-color);
    }
    
    .passenger-container {
        background: rgba(255, 255, 255, 0.95);
        border-radius: 25px;
        padding: 40px;
        box-shadow: 0 15px 40px rgba(0, 0, 0, 0.1);
        border: 2px solid rgba(255, 255, 255, 0.2);
        backdrop-filter: blur(10px);
        animation: fadeInUp 0.8s ease 0.2s both;
    }
    
    .passenger-section-title {
        font-family: 'Montserrat', sans-serif;
        font-size: 1.8rem;
        font-weight: 600;
        color: var(--primary-color);
        margin-bottom: 30px;
        display: flex;
        align-items: center;
        gap: 15px;
    }
    
    .passenger-section-title i {
        background: var(--gradient-primary);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }
    
    .passenger-form-card {
        background: white;
        border-radius: 20px;
        padding: 30px;
        margin-bottom: 30px;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
        border: 2px solid #e2e8f0;
        transition: all 0.3s ease;
    }
    
    .passenger-form-card:hover {
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
        border-color: var(--primary-color);
    }
    
    .passenger-number {
        display: inline-block;
        background: var(--gradient-primary);
        color: white;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        text-align: center;
        line-height: 40px;
        font-weight: 700;
        font-size: 1.2rem;
        margin-bottom: 20px;
    }
    
    .form-row-custom {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 25px;
        margin-bottom: 25px;
    }
    
    .form-group-custom {
        position: relative;
    }
    
    .form-label {
        display: block;
        margin-bottom: 10px;
        font-weight: 600;
        color: var(--dark-color);
        font-size: 1rem;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .form-label i {
        color: var(--primary-color);
    }
    
    .form-input {
        width: 100%;
        padding: 15px 20px;
        border: 2px solid #e2e8f0;
        border-radius: 12px;
        font-size: 1rem;
        font-family: 'Poppins', sans-serif;
        transition: all 0.3s ease;
        background: white;
        color: var(--dark-color);
    }
    
    .form-input:focus {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
        outline: none;
    }
    
    .form-input::placeholder {
        color: #a0aec0;
    }
    
    .form-input.error {
        border-color: var(--danger-color);
        background: #fef2f2;
    }
    
    .form-input.success {
        border-color: var(--success-color);
    }
    
    .error-message {
        color: var(--danger-color);
        font-size: 0.85rem;
        margin-top: 5px;
        display: flex;
        align-items: center;
        gap: 5px;
    }
    
    .success-message {
        color: var(--success-color);
        font-size: 0.85rem;
        margin-top: 5px;
        display: flex;
        align-items: center;
        gap: 5px;
    }
    
    .age-calculator {
        background: #f0f9ff;
        border-radius: 10px;
        padding: 10px 15px;
        margin-top: 8px;
        font-size: 0.9rem;
        color: #0369a1;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .passenger-type {
        background: #f8fafc;
        border-radius: 15px;
        padding: 20px;
        margin-top: 20px;
        border-left: 5px solid var(--primary-color);
    }
    
    .passenger-type-label {
        font-weight: 600;
        color: var(--primary-color);
        margin-bottom: 10px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .passenger-type-options {
        display: flex;
        gap: 20px;
        flex-wrap: wrap;
    }
    
    .passenger-type-option {
        display: flex;
        align-items: center;
        gap: 8px;
        cursor: pointer;
    }
    
    .type-radio {
        width: 20px;
        height: 20px;
        border: 2px solid #cbd5e1;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s ease;
    }
    
    .type-radio.selected {
        border-color: var(--primary-color);
    }
    
    .type-radio.selected::after {
        content: '';
        width: 10px;
        height: 10px;
        background: var(--primary-color);
        border-radius: 50%;
    }
    
    .type-label {
        font-weight: 500;
        color: #4a5568;
    }
    
    .type-description {
        font-size: 0.85rem;
        color: #718096;
        margin-left: 28px;
        margin-top: 2px;
    }
    
    .submit-section {
        text-align: center;
        margin-top: 50px;
        padding-top: 30px;
        border-top: 2px dashed #e2e8f0;
    }
    
    .submit-btn {
        background: var(--gradient-success);
        color: white;
        border: none;
        border-radius: 15px;
        padding: 18px 50px;
        font-size: 1.2rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        text-transform: uppercase;
        letter-spacing: 1px;
        font-family: 'Montserrat', sans-serif;
        display: inline-flex;
        align-items: center;
        gap: 15px;
        position: relative;
        overflow: hidden;
    }
    
    .submit-btn:hover:not(:disabled) {
        transform: translateY(-3px);
        box-shadow: 0 15px 35px rgba(16, 185, 129, 0.3);
    }
    
    .submit-btn:active:not(:disabled) {
        transform: translateY(-1px);
    }
    
    .submit-btn:disabled {
        background: #cbd5e1;
        cursor: not-allowed;
        transform: none;
        box-shadow: none;
    }
    
    .spinner-border {
        display: none;
    }
    
    .price-summary {
        background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
        border-radius: 20px;
        padding: 25px;
        margin-top: 30px;
        border: 2px solid #a7f3d0;
    }
    
    .price-title {
        font-weight: 700;
        color: #065f46;
        margin-bottom: 15px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .price-breakdown {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
        margin-bottom: 20px;
    }
    
    .price-item {
        display: flex;
        justify-content: space-between;
        padding: 10px 0;
        border-bottom: 1px solid #d1fae5;
    }
    
    .price-label {
        color: #047857;
    }
    
    .price-value {
        font-weight: 600;
        color: #065f46;
    }
    
    .price-total {
        display: flex;
        justify-content: space-between;
        padding: 15px 0;
        border-top: 2px solid #10b981;
        font-size: 1.3rem;
        font-weight: 700;
        color: #065f46;
    }
    
    .alert-container {
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
        max-width: 400px;
    }
    
    .alert-custom {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(10px);
        border-radius: 15px;
        padding: 15px 20px;
        margin-bottom: 10px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        border-left: 5px solid;
        animation: slideInRight 0.3s ease;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .alert-error { border-color: var(--danger-color); }
    .alert-success { border-color: var(--success-color); }
    
    .progress-container {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 5px;
        background: rgba(255, 255, 255, 0.1);
        z-index: 9998;
    }
    
    .progress-bar {
        height: 100%;
        background: var(--gradient-primary);
        width: 50%;
        transition: width 0.3s ease;
    }
    
    @keyframes fadeInDown {
        from {
            opacity: 0;
            transform: translateY(-30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
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
    
    @keyframes slideInRight {
        from {
            opacity: 0;
            transform: translateX(30px);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }
    
    @media (max-width: 768px) {
        .container-main {
            padding: 10px;
        }
        
        .booking-header, .passenger-container {
            padding: 25px;
        }
        
        .booking-title {
            font-size: 2.2rem;
        }
        
        .flight-info-grid {
            grid-template-columns: repeat(2, 1fr);
        }
        
        .form-row-custom {
            grid-template-columns: 1fr;
        }
        
        .submit-btn {
            width: 100%;
            padding: 18px 30px;
            justify-content: center;
        }
    }
    
    @media (max-width: 480px) {
        .booking-title {
            font-size: 1.8rem;
        }
        
        .flight-info-grid {
            grid-template-columns: 1fr;
        }
        
        .passenger-form-card {
            padding: 20px;
        }
        
        .passenger-section-title {
            font-size: 1.5rem;
            flex-direction: column;
            align-items: flex-start;
            gap: 10px;
        }
    }
    </style>
</head>
<body>
    <!-- Progress Bar -->
    <div class="progress-container">
        <div class="progress-bar" id="progressBar"></div>
    </div>
    
    <!-- Error Messages -->
    <?php if (!empty($error_messages)): ?>
    <div class="alert-container">
        <?php foreach ($error_messages as $error): ?>
        <div class="alert-custom alert-error">
            <i class="fas fa-exclamation-circle"></i>
            <span><?php echo htmlspecialchars($error); ?></span>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    
    <div class="container-main">
        <!-- Booking Header -->
        <div class="booking-header">
            <h1 class="booking-title">Complete Your Booking</h1>
            <p class="booking-subtitle">Enter passenger details for <?php echo $passengers; ?> traveler(s)</p>
            
            <!-- Flight Summary -->
            <div class="flight-summary">
                <div class="flight-info-grid">
                    <div class="flight-info-item">
                        <div class="info-label">Airline</div>
                        <div class="info-value-large"><?php echo htmlspecialchars($flight['airline']); ?></div>
                    </div>
                    <div class="flight-info-item">
                        <div class="info-label">From</div>
                        <div class="info-value-large"><?php echo htmlspecialchars($flight['source']); ?></div>
                    </div>
                    <div class="flight-info-item">
                        <div class="info-label">To</div>
                        <div class="info-value-large"><?php echo htmlspecialchars($flight['Destination']); ?></div>
                    </div>
                    <div class="flight-info-item">
                        <div class="info-label">Departure</div>
                        <div class="info-value">
                            <?php echo date('M j, Y', strtotime($flight['departure'])); ?><br>
                            <?php echo date('h:i A', strtotime($flight['departure'])); ?>
                        </div>
                    </div>
                </div>
                
                <div class="price-summary">
                    <div class="price-title">
                        <i class="fas fa-receipt"></i> Price Summary
                    </div>
                    <div class="price-breakdown">
                        <div class="price-item">
                            <span class="price-label">Base Fare (x<?php echo $passengers; ?>)</span>
                            <span class="price-value">KES <?php echo number_format($price / $passengers); ?></span>
                        </div>
                        <?php if($class === 'B'): ?>
                        <div class="price-item">
                            <span class="price-label">Business Class Upgrade</span>
                            <span class="price-value">+ 50%</span>
                        </div>
                        <?php endif; ?>
                        <?php if($type === 'round'): ?>
                        <div class="price-item">
                            <span class="price-label">Return Trip</span>
                            <span class="price-value">x 2</span>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="price-total">
                        <span>Total Amount</span>
                        <span>KES <?php echo number_format($price); ?></span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Passenger Forms -->
        <div class="passenger-container">
            <h2 class="passenger-section-title">
                <i class="fas fa-users"></i> Passenger Details
            </h2>
            
            <form action="includes/pass_detail.inc.php" method="POST" id="passengerForm" novalidate>
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <input type="hidden" name="type" value="<?php echo htmlspecialchars($type); ?>">
                <input type="hidden" name="class" value="<?php echo htmlspecialchars($class); ?>">
                <input type="hidden" name="passengers" value="<?php echo $passengers; ?>">
                <input type="hidden" name="price" value="<?php echo $price; ?>">
                <input type="hidden" name="flight_id" value="<?php echo $flight_id; ?>">
                
                <?php for($i = 1; $i <= $passengers; $i++): ?>
                <div class="passenger-form-card" id="passenger-<?php echo $i; ?>">
                    <div class="passenger-number"><?php echo $i; ?></div>
                    
                    <div class="form-row-custom">
                        <div class="form-group-custom">
                            <label for="firstname-<?php echo $i; ?>" class="form-label">
                                <i class="fas fa-user"></i> First Name <span style="color: var(--danger-color);">*</span>
                            </label>
                            <input type="text" name="firstname[]" id="firstname-<?php echo $i; ?>" 
                                   class="form-input" placeholder="Enter first name" required
                                   pattern="[A-Za-z\s]{2,50}" title="Only letters and spaces (2-50 characters)">
                            <div class="error-message" id="firstname-error-<?php echo $i; ?>"></div>
                        </div>
                        
                        <div class="form-group-custom">
                            <label for="midname-<?php echo $i; ?>" class="form-label">
                                <i class="fas fa-user"></i> Middle Name
                            </label>
                            <input type="text" name="midname[]" id="midname-<?php echo $i; ?>" 
                                   class="form-input" placeholder="Enter middle name (optional)"
                                   pattern="[A-Za-z\s]{0,50}" title="Only letters and spaces (max 50 characters)">
                            <div class="error-message" id="midname-error-<?php echo $i; ?>"></div>
                        </div>
                        
                        <div class="form-group-custom">
                            <label for="lastname-<?php echo $i; ?>" class="form-label">
                                <i class="fas fa-user"></i> Last Name <span style="color: var(--danger-color);">*</span>
                            </label>
                            <input type="text" name="lastname[]" id="lastname-<?php echo $i; ?>" 
                                   class="form-input" placeholder="Enter last name" required
                                   pattern="[A-Za-z\s]{2,50}" title="Only letters and spaces (2-50 characters)">
                            <div class="error-message" id="lastname-error-<?php echo $i; ?>"></div>
                        </div>
                    </div>
                    
                    <div class="form-row-custom">
                        <div class="form-group-custom">
                            <label for="mobile-<?php echo $i; ?>" class="form-label">
                                <i class="fas fa-phone"></i> Mobile Number <span style="color: var(--danger-color);">*</span>
                            </label>
                            <input type="tel" name="mobile[]" id="mobile-<?php echo $i; ?>" 
                                   class="form-input" placeholder="Enter 10-digit mobile number" required
                                   pattern="[0-9]{10}" title="10-digit mobile number">
                            <div class="error-message" id="mobile-error-<?php echo $i; ?>"></div>
                        </div>
                        
                        <div class="form-group-custom">
                            <label for="date-<?php echo $i; ?>" class="form-label">
                                <i class="fas fa-calendar-alt"></i> Date of Birth <span style="color: var(--danger-color);">*</span>
                            </label>
                            <input type="date" name="date[]" id="date-<?php echo $i; ?>" 
                                   class="form-input" required max="<?php echo date('Y-m-d'); ?>">
                            <div class="error-message" id="date-error-<?php echo $i; ?>"></div>
                            <div class="age-calculator" id="age-display-<?php echo $i; ?>">
                                <i class="fas fa-birthday-cake"></i>
                                <span>Age will be calculated automatically</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Passenger Type Selection -->
                    <div class="passenger-type">
                        <div class="passenger-type-label">
                            <i class="fas fa-user-tag"></i> Passenger Type
                        </div>
                        <div class="passenger-type-options">
                            <div class="passenger-type-option" data-passenger="<?php echo $i; ?>" data-type="adult">
                                <div class="type-radio <?php echo $i === 1 ? 'selected' : ''; ?>" id="type-adult-<?php echo $i; ?>"></div>
                                <span class="type-label">Adult (12+ years)</span>
                                <input type="radio" name="passenger_type[<?php echo $i; ?>]" value="adult" 
                                       <?php echo $i === 1 ? 'checked' : ''; ?> style="display: none;">
                            </div>
                            <div class="passenger-type-option" data-passenger="<?php echo $i; ?>" data-type="child">
                                <div class="type-radio" id="type-child-<?php echo $i; ?>"></div>
                                <span class="type-label">Child (2-11 years)</span>
                                <input type="radio" name="passenger_type[<?php echo $i; ?>]" value="child" style="display: none;">
                            </div>
                            <div class="passenger-type-option" data-passenger="<?php echo $i; ?>" data-type="infant">
                                <div class="type-radio" id="type-infant-<?php echo $i; ?>"></div>
                                <span class="type-label">Infant (0-2 years)</span>
                                <input type="radio" name="passenger_type[<?php echo $i; ?>]" value="infant" style="display: none;">
                            </div>
                        </div>
                        <div class="type-description" id="type-description-<?php echo $i; ?>">
                            <?php if($i === 1): ?>Primary passenger who will receive booking confirmation<?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endfor; ?>
                
                <!-- Terms and Conditions -->
                <div class="passenger-form-card">
                    <div class="form-group-custom">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="terms" required>
                            <label class="form-check-label" for="terms">
                                I agree to the <a href="terms.php" target="_blank">Terms & Conditions</a> and 
                                <a href="privacy.php" target="_blank">Privacy Policy</a>
                            </label>
                        </div>
                    </div>
                    <div class="form-group-custom">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="newsletter" checked>
                            <label class="form-check-label" for="newsletter">
                                Send me flight updates, promotional offers, and travel tips
                            </label>
                        </div>
                    </div>
                </div>
                
                <!-- Submit Section -->
                <div class="submit-section">
                    <button type="submit" name="pass_but" class="submit-btn" id="submitBtn">
                        <span class="spinner-border spinner-border-sm" id="spinner" role="status" aria-hidden="true"></span>
                        <span id="btn-text">
                            <i class="fas fa-arrow-right"></i> Proceed to Payment
                        </span>
                    </button>
                    <p class="text-muted mt-3">
                        <i class="fas fa-lock"></i> Your information is secured with 256-bit SSL encryption
                    </p>
                </div>
            </form>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('passengerForm');
        const submitBtn = document.getElementById('submitBtn');
        const btnText = document.getElementById('btn-text');
        const spinner = document.getElementById('spinner');
        const progressBar = document.getElementById('progressBar');
        
        // Auto-hide alerts
        setTimeout(() => {
            document.querySelectorAll('.alert-custom').forEach(alert => {
                alert.style.opacity = '0';
                alert.style.transition = 'opacity 0.3s ease';
                
                setTimeout(() => {
                    if (alert.parentNode) {
                        alert.parentNode.removeChild(alert);
                    }
                }, 300);
            });
        }, 5000);
        
        // Passenger type selection
        document.querySelectorAll('.passenger-type-option').forEach(option => {
            option.addEventListener('click', function() {
                const passengerNum = this.getAttribute('data-passenger');
                const type = this.getAttribute('data-type');
                
                // Update radio buttons
                document.querySelectorAll(`[data-passenger="${passengerNum}"] .type-radio`).forEach(radio => {
                    radio.classList.remove('selected');
                });
                this.querySelector('.type-radio').classList.add('selected');
                
                // Update hidden radio input
                document.querySelector(`input[name="passenger_type[${passengerNum}]"][value="${type}"]`).checked = true;
                
                // Update description
                const descriptions = {
                    'adult': 'Primary passenger who will receive booking confirmation',
                    'child': 'Children aged 2-11 years. Must travel with an adult.',
                    'infant': 'Infants aged 0-2 years. Must travel on an adult\'s lap.'
                };
                document.getElementById(`type-description-${passengerNum}`).textContent = descriptions[type];
            });
        });
        
        // Age calculation
        document.querySelectorAll('input[type="date"]').forEach((dateInput, index) => {
            const passengerNum = index + 1;
            const ageDisplay = document.getElementById(`age-display-${passengerNum}`);
            
            dateInput.addEventListener('change', function() {
                const dob = new Date(this.value);
                const today = new Date();
                let age = today.getFullYear() - dob.getFullYear();
                const monthDiff = today.getMonth() - dob.getMonth();
                
                if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < dob.getDate())) {
                    age--;
                }
                
                if (this.value && age >= 0) {
                    ageDisplay.innerHTML = `<i class="fas fa-birthday-cake"></i> <span>Age: ${age} years</span>`;
                    
                    // Auto-select passenger type based on age
                    if (age >= 12) {
                        document.querySelector(`[data-passenger="${passengerNum}"][data-type="adult"]`).click();
                    } else if (age >= 2) {
                        document.querySelector(`[data-passenger="${passengerNum}"][data-type="child"]`).click();
                    } else {
                        document.querySelector(`[data-passenger="${passengerNum}"][data-type="infant"]`).click();
                    }
                }
            });
        });
        
        // Form validation
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            let isValid = true;
            const passengers = <?php echo $passengers; ?>;
            
            // Validate each passenger
            for(let i = 1; i <= passengers; i++) {
                const firstName = document.getElementById(`firstname-${i}`);
                const lastName = document.getElementById(`lastname-${i}`);
                const mobile = document.getElementById(`mobile-${i}`);
                const dob = document.getElementById(`date-${i}`);
                
                // Clear previous errors
                document.getElementById(`firstname-error-${i}`).textContent = '';
                document.getElementById(`lastname-error-${i}`).textContent = '';
                document.getElementById(`mobile-error-${i}`).textContent = '';
                document.getElementById(`date-error-${i}`).textContent = '';
                
                // Validate first name
                if (!firstName.value.match(/^[A-Za-z\s]{2,50}$/)) {
                    document.getElementById(`firstname-error-${i}`).textContent = 'Please enter a valid first name (2-50 letters)';
                    firstName.classList.add('error');
                    isValid = false;
                } else {
                    firstName.classList.remove('error');
                    firstName.classList.add('success');
                }
                
                // Validate last name
                if (!lastName.value.match(/^[A-Za-z\s]{2,50}$/)) {
                    document.getElementById(`lastname-error-${i}`).textContent = 'Please enter a valid last name (2-50 letters)';
                    lastName.classList.add('error');
                    isValid = false;
                } else {
                    lastName.classList.remove('error');
                    lastName.classList.add('success');
                }
                
                // Validate mobile
                if (!mobile.value.match(/^[0-9]{10}$/)) {
                    document.getElementById(`mobile-error-${i}`).textContent = 'Please enter a valid 10-digit mobile number';
                    mobile.classList.add('error');
                    isValid = false;
                } else {
                    mobile.classList.remove('error');
                    mobile.classList.add('success');
                }
                
                // Validate date of birth
                if (!dob.value) {
                    document.getElementById(`date-error-${i}`).textContent = 'Please select date of birth';
                    dob.classList.add('error');
                    isValid = false;
                } else {
                    const selectedDate = new Date(dob.value);
                    const today = new Date();
                    
                    if (selectedDate > today) {
                        document.getElementById(`date-error-${i}`).textContent = 'Date of birth cannot be in the future';
                        dob.classList.add('error');
                        isValid = false;
                    } else {
                        const age = today.getFullYear() - selectedDate.getFullYear();
                        if (age < 0) {
                            document.getElementById(`date-error-${i}`).textContent = 'Invalid date of birth';
                            dob.classList.add('error');
                            isValid = false;
                        } else {
                            dob.classList.remove('error');
                            dob.classList.add('success');
                        }
                    }
                }
            }
            
            // Validate terms
            if (!document.getElementById('terms').checked) {
                alert('Please accept the Terms & Conditions to continue');
                isValid = false;
            }
            
            if (isValid) {
                // Show loading state
                submitBtn.disabled = true;
                btnText.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
                spinner.style.display = 'inline-block';
                progressBar.style.width = '100%';
                
                // Submit form
                setTimeout(() => {
                    form.submit();
                }, 500);
            } else {
                // Scroll to first error
                const firstError = document.querySelector('.error');
                if (firstError) {
                    firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }
        });
        
        // Real-time validation
        document.querySelectorAll('.form-input').forEach(input => {
            input.addEventListener('input', function() {
                this.classList.remove('error', 'success');
                
                // Remove error message
                const passengerNum = this.id.split('-')[1];
                const field = this.id.split('-')[0];
                const errorElement = document.getElementById(`${field}-error-${passengerNum}`);
                if (errorElement) {
                    errorElement.textContent = '';
                }
            });
        });
        
        // Update progress bar on scroll
        window.addEventListener('scroll', function() {
            const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
            const scrollHeight = document.documentElement.scrollHeight - window.innerHeight;
            const scrollPercent = (scrollTop / scrollHeight) * 100;
            
            if (scrollPercent > 50) {
                progressBar.style.width = '75%';
            } else {
                progressBar.style.width = '50%';
            }
        });
        
        // Auto-focus first input
        document.getElementById('firstname-1').focus();
        
        // Add animation to form cards
        document.querySelectorAll('.passenger-form-card').forEach((card, index) => {
            card.style.animationDelay = `${index * 0.1}s`;
            card.classList.add('animate__animated', 'animate__fadeInUp');
        });
    });
    
    // Handle browser back/forward cache
    window.addEventListener('pageshow', function(event) {
        if (event.persisted) {
            window.location.reload();
        }
    });
    </script>
</body>
</html>

<?php 
subview('footer.php');
mysqli_close($conn);
?>
