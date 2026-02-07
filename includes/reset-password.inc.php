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

// Only process if form submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new-pwd-submit'])) {
    
    // CSRF Protection
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
        header('Location: ../create-new-pwd.php?error=csrf');
        exit();
    }
    
    // Rate limiting for password reset attempts
    if (passwordResetRateLimitExceeded($_SERVER['REMOTE_ADDR'])) {
        header('Location: ../create-new-pwd.php?error=ratelimit&retry=' . (time() + 300));
        exit();
    }
    
    require '../helpers/init_conn_db.php';
    
    // Initialize validation errors
    $errors = [];
    
    // Validate and sanitize inputs
    $selector = filter_input(INPUT_POST, 'selector', FILTER_SANITIZE_STRING);
    $validator = filter_input(INPUT_POST, 'validator', FILTER_SANITIZE_STRING);
    $password = $_POST['password'] ?? '';
    $password_repeat = $_POST['password_repeat'] ?? '';
    
    // Validate selector and validator format
    if (!preg_match('/^[a-f0-9]{32}$/', $selector)) {
        $errors[] = 'Invalid reset token format';
    }
    
    if (!preg_match('/^[a-f0-9]{64}$/', $validator)) {
        $errors[] = 'Invalid validator format';
    }
    
    // Validate passwords
    if (empty($password)) {
        $errors[] = 'Password is required';
    } else {
        $password_errors = validatePasswordStrength($password);
        if (!empty($password_errors)) {
            $errors = array_merge($errors, $password_errors);
        }
    }
    
    if ($password !== $password_repeat) {
        $errors[] = 'Passwords do not match';
    }
    
    // If there are validation errors, redirect back with errors
    if (!empty($errors)) {
        $_SESSION['reset_errors'] = $errors;
        header('Location: ../create-new-pwd.php?selector=' . urlencode($selector) . 
               '&validator=' . urlencode($validator) . '&error=validation');
        exit();
    }
    
    // Start database transaction
    mysqli_begin_transaction($conn);
    
    try {
        // Get current timestamp
        $curr_date = date('U');
        
        // Check for valid reset token
        $sql = 'SELECT pwd_reset_id, pwd_reset_email, pwd_reset_token, pwd_reset_attempts 
                FROM PwdReset 
                WHERE pwd_reset_selector = ? 
                AND pwd_reset_expires >= ? 
                AND pwd_reset_used = 0';
        
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, 'si', $selector, $curr_date);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if (!$row = mysqli_fetch_assoc($result)) {
            // Log failed reset attempt
            logPasswordResetEvent(null, 'invalid_or_expired_token', $_SERVER['REMOTE_ADDR']);
            
            // Don't reveal if token exists or not
            $_SESSION['reset_message'] = 'Password reset link is invalid or has expired.';
            header('Location: ../login.php?reset=invalid');
            exit();
        }
        
        mysqli_stmt_close($stmt);
        
        // Check if token has been attempted too many times
        if ($row['pwd_reset_attempts'] >= 5) {
            // Mark token as used to prevent further attempts
            $sql = 'UPDATE PwdReset SET pwd_reset_used = 1 WHERE pwd_reset_id = ?';
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, 'i', $row['pwd_reset_id']);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            
            logPasswordResetEvent($row['pwd_reset_email'], 'too_many_attempts', $_SERVER['REMOTE_ADDR']);
            
            $_SESSION['reset_message'] = 'Too many attempts with this reset link. Please request a new one.';
            header('Location: ../login.php?reset=attempts_exceeded');
            exit();
        }
        
        // Verify the validator token securely using hash_equals (timing attack safe)
        $token_bin = hex2bin($validator);
        $stored_token_hash = $row['pwd_reset_token'];
        
        // Use hash_equals for timing attack protection
        if (!hash_equals(hash('sha256', $token_bin), $stored_token_hash)) {
            // Increment attempt counter
            $sql = 'UPDATE PwdReset SET pwd_reset_attempts = pwd_reset_attempts + 1 
                    WHERE pwd_reset_id = ?';
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, 'i', $row['pwd_reset_id']);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            
            logPasswordResetEvent($row['pwd_reset_email'], 'invalid_token', $_SERVER['REMOTE_ADDR']);
            
            // Don't reveal whether token was valid or not
            $_SESSION['reset_message'] = 'Password reset link is invalid or has expired.';
            header('Location: ../login.php?reset=invalid');
            exit();
        }
        
        $token_email = $row['pwd_reset_email'];
        
        // Check if user exists and is active
        $sql = 'SELECT user_id, username, is_active FROM Users WHERE email = ?';
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, 's', $token_email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if (!$user_row = mysqli_fetch_assoc($result)) {
            throw new Exception('User not found');
        }
        
        if (!$user_row['is_active']) {
            throw new Exception('User account is not active');
        }
        
        mysqli_stmt_close($stmt);
        
        // Check if new password is different from last N passwords
        if (!isPasswordNew($conn, $user_row['user_id'], $password)) {
            $_SESSION['reset_errors'] = ['You cannot reuse a recently used password.'];
            header('Location: ../create-new-pwd.php?selector=' . urlencode($selector) . 
                   '&validator=' . urlencode($validator) . '&error=password_reused');
            exit();
        }
        
        // Generate secure password hash using Argon2id
        $pwd_hash = password_hash($password, PASSWORD_ARGON2ID, [
            'memory_cost' => 65536,
            'time_cost' => 4,
            'threads' => 2
        ]);
        
        if (!$pwd_hash) {
            throw new Exception('Password hashing failed');
        }
        
        // Update user password
        $sql = 'UPDATE Users SET password = ?, password_changed_at = NOW() WHERE email = ?';
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, 'ss', $pwd_hash, $token_email);
        
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception('Failed to update password');
        }
        mysqli_stmt_close($stmt);
        
        // Store password in history
        storePasswordHistory($conn, $user_row['user_id'], $pwd_hash);
        
        // Mark reset token as used
        $sql = 'UPDATE PwdReset SET pwd_reset_used = 1, pwd_reset_used_at = NOW() 
                WHERE pwd_reset_id = ?';
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, 'i', $row['pwd_reset_id']);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        
        // Invalidate all other active sessions for this user (force logout everywhere)
        invalidateUserSessions($conn, $user_row['user_id']);
        
        // Log the successful password reset
        logPasswordResetEvent($token_email, 'password_reset_success', $_SERVER['REMOTE_ADDR'], $user_row['user_id']);
        
        // Send confirmation email
        sendPasswordChangeConfirmation($token_email, $user_row['username']);
        
        // Commit transaction
        mysqli_commit($conn);
        
        // Clear reset errors from session
        unset($_SESSION['reset_errors']);
        
        // Generate new CSRF token
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        
        // Set success message
        $_SESSION['reset_success'] = true;
        $_SESSION['reset_email'] = $token_email;
        
        // Redirect to login with success message
        header('Location: ../login.php?reset=success');
        exit();
        
    } catch (Exception $e) {
        // Rollback transaction on error
        mysqli_rollback($conn);
        
        // Log error
        error_log("Password Reset Error: " . $e->getMessage());
        
        // Store errors in session
        $_SESSION['reset_errors'] = ['An error occurred while resetting your password. Please try again.'];
        
        header('Location: ../create-new-pwd.php?selector=' . urlencode($selector) . 
               '&validator=' . urlencode($validator) . '&error=processing');
        exit();
    }
    
    mysqli_close($conn);
    
} else {
    // Invalid access
    header('Location: ../create-new-pwd.php?error=invalid_access');
    exit();
}

