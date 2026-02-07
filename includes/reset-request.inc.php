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

// Check if reset request form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset-req-submit'])) {
    
    // CSRF Protection
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
        header('Location: ../reset-pwd.php?error=csrf');
        exit();
    }
    
    // Rate limiting for reset requests
    if (resetRequestRateLimitExceeded()) {
        header('Location: ../reset-pwd.php?error=ratelimit&retry=' . (time() + 900));
        exit();
    }
    
    require '../helpers/init_conn_db.php';
    
    // Initialize validation errors
    $errors = [];
    
    // Validate and sanitize email
    $user_email = trim($_POST['user_email'] ?? '');
    
    if (empty($user_email)) {
        $errors[] = 'Email is required';
    } elseif (!filter_var($user_email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email format';
    } else {
        $user_email = filter_var($user_email, FILTER_SANITIZE_EMAIL);
        
        // Check for disposable emails
        if (isDisposableEmail($user_email)) {
            $errors[] = 'Temporary email addresses are not allowed';
        }
        
        // Validate email domain
        if (!validateEmailDomain($user_email)) {
            $errors[] = 'Email domain appears to be invalid';
        }
    }
    
    // If there are validation errors, redirect back with errors
    if (!empty($errors)) {
        $_SESSION['reset_request_errors'] = $errors;
        $_SESSION['reset_request_email'] = htmlspecialchars($user_email);
        header('Location: ../reset-pwd.php?error=validation');
        exit();
    }
    
    // Generate secure reset tokens
    $selector = bin2hex(random_bytes(16)); // 32 characters
    $token = random_bytes(64); // 512-bit token
    $token_hash = hash('sha256', $token); // Store only the hash
    
    // Set expiration (30 minutes)
    $expires = time() + 1800;
    
    // Check if user exists (but don't reveal if they do or don't)
    $user_exists = checkUserExists($conn, $user_email);
    
    // Only proceed if user exists and is active
    if ($user_exists && $user_exists['is_active']) {
        
        // Start database transaction
        mysqli_begin_transaction($conn);
        
        try {
            // Delete any existing reset tokens for this email
            $sql = 'DELETE FROM Password_Resets WHERE pwd_reset_email = ?';
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, 's', $user_email);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            
            // Insert new reset token
            $sql = 'INSERT INTO Password_Resets 
                    (pwd_reset_email, pwd_reset_selector, pwd_reset_token, 
                     pwd_reset_expires, ip_address, user_agent, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, NOW())';
            
            $stmt = mysqli_prepare($conn, $sql);
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
            
            mysqli_stmt_bind_param($stmt, 'sssiss', 
                $user_email, 
                $selector, 
                $token_hash,
                $expires,
                $ip_address,
                $user_agent
            );
            
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception('Failed to store reset token');
            }
            
            $reset_id = mysqli_insert_id($conn);
            mysqli_stmt_close($stmt);
            
            // Generate reset URL
            $base_url = getBaseUrl();
            $validator = bin2hex($token);
            $reset_url = $base_url . '/views/create-new-pwd.php?selector=' . urlencode($selector) . 
                        '&validator=' . urlencode($validator);
            
            // Send reset email
            $email_sent = sendPasswordResetEmail($user_email, $user_exists['username'], $reset_url);
            
            if (!$email_sent) {
                throw new Exception('Failed to send reset email');
            }
            
            // Log the reset request
            logResetRequestEvent($user_email, 'reset_requested', $ip_address, $reset_id, $user_exists['user_id']);
            
            // Commit transaction
            mysqli_commit($conn);
            
        } catch (Exception $e) {
            // Rollback transaction on error
            mysqli_rollback($conn);
            
            // Log error
            error_log("Password Reset Request Error: " . $e->getMessage());
            
            // Log failed reset request
            logResetRequestEvent($user_email, 'reset_failed', $ip_address ?? '0.0.0.0', null, null, $e->getMessage());
            
            // Show generic error (don't reveal specific details)
            $_SESSION['reset_request_message'] = 'An error occurred. Please try again later.';
            header('Location: ../reset-pwd.php?error=processing');
            exit();
        }
    } else {
        // User doesn't exist or is inactive
        // Still show success message to prevent email enumeration
        logResetRequestEvent($user_email, 'reset_requested_nonexistent', $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
    }
    
    // Always show success message (security through obscurity)
    $_SESSION['reset_request_success'] = true;
    
    // Generate new CSRF token
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    
    // Clear any stored errors/email
    unset($_SESSION['reset_request_errors'], $_SESSION['reset_request_email']);
    
    // Redirect to success page
    header('Location: ../reset-pwd.php?mail=success');
    exit();
    
} else {
    // Invalid access
    header('Location: ../reset-pwd.php?error=invalid_access');
    exit();
}

