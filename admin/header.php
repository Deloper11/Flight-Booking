<?php
session_start();
?>
<!doctype html>
<html lang="en" data-theme="dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <meta name="description" content="Airlines Management System - Admin Dashboard 2026">
        <meta name="author" content="Airlines 2026">
        <meta name="robots" content="noindex, nofollow">
        
        <!-- Primary Meta Tags -->
        <title>Admin Dashboard | Airlines 2026</title>
        <link rel="icon" type="image/x-icon" href="../assets/images/brand-2026.svg">
        
        <!-- Preload Critical Assets -->
        <link rel="preload" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" as="style">
        <link rel="preload" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" as="style">
        <link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" as="style">
        
        <!-- Fonts -->
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Space+Grotesk:wght@400;500;600;700&family=JetBrains+Mono:wght@300;400;500&display=swap" rel="stylesheet">
        
        <!-- CSS Libraries -->
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@eonasdan/tempus-dominus@6.7.0/dist/css/tempus-dominus.min.css">
        
        <!-- Custom Admin CSS -->
        <link rel="stylesheet" href="../assets/css/admin-core.css?v=<?php echo time(); ?>">
        <link rel="stylesheet" href="../assets/css/admin-responsive.css?v=<?php echo time(); ?>">
        
        <style>
            :root {
                --admin-primary: #4361ee;
                --admin-secondary: #3a0ca3;
                --admin-success: #06d6a0;
                --admin-warning: #ffd166;
                --admin-danger: #ef476f;
                --admin-info: #4cc9f0;
                --admin-dark: #121826;
                --admin-light: #f8f9fa;
                --admin-surface: #1a202c;
                --admin-surface-light: #2d3748;
                --admin-border: #2d3748;
                --admin-text: #e2e8f0;
                --admin-text-secondary: #a0aec0;
                --admin-gradient: linear-gradient(135deg, #4361ee 0%, #3a0ca3 100%);
                --admin-glass: rgba(26, 32, 44, 0.8);
                --admin-glass-border: rgba(255, 255, 255, 0.1);
                --admin-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.3);
                --admin-shadow-lg: 0 20px 25px -5px rgba(0, 0, 0, 0.5);
            }

            [data-theme="light"] {
                --admin-dark: #f8fafc;
                --admin-surface: #ffffff;
                --admin-surface-light: #f1f5f9;
                --admin-border: #e2e8f0;
                --admin-text: #1e293b;
                --admin-text-secondary: #64748b;
                --admin-glass: rgba(255, 255, 255, 0.8);
                --admin-glass-border: rgba(0, 0, 0, 0.1);
            }

            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }

            body {
                font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
                background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
                color: var(--admin-text);
                min-height: 100vh;
                overflow-x: hidden;
            }

            /* Admin Header */
            .admin-header {
                background: var(--admin-glass);
                backdrop-filter: blur(20px);
                border-bottom: 1px solid var(--admin-glass-border);
                position: sticky;
                top: 0;
                z-index: 1030;
                box-shadow: var(--admin-shadow);
            }

            .header-container {
                max-width: 1400px;
                margin: 0 auto;
                padding: 0 1.5rem;
            }

            /* Brand Section */
            .brand-section {
                display: flex;
                align-items: center;
                gap: 1rem;
            }

            .brand-logo {
                width: 40px;
                height: 40px;
                background: var(--admin-gradient);
                border-radius: 12px;
                display: flex;
                align-items: center;
                justify-content: center;
                color: white;
                font-size: 1.25rem;
            }

            .brand-text {
                display: flex;
                flex-direction: column;
            }

            .brand-title {
                font-family: 'Space Grotesk', sans-serif;
                font-size: 1.5rem;
                font-weight: 700;
                background: var(--admin-gradient);
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
                background-clip: text;
                line-height: 1.2;
            }

            .brand-subtitle {
                font-size: 0.75rem;
                color: var(--admin-text-secondary);
                font-weight: 500;
                letter-spacing: 1px;
                text-transform: uppercase;
            }

            /* Navigation */
            .nav-container {
                display: flex;
                align-items: center;
                justify-content: space-between;
                height: 70px;
            }

            .nav-main {
                display: flex;
                align-items: center;
                gap: 0.5rem;
            }

            .nav-item {
                position: relative;
            }

            .nav-link {
                color: var(--admin-text);
                text-decoration: none;
                padding: 0.75rem 1.25rem;
                border-radius: 12px;
                display: flex;
                align-items: center;
                gap: 0.75rem;
                font-weight: 500;
                transition: all 0.3s ease;
                position: relative;
                overflow: hidden;
            }

            .nav-link::before {
                content: '';
                position: absolute;
                bottom: 0;
                left: 50%;
                transform: translateX(-50%);
                width: 0;
                height: 3px;
                background: var(--admin-primary);
                border-radius: 3px;
                transition: width 0.3s ease;
            }

            .nav-link:hover {
                background: rgba(67, 97, 238, 0.1);
                color: var(--admin-primary);
            }

            .nav-link:hover::before {
                width: 80%;
            }

            .nav-link.active {
                background: rgba(67, 97, 238, 0.15);
                color: var(--admin-primary);
            }

            .nav-link.active::before {
                width: 80%;
            }

            .nav-icon {
                font-size: 1.1rem;
                width: 20px;
                text-align: center;
            }

            .nav-label {
                font-size: 0.95rem;
            }

            /* Quick Actions */
            .quick-actions {
                display: flex;
                align-items: center;
                gap: 1rem;
            }

            /* Search Bar */
            .search-container {
                position: relative;
                width: 300px;
            }

            .search-input {
                width: 100%;
                background: var(--admin-surface-light);
                border: 2px solid var(--admin-border);
                border-radius: 50px;
                padding: 0.75rem 1rem 0.75rem 3rem;
                color: var(--admin-text);
                font-size: 0.95rem;
                transition: all 0.3s ease;
            }

            .search-input:focus {
                outline: none;
                border-color: var(--admin-primary);
                box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
                background: var(--admin-surface);
            }

            .search-icon {
                position: absolute;
                left: 1rem;
                top: 50%;
                transform: translateY(-50%);
                color: var(--admin-text-secondary);
                pointer-events: none;
            }

            /* Notification Bell */
            .notification-bell {
                position: relative;
            }

            .notification-btn {
                width: 45px;
                height: 45px;
                border-radius: 50%;
                background: var(--admin-surface-light);
                border: 2px solid var(--admin-border);
                color: var(--admin-text);
                display: flex;
                align-items: center;
                justify-content: center;
                cursor: pointer;
                transition: all 0.3s ease;
                position: relative;
            }

            .notification-btn:hover {
                background: var(--admin-surface);
                border-color: var(--admin-primary);
                color: var(--admin-primary);
            }

            .notification-badge {
                position: absolute;
                top: -5px;
                right: -5px;
                background: var(--admin-danger);
                color: white;
                font-size: 0.7rem;
                font-weight: 600;
                min-width: 20px;
                height: 20px;
                border-radius: 10px;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 0 5px;
                border: 2px solid var(--admin-surface);
            }

            /* User Profile */
            .user-profile {
                display: flex;
                align-items: center;
                gap: 0.75rem;
                padding: 0.5rem;
                border-radius: 12px;
                cursor: pointer;
                transition: all 0.3s ease;
                position: relative;
            }

            .user-profile:hover {
                background: var(--admin-surface-light);
            }

            .user-avatar {
                width: 45px;
                height: 45px;
                border-radius: 50%;
                background: var(--admin-gradient);
                display: flex;
                align-items: center;
                justify-content: center;
                color: white;
                font-weight: 600;
                font-size: 1.1rem;
                border: 3px solid var(--admin-surface-light);
            }

            .user-info {
                display: flex;
                flex-direction: column;
            }

            .user-name {
                font-weight: 600;
                color: var(--admin-text);
                font-size: 0.95rem;
            }

            .user-role {
                font-size: 0.8rem;
                color: var(--admin-text-secondary);
                font-weight: 500;
            }

            .user-dropdown-arrow {
                color: var(--admin-text-secondary);
                transition: transform 0.3s ease;
            }

            /* Dropdown Menu */
            .dropdown-menu {
                background: var(--admin-surface);
                border: 1px solid var(--admin-border);
                border-radius: 16px;
                padding: 0;
                min-width: 250px;
                box-shadow: var(--admin-shadow-lg);
                animation: dropdownSlide 0.2s ease;
                overflow: hidden;
            }

            @keyframes dropdownSlide {
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
                padding: 0.875rem 1.25rem;
                color: var(--admin-text);
                text-decoration: none;
                display: flex;
                align-items: center;
                gap: 0.75rem;
                transition: all 0.3s ease;
                border-left: 3px solid transparent;
            }

            .dropdown-item:hover {
                background: var(--admin-surface-light);
                color: var(--admin-primary);
                border-left-color: var(--admin-primary);
            }

            .dropdown-item.danger:hover {
                color: var(--admin-danger);
                border-left-color: var(--admin-danger);
            }

            .dropdown-divider {
                border-color: var(--admin-border);
                margin: 0.5rem 0;
            }

            /* Mobile Menu Toggle */
            .mobile-menu-toggle {
                display: none;
                background: none;
                border: none;
                color: var(--admin-text);
                font-size: 1.5rem;
                cursor: pointer;
                padding: 0.5rem;
                border-radius: 8px;
                transition: all 0.3s ease;
            }

            .mobile-menu-toggle:hover {
                background: var(--admin-surface-light);
                color: var(--admin-primary);
            }

            /* Theme Toggle */
            .theme-toggle {
                width: 45px;
                height: 45px;
                border-radius: 50%;
                background: var(--admin-surface-light);
                border: 2px solid var(--admin-border);
                color: var(--admin-text);
                display: flex;
                align-items: center;
                justify-content: center;
                cursor: pointer;
                transition: all 0.3s ease;
            }

            .theme-toggle:hover {
                background: var(--admin-surface);
                border-color: var(--admin-primary);
                color: var(--admin-primary);
            }

            /* Responsive Design */
            @media (max-width: 1200px) {
                .nav-label {
                    display: none;
                }
                
                .nav-link {
                    padding: 0.75rem;
                }
                
                .search-container {
                    width: 250px;
                }
            }

            @media (max-width: 992px) {
                .mobile-menu-toggle {
                    display: block;
                }
                
                .nav-main {
                    position: fixed;
                    top: 70px;
                    left: 0;
                    right: 0;
                    background: var(--admin-surface);
                    border-top: 1px solid var(--admin-border);
                    border-bottom: 1px solid var(--admin-border);
                    padding: 1rem;
                    flex-direction: column;
                    align-items: stretch;
                    display: none;
                    box-shadow: var(--admin-shadow);
                    z-index: 1000;
                }
                
                .nav-main.active {
                    display: flex;
                }
                
                .nav-item {
                    width: 100%;
                }
                
                .nav-link {
                    justify-content: flex-start;
                    padding: 1rem;
                    border-radius: 8px;
                }
                
                .search-container {
                    width: 100%;
                    margin-bottom: 1rem;
                }
                
                .quick-actions {
                    margin-left: auto;
                }
            }

            @media (max-width: 768px) {
                .brand-text {
                    display: none;
                }
                
                .user-info {
                    display: none;
                }
                
                .header-container {
                    padding: 0 1rem;
                }
            }

            @media (max-width: 576px) {
                .quick-actions {
                    gap: 0.5rem;
                }
                
                .search-container {
                    display: none;
                }
                
                .user-profile {
                    padding: 0.25rem;
                }
            }

            /* Dark/Light Mode Styles */
            .theme-icon-light {
                display: none;
            }

            .theme-icon-dark {
                display: block;
            }

            [data-theme="light"] .theme-icon-light {
                display: block;
            }

            [data-theme="light"] .theme-icon-dark {
                display: none;
            }

            /* Accessibility */
            @media (prefers-reduced-motion: reduce) {
                * {
                    animation-duration: 0.01ms !important;
                    animation-iteration-count: 1 !important;
                    transition-duration: 0.01ms !important;
                }
            }

            /* Print Styles */
            @media print {
                .admin-header {
                    display: none !important;
                }
            }
        </style>
    </head>
    <body>
        <!-- Admin Header -->
        <header class="admin-header">
            <div class="header-container">
                <div class="nav-container">
                    <!-- Brand Section -->
                    <div class="brand-section">
                        <div class="brand-logo">
                            <i class="fas fa-plane"></i>
                        </div>
                        <div class="brand-text">
                            <div class="brand-title">AIRLINES 2026</div>
                            <div class="brand-subtitle">ADMIN DASHBOARD</div>
                        </div>
                    </div>

                    <!-- Mobile Menu Toggle -->
                    <button class="mobile-menu-toggle" id="mobileMenuToggle">
                        <i class="fas fa-bars"></i>
                    </button>

                    <!-- Main Navigation -->
                    <nav class="nav-main" id="mainNav">
                        <!-- Search Bar -->
                        <div class="search-container">
                            <i class="fas fa-search search-icon"></i>
                            <input type="search" 
                                   class="search-input" 
                                   placeholder="Search flights, bookings, users..."
                                   aria-label="Search">
                        </div>

                        <?php if(isset($_SESSION['adminId'])): ?>
                        <!-- Navigation Items -->
                        <div class="nav-item">
                            <a href="index.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
                                <i class="fas fa-tachometer-alt nav-icon"></i>
                                <span class="nav-label">Dashboard</span>
                            </a>
                        </div>

                        <div class="nav-item">
                            <a href="add_flight.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'add_flight.php' ? 'active' : ''; ?>">
                                <i class="fas fa-plus-circle nav-icon"></i>
                                <span class="nav-label">Add Flight</span>
                            </a>
                        </div>

                        <div class="nav-item">
                            <a href="flightscnt.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'flightscnt.php' ? 'active' : ''; ?>">
                                <i class="fas fa-plane nav-icon"></i>
                                <span class="nav-label">Manage Flights</span>
                            </a>
                        </div>

                        <div class="nav-item">
                            <a href="bookings.php" class="nav-link <?php echo in_array(basename($_SERVER['PHP_SELF']), ['bookings.php', 'view_booking.php']) ? 'active' : ''; ?>">
                                <i class="fas fa-ticket-alt nav-icon"></i>
                                <span class="nav-label">Bookings</span>
                            </a>
                        </div>

                        <div class="nav-item">
                            <a href="users.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : ''; ?>">
                                <i class="fas fa-users nav-icon"></i>
                                <span class="nav-label">Users</span>
                            </a>
                        </div>

                        <div class="nav-item">
                            <a href="airlines.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'airlines.php' ? 'active' : ''; ?>">
                                <i class="fas fa-building nav-icon"></i>
                                <span class="nav-label">Airlines</span>
                            </a>
                        </div>

                        <div class="nav-item">
                            <a href="reports.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : ''; ?>">
                                <i class="fas fa-chart-bar nav-icon"></i>
                                <span class="nav-label">Reports</span>
                            </a>
                        </div>

                        <div class="nav-item">
                            <a href="settings.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?>">
                                <i class="fas fa-cog nav-icon"></i>
                                <span class="nav-label">Settings</span>
                            </a>
                        </div>
                        <?php endif; ?>
                    </nav>

                    <!-- Quick Actions -->
                    <div class="quick-actions">
                        <?php if(isset($_SESSION['adminId'])): ?>
                        <!-- Theme Toggle -->
                        <button class="theme-toggle" id="themeToggle" title="Toggle Theme">
                            <i class="fas fa-moon theme-icon-light"></i>
                            <i class="fas fa-sun theme-icon-dark"></i>
                        </button>

                        <!-- Notification Bell -->
                        <div class="notification-bell">
                            <button class="notification-btn" id="notificationBtn">
                                <i class="fas fa-bell"></i>
                                <span class="notification-badge">3</span>
                            </button>
                        </div>

                        <!-- User Profile Dropdown -->
                        <div class="user-profile dropdown" id="userDropdown">
                            <div class="user-avatar">
                                <?php 
                                    $initials = '';
                                    if(isset($_SESSION['adminUname'])) {
                                        $words = explode(' ', $_SESSION['adminUname']);
                                        foreach($words as $word) {
                                            $initials .= strtoupper(substr($word, 0, 1));
                                            if(strlen($initials) >= 2) break;
                                        }
                                    }
                                    echo $initials ?: 'AD';
                                ?>
                            </div>
                            <div class="user-info">
                                <div class="user-name"><?php echo htmlspecialchars($_SESSION['adminUname'] ?? 'Administrator'); ?></div>
                                <div class="user-role">Super Admin</div>
                            </div>
                            <i class="fas fa-chevron-down user-dropdown-arrow"></i>
                            
                            <!-- Dropdown Menu -->
                            <div class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                                <div class="px-3 py-2">
                                    <div class="text-muted small">Signed in as</div>
                                    <div class="fw-bold"><?php echo htmlspecialchars($_SESSION['adminUname'] ?? 'Administrator'); ?></div>
                                </div>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="profile.php">
                                    <i class="fas fa-user-circle"></i>
                                    My Profile
                                </a>
                                <a class="dropdown-item" href="settings.php">
                                    <i class="fas fa-cog"></i>
                                    Account Settings
                                </a>
                                <a class="dropdown-item" href="activity.php">
                                    <i class="fas fa-history"></i>
                                    Activity Log
                                </a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="help.php">
                                    <i class="fas fa-question-circle"></i>
                                    Help & Support
                                </a>
                                <a class="dropdown-item" href="documentation.php">
                                    <i class="fas fa-book"></i>
                                    Documentation
                                </a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item danger" href="../includes/logout.inc.php">
                                    <i class="fas fa-sign-out-alt"></i>
                                    Logout
                                </a>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </header>

        <!-- Notification Panel -->
        <div class="notification-panel" id="notificationPanel">
            <div class="notification-header">
                <h5>Notifications</h5>
                <button class="notification-close" id="notificationClose">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="notification-list">
                <!-- Notifications will be loaded here -->
            </div>
        </div>

        <!-- Quick Add Modal -->
        <div class="modal fade" id="quickAddModal" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Quick Add</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <ul class="nav nav-pills mb-3" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" data-bs-toggle="pill" data-bs-target="#airlineTab">
                                    <i class="fas fa-plane me-2"></i>Airline
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" data-bs-toggle="pill" data-bs-target="#flightTab">
                                    <i class="fas fa-route me-2"></i>Flight
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" data-bs-toggle="pill" data-bs-target="#userTab">
                                    <i class="fas fa-user-plus me-2"></i>User
                                </button>
                            </li>
                        </ul>
                        
                        <div class="tab-content">
                            <div class="tab-pane fade show active" id="airlineTab">
                                <form action="../includes/admin/airline.inc.php" method="post">
                                    <div class="mb-3">
                                        <label class="form-label">Airline Name</label>
                                        <input type="text" class="form-control" name="airline" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Airline Code</label>
                                        <input type="text" class="form-control" name="code" maxlength="3" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Total Seats</label>
                                        <input type="number" class="form-control" name="seats" min="1" required>
                                    </div>
                                    <button type="submit" name="air_but" class="btn btn-primary w-100">
                                        <i class="fas fa-plus me-2"></i>Add Airline
                                    </button>
                                </form>
                            </div>
                            <div class="tab-pane fade" id="flightTab">
                                <!-- Quick flight form -->
                            </div>
                            <div class="tab-pane fade" id="userTab">
                                <!-- Quick user form -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content Wrapper -->
        <main class="main-content">
            <div class="container-fluid py-4"> 
