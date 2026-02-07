<?php
declare(strict_types=1);
error_reporting(0); // Turn off error display in production
ini_set('display_errors', '0');

// Start session securely
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => $_SERVER['HTTP_HOST'] ?? '',
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
    session_start();
}

// Only process if form submitted and user is authenticated
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pay_but']) && isset($_SESSION['userId'])) {
    
    // CSRF Protection
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
        header('Location: ../payment.php?error=csrf');
        exit();
    }
    
    // Rate limiting for payment attempts
    if (paymentRateLimitExceeded($_SESSION['userId'])) {
        header('Location: ../payment.php?error=ratelimit&retry=' . (time() + 60));
        exit();
    }
    
    // Include database connection
    require '../helpers/init_conn_db.php';
    
    // Validate session data exists
    $requiredSessionVars = ['booking_id', 'flight_id', 'price', 'passengers', 'class', 'type', 'passenger_ids'];
    foreach ($requiredSessionVars as $var) {
        if (!isset($_SESSION[$var])) {
            header('Location: ../pass_form.php?error=session_expired');
            exit();
        }
    }
    
    // Initialize validation errors
    $errors = [];
    
    // Validate and sanitize payment details
    $card_no = filter_input(INPUT_POST, 'cc-number', FILTER_SANITIZE_STRING);
    $card_no = str_replace([' ', '-', '_'], '', $card_no);
    
    if (!validateCreditCard($card_no)) {
        $errors[] = 'Invalid credit card number';
    }
    
    $expiry = filter_input(INPUT_POST, 'cc-exp', FILTER_SANITIZE_STRING);
    if (!validateExpiryDate($expiry)) {
        $errors[] = 'Invalid or expired card';
    }
    
    $cvv = filter_input(INPUT_POST, 'cc-cvc', FILTER_SANITIZE_STRING);
    if (!preg_match('/^\d{3,4}$/', $cvv)) {
        $errors[] = 'Invalid CVV code';
    }
    
    // Get session variables with validation
    $flight_id = (int)$_SESSION['flight_id'];
    $price = (float)$_SESSION['price'];
    $passengers = (int)$_SESSION['passengers'];
    $passenger_ids = $_SESSION['passenger_ids'];
    $booking_id = (int)$_SESSION['booking_id'];
    $class = $_SESSION['class'];
    $type = $_SESSION['type'];
    $ret_date = $_SESSION['ret_date'] ?? null;
    
    // Validate passenger count matches IDs
    if (count($passenger_ids) !== $passengers) {
        $errors[] = 'Passenger data mismatch';
    }
    
    // Validate price is reasonable
    if ($price <= 0 || $price > 10000) {
        $errors[] = 'Invalid price amount';
    }
    
    // Check if booking still exists and is pending
    if (!validateBookingStatus($conn, $booking_id)) {
        $errors[] = 'Booking is no longer available';
    }
    
    // Check if seats are still available
    if (!validateSeatAvailability($conn, $flight_id, $passengers, $class)) {
        $errors[] = 'Seats are no longer available';
    }
    
    // Process payment if no validation errors
    if (empty($errors)) {
        // Start database transaction
        mysqli_begin_transaction($conn);
        
        try {
            // Process payment with payment gateway (simulated)
            $payment_result = processPayment($card_no, $expiry, $cvv, $price);
            
            if (!$payment_result['success']) {
                throw new Exception('Payment failed: ' . $payment_result['message']);
            }
            
            // Store payment token (not actual card details)
            $payment_token = bin2hex(random_bytes(16));
            $payment_reference = generatePaymentReference();
            
            // Insert encrypted payment record
            $sql = 'INSERT INTO PAYMENTS 
                    (user_id, booking_id, amount, currency, payment_token, 
                     payment_reference, payment_method, status, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())';
            
            $stmt = mysqli_prepare($conn, $sql);
            $currency = 'USD';
            $payment_method = 'credit_card';
            $status = 'completed';
            
            mysqli_stmt_bind_param($stmt, 'iidsssss',
                $_SESSION['userId'],
                $booking_id,
                $price,
                $currency,
                $payment_token,
                $payment_reference,
                $payment_method,
                $status
            );
            
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception('Failed to record payment');
            }
            $payment_id = mysqli_insert_id($conn);
            mysqli_stmt_close($stmt);
            
            // Process outbound flight tickets
            processFlightTickets($conn, $flight_id, $passenger_ids, $class, $price, $booking_id);
            
            // Process return flight if round trip
            if ($type === 'round-trip' && $ret_date) {
                $return_flight_id = findReturnFlight($conn, $flight_id, $ret_date);
                
                if ($return_flight_id) {
                    processFlightTickets($conn, $return_flight_id, $passenger_ids, $class, $price, $booking_id, true);
                } else {
                    throw new Exception('Return flight not available');
                }
            }
            
            // Update booking status
            updateBookingStatus($conn, $booking_id, 'confirmed');
            
            // Generate tickets and send confirmation
            $ticket_details = generateTickets($conn, $booking_id);
            sendBookingConfirmation($_SESSION['user_email'], $booking_id, $ticket_details, $payment_reference);
            
            // Log successful payment
            logPaymentEvent($payment_id, $_SESSION['userId'], 'payment_completed', $price);
            
            // Commit transaction
            mysqli_commit($conn);
            
            // Store confirmation data in session
            $_SESSION['booking_confirmation'] = [
                'booking_id' => $booking_id,
                'payment_reference' => $payment_reference,
                'amount' => $price,
                'passenger_count' => $passengers,
                'tickets' => $ticket_details
            ];
            
            // Clear sensitive session data
            unset($_SESSION['flight_id'], $_SESSION['price'], $_SESSION['passengers'],
                  $_SESSION['passenger_ids'], $_SESSION['class'], $_SESSION['type'], 
                  $_SESSION['ret_date'], $_SESSION['booking_id']);
            
            // Generate new CSRF token
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            
            // Redirect to success page
            header('Location: ../pay_success.php?ref=' . urlencode($payment_reference));
            exit();
            
        } catch (Exception $e) {
            // Rollback transaction on error
            mysqli_rollback($conn);
            
            // Log payment failure
            logPaymentEvent(0, $_SESSION['userId'], 'payment_failed', $price, $e->getMessage());
            
            // Store error for display
            $_SESSION['payment_error'] = $e->getMessage();
            header('Location: ../payment.php?error=payment_failed&code=' . urlencode($payment_result['code'] ?? 'unknown'));
            exit();
        }
        
        mysqli_close($conn);
    } else {
        // Store validation errors and redirect back
        $_SESSION['payment_errors'] = $errors;
        header('Location: ../payment.php?error=validation');
        exit();
    }
    
} else {
    // Invalid access
    header('Location: ../payment.php?error=invalid_access');
    exit();
}

