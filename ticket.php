<?php
session_start();
include_once 'helpers/helper.php';
include_once 'helpers/init_conn_db.php';
?>

<?php subview('header.php'); ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Tickets | Airline System 2026</title>
    
    <!-- Modern CSS Framework -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@9/swiper-bundle.min.css">
    <link rel="stylesheet" href="assets/css/tickets.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Inter:wght@400;500;600;700&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --secondary-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --glass-bg: rgba(255, 255, 255, 0.1);
            --glass-border: rgba(255, 255, 255, 0.2);
            --shadow-lg: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            --shadow-xl: 0 35px 60px -15px rgba(0, 0, 0, 0.3);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            background: 
                linear-gradient(rgba(15, 23, 42, 0.95), rgba(15, 23, 42, 0.98)),
                url('assets/images/plane2.jpg') no-repeat center center fixed;
            background-size: cover;
            background-blend-mode: overlay;
            color: #fff;
            position: relative;
            overflow-x: hidden;
        }
        
        /* Animated Background Elements */
        .bg-particles {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            opacity: 0.3;
        }
        
        .particle {
            position: absolute;
            background: rgba(102, 126, 234, 0.3);
            border-radius: 50%;
            animation: float 20s infinite linear;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(180deg); }
        }
        
        /* Header Styles */
        .main-header {
            padding: 2rem 0;
            background: rgba(15, 23, 42, 0.8);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        
        .header-content {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 2rem;
        }
        
        .page-title {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 3.5rem;
            font-weight: 700;
            background: linear-gradient(135deg, #fff 0%, #a5b4fc 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 0.5rem;
        }
        
        .page-subtitle {
            color: rgba(255, 255, 255, 0.7);
            font-size: 1.1rem;
            font-weight: 400;
        }
        
        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin: 2rem 0 3rem;
        }
        
        .stat-card {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 20px;
            padding: 1.5rem;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            background: rgba(255, 255, 255, 0.08);
            border-color: rgba(102, 126, 234, 0.4);
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: var(--primary-gradient);
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            background: rgba(102, 126, 234, 0.1);
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
            font-size: 24px;
            color: #667eea;
        }
        
        .stat-value {
            font-size: 2.5rem;
            font-weight: 700;
            color: #fff;
            margin-bottom: 0.25rem;
        }
        
        .stat-label {
            color: rgba(255, 255, 255, 0.6);
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: rgba(255, 255, 255, 0.03);
            border-radius: 24px;
            border: 2px dashed rgba(255, 255, 255, 0.1);
            margin: 2rem 0;
        }
        
        .empty-icon {
            font-size: 4rem;
            color: rgba(255, 255, 255, 0.2);
            margin-bottom: 1.5rem;
        }
        
        /* Loading Animation */
        .loading-container {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(15, 23, 42, 0.95);
            z-index: 9999;
            justify-content: center;
            align-items: center;
        }
        
        .loading-spinner {
            width: 80px;
            height: 80px;
            border: 4px solid rgba(102, 126, 234, 0.1);
            border-radius: 50%;
            border-top-color: #667eea;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        /* Modal Styles */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(10px);
            z-index: 9998;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .modal-overlay.active {
            display: block;
            opacity: 1;
        }
        
        /* Responsive Design */
        @media (max-width: 1200px) {
            .page-title {
                font-size: 2.8rem;
            }
        }
        
        @media (max-width: 768px) {
            .page-title {
                font-size: 2.2rem;
            }
            
            .header-content {
                padding: 0 1rem;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 480px) {
            .page-title {
                font-size: 1.8rem;
            }
        }
    </style>
</head>
<body>
    <?php if(isset($_SESSION['userId'])): ?>
        <!-- Animated Background -->
        <div class="bg-particles" id="bgParticles"></div>
        
        <!-- Loading Overlay -->
        <div class="loading-container" id="loadingOverlay">
            <div class="loading-spinner"></div>
        </div>
        
        <!-- Confirmation Modal -->
        <div class="modal-overlay" id="confirmationModal">
            <div class="modal-dialog modal-dialog-centered" style="max-width: 500px; margin: 2rem;">
                <div class="modal-content" style="background: rgba(30, 41, 59, 0.95); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 20px; backdrop-filter: blur(20px);">
                    <div class="modal-header border-0">
                        <h5 class="modal-title text-white" style="font-size: 1.5rem; font-weight: 600;">Confirm Cancellation</h5>
                        <button type="button" class="btn-close btn-close-white" onclick="closeModal()"></button>
                    </div>
                    <div class="modal-body">
                        <div class="text-center mb-4">
                            <div class="warning-icon mb-3" style="width: 80px; height: 80px; background: rgba(239, 68, 68, 0.1); border-radius: 50%; display: inline-flex; align-items: center; justify-content: center;">
                                <i class="fas fa-exclamation-triangle" style="font-size: 36px; color: #ef4444;"></i>
                            </div>
                            <h4 class="text-white mb-2">Are you sure?</h4>
                            <p class="text-muted">This action cannot be undone. The ticket will be permanently removed from your account.</p>
                        </div>
                        <div class="alert alert-warning" style="background: rgba(234, 179, 8, 0.1); border: 1px solid rgba(234, 179, 8, 0.3); color: #fef3c7;">
                            <i class="fas fa-info-circle me-2"></i>
                            Cancellation fees may apply based on your ticket type and flight time.
                        </div>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-outline-light" onclick="closeModal()" style="border-radius: 10px; padding: 10px 30px;">Cancel</button>
                        <form method="POST" action="ticket.php" id="cancelForm">
                            <input type="hidden" name="ticket_id" id="cancelTicketId">
                            <button type="submit" name="cancel_but" class="btn btn-danger" style="border-radius: 10px; padding: 10px 30px; background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); border: none;">
                                <i class="fas fa-trash-alt me-2"></i>Confirm Cancellation
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Main Header -->
        <header class="main-header">
            <div class="header-content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h1 class="page-title">My Tickets</h1>
                        <p class="page-subtitle">Manage your flight bookings and travel plans</p>
                    </div>
                    <div>
                        <button class="btn btn-outline-light me-2" onclick="window.location.href='booking.php'" style="border-radius: 10px; padding: 10px 25px;">
                            <i class="fas fa-plus me-2"></i>New Booking
                        </button>
                    </div>
                </div>
                
                <!-- Stats Overview -->
                <div class="stats-grid">
                    <?php
                    $userId = $_SESSION['userId'];
                    
                    // Total Tickets
                    $totalSql = "SELECT COUNT(*) as total FROM Ticket WHERE user_id = ?";
                    $totalStmt = mysqli_stmt_init($conn);
                    if(mysqli_stmt_prepare($totalStmt, $totalSql)) {
                        mysqli_stmt_bind_param($totalStmt, "i", $userId);
                        mysqli_stmt_execute($totalStmt);
                        $totalResult = mysqli_stmt_get_result($totalStmt);
                        $totalData = mysqli_fetch_assoc($totalResult);
                        $totalTickets = $totalData['total'];
                    }
                    
                    // Upcoming Flights
                    $upcomingSql = "SELECT COUNT(*) as upcoming FROM Ticket t 
                                  JOIN Flight f ON t.flight_id = f.flight_id 
                                  WHERE t.user_id = ? AND f.departure > NOW()";
                    $upcomingStmt = mysqli_stmt_init($conn);
                    if(mysqli_stmt_prepare($upcomingStmt, $upcomingSql)) {
                        mysqli_stmt_bind_param($upcomingStmt, "i", $userId);
                        mysqli_stmt_execute($upcomingStmt);
                        $upcomingResult = mysqli_stmt_get_result($upcomingStmt);
                        $upcomingData = mysqli_fetch_assoc($upcomingResult);
                        $upcomingFlights = $upcomingData['upcoming'];
                    }
                    
                    // Total Spent
                    $spentSql = "SELECT COALESCE(SUM(f.price), 0) as total_spent FROM Ticket t 
                               JOIN Flight f ON t.flight_id = f.flight_id 
                               WHERE t.user_id = ?";
                    $spentStmt = mysqli_stmt_init($conn);
                    if(mysqli_stmt_prepare($spentStmt, $spentSql)) {
                        mysqli_stmt_bind_param($spentStmt, "i", $userId);
                        mysqli_stmt_execute($spentStmt);
                        $spentResult = mysqli_stmt_get_result($spentStmt);
                        $spentData = mysqli_fetch_assoc($spentResult);
                        $totalSpent = number_format($spentData['total_spent'], 2);
                    }
                    ?>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-ticket-alt"></i>
                        </div>
                        <div class="stat-value"><?php echo $totalTickets; ?></div>
                        <div class="stat-label">Total Tickets</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-plane-departure"></i>
                        </div>
                        <div class="stat-value"><?php echo $upcomingFlights; ?></div>
                        <div class="stat-label">Upcoming Flights</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-wallet"></i>
                        </div>
                        <div class="stat-value">$<?php echo $totalSpent; ?></div>
                        <div class="stat-label">Total Spent</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-star"></i>
                        </div>
                        <div class="stat-value">Premium</div>
                        <div class="stat-label">Member Status</div>
                    </div>
                </div>
            </div>
        </header>
        
        <!-- Main Content -->
        <main class="py-4">
            <div class="container-fluid px-4">
                <?php
                // Handle ticket cancellation
                if(isset($_POST['cancel_but'])) {
                    $ticket_id = $_POST['ticket_id'];
                    
                    // Show loading
                    echo '<script>document.getElementById("loadingOverlay").style.display = "flex";</script>';
                    
                    // Begin transaction
                    mysqli_begin_transaction($conn);
                    
                    try {
                        // Get ticket details
                        $sql = 'SELECT * FROM Ticket WHERE ticket_id=?';
                        $stmt = mysqli_stmt_init($conn);
                        
                        if(!mysqli_stmt_prepare($stmt, $sql)) {
                            throw new Exception('Database error');
                        }
                        
                        mysqli_stmt_bind_param($stmt, 'i', $ticket_id);            
                        mysqli_stmt_execute($stmt);
                        $result = mysqli_stmt_get_result($stmt);
                        
                        if ($row = mysqli_fetch_assoc($result)) {   
                            // Delete passenger profile
                            $sql_pas = 'DELETE FROM Passenger_profile WHERE passenger_id=?';
                            $stmt_pas = mysqli_stmt_init($conn);
                            
                            if(!mysqli_stmt_prepare($stmt_pas, $sql_pas)) {
                                throw new Exception('Database error');
                            }
                            
                            mysqli_stmt_bind_param($stmt_pas, 'i', $row['passenger_id']);            
                            mysqli_stmt_execute($stmt_pas);
                            
                            // Delete ticket
                            $sql_t = 'DELETE FROM Ticket WHERE ticket_id=?';
                            $stmt_t = mysqli_stmt_init($conn);
                            
                            if(!mysqli_stmt_prepare($stmt_t, $sql_t)) {
                                throw new Exception('Database error');
                            }
                            
                            mysqli_stmt_bind_param($stmt_t, 'i', $row['ticket_id']);            
                            mysqli_stmt_execute($stmt_t);
                            
                            // Commit transaction
                            mysqli_commit($conn);
                            
                            // Show success message
                            echo '
                            <div class="alert alert-success alert-dismissible fade show mx-4" role="alert" style="background: rgba(34, 197, 94, 0.1); border: 1px solid rgba(34, 197, 94, 0.3); color: #bbf7d0; border-radius: 12px;">
                                <i class="fas fa-check-circle me-2"></i>
                                <strong>Success!</strong> Ticket has been cancelled successfully.
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
                            </div>
                            ';
                            
                            // Refresh page after 2 seconds
                            echo '<script>
                                setTimeout(function() {
                                    window.location.href = "ticket.php";
                                }, 2000);
                            </script>';
                        }
                    } catch (Exception $e) {
                        mysqli_rollback($conn);
                        
                        echo '
                        <div class="alert alert-danger alert-dismissible fade show mx-4" role="alert" style="background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); color: #fecaca; border-radius: 12px;">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <strong>Error!</strong> Failed to cancel ticket. Please try again.
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
                        </div>
                        ';
                    }
                    
                    echo '<script>document.getElementById("loadingOverlay").style.display = "none";</script>';
                }
                ?>
                
                <!-- Tickets Section -->
                <section class="tickets-section">
                    <?php
                    $stmt = mysqli_stmt_init($conn);
                    $sql = 'SELECT * FROM Ticket WHERE user_id=? ORDER BY ticket_id DESC';
                    
                    if(!mysqli_stmt_prepare($stmt, $sql)) {
                        echo '<div class="alert alert-danger">Database error occurred.</div>';
                    } else {
                        mysqli_stmt_bind_param($stmt, 'i', $_SESSION['userId']);            
                        mysqli_stmt_execute($stmt);
                        $result = mysqli_stmt_get_result($stmt);
                        
                        if(mysqli_num_rows($result) === 0): ?>
                            <div class="empty-state">
                                <div class="empty-icon">
                                    <i class="fas fa-ticket-alt"></i>
                                </div>
                                <h3 class="text-white mb-3">No Tickets Found</h3>
                                <p class="text-muted mb-4">You haven't booked any flights yet. Start your journey by booking a flight!</p>
                                <button class="btn btn-primary" onclick="window.location.href='booking.php'" style="background: var(--primary-gradient); border: none; border-radius: 10px; padding: 12px 30px;">
                                    <i class="fas fa-plane me-2"></i>Book Your First Flight
                                </button>
                            </div>
                        <?php else: ?>
                            <div class="row g-4" id="ticketsContainer">
                                <?php while ($row = mysqli_fetch_assoc($result)):   
                                    $sql_p = 'SELECT * FROM Passenger_profile WHERE passenger_id=?';
                                    $stmt_p = mysqli_stmt_init($conn);
                                    
                                    if(!mysqli_stmt_prepare($stmt_p, $sql_p)) {
                                        continue;
                                    }
                                    
                                    mysqli_stmt_bind_param($stmt_p, 'i', $row['passenger_id']);            
                                    mysqli_stmt_execute($stmt_p);
                                    $result_p = mysqli_stmt_get_result($stmt_p);
                                    
                                    if($row_p = mysqli_fetch_assoc($result_p)) {
                                        $sql_f = 'SELECT * FROM Flight WHERE flight_id=?';
                                        $stmt_f = mysqli_stmt_init($conn);
                                        
                                        if(!mysqli_stmt_prepare($stmt_f, $sql_f)) {
                                            continue;
                                        }
                                        
                                        mysqli_stmt_bind_param($stmt_f, 'i', $row['flight_id']);            
                                        mysqli_stmt_execute($stmt_f);
                                        $result_f = mysqli_stmt_get_result($stmt_f);
                                        
                                        if($row_f = mysqli_fetch_assoc($result_f)) {
                                            $date_time_dep = $row_f['departure'];
                                            $date_dep = date('d M Y', strtotime($date_time_dep));
                                            $time_dep = date('H:i', strtotime($date_time_dep));
                                            
                                            $date_time_arr = $row_f['arrivale'];
                                            $date_arr = date('d M Y', strtotime($date_time_arr));
                                            $time_arr = date('H:i', strtotime($date_time_arr));
                                            
                                            $class_txt = ($row['class'] === 'E') ? 'ECONOMY' : 'BUSINESS';
                                            $class_color = ($row['class'] === 'E') ? '#10b981' : '#8b5cf6';
                                            
                                            // Check if flight is upcoming
                                            $isUpcoming = strtotime($date_time_dep) > time();
                                            
                                            // Calculate duration
                                            $duration = strtotime($date_time_arr) - strtotime($date_time_dep);
                                            $hours = floor($duration / 3600);
                                            $minutes = floor(($duration % 3600) / 60);
                                            ?>
                                            
                                            <!-- Modern Ticket Card -->
                                            <div class="col-12">
                                                <div class="ticket-card" data-ticket-id="<?php echo $row['ticket_id']; ?>">
                                                    <div class="ticket-header">
                                                        <div class="airline-info">
                                                            <div class="airline-logo">
                                                                <i class="fas fa-plane"></i>
                                                            </div>
                                                            <div>
                                                                <h3 class="airline-name"><?php echo htmlspecialchars($row_f['airline']); ?></h3>
                                                                <span class="flight-number">Flight #<?php echo htmlspecialchars($row_f['flight_id']); ?></span>
                                                            </div>
                                                        </div>
                                                        <div class="ticket-status">
                                                            <span class="status-badge <?php echo $isUpcoming ? 'status-upcoming' : 'status-completed'; ?>">
                                                                <i class="fas <?php echo $isUpcoming ? 'fa-clock' : 'fa-check-circle'; ?> me-1"></i>
                                                                <?php echo $isUpcoming ? 'UPCOMING' : 'COMPLETED'; ?>
                                                            </span>
                                                            <span class="class-badge" style="background: <?php echo $class_color; ?>;">
                                                                <?php echo $class_txt; ?>
                                                            </span>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="ticket-body">
                                                        <div class="route-section">
                                                            <div class="route-dot start">
                                                                <div class="dot"></div>
                                                                <div class="route-line"></div>
                                                            </div>
                                                            <div class="route-info">
                                                                <div class="departure">
                                                                    <div class="time"><?php echo $time_dep; ?></div>
                                                                    <div class="date"><?php echo $date_dep; ?></div>
                                                                    <div class="city"><?php echo htmlspecialchars($row_f['source']); ?></div>
                                                                </div>
                                                            </div>
                                                            
                                                            <div class="flight-duration">
                                                                <div class="duration-text"><?php echo $hours; ?>h <?php echo $minutes; ?>m</div>
                                                                <div class="duration-line">
                                                                    <div class="line"></div>
                                                                    <i class="fas fa-plane"></i>
                                                                </div>
                                                                <div class="stops">Non-stop</div>
                                                            </div>
                                                            
                                                            <div class="route-info">
                                                                <div class="arrival">
                                                                    <div class="time"><?php echo $time_arr; ?></div>
                                                                    <div class="date"><?php echo $date_arr; ?></div>
                                                                    <div class="city"><?php echo htmlspecialchars($row_f['Destination']); ?></div>
                                                                </div>
                                                            </div>
                                                            <div class="route-dot end">
                                                                <div class="route-line"></div>
                                                                <div class="dot"></div>
                                                            </div>
                                                        </div>
                                                        
                                                        <div class="passenger-details">
                                                            <div class="detail-item">
                                                                <span class="detail-label">
                                                                    <i class="fas fa-user me-2"></i>Passenger
                                                                </span>
                                                                <span class="detail-value">
                                                                    <?php echo htmlspecialchars($row_p['f_name'] . ' ' . $row_p['l_name']); ?>
                                                                </span>
                                                            </div>
                                                            <div class="detail-item">
                                                                <span class="detail-label">
                                                                    <i class="fas fa-chair me-2"></i>Seat
                                                                </span>
                                                                <span class="detail-value"><?php echo htmlspecialchars($row['seat_no']); ?></span>
                                                            </div>
                                                            <div class="detail-item">
                                                                <span class="detail-label">
                                                                    <i class="fas fa-door-open me-2"></i>Gate
                                                                </span>
                                                                <span class="detail-value">A22</span>
                                                            </div>
                                                            <div class="detail-item">
                                                                <span class="detail-label">
                                                                    <i class="fas fa-tag me-2"></i>Price
                                                                </span>
                                                                <span class="detail-value">$<?php echo number_format($row_f['price'], 2); ?></span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="ticket-footer">
                                                        <div class="ticket-actions">
                                                            <button class="btn-action btn-view" onclick="viewTicketDetails(<?php echo $row['ticket_id']; ?>)">
                                                                <i class="fas fa-eye me-2"></i>View Details
                                                            </button>
                                                            <button class="btn-action btn-print" onclick="printTicket(<?php echo $row['ticket_id']; ?>)">
                                                                <i class="fas fa-print me-2"></i>Print Ticket
                                                            </button>
                                                            <?php if($isUpcoming): ?>
                                                                <button class="btn-action btn-cancel" onclick="showCancelModal(<?php echo $row['ticket_id']; ?>)">
                                                                    <i class="fas fa-times me-2"></i>Cancel Ticket
                                                                </button>
                                                            <?php endif; ?>
                                                        </div>
                                                        <div class="ticket-id">
                                                            Ticket ID: <span><?php echo $row['ticket_id']; ?></span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php
                                        }
                                    }
                                endwhile; ?>
                            </div>
                        <?php endif;
                    }
                    ?>
                </section>
                
                <!-- View Ticket Modal -->
                <div class="modal fade" id="ticketDetailsModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content" style="background: rgba(30, 41, 59, 0.95); border: 1px solid rgba(255, 255, 255, 0.1); backdrop-filter: blur(20px);">
                            <div class="modal-header border-0">
                                <h5 class="modal-title text-white">Ticket Details</h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body" id="ticketDetailsContent">
                                <!-- Dynamic content will be loaded here -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
        
        <!-- Back to Top Button -->
        <button id="backToTop" class="back-to-top">
            <i class="fas fa-chevron-up"></i>
        </button>
        
    <?php else: ?>
        <!-- Not Logged In State -->
        <div class="container mt-5">
            <div class="row justify-content-center">
                <div class="col-md-8 text-center">
                    <div class="empty-state">
                        <div class="empty-icon">
                            <i class="fas fa-lock" style="font-size: 4rem;"></i>
                        </div>
                        <h1 class="text-white mb-4">Access Denied</h1>
                        <p class="text-muted mb-4">Please log in to view your tickets</p>
                        <a href="login.php" class="btn btn-primary btn-lg" style="background: var(--primary-gradient); border: none; border-radius: 10px; padding: 12px 40px;">
                            <i class="fas fa-sign-in-alt me-2"></i>Login to Continue
                        </a>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/swiper@9/swiper-bundle.min.js"></script>
    <script>
        // Create animated background particles
        function createParticles() {
            const container = document.getElementById('bgParticles');
            if (!container) return;
            
            const particleCount = 15;
            
            for (let i = 0; i < particleCount; i++) {
                const particle = document.createElement('div');
                particle.classList.add('particle');
                
                // Random size
                const size = Math.random() * 100 + 50;
                particle.style.width = `${size}px`;
                particle.style.height = `${size}px`;
                
                // Random position
                particle.style.left = `${Math.random() * 100}%`;
                particle.style.top = `${Math.random() * 100}%`;
                
                // Random animation delay
                particle.style.animationDelay = `${Math.random() * 20}s`;
                
                // Random opacity
                particle.style.opacity = Math.random() * 0.1 + 0.05;
                
                container.appendChild(particle);
            }
        }
        
        // Show cancellation modal
        function showCancelModal(ticketId) {
            document.getElementById('cancelTicketId').value = ticketId;
            document.getElementById('confirmationModal').classList.add('active');
            document.body.style.overflow = 'hidden';
        }
        
        // Close modal
        function closeModal() {
            document.getElementById('confirmationModal').classList.remove('active');
            document.body.style.overflow = 'auto';
        }
        
        // Print ticket
        function printTicket(ticketId) {
            window.open(`e_ticket.php?ticket_id=${ticketId}`, '_blank');
        }
        
        // View ticket details (AJAX)
        function viewTicketDetails(ticketId) {
            const modal = new bootstrap.Modal(document.getElementById('ticketDetailsModal'));
            
            // Show loading
            document.getElementById('ticketDetailsContent').innerHTML = `
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-3 text-muted">Loading ticket details...</p>
                </div>
            `;
            
            modal.show();
            
            // Fetch ticket details via AJAX
            fetch(`ajax/get_ticket_details.php?ticket_id=${ticketId}`)
                .then(response => response.text())
                .then(html => {
                    document.getElementById('ticketDetailsContent').innerHTML = html;
                })
                .catch(error => {
                    document.getElementById('ticketDetailsContent').innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            Failed to load ticket details. Please try again.
                        </div>
                    `;
                });
        }
        
        // Back to top button
        const backToTopBtn = document.getElementById('backToTop');
        if (backToTopBtn) {
            window.addEventListener('scroll', () => {
                if (window.pageYOffset > 300) {
                    backToTopBtn.classList.add('show');
                } else {
                    backToTopBtn.classList.remove('show');
                }
            });
            
            backToTopBtn.addEventListener('click', () => {
                window.scrollTo({ top: 0, behavior: 'smooth' });
            });
        }
        
        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            createParticles();
            
            // Close modal on outside click
            document.getElementById('confirmationModal').addEventListener('click', function(e) {
                if (e.target === this) {
                    closeModal();
                }
            });
            
            // Add ticket card hover effects
            document.querySelectorAll('.ticket-card').forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-10px)';
                });
                
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                });
            });
            
            // Auto-hide alerts after 5 seconds
            setTimeout(() => {
                document.querySelectorAll('.alert').forEach(alert => {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                });
            }, 5000);
        });
        
        // Add CSS for dynamic elements
        const style = document.createElement('style');
        style.textContent = `
            .ticket-card {
                background: rgba(255, 255, 255, 0.05);
                border-radius: 20px;
                border: 1px solid rgba(255, 255, 255, 0.1);
                backdrop-filter: blur(20px);
                padding: 24px;
                margin-bottom: 20px;
                transition: all 0.3s ease;
                position: relative;
                overflow: hidden;
            }
            
            .ticket-card::before {
                content: '';
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 4px;
                background: var(--primary-gradient);
            }
            
            .ticket-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 24px;
            }
            
            .airline-info {
                display: flex;
                align-items: center;
                gap: 16px;
            }
            
            .airline-logo {
                width: 60px;
                height: 60px;
                background: rgba(102, 126, 234, 0.1);
                border-radius: 12px;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 24px;
                color: #667eea;
            }
            
            .airline-name {
                font-size: 1.5rem;
                font-weight: 600;
                color: white;
                margin: 0;
            }
            
            .flight-number {
                color: rgba(255, 255, 255, 0.6);
                font-size: 0.9rem;
            }
            
            .ticket-status {
                display: flex;
                gap: 8px;
            }
            
            .status-badge, .class-badge {
                padding: 6px 12px;
                border-radius: 20px;
                font-size: 0.8rem;
                font-weight: 600;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }
            
            .status-upcoming {
                background: rgba(234, 179, 8, 0.1);
                color: #fbbf24;
                border: 1px solid rgba(234, 179, 8, 0.3);
            }
            
            .status-completed {
                background: rgba(34, 197, 94, 0.1);
                color: #4ade80;
                border: 1px solid rgba(34, 197, 94, 0.3);
            }
            
            .class-badge {
                color: white;
            }
            
            .route-section {
                display: flex;
                align-items: center;
                justify-content: space-between;
                margin-bottom: 32px;
                position: relative;
            }
            
            .route-dot {
                display: flex;
                flex-direction: column;
                align-items: center;
                position: relative;
            }
            
            .route-dot .dot {
                width: 16px;
                height: 16px;
                background: #667eea;
                border-radius: 50%;
                border: 3px solid rgba(255, 255, 255, 0.1);
            }
            
            .route-dot.start .dot {
                background: #10b981;
            }
            
            .route-dot.end .dot {
                background: #8b5cf6;
            }
            
            .route-line {
                width: 2px;
                height: 40px;
                background: rgba(255, 255, 255, 0.1);
                margin-top: 8px;
            }
            
            .route-info {
                flex: 1;
                text-align: center;
            }
            
            .departure .time, .arrival .time {
                font-size: 2rem;
                font-weight: 700;
                color: white;
                margin-bottom: 4px;
            }
            
            .departure .date, .arrival .date {
                color: rgba(255, 255, 255, 0.6);
                font-size: 0.9rem;
                margin-bottom: 8px;
            }
            
            .departure .city, .arrival .city {
                font-size: 1.2rem;
                font-weight: 600;
                color: white;
            }
            
            .flight-duration {
                text-align: center;
                position: relative;
                padding: 0 40px;
            }
            
            .duration-text {
                color: rgba(255, 255, 255, 0.8);
                font-size: 0.9rem;
                margin-bottom: 8px;
            }
            
            .duration-line {
                position: relative;
                height: 2px;
                background: rgba(255, 255, 255, 0.1);
                margin: 0 20px;
            }
            
            .duration-line .line {
                height: 100%;
                background: #667eea;
                width: 100%;
            }
            
            .duration-line .fa-plane {
                position: absolute;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                background: rgba(30, 41, 59, 0.95);
                padding: 8px;
                border-radius: 50%;
                color: #667eea;
                font-size: 14px;
            }
            
            .stops {
                color: rgba(255, 255, 255, 0.5);
                font-size: 0.8rem;
                margin-top: 8px;
            }
            
            .passenger-details {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 20px;
                margin-bottom: 24px;
                padding: 20px;
                background: rgba(255, 255, 255, 0.02);
                border-radius: 12px;
                border: 1px solid rgba(255, 255, 255, 0.05);
            }
            
            .detail-item {
                display: flex;
                flex-direction: column;
                gap: 4px;
            }
            
            .detail-label {
                color: rgba(255, 255, 255, 0.6);
                font-size: 0.9rem;
                display: flex;
                align-items: center;
            }
            
            .detail-value {
                color: white;
                font-size: 1.1rem;
                font-weight: 600;
            }
            
            .ticket-footer {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding-top: 20px;
                border-top: 1px solid rgba(255, 255, 255, 0.1);
            }
            
            .ticket-actions {
                display: flex;
                gap: 12px;
            }
            
            .btn-action {
                padding: 10px 20px;
                border-radius: 10px;
                border: none;
                font-weight: 600;
                cursor: pointer;
                transition: all 0.3s ease;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            
            .btn-view {
                background: rgba(102, 126, 234, 0.1);
                color: #667eea;
                border: 1px solid rgba(102, 126, 234, 0.3);
            }
            
            .btn-view:hover {
                background: rgba(102, 126, 234, 0.2);
                transform: translateY(-2px);
            }
            
            .btn-print {
                background: rgba(34, 197, 94, 0.1);
                color: #10b981;
                border: 1px solid rgba(34, 197, 94, 0.3);
            }
            
            .btn-print:hover {
                background: rgba(34, 197, 94, 0.2);
                transform: translateY(-2px);
            }
            
            .btn-cancel {
                background: rgba(239, 68, 68, 0.1);
                color: #ef4444;
                border: 1px solid rgba(239, 68, 68, 0.3);
            }
            
            .btn-cancel:hover {
                background: rgba(239, 68, 68, 0.2);
                transform: translateY(-2px);
            }
            
            .ticket-id {
                color: rgba(255, 255, 255, 0.5);
                font-size: 0.9rem;
            }
            
            .ticket-id span {
                color: white;
                font-weight: 600;
            }
            
            .back-to-top {
                position: fixed;
                bottom: 30px;
                right: 30px;
                width: 50px;
                height: 50px;
                background: var(--primary-gradient);
                border: none;
                border-radius: 50%;
                color: white;
                font-size: 20px;
                cursor: pointer;
                opacity: 0;
                visibility: hidden;
                transition: all 0.3s ease;
                z-index: 1000;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            
            .back-to-top.show {
                opacity: 1;
                visibility: visible;
            }
            
            .back-to-top:hover {
                transform: translateY(-5px);
                box-shadow: 0 10px 20px rgba(102, 126, 234, 0.4);
            }
            
            @media (max-width: 992px) {
                .ticket-actions {
                    flex-direction: column;
                }
                
                .route-section {
                    flex-direction: column;
                    gap: 20px;
                }
                
                .flight-duration {
                    order: 3;
                    padding: 20px 0;
                }
                
                .route-dot .route-line {
                    display: none;
                }
                
                .duration-line {
                    width: 100%;
                    margin: 0;
                }
            }
            
            @media (max-width: 576px) {
                .ticket-footer {
                    flex-direction: column;
                    gap: 20px;
                }
                
                .ticket-actions {
                    width: 100%;
                }
                
                .btn-action {
                    width: 100%;
                }
                
                .passenger-details {
                    grid-template-columns: 1fr;
                }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>

<?php subview('footer.php'); ?>
