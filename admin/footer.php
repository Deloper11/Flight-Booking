<!-- Modern Admin Footer 2026 -->
<footer class="admin-footer">
    <!-- Main Footer -->
    <div class="footer-main">
        <div class="footer-content">
            <!-- Quick Links -->
            <div class="footer-section">
                <h5 class="footer-title">Quick Links</h5>
                <ul class="footer-links">
                    <li><a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
                    <li><a href="flightscnt.php"><i class="fas fa-plane"></i> Flights</a></li>
                    <li><a href="bookings.php"><i class="fas fa-ticket-alt"></i> Bookings</a></li>
                    <li><a href="users.php"><i class="fas fa-users"></i> Users</a></li>
                    <li><a href="airlines.php"><i class="fas fa-building"></i> Airlines</a></li>
                    <li><a href="reports.php"><i class="fas fa-chart-bar"></i> Reports</a></li>
                </ul>
            </div>

            <!-- System Info -->
            <div class="footer-section">
                <h5 class="footer-title">System</h5>
                <ul class="footer-links">
                    <li><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
                    <li><a href="logs.php"><i class="fas fa-clipboard-list"></i> Activity Logs</a></li>
                    <li><a href="backup.php"><i class="fas fa-database"></i> Backup & Restore</a></li>
                    <li><a href="api.php"><i class="fas fa-code"></i> API Documentation</a></li>
                    <li><a href="help.php"><i class="fas fa-question-circle"></i> Help Center</a></li>
                    <li><a href="support.php"><i class="fas fa-headset"></i> Support</a></li>
                </ul>
            </div>

            <!-- Legal & Company -->
            <div class="footer-section">
                <h5 class="footer-title">Legal</h5>
                <ul class="footer-links">
                    <li><a href="privacy.php"><i class="fas fa-shield-alt"></i> Privacy Policy</a></li>
                    <li><a href="terms.php"><i class="fas fa-file-contract"></i> Terms of Service</a></li>
                    <li><a href="cookies.php"><i class="fas fa-cookie-bite"></i> Cookie Policy</a></li>
                    <li><a href="security.php"><i class="fas fa-lock"></i> Security</a></li>
                    <li><a href="accessibility.php"><i class="fas fa-universal-access"></i> Accessibility</a></li>
                    <li><a href="compliance.php"><i class="fas fa-gavel"></i> Compliance</a></li>
                </ul>
            </div>

            <!-- Contact & Support -->
            <div class="footer-section">
                <h5 class="footer-title">Contact</h5>
                <div class="contact-info">
                    <p><i class="fas fa-envelope"></i> admin@airlines2026.com</p>
                    <p><i class="fas fa-phone"></i> +254 700 123 456</p>
                    <p><i class="fas fa-map-marker-alt"></i> Nairobi, Kenya</p>
                </div>
                <div class="social-links">
                    <a href="#" class="social-link"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="social-link"><i class="fab fa-linkedin"></i></a>
                    <a href="#" class="social-link"><i class="fab fa-github"></i></a>
                    <a href="#" class="social-link"><i class="fab fa-discord"></i></a>
                </div>
            </div>
        </div>

        <!-- System Status -->
        <div class="system-status">
            <div class="status-indicators">
                <div class="status-item">
                    <span class="status-dot online"></span>
                    <span>System: Online</span>
                </div>
                <div class="status-item">
                    <span class="status-dot warning"></span>
                    <span>API: Rate Limited</span>
                </div>
                <div class="status-item">
                    <span class="status-dot online"></span>
                    <span>Database: Active</span>
                </div>
                <div class="status-item">
                    <span class="status-dot offline"></span>
                    <span>Backup: Scheduled</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer Bottom -->
    <div class="footer-bottom">
        <div class="footer-bottom-content">
            <!-- Copyright -->
            <div class="copyright">
                <p>&copy; 2026 Airlines Management System. All rights reserved. v4.2.1</p>
                <p class="build-info">Build: #2026.02.07 | Last Updated: <?php echo date('F j, Y, g:i a'); ?></p>
            </div>

            <!-- Back to Top -->
            <button class="back-to-top" id="backToTop">
                <i class="fas fa-arrow-up"></i>
            </button>
        </div>
    </div>
</footer>

<!-- Toast Notifications -->
<div class="toast-container" id="toastContainer"></div>

<!-- Loading Overlay -->
<div class="global-loading" id="globalLoading">
    <div class="loading-content">
        <div class="spinner"></div>
        <p>Processing...</p>
    </div>