/**
 * Validate credit card number using Luhn algorithm
 */
function validateCreditCard(string $number): bool {
    $number = preg_replace('/\D/', '', $number);
    
    if (empty($number) || strlen($number) < 13 || strlen($number) > 19) {
        return false;
    }
    
    // Check card type prefix
    $firstDigit = (int)$number[0];
    $firstTwoDigits = (int)substr($number, 0, 2);
    
    // Visa: 4
    // MasterCard: 51-55, 2221-2720
    // American Express: 34, 37
    // Discover: 6011, 644-649, 65, 622126-622925
    
    if ($firstDigit === 4) {
        $cardType = 'visa';
    } elseif ($firstTwoDigits >= 51 && $firstTwoDigits <= 55) {
        $cardType = 'mastercard';
    } elseif ($firstTwoDigits === 34 || $firstTwoDigits === 37) {
        $cardType = 'amex';
    } elseif (preg_match('/^6(?:011|4[4-9]|5)/', $number)) {
        $cardType = 'discover';
    } else {
        return false;
    }
    
    // Luhn algorithm
    $sum = 0;
    $length = strlen($number);
    
    for ($i = 0; $i < $length; $i++) {
        $digit = (int)$number[$length - $i - 1];
        
        if ($i % 2 === 1) {
            $digit *= 2;
            if ($digit > 9) {
                $digit -= 9;
            }
        }
        
        $sum += $digit;
    }
    
    return ($sum % 10 === 0);
}

/**
 * Validate expiry date (MM/YY format)
 */
function validateExpiryDate(string $expiry): bool {
    if (!preg_match('/^(0[1-9]|1[0-2])\/([0-9]{2})$/', $expiry, $matches)) {
        return false;
    }
    
    $month = (int)$matches[1];
    $year = (int)$matches[2];
    
    $currentYear = (int)date('y');
    $currentMonth = (int)date('m');
    
    if ($year < $currentYear) {
        return false;
    }
    
    if ($year === $currentYear && $month < $currentMonth) {
        return false;
    }
    
    // Check if expiry is within reasonable future (10 years)
    if ($year > $currentYear + 10) {
        return false;
    }
    
    return true;
}

/**
 * Process payment through payment gateway (simulated)
 */
