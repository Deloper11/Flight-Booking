<?php
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '0'); // Log errors, don't display

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
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pass_but']) && isset($_SESSION['userId'])) {
    
    // CSRF Protection
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
        header('Location: ../pass_form.php?error=csrf');
        exit();
    }
    
    // Include database connection
    require '../helpers/init_conn_db.php';
    
    // Initialize validation errors array
    $errors = [];
    
    // Validate and sanitize flight_id
    $flight_id = filter_input(INPUT_POST, 'flight_id', FILTER_VALIDATE_INT);
    if (!$flight_id || $flight_id < 1) {
        $errors[] = 'Invalid flight selection';
    }
    
    // Validate number of passengers
    $passengers = filter_input(INPUT_POST, 'passengers', FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1, 'max_range' => 10] // Reasonable limit
    ]);
    if (!$passengers) {
        $errors[] = 'Invalid number of passengers';
    }
    
    // Validate class
    $class = filter_input(INPUT_POST, 'class', FILTER_SANITIZE_STRING);
    $allowedClasses = ['economy', 'business', 'first'];
    if (!in_array(strtolower($class), $allowedClasses, true)) {
        $errors[] = 'Invalid travel class';
    }
    
    // Validate price
    $price = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT);
    if (!$price || $price < 0) {
        $errors[] = 'Invalid price';
    }
    
    // Validate type (one-way/round-trip)
    $type = filter_input(INPUT_POST, 'type', FILTER_SANITIZE_STRING);
    if (!in_array($type, ['one-way', 'round-trip'], true)) {
        $errors[] = 'Invalid trip type';
    }
    
    // Validate return date if round trip
    $ret_date = null;
    if ($type === 'round-trip') {
        $ret_date = filter_input(INPUT_POST, 'ret_date', FILTER_SANITIZE_STRING);
        if (!$ret_date || !validateFutureDate($ret_date)) {
            $errors[] = 'Invalid return date';
        }
    }
    
    // Initialize arrays for passenger data
    $passengerData = [];
    
    // Validate each passenger
    for ($i = 0; $i < $passengers; $i++) {
        $passenger = [];
        
        // Validate mobile number
        $mobile = filter_input(INPUT_POST, 'mobile', FILTER_SANITIZE_STRING, [
            'flags' => FILTER_REQUIRE_ARRAY,
            'options' => ['default' => '']
        ])[$i] ?? '';
        
        if (!preg_match('/^[0-9]{10}$/', $mobile)) {
            $errors[] = "Invalid mobile number for passenger " . ($i + 1);
        }
        $passenger['mobile'] = $mobile;
        
        // Validate date of birth
        $dob = filter_input(INPUT_POST, 'date', FILTER_SANITIZE_STRING, [
            'flags' => FILTER_REQUIRE_ARRAY,
            'options' => ['default' => '']
        ])[$i] ?? '';
        
        if (!validateDate($dob) || !validatePastDate($dob)) {
            $errors[] = "Invalid date of birth for passenger " . ($i + 1);
        }
        $passenger['dob'] = $dob;
        
        // Validate names
        $firstname = filter_input(INPUT_POST, 'firstname', FILTER_SANITIZE_STRING, [
            'flags' => FILTER_REQUIRE_ARRAY,
            'options' => [
                'default' => '',
                'FILTER_FLAG_STRIP_LOW' => true,
                'FILTER_FLAG_STRIP_HIGH' => true
            ]
        ])[$i] ?? '';
        
        $midname = filter_input(INPUT_POST, 'midname', FILTER_SANITIZE_STRING, [
            'flags' => FILTER_REQUIRE_ARRAY,
            'options' => [
                'default' => '',
                'FILTER_FLAG_STRIP_LOW' => true,
                'FILTER_FLAG_STRIP_HIGH' => true
            ]
        ])[$i] ?? '';
        
        $lastname = filter_input(INPUT_POST, 'lastname', FILTER_SANITIZE_STRING, [
            'flags' => FILTER_REQUIRE_ARRAY,
            'options' => [
                'default' => '',
                'FILTER_FLAG_STRIP_LOW' => true,
                'FILTER_FLAG_STRIP_HIGH' => true
            ]
        ])[$i] ?? '';
        
        // Validate name length and content
        if (!validateName($firstname) || !validateName($lastname)) {
            $errors[] = "Invalid name format for passenger " . ($i + 1);
        }
        
        $passenger['firstname'] = $firstname;
        $passenger['midname'] = $midname;
        $passenger['lastname'] = $lastname;
        
        $passengerData[] = $passenger;
    }
    
    // Check if flight exists and has available seats
    if (empty($errors)) {
        if (!validateFlightAvailability($conn, $flight_id, $passengers, $class)) {
            $errors[] = 'Selected flight does not have enough available seats';
        }
    }
    
    // If there are validation errors, redirect back with errors
    if (!empty($errors)) {
        $_SESSION['form_errors'] = $errors;
        $_SESSION['form_data'] = $_POST;
        header('Location: ../pass_form.php?error=validation');
        exit();
    }
    
    // Start database transaction
    mysqli_begin_transaction($conn);
    
    try {
        // Insert passenger profiles
        $insertedPassengerIds = [];
        
        foreach ($passengerData as $passenger) {
            // Check if passenger profile already exists for this user
            $existingProfileId = checkExistingProfile(
                $conn,
                $_SESSION['userId'],
                $passenger['firstname'],
                $passenger['lastname'],
                $passenger['dob']
            );
            
            if ($existingProfileId) {
                // Update existing profile
                $sql = 'UPDATE Passenger_profile 
                        SET mobile = ?, flight_id = ?, updated_at = NOW()
                        WHERE passenger_id = ? AND user_id = ?';
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, 'siii',
                    $passenger['mobile'],
                    $flight_id,
                    $existingProfileId,
                    $_SESSION['userId']
                );
                mysqli_stmt_execute($stmt);
                $insertedPassengerIds[] = $existingProfileId;
                mysqli_stmt_close($stmt);
            } else {
                // Insert new profile
                $sql = 'INSERT INTO Passenger_profile 
                        (user_id, mobile, dob, f_name, m_name, l_name, flight_id, created_at) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())';
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, 'isssssi',
                    $_SESSION['userId'],
                    $passenger['mobile'],
                    $passenger['dob'],
                    $passenger['firstname'],
                    $passenger['midname'],
                    $passenger['lastname'],
                    $flight_id
                );
                mysqli_stmt_execute($stmt);
                $insertedPassengerIds[] = mysqli_insert_id($conn);
                mysqli_stmt_close($stmt);
            }
        }
        
        // Create booking record
        $bookingReference = generateBookingReference();
        $totalPrice = calculateTotalPrice($price, $passengers, $class);
        
        $sql = 'INSERT INTO Bookings 
                (user_id, flight_id, booking_reference, passengers, class, 
                 total_price, trip_type, return_date, status, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())';
        $stmt = mysqli_prepare($conn, $sql);
        $status = 'pending_payment';
        mysqli_stmt_bind_param($stmt, 'iisisssss',
            $_SESSION['userId'],
            $flight_id,
            $bookingReference,
            $passengers,
            $class,
            $totalPrice,
            $type,
            $ret_date,
            $status
        );
        mysqli_stmt_execute($stmt);
        $bookingId = mysqli_insert_id($conn);
        mysqli_stmt_close($stmt);
        
        // Link passengers to booking
        foreach ($insertedPassengerIds as $passengerId) {
            $sql = 'INSERT INTO Booking_Passengers 
                    (booking_id, passenger_id) 
                    VALUES (?, ?)';
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, 'ii', $bookingId, $passengerId);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
        
        // Reserve seats on flight
        if (!reserveSeats($conn, $flight_id, $passengers, $class)) {
            throw new Exception('Failed to reserve seats');
        }
        
        // Commit transaction
        mysqli_commit($conn);
        
        // Log successful passenger details submission
        logBookingEvent($bookingId, $_SESSION['userId'], 'passenger_details_submitted');
        
        // Set session variables for payment
        $_SESSION['booking_id'] = $bookingId;
        $_SESSION['booking_reference'] = $bookingReference;
        $_SESSION['flight_id'] = $flight_id;
        $_SESSION['class'] = $class;
        $_SESSION['passengers'] = $passengers;
        $_SESSION['price'] = $totalPrice;
        $_SESSION['type'] = $type;
        $_SESSION['ret_date'] = $ret_date;
        $_SESSION['passenger_ids'] = $insertedPassengerIds;
        
        // Generate new CSRF token
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        
        // Clear form data from session
        unset($_SESSION['form_data']);
        
        // Redirect to payment page
        header('Location: ../payment.php?booking=' . urlencode($bookingReference));
        exit();
        
    } catch (Exception $e) {
        // Rollback transaction on error
        mysqli_rollback($conn);
        
        // Log error
        error_log("Booking Error: " . $e->getMessage());
        
        // Store errors in session
        $_SESSION['form_errors'] = ['An error occurred while processing your booking. Please try again.'];
        $_SESSION['form_data'] = $_POST;
        
        header('Location: ../pass_form.php?error=processing');
        exit();
    }
    
    mysqli_close($conn);
    
} else {
    // Invalid access
    header('Location: ../pass_form.php?error=invalid_access');
    exit();
}