/**
 * Rate limiting for password reset requests
 */
function resetRequestRateLimitExceeded(): bool {
    $maxRequestsPerHour = 5;
    $maxRequestsPerDay = 20;
    
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $currentHour = date('Y-m-d H');
    $currentDay = date('Y-m-d');
    
    // Initialize session tracking
    if (!isset($_SESSION['reset_requests'])) {
        $_SESSION['reset_requests'] = [
            'hourly' => [],
            'daily' => [],
            'last_request' => 0
        ];
    }
    
    $reset_requests = &$_SESSION['reset_requests'];
    
    // Clean up old entries
    $reset_requests['hourly'] = array_filter(
        $reset_requests['hourly'],
        function ($timestamp) use ($currentHour) {
            return date('Y-m-d H', $timestamp) === $currentHour;
        }
    );
    
    $reset_requests['daily'] = array_filter(
        $reset_requests['daily'],
        function ($timestamp) use ($currentDay) {
            return date('Y-m-d', $timestamp) === $currentDay;
        }
    );
    
    // Check hourly limit
    if (count($reset_requests['hourly']) >= $maxRequestsPerHour) {
        return true;
    }
    
    // Check daily limit
    if (count($reset_requests['daily']) >= $maxRequestsPerDay) {
        return true;
    }
    
    // Add current request
    $now = time();
    $reset_requests['hourly'][] = $now;
    $reset_requests['daily'][] = $now;
    $reset_requests['last_request'] = $now;
    
    // Limit array sizes to prevent memory issues
    if (count($reset_requests['hourly']) > 100) {
        array_shift($reset_requests['hourly']);
    }
    if (count($reset_requests['daily']) > 500) {
        array_shift($reset_requests['daily']);
    }
    
    return false;
}

/**
 * Check if email is from a disposable email service
 */
function isDisposableEmail(string $email): bool {
    $disposableDomains = [
        'tempmail.com', 'mailinator.com', 'guerrillamail.com', 
        '10minutemail.com', 'throwawaymail.com', 'yopmail.com',
        'temp-mail.org', 'fakeinbox.com', 'trashmail.com',
        'sharklasers.com', 'guerrillamail.biz', 'guerrillamail.org',
        'guerrillamail.net', 'grr.la', 'spam4.me'
    ];
    
    $domain = strtolower(substr(strrchr($email, "@"), 1));
    
    // Check against known disposable domains
    if (in_array($domain, $disposableDomains, true)) {
        return true;
    }
    
    // Check subdomains of disposable services
    foreach ($disposableDomains as $disposable) {
        if (str_ends_with($domain, '.' . $disposable)) {
            return true;
        }
    }
    
    // Optional: Check against API or database of disposable domains
    // return checkDisposableEmailAPI($email);
    
    return false;
}

/**
 * Validate email domain exists
 */
function validateEmailDomain(string $email): bool {
    $domain = substr(strrchr($email, "@"), 1);
    
    // Skip validation for test domains
    if (in_array($domain, ['test.com', 'example.com', 'localhost'])) {
        return true;
    }
    
    // Check if domain has valid MX or A records
    return checkdnsrr($domain, 'MX') || checkdnsrr($domain, 'A');
}

/**
 * Check if user exists and is active
 */
function checkUserExists(mysqli $conn, string $email): ?array {
    $sql = 'SELECT user_id, username, email, is_active FROM Users WHERE email = ? LIMIT 1';
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 's', $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        mysqli_stmt_close($stmt);
        return $row;
    }
    
    mysqli_stmt_close($stmt);
    return null;
}