function processPayment(string $card_no, string $expiry, string $cvv, float $amount): array {
    // In production, integrate with a real payment gateway like Stripe, PayPal, etc.
    // This is a simulation for demonstration
    
    // Rate limit check per card (basic)
    $card_last4 = substr($card_no, -4);
    if (isCardRateLimited($card_last4)) {
        return [
            'success' => false,
            'message' => 'Too many payment attempts with this card',
            'code' => 'rate_limited'
        ];
    }
    
    // Simulate payment processing with 95% success rate
    $success = (mt_rand(1, 100) <= 95);
    
    if ($success) {
        return [
            'success' => true,
            'message' => 'Payment processed successfully',
            'transaction_id' => 'TXN' . time() . mt_rand(1000, 9999),
            'auth_code' => 'AUTH' . mt_rand(100000, 999999)
        ];
    } else {
        // Simulate various failure reasons
        $failureReasons = [
            'insufficient_funds',
            'card_declined',
            'expired_card',
            'invalid_cvv',
            'processing_error'
        ];
        
        $reason = $failureReasons[array_rand($failureReasons)];
        
        return [
            'success' => false,
            'message' => 'Payment declined by bank',
            'code' => $reason
        ];
    }
}

/**
 * Generate unique payment reference
 */
function generatePaymentReference(): string {
    $prefix = 'PAY';
    $timestamp = time();
    $random = bin2hex(random_bytes(4));
    return $prefix . $timestamp . strtoupper($random);
}

/**
 * Process tickets for a flight
 */
function processFlightTickets(mysqli $conn, int $flight_id, array $passenger_ids, string $class, float $price, int $booking_id, bool $is_return = false): void {
    // Get flight details
    $flight = getFlightDetails($conn, $flight_id);
    if (!$flight) {
        throw new Exception('Flight not found');
    }
    
    // Assign seats
    $assigned_seats = assignSeats($conn, $flight_id, count($passenger_ids), $class);
    
    // Insert tickets for each passenger
    foreach ($passenger_ids as $index => $passenger_id) {
        $seat_no = $assigned_seats[$index] ?? assignSeatNumber($class, $index);
        $ticket_number = generateTicketNumber();
        
        $sql = 'INSERT INTO TICKETS 
                (booking_id, passenger_id, flight_id, ticket_number, seat_no, 
                 class, price, is_return, status, issued_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())';
        
        $stmt = mysqli_prepare($conn, $sql);
        $status = 'confirmed';
        $is_return_int = $is_return ? 1 : 0;
        
        mysqli_stmt_bind_param($stmt, 'iiisssdsi',
            $booking_id,
            $passenger_id,
            $flight_id,
            $ticket_number,
            $seat_no,
            $class,
            $price,
            $is_return_int,
            $status
        );
        
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception('Failed to create ticket');
        }
        mysqli_stmt_close($stmt);
    }
    
    // Update flight seat availability
    updateSeatAvailability($conn, $flight_id, $class, count($passenger_ids));
}

/**
 * Assign seats for passengers
 */
function assignSeats(mysqli $conn, int $flight_id, int $passenger_count, string $class): array {
    $assigned_seats = [];
    
    // Get current seat assignments for this flight and class
    $sql = 'SELECT seat_no FROM TICKETS 
            WHERE flight_id = ? AND class = ? 
            ORDER BY seat_no';
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'is', $flight_id, $class);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $occupied_seats = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $occupied_seats[] = $row['seat_no'];
    }
    mysqli_stmt_close($stmt);
    
    // Define seat map based on class
    $seat_config = [
        'economy' => ['rows' => 20, 'seats_per_row' => 6, 'start_row' => 21],
        'business' => ['rows' => 10, 'seats_per_row' => 6, 'start_row' => 11],
        'first' => ['rows' => 4, 'seats_per_row' => 4, 'start_row' => 1]
    ];
    
    $config = $seat_config[strtolower($class)] ?? $seat_config['economy'];
    $seat_letters = ['A', 'B', 'C', 'D', 'E', 'F'];
    
    // Assign seats (simple sequential assignment, could be enhanced for group seating)
    $assigned = 0;
    for ($row = $config['start_row']; $row < $config['start_row'] + $config['rows']; $row++) {
        foreach ($seat_letters as $letter) {
            $seat = $row . $letter;
            
            if (!in_array($seat, $occupied_seats, true)) {
                $assigned_seats[] = $seat;
                $assigned++;
                
                if ($assigned >= $passenger_count) {
                    return $assigned_seats;
                }
            }
        }
    }
    
    // If we run out of seats (shouldn't happen due to prior validation)
    throw new Exception('Not enough available seats');
}

/**
 * Generate ticket number
 */
function generateTicketNumber(): string {
    $airline_code = 'AIR';
    $random = bin2hex(random_bytes(6));
    return $airline_code . strtoupper($random);
}

/**
 * Find return flight
 */
