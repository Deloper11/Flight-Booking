<?php
declare(strict_types=1);
error_reporting(0); // Turn off error display in production

// Start session at the beginning
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => $_SERVER['HTTP_HOST'] ?? '',
        'secure' => true, // Require HTTPS
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
    session_start();
}

// Only process if this is a POST request (prevents accidental logout via GET)
// Or include CSRF protection for GET logout if needed
if ($_SERVER['REQUEST_METHOD'] === 'POST' || 
    (isset($_GET['logout_token']) && validateLogoutToken($_GET['logout_token']))) {
    
    // CSRF Protection for POST requests
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
            header('Location: ../index.php?error=csrf');
            exit();
        }
    }
    
    // Log the logout event
    logLogoutEvent($_SESSION['user_id'] ?? 0);
    
    // Clear all session data
    $_SESSION = [];
    
    // Destroy session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            [
                'expires' => time() - 42000,
                'path' => $params["path"],
                'domain' => $params["domain"],
                'secure' => $params["secure"],
                'httponly' => $params["httponly"],
                'samesite' => $params["samesite"]
            ]
        );
    }
    
    // Destroy the session
    session_destroy();
    
    // Clear any existing authentication cookies (for backward compatibility)
    clearAuthCookies();
    
    // Clear any OAuth or other third-party auth tokens
    clearThirdPartyTokens();
    
    // Regenerate session ID for next session
    session_start();
    session_regenerate_id(true);
    
    // Set a new CSRF token for any future login
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    
    // Redirect with appropriate message
    header('Location: ../index.php?logout=success&t=' . time());
    exit();
    
} else {
    // If accessed directly without proper method/token, show confirmation page
    showLogoutConfirmation();
    exit();
}

/**
 * Clear authentication cookies securely
 */
function clearAuthCookies(): void {
    $cookiesToClear = ['Uname', 'Upwd', 'remember_me', 'auth_token', 'session_token'];
    
    foreach ($cookiesToClear as $cookieName) {
        if (isset($_COOKIE[$cookieName])) {
            setcookie($cookieName, '', [
                'expires' => time() - 3600 * 24 * 7, // Past date
                'path' => '/',
                'domain' => $_SERVER['HTTP_HOST'] ?? '',
                'secure' => true,
                'httponly' => true,
                'samesite' => 'Strict'
            ]);
            unset($_COOKIE[$cookieName]);
        }
    }
    
    // Also clear any cookies with similar names (wildcard handling)
    foreach ($_COOKIE as $name => $value) {
        if (strpos($name, 'auth') !== false || 
            strpos($name, 'token') !== false ||
            strpos($name, 'session') !== false) {
            setcookie($name, '', [
                'expires' => time() - 3600,
                'path' => '/',
                'domain' => $_SERVER['HTTP_HOST'] ?? '',
                'secure' => true,
                'httponly' => true,
                'samesite' => 'Strict'
            ]);
            unset($_COOKIE[$name]);
        }
    }
}

/**
 * Clear third-party authentication tokens
 */
function clearThirdPartyTokens(): void {
    // Clear OAuth tokens from session
    $oauthTokens = ['oauth_token', 'oauth_token_secret', 'google_token', 'facebook_token'];
    
    foreach ($oauthTokens as $token) {
        if (isset($_SESSION[$token])) {
            unset($_SESSION[$token]);
        }
    }
    
    // Clear from cookies as well
    foreach ($oauthTokens as $token) {
        if (isset($_COOKIE[$token])) {
            setcookie($token, '', [
                'expires' => time() - 3600,
                'path' => '/',
                'domain' => $_SERVER['HTTP_HOST'] ?? '',
                'secure' => true,
                'httponly' => true,
                'samesite' => 'Strict'
            ]);
        }
    }
}

/**
 * Log logout event for security monitoring
 */
function logLogoutEvent(int $userId = 0): void {
    $logFile = __DIR__ . '/../logs/security.log';
    $timestamp = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    $referrer = $_SERVER['HTTP_REFERER'] ?? 'Direct';
    
    $logMessage = sprintf(
        "[%s] LOGOUT - UserID: %d, IP: %s, UserAgent: %s, Referrer: %s\n",
        $timestamp,
        $userId,
        $ip,
        substr($userAgent, 0, 100), // Limit length
        $referrer
    );
    
    if (is_writable(dirname($logFile))) {
        file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
    }
    
    // Also log to database if configured
    if (function_exists('logToDatabase')) {
        logToDatabase('logout', $userId, $ip);
    }
}

/**
 * Validate logout token for GET requests
 * (One-time use token for logout links)
 */