/**
 * Get base URL dynamically
 */
function getBaseUrl(): string {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    
    // Remove port if present (for cleaner URLs)
    $host = strtok($host, ':');
    
    return $protocol . $host;
}

/**
 * Send password reset email securely
 */
function sendPasswordResetEmail(string $email, string $username, string $reset_url): bool {
    try {
        // Load PHPMailer
        require_once '../vendor/autoload.php';
        
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        
        // Server settings
        $mail->isSMTP();
        $mail->Host = getenv('SMTP_HOST') ?: 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = getenv('SMTP_USERNAME') ?: 'your_username';
        $mail->Password = getenv('SMTP_PASSWORD') ?: 'your_password';
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = getenv('SMTP_PORT') ?: 587;
        
        // Additional security settings
        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
                'allow_self_signed' => false,
            ]
        ];
        
        // Sender info
        $mail->setFrom(getenv('MAIL_FROM_ADDRESS') ?: 'noreply@example.com', 
                      getenv('MAIL_FROM_NAME') ?: 'Security Team');
        $mail->addReplyTo(getenv('MAIL_REPLY_TO') ?: 'support@example.com', 'Support');
        
        // Recipient
        $mail->addAddress($email, $username);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Password Reset Request';
        
        // Generate HTML email with security information
        $mail->Body = generateResetEmailHtml($username, $reset_url);
        $mail->AltBody = generateResetEmailText($username, $reset_url);
        
        // Additional headers for security
        $mail->addCustomHeader('X-Priority', '1 (High)');
        $mail->addCustomHeader('X-MSMail-Priority', 'High');
        $mail->addCustomHeader('Importance', 'High');
        $mail->addCustomHeader('X-Mailer', 'PHP/' . phpversion());
        
        // Send email
        $mail->send();
        
        // Log successful email send
        logEmailEvent($email, 'password_reset_request', true);
        
        return true;
        
    } catch (Exception $e) {
        // Log email failure
        error_log("Password reset email error: " . $e->getMessage());
        logEmailEvent($email, 'password_reset_request', false, $e->getMessage());
        
        return false;
    }
}

/**
 * Generate HTML email for password reset
 */