/**
 * Validate date format
 */
function validateDate(string $date, string $format = 'Y-m-d'): bool {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

/**
 * Validate that date is in the past
 */
function validatePastDate(string $date): bool {
    $dateTime = new DateTime($date);
    $now = new DateTime();
    return $dateTime < $now;
}

/**
 * Validate that date is in the future
 */
function validateFutureDate(string $date): bool {
    $dateTime = new DateTime($date);
    $now = new DateTime();
    return $dateTime > $now;
}

/**
 * Validate name (letters, spaces, hyphens, apostrophes)
 */
function validateName(string $name): bool {
    if (empty($name)) {
        return false;
    }
    return preg_match('/^[\p{L}\s\'\-]{2,50}$/u', $name) === 1;
}

/**
 * Check flight availability
 */
function validateFlightAvailability(mysqli $conn, int $flightId, int $passengers, string $class): bool {
    // Get available seats for the flight and class
    $sql = 'SELECT available_seats FROM Flight_Seats 
            WHERE flight_id = ? AND class = ?';
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'is', $flightId, $class);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        $availableSeats = (int)$row['available_seats'];
        mysqli_stmt_close($stmt);
        return $availableSeats >= $passengers;
    }
    
    mysqli_stmt_close($stmt);
    return false;
}

/**
 * Check if passenger profile already exists
 */
