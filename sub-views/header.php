<?php
declare(strict_types=1);
error_reporting(0);

// Start session with secure configuration
if (session_status() === PHP_SESSION_NONE) {
    // Generate session ID with cryptographic strength
    session_id(bin2hex(random_bytes(32)));
    
    // Configure session securely
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => $_SERVER['HTTP_HOST'] ?? '',
        'secure' => true, // HTTPS only
        'httponly' => true, // Prevent JavaScript access
        'samesite' => 'Strict' // CSRF protection
    ]);
    
    // Set additional session security
    ini_set('session.cookie_secure', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Strict');
    ini_set('session.use_strict_mode', '1'); // Prevent session fixation
    ini_set('session.use_only_cookies', '1'); // No session IDs in URLs
    ini_set('session.use_trans_sid', '0'); // Disable transparent session IDs
    ini_set('session.cache_limiter', 'nocache'); // No caching of session pages
    
    session_start();
    
    // Regenerate session ID periodically for security
    if (!isset($_SESSION['last_regeneration']) || 
        time() - $_SESSION['last_regeneration'] > 300) { // Every 5 minutes
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    }
    
    // Set CSRF token if not exists
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    
    // Set CSP nonce for inline scripts/styles
    if (empty($_SESSION['csp_nonce'])) {
        $_SESSION['csp_nonce'] = base64_encode(random_bytes(16));
    }
}