/**
 * Rate limiting for password reset attempts
 */
function passwordResetRateLimitExceeded(string $ip): bool {
    $maxAttempts = 10;
    $timeWindow = 900; // 15 minutes
    
    if (!isset($_SESSION['password_reset_attempts'])) {
        $_SESSION['password_reset_attempts'] = [];
    }
    
    $now = time();
    $_SESSION['password_reset_attempts'] = array_filter(
        $_SESSION['password_reset_attempts'],
        function ($timestamp) use ($now, $timeWindow) {
            return ($now - $timestamp) < $timeWindow;
        }
    );
    
    if (count($_SESSION['password_reset_attempts']) >= $maxAttempts) {
        return true;
    }
    
    $_SESSION['password_reset_attempts'][] = $now;
    return false;
}

/**
 * Validate password strength
 */
function validatePasswordStrength(string $password): array {
    $errors = [];
    
    // Minimum length
    if (strlen($password) < 12) {
        $errors[] = 'Password must be at least 12 characters long';
    }
    
    // Check for common passwords
    $commonPasswords = ['password', '123456', 'qwerty', 'letmein', 'welcome'];
    if (in_array(strtolower($password), $commonPasswords, true)) {
        $errors[] = 'Password is too common and easily guessable';
    }
    
    // Character diversity
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = 'Password must contain at least one uppercase letter';
    }
    
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = 'Password must contain at least one lowercase letter';
    }
    
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = 'Password must contain at least one number';
    }
    
    if (!preg_match('/[^A-Za-z0-9]/', $password)) {
        $errors[] = 'Password must contain at least one special character';
    }
    
    // Check for sequential patterns
    if (preg_match('/(abc|bcd|cde|def|efg|fgh|ghi|hij|ijk|jkl|klm|lmn|mno|nop|opq|pqr|qrs|rst|stu|tuv|uvw|vwx|wxy|xyz)/i', $password)) {
        $errors[] = 'Password should not contain sequential letters';
    }
    
    if (preg_match('/(012|123|234|345|456|567|678|789|890)/', $password)) {
        $errors[] = 'Password should not contain sequential numbers';
    }
    
    // Check for repeated characters
    if (preg_match('/(.)\1{2,}/', $password)) {
        $errors[] = 'Password should not contain repeated characters';
    }
    
    return $errors;
}