</div>

<!-- Confirmation Modal -->
<div class="modal-overlay" id="confirmationModal">
    <div class="modal modal-sm">
        <div class="modal-header">
            <h3 class="modal-title">Confirm Action</h3>
            <button type="button" class="modal-close" onclick="closeModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <p id="confirmationMessage">Are you sure you want to perform this action?</p>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
            <button type="button" class="btn btn-danger" id="confirmActionBtn">Confirm</button>
        </div>
    </div>
</div>

<!-- Session Warning Modal -->
<div class="modal-overlay" id="sessionModal">
    <div class="modal modal-sm">
        <div class="modal-header">
            <h3 class="modal-title">Session Expiring Soon</h3>
        </div>
        <div class="modal-body">
            <p>Your session will expire in <span id="sessionTimer">5:00</span> minutes.</p>
            <p class="text-muted">Click "Extend Session" to continue working.</p>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="logout()">Logout</button>
            <button type="button" class="btn btn-primary" onclick="extendSession()">Extend Session</button>
        </div>
    </div>
</div>

<!-- Global Scripts -->
<script>
    // Modern jQuery 2026
    document.addEventListener('DOMContentLoaded', function() {
        // Back to Top Button
        const backToTop = document.getElementById('backToTop');
        if (backToTop) {
            backToTop.addEventListener('click', function() {
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            });

            window.addEventListener('scroll', function() {
                if (window.pageYOffset > 300) {
                    backToTop.classList.add('visible');
                } else {
                    backToTop.classList.remove('visible');
                }
            });
        }

        // Toast Notification System
        window.showToast = function(message, type = 'info', duration = 5000) {
            const toastContainer = document.getElementById('toastContainer');
            const toast = document.createElement('div');
            toast.className = `toast toast-${type}`;
            
            const icons = {
                success: 'fas fa-check-circle',
                error: 'fas fa-exclamation-circle',
                warning: 'fas fa-exclamation-triangle',
                info: 'fas fa-info-circle'
            };

            toast.innerHTML = `
                <div class="toast-icon">
                    <i class="${icons[type] || icons.info}"></i>
                </div>
                <div class="toast-content">
                    <p>${message}</p>
                </div>
                <button class="toast-close">
                    <i class="fas fa-times"></i>
                </button>
            `;

            toastContainer.appendChild(toast);

            // Animate in
            setTimeout(() => {
                toast.classList.add('show');
            }, 10);

            // Auto remove
            const autoRemove = setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => {
                    if (toast.parentNode) {
                        toast.parentNode.removeChild(toast);
                    }
                }, 300);
            }, duration);

            // Close button
            const closeBtn = toast.querySelector('.toast-close');
            closeBtn.addEventListener('click', function() {
                clearTimeout(autoRemove);
                toast.classList.remove('show');
                setTimeout(() => {
                    if (toast.parentNode) {
                        toast.parentNode.removeChild(toast);
                    }
                }, 300);
            });
        };

        // Confirmation Dialog System
        window.confirmAction = function(message, callback) {
            const modal = document.getElementById('confirmationModal');
            const messageEl = document.getElementById('confirmationMessage');
            const confirmBtn = document.getElementById('confirmActionBtn');
            
            messageEl.textContent = message;
            modal.classList.add('active');
            
            const confirmHandler = function() {
                modal.classList.remove('active');
                confirmBtn.removeEventListener('click', confirmHandler);
                if (typeof callback === 'function') {
                    callback();
                }
            };
            
            confirmBtn.addEventListener('click', confirmHandler);
        };

        // Session Management
        let sessionWarningShown = false;
        const sessionModal = document.getElementById('sessionModal');
        const sessionTimer = document.getElementById('sessionTimer');
        
        function checkSession() {
            // Check if session is about to expire (last 5 minutes)
            // You would typically get this from your server-side session management
            const timeLeft = 300; // 5 minutes in seconds
            if (timeLeft < 300 && !sessionWarningShown) {
                sessionWarningShown = true;
                showSessionWarning(timeLeft);
            }
        }
        
        function showSessionWarning(seconds) {
            let timeLeft = seconds;
            const interval = setInterval(() => {
                timeLeft--;
                const minutes = Math.floor(timeLeft / 60);
                const secs = timeLeft % 60;
                sessionTimer.textContent = `${minutes}:${secs.toString().padStart(2, '0')}`;
                
                if (timeLeft <= 0) {
                    clearInterval(interval);
                    sessionModal.classList.remove('active');
                    logout();
                }
            }, 1000);
            
            sessionModal.classList.add('active');
        }
        
        function extendSession() {
            fetch('../helpers/extend_session.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        sessionModal.classList.remove('active');
                        sessionWarningShown = false;
                        showToast('Session extended successfully', 'success');
                    }
                });
        }
        
        function logout() {
            window.location.href = 'logout.php';
        }
        
        // Check session every minute
        setInterval(checkSession, 60000);
        
        // Keyboard Shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl/Cmd + S for save
            if ((e.ctrlKey || e.metaKey) && e.key === 's') {
                e.preventDefault();
                const saveBtn = document.querySelector('[type="submit"]');
                if (saveBtn) {
                    saveBtn.click();
                }
            }
            
            // Ctrl/Cmd + K for search
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                const searchInput = document.querySelector('input[type="search"], .search-input');
                if (searchInput) {
                    searchInput.focus();
                }
            }
            
            // Escape to close modals
            if (e.key === 'Escape') {
                closeModal();
            }
            
            // F1 for help
            if (e.key === 'F1') {
                e.preventDefault();
                window.open('help.php', '_blank');
            }
        });
        
        // Global Loading
        window.showLoading = function() {
            document.getElementById('globalLoading').classList.add('active');
        };
        
        window.hideLoading = function() {
            document.getElementById('globalLoading').classList.remove('active');
        };
        
        // Auto-save forms with data-loss prevention
        const forms = document.querySelectorAll('form[data-autosave]');
        forms.forEach(form => {
            let autoSaveTimer;
            form.addEventListener('input', function() {
                clearTimeout(autoSaveTimer);
                autoSaveTimer = setTimeout(() => {
                    saveFormDraft(form);
                }, 2000);
            });
            
            window.addEventListener('beforeunload', function(e) {
                if (formHasChanges(form)) {
                    e.preventDefault();
                    e.returnValue = 'You have unsaved changes. Are you sure you want to leave?';
                }
            });
        });
        
        function saveFormDraft(form) {
            const formData = new FormData(form);
            const data = Object.fromEntries(formData);
            localStorage.setItem(`draft_${form.id}`, JSON.stringify(data));
        }
        
        function formHasChanges(form) {
            const savedDraft = localStorage.getItem(`draft_${form.id}`);
            if (!savedDraft) return false;
            
            const formData = new FormData(form);
            const currentData = Object.fromEntries(formData);
            const savedData = JSON.parse(savedDraft);
            
            return JSON.stringify(currentData) !== JSON.stringify(savedData);
        }
        
        // Performance Monitoring
        const perfObserver = new PerformanceObserver((list) => {
            for (const entry of list.getEntries()) {
                if (entry.duration > 2000) {
                    console.warn(`Slow operation detected: ${entry.name} took ${entry.duration}ms`);
                }
            }
        });
        
        perfObserver.observe({ entryTypes: ["measure", "resource"] });
    });

    // Error Boundary
    window.addEventListener('error', function(e) {
        console.error('Global error caught:', e.error);
        showToast('An error occurred. Please try again.', 'error');
        return false;
    });

    // AJAX Error Handling
    $(document).ajaxError(function(event, jqxhr, settings, thrownError) {
        if (jqxhr.status === 401) {
            window.location.href = 'login.php?session_expired=true';
        } else if (jqxhr.status === 403) {
            showToast('You do not have permission to perform this action.', 'error');
        } else if (jqxhr.status === 500) {
            showToast('Server error occurred. Please try again later.', 'error');
        }
    });

    // Close Modal Function
    function closeModal() {
        const modals = document.querySelectorAll('.modal-overlay');
        modals.forEach(modal => {
            modal.classList.remove('active');
        });
    }

    // Print Function
    function printContent(selector) {
        const content = document.querySelector(selector);
        const printWindow = window.open('', '_blank');
        printWindow.document.write(`
            <html>
                <head>
                    <title>Print Document</title>
                    <style>
                        body { font-family: Arial, sans-serif; }
                        @media print {
                            .no-print { display: none; }
                        }
                    </style>
                </head>
                <body>
                    ${content.innerHTML}
                </body>
            </html>
        `);
        printWindow.document.close();
        printWindow.print();
    }

    // Export to CSV
    function exportToCSV(data, filename) {
        const csvContent = "data:text/csv;charset=utf-8," 
            + data.map(row => Object.values(row).join(",")).join("\n");
        
        const encodedUri = encodeURI(csvContent);
        const link = document.createElement("a");
        link.setAttribute("href", encodedUri);
        link.setAttribute("download", `${filename}_${new Date().toISOString().split('T')[0]}.csv`);
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    // Dark/Light Mode Toggle
    function toggleTheme() {
        const currentTheme = localStorage.getItem('theme') || 'dark';
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        
        document.documentElement.setAttribute('data-theme', newTheme);
        localStorage.setItem('theme', newTheme);
        
        showToast(`Switched to ${newTheme} mode`, 'success');
    }

    // Initialize theme
    const savedTheme = localStorage.getItem('theme') || 'dark';
    document.documentElement.setAttribute('data-theme', savedTheme);
</script>

<!-- Dependencies -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js" 
        integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" 
        crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@2.11.8/dist/umd/popper.min.js" 
        integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r" 
        crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" 
        integrity="sha384-geWF76RCwLtnZ8qwWowPQNguL3RmwHVBC9FhGdlKrxdiJJigb/j/68SIy3Te4Bkz" 
        crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.2.0/dist/chartjs-plugin-datalabels.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/moment@2.29.4/moment.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/apexcharts@3.43.0/dist/apexcharts.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/axios@1.5.0/dist/axios.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/luxon@3.3.0/build/global/luxon.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/quill@1.3.7/dist/quill.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/clipboard@2.0.11/dist/clipboard.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/validator@13.11.0/validator.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/hotkeys-js@3.10.1/dist/hotkeys.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/simplebar@6.2.5/dist/simplebar.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/countup.js@2.7.0/dist/countUp.umd.js"></script>

<!-- Custom Admin JS -->
<script src="../assets/js/admin.js?v=<?php echo time(); ?>"></script>
<script src="../assets/js/notifications.js?v=<?php echo time(); ?>"></script>
<script src="../assets/js/forms.js?v=<?php echo time(); ?>"></script>
<script src="../assets/js/charts.js?v=<?php echo time(); ?>"></script>
<script src="../assets/js/dashboard.js?v=<?php echo time(); ?>"></script>

<!-- Service Worker for PWA -->
<script>
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('../sw.js')
            .then(registration => {
                console.log('Service Worker registered with scope:', registration.scope);
            })
            .catch(error => {
                console.log('Service Worker registration failed:', error);
            });
    }
