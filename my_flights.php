<?php 
session_start();
include_once 'helpers/helper.php'; 
subview('header.php');

// Security headers
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: strict-origin-when-cross-origin");

// Check if user is logged in
if(!isset($_SESSION['userId'])) {
    header('Location: login.php?error=notloggedin');
    exit();
}

require 'helpers/init_conn_db.php';

// CSRF token for actions
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Handle flight cancellation
if(isset($_POST['cancel_flight']) && isset($_POST['csrf_token'])) {
    if($_POST['csrf_token'] === $_SESSION['csrf_token']) {
        $ticket_id = $_POST['ticket_id'];
        
        // Verify ticket belongs to user
        $sql = 'SELECT t.* FROM Ticket t 
                INNER JOIN Passenger_profile p ON t.passenger_id = p.passenger_id 
                WHERE t.ticket_id = ? AND p.user_id = ?';
        $stmt = mysqli_stmt_init($conn);
        
        if(mysqli_stmt_prepare($stmt, $sql)) {
            mysqli_stmt_bind_param($stmt, 'ii', $ticket_id, $_SESSION['userId']);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            if($row = mysqli_fetch_assoc($result)) {
                // Update ticket status to cancelled
                $update_sql = 'UPDATE Ticket SET status = "cancelled" WHERE ticket_id = ?';
                $update_stmt = mysqli_stmt_init($conn);
                
                if(mysqli_stmt_prepare($update_stmt, $update_sql)) {
                    mysqli_stmt_bind_param($update_stmt, 'i', $ticket_id);
                    mysqli_stmt_execute($update_stmt);
                    
                    $_SESSION['success_message'] = 'Flight booking cancelled successfully.';
                }
            }
            mysqli_stmt_close($stmt);
        }
    }
    header('Location: my_flights.php');
    exit();
}

// Handle messages
$messages = [];
if(isset($_SESSION['success_message'])) {
    $messages[] = ['type' => 'success', 'text' => $_SESSION['success_message']];
    unset($_SESSION['success_message']);
}

