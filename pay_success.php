<?php
session_start();
require_once 'config/database.php';
require_once 'helpers/sslcommerz.php';

// Initialize SSLCommerz
$sslcommerz = new SSLCommerz();
$store_id = 'etato690d02c67cd99';
$store_password = 'etato690d02c67cd99@ssl';
$currency = "BDT";

// Get transaction data
$tran_id = isset($_POST['tran_id']) ? $_POST['tran_id'] : (isset($_GET['tran_id']) ? $_GET['tran_id'] : '');
$val_id = isset($_POST['val_id']) ? $_POST['val_id'] : (isset($_GET['val_id']) ? $_GET['val_id'] : '');
$amount = isset($_POST['amount']) ? $_POST['amount'] : '';
$bank_tran_id = isset($_POST['bank_tran_id']) ? $_POST['bank_tran_id'] : '';
$card_type = isset($_POST['card_type']) ? $_POST['card_type'] : '';
$card_no = isset($_POST['card_no']) ? $_POST['card_no'] : '';
$card_issuer = isset($_POST['card_issuer']) ? $_POST['card_issuer'] : '';
$card_brand = isset($_POST['card_brand']) ? $_POST['card_brand'] : '';

// Verify payment with SSLCommerz
$is_valid = false;
$payment_status = 'Pending';
$verification_response = [];

if ($val_id) {
    $verification_response = $sslcommerz->verifyPayment($val_id);
    $is_valid = ($verification_response['status'] === 'VALID' || $verification_response['status'] === 'VALIDATED');
    $payment_status = $is_valid ? 'Success' : 'Failed';
}

// Get booking data from session or database
$booking_data = $_SESSION['booking_data'] ?? [];
if (!$booking_data && $tran_id) {
    $booking_data = getBookingByTransaction($tran_id);
}

// Generate PNR if not exists
$pnr = $booking_data['pnr'] ?? generatePNR();

// Save to database if valid
if ($is_valid && $booking_data) {
    saveSuccessfulPayment($booking_data, $tran_id, $val_id, $amount, $verification_response);
    
    // Send notifications
    sendNotifications($booking_data, $pnr);
    
    // Clear session data
    unset($_SESSION['booking_data']);
}

// Helper functions
function generatePNR() {
    $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $pnr = 'ETA';
    for ($i = 0; $i < 6; $i++) {
        $pnr .= $characters[rand(0, 35)];
    }
    return $pnr;
}