</script>

<!-- Analytics -->
<script>
    // Google Analytics 4
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());
    gtag('config', 'G-XXXXXXXXXX');
</script>
<script async src="https://www.googletagmanager.com/gtag/js?id=G-XXXXXXXXXX"></script>

<!-- Error Reporting -->
<script>
    window.onerror = function(msg, url, lineNo, columnNo, error) {
        const errorData = {
            message: msg,
            url: url,
            line: lineNo,
            column: columnNo,
            error: error ? error.stack : null,
            userAgent: navigator.userAgent,
            timestamp: new Date().toISOString()
        };
        
        // Send to error tracking service
        fetch('../helpers/error_log.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(errorData)
        });
        
        return false;
    };
</script>

<style>
    /* Footer Styles */
    .admin-footer {
        background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
        border-top: 1px solid rgba(255, 255, 255, 0.1);
        margin-top: auto;
        position: relative;
    }
    
    .footer-main {
        padding: 3rem 2rem;
        max-width: 1400px;
        margin: 0 auto;
    }
    
    .footer-content {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 3rem;
        margin-bottom: 3rem;
    }
    
    .footer-section {
        color: #cbd5e1;
    }
    
    .footer-title {
        font-family: 'Space Grotesk', sans-serif;
        font-size: 1.25rem;
        font-weight: 600;
        color: white;
        margin-bottom: 1.5rem;
        padding-bottom: 0.75rem;
        border-bottom: 2px solid #4361ee;
        display: inline-block;
    }
    
    .footer-links {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    
    .footer-links li {
        margin-bottom: 0.75rem;
    }
    
    .footer-links a {
        color: #cbd5e1;
        text-decoration: none;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.5rem 0;
    }
    
    .footer-links a:hover {
        color: #4361ee;
        transform: translateX(5px);
    }
    
    .footer-links i {
        width: 20px;
        text-align: center;
    }
    
    .contact-info {
        margin-bottom: 1.5rem;
    }
    
    .contact-info p {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        margin-bottom: 0.75rem;
        color: #cbd5e1;
    }
    
    .social-links {
        display: flex;
        gap: 1rem;
    }
    
    .social-link {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.1);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        text-decoration: none;
        transition: all 0.3s ease;
    }
    
    .social-link:hover {
        background: #4361ee;
        transform: translateY(-3px);
    }
    
    .system-status {
        background: rgba(15, 23, 42, 0.5);
        border-radius: 12px;
        padding: 1.5rem;
        margin-top: 2rem;
    }
    
    .status-indicators {
        display: flex;
        justify-content: center;
        gap: 2rem;
        flex-wrap: wrap;
    }
    
    .status-item {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        color: #cbd5e1;
    }
    
    .status-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
    }
    
    .status-dot.online {
        background: #06d6a0;
        box-shadow: 0 0 10px rgba(6, 214, 160, 0.5);
    }
    
    .status-dot.warning {
        background: #ffd166;
        box-shadow: 0 0 10px rgba(255, 209, 102, 0.5);
    }
    
    .status-dot.offline {
        background: #ef476f;
        box-shadow: 0 0 10px rgba(239, 71, 111, 0.5);
    }
    
    .footer-bottom {
        background: rgba(15, 23, 42, 0.8);
        padding: 1.5rem 2rem;
        border-top: 1px solid rgba(255, 255, 255, 0.05);
    }
    
    .footer-bottom-content {
        max-width: 1400px;
        margin: 0 auto;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 1rem;
    }
    
    .copyright {
        color: #94a3b8;
        font-size: 0.875rem;
    }
    
    .build-info {
        font-size: 0.75rem;
        color: #64748b;
        margin-top: 0.25rem;
    }
    
    .back-to-top {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        background: #4361ee;
        border: none;
        color: white;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s ease;
        opacity: 0;
        visibility: hidden;
    }
    
    .back-to-top.visible {
        opacity: 1;
        visibility: visible;
    }
    
    .back-to-top:hover {
        background: #3a0ca3;
        transform: translateY(-3px);
        box-shadow: 0 10px 20px rgba(67, 97, 238, 0.3);
    }
    
    /* Toast Notifications */
    .toast-container {
        position: fixed;
        bottom: 2rem;
        right: 2rem;
        z-index: 9999;
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }
    
    .toast {
        background: white;
        border-radius: 12px;
        padding: 1rem 1.5rem;
        min-width: 300px;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        display: flex;
        align-items: center;
        gap: 1rem;
        transform: translateX(100%);
        transition: transform 0.3s ease;
        border-left: 4px solid #4361ee;
    }
    
    .toast.show {
        transform: translateX(0);
    }
    
    .toast-success {
        border-left-color: #06d6a0;
    }
    
    .toast-error {
        border-left-color: #ef476f;
    }
    
    .toast-warning {
        border-left-color: #ffd166;
    }
    
    .toast-info {
        border-left-color: #4361ee;
    }
    
    .toast-icon {
        font-size: 1.25rem;
    }
    
    .toast-success .toast-icon {
        color: #06d6a0;
    }
    
    .toast-error .toast-icon {
        color: #ef476f;
    }
    
    .toast-warning .toast-icon {
        color: #ffd166;
    }
    
    .toast-info .toast-icon {
        color: #4361ee;
    }
    
    .toast-content {
        flex: 1;
    }
    
    .toast-content p {
        margin: 0;
        color: #1e293b;
        font-weight: 500;
    }
    
    .toast-close {
        background: none;
        border: none;
        color: #64748b;
        cursor: pointer;
        padding: 0.25rem;
        transition: color 0.3s ease;
    }
    
    .toast-close:hover {
        color: #ef476f;
    }
    
    /* Global Loading */
    .global-loading {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(15, 23, 42, 0.95);
        backdrop-filter: blur(10px);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 99999;
    }
    
    .global-loading.active {
        display: flex;
    }
    
    .loading-content {
        text-align: center;
    }
    
    .loading-content .spinner {
        width: 60px;
        height: 60px;
        border: 4px solid rgba(67, 97, 238, 0.1);
        border-radius: 50%;
        border-top-color: #4361ee;
        animation: spin 1s linear infinite;
        margin: 0 auto 1rem;
    }
    
    .loading-content p {
        color: white;
        font-size: 1rem;
    }
    
    @keyframes spin {
        to { transform: rotate(360deg); }
    }
    
    /* Modals */
    .modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.7);
        backdrop-filter: blur(10px);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 9999;
        padding: 1rem;
    }
    
    .modal-overlay.active {
        display: flex;
    }
    
    .modal {
        background: #1e293b;
        border-radius: 20px;
        border: 1px solid rgba(255, 255, 255, 0.1);
        width: 90%;
        max-width: 500px;
        overflow: hidden;
        animation: modalSlideIn 0.3s ease;
    }
    
    .modal-sm {
        max-width: 400px;
    }
    
    @keyframes modalSlideIn {
        from {
            opacity: 0;
            transform: translateY(-20px) scale(0.95);
        }
        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }
    
    .modal-header {
        padding: 1.5rem;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    
    .modal-title {
        font-size: 1.5rem;
        font-weight: 600;
        color: white;
        margin: 0;
    }
    
    .modal-close {
        background: none;
        border: none;
        color: #94a3b8;
        font-size: 1.25rem;
        cursor: pointer;
        padding: 0.5rem;
        border-radius: 8px;
        transition: all 0.3s ease;
    }
    
    .modal-close:hover {
        background: rgba(239, 71, 111, 0.1);
        color: #ef476f;
    }
    
    .modal-body {
        padding: 1.5rem;
        color: #cbd5e1;
    }
    
    .modal-footer {
        padding: 1.5rem;
        border-top: 1px solid rgba(255, 255, 255, 0.1);
        display: flex;
        justify-content: flex-end;
        gap: 1rem;
    }
    
    /* Theme Support */
    [data-theme="light"] .admin-footer {
        background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
        border-top: 1px solid #cbd5e1;
    }
    
    [data-theme="light"] .footer-title {
        color: #0f172a;
        border-bottom-color: #4361ee;
    }
    
    [data-theme="light"] .footer-links a {
        color: #475569;
    }
    
    [data-theme="light"] .footer-links a:hover {
        color: #4361ee;
    }
    
    [data-theme="light"] .contact-info p {
        color: #475569;
    }
    
    [data-theme="light"] .social-link {
        background: #e2e8f0;
        color: #0f172a;
    }
    
    [data-theme="light"] .system-status {
        background: #f1f5f9;
    }
    
    [data-theme="light"] .status-item {
        color: #475569;
    }
    
    [data-theme="light"] .footer-bottom {
        background: #f1f5f9;
        border-top: 1px solid #cbd5e1;
    }
    
    [data-theme="light"] .copyright {
        color: #64748b;
    }
    
    [data-theme="light"] .modal {
        background: white;
        border: 1px solid #e2e8f0;
    }
    
    [data-theme="light"] .modal-title {
        color: #0f172a;
    }
    
    [data-theme="light"] .modal-body {
        color: #475569;
    }
    
    [data-theme="light"] .modal-close {
        color: #64748b;
    }
    
    [data-theme="light"] .modal-header,
    [data-theme="light"] .modal-footer {
        border-color: #e2e8f0;
    }
    
    [data-theme="light"] .toast {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
    }
    
    [data-theme="light"] .toast-content p {
        color: #0f172a;
    }
    
    /* Responsive Design */
    @media (max-width: 768px) {
        .footer-main {
            padding: 2rem 1rem;
        }
        
        .footer-content {
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 2rem;
        }
        
        .status-indicators {
            flex-direction: column;
            align-items: flex-start;
            gap: 1rem;
        }
        
        .footer-bottom-content {
            flex-direction: column;
            text-align: center;
        }
        
        .back-to-top {
            position: fixed;
            bottom: 1rem;
            right: 1rem;
        }
        
        .toast-container {
            bottom: 1rem;
            right: 1rem;
            left: 1rem;
        }
        
        .toast {
            min-width: auto;
            width: 100%;
        }
    }
    
    @media (max-width: 480px) {
        .footer-content {
            grid-template-columns: 1fr;
        }
        
        .modal {
            width: 95%;
        }
    }
    
    /* Print Styles */
    @media print {
        .admin-footer,
        .toast-container,
        .global-loading,
        .modal-overlay,
        .back-to-top {
            display: none !important;
        }
    }
    
    /* Accessibility */
    @media (prefers-reduced-motion: reduce) {
        * {
            animation-duration: 0.01ms !important;
            animation-iteration-count: 1 !important;
            transition-duration: 0.01ms !important;
        }
    }
    
    /* High Contrast Mode */
    @media (prefers-contrast: high) {
        .admin-footer {
            border-top: 2px solid black;
        }
        
        .footer-links a {
            text-decoration: underline;
        }
        
        .social-link {
            border: 2px solid black;
        }
    }
</style>

</body>
</html>
<?php include 'footer.php'; ?>
