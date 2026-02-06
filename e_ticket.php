<?php 
session_start();
include_once 'helpers/helper.php'; 
subview('header.php'); 

// Security headers
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("Cache-Control: no-cache, must-revalidate");

// CSRF token for form
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Redirect if not logged in
if(!isset($_SESSION['userId'])) {
    header('Location: login.php?error=notloggedin');
    exit();
}

require 'helpers/init_conn_db.php';

// Load QR code library if available
$hasQrCode = file_exists('vendor/autoload.php');
if($hasQrCode) {
    require_once 'vendor/autoload.php';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Ticket - AirTic 2026</title>
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&family=Open+Sans:wght@300;400;600&display=swap" rel="stylesheet">
    
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
    }
    
    @font-face {
        font-family: 'product sans';
        src: url('assets/css/Product Sans Bold.ttf');
    }
    
    body {
        font-family: 'Open Sans', sans-serif;
        background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        min-height: 100vh;
        padding: 20px;
        color: var(--dark-color);
    }
    
    .ticket-container {
        max-width: 1000px;
        margin: 0 auto;
    }
    
    .ticket-card {
        background: white;
        border-radius: 24px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
        overflow: hidden;
        margin-bottom: 30px;
        border: 1px solid rgba(255, 255, 255, 0.3);
        position: relative;
    }
    
    .ticket-header {
        background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
        color: white;
        padding: 30px;
        position: relative;
        overflow: hidden;
    }
    
    .ticket-header::before {
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
    
    .ticket-brand {
        font-family: 'product sans', 'Montserrat', sans-serif;
        font-size: 2.5rem;
        font-weight: 700;
        margin-bottom: 10px;
        letter-spacing: 1px;
    }
    
    .ticket-subtitle {
        font-size: 1.1rem;
        opacity: 0.9;
        margin-bottom: 0;
    }
    
    .ticket-status {
        position: absolute;
        top: 20px;
        right: 20px;
        background: rgba(255, 255, 255, 0.2);
        padding: 8px 20px;
        border-radius: 50px;
        font-weight: 600;
        font-size: 0.9rem;
        backdrop-filter: blur(10px);
    }
    
    .ticket-body {
        padding: 40px;
    }
    
    .ticket-section {
        margin-bottom: 40px;
        padding-bottom: 30px;
        border-bottom: 2px dashed #e2e8f0;
    }
    
    .ticket-section:last-child {
        border-bottom: none;
        margin-bottom: 0;
        padding-bottom: 0;
    }
    
    .section-title {
        font-family: 'Montserrat', sans-serif;
        font-weight: 600;
        color: var(--primary-color);
        margin-bottom: 25px;
        font-size: 1.5rem;
        position: relative;
        padding-left: 15px;
    }
    
    .section-title::before {
        content: '';
        position: absolute;
        left: 0;
        top: 0;
        height: 100%;
        width: 5px;
        background: var(--primary-color);
        border-radius: 3px;
    }
    
    .info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 25px;
    }
    
    .info-item {
        background: #f8fafc;
        padding: 20px;
        border-radius: 15px;
        border-left: 5px solid var(--primary-color);
        transition: transform 0.3s ease;
    }
    
    .info-item:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.05);
    }
    
    .info-label {
        font-size: 0.9rem;
        text-transform: uppercase;
        color: #64748b;
        font-weight: 600;
        letter-spacing: 0.5px;
        margin-bottom: 8px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .info-value {
        font-size: 1.3rem;
        font-weight: 600;
        color: var(--dark-color);
        margin: 0;
    }
    
    .info-value-large {
        font-size: 1.8rem;
        font-weight: 700;
        color: var(--primary-color);
    }
    
    .passenger-details {
        background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
        border-radius: 15px;
        padding: 25px;
    }
    
    .passenger-name {
        font-family: 'Montserrat', sans-serif;
        font-size: 2rem;
        font-weight: 700;
        color: var(--secondary-color);
        margin-bottom: 15px;
    }
    
    .seat-badge {
        background: var(--success-color);
        color: white;
        padding: 8px 20px;
        border-radius: 50px;
        font-weight: 600;
        font-size: 1.1rem;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    
    .class-badge {
        background: var(--warning-color);
        color: white;
        padding: 8px 20px;
        border-radius: 50px;
        font-weight: 600;
        font-size: 1.1rem;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        margin-left: 15px;
    }
    
    .qr-code-container {
        background: white;
        border: 2px dashed #cbd5e1;
        border-radius: 15px;
        padding: 25px;
        text-align: center;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        min-height: 300px;
    }
    
    .qr-placeholder {
        width: 200px;
        height: 200px;
        background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 20px;
    }
    
    .boarding-pass {
        background: white;
        border: 2px solid #e2e8f0;
        border-radius: 15px;
        padding: 25px;
        position: relative;
    }
    
    .boarding-pass::before {
        content: '';
        position: absolute;
        top: 50%;
        left: -10px;
        transform: translateY(-50%);
        width: 20px;
        height: 20px;
        background: #f8fafc;
        border: 2px solid #e2e8f0;
        border-radius: 50%;
    }
    
    .boarding-pass::after {
        content: '';
        position: absolute;
        top: 50%;
        right: -10px;
        transform: translateY(-50%);
        width: 20px;
        height: 20px;
        background: #f8fafc;
        border: 2px solid #e2e8f0;
        border-radius: 50%;
    }
    
    .ticket-footer {
        background: #f8fafc;
        padding: 30px;
        text-align: center;
        border-top: 2px dashed #e2e8f0;
    }
    
    .security-notice {
        background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
        border: 2px solid #f59e0b;
        border-radius: 15px;
        padding: 20px;
        margin-top: 30px;
    }
    
    .ticket-id {
        font-family: 'Courier New', monospace;
        background: #1e293b;
        color: white;
        padding: 15px;
        border-radius: 10px;
        font-size: 1.2rem;
        letter-spacing: 2px;
        margin-top: 20px;
    }
    
    .print-btn {
        background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
        color: white;
        border: none;
        padding: 15px 40px;
        font-size: 1.1rem;
        font-weight: 600;
        border-radius: 50px;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 10px;
    }
    
    .print-btn:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 25px rgba(67, 97, 238, 0.3);
    }
    
    .download-btn {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
        border: none;
        padding: 15px 40px;
        font-size: 1.1rem;
        font-weight: 600;
        border-radius: 50px;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 10px;
        margin-left: 15px;
    }
    
    .download-btn:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 25px rgba(16, 185, 129, 0.3);
    }
    
    .ticket-watermark {
        position: absolute;
        bottom: 20px;
        right: 20px;
        opacity: 0.1;
        font-size: 120px;
        font-weight: 900;
        color: var(--primary-color);
        z-index: 0;
        pointer-events: none;
    }
    
    @media print {
        body * {
            visibility: hidden;
        }
        .ticket-card, .ticket-card * {
            visibility: visible;
        }
        .ticket-card {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            box-shadow: none;
            border-radius: 0;
        }
        .no-print {
            display: none !important;
        }
    }
    
    @media (max-width: 768px) {
        .ticket-header {
            padding: 20px;
        }
        .ticket-body {
            padding: 20px;
        }
        .ticket-brand {
            font-size: 1.8rem;
        }
        .info-grid {
            grid-template-columns: 1fr;
        }
        .print-btn, .download-btn {
            width: 100%;
            margin: 10px 0;
            justify-content: center;
        }
    }
    </style>