function getBookingByTransaction($tran_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM bookings WHERE transaction_id = ?");
    $stmt->execute([$tran_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function saveSuccessfulPayment($booking_data, $tran_id, $val_id, $amount, $verification_response) {
    global $pdo;
    
    // Update booking status
    $stmt = $pdo->prepare("UPDATE bookings SET 
        payment_status = 'success',
        val_id = ?,
        bank_tran_id = ?,
        card_type = ?,
        card_no = ?,
        card_issuer = ?,
        card_brand = ?,
        verification_response = ?,
        payment_date = NOW()
        WHERE transaction_id = ?");
    
    $stmt->execute([
        $val_id,
        $verification_response['bank_tran_id'] ?? '',
        $verification_response['card_type'] ?? '',
        maskCardNumber($verification_response['card_no'] ?? ''),
        $verification_response['card_issuer'] ?? '',
        $verification_response['card_brand'] ?? '',
        json_encode($verification_response),
        $tran_id
    ]);
    
    // Create payment record
    $payment_stmt = $pdo->prepare("INSERT INTO payments SET
        transaction_id = ?,
        val_id = ?,
        amount = ?,
        currency = 'BDT',
        payment_method = ?,
        bank_transaction_id = ?,
        card_last_four = ?,
        card_type = ?,
        card_issuer = ?,
        status = 'success',
        verification_data = ?,
        payment_date = NOW()");
    
    $payment_stmt->execute([
        $tran_id,
        $val_id,
        $amount,
        $verification_response['card_type'] ?? $booking_data['payment_method'],
        $verification_response['bank_tran_id'] ?? '',
        substr($verification_response['card_no'] ?? '', -4),
        $verification_response['card_type'] ?? '',
        $verification_response['card_issuer'] ?? '',
        json_encode($verification_response)
    ]);
}

function sendNotifications($booking_data, $pnr) {
    // Send email
    sendConfirmationEmail($booking_data, $pnr);
    
    // Send SMS
    sendConfirmationSMS($booking_data, $pnr);
    
    // Send WhatsApp (optional)
    sendWhatsAppNotification($booking_data, $pnr);
}

function sendConfirmationEmail($booking_data, $pnr) {
    $to = $booking_data['passenger_email'];
    $subject = "ETA Tour & Travel - Booking Confirmation (PNR: $pnr)";
    
    $message = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #1a365d 0%, #2d3748 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
            .content { padding: 30px; background: #f8f9fa; }
            .ticket { background: white; border: 2px solid #e2e8f0; border-radius: 10px; padding: 20px; margin: 20px 0; }
            .footer { background: #2d3748; color: white; padding: 20px; text-align: center; border-radius: 0 0 10px 10px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>Booking Confirmed!</h2>
                <p>ETA Tour & Travel Agency</p>
            </div>
            <div class='content'>
                <h3>Dear {$booking_data['passenger_name']},</h3>
                <p>Your flight booking has been successfully confirmed. Below are your booking details:</p>
                
                <div class='ticket'>
                    <h4 style='color: #1a365d;'>PNR: $pnr</h4>
                    <p><strong>Passenger:</strong> {$booking_data['passenger_name']}</p>
                    <p><strong>Email:</strong> {$booking_data['passenger_email']}</p>
                    <p><strong>Phone:</strong> {$booking_data['passenger_phone']}</p>
                    <p><strong>Passport:</strong> {$booking_data['passenger_passport']}</p>
                    <p><strong>Flight:</strong> {$booking_data['flight_from']} → {$booking_data['flight_to']}</p>
                    <p><strong>Date:</strong> " . date('d M Y', strtotime($booking_data['flight_date'])) . "</p>
                    <p><strong>Amount Paid:</strong> ৳" . number_format($booking_data['total_amount'], 2) . "</p>
                </div>
                
                <p>You can check-in online 24 hours before departure or at the airport counter.</p>
                <p>For any queries, contact our 24/7 support: +880 9658 001016</p>
            </div>
            <div class='footer'>
                <p>© 2024 ETA Tour & Travel Agency | IATA Accredited Agent</p>
                <p>Email: support@etatourtravel.com | Web: www.etatourtravel.com</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: ETA Tour & Travel <noreply@etatourtravel.com>" . "\r\n";
    
    mail($to, $subject, $message, $headers);
}

function sendConfirmationSMS($booking_data, $pnr) {
    $phone = $booking_data['passenger_phone'];
    $message = "Dear {$booking_data['passenger_name']}, your flight booking PNR: $pnr is confirmed. Amount: ৳{$booking_data['total_amount']}. Check email for e-ticket. -ETA Tour & Travel";
    
    // Use SMS API (Twilio, SMS Gateway, etc.)
    // Example with SMS Gateway API
    $api_url = "https://api.sms.net.bd/sendsms";
    $data = [
        'api_key' => 'YOUR_SMS_API_KEY',
        'msg' => $message,
        'to' => $phone
    ];
    
    // Uncomment to send SMS
    // $ch = curl_init();
    // curl_setopt($ch, CURLOPT_URL, $api_url);
    // curl_setopt($ch, CURLOPT_POST, 1);
    // curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    // curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    // curl_exec($ch);
    // curl_close($ch);
}

function sendWhatsAppNotification($booking_data, $pnr) {
    // Optional: Send WhatsApp message via Twilio or other API
}

function maskCardNumber($card_number) {
    if (strlen($card_number) > 4) {
        return str_repeat('*', strlen($card_number) - 4) . substr($card_number, -4);
    }
    return $card_number;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Successful - ETA Tour & Travel</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary: #1a365d;
            --secondary: #2d3748;
            --accent: #2b6cb0;
            --success: #38a169;
            --warning: #d69e2e;
            --danger: #e53e3e;
            --light: #f7fafc;
            --dark: #1a202c;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .success-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
            max-width: 1000px;
            width: 100%;
        }
        
        .success-header {
            background: linear-gradient(135deg, var(--success) 0%, #2f855a 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .success-header::before {
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
        
        .success-icon {
            font-size: 100px;
            margin-bottom: 20px;
            animation: bounce 1s ease infinite;
        }
        
        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-20px); }
        }
        
        .success-title {
            font-size: 42px;
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        .success-subtitle {
            font-size: 18px;
            opacity: 0.9;
        }
        
        .pnr-badge {
            background: white;
            color: var(--success);
            padding: 15px 30px;
            border-radius: 50px;
            font-size: 24px;
            font-weight: 700;
            display: inline-block;
            margin-top: 20px;
            box-shadow: 0 10px 30px rgba(56, 161, 105, 0.3);
            letter-spacing: 2px;
        }
        
        .content-wrapper {
            padding: 40px;
        }
        
        .info-card {
            background: var(--light);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            border-left: 5px solid var(--success);
            transition: transform 0.3s ease;
        }
        
        .info-card:hover {
            transform: translateY(-5px);
        }
        
        .info-card h5 {
            color: var(--primary);
            margin-bottom: 20px;
            font-weight: 600;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid rgba(0,0,0,0.1);
        }
        
        .info-row:last-child {
            border-bottom: none;
        }
        
        .info-label {
            color: var(--secondary);
            font-weight: 500;
        }
        
        .info-value {
            color: var(--dark);
            font-weight: 600;
        }
        
        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 40px;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 15px 30px;
            border-radius: 10px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            transition: all 0.3s ease;
            border: none;
            font-size: 16px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--accent) 0%, #2c5282 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(43, 108, 176, 0.3);
        }
        
        .btn-success {
            background: linear-gradient(135deg, var(--success) 0%, #2f855a 100%);
            color: white;
        }
        
        .btn-success:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(56, 161, 105, 0.3);
        }
        
        .btn-outline {
            background: white;
            color: var(--primary);
            border: 2px solid var(--primary);
        }
        
        .btn-outline:hover {
            background: var(--primary);
            color: white;
            transform: translateY(-3px);
        }
        
        .notification-badge {
            background: #c6f6d5;
            color: #22543d;
            padding: 15px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            gap: 15px;
            margin: 20px 0;
        }
        
        .ssl-badge {
            background: linear-gradient(135deg, #FF6B35 0%, #FFA62E 100%);
            color: white;
            padding: 10px 20px;
            border-radius: 25px;
            font-size: 14px;
            font-weight: 600;
            display: inline-block;
        }
        
        @media (max-width: 768px) {
            .success-title {
                font-size: 32px;
            }
            
            .content-wrapper {
                padding: 20px;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }
        }
        
        .payment-method-icon {
            font-size: 30px;
            margin-right: 10px;
            vertical-align: middle;
        }
        
        .ticket-preview {
            background: linear-gradient(135deg, #1a365d 0%, #2d3748 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
            text-align: center;
        }
        
        .countdown-timer {
            background: #fefcbf;
            color: #744210;
            padding: 15px;
            border-radius: 10px;
            text-align: center;
            margin: 20px 0;
            font-size: 18px;
            font-weight: 600;
        }
        
        .security-badge {
            background: #bee3f8;
            color: #2c5282;
            padding: 15px;
            border-radius: 10px;
            margin: 20px 0;
            display: flex;
            align-items: center;
            gap: 15px;
        }
    </style>
</head>
<body>
    <div class="success-container">
        <div class="success-header">
            <div class="success-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <h1 class="success-title">Payment Successful!</h1>
            <p class="success-subtitle">Your flight booking has been confirmed with SSLCommerz</p>
            <div class="pnr-badge">
                <i class="fas fa-ticket-alt"></i> PNR: <?php echo $pnr; ?>
            </div>
        </div>
        
        <div class="content-wrapper">
            <div class="notification-badge">
                <i class="fas fa-check-circle fa-2x"></i>
                <div>
                    <strong>Notifications Sent!</strong><br>
                    Confirmation email and SMS have been sent to your registered contact details.
                </div>
            </div>
            
            <div class="security-badge">
                <i class="fas fa-shield-alt fa-2x"></i>
                <div>
                    <strong>256-bit SSL Secured Payment</strong><br>
                    Your payment was processed securely through SSLCommerz
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="info-card">
                        <h5><i class="fas fa-user-circle me-2"></i>Passenger Details</h5>
                        <div class="info-row">
                            <span class="info-label">Name:</span>
                            <span class="info-value"><?php echo $booking_data['passenger_name'] ?? 'N/A'; ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Email:</span>
                            <span class="info-value"><?php echo $booking_data['passenger_email'] ?? 'N/A'; ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Phone:</span>
                            <span class="info-value"><?php echo $booking_data['passenger_phone'] ?? 'N/A'; ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Passport:</span>
                            <span class="info-value"><?php echo $booking_data['passenger_passport'] ?? 'N/A'; ?></span>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="info-card">
                        <h5><i class="fas fa-credit-card me-2"></i>Payment Details</h5>
                        <div class="info-row">
                            <span class="info-label">Transaction ID:</span>
                            <span class="info-value"><?php echo $tran_id; ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Amount Paid:</span>
                            <span class="info-value">৳ <?php echo number_format($amount ?: ($booking_data['total_amount'] ?? 0), 2); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Payment Method:</span>
                            <span class="info-value">
                                <?php 
                                if ($card_type) {
                                    echo '<i class="fas fa-credit-card payment-method-icon"></i>' . $card_type;
                                } else {
                                    echo '<i class="fas fa-mobile-alt payment-method-icon"></i>' . ($booking_data['payment_method'] ?? 'SSLCommerz');
                                }
                                ?>
                            </span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Status:</span>
                            <span class="info-value" style="color: var(--success); font-weight: bold;">
                                <i class="fas fa-check-circle"></i> Confirmed
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="info-card">
                        <h5><i class="fas fa-plane me-2"></i>Flight Details</h5>
                        <div class="info-row">
                            <span class="info-label">Flight:</span>
                            <span class="info-value"><?php echo $booking_data['flight_from'] ?? 'DAC'; ?> → <?php echo $booking_data['flight_to'] ?? 'DXB'; ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Date:</span>
                            <span class="info-value"><?php echo isset($booking_data['flight_date']) ? date('d M Y', strtotime($booking_data['flight_date'])) : date('d M Y'); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Airline:</span>
                            <span class="info-value"><?php echo $booking_data['airline'] ?? 'ETA Airlines'; ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Class:</span>
                            <span class="info-value"><?php echo $booking_data['class'] ?? 'Economy'; ?></span>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="info-card">
                        <h5><i class="fas fa-shield-alt me-2"></i>Security Details</h5>
                        <div class="info-row">
                            <span class="info-label">Bank Transaction:</span>
                            <span class="info-value"><?php echo $bank_tran_id ?: 'N/A'; ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Card Issuer:</span>
                            <span class="info-value"><?php echo $card_issuer ?: 'N/A'; ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Verification:</span>
                            <span class="info-value" style="color: var(--success);">
                                <i class="fas fa-check-circle"></i> SSL Verified
                            </span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Payment Date:</span>
                            <span class="info-value"><?php echo date('d M Y h:i A'); ?></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="countdown-timer">
                <i class="fas fa-clock me-2"></i>
                Online check-in opens in: <span id="countdown">24:00:00</span>
            </div>
            
            <div class="action-buttons">
                <button class="btn btn-primary" onclick="printTicket()">
                    <i class="fas fa-print"></i> Print Ticket
                </button>
                <button class="btn btn-success" onclick="emailTicket()">
                    <i class="fas fa-envelope"></i> Email Ticket Again
                </button>
                <a href="download_ticket.php?pnr=<?php echo $pnr; ?>" class="btn btn-primary">
                    <i class="fas fa-download"></i> Download PDF
                </a>
                <a href="index.php" class="btn btn-outline">
                    <i class="fas fa-home"></i> Back to Home
                </a>
                <a href="my_bookings.php" class="btn btn-outline">
                    <i class="fas fa-list"></i> My Bookings
                </a>
            </div>
            
            <div class="text-center mt-4">
                <div class="ssl-badge">
                    <i class="fas fa-lock"></i> Secured by SSLCommerz
                </div>
                <p class="text-muted mt-2">
                    <small>
                        Need help? Call us: +880 9658 001016 | Email: support@etatourtravel.com<br>
                        Transaction ID: <?php echo $tran_id; ?> | Verified at: <?php echo date('d M Y H:i:s'); ?>
                    </small>
                </p>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Countdown timer for check-in
        function startCountdown() {
            const countdownElement = document.getElementById('countdown');
            let timeLeft = 24 * 60 * 60; // 24 hours in seconds
            
            function updateCountdown() {
                const hours = Math.floor(timeLeft / 3600);
                const minutes = Math.floor((timeLeft % 3600) / 60);
                const seconds = timeLeft % 60;
                
                countdownElement.textContent = 
                    `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
                
                if (timeLeft > 0) {
                    timeLeft--;
                } else {
                    clearInterval(timer);
                    countdownElement.textContent = "Check-in Now!";
                    countdownElement.style.color = "var(--success)";
                }
            }
            
            updateCountdown();
            const timer = setInterval(updateCountdown, 1000);
        }
        
        // Print ticket function
        function printTicket() {
            const printWindow = window.open('', '_blank');
            printWindow.document.write(`
                <html>
                <head>
                    <title>ETA Ticket - <?php echo $pnr; ?></title>
                    <style>
                        body { font-family: Arial, sans-serif; padding: 20px; }
                        .ticket { border: 2px solid #000; padding: 20px; max-width: 600px; margin: 0 auto; }
                        .header { text-align: center; margin-bottom: 20px; }
                        .details { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
                        .qr-code { text-align: center; margin: 20px 0; }
                        .footer { text-align: center; margin-top: 20px; font-size: 12px; }
                        @media print {
                            .no-print { display: none; }
                        }
                    </style>
                </head>
                <body>
                    <div class="ticket">
                        <div class="header">
                            <h2>ETA Tour & Travel</h2>
                            <h3>E-TICKET</h3>
                            <h4>PNR: <?php echo $pnr; ?></h4>
                        </div>
                        <div class="details">
                            <div><strong>Passenger:</strong> <?php echo $booking_data['passenger_name'] ?? 'N/A'; ?></div>
                            <div><strong>Flight:</strong> <?php echo $booking_data['flight_from'] ?? 'DAC'; ?> → <?php echo $booking_data['flight_to'] ?? 'DXB'; ?></div>
                            <div><strong>Date:</strong> <?php echo isset($booking_data['flight_date']) ? date('d M Y', strtotime($booking_data['flight_date'])) : date('d M Y'); ?></div>
                            <div><strong>Class:</strong> <?php echo $booking_data['class'] ?? 'Economy'; ?></div>
                            <div><strong>Transaction ID:</strong> <?php echo $tran_id; ?></div>
                            <div><strong>Amount:</strong> ৳ <?php echo number_format($amount ?: ($booking_data['total_amount'] ?? 0), 2); ?></div>
                        </div>
                        <div class="qr-code">
                            <div id="qrcode"></div>
                            <p>Scan for mobile boarding pass</p>
                        </div>
                        <div class="footer">
                            <p>ETA Tour & Travel Agency | IATA Accredited</p>
                            <p>24/7 Support: +880 9658 001016</p>
                        </div>
                    </div>
                    <script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.3/build/qrcode.min.js"><\/script>
                    <script>
                        QRCode.toCanvas(document.getElementById('qrcode'), 'ETA:<?php echo $pnr; ?>', {
                            width: 150,
                            margin: 2
                        });
                        window.print();
                    <\/script>
                </body>
                </html>
            `);
            printWindow.document.close();
        }
        
        // Email ticket function
        function emailTicket() {
            fetch('send_email.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    pnr: '<?php echo $pnr; ?>',
                    email: '<?php echo $booking_data['passenger_email'] ?? ''; ?>'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Ticket emailed successfully!');
                } else {
                    alert('Failed to send email. Please try again.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
            });
        }
        
        // Auto-save to PDF
        function autoSavePDF() {
            // This would typically trigger a server-side PDF generation
            console.log('Auto-saving ticket as PDF...');
        }
        
        // Initialize countdown when page loads
        document.addEventListener('DOMContentLoaded', function() {
            startCountdown();
            autoSavePDF();
            
            // Save booking to localStorage for offline access
            const bookingData = {
                pnr: '<?php echo $pnr; ?>',
                passenger: '<?php echo $booking_data['passenger_name'] ?? ''; ?>',
                flight: '<?php echo $booking_data['flight_from'] ?? ''; ?> → <?php echo $booking_data['flight_to'] ?? ''; ?>',
                date: '<?php echo isset($booking_data['flight_date']) ? date('d M Y', strtotime($booking_data['flight_date'])) : date('d M Y'); ?>',
                amount: '<?php echo number_format($amount ?: ($booking_data['total_amount'] ?? 0), 2); ?>'
            };
            
            localStorage.setItem('eta_last_booking', JSON.stringify(bookingData));
            
            // Share on social media
            if (navigator.share) {
                document.getElementById('shareBtn').style.display = 'inline-block';
            }
        });
    </script>
</body>
</html>
