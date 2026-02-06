<?php 
session_start();
include_once 'helpers/helper.php'; 
subview('header.php');
require 'helpers/init_conn_db.php'; 

// Security headers
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: strict-origin-when-cross-origin");

// CSRF token for forms
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Error handling
$error_messages = [];
if(isset($_GET['error'])) {
    switch($_GET['error']) {
        case 'sameval':
            $error_messages[] = "Please select different departure and arrival cities";
            break;
        case 'seldep':
            $error_messages[] = "Please select a departure city";
            break;
        case 'selarr':
            $error_messages[] = "Please select an arrival city";
            break;
        case 'nodate':
            $error_messages[] = "Please select a departure date";
            break;
        case 'pastdate':
            $error_messages[] = "Departure date cannot be in the past";
            break;
        case 'futuredate':
            $error_messages[] = "Departure date cannot be more than 1 year in the future";
            break;
    }
}
?> 

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Book flights with AirTic - Your trusted flight booking partner for 2026">
    <meta name="keywords" content="flights, booking, travel, airtic, 2026">
    <title>AirTic 2026 - Online Flight Booking</title>
    
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
        --gradient-accent: linear-gradient(135deg, var(--accent-color) 0%, #7209b7 100%);
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
        background: linear-gradient(rgba(0, 0, 0, 0.85), rgba(0, 0, 0, 0.9)), 
                    url('assets/images/plane1.jpg') no-repeat center center fixed;
        background-size: cover;
        color: var(--light-color);
        min-height: 100vh;
        overflow-x: hidden;
    }
    
    /* Hero Section */
    .hero-section {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
        position: relative;
    }
    
    .hero-overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.6);
        backdrop-filter: blur(5px);
        z-index: 1;
    }
    
    .hero-content {
        position: relative;
        z-index: 2;
        max-width: 1400px;
        width: 100%;
    }
    
    .brand-header {
        text-align: center;
        margin-bottom: 40px;
        animation: fadeInDown 1s ease;
    }
    
    .brand-logo {
        width: 120px;
        height: 120px;
        margin-bottom: 20px;
        filter: drop-shadow(0 4px 12px rgba(0, 0, 0, 0.3));
        animation: pulse 2s infinite;
    }
    
    @keyframes pulse {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.05); }
    }
    
    .brand-title {
        font-family: 'product sans', 'Montserrat', sans-serif;
        font-size: 4rem;
        font-weight: 800;
        background: linear-gradient(135deg, #fff 0%, #4cc9f0 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        margin-bottom: 10px;
        text-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
    }
    
    .brand-subtitle {
        font-size: 1.2rem;
        color: rgba(255, 255, 255, 0.9);
        max-width: 600px;
        margin: 0 auto;
    }
    
    /* Search Container */
    .search-container {
        background: rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(15px);
        border-radius: 25px;
        border: 1px solid rgba(255, 255, 255, 0.2);
        padding: 40px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        margin-bottom: 60px;
        animation: fadeInUp 1s ease 0.3s both;
    }
    
    /* Tab Navigation */
    .booking-tabs {
        display: flex;
        gap: 10px;
        margin-bottom: 40px;
        border-bottom: 2px solid rgba(255, 255, 255, 0.1);
        padding-bottom: 20px;
    }
    
    .tab-btn {
        flex: 1;
        padding: 18px 25px;
        background: rgba(255, 255, 255, 0.1);
        border: none;
        border-radius: 15px;
        color: white;
        font-family: 'Montserrat', sans-serif;
        font-weight: 600;
        font-size: 1.1rem;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
    }
    
    .tab-btn:hover {
        background: rgba(255, 255, 255, 0.2);
        transform: translateY(-2px);
    }
    
    .tab-btn.active {
        background: var(--gradient-primary);
        box-shadow: 0 8px 25px rgba(67, 97, 238, 0.3);
    }
    
    /* Form Styles */
    .booking-form {
        display: none;
        animation: fadeIn 0.5s ease;
    }
    
    .booking-form.active {
        display: block;
    }
    
    .form-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 25px;
        margin-bottom: 30px;
    }
    
    .form-group {
        position: relative;
    }
    
    .form-label {
        display: block;
        margin-bottom: 10px;
        font-weight: 500;
        color: rgba(255, 255, 255, 0.9);
        font-size: 1rem;
    }
    
    .form-icon {
        position: absolute;
        left: 20px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--accent-color);
        font-size: 1.2rem;
        z-index: 2;
    }
    
    .form-select, .form-input, .form-date {
        width: 100%;
        padding: 18px 20px 18px 55px;
        background: rgba(255, 255, 255, 0.1);
        border: 2px solid rgba(255, 255, 255, 0.2);
        border-radius: 15px;
        color: white;
        font-size: 1rem;
        font-family: 'Poppins', sans-serif;
        transition: all 0.3s ease;
        appearance: none;
        cursor: pointer;
    }
    
    .form-select:focus, .form-input:focus, .form-date:focus {
        outline: none;
        border-color: var(--accent-color);
        box-shadow: 0 0 0 3px rgba(76, 201, 240, 0.1);
        background: rgba(255, 255, 255, 0.15);
    }
    
    .form-select {
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%234cc9f0' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 20px center;
        background-size: 16px;
    }
    
    .form-date::-webkit-calendar-picker-indicator {
        filter: invert(1);
        opacity: 0.7;
        cursor: pointer;
    }
    
    .passenger-selector {
        background: rgba(255, 255, 255, 0.1);
        border: 2px solid rgba(255, 255, 255, 0.2);
        border-radius: 15px;
        padding: 20px;
        margin-bottom: 30px;
    }
    
    .passenger-controls {
        display: flex;
        align-items: center;
        gap: 20px;
    }
    
    .passenger-btn {
        width: 45px;
        height: 45px;
        border-radius: 50%;
        border: 2px solid rgba(255, 255, 255, 0.3);
        background: rgba(255, 255, 255, 0.1);
        color: white;
        font-size: 1.5rem;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .passenger-btn:hover {
        background: rgba(255, 255, 255, 0.2);
        border-color: var(--accent-color);
    }
    
    .passenger-btn:disabled {
        opacity: 0.3;
        cursor: not-allowed;
    }
    
    .passenger-count {
        font-size: 2rem;
        font-weight: 700;
        min-width: 60px;
        text-align: center;
        color: white;
    }
    
    .passenger-label {
        font-size: 1.1rem;
        font-weight: 500;
    }
    
    .class-selector {
        display: flex;
        gap: 15px;
        margin-bottom: 30px;
    }
    
    .class-option {
        flex: 1;
        padding: 20px;
        background: rgba(255, 255, 255, 0.1);
        border: 2px solid rgba(255, 255, 255, 0.2);
        border-radius: 15px;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .class-option:hover {
        background: rgba(255, 255, 255, 0.15);
        transform: translateY(-3px);
    }
    
    .class-option.selected {
        background: var(--gradient-primary);
        border-color: var(--primary-color);
        box-shadow: 0 8px 25px rgba(67, 97, 238, 0.3);
    }
    
    .class-icon {
        font-size: 2.5rem;
        margin-bottom: 10px;
        display: block;
    }
    
    .class-name {
        font-weight: 600;
        font-size: 1.1rem;
        margin-bottom: 5px;
    }
    
    .class-price {
        font-size: 0.9rem;
        opacity: 0.8;
    }
    
    .search-btn {
        background: var(--gradient-accent);
        color: white;
        border: none;
        border-radius: 15px;
        padding: 20px 40px;
        font-size: 1.2rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        width: 100%;
        text-transform: uppercase;
        letter-spacing: 1px;
        font-family: 'Montserrat', sans-serif;
    }
    
    .search-btn:hover {
        transform: translateY(-3px);
        box-shadow: 0 15px 35px rgba(76, 201, 240, 0.3);
    }
    
    .search-btn:active {
        transform: translateY(-1px);
    }
    
    /* Features Section */
    .features-section {
        padding: 80px 20px;
        background: rgba(0, 0, 0, 0.7);
        backdrop-filter: blur(10px);
        position: relative;
    }
    
    .section-title {
        text-align: center;
        font-family: 'Montserrat', sans-serif;
        font-size: 2.5rem;
        font-weight: 700;
        margin-bottom: 60px;
        background: linear-gradient(135deg, #fff 0%, #4cc9f0 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }
    
    .features-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 40px;
        max-width: 1200px;
        margin: 0 auto;
    }
    
    .feature-card {
        background: rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(10px);
        border-radius: 20px;
        padding: 40px 30px;
        text-align: center;
        border: 1px solid rgba(255, 255, 255, 0.1);
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }
    
    .feature-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 5px;
        background: var(--gradient-primary);
    }
    
    .feature-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
        border-color: rgba(255, 255, 255, 0.3);
    }
    
    .feature-icon {
        font-size: 3.5rem;
        margin-bottom: 25px;
        background: var(--gradient-primary);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }
    
    .feature-title {
        font-family: 'Montserrat', sans-serif;
        font-size: 1.5rem;
        font-weight: 600;
        margin-bottom: 15px;
        color: white;
    }
    
    .feature-desc {
        color: rgba(255, 255, 255, 0.8);
        line-height: 1.6;
        font-size: 1rem;
    }
    
    /* Error Display */
    .alert-container {
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
        max-width: 400px;
    }
    
    .alert-custom {
        background: rgba(239, 68, 68, 0.9);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.2);
        border-radius: 15px;
        color: white;
        padding: 15px 20px;
        margin-bottom: 10px;
        animation: slideInRight 0.3s ease;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .alert-custom i {
        font-size: 1.2rem;
    }
    
    /* Responsive Design */
    @media (max-width: 992px) {
        .brand-title {
            font-size: 3rem;
        }
        
        .search-container {
            padding: 30px;
        }
        
        .features-grid {
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
        }
    }
    
    @media (max-width: 768px) {
        .brand-title {
            font-size: 2.5rem;
        }
        
        .brand-logo {
            width: 100px;
            height: 100px;
        }
        
        .search-container {
            padding: 20px;
        }
        
        .tab-btn {
            padding: 15px;
            font-size: 1rem;
        }
        
        .form-grid {
            grid-template-columns: 1fr;
            gap: 20px;
        }
        
        .section-title {
            font-size: 2rem;
        }
    }
    
    @media (max-width: 576px) {
        .hero-section {
            padding: 10px;
        }
        
        .brand-title {
            font-size: 2rem;
        }
        
        .brand-subtitle {
            font-size: 1rem;
        }
        
        .tab-btn {
            font-size: 0.9rem;
            padding: 12px;
        }
        
        .class-selector {
            flex-direction: column;
        }
        
        .feature-card {
            padding: 30px 20px;
        }
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
    </style>
</head>
<body>
    <!-- Error Messages -->
    <?php if (!empty($error_messages)): ?>
    <div class="alert-container">
        <?php foreach ($error_messages as $error): ?>
        <div class="alert-custom">
            <i class="fas fa-exclamation-circle"></i>
            <span><?php echo htmlspecialchars($error); ?></span>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    
    <!-- Hero Section -->
    <section class="hero-section">
        <div class="hero-overlay"></div>
        <div class="hero-content">
            <!-- Brand Header -->
            <div class="brand-header">
                <img src="assets/images/airtic.png" alt="AirTic Logo" class="brand-logo">
                <h1 class="brand-title">AirTic 2026</h1>
                <p class="brand-subtitle">Your gateway to seamless air travel experiences. Book smarter, fly better.</p>
            </div>
            
            <!-- Search Container -->
            <div class="search-container">
                <!-- Tab Navigation -->
                <div class="booking-tabs">
                    <button class="tab-btn active" data-tab="roundtrip">
                        <i class="fas fa-exchange-alt"></i> Round Trip
                    </button>
                    <button class="tab-btn" data-tab="oneway">
                        <i class="fas fa-long-arrow-alt-right"></i> One Way
                    </button>
                    <button class="tab-btn" data-tab="multicity">
                        <i class="fas fa-map-marked-alt"></i> Multi-City
                    </button>
                </div>
                
                <!-- Round Trip Form -->
                <form action="book_flight.php" method="post" class="booking-form active" id="roundtrip">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input type="hidden" name="type" value="round">
                    
                    <div class="form-grid">
                        <!-- Departure City -->
                        <div class="form-group">
                            <label class="form-label"><i class="fas fa-plane-departure me-2"></i> From</label>
                            <i class="form-icon fas fa-map-marker-alt"></i>
                            <select class="form-select" name="dep_city" required>
                                <option value="0" selected disabled>Select departure city</option>
                                <?php
                                $sql = 'SELECT * FROM Cities ORDER BY city ASC';
                                $stmt = mysqli_stmt_init($conn);
                                mysqli_stmt_prepare($stmt, $sql);         
                                mysqli_stmt_execute($stmt);          
                                $result = mysqli_stmt_get_result($stmt);
                                while ($row = mysqli_fetch_assoc($result)) {
                                    echo '<option value="'.htmlspecialchars($row['city']).'">'.htmlspecialchars($row['city']).'</option>';
                                }
                                ?>
                            </select>
                        </div>
                        
                        <!-- Arrival City -->
                        <div class="form-group">
                            <label class="form-label"><i class="fas fa-plane-arrival me-2"></i> To</label>
                            <i class="form-icon fas fa-map-marker-alt"></i>
                            <select class="form-select" name="arr_city" required>
                                <option value="0" selected disabled>Select arrival city</option>
                                <?php
                                mysqli_data_seek($result, 0);
                                while ($row = mysqli_fetch_assoc($result)) {
                                    echo '<option value="'.htmlspecialchars($row['city']).'">'.htmlspecialchars($row['city']).'</option>';
                                }
                                ?>
                            </select>
                        </div>
                        
                        <!-- Departure Date -->
                        <div class="form-group">
                            <label class="form-label"><i class="fas fa-calendar-alt me-2"></i> Depart Date</label>
                            <i class="form-icon fas fa-calendar"></i>
                            <input type="date" class="form-date" name="dep_date" required 
                                   min="<?php echo date('Y-m-d'); ?>" 
                                   max="<?php echo date('Y-m-d', strtotime('+1 year')); ?>">
                        </div>
                        
                        <!-- Return Date -->
                        <div class="form-group">
                            <label class="form-label"><i class="fas fa-calendar-alt me-2"></i> Return Date</label>
                            <i class="form-icon fas fa-calendar"></i>
                            <input type="date" class="form-date" name="ret_date" required 
                                   min="<?php echo date('Y-m-d'); ?>" 
                                   max="<?php echo date('Y-m-d', strtotime('+1 year')); ?>">
                        </div>
                    </div>
                    
                    <!-- Passenger Selector -->
                    <div class="passenger-selector">
                        <label class="form-label mb-3"><i class="fas fa-users me-2"></i> Passengers</label>
                        <div class="passenger-controls">
                            <button type="button" class="passenger-btn" id="passenger-minus">
                                <i class="fas fa-minus"></i>
                            </button>
                            <div class="passenger-count" id="passenger-display">1</div>
                            <input type="hidden" name="passengers" id="passengers" value="1">
                            <button type="button" class="passenger-btn" id="passenger-plus">
                                <i class="fas fa-plus"></i>
                            </button>
                            <span class="passenger-label">Traveler(s)</span>
                        </div>
                    </div>
                    
                    <!-- Class Selector -->
                    <div class="class-selector">
                        <div class="class-option selected" data-class="E">
                            <i class="class-icon fas fa-chair"></i>
                            <div class="class-name">Economy</div>
                            <div class="class-price">Best Price</div>
                        </div>
                        <div class="class-option" data-class="B">
                            <i class="class-icon fas fa-crown"></i>
                            <div class="class-name">Business</div>
                            <div class="class-price">Extra Comfort</div>
                        </div>
                        <div class="class-option" data-class="F">
                            <i class="class-icon fas fa-star"></i>
                            <div class="class-name">First Class</div>
                            <div class="class-price">Luxury Experience</div>
                        </div>
                        <input type="hidden" name="f_class" id="f_class" value="E">
                    </div>
                    
                    <button type="submit" name="search_but" class="search-btn">
                        <i class="fas fa-search me-2"></i> Search Flights
                    </button>
                </form>
                
                <!-- One Way Form -->
                <form action="book_flight.php" method="post" class="booking-form" id="oneway">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input type="hidden" name="type" value="one">
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label"><i class="fas fa-plane-departure me-2"></i> From</label>
                            <i class="form-icon fas fa-map-marker-alt"></i>
                            <select class="form-select" name="dep_city" required>
                                <option value="0" selected disabled>Select departure city</option>
                                <?php
                                mysqli_data_seek($result, 0);
                                while ($row = mysqli_fetch_assoc($result)) {
                                    echo '<option value="'.htmlspecialchars($row['city']).'">'.htmlspecialchars($row['city']).'</option>';
                                }
                                ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label"><i class="fas fa-plane-arrival me-2"></i> To</label>
                            <i class="form-icon fas fa-map-marker-alt"></i>
                            <select class="form-select" name="arr_city" required>
                                <option value="0" selected disabled>Select arrival city</option>
                                <?php
                                mysqli_data_seek($result, 0);
                                while ($row = mysqli_fetch_assoc($result)) {
                                    echo '<option value="'.htmlspecialchars($row['city']).'">'.htmlspecialchars($row['city']).'</option>';
                                }
                                ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label"><i class="fas fa-calendar-alt me-2"></i> Depart Date</label>
                            <i class="form-icon fas fa-calendar"></i>
                            <input type="date" class="form-date" name="dep_date" required 
                                   min="<?php echo date('Y-m-d'); ?>" 
                                   max="<?php echo date('Y-m-d', strtotime('+1 year')); ?>">
                        </div>
                    </div>
                    
                    <div class="passenger-selector">
                        <label class="form-label mb-3"><i class="fas fa-users me-2"></i> Passengers</label>
                        <div class="passenger-controls">
                            <button type="button" class="passenger-btn passenger-minus-one">
                                <i class="fas fa-minus"></i>
                            </button>
                            <div class="passenger-count passenger-display-one">1</div>
                            <input type="hidden" name="passengers" class="passengers-one" value="1">
                            <button type="button" class="passenger-btn passenger-plus-one">
                                <i class="fas fa-plus"></i>
                            </button>
                            <span class="passenger-label">Traveler(s)</span>
                        </div>
                    </div>
                    
                    <div class="class-selector">
                        <div class="class-option selected" data-class-one="E">
                            <i class="class-icon fas fa-chair"></i>
                            <div class="class-name">Economy</div>
                            <div class="class-price">Best Price</div>
                        </div>
                        <div class="class-option" data-class-one="B">
                            <i class="class-icon fas fa-crown"></i>
                            <div class="class-name">Business</div>
                            <div class="class-price">Extra Comfort</div>
                        </div>
                        <div class="class-option" data-class-one="F">
                            <i class="class-icon fas fa-star"></i>
                            <div class="class-name">First Class</div>
                            <div class="class-price">Luxury Experience</div>
                        </div>
                        <input type="hidden" name="f_class" class="f_class_one" value="E">
                    </div>
                    
                    <button type="submit" name="search_but" class="search-btn">
                        <i class="fas fa-search me-2"></i> Search Flights
                    </button>
                </form>
                
                <!-- Multi-City Form -->
                <form action="book_flight.php" method="post" class="booking-form" id="multicity">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input type="hidden" name="type" value="multi">
                    <div id="multi-city-container">
                        <!-- Multi-city segments will be added here -->
                        <div class="multi-city-segment">
                            <div class="form-grid">
                                <div class="form-group">
                                    <label class="form-label">From</label>
                                    <i class="form-icon fas fa-map-marker-alt"></i>
                                    <select class="form-select" name="dep_city_multi[]" required>
                                        <option value="0" selected disabled>Select city</option>
                                        <?php
                                        mysqli_data_seek($result, 0);
                                        while ($row = mysqli_fetch_assoc($result)) {
                                            echo '<option value="'.htmlspecialchars($row['city']).'">'.htmlspecialchars($row['city']).'</option>';
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">To</label>
                                    <i class="form-icon fas fa-map-marker-alt"></i>
                                    <select class="form-select" name="arr_city_multi[]" required>
                                        <option value="0" selected disabled>Select city</option>
                                        <?php
                                        mysqli_data_seek($result, 0);
                                        while ($row = mysqli_fetch_assoc($result)) {
                                            echo '<option value="'.htmlspecialchars($row['city']).'">'.htmlspecialchars($row['city']).'</option>';
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Date</label>
                                    <i class="form-icon fas fa-calendar"></i>
                                    <input type="date" class="form-date" name="date_multi[]" required 
                                           min="<?php echo date('Y-m-d'); ?>" 
                                           max="<?php echo date('Y-m-d', strtotime('+1 year')); ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <button type="button" class="btn btn-outline-light mb-4" id="add-segment">
                        <i class="fas fa-plus me-2"></i> Add Another Flight
                    </button>
                    
                    <div class="passenger-selector">
                        <label class="form-label mb-3"><i class="fas fa-users me-2"></i> Passengers</label>
                        <div class="passenger-controls">
                            <button type="button" class="passenger-btn passenger-minus-multi">
                                <i class="fas fa-minus"></i>
                            </button>
                            <div class="passenger-count passenger-display-multi">1</div>
                            <input type="hidden" name="passengers" class="passengers-multi" value="1">
                            <button type="button" class="passenger-btn passenger-plus-multi">
                                <i class="fas fa-plus"></i>
                            </button>
                            <span class="passenger-label">Traveler(s)</span>
                        </div>
                    </div>
                    
                    <div class="class-selector">
                        <div class="class-option selected" data-class-multi="E">
                            <i class="class-icon fas fa-chair"></i>
                            <div class="class-name">Economy</div>
                            <div class="class-price">Best Price</div>
                        </div>
                        <div class="class-option" data-class-multi="B">
                            <i class="class-icon fas fa-crown"></i>
                            <div class="class-name">Business</div>
                            <div class="class-price">Extra Comfort</div>
                        </div>
                        <div class="class-option" data-class-multi="F">
                            <i class="class-icon fas fa-star"></i>
                            <div class="class-name">First Class</div>
                            <div class="class-price">Luxury Experience</div>
                        </div>
                        <input type="hidden" name="f_class" class="f_class_multi" value="E">
                    </div>
                    
                    <button type="submit" name="search_but" class="search-btn">
                        <i class="fas fa-search me-2"></i> Search Multi-City Flights
                    </button>
                </form>
            </div>
        </div>
    </section>
    
    <!-- Features Section -->
    <section class="features-section">
        <div class="container">
            <h2 class="section-title">Why Choose AirTic 2026?</h2>
            <div class="features-grid">
                <div class="feature-card">
                    <i class="feature-icon fas fa-bolt"></i>
                    <h3 class="feature-title">Lightning Fast Booking</h3>
                    <p class="feature-desc">Book flights in under 60 seconds with our streamlined process and AI-powered search.</p>
                </div>
                
                <div class="feature-card">
                    <i class="feature-icon fas fa-shield-alt"></i>
                    <h3 class="feature-title">Secure & Protected</h3>
                    <p class="feature-desc">Your data is protected with military-grade encryption and GDPR compliance.</p>
                </div>
                
                <div class="feature-card">
                    <i class="feature-icon fas fa-percentage"></i>
                    <h3 class="feature-title">Best Price Guarantee</h3>
                    <p class="feature-desc">Find the lowest fares with our price comparison technology across 500+ airlines.</p>
                </div>
                
                <div class="feature-card">
                    <i class="feature-icon fas fa-headset"></i>
                    <h3 class="feature-title">24/7 Support</h3>
                    <p class="feature-desc">Round-the-clock customer support via chat, phone, and email in multiple languages.</p>
                </div>
                
                <div class="feature-card">
                    <i class="feature-icon fas fa-leaf"></i>
                    <h3 class="feature-title">Eco-Friendly Travel</h3>
                    <p class="feature-desc">Offset your carbon footprint with our sustainable travel options.</p>
                </div>
                
                <div class="feature-card">
                    <i class="feature-icon fas fa-mobile-alt"></i>
                    <h3 class="feature-title">Mobile First</h3>
                    <p class="feature-desc">Seamless experience across all devices with our progressive web app.</p>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Footer -->
    <footer class="py-5" style="background: rgba(0, 0, 0, 0.8);">
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="d-flex align-items-center mb-3">
                        <img src="assets/images/airtic.png" alt="AirTic Logo" height="50" class="me-3">
                        <h4 class="mb-0" style="font-family: 'product sans';">AirTic 2026</h4>
                    </div>
                    <p class="text-muted">Redefining air travel with innovation, security, and exceptional service.</p>
                    <div class="social-icons mt-3">
                        <a href="#" class="text-light me-3"><i class="fab fa-facebook fa-lg"></i></a>
                        <a href="#" class="text-light me-3"><i class="fab fa-twitter fa-lg"></i></a>
                        <a href="#" class="text-light me-3"><i class="fab fa-instagram fa-lg"></i></a>
                        <a href="#" class="text-light"><i class="fab fa-linkedin fa-lg"></i></a>
                    </div>
                </div>
                <div class="col-md-2 col-6 mb-4">
                    <h5 class="mb-3">Company</h5>
                    <ul class="list-unstyled">
                        <li><a href="about.php" class="text-muted text-decoration-none">About Us</a></li>
                        <li><a href="careers.php" class="text-muted text-decoration-none">Careers</a></li>
                        <li><a href="press.php" class="text-muted text-decoration-none">Press</a></li>
                        <li><a href="blog.php" class="text-muted text-decoration-none">Blog</a></li>
                    </ul>
                </div>
                <div class="col-md-2 col-6 mb-4">
                    <h5 class="mb-3">Support</h5>
                    <ul class="list-unstyled">
                        <li><a href="help.php" class="text-muted text-decoration-none">Help Center</a></li>
                        <li><a href="contact.php" class="text-muted text-decoration-none">Contact Us</a></li>
                        <li><a href="faq.php" class="text-muted text-decoration-none">FAQ</a></li>
                        <li><a href="feedback.php" class="text-muted text-decoration-none">Feedback</a></li>
                    </ul>
                </div>
                <div class="col-md-2 col-6 mb-4">
                    <h5 class="mb-3">Legal</h5>
                    <ul class="list-unstyled">
                        <li><a href="privacy.php" class="text-muted text-decoration-none">Privacy Policy</a></li>
                        <li><a href="terms.php" class="text-muted text-decoration-none">Terms of Service</a></li>
                        <li><a href="cookies.php" class="text-muted text-decoration-none">Cookie Policy</a></li>
                        <li><a href="accessibility.php" class="text-muted text-decoration-none">Accessibility</a></li>
                    </ul>
                </div>
                <div class="col-md-2 col-6 mb-4">
                    <h5 class="mb-3">Download</h5>
                    <div class="app-buttons">
                        <a href="#" class="d-block mb-2">
                            <img src="https://upload.wikimedia.org/wikipedia/commons/7/78/Google_Play_Store_badge_EN.svg" alt="Google Play" height="40">
                        </a>
                        <a href="#" class="d-block">
                            <img src="https://upload.wikimedia.org/wikipedia/commons/3/3c/Download_on_the_App_Store_Badge.svg" alt="App Store" height="40">
                        </a>
                    </div>
                </div>
            </div>
            <hr class="my-4" style="border-color: rgba(255,255,255,0.1);">
            <div class="row">
                <div class="col-md-6">
                    <p class="text-muted mb-0">
                        &copy; 2024-2026 AirTic Flight Booking System. All rights reserved.
                    </p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="text-muted mb-0">
                        Developed with <i class="fas fa-heart text-danger"></i> by MD TAJUL ISLAM
                    </p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <script>
    $(document).ready(function() {
        // Tab Switching
        $('.tab-btn').on('click', function() {
            const tabId = $(this).data('tab');
            
            // Update active tab button
            $('.tab-btn').removeClass('active');
            $(this).addClass('active');
            
            // Show corresponding form
            $('.booking-form').removeClass('active');
            $('#' + tabId).addClass('active');
        });
        
        // Passenger Counter for Round Trip
        let passengerCount = 1;
        const maxPassengers = 9;
        const minPassengers = 1;
        
        $('#passenger-plus').on('click', function() {
            if (passengerCount < maxPassengers) {
                passengerCount++;
                updatePassengerDisplay();
            }
        });
        
        $('#passenger-minus').on('click', function() {
            if (passengerCount > minPassengers) {
                passengerCount--;
                updatePassengerDisplay();
            }
        });
        
        function updatePassengerDisplay() {
            $('#passenger-display').text(passengerCount);
            $('#passengers').val(passengerCount);
            
            // Update button states
            $('#passenger-minus').prop('disabled', passengerCount <= minPassengers);
            $('#passenger-plus').prop('disabled', passengerCount >= maxPassengers);
        }
        
        // Passenger Counter for One Way
        $('.passenger-plus-one').on('click', function() {
            let count = parseInt($('.passenger-display-one').text());
            if (count < maxPassengers) {
                count++;
                $('.passenger-display-one').text(count);
                $('.passengers-one').val(count);
            }
        });
        
        $('.passenger-minus-one').on('click', function() {
            let count = parseInt($('.passenger-display-one').text());
            if (count > minPassengers) {
                count--;
                $('.passenger-display-one').text(count);
                $('.passengers-one').val(count);
            }
        });
        
        // Passenger Counter for Multi-City
        $('.passenger-plus-multi').on('click', function() {
            let count = parseInt($('.passenger-display-multi').text());
            if (count < maxPassengers) {
                count++;
                $('.passenger-display-multi').text(count);
                $('.passengers-multi').val(count);
            }
        });
        
        $('.passenger-minus-multi').on('click', function() {
            let count = parseInt($('.passenger-display-multi').text());
            if (count > minPassengers) {
                count--;
                $('.passenger-display-multi').text(count);
                $('.passengers-multi').val(count);
            }
        });
        
        // Class Selection
        $('.class-option').on('click', function() {
            const formType = $(this).closest('form').attr('id');
            const selectedClass = $(this).data('class') || $(this).data('class-one') || $(this).data('class-multi');
            
            // Update UI
            $(this).closest('.class-selector').find('.class-option').removeClass('selected');
            $(this).addClass('selected');
            
            // Update hidden input
            if (formType === 'roundtrip') {
                $('#f_class').val(selectedClass);
            } else if (formType === 'oneway') {
                $('.f_class_one').val(selectedClass);
            } else {
                $('.f_class_multi').val(selectedClass);
            }
        });
        
        // Add multi-city segment
        let segmentCount = 1;
        $('#add-segment').on('click', function() {
            if (segmentCount < 5) {
                segmentCount++;
                const newSegment = `
                <div class="multi-city-segment mt-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="mb-0">Flight Segment ${segmentCount}</h6>
                        <button type="button" class="btn btn-sm btn-outline-danger remove-segment">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">From</label>
                            <i class="form-icon fas fa-map-marker-alt"></i>
                            <select class="form-select" name="dep_city_multi[]" required>
                                <option value="0" selected disabled>Select city</option>
                                <?php
                                mysqli_data_seek($result, 0);
                                while ($row = mysqli_fetch_assoc($result)) {
                                    echo '<option value="'.htmlspecialchars($row['city']).'">'.htmlspecialchars($row['city']).'</option>';
                                }
                                ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">To</label>
                            <i class="form-icon fas fa-map-marker-alt"></i>
                            <select class="form-select" name="arr_city_multi[]" required>
                                <option value="0" selected disabled>Select city</option>
                                <?php
                                mysqli_data_seek($result, 0);
                                while ($row = mysqli_fetch_assoc($result)) {
                                    echo '<option value="'.htmlspecialchars($row['city']).'">'.htmlspecialchars($row['city']).'</option>';
                                }
                                ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Date</label>
                            <i class="form-icon fas fa-calendar"></i>
                            <input type="date" class="form-date" name="date_multi[]" required 
                                   min="<?php echo date('Y-m-d'); ?>" 
                                   max="<?php echo date('Y-m-d', strtotime('+1 year')); ?>">
                        </div>
                    </div>
                </div>
                `;
                $('#multi-city-container').append(newSegment);
                
                // Update date min values for new segments
                updateDateMins();
            } else {
                alert('Maximum 5 flight segments allowed.');
            }
        });
        
        // Remove multi-city segment
        $(document).on('click', '.remove-segment', function() {
            if ($('.multi-city-segment').length > 1) {
                $(this).closest('.multi-city-segment').remove();
                segmentCount--;
                // Renumber segments
                $('.multi-city-segment').each(function(index) {
                    $(this).find('h6').text(`Flight Segment ${index + 1}`);
                });
            }
        });
        
        // Date validation
        function updateDateMins() {
            const today = new Date().toISOString().split('T')[0];
            const maxDate = new Date();
            maxDate.setFullYear(maxDate.getFullYear() + 1);
            const maxDateStr = maxDate.toISOString().split('T')[0];
            
            $('input[type="date"]').each(function() {
                $(this).attr('min', today);
                $(this).attr('max', maxDateStr);
            });
        }
        
        // Initialize date restrictions
        updateDateMins();
        
        // Form validation
        $('form').on('submit', function(e) {
            const depCity = $(this).find('select[name*="dep_city"]').val();
            const arrCity = $(this).find('select[name*="arr_city"]').val();
            
            if (depCity === '0' || depCity === null) {
                e.preventDefault();
                showError('Please select a departure city');
                return;
            }
            
            if (arrCity === '0' || arrCity === null) {
                e.preventDefault();
                showError('Please select an arrival city');
                return;
            }
            
            if (depCity === arrCity) {
                e.preventDefault();
                showError('Departure and arrival cities cannot be the same');
                return;
            }
        });
        
        // Error display function
        function showError(message) {
            // Remove existing error alerts
            $('.alert-custom').remove();
            
            // Create new error alert
            const alertDiv = $(`
                <div class="alert-custom">
                    <i class="fas fa-exclamation-circle"></i>
                    <span>${message}</span>
                </div>
            `);
            
            // Add to container
            $('.alert-container').append(alertDiv);
            
            // Auto remove after 5 seconds
            setTimeout(() => {
                alertDiv.remove();
            }, 5000);
        }
        
        // Auto-hide existing alerts
        setTimeout(() => {
            $('.alert-custom').remove();
        }, 5000);
        
        // Animate elements on scroll
        function animateOnScroll() {
            const features = document.querySelectorAll('.feature-card');
            
            features.forEach((feature, index) => {
                const featurePosition = feature.getBoundingClientRect().top;
                const screenPosition = window.innerHeight / 1.2;
                
                if (featurePosition < screenPosition) {
                    setTimeout(() => {
                        feature.style.opacity = '1';
                        feature.style.transform = 'translateY(0)';
                    }, index * 100);
                }
            });
        }
        
        // Set initial styles for animation
        $('.feature-card').css({
            opacity: '0',
            transform: 'translateY(20px)',
            transition: 'opacity 0.5s ease, transform 0.5s ease'
        });
        
        // Trigger animation on load and scroll
        window.addEventListener('load', animateOnScroll);
        window.addEventListener('scroll', animateOnScroll);
        
        // Set today as default for departure date
        const today = new Date().toISOString().split('T')[0];
        $('input[name="dep_date"]').val(today);
        
        // Set tomorrow as default for return date
        const tomorrow = new Date();
        tomorrow.setDate(tomorrow.getDate() + 1);
        $('input[name="ret_date"]').val(tomorrow.toISOString().split('T')[0]);
    });
    </script>
</body>
</html>

<?php subview('footer.php'); ?>