</head>
<body>
    <div class="ticket-container">
        <?php 
        if(isset($_POST['print_but'])) {
            // Validate CSRF token
            if(!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
                echo '<div class="alert alert-danger">Security token invalid. Please try again.</div>';
                exit();
            }
            
            $ticket_id = $_POST['ticket_id'];      
            
            // Validate ticket_id
            if(!is_numeric($ticket_id)) {
                echo '<div class="alert alert-danger">Invalid ticket ID.</div>';
                exit();
            }
            
            $stmt = mysqli_stmt_init($conn);
            $sql = 'SELECT * FROM Ticket WHERE ticket_id=?';
            
            if(!mysqli_stmt_prepare($stmt, $sql)) {
                echo '<div class="alert alert-danger">Database error. Please try again.</div>';
                exit();            
            } else {
                mysqli_stmt_bind_param($stmt, 'i', $ticket_id);            
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                
                if ($row = mysqli_fetch_assoc($result)) {   
                    // Verify ticket belongs to logged in user
                    $user_id = $_SESSION['userId'];
                    $verify_sql = 'SELECT user_id FROM Passenger_profile WHERE passenger_id = ?';
                    $verify_stmt = mysqli_stmt_init($conn);
                    
                    if(mysqli_stmt_prepare($verify_stmt, $verify_sql)) {
                        mysqli_stmt_bind_param($verify_stmt, 'i', $row['passenger_id']);
                        mysqli_stmt_execute($verify_stmt);
                        $verify_result = mysqli_stmt_get_result($verify_stmt);
                        
                        if($verify_row = mysqli_fetch_assoc($verify_result)) {
                            if($verify_row['user_id'] != $user_id) {
                                echo '<div class="alert alert-danger">You do not have permission to view this ticket.</div>';
                                exit();
                            }
                        }
                    }
                    
                    $sql_p = 'SELECT * FROM Passenger_profile WHERE passenger_id=?';
                    $stmt_p = mysqli_stmt_init($conn);
                    
                    if(!mysqli_stmt_prepare($stmt_p, $sql_p)) {
                        echo '<div class="alert alert-danger">Database error. Please try again.</div>';
                        exit();            
                    } else {
                        mysqli_stmt_bind_param($stmt_p, 'i', $row['passenger_id']);            
                        mysqli_stmt_execute($stmt_p);
                        $result_p = mysqli_stmt_get_result($stmt_p);
                        
                        if($row_p = mysqli_fetch_assoc($result_p)) {
                            $sql_f = 'SELECT * FROM Flight WHERE flight_id=?';
                            $stmt_f = mysqli_stmt_init($conn);
                            
                            if(!mysqli_stmt_prepare($stmt_f, $sql_f)) {
                                echo '<div class="alert alert-danger">Database error. Please try again.</div>';
                                exit();            
                            } else {
                                mysqli_stmt_bind_param($stmt_f, 'i', $row['flight_id']);            
                                mysqli_stmt_execute($stmt_f);
                                $result_f = mysqli_stmt_get_result($stmt_f);
                                
                                if($row_f = mysqli_fetch_assoc($result_f)) {
                                    $date_time_dep = $row_f['departure'];
                                    $date_dep = date('F j, Y', strtotime($date_time_dep));
                                    $time_dep = date('h:i A', strtotime($date_time_dep));    
                                    
                                    $date_time_arr = $row_f['arrivale'];
                                    $date_arr = date('F j, Y', strtotime($date_time_arr));
                                    $time_arr = date('h:i A', strtotime($date_time_arr));
                                    
                                    // Calculate duration
                                    $dep_timestamp = strtotime($date_time_dep);
                                    $arr_timestamp = strtotime($date_time_arr);
                                    $duration_seconds = $arr_timestamp - $dep_timestamp;
                                    $duration_hours = floor($duration_seconds / 3600);
                                    $duration_minutes = floor(($duration_seconds % 3600) / 60);
                                    
                                    // Flight status
                                    $current_time = time();
                                    if($current_time < $dep_timestamp) {
                                        $status = 'UPCOMING';
                                        $status_class = 'bg-primary';
                                    } elseif ($current_time >= $dep_timestamp && $current_time <= $arr_timestamp) {
                                        $status = 'IN FLIGHT';
                                        $status_class = 'bg-info';
                                    } else {
                                        $status = 'COMPLETED';
                                        $status_class = 'bg-success';
                                    }
                                    
                                    if($row['class'] === 'E') {
                                        $class_txt = 'ECONOMY';
                                        $class_color = '#3b82f6';
                                    } else if($row['class'] === 'B') {
                                        $class_txt = 'BUSINESS';
                                        $class_color = '#f59e0b';
                                    }
                                    
                                    // Generate ticket barcode data
                                    $ticket_data = "TICKET:{$ticket_id}|PASSENGER:{$row_p['passenger_id']}|FLIGHT:{$row_f['flight_id']}|DATE:{$date_time_dep}";
                                    
                                    // Generate unique booking reference
                                    $booking_ref = strtoupper(substr(md5($ticket_id . $row_f['flight_id'] . $date_time_dep), 0, 8));
                                    
                                    echo '
                                    <div class="ticket-card">
                                        <div class="ticket-watermark">BOARDING PASS</div>
                                        
                                        <div class="ticket-header">
                                            <div class="row align-items-center">
                                                <div class="col-md-8">
                                                    <h1 class="ticket-brand"><i class="fas fa-plane"></i> AirTic 2026</h1>
                                                    <p class="ticket-subtitle">Electronic Boarding Pass</p>
                                                </div>
                                                <div class="col-md-4 text-md-end">
                                                    <div class="ticket-status ' . $status_class . '">' . $status . '</div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="ticket-body">
                                            <!-- Flight Information -->
                                            <div class="ticket-section">
                                                <h3 class="section-title"><i class="fas fa-plane-departure me-2"></i> Flight Details</h3>
                                                <div class="info-grid">
                                                    <div class="info-item">
                                                        <div class="info-label"><i class="fas fa-plane"></i> Airline</div>
                                                        <p class="info-value">' . htmlspecialchars($row_f['airline']) . '</p>
                                                    </div>
                                                    <div class="info-item">
                                                        <div class="info-label"><i class="fas fa-map-marker-alt"></i> From</div>
                                                        <p class="info-value-large">' . htmlspecialchars($row_f['source']) . '</p>
                                                    </div>
                                                    <div class="info-item">
                                                        <div class="info-label"><i class="fas fa-map-marker-alt"></i> To</div>
                                                        <p class="info-value-large">' . htmlspecialchars($row_f['Destination']) . '</p>
                                                    </div>
                                                    <div class="info-item">
                                                        <div class="info-label"><i class="fas fa-clock"></i> Duration</div>
                                                        <p class="info-value">' . $duration_hours . 'h ' . $duration_minutes . 'm</p>
                                                    </div>
                                                </div>
                                                
                                                <div class="row mt-4">
                                                    <div class="col-md-6">
                                                        <div class="info-item">
                                                            <div class="info-label"><i class="fas fa-calendar-alt"></i> Departure Date & Time</div>
                                                            <p class="info-value">' . $date_dep . '</p>
                                                            <p class="info-value-large">' . $time_dep . '</p>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="info-item">
                                                            <div class="info-label"><i class="fas fa-calendar-alt"></i> Arrival Date & Time</div>
                                                            <p class="info-value">' . $date_arr . '</p>
                                                            <p class="info-value-large">' . $time_arr . '</p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <!-- Passenger Information -->
                                            <div class="ticket-section">
                                                <h3 class="section-title"><i class="fas fa-user me-2"></i> Passenger Information</h3>
                                                <div class="passenger-details">
                                                    <div class="row align-items-center">
                                                        <div class="col-md-8">
                                                            <h2 class="passenger-name">' . htmlspecialchars($row_p['f_name'] . ' ' . $row_p['m_name'] . ' ' . $row_p['l_name']) . '</h2>
                                                            <div class="d-flex flex-wrap gap-2">
                                                                <span class="seat-badge">
                                                                    <i class="fas fa-chair"></i> Seat: ' . htmlspecialchars($row['seat_no']) . '
                                                                </span>
                                                                <span class="class-badge" style="background-color: ' . $class_color . '">
                                                                    <i class="fas fa-crown"></i> ' . $class_txt . '
                                                                </span>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <div class="ticket-id">
                                                                <small class="d-block mb-1">Booking Reference</small>
                                                                <strong>' . $booking_ref . '</strong>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <!-- Boarding Information -->
                                            <div class="ticket-section">
                                                <h3 class="section-title"><i class="fas fa-info-circle me-2"></i> Boarding Information</h3>
                                                <div class="boarding-pass">
                                                    <div class="row text-center">
                                                        <div class="col-md-3">
                                                            <div class="info-label"><i class="fas fa-door-open"></i> Gate</div>
                                                            <p class="info-value-large">A22</p>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <div class="info-label"><i class="fas fa-clock"></i> Boarding Time</div>
                                                            <p class="info-value-large">' . date('h:i A', strtotime($date_time_dep . ' -45 minutes')) . '</p>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <div class="info-label"><i class="fas fa-suitcase"></i> Baggage</div>
                                                            <p class="info-value-large">2 Pieces</p>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <div class="info-label"><i class="fas fa-tag"></i> Terminal</div>
                                                            <p class="info-value-large">T1</p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <!-- QR Code / Barcode -->
                                            <div class="ticket-section">
                                                <h3 class="section-title"><i class="fas fa-qrcode me-2"></i> Digital Pass</h3>
                                                <div class="qr-code-container">
                                                    ' . ($hasQrCode ? '
                                                    <!-- QR Code would be generated here -->
                                                    <div class="qr-placeholder">
                                                        <i class="fas fa-qrcode fa-5x text-muted"></i>
                                                    </div>
                                                    ' : '
                                                    <div class="qr-placeholder">
                                                        <i class="fas fa-barcode fa-5x text-muted"></i>
                                                    </div>
                                                    ') . '
                                                    <p class="text-muted mt-3">Scan this code at the gate for boarding</p>
                                                    <div class="ticket-id mt-3">
                                                        <small>TICKET ID: ' . $ticket_id . '</small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="ticket-footer">
                                            <div class="security-notice">
                                                <h5><i class="fas fa-shield-alt me-2"></i> Important Security Notice</h5>
                                                <p class="mb-0">Please arrive at the gate at least 45 minutes before departure. Have your ID and this boarding pass ready for verification.</p>
                                            </div>
                                            
                                            <div class="mt-4">
                                                <p class="text-muted mb-3">This is your official boarding pass. Please keep it safe.</p>
                                                
                                                <div class="no-print">
                                                    <button onclick="window.print()" class="print-btn">
                                                        <i class="fas fa-print"></i> Print Boarding Pass
                                                    </button>
                                                    
                                                    <button onclick="downloadTicket()" class="download-btn">
                                                        <i class="fas fa-download"></i> Download PDF
                                                    </button>
                                                    
                                                    <a href="my_flights.php" class="btn btn-outline-primary ms-2">
                                                        <i class="fas fa-arrow-left"></i> Back to My Flights
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    ';
                                } else {
                                    echo '<div class="alert alert-danger">Flight information not found.</div>';
                                }
                            }                  
                        } else {
                            echo '<div class="alert alert-danger">Passenger information not found.</div>';
                        }
                    }                                    
                } else {
                    echo '<div class="alert alert-danger">Ticket not found.</div>';
                }
            }   
        } else { 
            echo '
            <div class="alert alert-warning text-center">
                <i class="fas fa-exclamation-triangle fa-2x mb-3"></i>
                <h4>No Ticket Selected</h4>
                <p>Please select a ticket to view from your bookings.</p>
                <a href="my_flights.php" class="btn btn-primary">
                    <i class="fas fa-arrow-left"></i> Go to My Bookings
                </a>
            </div>';
        }
        ?> 
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function downloadTicket() {
        alert('PDF download feature requires server-side implementation. For now, please use the print function.');
    }
    
    // Auto-print if specified in URL
    if(window.location.search.includes('print=true')) {
        window.print();
    }
    
    // Add animation to ticket
    document.addEventListener('DOMContentLoaded', function() {
        const ticket = document.querySelector('.ticket-card');
        if(ticket) {
            ticket.style.opacity = '0';
            ticket.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                ticket.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                ticket.style.opacity = '1';
                ticket.style.transform = 'translateY(0)';
            }, 100);
        }
    });
    </script>
</body>
</html>

<?php subview('footer.php'); ?>