if(isset($_GET['error'])) {
    switch($_GET['error']) {
        case 'sqlerror':
            $messages[] = ['type' => 'error', 'text' => 'Database error occurred.'];
            break;
        case 'notfound':
            $messages[] = ['type' => 'error', 'text' => 'Flight not found.'];
            break;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="My Flights - AirTic 2026">
    <title>My Flights - AirTic 2026</title>
    
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
        --gradient-warning: linear-gradient(135deg, var(--warning-color) 0%, #f97316 100%);
        --gradient-danger: linear-gradient(135deg, var(--danger-color) 0%, #dc2626 100%);
    }
    
    @font-face {
        font-family: 'product sans';
        src: url('assets/css/Product Sans Bold.ttf');
    }
    
    body {
        font-family: 'Poppins', sans-serif;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        min-height: 100vh;
        color: var(--dark-color);
        padding-bottom: 50px;
    }
    
    .flights-container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 20px;
    }
    
    .header-section {
        text-align: center;
        margin-bottom: 50px;
        padding-top: 30px;
    }
    
    .header-title {
        font-family: 'product sans', 'Montserrat', sans-serif;
        font-size: 3.5rem;
        font-weight: 800;
        color: white;
        margin-bottom: 15px;
        text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
    }
    
    .header-subtitle {
        color: rgba(255, 255, 255, 0.9);
        font-size: 1.2rem;
        max-width: 600px;
        margin: 0 auto 30px;
    }
    
    .stats-container {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 40px;
    }
    
    .stat-card {
        background: rgba(255, 255, 255, 0.95);
        border-radius: 20px;
        padding: 25px;
        text-align: center;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        transition: transform 0.3s ease;
        border: 2px solid rgba(255, 255, 255, 0.2);
        backdrop-filter: blur(10px);
    }
    
    .stat-card:hover {
        transform: translateY(-5px);
    }
    
    .stat-icon {
        font-size: 2.5rem;
        margin-bottom: 15px;
        background: var(--gradient-primary);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }
    
    .stat-number {
        font-size: 2.5rem;
        font-weight: 700;
        color: var(--primary-color);
        margin-bottom: 5px;
    }
    
    .stat-label {
        color: #64748b;
        font-size: 1rem;
        font-weight: 500;
    }
    
    .filters-section {
        background: rgba(255, 255, 255, 0.95);
        border-radius: 20px;
        padding: 30px;
        margin-bottom: 40px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        border: 2px solid rgba(255, 255, 255, 0.2);
        backdrop-filter: blur(10px);
    }
    
    .filter-title {
        font-family: 'Montserrat', sans-serif;
        font-size: 1.5rem;
        font-weight: 600;
        color: var(--primary-color);
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .filter-group {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
        align-items: center;
    }
    
    .filter-btn {
        padding: 10px 25px;
        border: 2px solid #e2e8f0;
        border-radius: 15px;
        background: white;
        color: #4a5568;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .filter-btn:hover, .filter-btn.active {
        background: var(--primary-color);
        color: white;
        border-color: var(--primary-color);
        transform: translateY(-2px);
    }
    
    .search-box {
        flex: 1;
        min-width: 250px;
    }
    
    .search-input {
        width: 100%;
        padding: 12px 20px;
        border: 2px solid #e2e8f0;
        border-radius: 15px;
        font-size: 1rem;
        transition: all 0.3s ease;
    }
    
    .search-input:focus {
        outline: none;
        border-color: var(--primary-color);
        box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
    }
    
    /* Flight Cards */
    .flights-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
        gap: 30px;
        margin-bottom: 50px;
    }
    
    .flight-card {
        background: rgba(255, 255, 255, 0.95);
        border-radius: 25px;
        overflow: hidden;
        box-shadow: 0 15px 40px rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease;
        border: 2px solid rgba(255, 255, 255, 0.2);
        backdrop-filter: blur(10px);
        animation: fadeInUp 0.5s ease;
    }
    
    .flight-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 25px 60px rgba(0, 0, 0, 0.2);
    }
    
    .flight-header {
        background: var(--gradient-primary);
        padding: 25px;
        color: white;
        position: relative;
        overflow: hidden;
    }
    
    .flight-header::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -50%;
        width: 100%;
        height: 200%;
        background: radial-gradient(circle, rgba(255,255,255,0.1) 1px, transparent 1px);
        background-size: 20px 20px;
        transform: rotate(15deg);
        opacity: 0.3;
    }
    
    .flight-route {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 15px;
        position: relative;
        z-index: 2;
    }
    
    .city {
        font-family: 'Montserrat', sans-serif;
        font-size: 1.8rem;
        font-weight: 700;
    }
    
    .flight-icon {
        font-size: 2.5rem;
        animation: planeMove 3s infinite;
    }
    
    @keyframes planeMove {
        0%, 100% { transform: translateX(0); }
        50% { transform: translateX(10px); }
    }
    
    .flight-airline {
        font-size: 1.2rem;
        opacity: 0.9;
        z-index: 2;
        position: relative;
    }
    
    .flight-body {
        padding: 30px;
    }
    
    .flight-info {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 20px;
        margin-bottom: 25px;
    }
    
    .info-item {
        text-align: center;
    }
    
    .info-label {
        font-size: 0.9rem;
        color: #64748b;
        margin-bottom: 5px;
        text-transform: uppercase;
        font-weight: 600;
    }
    
    .info-value {
        font-size: 1.3rem;
        font-weight: 700;
        color: var(--dark-color);
    }
    
    .flight-status {
        background: #f8fafc;
        border-radius: 15px;
        padding: 20px;
        margin-bottom: 25px;
        text-align: center;
        border-left: 5px solid var(--primary-color);
    }
    
    .status-badge {
        display: inline-block;
        padding: 8px 20px;
        border-radius: 50px;
        font-weight: 600;
        font-size: 0.9rem;
        margin-bottom: 10px;
    }
    
    .status-scheduled { background: #dbeafe; color: #1e40af; }
    .status-boarding { background: #fef3c7; color: #92400e; }
    .status-inflight { background: #e0f2fe; color: #0369a1; }
    .status-delayed { background: #fee2e2; color: #991b1b; }
    .status-arrived { background: #d1fae5; color: #065f46; }
    .status-cancelled { background: #f3f4f6; color: #374151; }
    
    .status-time {
        font-size: 0.9rem;
        color: #64748b;
    }
    
    .flight-actions {
        display: flex;
        gap: 15px;
        flex-wrap: wrap;
    }
    
    .action-btn {
        flex: 1;
        padding: 12px 20px;
        border-radius: 12px;
        border: none;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        text-align: center;
        text-decoration: none;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        min-width: 120px;
    }
    
    .btn-primary {
        background: var(--primary-color);
        color: white;
    }
    
    .btn-primary:hover {
        background: var(--secondary-color);
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(67, 97, 238, 0.3);
    }
    
    .btn-success {
        background: var(--success-color);
        color: white;
    }
    
    .btn-success:hover {
        background: #10b981;
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(16, 185, 129, 0.3);
    }
    
    .btn-danger {
        background: var(--danger-color);
        color: white;
    }
    
    .btn-danger:hover {
        background: #dc2626;
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(239, 68, 68, 0.3);
    }
    
    .btn-outline {
        background: transparent;
        border: 2px solid var(--primary-color);
        color: var(--primary-color);
    }
    
    .btn-outline:hover {
        background: var(--primary-color);
        color: white;
        transform: translateY(-2px);
    }
    
    .btn-disabled {
        background: #e5e7eb;
        color: #9ca3af;
        cursor: not-allowed;
    }
    
    .btn-disabled:hover {
        transform: none;
        box-shadow: none;
    }
    
    .no-flights {
        background: rgba(255, 255, 255, 0.95);
        border-radius: 20px;
        padding: 60px 30px;
        text-align: center;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        border: 2px solid rgba(255, 255, 255, 0.2);
        backdrop-filter: blur(10px);
        grid-column: 1 / -1;
    }
    
    .no-flights-icon {
        font-size: 4rem;
        color: #cbd5e1;
        margin-bottom: 20px;
    }
    
    .no-flights-title {
        font-size: 2rem;
        color: var(--dark-color);
        margin-bottom: 15px;
    }
    
    .no-flights-text {
        color: #64748b;
        font-size: 1.1rem;
        margin-bottom: 30px;
        max-width: 500px;
        margin-left: auto;
        margin-right: auto;
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
    
    .alert-success { border-color: var(--success-color); }
    .alert-error { border-color: var(--danger-color); }
    .alert-warning { border-color: var(--warning-color); }
    
    .alert-custom i {
        font-size: 1.2rem;
    }
    
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
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
    
    .modal-custom .modal-content {
        border-radius: 20px;
        border: none;
        overflow: hidden;
    }
    
    .modal-custom .modal-header {
        background: var(--gradient-primary);
        color: white;
        border-bottom: none;
        padding: 25px;
    }
    
    .modal-custom .modal-body {
        padding: 30px;
    }
    
    .modal-custom .modal-footer {
        border-top: 1px solid #e2e8f0;
        padding: 20px 30px;
    }
    
    .loading {
        display: none;
    }
    
    @media (max-width: 768px) {
        .flights-grid {
            grid-template-columns: 1fr;
        }
        
        .header-title {
            font-size: 2.5rem;
        }
        
        .flight-route {
            flex-direction: column;
            gap: 10px;
            text-align: center;
        }
        
        .flight-info {
            grid-template-columns: 1fr;
        }
        
        .flight-actions {
            flex-direction: column;
        }
        
        .action-btn {
            width: 100%;
        }
        
        .stats-container {
            grid-template-columns: repeat(2, 1fr);
        }
    }
    
    @media (max-width: 480px) {
        .stats-container {
            grid-template-columns: 1fr;
        }
        
        .header-title {
            font-size: 2rem;
        }
        
        .filters-section {
            padding: 20px;
        }
        
        .filter-group {
            flex-direction: column;
            align-items: stretch;
        }
        
        .search-box {
            min-width: 100%;
        }
    }
    </style>
</head>
<body>
    <!-- Messages -->
    <?php if (!empty($messages)): ?>
    <div class="alert-container">
        <?php foreach ($messages as $message): ?>
        <div class="alert-custom alert-<?php echo $message['type']; ?>">
            <i class="fas <?php echo $message['type'] === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
            <span><?php echo htmlspecialchars($message['text']); ?></span>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    
    <div class="flights-container">
        <!-- Header -->
        <div class="header-section">
            <h1 class="header-title">My Flight Dashboard</h1>
            <p class="header-subtitle">Track and manage all your upcoming and past flights in one place</p>
        </div>
        
        <?php
        // Get flight statistics
        $stats = [
            'total' => 0,
            'upcoming' => 0,
            'completed' => 0,
            'cancelled' => 0
        ];
        
        $stmt = mysqli_stmt_init($conn);
        $sql = 'SELECT COUNT(*) as total FROM Ticket t 
                INNER JOIN Passenger_profile p ON t.passenger_id = p.passenger_id 
                WHERE p.user_id = ?';
        
        if(mysqli_stmt_prepare($stmt, $sql)) {
            mysqli_stmt_bind_param($stmt, 'i', $_SESSION['userId']);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            if($row = mysqli_fetch_assoc($result)) {
                $stats['total'] = $row['total'];
            }
            mysqli_stmt_close($stmt);
        }
        
        // Check if user has flights
        if($stats['total'] > 0) {
        ?>
        
        <!-- Statistics -->
        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-plane"></i>
                </div>
                <div class="stat-number"><?php echo $stats['total']; ?></div>
                <div class="stat-label">Total Flights</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <div class="stat-number" id="upcoming-count">0</div>
                <div class="stat-label">Upcoming</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-number" id="completed-count">0</div>
                <div class="stat-label">Completed</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-times-circle"></i>
                </div>
                <div class="stat-number" id="cancelled-count">0</div>
                <div class="stat-label">Cancelled</div>
            </div>
        </div>
        
        <!-- Filters -->
        <div class="filters-section">
            <div class="filter-title">
                <i class="fas fa-filter"></i> Filter Flights
            </div>
            <div class="filter-group">
                <button class="filter-btn active" data-filter="all">All Flights</button>
                <button class="filter-btn" data-filter="upcoming">Upcoming</button>
                <button class="filter-btn" data-filter="completed">Completed</button>
                <button class="filter-btn" data-filter="cancelled">Cancelled</button>
                <div class="search-box">
                    <input type="text" class="search-input" placeholder="Search flights by city, airline, or flight number..." id="flightSearch">
                </div>
            </div>
        </div>
        
        <!-- Flight Cards -->
        <div class="flights-grid" id="flightsGrid">
            <?php
            // Fetch user's flights
            $sql = 'SELECT t.*, f.*, p.f_name, p.l_name 
                    FROM Ticket t 
                    INNER JOIN Passenger_profile p ON t.passenger_id = p.passenger_id 
                    INNER JOIN Flight f ON t.flight_id = f.flight_id 
                    WHERE p.user_id = ? 
                    ORDER BY f.departure DESC';
            
            $stmt = mysqli_stmt_init($conn);
            if(mysqli_stmt_prepare($stmt, $sql)) {
                mysqli_stmt_bind_param($stmt, 'i', $_SESSION['userId']);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                
                $upcoming_count = 0;
                $completed_count = 0;
                $cancelled_count = 0;
                
                while($row = mysqli_fetch_assoc($result)) {
                    $departure_time = strtotime($row['departure']);
                    $arrival_time = strtotime($row['arrivale']);
                    $current_time = time();
                    
                    // Determine flight status
                    if($row['status'] === 'cancelled') {
                        $status = 'cancelled';
                        $status_class = 'status-cancelled';
                        $status_text = 'Cancelled';
                        $cancelled_count++;
                    } else if($current_time < $departure_time) {
                        $status = 'upcoming';
                        $status_class = 'status-scheduled';
                        $status_text = 'Scheduled';
                        $upcoming_count++;
                    } else if($current_time >= $departure_time && $current_time <= $arrival_time) {
                        $status = 'upcoming';
                        $status_class = 'status-inflight';
                        $status_text = 'In Flight';
                        $upcoming_count++;
                    } else {
                        $status = 'completed';
                        $status_class = 'status-arrived';
                        $status_text = 'Completed';
                        $completed_count++;
                    }
                    
                    // Format dates and times
                    $departure_date = date('F j, Y', $departure_time);
                    $departure_time_formatted = date('h:i A', $departure_time);
                    $arrival_date = date('F j, Y', $arrival_time);
                    $arrival_time_formatted = date('h:i A', $arrival_time);
                    
                    // Calculate duration
                    $duration = $arrival_time - $departure_time;
                    $hours = floor($duration / 3600);
                    $minutes = floor(($duration % 3600) / 60);
                    
                    // Generate booking reference
                    $booking_ref = strtoupper(substr(md5($row['ticket_id'] . $row['flight_id']), 0, 8));
                    
                    // Determine if flight can be cancelled (only upcoming flights)
                    $can_cancel = ($status === 'upcoming' && $current_time < $departure_time - 3600); // 1 hour before departure
                    
                    // Determine if flight can be checked in (24 hours before departure)
                    $can_checkin = ($status === 'upcoming' && $current_time >= $departure_time - 86400 && $current_time < $departure_time);
                    ?>
                    
                    <div class="flight-card" data-status="<?php echo $status; ?>" data-search="<?php echo strtolower($row['source'] . ' ' . $row['Destination'] . ' ' . $row['airline'] . ' ' . $booking_ref); ?>">
                        <div class="flight-header">
                            <div class="flight-route">
                                <div class="city"><?php echo htmlspecialchars($row['source']); ?></div>
                                <div class="flight-icon">
                                    <i class="fas fa-plane"></i>
                                </div>
                                <div class="city"><?php echo htmlspecialchars($row['Destination']); ?></div>
                            </div>
                            <div class="flight-airline">
                                <i class="fas fa-plane me-2"></i> <?php echo htmlspecialchars($row['airline']); ?>
                            </div>
                        </div>
                        
                        <div class="flight-body">
                            <div class="flight-info">
                                <div class="info-item">
                                    <div class="info-label">Departure</div>
                                    <div class="info-value"><?php echo $departure_time_formatted; ?></div>
                                    <small class="text-muted"><?php echo $departure_date; ?></small>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Arrival</div>
                                    <div class="info-value"><?php echo $arrival_time_formatted; ?></div>
                                    <small class="text-muted"><?php echo $arrival_date; ?></small>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Duration</div>
                                    <div class="info-value"><?php echo $hours; ?>h <?php echo $minutes; ?>m</div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Booking Ref</div>
                                    <div class="info-value"><?php echo $booking_ref; ?></div>
                                </div>
                            </div>
                            
                            <div class="flight-status">
                                <div class="status-badge <?php echo $status_class; ?>">
                                    <?php echo $status_text; ?>
                                </div>
                                <div class="status-time">
                                    <?php if($status === 'upcoming'): ?>
                                        Departs in <?php echo human_time_diff($current_time, $departure_time); ?>
                                    <?php elseif($status === 'completed'): ?>
                                        Arrived <?php echo human_time_diff($arrival_time, $current_time); ?> ago
                                    <?php elseif($status === 'cancelled'): ?>
                                        Cancelled
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="flight-actions">
                                <a href="e_ticket.php?ticket=<?php echo $row['ticket_id']; ?>" class="action-btn btn-primary">
                                    <i class="fas fa-ticket-alt"></i> View Ticket
                                </a>
                                
                                <?php if($can_checkin): ?>
                                    <a href="checkin.php?ticket=<?php echo $row['ticket_id']; ?>" class="action-btn btn-success">
                                        <i class="fas fa-check-in"></i> Check-in
                                    </a>
                                <?php endif; ?>
                                
                                <?php if($can_cancel): ?>
                                    <button class="action-btn btn-danger cancel-btn" data-ticket-id="<?php echo $row['ticket_id']; ?>">
                                        <i class="fas fa-times"></i> Cancel
                                    </button>
                                <?php else: ?>
                                    <button class="action-btn btn-disabled" disabled>
                                        <i class="fas fa-times"></i> Cancel
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php
                }
                
                // Update counts for JavaScript
                echo '<script>';
                echo 'document.getElementById("upcoming-count").textContent = "' . $upcoming_count . '";';
                echo 'document.getElementById("completed-count").textContent = "' . $completed_count . '";';
                echo 'document.getElementById("cancelled-count").textContent = "' . $cancelled_count . '";';
                echo '</script>';
                
                mysqli_stmt_close($stmt);
            }
            ?>
        </div>
        
        <?php } else { ?>
        
        <!-- No Flights State -->
        <div class="no-flights">
            <div class="no-flights-icon">
                <i class="fas fa-plane-slash"></i>
            </div>
            <h2 class="no-flights-title">No Flights Booked Yet</h2>
            <p class="no-flights-text">You haven't booked any flights yet. Start your journey by searching for flights and making your first booking!</p>
            <a href="index.php" class="action-btn btn-primary" style="max-width: 250px; margin: 0 auto;">
                <i class="fas fa-search"></i> Search Flights
            </a>
        </div>
        
        <?php } ?>
        
        <!-- Empty State for Filter Results -->
        <div class="no-flights" id="noResults" style="display: none;">
            <div class="no-flights-icon">
                <i class="fas fa-search"></i>
            </div>
            <h2 class="no-flights-title">No Flights Found</h2>
            <p class="no-flights-text">No flights match your search criteria. Try adjusting your filters or search terms.</p>
            <button class="action-btn btn-outline" id="resetFilters" style="max-width: 250px; margin: 0 auto;">
                <i class="fas fa-redo"></i> Reset Filters
            </button>
        </div>
    </div>
    
    <!-- Cancel Flight Modal -->
    <div class="modal fade modal-custom" id="cancelModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-exclamation-triangle me-2"></i> Cancel Flight</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="my_flights.php" id="cancelForm">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input type="hidden" name="cancel_flight" value="1">
                    <input type="hidden" name="ticket_id" id="cancelTicketId">
                    
                    <div class="modal-body">
                        <p>Are you sure you want to cancel this flight?</p>
                        <div class="alert alert-warning">
                            <i class="fas fa-info-circle me-2"></i>
                            Cancellation fees may apply based on your ticket type and timing.
                        </div>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="confirmCancel" required>
                            <label class="form-check-label" for="confirmCancel">
                                I understand that this action cannot be undone
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Keep Flight</button>
                        <button type="submit" class="btn btn-danger">Cancel Flight</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Filter flights
        const filterBtns = document.querySelectorAll('.filter-btn');
        const flightCards = document.querySelectorAll('.flight-card');
        const searchInput = document.getElementById('flightSearch');
        const flightsGrid = document.getElementById('flightsGrid');
        const noResults = document.getElementById('noResults');
        
        // Filter function
        function filterFlights(filter, searchTerm = '') {
            let visibleCount = 0;
            
            flightCards.forEach(card => {
                const status = card.getAttribute('data-status');
                const searchData = card.getAttribute('data-search');
                const matchesFilter = filter === 'all' || status === filter;
                const matchesSearch = !searchTerm || searchData.includes(searchTerm.toLowerCase());
                
                if (matchesFilter && matchesSearch) {
                    card.style.display = 'block';
                    visibleCount++;
                } else {
                    card.style.display = 'none';
                }
            });
            
            // Show/hide no results message
            if (visibleCount === 0) {
                noResults.style.display = 'block';
                flightsGrid.style.display = 'none';
            } else {
                noResults.style.display = 'none';
                flightsGrid.style.display = 'grid';
            }
        }
        
        // Filter button click
        filterBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                filterBtns.forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                const filter = this.getAttribute('data-filter');
                filterFlights(filter, searchInput.value);
            });
        });
        
        // Search input
        searchInput.addEventListener('input', function() {
            const activeFilter = document.querySelector('.filter-btn.active').getAttribute('data-filter');
            filterFlights(activeFilter, this.value);
        });
        
        // Reset filters
        document.getElementById('resetFilters')?.addEventListener('click', function() {
            filterBtns.forEach(b => b.classList.remove('active'));
            filterBtns[0].classList.add('active');
            searchInput.value = '';
            filterFlights('all', '');
        });
        
        // Cancel flight modal
        const cancelModal = new bootstrap.Modal(document.getElementById('cancelModal'));
        const cancelForm = document.getElementById('cancelForm');
        const confirmCancel = document.getElementById('confirmCancel');
        
        document.querySelectorAll('.cancel-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const ticketId = this.getAttribute('data-ticket-id');
                document.getElementById('cancelTicketId').value = ticketId;
                confirmCancel.checked = false;
                cancelModal.show();
            });
        });
        
        // Form validation for cancel
        cancelForm.addEventListener('submit', function(e) {
            if (!confirmCancel.checked) {
                e.preventDefault();
                alert('Please confirm cancellation by checking the box.');
            }
        });
        
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
        
        // Add animation to flight cards on scroll
        function animateCardsOnScroll() {
            const cards = document.querySelectorAll('.flight-card');
            
            cards.forEach((card, index) => {
                const cardPosition = card.getBoundingClientRect().top;
                const screenPosition = window.innerHeight / 1.2;
                
                if (cardPosition < screenPosition) {
                    setTimeout(() => {
                        card.style.opacity = '1';
                        card.style.transform = 'translateY(0)';
                    }, index * 100);
                }
            });
        }
        
        // Set initial styles for animation
        document.querySelectorAll('.flight-card').forEach(card => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
        });
        
        // Trigger animation on load and scroll
        window.addEventListener('load', animateCardsOnScroll);
        window.addEventListener('scroll', animateCardsOnScroll);
        
        // Initialize tooltips
        const tooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        tooltips.forEach(tooltip => {
            new bootstrap.Tooltip(tooltip);
        });
    });
    
    // Helper function for human readable time difference
    function human_time_diff(from, to) {
        const diff = Math.abs(to - from);
        const days = Math.floor(diff / (24 * 3600));
        const hours = Math.floor((diff % (24 * 3600)) / 3600);
        const minutes = Math.floor((diff % 3600) / 60);
        
        if (days > 0) return days + ' day' + (days > 1 ? 's' : '');
        if (hours > 0) return hours + ' hour' + (hours > 1 ? 's' : '');
        return minutes + ' minute' + (minutes > 1 ? 's' : '');
    }
    </script>
    
    <?php
    // Helper function for human readable time difference (PHP version)
    function human_time_diff_php($from, $to) {
        $diff = abs($to - $from);
        $days = floor($diff / (24 * 3600));
        $hours = floor(($diff % (24 * 3600)) / 3600);
        $minutes = floor(($diff % 3600) / 60);
        
        if ($days > 0) return $days . ' day' . ($days > 1 ? 's' : '');
        if ($hours > 0) return $hours . ' hour' . ($hours > 1 ? 's' : '');
        return $minutes . ' minute' . ($minutes > 1 ? 's' : '');
    }
    ?>
</body>
</html>

<?php 
subview('footer.php');
mysqli_close($conn);
?>