function generateResetEmailHtml(string $username, string $reset_url): string {
    $expiry_hours = 0.5; // 30 minutes
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    $timestamp = date('Y-m-d H:i:s T');
    
    $html = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset Request</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #f8f9fa; padding: 20px; text-align: center; }
        .content { padding: 30px; background: #fff; border: 1px solid #dee2e6; }
        .button { 
            display: inline-block; 
            padding: 12px 24px; 
            background: #007bff; 
            color: white; 
            text-decoration: none; 
            border-radius: 4px; 
            font-weight: bold; 
            margin: 20px 0; 
        }
        .button:hover { background: #0056b3; }
        .footer { 
            margin-top: 30px; 
            padding-top: 20px; 
            border-top: 1px solid #dee2e6; 
            font-size: 12px; 
            color: #6c757d; 
        }
        .security-note { 
            background: #fff3cd; 
            border: 1px solid #ffc107; 
            padding: 15px; 
            border-radius: 4px; 
            margin: 20px 0; 
        }
        .warning { color: #856404; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Password Reset Request</h2>
        </div>
        <div class="content">
            <p>Hello <strong>{$username}</strong>,</p>
            
            <p>We received a request to reset your password. If you made this request, 
            please click the button below to create a new password:</p>
            
            <p style="text-align: center;">
                <a href="{$reset_url}" class="button">Reset Password</a>
            </p>
            
            <p>Or copy and paste this link into your browser:</p>
            <p style="word-break: break-all; background: #f8f9fa; padding: 10px; border-radius: 4px;">
                {$reset_url}
            </p>
            
            <div class="security-note">
                <p class="warning"><strong>⚠️ Security Information:</strong></p>
                <ul>
                    <li>This link will expire in <strong>{$expiry_hours} hours</strong></li>
                    <li>Request IP: {$ip_address}</li>
                    <li>Request Time: {$timestamp}</li>
                    <li>If you didn't request this, please ignore this email</li>
                    <li>Your password will not change until you click the link above</li>
                </ul>
            </div>
            
            <p>For security reasons:</p>
            <ul>
                <li>Never share your reset link with anyone</li>
                <li>Use a strong, unique password</li>
                <li>Enable two-factor authentication if available</li>
                <li>Update your password regularly</li>
            </ul>
            
            <p>If you have any questions, contact our support team.</p>
            
            <p>Best regards,<br>The Security Team</p>
        </div>
        
        <div class="footer">
            <p>This is an automated message. Please do not reply to this email.</p>
            <p>For security reasons, we recommend that you:</p>
            <ul>
                <li>Delete this email after resetting your password</li>
                <li>Clear your browser history after completing the reset</li>
                <li>Use a password manager to generate strong passwords</li>
            </ul>
            <p>© " . date('Y') . " Your Company. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
HTML;
    
    return $html;
}

/**
 * Generate plain text email for password reset
 */
function generateResetEmailText(string $username, string $reset_url): string {
    $expiry_hours = 0.5; // 30 minutes
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    $timestamp = date('Y-m-d H:i:s T');
    
    $text = "PASSWORD RESET REQUEST\n";
    $text .= "=====================\n\n";
    $text .= "Hello {$username},\n\n";
    $text .= "We received a request to reset your password. If you made this request, ";
    $text .= "please use the link below to create a new password:\n\n";
    $text .= "Reset Link: {$reset_url}\n\n";
    $text .= "SECURITY INFORMATION:\n";
    $text .= "- This link expires in {$expiry_hours} hours\n";
    $text .= "- Request IP: {$ip_address}\n";
    $text .= "- Request Time: {$timestamp}\n";
    $text .= "- If you didn't request this, please ignore this email\n\n";
    $text .= "For security:\n";
    $text .= "- Never share your reset link\n";
    $text .= "- Use a strong, unique password\n";
    $text .= "- Enable two-factor authentication\n";
    $text .= "- Update your password regularly\n\n";
    $text .= "Best regards,\n";
    $text .= "The Security Team\n\n";
    $text .= "---\n";
    $text .= "This is an automated message. Please do not reply.\n";
    $text .= "© " . date('Y') . " Your Company. All rights reserved.";
    
    return $text;
}

/**
 * Log reset request events
 */
function logResetRequestEvent(string $email, string $event, string $ip, ?int $reset_id = null, ?int $user_id = null, string $details = ''): void {
    $logFile = __DIR__ . '/../logs/password_reset_requests.log';
    $timestamp = date('Y-m-d H:i:s');
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    
    // Mask email for privacy
    $masked_email = maskEmail($email);
    
    $logMessage = sprintf(
        "[%s] Event: %s, UserID: %s, ResetID: %s, Email: %s, IP: %s, Details: %s\n",
        $timestamp,
        $event,
        $user_id ?? 'N/A',
        $reset_id ?? 'N/A',
        $masked_email,
        $ip,
        $details
    );
    
    if (is_writable(dirname($logFile))) {
        file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
    }
}

/**
 * Log email events
 */
function logEmailEvent(string $email, string $type, bool $success, string $details = ''): void {
    $logFile = __DIR__ . '/../logs/email_delivery.log';
    $timestamp = date('Y-m-d H:i:s');
    $status = $success ? 'SUCCESS' : 'FAILED';
    
    $logMessage = sprintf(
        "[%s] Type: %s, Status: %s, To: %s, Details: %s\n",
        $timestamp,
        $type,
        $status,
        maskEmail($email),
        $details
    );
    
    if (is_writable(dirname($logFile))) {
        file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
    }
}

/**
 * Mask email address for logging
 */
function maskEmail(string $email): string {
    $parts = explode('@', $email);
    if (count($parts) !== 2) {
        return 'invalid-email';
    }
    
    $local = $parts[0];
    $domain = $parts[1];
    
    if (strlen($local) <= 2) {
        $masked_local = str_repeat('*', strlen($local));
    } else {
        $masked_local = $local[0] . str_repeat('*', max(3, strlen($local) - 2)) . substr($local, -1);
    }
    
    return $masked_local . '@' . $domain;
}