function checkExistingProfile(mysqli $conn, int $userId, string $firstName, string $lastName, string $dob): ?int {
    $sql = 'SELECT passenger_id FROM Passenger_profile 
            WHERE user_id = ? AND f_name = ? AND l_name = ? AND dob = ? 
            LIMIT 1';
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'isss', $userId, $firstName, $lastName, $dob);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        $id = (int)$row['passenger_id'];
        mysqli_stmt_close($stmt);
        return $id;
    }
    
    mysqli_stmt_close($stmt);
    return null;
}

/**
 * Generate unique booking reference
 */
function generateBookingReference(): string {
    $prefix = 'BK';
    $timestamp = time();
    $random = bin2hex(random_bytes(3));
    return $prefix . $timestamp . strtoupper($random);
}

/**
 * Calculate total price with class multiplier
 */
function calculateTotalPrice(float $basePrice, int $passengers, string $class): float {
    $multipliers = [
        'economy' => 1.0,
        'business' => 2.5,
        'first' => 4.0
    ];
    
    $multiplier = $multipliers[strtolower($class)] ?? 1.0;
    return round($basePrice * $passengers * $multiplier, 2);
}

/**
 * Reserve seats on flight
 */
function reserveSeats(mysqli $conn, int $flightId, int $passengers, string $class): bool {
    $sql = 'UPDATE Flight_Seats 
            SET available_seats = available_seats - ?, 
                reserved_seats = reserved_seats + ?,
                updated_at = NOW()
            WHERE flight_id = ? AND class = ? AND available_seats >= ?';
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'iiisi', $passengers, $passengers, $flightId, $class, $passengers);
    $result = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    return $result;
}

/**
 * Log booking events for audit trail
 */
function logBookingEvent(int $bookingId, int $userId, string $event): void {
    $logFile = __DIR__ . '/../logs/booking.log';
    $timestamp = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    
    $logMessage = sprintf(
        "[%s] BookingID: %d, UserID: %d, Event: %s, IP: %s\n",
        $timestamp,
        $bookingId,
        $userId,
        $event,
        $ip
    );
    
    if (is_writable(dirname($logFile))) {
        file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
    }
}