/**
 * Check if password is different from last N passwords
 */
function isPasswordNew(mysqli $conn, int $user_id, string $new_password): bool {
    $historyLimit = 5;
    
    $sql = 'SELECT password_hash FROM Password_History 
            WHERE user_id = ? 
            ORDER BY created_at DESC 
            LIMIT ?';
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'ii', $user_id, $historyLimit);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    while ($row = mysqli_fetch_assoc($result)) {
        if (password_verify($new_password, $row['password_hash'])) {
            mysqli_stmt_close($stmt);
            return false;
        }
    }
    
    mysqli_stmt_close($stmt);
    return true;
}

/**
 * Store password in history
 */
function storePasswordHistory(mysqli $conn, int $user_id, string $password_hash): void {
    $sql = 'INSERT INTO Password_History (user_id, password_hash, created_at) 
            VALUES (?, ?, NOW())';
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'is', $user_id, $password_hash);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

/**
 * Invalidate all user sessions (force logout)
 */
function invalidateUserSessions(mysqli $conn, int $user_id): void {
    // Generate new session invalidation token
    $new_token = bin2hex(random_bytes(32));
    
    $sql = 'UPDATE Users SET session_invalidation_token = ? WHERE user_id = ?';
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'si', $new_token, $user_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    
    // In API systems, you might also want to revoke all refresh tokens
    // This depends on your session/token implementation
}

/**
 * Log password reset events
 */
function logPasswordResetEvent(?string $email, string $event, string $ip, ?int $user_id = null): void {
    $logFile = __DIR__ . '/../logs/password_resets.log';
    $timestamp = date('Y-m-d H:i:s');
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    
    // Mask email for logging
    $masked_email = $email ? maskEmail($email) : 'N/A';
    
    $logMessage = sprintf(
        "[%s] Event: %s, UserID: %s, Email: %s, IP: %s, UserAgent: %s\n",
        $timestamp,
        $event,
        $user_id ?? 'N/A',
        $masked_email,
        $ip,
        substr($user_agent, 0, 100)
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
    
    // Show first and last character of local part
    if (strlen($local) <= 2) {
        $masked_local = str_repeat('*', strlen($local));
    } else {
        $masked_local = $local[0] . str_repeat('*', strlen($local) - 2) . substr($local, -1);
    }
    
    // Show domain
    $domain_parts = explode('.', $domain);
    if (count($domain_parts) >= 2) {
        $masked_domain = $domain_parts[0][0] . '***.' . end($domain_parts);
    } else {
        $masked_domain = '***';
    }
    
    return $masked_local . '@' . $masked_domain;
}

/**
 * Send password change confirmation email
 */
function sendPasswordChangeConfirmation(string $email, string $username): bool {
    $subject = "Password Changed Successfully";
    
    $message = "Hello " . htmlspecialchars($username) . ",\n\n";
    $message .= "Your password has been successfully changed.\n\n";
    $message .= "Date: " . date('Y-m-d H:i:s') . "\n";
    $message .= "IP Address: " . ($_SERVER['REMOTE_ADDR'] ?? 'Unknown') . "\n\n";
    
    $message .= "If you did not make this change, please contact our support immediately.\n\n";
    $message .= "For security reasons, we recommend that you:\n";
    $message .= "1. Use a strong, unique password\n";
    $message .= "2. Enable two-factor authentication if available\n";
    $message .= "3. Review your account activity regularly\n\n";
    
    $message .= "Best regards,\nThe Security Team";
    
    // Log email (in production, use PHPMailer or similar)
    $logFile = __DIR__ . '/../logs/password_change_emails.log';
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] To: " . maskEmail($email) . ", Subject: $subject\n";
    
    file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
    
    return true;
}