function validateLogoutToken(string $token): bool {
    if (!isset($_SESSION['logout_tokens'])) {
        return false;
    }
    
    $validTokens = $_SESSION['logout_tokens'];
    $currentTime = time();
    
    foreach ($validTokens as $index => $tokenData) {
        // Remove expired tokens
        if ($tokenData['expires'] < $currentTime) {
            unset($validTokens[$index]);
            continue;
        }
        
        // Check if token matches
        if (hash_equals($tokenData['token'], hash('sha256', $token))) {
            // Remove used token
            unset($validTokens[$index]);
            $_SESSION['logout_tokens'] = $validTokens;
            return true;
        }
    }
    
    $_SESSION['logout_tokens'] = $validTokens;
    return false;
}

/**
 * Generate a secure logout token for GET requests
 * Call this when creating logout links
 */
function generateLogoutToken(): string {
    if (!isset($_SESSION['logout_tokens'])) {
        $_SESSION['logout_tokens'] = [];
    }
    
    // Clean up old tokens first
    $currentTime = time();
    $_SESSION['logout_tokens'] = array_filter(
        $_SESSION['logout_tokens'],
        function ($tokenData) use ($currentTime) {
            return $tokenData['expires'] > $currentTime;
        }
    );
    
    // Generate new token
    $token = bin2hex(random_bytes(32));
    $hashedToken = hash('sha256', $token);
    
    $_SESSION['logout_tokens'][] = [
        'token' => $hashedToken,
        'expires' => $currentTime + 300, // 5 minutes validity
        'created' => $currentTime
    ];
    
    // Limit number of active tokens
    if (count($_SESSION['logout_tokens']) > 5) {
        array_shift($_SESSION['logout_tokens']);
    }
    
    return $token;
}

/**
 * Show logout confirmation page
 */
function showLogoutConfirmation(): void {
    header('Content-Type: text/html; charset=UTF-8');
    
    $csrfToken = $_SESSION['csrf_token'] ?? bin2hex(random_bytes(32));
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = $csrfToken;
    }
    
    echo '<!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="robots" content="noindex,nofollow">
        <title>Confirm Logout</title>
        <style>
            body {
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 20px;
            }
            .logout-box {
                background: white;
                border-radius: 12px;
                padding: 40px;
                box-shadow: 0 20px 60px rgba(0,0,0,0.3);
                text-align: center;
                max-width: 400px;
                width: 100%;
            }
            h2 {
                color: #333;
                margin-bottom: 20px;
                font-weight: 600;
            }
            p {
                color: #666;
                margin-bottom: 30px;
                line-height: 1.6;
            }
            .btn-group {
                display: flex;
                gap: 15px;
                justify-content: center;
            }
            button {
                padding: 12px 30px;
                border: none;
                border-radius: 6px;
                font-size: 16px;
                font-weight: 500;
                cursor: pointer;
                transition: all 0.3s ease;
                min-width: 120px;
            }
            .btn-logout {
                background: #dc3545;
                color: white;
            }
            .btn-logout:hover {
                background: #c82333;
                transform: translateY(-2px);
                box-shadow: 0 5px 15px rgba(220,53,69,0.4);
            }
            .btn-cancel {
                background: #6c757d;
                color: white;
            }
            .btn-cancel:hover {
                background: #5a6268;
                transform: translateY(-2px);
            }
            .security-note {
                font-size: 12px;
                color: #888;
                margin-top: 25px;
                padding-top: 15px;
                border-top: 1px solid #eee;
            }
        </style>
    </head>
    <body>
        <div class="logout-box">
            <h2>🔒 Confirm Logout</h2>
            <p>Are you sure you want to log out? This will end your current session and you\'ll need to log in again to access your account.</p>
            
            <form method="POST" action="' . htmlspecialchars($_SERVER['PHP_SELF']) . '" id="logoutForm">
                <input type="hidden" name="csrf_token" value="' . htmlspecialchars($csrfToken) . '">
                <div class="btn-group">
                    <button type="submit" class="btn-logout">Log Out</button>
                    <button type="button" class="btn-cancel" onclick="window.history.back();">Cancel</button>
                </div>
            </form>
            
            <div class="security-note">
                <strong>Security Tip:</strong> Always log out when using public or shared computers.
            </div>
        </div>
        
        <script>
            // Prevent form resubmission
            if (window.history.replaceState) {
                window.history.replaceState(null, null, window.location.href);
            }
            
            // Auto-submit on "Enter" key
            document.addEventListener("keydown", function(event) {
                if (event.key === "Enter") {
                    document.getElementById("logoutForm").submit();
                }
            });
        </script>
    </body>
    </html>';
};
