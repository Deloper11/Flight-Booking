<?php
session_start();
require_once '../helpers/init_conn_db.php';
require_once '../helpers/admin_auth.php';
require_once '../helpers/flight_helpers.php';

// Check admin authentication
if (!isset($_SESSION['adminId'])) {
    header('Location: login.php');
    exit();
}

// Check admin permissions
if (!checkAdminPermission('manage_flights')) {
    header('Location: dashboard.php?error=unauthorized');
    exit();
}

// Handle form submissions
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $flightData = sanitizeFlightData($_POST);
    
    if (validateFlightData($flightData)) {
        $result = createFlight($conn, $flightData);
        
        if ($result['success']) {
            $success = 'Flight successfully added!';
            // Clear form data
            $flightData = [];
        } else {
            $error = $result['message'];
        }
    } else {
        $error = 'Please fill all required fields correctly.';
    }
}

// Get airline and city data for dropdowns
$airlines = getAirlines($conn);
$cities = getCities($conn);
$aircrafts = getAircrafts($conn);

// Calculate default dates (tomorrow for departure, day after tomorrow for arrival)
$defaultDeparture = date('Y-m-d', strtotime('+1 day'));
$defaultArrival = date('Y-m-d', strtotime('+2 days'));
$defaultDepartureTime = '08:00';
$defaultArrivalTime = '10:00';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Flight | Admin Dashboard 2026</title>
    
    <!-- Modern CSS Framework -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@eonasdan/tempus-dominus@6.7.0/dist/css/tempus-dominus.min.css">
    
    <!-- Custom Admin CSS -->
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="../assets/css/flight-form.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Space+Grotesk:wght@400;500;600;700&family=JetBrains+Mono:wght@300;400&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --admin-primary: #4361ee;
            --admin-secondary: #3a0ca3;
            --admin-success: #06d6a0;
            --admin-warning: #ffd166;
            --admin-danger: #ef476f;
            --admin-dark: #121826;
            --admin-light: #f8f9fa;
            --admin-gray: #6c757d;
            --admin-border: #2d3748;
            --admin-surface: #1a202c;
            --admin-surface-light: #2d3748;
            --admin-text: #e2e8f0;
            --admin-text-secondary: #a0aec0;
            --admin-gradient: linear-gradient(135deg, #4361ee 0%, #3a0ca3 100%);
            --admin-gradient-light: linear-gradient(135deg, #4cc9f0 0%, #4361ee 100%);
            --admin-glass: rgba(26, 32, 44, 0.7);
            --admin-glass-border: rgba(255, 255, 255, 0.1);
            --admin-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.3);
            --admin-shadow-lg: 0 20px 25px -5px rgba(0, 0, 0, 0.4);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: 
                linear-gradient(rgba(15, 23, 42, 0.95), rgba(15, 23, 42, 0.98)),
                url('../assets/images/plane3.jpg') no-repeat center center fixed;
            background-size: cover;
            background-blend-mode: overlay;
            color: var(--admin-text);
            min-height: 100vh;
            position: relative;
        }
        
        /* Animated Background */
        .bg-animation {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: var(--admin-gradient);
            opacity: 0.05;
            z-index: -1;
            animation: gradientShift 20s ease infinite;
        }
        
        @keyframes gradientShift {
            0%, 100% { transform: scale(1); opacity: 0.05; }
            50% { transform: scale(1.1); opacity: 0.08; }
        }
        
        /* Main Container */
        .admin-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        /* Header */
        .page-header {
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--admin-border);
        }
        
        .page-title {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 2.5rem;
            font-weight: 700;
            background: linear-gradient(135deg, #fff 0%, #a5b4fc 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 0.5rem;
        }
        
        .page-subtitle {
            color: var(--admin-text-secondary);
            font-size: 1.1rem;
        }
        
        /* Form Container */
        .form-container {
            background: var(--admin-glass);
            backdrop-filter: blur(20px);
            border: 1px solid var(--admin-glass-border);
            border-radius: 24px;
            padding: 2rem;
            box-shadow: var(--admin-shadow-lg);
            position: relative;
            overflow: hidden;
        }
        
        .form-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: var(--admin-gradient);
        }
        
        /* Form Sections */
        .form-section {
            margin-bottom: 2.5rem;
        }
        
        .section-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid var(--admin-border);
        }
        
        .section-icon {
            width: 50px;
            height: 50px;
            background: rgba(67, 97, 238, 0.1);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: var(--admin-primary);
        }
        
        .section-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: white;
        }
        
        /* Form Grid */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 1rem;
        }
        
        /* Form Groups */
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-label {
            display: block;
            color: var(--admin-text);
            font-weight: 500;
            margin-bottom: 0.5rem;
            font-size: 0.95rem;
        }
        
        .form-label .required {
            color: var(--admin-danger);
        }
        
        .form-control-wrapper {
            position: relative;
        }
        
        .form-control, .form-select, .form-input {
            width: 100%;
            background: var(--admin-surface-light);
            border: 2px solid var(--admin-border);
            border-radius: 12px;
            color: var(--admin-text);
            padding: 0.875rem 1rem;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .form-control:focus, .form-select:focus, .form-input:focus {
            outline: none;
            border-color: var(--admin-primary);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
            background: var(--admin-surface);
        }
        
        .form-control:hover, .form-select:hover {
            border-color: var(--admin-primary);
        }
        
        .input-icon {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--admin-text-secondary);
        }
        
        /* Form Help Text */
        .form-help {
            display: block;
            margin-top: 0.5rem;
            color: var(--admin-text-secondary);
            font-size: 0.875rem;
        }
        
        /* Date/Time Picker */
        .datetime-group {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        
        /* Route Visualizer */
        .route-visualizer {
            background: var(--admin-surface);
            border-radius: 16px;
            padding: 2rem;
            margin-bottom: 2rem;
            border: 1px solid var(--admin-border);
        }
        
        .route-path {
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: relative;
            padding: 1rem 0;
        }
        
        .route-point {
            text-align: center;
            position: relative;
            z-index: 2;
        }
        
        .route-dot {
            width: 24px;
            height: 24px;
            background: var(--admin-primary);
            border-radius: 50%;
            margin: 0 auto 0.5rem;
            border: 4px solid var(--admin-surface);
        }
        
        .route-dot.destination {
            background: var(--admin-success);
        }
        
        .route-line {
            position: absolute;
            top: 50%;
            left: 10%;
            right: 10%;
            height: 2px;
            background: var(--admin-border);
            z-index: 1;
        }
        
        .route-line::after {
            content: '';
            position: absolute;
            top: 50%;
            right: 0;
            width: 12px;
            height: 12px;
            border-right: 2px solid var(--admin-border);
            border-top: 2px solid var(--admin-border);
            transform: translateY(-50%) rotate(45deg);
        }
        
        .city-name {
            font-weight: 600;
            color: white;
        }
        
        .airport-code {
            color: var(--admin-text-secondary);
            font-size: 0.875rem;
        }
        
        /* Flight Details Card */
        .flight-details-card {
            background: var(--admin-surface);
            border-radius: 16px;
            padding: 1.5rem;
            border: 1px solid var(--admin-border);
            margin-bottom: 2rem;
        }
        
        .details-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
        }
        
        .detail-item {
            display: flex;
            flex-direction: column;
        }
        
        .detail-label {
            color: var(--admin-text-secondary);
            font-size: 0.875rem;
            margin-bottom: 0.25rem;
        }
        
        .detail-value {
            color: white;
            font-weight: 600;
            font-size: 1.1rem;
        }
        
        /* Price Calculator */
        .price-calculator {
            background: var(--admin-surface);
            border-radius: 16px;
            padding: 1.5rem;
            border: 1px solid var(--admin-border);
            margin-bottom: 2rem;
        }
        
        .price-breakdown {
            margin-top: 1rem;
        }
        
        .price-item {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            border-bottom: 1px solid var(--admin-border);
        }
        
        .price-item:last-child {
            border-bottom: none;
            font-weight: 600;
            color: var(--admin-success);
        }
        
        /* Form Actions */
        .form-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            padding-top: 2rem;
            border-top: 1px solid var(--admin-border);
        }
        
        .btn {
            padding: 0.875rem 2rem;
            border-radius: 12px;
            border: none;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        
        .btn-primary {
            background: var(--admin-gradient);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: var(--admin-shadow);
        }
        
        .btn-secondary {
            background: var(--admin-surface-light);
            color: var(--admin-text);
            border: 1px solid var(--admin-border);
        }
        
        .btn-secondary:hover {
            background: var(--admin-surface);
            transform: translateY(-2px);
        }
        
        /* Alerts */
        .alert {
            padding: 1rem 1.5rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            animation: slideDown 0.3s ease;
        }
        
        .alert-success {
            background: rgba(6, 214, 160, 0.1);
            border: 1px solid rgba(6, 214, 160, 0.3);
            color: #06d6a0;
        }
        
        .alert-error {
            background: rgba(239, 71, 111, 0.1);
            border: 1px solid rgba(239, 71, 111, 0.3);
            color: #ef476f;
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
        
        /* Loading Overlay */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(18, 24, 38, 0.9);
            backdrop-filter: blur(8px);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        }
        
        .loading-overlay.active {
            display: flex;
        }
        
        .spinner {
            width: 60px;
            height: 60px;
            border: 4px solid rgba(67, 97, 238, 0.1);
            border-radius: 50%;
            border-top-color: var(--admin-primary);
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .admin-container {
                padding: 1rem;
            }
            
            .page-title {
                font-size: 2rem;
            }
            
            .form-container {
                padding: 1.5rem;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .datetime-group {
                grid-template-columns: 1fr;
            }
            
            .form-actions {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
            }
        }
        
        @media (max-width: 480px) {
            .page-title {
                font-size: 1.75rem;
            }
            
            .form-container {
                padding: 1rem;
            }
            
            .section-header {
                flex-direction: column;
                text-align: center;
                gap: 0.5rem;
            }
        }
        
        /* Validation States */
        .form-control.is-invalid {
            border-color: var(--admin-danger);
            background: rgba(239, 71, 111, 0.05);
        }
        
        .form-control.is-valid {
            border-color: var(--admin-success);
            background: rgba(6, 214, 160, 0.05);
        }
        
        .invalid-feedback {
            color: var(--admin-danger);
            font-size: 0.875rem;
            margin-top: 0.25rem;
            display: none;
        }
        
        .is-invalid + .invalid-feedback {
            display: block;
        }
        
        /* Flight Class Options */
        .class-options {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }
        
        .class-option {
            background: var(--admin-surface-light);
            border: 2px solid var(--admin-border);
            border-radius: 12px;
            padding: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
        }
        
        .class-option:hover {
            border-color: var(--admin-primary);
            background: var(--admin-surface);
        }
        
        .class-option.selected {
            border-color: var(--admin-primary);
            background: rgba(67, 97, 238, 0.1);
        }
        
        .class-icon {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
            color: var(--admin-primary);
        }
        
        .class-name {
            font-weight: 600;
            color: white;
            margin-bottom: 0.25rem;
        }
        
        .class-price {
            color: var(--admin-text-secondary);
            font-size: 0.875rem;
        }
        
        /* Auto-suggestions */
        .suggestions-container {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: var(--admin-surface);
            border: 1px solid var(--admin-border);
            border-radius: 0 0 12px 12px;
            max-height: 200px;
            overflow-y: auto;
            z-index: 1000;
            display: none;
        }
        
        .suggestions-container.show {
            display: block;
        }
        
        .suggestion-item {
            padding: 0.75rem 1rem;
            cursor: pointer;
            transition: background-color 0.2s ease;
        }
        
        .suggestion-item:hover {
            background: var(--admin-surface-light);
        }
    </style>
</head>
<body>
    <!-- Animated Background -->
    <div class="bg-animation"></div>
    
    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="spinner"></div>
    </div>
    
    <!-- Main Container -->
    <div class="admin-container">
        <!-- Back Navigation -->
        <div class="mb-4">
            <a href="all_flights.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>
                Back to Flights
            </a>
        </div>
        
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-title">Add New Flight</h1>
            <p class="page-subtitle">Create a new flight schedule with complete details</p>
        </div>
        
        <!-- Alerts -->
        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo htmlspecialchars($success); ?>
                <a href="all_flights.php" class="ms-auto btn btn-sm btn-primary">
                    View All Flights
                </a>
            </div>
        <?php endif; ?>
        
        <!-- Main Form -->
        <div class="form-container">
            <form id="flightForm" method="POST" novalidate>
                <!-- Flight Schedule Section -->
                <div class="form-section">
                    <div class="section-header">
                        <div class="section-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <h2 class="section-title">Flight Schedule</h2>
                    </div>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">
                                Departure Date <span class="required">*</span>
                            </label>
                            <div class="form-control-wrapper">
                                <input type="date" 
                                       name="source_date" 
                                       class="form-control"
                                       value="<?php echo $defaultDeparture; ?>"
                                       required
                                       min="<?php echo date('Y-m-d'); ?>">
                                <span class="input-icon">
                                    <i class="fas fa-calendar-alt"></i>
                                </span>
                            </div>
                            <span class="form-help">Select departure date</span>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">
                                Departure Time <span class="required">*</span>
                            </label>
                            <div class="form-control-wrapper">
                                <input type="time" 
                                       name="source_time" 
                                       class="form-control"
                                       value="<?php echo $defaultDepartureTime; ?>"
                                       required>
                                <span class="input-icon">
                                    <i class="fas fa-clock"></i>
                                </span>
                            </div>
                            <span class="form-help">Local time at departure airport</span>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">
                                Arrival Date <span class="required">*</span>
                            </label>
                            <div class="form-control-wrapper">
                                <input type="date" 
                                       name="dest_date" 
                                       class="form-control"
                                       value="<?php echo $defaultArrival; ?>"
                                       required
                                       min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
                                <span class="input-icon">
                                    <i class="fas fa-calendar-alt"></i>
                                </span>
                            </div>
                            <span class="form-help">Select arrival date</span>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">
                                Arrival Time <span class="required">*</span>
                            </label>
                            <div class="form-control-wrapper">
                                <input type="time" 
                                       name="dest_time" 
                                       class="form-control"
                                       value="<?php echo $defaultArrivalTime; ?>"
                                       required>
                                <span class="input-icon">
                                    <i class="fas fa-clock"></i>
                                </span>
                            </div>
                            <span class="form-help">Local time at arrival airport</span>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <div class="form-control-wrapper">
                            <input type="text" 
                                   name="dura" 
                                   class="form-control"
                                   placeholder="HH:MM"
                                   pattern="^([0-1]?[0-9]|2[0-3]):[0-5][0-9]$"
                                   title="Enter duration in HH:MM format"
                                   required>
                            <span class="input-icon">
                                <i class="fas fa-hourglass-half"></i>
                            </span>
                        </div>
                        <span class="form-help">Flight duration in hours and minutes (e.g., 02:30)</span>
                    </div>
                </div>
                
                <!-- Flight Route Section -->
                <div class="form-section">
                    <div class="section-header">
                        <div class="section-icon">
                            <i class="fas fa-route"></i>
                        </div>
                        <h2 class="section-title">Flight Route</h2>
                    </div>
                    
                    <div class="route-visualizer">
                        <div class="route-path">
                            <div class="route-point">
                                <div class="route-dot"></div>
                                <div class="city-name" id="departureCity">Select City</div>
                                <div class="airport-code" id="departureCode">---</div>
                            </div>
                            
                            <div class="route-line"></div>
                            
                            <div class="route-point">
                                <div class="route-dot destination"></div>
                                <div class="city-name" id="arrivalCity">Select City</div>
                                <div class="airport-code" id="arrivalCode">---</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">
                                Departure City <span class="required">*</span>
                            </label>
                            <div class="form-control-wrapper">
                                <select name="dep_city" 
                                        class="form-select" 
                                        id="departureSelect"
                                        required>
                                    <option value="">Select Departure City</option>
                                    <?php foreach ($cities as $city): ?>
                                        <option value="<?php echo htmlspecialchars($city['city']); ?>"
                                                data-code="<?php echo htmlspecialchars($city['code'] ?? ''); ?>">
                                            <?php echo htmlspecialchars($city['city']); ?>
                                            <?php if (!empty($city['code'])): ?>
                                                (<?php echo htmlspecialchars($city['code']); ?>)
                                            <?php endif; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <span class="input-icon">
                                    <i class="fas fa-plane-departure"></i>
                                </span>
                            </div>
                            <span class="form-help">City where the flight originates</span>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">
                                Arrival City <span class="required">*</span>
                            </label>
                            <div class="form-control-wrapper">
                                <select name="arr_city" 
                                        class="form-select" 
                                        id="arrivalSelect"
                                        required>
                                    <option value="">Select Arrival City</option>
                                    <?php foreach ($cities as $city): ?>
                                        <option value="<?php echo htmlspecialchars($city['city']); ?>"
                                                data-code="<?php echo htmlspecialchars($city['code'] ?? ''); ?>">
                                            <?php echo htmlspecialchars($city['city']); ?>
                                            <?php if (!empty($city['code'])): ?>
                                                (<?php echo htmlspecialchars($city['code']); ?>)
                                            <?php endif; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <span class="input-icon">
                                    <i class="fas fa-plane-arrival"></i>
                                </span>
                            </div>
                            <span class="form-help">City where the flight arrives</span>
                        </div>
                    </div>
                </div>
                
                <!-- Flight Details Section -->
                <div class="form-section">
                    <div class="section-header">
                        <div class="section-icon">
                            <i class="fas fa-info-circle"></i>
                        </div>
                        <h2 class="section-title">Flight Details</h2>
                    </div>
                    
                    <div class="flight-details-card">
                        <div class="details-grid">
                            <div class="form-group">
                                <label class="form-label">
                                    Airline <span class="required">*</span>
                                </label>
                                <div class="form-control-wrapper">
                                    <select name="airline_name" 
                                            class="form-select"
                                            required>
                                        <option value="">Select Airline</option>
                                        <?php foreach ($airlines as $airline): ?>
                                            <option value="<?php echo htmlspecialchars($airline['airline_id']); ?>">
                                                <?php echo htmlspecialchars($airline['name']); ?>
                                                <?php if (!empty($airline['code'])): ?>
                                                    (<?php echo htmlspecialchars($airline['code']); ?>)
                                                <?php endif; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <span class="input-icon">
                                        <i class="fas fa-building"></i>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">
                                    Aircraft Type
                                </label>
                                <div class="form-control-wrapper">
                                    <select name="aircraft_type" 
                                            class="form-select"
                                            id="aircraftSelect">
                                        <option value="">Select Aircraft</option>
                                        <?php foreach ($aircrafts as $aircraft): ?>
                                            <option value="<?php echo htmlspecialchars($aircraft['id']); ?>"
                                                    data-capacity="<?php echo $aircraft['capacity']; ?>"
                                                    data-range="<?php echo $aircraft['range']; ?>">
                                                <?php echo htmlspecialchars($aircraft['model']); ?>
                                                (<?php echo $aircraft['capacity']; ?> seats)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <span class="input-icon">
                                        <i class="fas fa-plane"></i>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">
                                    Total Seats <span class="required">*</span>
                                </label>
                                <div class="form-control-wrapper">
                                    <input type="number" 
                                           name="seats" 
                                           class="form-control"
                                           id="totalSeats"
                                           min="1"
                                           max="1000"
                                           value="180"
                                           required>
                                    <span class="input-icon">
                                        <i class="fas fa-chair"></i>
                                    </span>
                                </div>
                                <span class="form-help">Total available seats on this flight</span>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">
                                    Flight Number <span class="required">*</span>
                                </label>
                                <div class="form-control-wrapper">
                                    <input type="text" 
                                           name="flight_number" 
                                           class="form-control"
                                           pattern="^[A-Z]{2}[0-9]{3,4}$"
                                           title="Enter valid flight number (e.g., AA1234)"
                                           placeholder="AA1234"
                                           required>
                                    <span class="input-icon">
                                        <i class="fas fa-hashtag"></i>
                                    </span>
                                </div>
                                <span class="form-help">Format: Airline code + 3-4 digits (e.g., AA1234)</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Pricing Section -->
                <div class="form-section">
                    <div class="section-header">
                        <div class="section-icon">
                            <i class="fas fa-tag"></i>
                        </div>
                        <h2 class="section-title">Pricing</h2>
                    </div>
                    
                    <div class="price-calculator">
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label">
                                    Base Price (Economy) <span class="required">*</span>
                                </label>
                                <div class="form-control-wrapper">
                                    <div class="input-group">
                                        <span class="input-group-text" style="background: var(--admin-surface-light); border: 2px solid var(--admin-border); color: var(--admin-text); border-right: none; border-radius: 12px 0 0 12px;">
                                            KES
                                        </span>
                                        <input type="number" 
                                               name="price" 
                                               class="form-control"
                                               id="basePrice"
                                               min="0"
                                               step="100"
                                               value="15000"
                                               required
                                               style="border-radius: 0 12px 12px 0; border-left: none;">
                                    </div>
                                </div>
                                <span class="form-help">Price for Economy class tickets</span>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">
                                    Business Class Multiplier
                                </label>
                                <div class="form-control-wrapper">
                                    <div class="input-group">
                                        <input type="number" 
                                               name="business_multiplier" 
                                               class="form-control"
                                               id="businessMultiplier"
                                               min="1.5"
                                               max="5"
                                               step="0.1"
                                               value="2.5"
                                               style="border-right: none;">
                                        <span class="input-group-text" style="background: var(--admin-surface-light); border: 2px solid var(--admin-border); color: var(--admin-text); border-left: none; border-radius: 0 12px 12px 0;">
                                            x
                                        </span>
                                    </div>
                                </div>
                                <span class="form-help">Multiplier for Business class prices</span>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">
                                    First Class Multiplier
                                </label>
                                <div class="form-control-wrapper">
                                    <div class="input-group">
                                        <input type="number" 
                                               name="first_multiplier" 
                                               class="form-control"
                                               id="firstMultiplier"
                                               min="3"
                                               max="10"
                                               step="0.1"
                                               value="4.5"
                                               style="border-right: none;">
                                        <span class="input-group-text" style="background: var(--admin-surface-light); border: 2px solid var(--admin-border); color: var(--admin-text); border-left: none; border-radius: 0 12px 12px 0;">
                                            x
                                        </span>
                                    </div>
                                </div>
                                <span class="form-help">Multiplier for First class prices</span>
                            </div>
                        </div>
                        
                        <div class="price-breakdown" id="priceBreakdown">
                            <div class="price-item">
                                <span>Economy Class:</span>
                                <span id="economyPrice">KES 15,000</span>
                            </div>
                            <div class="price-item">
                                <span>Business Class:</span>
                                <span id="businessPrice">KES 37,500</span>
                            </div>
                            <div class="price-item">
                                <span>First Class:</span>
                                <span id="firstPrice">KES 67,500</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Additional Options -->
                <div class="form-section">
                    <div class="section-header">
                        <div class="section-icon">
                            <i class="fas fa-cogs"></i>
                        </div>
                        <h2 class="section-title">Additional Options</h2>
                    </div>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">
                                Meal Service
                            </label>
                            <div class="form-control-wrapper">
                                <select name="meal_service" class="form-select">
                                    <option value="standard">Standard Meal Service</option>
                                    <option value="premium">Premium Meal Service</option>
                                    <option value="none">No Meal Service</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">
                                Entertainment
                            </label>
                            <div class="form-control-wrapper">
                                <select name="entertainment" class="form-select">
                                    <option value="standard">Standard Entertainment</option>
                                    <option value="premium">Premium Entertainment</option>
                                    <option value="none">No Entertainment</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">
                                WiFi Available
                            </label>
                            <div class="form-control-wrapper">
                                <select name="wifi" class="form-select">
                                    <option value="1">Yes</option>
                                    <option value="0">No</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">
                                Baggage Allowance (kg)
                            </label>
                            <div class="form-control-wrapper">
                                <input type="number" 
                                       name="baggage_allowance" 
                                       class="form-control"
                                       min="0"
                                       value="20">
                                <span class="input-icon">
                                    <i class="fas fa-suitcase"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Form Actions -->
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" id="previewBtn">
                        <i class="fas fa-eye me-2"></i>
                        Preview
                    </button>
                    <button type="reset" class="btn btn-secondary">
                        <i class="fas fa-redo me-2"></i>
                        Reset
                    </button>
                    <button type="submit" name="flight_but" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>
                        Create Flight
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@eonasdan/tempus-dominus@6.7.0/dist/js/tempus-dominus.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
        // DOM Ready
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize Select2
            $('.form-select').select2({
                theme: 'dark',
                width: '100%',
                placeholder: 'Select an option',
                allowClear: true
            });
            
            // Initialize date pickers
            flatpickr('input[type="date"]', {
                minDate: 'today',
                dateFormat: 'Y-m-d',
                theme: 'dark'
            });
            
            // Initialize time pickers
            flatpickr('input[type="time"]', {
                enableTime: true,
                noCalendar: true,
                dateFormat: 'H:i',
                time_24hr: true,
                theme: 'dark'
            });
            
            // Update route visualizer when cities change
            const departureSelect = document.getElementById('departureSelect');
            const arrivalSelect = document.getElementById('arrivalSelect');
            const departureCity = document.getElementById('departureCity');
            const arrivalCity = document.getElementById('arrivalCity');
            const departureCode = document.getElementById('departureCode');
            const arrivalCode = document.getElementById('arrivalCode');
            
            departureSelect.addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                departureCity.textContent = selectedOption.text.split('(')[0].trim();
                departureCode.textContent = selectedOption.dataset.code || '---';
                validateRoute();
            });
            
            arrivalSelect.addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                arrivalCity.textContent = selectedOption.text.split('(')[0].trim();
                arrivalCode.textContent = selectedOption.dataset.code || '---';
                validateRoute();
            });
            
            // Update aircraft capacity when aircraft changes
            const aircraftSelect = document.getElementById('aircraftSelect');
            const totalSeats = document.getElementById('totalSeats');
            
            aircraftSelect.addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                if (selectedOption.dataset.capacity) {
                    totalSeats.value = selectedOption.dataset.capacity;
                }
            });
            
            // Update price breakdown when base price changes
            const basePrice = document.getElementById('basePrice');
            const businessMultiplier = document.getElementById('businessMultiplier');
            const firstMultiplier = document.getElementById('firstMultiplier');
            const economyPrice = document.getElementById('economyPrice');
            const businessPrice = document.getElementById('businessPrice');
            const firstPrice = document.getElementById('firstPrice');
            
            function updatePriceBreakdown() {
                const base = parseFloat(basePrice.value) || 0;
                const businessMult = parseFloat(businessMultiplier.value) || 2.5;
                const firstMult = parseFloat(firstMultiplier.value) || 4.5;
                
                const businessPriceVal = base * businessMult;
                const firstPriceVal = base * firstMult;
                
                economyPrice.textContent = formatCurrency(base);
                businessPrice.textContent = formatCurrency(businessPriceVal);
                firstPrice.textContent = formatCurrency(firstPriceVal);
            }
            
            basePrice.addEventListener('input', updatePriceBreakdown);
            businessMultiplier.addEventListener('input', updatePriceBreakdown);
            firstMultiplier.addEventListener('input', updatePriceBreakdown);
            
            // Initial price calculation
            updatePriceBreakdown();
            
            // Form validation
            const form = document.getElementById('flightForm');
            form.addEventListener('submit', function(e) {
                if (!validateForm()) {
                    e.preventDefault();
                    showFormErrors();
                } else {
                    showLoading();
                }
            });
            
            // Preview button
            document.getElementById('previewBtn').addEventListener('click', function() {
                if (validateForm()) {
                    showFlightPreview();
                }
            });
            
            // Reset button validation
            form.addEventListener('reset', function() {
                clearValidationErrors();
            });
            
            // Auto-calculate duration based on dates and times
            const sourceDate = document.querySelector('input[name="source_date"]');
            const sourceTime = document.querySelector('input[name="source_time"]');
            const destDate = document.querySelector('input[name="dest_date"]');
            const destTime = document.querySelector('input[name="dest_time"]');
            const durationInput = document.querySelector('input[name="dura"]');
            
            function calculateDuration() {
                if (sourceDate.value && sourceTime.value && destDate.value && destTime.value) {
                    const departure = new Date(`${sourceDate.value}T${sourceTime.value}`);
                    const arrival = new Date(`${destDate.value}T${destTime.value}`);
                    
                    if (arrival > departure) {
                        const diffMs = arrival - departure;
                        const diffHours = Math.floor(diffMs / (1000 * 60 * 60));
                        const diffMinutes = Math.floor((diffMs % (1000 * 60 * 60)) / (1000 * 60));
                        
                        durationInput.value = 
                            diffHours.toString().padStart(2, '0') + ':' + 
                            diffMinutes.toString().padStart(2, '0');
                    }
                }
            }
            
            sourceDate.addEventListener('change', calculateDuration);
            sourceTime.addEventListener('change', calculateDuration);
            destDate.addEventListener('change', calculateDuration);
            destTime.addEventListener('change', calculateDuration);
            
            // Real-time form validation
            const inputs = form.querySelectorAll('input, select');
            inputs.forEach(input => {
                input.addEventListener('blur', validateField);
                input.addEventListener('input', clearFieldError);
            });
        });
        
        // Validation Functions
        function validateForm() {
            let isValid = true;
            
            // Clear previous errors
            clearValidationErrors();
            
            // Validate required fields
            const requiredFields = document.querySelectorAll('[required]');
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    markFieldInvalid(field, 'This field is required');
                    isValid = false;
                }
            });
            
            // Validate route
            if (!validateRoute()) {
                isValid = false;
            }
            
            // Validate dates
            if (!validateDates()) {
                isValid = false;
            }
            
            // Validate flight number format
            const flightNumber = document.querySelector('input[name="flight_number"]');
            if (flightNumber.value && !/^[A-Z]{2}[0-9]{3,4}$/.test(flightNumber.value)) {
                markFieldInvalid(flightNumber, 'Flight number must be in format: AA1234');
                isValid = false;
            }
            
            // Validate price
            const price = document.getElementById('basePrice');
            if (price.value <= 0) {
                markFieldInvalid(price, 'Price must be greater than 0');
                isValid = false;
            }
            
            return isValid;
        }
        
        function validateField(e) {
            const field = e.target;
            
            if (field.hasAttribute('required') && !field.value.trim()) {
                markFieldInvalid(field, 'This field is required');
                return;
            }
            
            // Field-specific validations
            switch (field.name) {
                case 'flight_number':
                    if (field.value && !/^[A-Z]{2}[0-9]{3,4}$/.test(field.value)) {
                        markFieldInvalid(field, 'Invalid flight number format');
                    }
                    break;
                    
                case 'dura':
                    if (field.value && !/^([0-1]?[0-9]|2[0-3]):[0-5][0-9]$/.test(field.value)) {
                        markFieldInvalid(field, 'Invalid duration format (HH:MM)');
                    }
                    break;
                    
                case 'price':
                    if (field.value && field.value <= 0) {
                        markFieldInvalid(field, 'Price must be greater than 0');
                    }
                    break;
                    
                case 'seats':
                    if (field.value && (field.value < 1 || field.value > 1000)) {
                        markFieldInvalid(field, 'Seats must be between 1 and 1000');
                    }
                    break;
            }
        }
        
        function validateRoute() {
            const departure = document.getElementById('departureSelect').value;
            const arrival = document.getElementById('arrivalSelect').value;
            
            if (departure && arrival && departure === arrival) {
                markFieldInvalid(document.getElementById('departureSelect'), 
                    'Departure and arrival cities cannot be the same');
                markFieldInvalid(document.getElementById('arrivalSelect'), 
                    'Departure and arrival cities cannot be the same');
                return false;
            }
            
            return true;
        }
        
        function validateDates() {
            const sourceDate = document.querySelector('input[name="source_date"]');
            const sourceTime = document.querySelector('input[name="source_time"]');
            const destDate = document.querySelector('input[name="dest_date"]');
            const destTime = document.querySelector('input[name="dest_time"]');
            
            if (!sourceDate.value || !sourceTime.value || !destDate.value || !destTime.value) {
                return true; // Let required validation handle empty fields
            }
            
            const departure = new Date(`${sourceDate.value}T${sourceTime.value}`);
            const arrival = new Date(`${destDate.value}T${destTime.value}`);
            
            if (arrival <= departure) {
                markFieldInvalid(destDate, 'Arrival must be after departure');
                markFieldInvalid(destTime, 'Arrival must be after departure');
                return false;
            }
            
            // Check if departure is in the past
            if (departure < new Date()) {
                markFieldInvalid(sourceDate, 'Departure cannot be in the past');
                markFieldInvalid(sourceTime, 'Departure cannot be in the past');
                return false;
            }
            
            return true;
        }
        
        function markFieldInvalid(field, message) {
            field.classList.add('is-invalid');
            
            let feedback = field.nextElementSibling;
            if (!feedback || !feedback.classList.contains('invalid-feedback')) {
                feedback = document.createElement('div');
                feedback.className = 'invalid-feedback';
                field.parentNode.appendChild(feedback);
            }
            
            feedback.textContent = message;
            feedback.style.display = 'block';
        }
        
        function clearFieldError(e) {
            const field = e.target;
            field.classList.remove('is-invalid');
            
            const feedback = field.nextElementSibling;
            if (feedback && feedback.classList.contains('invalid-feedback')) {
                feedback.style.display = 'none';
            }
        }
        
        function clearValidationErrors() {
            document.querySelectorAll('.is-invalid').forEach(el => {
                el.classList.remove('is-invalid');
            });
            
            document.querySelectorAll('.invalid-feedback').forEach(el => {
                el.style.display = 'none';
            });
        }
        
        function showFormErrors() {
            const errors = document.querySelectorAll('.is-invalid');
            if (errors.length > 0) {
                const firstError = errors[0];
                firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                firstError.focus();
                
                Swal.fire({
                    icon: 'error',
                    title: 'Validation Error',
                    text: 'Please correct the errors in the form',
                    confirmButtonColor: '#4361ee'
                });
            }
        }
        
        function showFlightPreview() {
            const formData = new FormData(document.getElementById('flightForm'));
            const data = Object.fromEntries(formData);
            
            // Format dates and times
            const departure = new Date(`${data.source_date}T${data.source_time}`);
            const arrival = new Date(`${data.dest_date}T${data.dest_time}`);
            
            // Calculate duration
            const diffMs = arrival - departure;
            const hours = Math.floor(diffMs / (1000 * 60 * 60));
            const minutes = Math.floor((diffMs % (1000 * 60 * 60)) / (1000 * 60));
            
            // Get airline name
            const airlineSelect = document.querySelector('select[name="airline_name"]');
            const airlineOption = airlineSelect.options[airlineSelect.selectedIndex];
            const airlineName = airlineOption.text.split('(')[0].trim();
            
            // Get city names
            const departureCity = document.getElementById('departureSelect').options[
                document.getElementById('departureSelect').selectedIndex
            ].text.split('(')[0].trim();
            
            const arrivalCity = document.getElementById('arrivalSelect').options[
                document.getElementById('arrivalSelect').selectedIndex
            ].text.split('(')[0].trim();
            
            Swal.fire({
                title: 'Flight Preview',
                html: `
                    <div class="text-start">
                        <div class="mb-3">
                            <strong>Route:</strong> ${departureCity} → ${arrivalCity}
                        </div>
                        <div class="mb-3">
                            <strong>Departure:</strong> ${formatDateTime(departure)}
                        </div>
                        <div class="mb-3">
                            <strong>Arrival:</strong> ${formatDateTime(arrival)}
                        </div>
                        <div class="mb-3">
                            <strong>Duration:</strong> ${hours}h ${minutes}m
                        </div>
                        <div class="mb-3">
                            <strong>Airline:</strong> ${airlineName}
                        </div>
                        <div class="mb-3">
                            <strong>Flight Number:</strong> ${data.flight_number}
                        </div>
                        <div class="mb-3">
                            <strong>Seats:</strong> ${data.seats}
                        </div>
                        <div class="mb-3">
                            <strong>Prices:</strong><br>
                            Economy: ${formatCurrency(data.price)}<br>
                            Business: ${formatCurrency(data.price * (data.business_multiplier || 2.5))}<br>
                            First: ${formatCurrency(data.price * (data.first_multiplier || 4.5))}
                        </div>
                    </div>
                `,
                icon: 'info',
                showCancelButton: true,
                confirmButtonText: 'Create Flight',
                cancelButtonText: 'Edit Details',
                confirmButtonColor: '#06d6a0',
                cancelButtonColor: '#4361ee',
                width: 600
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('flightForm').submit();
                }
            });
        }
        
        function formatCurrency(amount) {
            return 'KES ' + parseFloat(amount).toLocaleString('en-KE', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }
        
        function formatDateTime(date) {
            return date.toLocaleDateString('en-US', {
                weekday: 'short',
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }
        
        function showLoading() {
            document.getElementById('loadingOverlay').classList.add('active');
        }
        
        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl/Cmd + Enter to submit
            if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
                e.preventDefault();
                document.getElementById('flightForm').dispatchEvent(new Event('submit'));
            }
            
            // Escape to reset
            if (e.key === 'Escape') {
                document.getElementById('flightForm').reset();
            }
        });
        
        // Auto-save draft
        let autoSaveTimer;
        const form = document.getElementById('flightForm');
        
        form.addEventListener('input', function() {
            clearTimeout(autoSaveTimer);
            autoSaveTimer = setTimeout(saveDraft, 2000);
        });
        
        function saveDraft() {
            const formData = new FormData(form);
            const data = Object.fromEntries(formData);
            localStorage.setItem('flight_draft', JSON.stringify(data));
            console.log('Draft saved');
        }
        
        function loadDraft() {
            const draft = localStorage.getItem('flight_draft');
            if (draft) {
                const data = JSON.parse(draft);
                Object.keys(data).forEach(key => {
                    const element = form.querySelector(`[name="${key}"]`);
                    if (element) {
                        element.value = data[key];
                    }
                });
                
                // Update dependent fields
                updatePriceBreakdown();
                
                Swal.fire({
                    title: 'Draft Found',
                    text: 'Loaded previously saved draft. Do you want to continue editing?',
                    icon: 'info',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, Continue',
                    cancelButtonText: 'No, Start Fresh'
                }).then((result) => {
                    if (!result.isConfirmed) {
                        localStorage.removeItem('flight_draft');
                        form.reset();
                    }
                });
            }
        }
        
        // Load draft on page load
        window.addEventListener('load', loadDraft);
    </script>
</body>
</html>