function findReturnFlight(mysqli $conn, int $outbound_flight_id, string $ret_date): ?int {
    // Get outbound flight details
    $sql = 'SELECT source, destination FROM Flights WHERE flight_id = ?';
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $outbound_flight_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        $source = $row['destination']; // Return source is outbound destination
        $destination = $row['source']; // Return destination is outbound source
        
        // Find return flight on the specified date
        $sql = 'SELECT flight_id FROM Flights 
                WHERE source = ? AND destination = ? 
                AND DATE(departure) = ? 
                AND status = "scheduled" 
                LIMIT 1';
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, 'sss', $source, $destination, $ret_date);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($return_row = mysqli_fetch_assoc($result)) {
            return (int)$return_row['flight_id'];
        }
    }
    
    return null;
}

/**
 * Generate ticket details for confirmation
 */
function generateTickets(mysqli $conn, int $booking_id): array {
    $sql = 'SELECT t.ticket_number, t.seat_no, t.class, t.flight_id,
                   f.flight_number, f.source, f.destination, f.departure,
                   p.f_name, p.l_name, p.mobile, t.is_return
            FROM TICKETS t
            JOIN Flights f ON t.flight_id = f.flight_id
            JOIN Passenger_profile p ON t.passenger_id = p.passenger_id
            WHERE t.booking_id = ?
            ORDER BY t.is_return, t.flight_id, p.passenger_id';
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $booking_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $tickets = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $tickets[] = [
            'ticket_number' => $row['ticket_number'],
            'passenger_name' => $row['f_name'] . ' ' . $row['l_name'],
            'flight_number' => $row['flight_number'],
            'route' => $row['source'] . ' → ' . $row['destination'],
            'departure' => $row['departure'],
            'seat' => $row['seat_no'],
            'class' => $row['class'],
            'is_return' => (bool)$row['is_return']
        ];
    }
    mysqli_stmt_close($stmt);
    
    return $tickets;
}

/**
 * Send booking confirmation email
 */
function sendBookingConfirmation(string $email, int $booking_id, array $tickets, string $payment_ref): bool {
    // In production, use PHPMailer or similar library
    
    // Simulate email sending
    $subject = "Booking Confirmation #" . $booking_id;
    $message = "Thank you for your booking!\n\n";
    $message .= "Payment Reference: " . $payment_ref . "\n";
    $message .= "Booking ID: " . $booking_id . "\n\n";
    $message .= "Tickets:\n";
    
    foreach ($tickets as $ticket) {
        $message .= "- " . $ticket['passenger_name'] . ": " . $ticket['ticket_number'] . 
                    " (" . $ticket['flight_number'] . ") - " . 
                    $ticket['seat'] . " " . $ticket['class'] . "\n";
    }
    
    // Log email (in production, actually send it)
    $logFile = __DIR__ . '/../logs/email.log';
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] To: $email, Subject: $subject\n";
    
    file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
    
    return true;
}

/**
 * Log payment events
 */
function logPaymentEvent(int $payment_id, int $user_id, string $event, float $amount, string $details = ''): void {
    $logFile = __DIR__ . '/../logs/payments.log';
    $timestamp = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    
    $logMessage = sprintf(
        "[%s] PaymentID: %d, UserID: %d, Event: %s, Amount: %.2f, IP: %s, Details: %s\n",
        $timestamp,
        $payment_id,
        $user_id,
        $event,
        $amount,
        $ip,
        $details
    );
    
    if (is_writable(dirname($logFile))) {
        file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
    }
}

/**
 * Rate limiting for payment attempts
 */
function paymentRateLimitExceeded(int $user_id): bool {
    $maxAttempts = 5;
    $timeWindow = 300; // 5 minutes
    
    if (!isset($_SESSION['payment_attempts'])) {
        $_SESSION['payment_attempts'] = [];
    }
    
    $now = time();
    $_SESSION['payment_attempts'] = array_filter(
        $_SESSION['payment_attempts'],
        function ($timestamp) use ($now, $timeWindow) {
            return ($now - $timestamp) < $timeWindow;
        }
    );
    
    if (count($_SESSION['payment_attempts']) >= $maxAttempts) {
        return true;
    }
    
    $_SESSION['payment_attempts'][] = $now;
    return false;
}

/**
 * Check if card is rate limited
 */
function isCardRateLimited(string $card_last4): bool {
    $maxAttempts = 3;
    $timeWindow = 900; // 15 minutes
    
    $key = 'card_attempts_' . $card_last4;
    
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = ['count' => 0, 'first_attempt' => time()];
    }
    
    $attempts = $_SESSION[$key];
    
    if ($attempts['first_attempt'] + $timeWindow < time()) {
        // Reset if time window has passed
        $_SESSION[$key] = ['count' => 1, 'first_attempt' => time()];
        return false;
    }
    
    if ($attempts['count'] >= $maxAttempts) {
        return true;
    }
    
    $_SESSION[$key]['count']++;
    return false;
}

// Include helper functions for validation (should be in separate file)
require_once '../helpers/validation_functions.php';