<!-- Header JavaScript -->
<script>
    // DOM Ready
    document.addEventListener('DOMContentLoaded', function() {
        // Mobile Menu Toggle
        const mobileMenuToggle = document.getElementById('mobileMenuToggle');
        const mainNav = document.getElementById('mainNav');
        
        if (mobileMenuToggle && mainNav) {
            mobileMenuToggle.addEventListener('click', function() {
                mainNav.classList.toggle('active');
                this.innerHTML = mainNav.classList.contains('active') 
                    ? '<i class="fas fa-times"></i>' 
                    : '<i class="fas fa-bars"></i>';
            });
            
            // Close menu when clicking outside
            document.addEventListener('click', function(e) {
                if (!mainNav.contains(e.target) && !mobileMenuToggle.contains(e.target)) {
                    mainNav.classList.remove('active');
                    mobileMenuToggle.innerHTML = '<i class="fas fa-bars"></i>';
                }
            });
        }
        
        // Theme Toggle
        const themeToggle = document.getElementById('themeToggle');
        if (themeToggle) {
            themeToggle.addEventListener('click', function() {
                const currentTheme = document.documentElement.getAttribute('data-theme');
                const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
                
                document.documentElement.setAttribute('data-theme', newTheme);
                localStorage.setItem('admin-theme', newTheme);
                
                // Show notification
                showToast(`Switched to ${newTheme} mode`, 'success');
            });
            
            // Load saved theme
            const savedTheme = localStorage.getItem('admin-theme');
            if (savedTheme) {
                document.documentElement.setAttribute('data-theme', savedTheme);
            }
        }
        
        // Notification Panel
        const notificationBtn = document.getElementById('notificationBtn');
        const notificationPanel = document.getElementById('notificationPanel');
        const notificationClose = document.getElementById('notificationClose');
        
        if (notificationBtn && notificationPanel) {
            notificationBtn.addEventListener('click', function() {
                notificationPanel.classList.toggle('show');
                loadNotifications();
            });
            
            if (notificationClose) {
                notificationClose.addEventListener('click', function() {
                    notificationPanel.classList.remove('show');
                });
            }
            
            // Close panel when clicking outside
            document.addEventListener('click', function(e) {
                if (!notificationPanel.contains(e.target) && !notificationBtn.contains(e.target)) {
                    notificationPanel.classList.remove('show');
                }
            });
        }
        
        // User Dropdown
        const userDropdown = document.getElementById('userDropdown');
        if (userDropdown) {
            const dropdownMenu = userDropdown.querySelector('.dropdown-menu');
            const dropdownArrow = userDropdown.querySelector('.user-dropdown-arrow');
            
            userDropdown.addEventListener('click', function(e) {
                e.stopPropagation();
                dropdownMenu.classList.toggle('show');
                dropdownArrow.style.transform = dropdownMenu.classList.contains('show') 
                    ? 'rotate(180deg)' 
                    : 'rotate(0deg)';
            });
            
            // Close dropdown when clicking outside
            document.addEventListener('click', function() {
                dropdownMenu.classList.remove('show');
                dropdownArrow.style.transform = 'rotate(0deg)';
            });
        }
        
        // Search Functionality
        const searchInput = document.querySelector('.search-input');
        if (searchInput) {
            let searchTimeout;
            
            searchInput.addEventListener('input', function(e) {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    performSearch(e.target.value);
                }, 500);
            });
            
            searchInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    performSearch(e.target.value);
                }
            });
        }
        
        // Load Notifications
        function loadNotifications() {
            const notificationList = document.querySelector('.notification-list');
            if (!notificationList) return;
            
            // Simulated notifications
            const notifications = [
                {
                    id: 1,
                    type: 'flight',
                    title: 'New Flight Added',
                    message: 'Flight AA1234 from Nairobi to Mombasa has been scheduled',
                    time: '5 minutes ago',
                    icon: 'fas fa-plane',
                    read: false
                },
                {
                    id: 2,
                    type: 'booking',
                    title: 'New Booking',
                    message: 'John Doe booked 2 tickets on flight BA456',
                    time: '1 hour ago',
                    icon: 'fas fa-ticket-alt',
                    read: false
                },
                {
                    id: 3,
                    type: 'warning',
                    title: 'System Maintenance',
                    message: 'Scheduled maintenance in 2 hours',
                    time: '2 hours ago',
                    icon: 'fas fa-tools',
                    read: true
                }
            ];
            
            notificationList.innerHTML = notifications.map(notification => `
                <div class="notification-item ${notification.read ? 'read' : 'unread'}" 
                     data-id="${notification.id}">
                    <div class="notification-icon">
                        <i class="${notification.icon}"></i>
                    </div>
                    <div class="notification-content">
                        <div class="notification-title">${notification.title}</div>
                        <div class="notification-message">${notification.message}</div>
                        <div class="notification-time">${notification.time}</div>
                    </div>
                    ${!notification.read ? '<span class="notification-dot"></span>' : ''}
                </div>
            `).join('');
        }
        
        // Perform Search
        function performSearch(query) {
            if (!query.trim()) return;
            
            // Show loading
            showToast('Searching...', 'info');
            
            // In a real application, this would be an AJAX call
            setTimeout(() => {
                showToast(`Found results for "${query}"`, 'success');
            }, 1000);
        }
        
        // Toast Notification System
        function showToast(message, type = 'info') {
            const toast = document.createElement('div');
            toast.className = `toast toast-${type}`;
            toast.innerHTML = `
                <div class="toast-content">
                    <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
                    <span>${message}</span>
                </div>
                <button class="toast-close">
                    <i class="fas fa-times"></i>
                </button>
            `;
            
            document.body.appendChild(toast);
            
            // Animate in
            setTimeout(() => {
                toast.classList.add('show');
            }, 10);
            
            // Auto remove
            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => {
                    toast.remove();
                }, 300);
            }, 5000);
            
            // Close button
            toast.querySelector('.toast-close').addEventListener('click', function() {
                toast.classList.remove('show');
                setTimeout(() => {
                    toast.remove();
                }, 300);
            });
        }
        
        // Initialize
        loadNotifications();
        
        // Add toast styles
        const toastStyles = document.createElement('style');
        toastStyles.textContent = `
            .toast {
                position: fixed;
                bottom: 20px;
                right: 20px;
                background: var(--admin-surface);
                border: 1px solid var(--admin-border);
                border-radius: 12px;
                padding: 1rem 1.5rem;
                display: flex;
                align-items: center;
                gap: 1rem;
                box-shadow: var(--admin-shadow-lg);
                transform: translateX(150%);
                transition: transform 0.3s ease;
                z-index: 9999;
                max-width: 400px;
            }
            
            .toast.show {
                transform: translateX(0);
            }
            
            .toast-content {
                display: flex;
                align-items: center;
                gap: 0.75rem;
                flex: 1;
            }
            
            .toast-success .toast-content i {
                color: var(--admin-success);
            }
            
            .toast-error .toast-content i {
                color: var(--admin-danger);
            }
            
            .toast-info .toast-content i {
                color: var(--admin-info);
            }
            
            .toast-close {
                background: none;
                border: none;
                color: var(--admin-text-secondary);
                cursor: pointer;
                padding: 0.25rem;
                border-radius: 6px;
                transition: all 0.3s ease;
            }
            
            .toast-close:hover {
                background: rgba(239, 71, 111, 0.1);
                color: var(--admin-danger);
            }
            
            .notification-panel {
                position: fixed;
                top: 70px;
                right: 20px;
                width: 350px;
                background: var(--admin-surface);
                border: 1px solid var(--admin-border);
                border-radius: 16px;
                box-shadow: var(--admin-shadow-lg);
                z-index: 999;
                transform: translateY(-20px);
                opacity: 0;
                visibility: hidden;
                transition: all 0.3s ease;
            }
            
            .notification-panel.show {
                transform: translateY(0);
                opacity: 1;
                visibility: visible;
            }
            
            .notification-header {
                padding: 1.25rem;
                border-bottom: 1px solid var(--admin-border);
                display: flex;
                align-items: center;
                justify-content: space-between;
            }
            
            .notification-header h5 {
                margin: 0;
                color: var(--admin-text);
            }
            
            .notification-close {
                background: none;
                border: none;
                color: var(--admin-text-secondary);
                cursor: pointer;
                padding: 0.5rem;
                border-radius: 8px;
                transition: all 0.3s ease;
            }
            
            .notification-close:hover {
                background: rgba(239, 71, 111, 0.1);
                color: var(--admin-danger);
            }
            
            .notification-list {
                max-height: 400px;
                overflow-y: auto;
            }
            
            .notification-item {
                padding: 1rem 1.25rem;
                border-bottom: 1px solid var(--admin-border);
                display: flex;
                gap: 1rem;
                position: relative;
                cursor: pointer;
                transition: all 0.3s ease;
            }
            
            .notification-item:hover {
                background: var(--admin-surface-light);
            }
            
            .notification-item.unread {
                background: rgba(67, 97, 238, 0.05);
            }
            
            .notification-icon {
                width: 40px;
                height: 40px;
                border-radius: 10px;
                background: var(--admin-surface-light);
                display: flex;
                align-items: center;
                justify-content: center;
                color: var(--admin-primary);
            }
            
            .notification-content {
                flex: 1;
            }
            
            .notification-title {
                font-weight: 600;
                color: var(--admin-text);
                margin-bottom: 0.25rem;
            }
            
            .notification-message {
                color: var(--admin-text-secondary);
                font-size: 0.9rem;
                margin-bottom: 0.25rem;
            }
            
            .notification-time {
                font-size: 0.8rem;
                color: var(--admin-text-secondary);
            }
            
            .notification-dot {
                position: absolute;
                top: 1rem;
                right: 1rem;
                width: 8px;
                height: 8px;
                border-radius: 50%;
                background: var(--admin-primary);
            }
        `;
        document.head.appendChild(toastStyles);
    });
    
    // Keyboard Shortcuts
    document.addEventListener('keydown', function(e) {
        // Ctrl/Cmd + K for search
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            const searchInput = document.querySelector('.search-input');
            if (searchInput) {
                searchInput.focus();
                searchInput.select();
            }
        }
        
        // Ctrl/Cmd + / for help
        if ((e.ctrlKey || e.metaKey) && e.key === '/') {
            e.preventDefault();
            window.open('help.php', '_blank');
        }
        
        // Escape to close panels
        if (e.key === 'Escape') {
            document.querySelectorAll('.notification-panel, .dropdown-menu').forEach(el => {
                el.classList.remove('show');
            });
        }
    });
    
    // Performance Monitoring
    window.addEventListener('load', function() {
        // Report page load time
        const perfData = window.performance.timing;
        const loadTime = perfData.loadEventEnd - perfData.navigationStart;
        
        if (loadTime > 3000) {
            console.warn(`Page load took ${loadTime}ms - Consider optimizing`);
        }
    });
</script>
