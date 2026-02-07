<?php
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '0'); // Log errors instead of displaying

// Start session at the very beginning
session_start();

// Check if form was submitted
if (isset($_POST['login_but'])) {
    
    // CSRF Protection
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        header('Location: ../login.php?error=csrf');
        exit();
    }
    
    // Rate limiting check
    if (rateLimitExceeded($_SERVER['REMOTE_ADDR'])) {
        header('Location: ../login.php?error=ratelimit&retry=' . (time() + 300));
        exit();
    }
    
    require '../helpers/init_conn_db.php';
    
    // Validate and sanitize inputs
    $username = trim($_POST['user_id'] ?? '');
    $password = $_POST['user_pass'] ?? '';
    
    // Input validation
    if (empty($username) || empty($password)) {
        header('Location: ../login.php?error=emptyfields');
        exit();
    }
    
    // Email validation if input looks like email
    if (filter_var($username, FILTER_VALIDATE_EMAIL)) {
        $username = filter_var($username, FILTER_SANITIZE_EMAIL);
    } else {
        // Username validation (alphanumeric + underscores, 3-30 chars)
        if (!preg_match('/^[a-zA-Z0-9_]{3,30}$/', $username)) {
            header('Location: ../login.php?error=invalidusername');
            exit();
        }
    }
    
    // Prepare SQL statement with prepared statements
    $sql = 'SELECT user_id, username, email, password, is_active, 
                   failed_attempts, last_login, account_locked_until 
            FROM Users 
            WHERE (username = ? OR email = ?) 
            AND is_active = 1';
    
    $stmt = mysqli_stmt_init($conn);
    
    if (!mysqli_stmt_prepare($stmt, $sql)) {
        error_log("Login SQL Error: " . mysqli_error($conn));
        header('Location: ../login.php?error=sqlerror');
        exit();
    }
    
    mysqli_stmt_bind_param($stmt, 'ss', $username, $username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        
        // Check if account is locked
        if (isset($row['account_locked_until']) && $row['account_locked_until'] > date('Y-m-d H:i:s')) {
            header('Location: ../login.php?error=accountlocked');
            exit();
        }
        
        // Verify password
        if (password_verify($password, $row['password'])) {
            
            // Check if password needs rehashing (for future algorithm updates)
            if (password_needs_rehash($row['password'], PASSWORD_ARGON2ID)) {
                $newHash = password_hash($password, PASSWORD_ARGON2ID);
                $updateSql = "UPDATE Users SET password = ? WHERE user_id = ?";
                $updateStmt = mysqli_prepare($conn, $updateSql);
                mysqli_stmt_bind_param($updateStmt, 'si', $newHash, $row['user_id']);
                mysqli_stmt_execute($updateStmt);
                mysqli_stmt_close($updateStmt);
            }
            
            // Reset failed attempts on successful login
            $resetSql = "UPDATE Users SET failed_attempts = 0, 
                         account_locked_until = NULL, 
                         last_login = NOW() 
                         WHERE user_id = ?";
            $resetStmt = mysqli_prepare($conn, $resetSql);
            mysqli_stmt_bind_param($resetStmt, 'i', $row['user_id']);
            mysqli_stmt_execute($resetStmt);
            mysqli_stmt_close($resetStmt);
            
            // Regenerate session ID to prevent fixation
            session_regenerate_id(true);
            
            // Set session variables
            $_SESSION['user_id'] = $row['user_id'];
            $_SESSION['user_uid'] = $row['username'];
            $_SESSION['user_email'] = $row['email'];
            $_SESSION['login_time'] = time();
            $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
            $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'];
            
            // Generate new CSRF token for next request
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            
            // Set secure, HTTP-only cookies (if needed for "remember me" functionality)
            // Note: REMOVED storing credentials in cookies (security risk)
            
            // Set a secure session cookie only
            $cookieParams = session_get_cookie_params();
            setcookie(
                session_name(),
                session_id(),
                [
                    'expires' => time() + (86400 * 30), // 30 days
                    'path' => $cookieParams['path'],
                    'domain' => $cookieParams['domain'],
                    'secure' => true, // Requires HTTPS
                    'httponly' => true,
                    'samesite' => 'Strict'
                ]
            );
            
            // Log successful login
            logLoginAttempt($row['user_id'], $_SERVER['REMOTE_ADDR'], true);
            
            // Redirect to dashboard or previous page
            if (isset($_SESSION['redirect_to'])) {
                $redirect = $_SESSION['redirect_to'];
                unset($_SESSION['redirect_to']);
                header("Location: $redirect");
            } else {
                header('Location: ../dashboard.php?login=success');
            }
            exit();
            
        } else {
            // Increment failed attempts
            incrementFailedAttempts($conn, $row['user_id']);
            
            // Log failed attempt
            logLoginAttempt($row['user_id'], $_SERVER['REMOTE_ADDR'], false);
            
            // Check if account should be locked
            $newAttempts = $row['failed_attempts'] + 1;
            if ($newAttempts >= 5) {
                $lockUntil = date('Y-m-d H:i:s', strtotime('+15 minutes'));
                $lockSql = "UPDATE Users SET account_locked_until = ? WHERE user_id = ?";
                $lockStmt = mysqli_prepare($conn, $lockSql);
                mysqli_stmt_bind_param($lockStmt, 'si', $lockUntil, $row['user_id']);
                mysqli_stmt_execute($lockStmt);
                mysqli_stmt_close($lockStmt);
                header('Location: ../login.php?error=accountlocked');
            } else {
                header('Location: ../login.php?error=wrongpwd&attempts=' . $newAttempts);
            }
            exit();
        }
    } else {
        // User not found
        header('Location: ../login.php?error=invalidcred');
        exit();
    }
    
    mysqli_stmt_close($stmt);
    mysqli_close($conn);
    
} else {
    header('Location: ../login.php');
    exit();
}

/**
 * Rate limiting function
 */
function rateLimitExceeded(string $ip): bool {
    $maxAttempts = 10;
    $timeWindow = 900; // 15 minutes in seconds
    
    // In production, use Redis or database for distributed systems
    // This is a simplified version using session
    if (!isset($_SESSION['login_attempts'])) {
        $_SESSION['login_attempts'] = [];
    }
    
    $now = time();
    $_SESSION['login_attempts'] = array_filter(
        $_SESSION['login_attempts'],
        function ($timestamp) use ($now, $timeWindow) {
            return ($now - $timestamp) < $timeWindow;
        }
    );
    
    if (count($_SESSION['login_attempts']) >= $maxAttempts) {
        return true;
    }
    
    $_SESSION['login_attempts'][] = $now;
    return false;
}

/**
 * Increment failed login attempts
 */
function incrementFailedAttempts(mysqli $conn, int $userId): void {
    $sql = "UPDATE Users 
            SET failed_attempts = failed_attempts + 1,
                last_failed_attempt = NOW()
            WHERE user_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $userId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

/**
 * Log login attempts for security monitoring
 */
function logLoginAttempt(int $userId, string $ip, bool $success): void {
    $logFile = __DIR__ . '/../logs/login_attempts.log';
    $timestamp = date('Y-m-d H:i:s');
    $status = $success ? 'SUCCESS' : 'FAILED';
    $logMessage = "[$timestamp] User ID: $userId, IP: $ip, Status: $status\n";
    
    file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
}

/**
 * Generate CSRF token (should be called on login form page)
 */
function generateCsrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}