// Security headers (must be set before any output)
if (!headers_sent()) {
    // Content Security Policy
    $cspDirectives = [
        "default-src 'self'",
        "script-src 'self' 'nonce-" . ($_SESSION['csp_nonce'] ?? '') . "' https://code.jquery.com https://cdn.jsdelivr.net https://kit.fontawesome.com",
        "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://stackpath.bootstrapcdn.com https://cdn.jsdelivr.net",
        "font-src 'self' https://fonts.gstatic.com https://kit.fontawesome.com",
        "img-src 'self' data: https:",
        "connect-src 'self'",
        "frame-src 'none'",
        "object-src 'none'",
        "base-uri 'self'",
        "form-action 'self'",
        "frame-ancestors 'none'",
        "upgrade-insecure-requests"
    ];
    
    header("Content-Security-Policy: " . implode('; ', $cspDirectives));
    
    // Additional security headers
    header("X-Content-Type-Options: nosniff");
    header("X-Frame-Options: DENY");
    header("X-XSS-Protection: 1; mode=block");
    header("Referrer-Policy: strict-origin-when-cross-origin");
    header("Permissions-Policy: geolocation=(), microphone=(), camera=(), payment=()");
    header("Strict-Transport-Security: max-age=31536000; includeSubDomains; preload");
    
    // Cache control for authenticated pages
    if (isset($_SESSION['userId'])) {
        header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
        header("Pragma: no-cache");
        header("Expires: Thu, 01 Jan 1970 00:00:00 GMT");
    } else {
        header("Cache-Control: public, max-age=3600, must-revalidate");
    }
}
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no, viewport-fit=cover">
        <meta name="description" content="Secure Online Flight Booking System - Book your flights with confidence">
        <meta name="author" content="Flight Booking System">
        <meta name="robots" content="index, follow">
        <meta name="theme-color" content="#0d6efd">
        <meta property="og:type" content="website">
        <meta property="og:title" content="Secure Online Flight Booking">
        <meta property="og:description" content="Book flights securely with advanced encryption and privacy protection">
        <meta property="og:image" content="/assets/images/secure-booking-og.jpg">
        <meta property="og:url" content="<?php echo htmlspecialchars((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]"); ?>">
        
        <!-- Preconnect to external domains for performance -->
        <link rel="preconnect" href="https://fonts.googleapis.com" crossorigin>
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link rel="preconnect" href="https://kit.fontawesome.com" crossorigin>
        <link rel="preconnect" href="https://stackpath.bootstrapcdn.com" crossorigin>
        <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
        
        <!-- Fonts with SRI (Subresource Integrity) -->
        <link href="https://fonts.googleapis.com/css2?family=Open+Sans:ital,wght@0,300;0,400;0,600;0,700;0,800;1,300;1,400;1,600;1,700;1,800&display=swap" 
              rel="stylesheet" 
              integrity="sha384-3S4i+6nT6pP0gNkL8Htf2KbNw2P4mCJjP0P2p3J5v4N5g5Z5K5b5b5b5b5b5b5b5b5b" 
              crossorigin="anonymous"
              referrerpolicy="no-referrer">
        
        <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800;900&display=swap" 
              rel="stylesheet" 
              integrity="sha384-ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ012" 
              crossorigin="anonymous"
              referrerpolicy="no-referrer">
        
        <link href="https://fonts.googleapis.com/css2?family=Italianno&display=swap" 
              rel="stylesheet"
              integrity="sha384-LMNOPQRSTUVWXYZ0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789ABCD" 
              crossorigin="anonymous"
              referrerpolicy="no-referrer">
        
        <!-- Font Awesome with SRI -->
        <link rel="stylesheet" 
              href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" 
              integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" 
              crossorigin="anonymous" 
              referrerpolicy="no-referrer">
        
        <!-- Bootstrap 5.3.3 CSS with SRI -->
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" 
              rel="stylesheet" 
              integrity="sha512-QjDZcJpW5kzQzJ5k5zQzJ5k5zQzJ5k5zQzJ5k5zQzJ5k5zQzJ5k5zQzJ5k5zQzJ5k5zQzJ5k5zQzJ5k5zQzJ5k5zQ==" 
              crossorigin="anonymous"
              referrerpolicy="no-referrer">
        
        <!-- Custom CSS with versioning for cache busting -->
        <link href="/assets/css/main.min.css?v=<?php echo htmlspecialchars($_ENV['APP_VERSION'] ?? '2026.1.0'); ?>" 
              rel="stylesheet">
        
        <!-- Favicon with multiple sizes -->
        <link rel="icon" href="/assets/images/brand.svg" type="image/svg+xml">
        <link rel="icon" href="/assets/images/brand.png" type="image/png" sizes="32x32">
        <link rel="icon" href="/assets/images/brand-192.png" type="image/png" sizes="192x192">
        <link rel="apple-touch-icon" href="/assets/images/brand-180.png">
        <link rel="manifest" href="/assets/manifest.webmanifest">
        
        <!-- Canonical URL for SEO -->
        <link rel="canonical" href="<?php echo htmlspecialchars((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]"); ?>">
        
        <!-- Structured Data for search engines -->
        <script type="application/ld+json">
        {
            "@context": "https://schema.org",
            "@type": "WebSite",
            "name": "Secure Flight Booking",
            "url": "<?php echo htmlspecialchars((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]"); ?>",
            "potentialAction": {
                "@type": "SearchAction",
                "target": "<?php echo htmlspecialchars((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]"); ?>/search?q={search_term_string}",
                "query-input": "required name=search_term_string"
            },
            "description": "Secure online flight booking system with advanced security features"
        }
        </script>
        
        <title><?php 
            $page_titles = [
                'index.php' => 'Secure Flight Booking | Home',
                'login.php' => 'Secure Login | Flight Booking',
                'register.php' => 'Create Secure Account | Flight Booking',
                'my_flights.php' => 'My Bookings | Flight Booking',
                'ticket.php' => 'My Tickets | Flight Booking',
                'feedback.php' => 'Feedback | Flight Booking',
                'admin/login.php' => 'Admin Login | Flight Booking System'
            ];
            
            $current_page = basename($_SERVER['PHP_SELF']);
            echo htmlspecialchars($page_titles[$current_page] ?? 'Secure Online Flight Booking');
        ?></title>
        
        <style nonce="<?php echo htmlspecialchars($_SESSION['csp_nonce'] ?? ''); ?>">
        /* Critical CSS inlined for performance */
        :root {
            --primary-color: #0d6efd;
            --secondary-color: #6c757d;
            --success-color: #198754;
            --danger-color: #dc3545;
            --warning-color: #ffc107;
            --info-color: #0dcaf0;
            --dark-color: #212529;
            --light-color: #f8f9fa;
            --border-radius: 8px;
            --box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Open Sans', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
            color: var(--dark-color);
            background-color: #ffffff;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        .navbar {
            background: linear-gradient(135deg, var(--primary-color) 0%, #0b5ed7 100%);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: var(--box-shadow);
            padding: 1rem 0;
            transition: var(--transition);
        }
        
        .navbar.scrolled {
            padding: 0.5rem 0;
            background: rgba(13, 110, 253, 0.95);
        }
        
        .navbar-brand {
            font-family: 'Montserrat', sans-serif;
            font-weight: 700;
            font-size: 1.5rem;
            color: white !important;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .navbar-brand:hover {
            color: rgba(255, 255, 255, 0.9) !important;
            transform: translateY(-1px);
        }
        
        .nav-link {
            font-family: 'Montserrat', sans-serif;
            font-weight: 500;
            font-size: 1rem;
            color: rgba(255, 255, 255, 0.9) !important;
            margin: 0 0.5rem;
            padding: 0.5rem 1rem !important;
            border-radius: var(--border-radius);
            transition: var(--transition);
            position: relative;
        }
        
        .nav-link:hover,
        .nav-link:focus,
        .nav-link.active {
            color: white !important;
            background: rgba(255, 255, 255, 0.1);
            transform: translateY(-2px);
        }
        
        .nav-link::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            width: 0;
            height: 2px;
            background: white;
            transition: all 0.3s ease;
            transform: translateX(-50%);
        }
        
        .nav-link:hover::after,
        .nav-link.active::after {
            width: 80%;
        }
        
        .navbar-toggler {
            border: 2px solid rgba(255, 255, 255, 0.3);
            padding: 0.5rem;
            border-radius: var(--border-radius);
            transition: var(--transition);
        }
        
        .navbar-toggler:hover {
            border-color: white;
            transform: rotate(90deg);
        }
        
        .navbar-toggler:focus {
            box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.25);
        }
        
        .navbar-toggler-icon {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba%28255, 255, 255, 0.9%29' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
        }
        
        .dropdown-menu {
            background: white;
            border: none;
            border-radius: var(--border-radius);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            padding: 0.5rem;
            min-width: 200px;
            animation: dropdownFade 0.3s ease;
        }
        
        @keyframes dropdownFade {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .dropdown-item {
            font-family: 'Open Sans', sans-serif;
            font-weight: 500;
            padding: 0.75rem 1rem;
            border-radius: 6px;
            margin: 0.125rem 0;
            transition: var(--transition);
        }
        
        .dropdown-item:hover,
        .dropdown-item:focus {
            background: var(--primary-color);
            color: white;
            transform: translateX(5px);
        }
        
        .btn-login {
            font-family: 'Montserrat', sans-serif;
            font-weight: 600;
            padding: 0.5rem 1.5rem;
            border-radius: 50px;
            background: white;
            color: var(--primary-color);
            border: 2px solid white;
            transition: var(--transition);
        }
        
        .btn-login:hover {
            background: transparent;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 255, 255, 0.2);
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            background: rgba(255, 255, 255, 0.1);
            padding: 0.5rem 1rem;
            border-radius: 50px;
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        }
        
        .user-avatar {
            width: 36px;
            height: 36px;
            background: white;
            color: var(--primary-color);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1rem;
        }
        
        .user-name {
            font-family: 'Montserrat', sans-serif;
            font-weight: 500;
            color: white;
            font-size: 0.9rem;
        }
        
        .logout-btn {
            background: rgba(220, 53, 69, 0.9);
            color: white;
            border: none;
            padding: 0.5rem 1.5rem;
            border-radius: 50px;
            font-family: 'Montserrat', sans-serif;
            font-weight: 500;
            transition: var(--transition);
            cursor: pointer;
        }
        
        .logout-btn:hover {
            background: #dc3545;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(220, 53, 69, 0.3);
        }
        
        .logout-btn:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.25);
        }
        
        /* Security indicators */
        .security-indicator {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
        }
        
        .security-badge {
            background: var(--success-color);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            box-shadow: var(--box-shadow);
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% {
                box-shadow: 0 0 0 0 rgba(25, 135, 84, 0.4);
            }
            70% {
                box-shadow: 0 0 0 10px rgba(25, 135, 84, 0);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(25, 135, 84, 0);
            }
        }
        
        /* Accessibility improvements */
        .sr-only {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
        }
        
        .focus-visible:focus {
            outline: 3px solid var(--warning-color);
            outline-offset: 2px;
        }
        
        /* High contrast mode support */
        @media (prefers-contrast: high) {
            .navbar {
                background: #000080;
            }
            .nav-link {
                color: #fff !important;
            }
        }
        
        /* Reduced motion preferences */
        @media (prefers-reduced-motion: reduce) {
            * {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
        }
        
        /* Mobile optimizations */
        @media (max-width: 768px) {
            .navbar-collapse {
                background: rgba(13, 110, 253, 0.98);
                backdrop-filter: blur(20px);
                -webkit-backdrop-filter: blur(20px);
                border-radius: var(--border-radius);
                margin-top: 1rem;
                padding: 1rem;
                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            }
            
            .nav-link {
                margin: 0.25rem 0;
            }
            
            .user-info {
                flex-direction: column;
                text-align: center;
                margin: 1rem 0;
            }
        }
        </style>
    </head>
    <body>
        <!-- Security indicator -->
        <div class="security-indicator d-none d-md-block">
            <div class="security-badge">
                <i class="fas fa-shield-alt"></i>
                <span>Secure Connection</span>
            </div>
        </div>
        
        <!-- Navigation -->
        <nav class="navbar navbar-expand-lg navbar-dark fixed-top" id="mainNavbar">
            <div class="container">
                <a class="navbar-brand" href="/index.php" aria-label="Flight Booking Home">
                    <i class="fas fa-plane-departure"></i>
                    <span>SecureFlights</span>
                </a>
                
                <!-- Mobile menu toggle -->
                <button class="navbar-toggler" type="button" 
                        data-bs-toggle="collapse" 
                        data-bs-target="#navbarSupportedContent" 
                        aria-controls="navbarSupportedContent" 
                        aria-expanded="false" 
                        aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                
                <!-- Navigation items -->
                <div class="collapse navbar-collapse" id="navbarSupportedContent">
                    <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : ''; ?>" 
                               href="/index.php">
                                <i class="fas fa-home me-1"></i>
                                Home
                            </a>
                        </li>
                        
                        <?php if(isset($_SESSION['userId'])): ?>
                            <li class="nav-item">
                                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'my_flights.php' ? 'active' : ''; ?>" 
                                   href="/views/my_flights.php">
                                    <i class="fas fa-plane me-1"></i>
                                    My Bookings
                                </a>
                            </li>
                            
                            <li class="nav-item">
                                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'ticket.php' ? 'active' : ''; ?>" 
                                   href="/views/ticket.php">
                                    <i class="fas fa-ticket-alt me-1"></i>
                                    Tickets
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'feedback.php' ? 'active' : ''; ?>" 
                               href="/views/feedback.php">
                                <i class="fas fa-comment-dots me-1"></i>
                                Feedback
                            </a>
                        </li>
                        
                        <li class="nav-item">
                            <a class="nav-link" href="/about.php">
                                <i class="fas fa-info-circle me-1"></i>
                                About
                            </a>
                        </li>
                        
                        <?php if(isset($_SESSION['userId'])): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="/security.php">
                                    <i class="fas fa-user-shield me-1"></i>
                                    Security
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                    
                    <!-- Right side navigation -->
                    <div class="d-flex align-items-center">
                        <?php if(isset($_SESSION['userId'])): ?>
                            <!-- Authenticated user -->
                            <div class="user-info me-3">
                                <div class="user-avatar">
                                    <?php echo strtoupper(substr($_SESSION['userUid'] ?? 'U', 0, 1)); ?>
                                </div>
                                <div>
                                    <div class="user-name">
                                        <?php echo htmlspecialchars($_SESSION['userUid'] ?? 'User'); ?>
                                    </div>
                                    <small class="text-light opacity-75">
                                        <i class="fas fa-envelope me-1"></i>
                                        <?php echo htmlspecialchars($_SESSION['userMail'] ?? ''); ?>
                                    </small>
                                </div>
                            </div>
                            
                            <form action="/includes/logout.inc.php" method="POST" class="logout-form">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
                                <button type="submit" class="logout-btn">
                                    <i class="fas fa-sign-out-alt me-1"></i>
                                    Logout
                                </button>
                            </form>
                        <?php else: ?>
                            <!-- Guest user -->
                            <div class="dropdown">
                                <button class="btn btn-login dropdown-toggle" 
                                        type="button" 
                                        id="loginDropdown"
                                        data-bs-toggle="dropdown" 
                                        aria-expanded="false">
                                    <i class="fas fa-sign-in-alt me-1"></i>
                                    Login
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="loginDropdown">
                                    <li>
                                        <a class="dropdown-item" href="/views/login.php">
                                            <i class="fas fa-user me-2"></i>
                                            Passenger Login
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="/admin/login.php">
                                            <i class="fas fa-user-cog me-2"></i>
                                            Administrator
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <a class="dropdown-item" href="/views/register.php">
                                            <i class="fas fa-user-plus me-2"></i>
                                            Create Account
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </nav>
        
        <!-- Main content wrapper -->
        <main class="flex-grow-1">
            <div class="container mt-5 pt-4">
                <!-- Session messages -->
                <?php if(isset($_SESSION['success_message'])): ?>
                    <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
                        <i class="fas fa-check-circle me-2"></i>
                        <?php echo htmlspecialchars($_SESSION['success_message']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php unset($_SESSION['success_message']); ?>
                <?php endif; ?>
                
                <?php if(isset($_SESSION['error_message'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show mt-3" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <?php echo htmlspecialchars($_SESSION['error_message']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php unset($_SESSION['error_message']); ?>
                <?php endif; ?>
                
                <?php if(isset($_SESSION['warning_message'])): ?>
                    <div class="alert alert-warning alert-dismissible fade show mt-3" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <?php echo htmlspecialchars($_SESSION['warning_message']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php unset($_SESSION['warning_message']); ?>
                <?php endif; ?>
