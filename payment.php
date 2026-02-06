<?php
session_start();
include_once 'helpers/helper.php';
include_once 'config/database.php';

// Get booking details from session or database
$booking_id = $_GET['booking_id'] ?? $_SESSION['booking_id'] ?? null;
$total_amount = $_GET['amount'] ?? $_SESSION['booking_amount'] ?? 0;

// If no booking ID, redirect to booking page
if (!$booking_id || $total_amount <= 0) {
    header('Location: book_flight.php');
    exit();
}

// SSLCommerz Configuration
$store_id = 'etato690d02c67cd99';
$store_password = 'etato690d02c67cd99@ssl';
$is_live = true; // Set to true for live production

// Generate transaction ID
$tran_id = "ETA" . date('YmdHis') . rand(1000, 9999);

// Save transaction data to session
$_SESSION['payment_data'] = [
    'tran_id' => $tran_id,
    'booking_id' => $booking_id,
    'total_amount' => $total_amount,
    'currency' => 'BDT',
    'payment_time' => date('Y-m-d H:i:s')
];
?>

<?php subview('header.php'); ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secure Payment - ETA Tour & Travel 2026</title>
    
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
            color: #333;
        }
        
        .payment-container {
            max-width: 1400px;
            margin: 30px auto;
            padding: 20px;
        }
        
        .payment-header {
            text-align: center;
            margin-bottom: 40px;
            padding: 30px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(26, 54, 93, 0.2);
        }
        
        .payment-header h1 {
            font-size: 3rem;
            font-weight: 800;
            margin-bottom: 15px;
            letter-spacing: 1px;
        }
        
        .payment-header p {
            font-size: 1.1rem;
            opacity: 0.9;
        }
        
        .ssl-badge {
            display: inline-block;
            background: linear-gradient(135deg, #FF6B35 0%, #FFA62E 100%);
            color: white;
            padding: 8px 25px;
            border-radius: 30px;
            font-weight: 700;
            font-size: 1rem;
            margin-top: 15px;
            box-shadow: 0 4px 15px rgba(255, 107, 53, 0.3);
        }
        
        .payment-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
            margin-bottom: 40px;
        }
        
        @media (max-width: 992px) {
            .payment-grid {
                grid-template-columns: 1fr;
            }
        }
        
        .payment-methods {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
        }
        
        .payment-summary {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            position: sticky;
            top: 30px;
        }
        
        .section-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 3px solid var(--accent);
        }
        
        .payment-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }
        
        .payment-tab {
            flex: 1;
            min-width: 120px;
            padding: 15px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            background: white;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
        }
        
        .payment-tab:hover {
            border-color: var(--accent);
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(43, 108, 176, 0.1);
        }
        
        .payment-tab.active {
            border-color: var(--accent);
            background: rgba(43, 108, 176, 0.1);
        }
        
        .payment-tab i {
            font-size: 2rem;
            margin-bottom: 10px;
            display: block;
        }
        
        .payment-tab.bkash i { color: #e2136e; }
        .payment-tab.nagad i { color: #f8a61c; }
        .payment-tab.card i { color: var(--primary); }
        .payment-tab.bank i { color: var(--success); }
        .payment-tab.rocket i { color: #5e72e4; }
        .payment-tab.upay i { color: #00a8ff; }
        .payment-tab.cash i { color: var(--danger); }
        
        .payment-form {
            display: none;
            animation: fadeIn 0.5s ease;
        }
        
        .payment-form.active {
            display: block;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
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
            padding: 15px;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
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
        
        .input-group {
            display: flex;
            gap: 15px;
        }
        
        .input-group .form-control {
            flex: 1;
        }
        
        .card-preview {
            display: flex;
            gap: 15px;
            margin-top: 10px;
        }
        
        .card-icon {
            width: 60px;
            height: 40px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f3f4f6;
            color: #6b7280;
            font-size: 1.5rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .card-icon:hover {
            background: #e5e7eb;
        }
        
        .card-icon.active {
            background: var(--accent);
            color: white;
        }
        
        .amount-display {
            text-align: center;
            margin: 30px 0;
            padding: 25px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            border-radius: 15px;
            color: white;
        }
        
        .amount-label {
            font-size: 1rem;
            opacity: 0.9;
            margin-bottom: 10px;
        }
        
        .amount-value {
            font-size: 3.5rem;
            font-weight: 800;
            line-height: 1;
        }
        
        .currency {
            font-size: 1.5rem;
            opacity: 0.9;
        }
        
        .summary-item {
            display: flex;
            justify-content: space-between;
            padding: 15px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .summary-item:last-child {
            border-bottom: none;
            font-weight: 700;
            font-size: 1.1rem;
            color: var(--primary);
        }
        
        .summary-label {
            color: #6b7280;
        }
        
        .summary-value {
            font-weight: 600;
        }
        
        .payment-btn {
            width: 100%;
            padding: 20px;
            background: linear-gradient(135deg, var(--success) 0%, #059669 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 25px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .payment-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(16, 185, 129, 0.3);
        }
        
        .payment-btn:disabled {
            background: #9ca3af;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        
        .security-badges {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 25px;
            flex-wrap: wrap;
        }
        
        .security-badge {
            background: #f3f4f6;
            padding: 12px 20px;
            border-radius: 30px;
            font-size: 0.9rem;
            color: #6b7280;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .qr-section {
            text-align: center;
            margin: 30px 0;
            padding: 25px;
            background: #f9fafb;
            border-radius: 15px;
            border: 2px dashed #d1d5db;
        }
        
        .qr-code {
            width: 200px;
            height: 200px;
            margin: 0 auto 20px;
            background: white;
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .qr-instruction {
            font-size: 0.9rem;
            color: #6b7280;
            margin-top: 15px;
        }
        
        .countdown {
            background: #fef3c7;
            color: #92400e;
            padding: 15px;
            border-radius: 10px;
            text-align: center;
            margin: 20px 0;
            font-weight: 600;
        }
        
        .countdown-timer {
            font-size: 1.5rem;
            font-family: monospace;
            margin-top: 5px;
        }
        
        .back-btn {
            display: inline-block;
            padding: 12px 30px;
            background: white;
            color: var(--primary);
            border: 2px solid var(--primary);
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            margin-top: 20px;
        }
        
        .back-btn:hover {
            background: var(--primary);
            color: white;
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
        
        .otp-section {
            display: none;
        }
        
        .otp-inputs {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin: 20px 0;
        }
        
        .otp-input {
            width: 50px;
            height: 50px;
            text-align: center;
            font-size: 1.5rem;
            font-weight: bold;
            border: 2px solid #d1d5db;
            border-radius: 8px;
        }
        
        .otp-input:focus {
            border-color: var(--accent);
        }
        
        .resend-otp {
            text-align: center;
            margin-top: 15px;
            font-size: 0.9rem;
        }
        
        .resend-otp a {
            color: var(--accent);
            text-decoration: none;
            font-weight: 600;
        }
        
        @media (max-width: 768px) {
            .payment-header h1 {
                font-size: 2rem;
            }
            
            .payment-tabs {
                flex-direction: column;
            }
            
            .input-group {
                flex-direction: column;
            }
            
            .amount-value {
                font-size: 2.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="payment-container">
        <div class="payment-header">
            <h1>Secure Payment Gateway 2026</h1>
            <p>Complete your booking with our secure SSLCommerz payment system</p>
            <div class="ssl-badge">
                <i class="fas fa-lock"></i> Secured by SSLCommerz
            </div>
        </div>
        
        <div class="alert alert-success" id="successAlert">
            <i class="fas fa-check-circle"></i> Payment processed successfully! Redirecting...
        </div>
        
        <div class="alert alert-error" id="errorAlert">
            <i class="fas fa-exclamation-circle"></i> <span id="errorMessage"></span>
        </div>
        
        <div class="payment-grid">
            <!-- Left Column: Payment Methods -->
            <div class="payment-methods">
                <h2 class="section-title">Select Payment Method</h2>
                
                <div class="payment-tabs">
                    <div class="payment-tab bkash active" data-method="bkash">
                        <i class="fas fa-mobile-alt"></i>
                        <div>bKash</div>
                    </div>
                    <div class="payment-tab nagad" data-method="nagad">
                        <i class="fas fa-wallet"></i>
                        <div>Nagad</div>
                    </div>
                    <div class="payment-tab card" data-method="card">
                        <i class="fas fa-credit-card"></i>
                        <div>Card</div>
                    </div>
                    <div class="payment-tab bank" data-method="bank">
                        <i class="fas fa-university"></i>
                        <div>Bank</div>
                    </div>
                    <div class="payment-tab rocket" data-method="rocket">
                        <i class="fas fa-bolt"></i>
                        <div>Rocket</div>
                    </div>
                    <div class="payment-tab upay" data-method="upay">
                        <i class="fas fa-qrcode"></i>
                        <div>Upay</div>
                    </div>
                    <div class="payment-tab cash" data-method="cash">
                        <i class="fas fa-money-bill-wave"></i>
                        <div>Cash</div>
                    </div>
                </div>
                
                <!-- bKash Payment Form -->
                <form id="bkashForm" class="payment-form active">
                    <div class="form-group">
                        <label class="form-label">bKash Account Number</label>
                        <input type="tel" class="form-control" id="bkashNumber" placeholder="01XXXXXXXXX" maxlength="11" required>
                        <small class="text-muted">Enter your bKash registered mobile number</small>
                    </div>
                    
                    <div class="otp-section" id="bkashOtpSection">
                        <div class="form-group">
                            <label class="form-label">Enter OTP</label>
                            <div class="otp-inputs">
                                <input type="text" class="otp-input" maxlength="1" data-index="1">
                                <input type="text" class="otp-input" maxlength="1" data-index="2">
                                <input type="text" class="otp-input" maxlength="1" data-index="3">
                                <input type="text" class="otp-input" maxlength="1" data-index="4">
                                <input type="text" class="otp-input" maxlength="1" data-index="5">
                                <input type="text" class="otp-input" maxlength="1" data-index="6">
                            </div>
                            <input type="hidden" id="bkashOtp">
                        </div>
                        
                        <div class="resend-otp">
                            Didn't receive OTP? <a href="#" id="resendBkashOtp">Resend</a> (59s)
                        </div>
                    </div>
                    
                    <div class="qr-section">
                        <div class="qr-code" id="bkashQrCode">
                            <!-- QR Code will be generated here -->
                        </div>
                        <p class="qr-instruction">Scan QR code to pay with bKash app</p>
                    </div>
                </form>
                
                <!-- Nagad Payment Form -->
                <form id="nagadForm" class="payment-form">
                    <div class="form-group">
                        <label class="form-label">Nagad Account Number</label>
                        <input type="tel" class="form-control" id="nagadNumber" placeholder="01XXXXXXXXX" maxlength="11" required>
                        <small class="text-muted">Enter your Nagad registered mobile number</small>
                    </div>
                    
                    <div class="otp-section" id="nagadOtpSection">
                        <div class="form-group">
                            <label class="form-label">Enter OTP</label>
                            <div class="otp-inputs">
                                <input type="text" class="otp-input" maxlength="1" data-index="1">
                                <input type="text" class="otp-input" maxlength="1" data-index="2">
                                <input type="text" class="otp-input" maxlength="1" data-index="3">
                                <input type="text" class="otp-input" maxlength="1" data-index="4">
                                <input type="text" class="otp-input" maxlength="1" data-index="5">
                                <input type="text" class="otp-input" maxlength="1" data-index="6">
                            </div>
                            <input type="hidden" id="nagadOtp">
                        </div>
                        
                        <div class="resend-otp">
                            Didn't receive OTP? <a href="#" id="resendNagadOtp">Resend</a> (59s)
                        </div>
                    </div>
                    
                    <div class="qr-section">
                        <div class="qr-code" id="nagadQrCode">
                            <!-- QR Code will be generated here -->
                        </div>
                        <p class="qr-instruction">Scan QR code to pay with Nagad app</p>
                    </div>
                </form>
                
                <!-- Card Payment Form -->
                <form id="cardForm" class="payment-form">
                    <div class="form-group">
                        <label class="form-label">Card Type</label>
                        <div class="card-preview">
                            <div class="card-icon visa active" data-type="visa">
                                <i class="fab fa-cc-visa"></i>
                            </div>
                            <div class="card-icon mastercard" data-type="mastercard">
                                <i class="fab fa-cc-mastercard"></i>
                            </div>
                            <div class="card-icon amex" data-type="amex">
                                <i class="fab fa-cc-amex"></i>
                            </div>
                            <div class="card-icon discover" data-type="discover">
                                <i class="fab fa-cc-discover"></i>
                            </div>
                        </div>
                        <input type="hidden" id="cardType" value="visa">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Card Number</label>
                        <input type="text" class="form-control" id="cardNumber" placeholder="1234 5678 9012 3456" maxlength="19" required>
                        <small class="text-muted">Enter your 16-digit card number</small>
                    </div>
                    
                    <div class="input-group">
                        <div class="form-group">
                            <label class="form-label">Expiry Date</label>
                            <input type="text" class="form-control" id="expiryDate" placeholder="MM/YY" maxlength="5" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">CVV</label>
                            <input type="password" class="form-control" id="cvv" placeholder="123" maxlength="4" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Card Holder Name</label>
                        <input type="text" class="form-control" id="cardHolder" placeholder="JOHN DOE" required>
                    </div>
                </form>
                
                <!-- Bank Transfer Form -->
                <form id="bankForm" class="payment-form">
                    <div class="form-group">
                        <label class="form-label">Select Bank</label>
                        <select class="form-control" id="bankName" required>
                            <option value="">Choose Bank</option>
                            <option value="dbbl">Dutch-Bangla Bank (DBBL)</option>
                            <option value="brac">BRAC Bank</option>
                            <option value="city">City Bank</option>
                            <option value="eastern">Eastern Bank</option>
                            <option value="prime">Prime Bank</option>
                            <option value="islami">Islami Bank</option>
                            <option value="sonali">Sonali Bank</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Account Number</label>
                        <input type="text" class="form-control" id="accountNumber" placeholder="Enter account number" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Transaction ID</label>
                        <input type="text" class="form-control" id="bankTransactionId" placeholder="Enter bank transaction ID" required>
                        <small class="text-muted">After payment, enter the transaction ID provided by your bank</small>
                    </div>
                </form>
                
                <!-- Rocket Payment Form -->
                <form id="rocketForm" class="payment-form">
                    <div class="form-group">
                        <label class="form-label">Rocket Account Number</label>
                        <input type="tel" class="form-control" id="rocketNumber" placeholder="01XXXXXXXXX" maxlength="11" required>
                        <small class="text-muted">Enter your Rocket registered mobile number</small>
                    </div>
                    
                    <div class="otp-section" id="rocketOtpSection">
                        <div class="form-group">
                            <label class="form-label">Enter OTP</label>
                            <div class="otp-inputs">
                                <input type="text" class="otp-input" maxlength="1" data-index="1">
                                <input type="text" class="otp-input" maxlength="1" data-index="2">
                                <input type="text" class="otp-input" maxlength="1" data-index="3">
                                <input type="text" class="otp-input" maxlength="1" data-index="4">
                                <input type="text" class="otp-input" maxlength="1" data-index="5">
                                <input type="text" class="otp-input" maxlength="1" data-index="6">
                            </div>
                            <input type="hidden" id="rocketOtp">
                        </div>
                        
                        <div class="resend-otp">
                            Didn't receive OTP? <a href="#" id="resendRocketOtp">Resend</a> (59s)
                        </div>
                    </div>
                </form>
                
                <!-- Upay Payment Form -->
                <form id="upayForm" class="payment-form">
                    <div class="qr-section">
                        <div class="qr-code" id="upayQrCode">
                            <!-- QR Code will be generated here -->
                        </div>
                        <p class="qr-instruction">Scan QR code with Upay app to complete payment</p>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Upay Transaction ID</label>
                        <input type="text" class="form-control" id="upayTransactionId" placeholder="Enter transaction ID from Upay" required>
                        <small class="text-muted">After scanning QR code, enter the transaction ID</small>
                    </div>
                </form>
                
                <!-- Cash Payment Form -->
                <form id="cashForm" class="payment-form">
                    <div class="alert alert-success">
                        <i class="fas fa-info-circle"></i> 
                        <strong>Pay at our office</strong><br>
                        Visit our office with your booking details to complete payment in cash.
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Office Address</label>
                        <div style="padding: 15px; background: #f3f4f6; border-radius: 10px;">
                            <strong>ETA Tour & Travel Agency</strong><br>
                            123 Airport Road, Dhaka-1229, Bangladesh<br>
                            Phone: +880 9658 001016<br>
                            Hours: 9:00 AM - 8:00 PM (7 days)
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Payment Reference</label>
                        <input type="text" class="form-control" id="cashReference" placeholder="Enter reference number (if any)">
                    </div>
                </form>
                
                <div class="security-badges">
                    <div class="security-badge">
                        <i class="fas fa-shield-alt"></i> 256-bit SSL
                    </div>
                    <div class="security-badge">
                        <i class="fas fa-lock"></i> PCI DSS Compliant
                    </div>
                    <div class="security-badge">
                        <i class="fas fa-user-shield"></i> Fraud Protection
                    </div>
                </div>
            </div>
            
            <!-- Right Column: Payment Summary -->
            <div class="payment-summary">
                <h2 class="section-title">Payment Summary</h2>
                
                <div class="amount-display">
                    <div class="amount-label">Total Amount to Pay</div>
                    <div class="amount-value">৳ <?php echo number_format($total_amount, 2); ?></div>
                    <div class="currency">Bangladeshi Taka (BDT)</div>
                </div>
                
                <div class="countdown">
                    <div>Complete payment within</div>
                    <div class="countdown-timer" id="paymentTimer">15:00</div>
                    <small>Your booking will be held until timer expires</small>
                </div>
                
                <div class="summary-details">
                    <div class="summary-item">
                        <span class="summary-label">Transaction ID:</span>
                        <span class="summary-value"><?php echo $tran_id; ?></span>
                    </div>
                    <div class="summary-item">
                        <span class="summary-label">Booking ID:</span>
                        <span class="summary-value"><?php echo $booking_id; ?></span>
                    </div>
                    <div class="summary-item">
                        <span class="summary-label">Date:</span>
                        <span class="summary-value"><?php echo date('d M Y, h:i A'); ?></span>
                    </div>
                    <div class="summary-item">
                        <span class="summary-label">Payment Method:</span>
                        <span class="summary-value" id="selectedMethod">bKash</span>
                    </div>
                    <div class="summary-item">
                        <span class="summary-label">Service Charge:</span>
                        <span class="summary-value">৳ 0.00</span>
                    </div>
                    <div class="summary-item">
                        <span class="summary-label">Total:</span>
                        <span class="summary-value">৳ <?php echo number_format($total_amount, 2); ?></span>
                    </div>
                </div>
                
                <button class="payment-btn" id="processPaymentBtn">
                    <i class="fas fa-lock"></i>
                    <span id="btnText">Pay with bKash via SSLCommerz</span>
                    <span id="btnLoading" style="display: none;">
                        <i class="fas fa-spinner fa-spin"></i> Processing...
                    </span>
                </button>
                
                <a href="booking_details.php?id=<?php echo $booking_id; ?>" class="back-btn">
                    <i class="fas fa-arrow-left"></i> Back to Booking
                </a>
                
                <div class="text-center mt-4">
                    <small class="text-muted">
                        <i class="fas fa-info-circle"></i>
                        Your payment is protected by SSLCommerz security
                    </small>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- QRCode Generator -->
    <script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.3/build/qrcode.min.js"></script>
    
    <script>
        // Global variables
        let currentMethod = 'bkash';
        let countdownTime = 15 * 60; // 15 minutes in seconds
        let otpTimer = 59;
        let otpInterval;
        
        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            // Start countdown timer
            startCountdown();
            
            // Generate QR codes
            generateQrCodes();
            
            // Setup event listeners
            setupEventListeners();
            
            // Initialize OTP inputs
            initOtpInputs();
        });
        
        // Start payment countdown
        function startCountdown() {
            const timerElement = document.getElementById('paymentTimer');
            const interval = setInterval(() => {
                const minutes = Math.floor(countdownTime / 60);
                const seconds = countdownTime % 60;
                timerElement.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
                
                if (countdownTime <= 0) {
                    clearInterval(interval);
                    showError('Payment time expired. Please restart booking.');
                    disablePaymentButton();
                }
                
                countdownTime--;
            }, 1000);
        }
        
        // Generate QR codes for mobile payments
        function generateQrCodes() {
            const methods = ['bkash', 'nagad', 'upay'];
            const amounts = {
                'bkash': `৳<?php echo number_format($total_amount, 2); ?>`,
                'nagad': `৳<?php echo number_format($total_amount, 2); ?>`,
                'upay': `upi://pay?pa=eta.travel@upay&pn=ETA Travel&am=<?php echo $total_amount; ?>&cu=BDT&tn=<?php echo $tran_id; ?>`
            };
            
            methods.forEach(method => {
                const qrDiv = document.getElementById(`${method}QrCode`);
                if (qrDiv) {
                    QRCode.toCanvas(qrDiv, amounts[method], {
                        width: 170,
                        height: 170,
                        margin: 1,
                        color: {
                            dark: '#000000',
                            light: '#FFFFFF'
                        }
                    });
                }
            });
        }
        
        // Setup event listeners
        function setupEventListeners() {
            // Payment method tabs
            document.querySelectorAll('.payment-tab').forEach(tab => {
                tab.addEventListener('click', function() {
                    const method = this.dataset.method;
                    switchPaymentMethod(method);
                });
            });
            
            // Card type selection
            document.querySelectorAll('.card-icon').forEach(icon => {
                icon.addEventListener('click', function() {
                    document.querySelectorAll('.card-icon').forEach(i => i.classList.remove('active'));
                    this.classList.add('active');
                    document.getElementById('cardType').value = this.dataset.type;
                });
            });
            
            // Card number formatting
            document.getElementById('cardNumber').addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, '');
                if (value.length > 16) value = value.substr(0, 16);
                e.target.value = value.replace(/(\d{4})/g, '$1 ').trim();
            });
            
            // Expiry date formatting
            document.getElementById('expiryDate').addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, '');
                if (value.length > 4) value = value.substr(0, 4);
                if (value.length >= 2) {
                    e.target.value = value.substr(0, 2) + '/' + value.substr(2);
                } else {
                    e.target.value = value;
                }
            });
            
            // Process payment button
            document.getElementById('processPaymentBtn').addEventListener('click', processPayment);
            
            // OTP resend buttons
            document.getElementById('resendBkashOtp')?.addEventListener('click', function(e) {
                e.preventDefault();
                sendOtp('bkash');
            });
            
            document.getElementById('resendNagadOtp')?.addEventListener('click', function(e) {
                e.preventDefault();
                sendOtp('nagad');
            });
            
            document.getElementById('resendRocketOtp')?.addEventListener('click', function(e) {
                e.preventDefault();
                sendOtp('rocket');
            });
        }
        
        // Switch payment method
        function switchPaymentMethod(method) {
            currentMethod = method;
            
            // Update active tab
            document.querySelectorAll('.payment-tab').forEach(tab => {
                tab.classList.remove('active');
            });
            document.querySelector(`.payment-tab[data-method="${method}"]`).classList.add('active');
            
            // Update active form
            document.querySelectorAll('.payment-form').forEach(form => {
                form.classList.remove('active');
            });
            document.getElementById(`${method}Form`).classList.add('active');
            
            // Update selected method in summary
            document.getElementById('selectedMethod').textContent = 
                method.charAt(0).toUpperCase() + method.slice(1);
            
            // Update button text
            const btnText = document.getElementById('btnText');
            const methods = {
                'bkash': 'Pay with bKash via SSLCommerz',
                'nagad': 'Pay with Nagad via SSLCommerz',
                'card': 'Pay with Card via SSLCommerz',
                'bank': 'Pay via Bank Transfer',
                'rocket': 'Pay with Rocket via SSLCommerz',
                'upay': 'Pay with Upay QR Code',
                'cash': 'Confirm Cash Payment'
            };
            btnText.textContent = methods[method];
            
            // Hide/show OTP section for mobile payments
            if (['bkash', 'nagad', 'rocket'].includes(method)) {
                sendOtp(method);
            }
        }
        
        // Send OTP for mobile payments
        function sendOtp(method) {
            const numberInput = document.getElementById(`${method}Number`);
            if (!numberInput || !numberInput.value) {
                showError(`Please enter your ${method} number first`);
                return;
            }
            
            // Show OTP section
            const otpSection = document.getElementById(`${method}OtpSection`);
            if (otpSection) {
                otpSection.style.display = 'block';
            }
            
            // Generate random OTP (in production, this would come from API)
            const otp = Math.floor(100000 + Math.random() * 900000);
            document.getElementById(`${method}Otp`).value = otp;
            
            // Simulate sending OTP
            console.log(`OTP for ${method}: ${otp}`);
            
            // Start OTP timer
            startOtpTimer(method);
        }
        
        // Start OTP timer
        function startOtpTimer(method) {
            const resendLink = document.getElementById(`resend${method.charAt(0).toUpperCase() + method.slice(1)}Otp`);
            const parent = resendLink.parentElement;
            
            let timeLeft = 59;
            otpInterval = setInterval(() => {
                parent.innerHTML = `Didn't receive OTP? <a href="#" id="resend${method.charAt(0).toUpperCase() + method.slice(1)}Otp">Resend</a> (${timeLeft}s)`;
                
                if (timeLeft <= 0) {
                    clearInterval(otpInterval);
                    parent.innerHTML = `Didn't receive OTP? <a href="#" id="resend${method.charAt(0).toUpperCase() + method.slice(1)}Otp">Resend</a>`;
                    
                    // Re-attach event listener
                    document.getElementById(`resend${method.charAt(0).toUpperCase() + method.slice(1)}Otp`)
                        .addEventListener('click', function(e) {
                            e.preventDefault();
                            sendOtp(method);
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
                    
                    if (value && index < 6) {
                        const nextInput = document.querySelector(`.otp-input[data-index="${index + 1}"]`);
                        if (nextInput) nextInput.focus();
                    }
                    
                    // Validate OTP
                    if (index === 6) {
                        validateOtp();
                    }
                });
                
                input.addEventListener('keydown', function(e) {
                    if (e.key === 'Backspace' && !this.value && this.dataset.index > 1) {
                        const prevInput = document.querySelector(`.otp-input[data-index="${parseInt(this.dataset.index) - 1}"]`);
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
            
            const storedOtp = document.getElementById(`${currentMethod}Otp`)?.value;
            if (enteredOtp === storedOtp) {
                showSuccess('OTP verified successfully!');
                return true;
            } else {
                showError('Invalid OTP. Please try again.');
                return false;
            }
        }
        
        // Process payment
        function processPayment() {
            const btn = document.getElementById('processPaymentBtn');
            const btnLoading = document.getElementById('btnLoading');
            const btnText = document.getElementById('btnText');
            
            // Validate form based on method
            if (!validateForm()) {
                return;
            }
            
            // Show loading
            btn.disabled = true;
            btnText.style.display = 'none';
            btnLoading.style.display = 'inline';
            
            // Collect payment data
            const paymentData = {
                method: currentMethod,
                tran_id: '<?php echo $tran_id; ?>',
                amount: <?php echo $total_amount; ?>,
                booking_id: '<?php echo $booking_id; ?>',
                currency: 'BDT',
                timestamp: new Date().toISOString()
            };
            
            // Add method-specific data
            switch(currentMethod) {
                case 'bkash':
                case 'nagad':
                case 'rocket':
                    paymentData.mobile = document.getElementById(`${currentMethod}Number`).value;
                    paymentData.otp = document.getElementById(`${currentMethod}Otp`).value;
                    break;
                case 'card':
                    paymentData.card_number = document.getElementById('cardNumber').value.replace(/\s/g, '');
                    paymentData.expiry = document.getElementById('expiryDate').value;
                    paymentData.cvv = document.getElementById('cvv').value;
                    paymentData.card_holder = document.getElementById('cardHolder').value;
                    paymentData.card_type = document.getElementById('cardType').value;
                    break;
                case 'bank':
                    paymentData.bank_name = document.getElementById('bankName').value;
                    paymentData.account_number = document.getElementById('accountNumber').value;
                    paymentData.transaction_id = document.getElementById('bankTransactionId').value;
                    break;
                case 'upay':
                    paymentData.transaction_id = document.getElementById('upayTransactionId').value;
                    break;
                case 'cash':
                    paymentData.reference = document.getElementById('cashReference').value;
                    break;
            }
            
            // Submit to SSLCommerz
            submitToSSLCommerz(paymentData);
        }
        
        // Validate form
        function validateForm() {
            switch(currentMethod) {
                case 'bkash':
                case 'nagad':
                case 'rocket':
                    const number = document.getElementById(`${currentMethod}Number`);
                    if (!number.value || number.value.length !== 11) {
                        showError(`Please enter a valid ${currentMethod} number (11 digits)`);
                        number.focus();
                        return false;
                    }
                    
                    if (!validateOtp()) {
                        showError('Please enter valid OTP');
                        return false;
                    }
                    break;
                    
                case 'card':
                    const cardNumber = document.getElementById('cardNumber').value.replace(/\s/g, '');
                    if (!validateCardNumber(cardNumber)) {
                        showError('Please enter a valid card number');
                        return false;
                    }
                    
                    const expiry = document.getElementById('expiryDate').value;
                    if (!validateExpiryDate(expiry)) {
                        showError('Please enter a valid expiry date (MM/YY)');
                        return false;
                    }
                    
                    const cvv = document.getElementById('cvv').value;
                    if (!validateCvv(cvv)) {
                        showError('Please enter a valid CVV (3-4 digits)');
                        return false;
                    }
                    
                    const cardHolder = document.getElementById('cardHolder').value;
                    if (!cardHolder.trim()) {
                        showError('Please enter card holder name');
                        return false;
                    }
                    break;
                    
                case 'bank':
                    const bankName = document.getElementById('bankName').value;
                    const accountNumber = document.getElementById('accountNumber').value;
                    const transactionId = document.getElementById('bankTransactionId').value;
                    
                    if (!bankName || !accountNumber || !transactionId) {
                        showError('Please fill all bank transfer details');
                        return false;
                    }
                    break;
                    
                case 'upay':
                    const upayTransactionId = document.getElementById('upayTransactionId').value;
                    if (!upayTransactionId) {
                        showError('Please enter Upay transaction ID');
                        return false;
                    }
                    break;
            }
            
            return true;
        }
        
        // Validate card number using Luhn algorithm
        function validateCardNumber(number) {
            let sum = 0;
            let isEven = false;
            
            for (let i = number.length - 1; i >= 0; i--) {
                let digit = parseInt(number.charAt(i));
                
                if (isEven) {
                    digit *= 2;
                    if (digit > 9) digit -= 9;
                }
                
                sum += digit;
                isEven = !isEven;
            }
            
            return (sum % 10 === 0);
        }
        
        // Validate expiry date
        function validateExpiryDate(expiry) {
            const match = expiry.match(/^(\d{2})\/(\d{2})$/);
            if (!match) return false;
            
            const month = parseInt(match[1]);
            const year = parseInt('20' + match[2]);
            
            if (month < 1 || month > 12) return false;
            
            const currentDate = new Date();
            const currentYear = currentDate.getFullYear();
            const currentMonth = currentDate.getMonth() + 1;
            
            if (year < currentYear) return false;
            if (year === currentYear && month < currentMonth) return false;
            
            return true;
        }
        
        // Validate CVV
        function validateCvv(cvv) {
            return /^\d{3,4}$/.test(cvv);
        }
        
        // Submit to SSLCommerz
        function submitToSSLCommerz(paymentData) {
            // Create form for SSLCommerz
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '<?php echo $is_live ? "https://securepay.sslcommerz.com/gwprocess/v4/api.php" : "https://sandbox.sslcommerz.com/gwprocess/v4/api.php"; ?>';
            
            // Add SSLCommerz required fields
            const fields = {
                store_id: '<?php echo $store_id; ?>',
                store_passwd: '<?php echo $store_password; ?>',
                total_amount: paymentData.amount,
                currency: 'BDT',
                tran_id: paymentData.tran_id,
                success_url: window.location.origin + '/pay_success.php',
                fail_url: window.location.origin + '/pay_fail.php',
                cancel_url: window.location.origin + '/pay_cancel.php',
                cus_name: 'ETA Customer',
                cus_email: 'customer@eta.com',
                cus_phone: '01700000000',
                cus_add1: 'Dhaka',
                cus_city: 'Dhaka',
                cus_country: 'Bangladesh',
                shipping_method: 'NO',
                product_name: 'Flight Ticket',
                product_category: 'Travel',
                product_profile: 'Travel'
            };
            
            // Add fields to form
            Object.keys(fields).forEach(key => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = key;
                input.value = fields[key];
                form.appendChild(input);
            });
            
            // Add custom data
            const customInput = document.createElement('input');
            customInput.type = 'hidden';
            customInput.name = 'custom_data';
            customInput.value = JSON.stringify(paymentData);
            form.appendChild(customInput);
            
            // Submit form
            document.body.appendChild(form);
            form.submit();
        }
        
        // Show success message
        function showSuccess(message) {
            const alert = document.getElementById('successAlert');
            alert.querySelector('span').textContent = message;
            alert.style.display = 'block';
            setTimeout(() => {
                alert.style.display = 'none';
            }, 5000);
        }
        
        // Show error message
        function showError(message) {
            const alert = document.getElementById('errorAlert');
            alert.querySelector('span').textContent = message;
            alert.style.display = 'block';
            setTimeout(() => {
                alert.style.display = 'none';
            }, 5000);
        }
        
        // Disable payment button
        function disablePaymentButton() {
            const btn = document.getElementById('processPaymentBtn');
            btn.disabled = true;
            btn.style.background = '#9ca3af';
        }
    </script>
</body>
</html>

<?php subview('footer.php'); ?>
