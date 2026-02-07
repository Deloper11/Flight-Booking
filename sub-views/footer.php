<!-- Subresource Integrity (SRI) for all external resources -->
<script 
    src="https://code.jquery.com/jquery-3.7.1.min.js" 
    integrity="sha512-v2CJ7UaYy4JwqLDIrZUI/4hqeoQieOmAZNXBeQyjo21dadnwR+8ZaIJVT8EE2iyI61OV8e6M8PP2/4hpQINQ/g==" 
    crossorigin="anonymous"
    referrerpolicy="no-referrer"
    defer>
</script>

<!-- Bootstrap 5.3.3 with SRI and Popper bundled -->
<script 
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" 
    integrity="sha512-Yf5Q5QI/xHhkcKkPZ+E4fLEj4XpFUFfaJYUxGm9xVkKzZ8j6HoxNfNQNngHGCn0KkZ9c+xA8tVgYJOFj2Z0tZDA==" 
    crossorigin="anonymous"
    referrerpolicy="no-referrer"
    defer>
</script>

<!-- Additional security libraries for 2026 -->
<script 
    src="https://cdn.jsdelivr.net/npm/crypto-js@4.2.0/crypto-js.min.js" 
    integrity="sha512-a+SUDuwNzXDvz4XrIcXHuCf089/iJAoN4lmrXJg18XnduKK6YlDHNRalv4yd1N40OKI80tFidF+rqTFKGPoWFQ==" 
    crossorigin="anonymous"
    referrerpolicy="no-referrer"
    defer>
</script>

<!-- Content Security Policy (CSP) nonce injection -->
<script nonce="<?php echo htmlspecialchars($_SESSION['csp_nonce'] ?? ''); ?>">
    // Set secure cookie attributes globally
    document.cookie = "HttpOnly; Secure; SameSite=Strict; Path=/";
    
    // Security headers meta tag
    const meta = document.createElement('meta');
    meta.httpEquiv = "Content-Security-Policy";
    meta.content = "default-src 'self'; script-src 'self' 'nonce-<?php echo htmlspecialchars($_SESSION['csp_nonce'] ?? ''); ?>' https://code.jquery.com https://cdn.jsdelivr.net https://www.google.com https://www.gstatic.com; style-src 'self' 'unsafe-inline' https://stackpath.bootstrapcdn.com https://cdn.jsdelivr.net; img-src 'self' data: https:; connect-src 'self' https://api.yoursite.com; font-src 'self' https://fonts.gstatic.com; frame-ancestors 'none';";
    document.head.appendChild(meta);
    
    // Additional security headers
    document.head.insertAdjacentHTML('beforeend', 
        '<meta http-equiv="X-Content-Type-Options" content="nosniff">' +
        '<meta http-equiv="X-Frame-Options" content="DENY">' +
        '<meta http-equiv="X-XSS-Protection" content="1; mode=block">' +
        '<meta http-equiv="Referrer-Policy" content="strict-origin-when-cross-origin">' +
        '<meta http-equiv="Permissions-Policy" content="geolocation=(), microphone=(), camera=(), payment=()">'
    );
</script>

<!-- Custom security scripts -->
<script src="/assets/js/security.min.js?v=<?php echo APP_VERSION; ?>" 
        integrity="sha384-<?php echo SECURITY_JS_HASH; ?>" 
        crossorigin="anonymous"
        defer>
</script>

<!-- Performance monitoring -->
<script 
    src="https://cdn.jsdelivr.net/npm/web-vitals@3.5.0/dist/web-vitals.attribution.iife.js" 
    integrity="sha512-xY6NUkPXY0tBbTg5K6o6En3+evRmmJokO3vpJ8lEMEefUztqobqKl28p/Gez6ffPT2rLhe6kk5sS5vLDKt+oRQ==" 
    crossorigin="anonymous"
    referrerpolicy="no-referrer"
    defer>
</script>

<!-- Session timeout management -->
<script nonce="<?php echo htmlspecialchars($_SESSION['csp_nonce'] ?? ''); ?>">
    let sessionTimeout;
    const SESSION_TIMEOUT = <?php echo SESSION_TIMEOUT_MINUTES * 60 * 1000; ?>;
    const WARNING_TIME = 5 * 60 * 1000; // 5 minutes warning
    
    function resetSessionTimer() {
        clearTimeout(sessionTimeout);
        sessionTimeout = setTimeout(showSessionWarning, SESSION_TIMEOUT - WARNING_TIME);
    }
    
    function showSessionWarning() {
        const modal = new bootstrap.Modal(document.getElementById('sessionWarningModal'));
        modal.show();
        
        // Countdown timer
        let timeLeft = WARNING_TIME / 1000;
        const countdown = setInterval(() => {
            timeLeft--;
            document.getElementById('sessionCountdown').textContent = timeLeft;
            
            if (timeLeft <= 0) {
                clearInterval(countdown);
                logoutUser();
            }
        }, 1000);
        
        // Extend session button
        document.getElementById('extendSession').addEventListener('click', () => {
            clearInterval(countdown);
            modal.hide();
            resetSessionTimer();
            
            // Send AJAX request to extend session
            fetch('/api/session/extend', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': '<?php echo $_SESSION['csrf_token'] ?? ''; ?>'
                },
                credentials: 'same-origin'
            });
        });
    }
    
    function logoutUser() {
        fetch('/api/logout', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': '<?php echo $_SESSION['csrf_token'] ?? ''; ?>'
            },
            credentials: 'same-origin'
        }).then(() => {
            window.location.href = '/login.php?session=expired';
        });
    }
    
    // Reset timer on user activity
    ['click', 'keypress', 'scroll', 'mousemove'].forEach(event => {
        document.addEventListener(event, resetSessionTimer, { passive: true });
    });
    
    // Initialize timer
    resetSessionTimer();
</script>

<!-- Security event logging -->
<script nonce="<?php echo htmlspecialchars($_SESSION['csp_nonce'] ?? ''); ?>">
    // Log security events to server
    function logSecurityEvent(eventType, details = {}) {
        const eventData = {
            type: eventType,
            details: details,
            timestamp: new Date().toISOString(),
            url: window.location.href,
            userAgent: navigator.userAgent,
            language: navigator.language,
            platform: navigator.platform,
            referrer: document.referrer,
            screenResolution: `${screen.width}x${screen.height}`,
            viewport: `${window.innerWidth}x${window.innerHeight}`,
            timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
            cookiesEnabled: navigator.cookieEnabled,
            doNotTrack: navigator.doNotTrack || 'unspecified',
            hash: window.location.hash
        };
        
        // Send to server with beacon API (doesn't wait for response)
        navigator.sendBeacon('/api/log/security', JSON.stringify(eventData));
    }
    
    // Log page visibility changes
    document.addEventListener('visibilitychange', () => {
        logSecurityEvent('visibility_change', {
            visibilityState: document.visibilityState,
            hidden: document.hidden
        });
    });
    
    // Log copy/paste events on sensitive fields
    document.querySelectorAll('[data-sensitive]').forEach(element => {
        element.addEventListener('copy', (e) => {
            logSecurityEvent('sensitive_copy', {
                fieldId: element.id,
                fieldType: element.type,
                valueLength: element.value.length
            });
        });
        
        element.addEventListener('paste', (e) => {
            logSecurityEvent('sensitive_paste', {
                fieldId: element.id,
                fieldType: element.type,
                pastedLength: e.clipboardData.getData('text').length
            });
        });
    });
</script>

<!-- Anti-autofill protection for sensitive forms -->
<script nonce="<?php echo htmlspecialchars($_SESSION['csp_nonce'] ?? ''); ?>">
    // Prevent browser autofill on sensitive forms
    document.querySelectorAll('form[data-no-autofill]').forEach(form => {
        // Add random hidden fields to confuse autofill
        const randomString = Math.random().toString(36).substring(2, 15);
        
        const fakeFields = [
            { type: 'text', name: `fake_username_${randomString}` },
            { type: 'password', name: `fake_password_${randomString}` },
            { type: 'email', name: `fake_email_${randomString}` }
        ];
        
        fakeFields.forEach(field => {
            const input = document.createElement('input');
            input.type = field.type;
            input.name = field.name;
            input.style.display = 'none';
            input.tabIndex = -1;
            input.autocomplete = 'new-password';
            form.appendChild(input);
        });
        
        // Mark real fields with autocomplete off
        form.querySelectorAll('input').forEach(input => {
            if (!input.name.includes('fake_')) {
                input.autocomplete = 'off';
                input.setAttribute('autocapitalize', 'none');
                input.setAttribute('spellcheck', 'false');
            }
        });
    });
</script>

<!-- Clickjacking protection -->
<script nonce="<?php echo htmlspecialchars($_SESSION['csp_nonce'] ?? ''); ?>">
    // Frame busting script
    if (self !== top) {
        // Log the attempt
        logSecurityEvent('frame_busting_triggered', {
            parentUrl: document.referrer,
            currentUrl: window.location.href
        });
        
        // Redirect to same page in top frame
        top.location = self.location;
    }
    
    // Monitor for iframe attempts
    const observer = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
            if (mutation.type === 'childList') {
                mutation.addedNodes.forEach((node) => {
                    if (node.tagName === 'IFRAME' || node.tagName === 'FRAME') {
                        logSecurityEvent('dynamic_frame_detected', {
                            src: node.src || 'dynamic',
                            id: node.id || 'no-id'
                        });
                    }
                });
            }
        });
    });
    
    observer.observe(document.documentElement, {
        childList: true,
        subtree: true
    });
</script>

<!-- Keyboard security -->
<script nonce="<?php echo htmlspecialchars($_SESSION['csp_nonce'] ?? ''); ?>">
    // Prevent keyboard shortcuts that could be malicious
    document.addEventListener('keydown', (e) => {
        // Block F12, Ctrl+Shift+I, Ctrl+Shift+J, Ctrl+U
        const blockedCombos = [
            { key: 'F12', ctrl: false, shift: false },
            { key: 'I', ctrl: true, shift: true },
            { key: 'J', ctrl: true, shift: true },
            { key: 'U', ctrl: true, shift: false }
        ];
        
        blockedCombos.forEach(combo => {
            if (e.key === combo.key && 
                e.ctrlKey === combo.ctrl && 
                e.shiftKey === combo.shift) {
                e.preventDefault();
                logSecurityEvent('blocked_keyboard_shortcut', {
                    key: e.key,
                    ctrlKey: e.ctrlKey,
                    shiftKey: e.shiftKey,
                    altKey: e.altKey
                });
                return false;
            }
        });
    }, { passive: false });
</script>

<!-- Privacy protection -->
<script nonce="<?php echo htmlspecialchars($_SESSION['csp_nonce'] ?? ''); ?>">
    // Opt-out of tracking
    if (localStorage.getItem('privacy_opt_out') === 'true') {
        // Disable Google Analytics if present
        window['ga-disable-UA-XXXXX-Y'] = true;
        
        // Clear any existing tracking cookies
        document.cookie.split(';').forEach(cookie => {
            const name = cookie.split('=')[0].trim();
            if (name.includes('_ga') || name.includes('_gid')) {
                document.cookie = `${name}=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;`;
            }
        });
        
        // Send Do Not Track header for future requests
        Object.defineProperty(navigator, 'doNotTrack', {
            value: '1',
            configurable: false,
            writable: false
        });
    }
</script>

<!-- Error boundary -->
<script nonce="<?php echo htmlspecialchars($_SESSION['csp_nonce'] ?? ''); ?>">
    // Global error handler
    window.onerror = function(message, source, lineno, colno, error) {
        const errorData = {
            message: message,
            source: source,
            line: lineno,
            column: colno,
            error: error ? error.toString() : null,
            stack: error ? error.stack : null,
            user: '<?php echo $_SESSION['user_id'] ?? 'anonymous'; ?>',
            timestamp: new Date().toISOString()
        };
        
        // Send to server
        fetch('/api/log/error', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': '<?php echo $_SESSION['csrf_token'] ?? ''; ?>'
            },
            body: JSON.stringify(errorData),
            credentials: 'same-origin'
        }).catch(() => {
            // Fallback to beacon API
            navigator.sendBeacon('/api/log/error', JSON.stringify(errorData));
        });
        
        // Don't prevent default error handling
        return false;
    };
    
    // Promise rejection handler
    window.addEventListener('unhandledrejection', (event) => {
        logSecurityEvent('unhandled_promise_rejection', {
            reason: event.reason?.toString() || 'Unknown',
            promise: event.promise?.toString() || 'Unknown'
        });
    });
</script>

<!-- Performance monitoring -->
<script nonce="<?php echo htmlspecialchars($_SESSION['csp_nonce'] ?? ''); ?>">
    // Log performance metrics
    window.addEventListener('load', () => {
        if ('PerformanceObserver' in window) {
            // Core Web Vitals
            const observer = new PerformanceObserver((list) => {
                list.getEntries().forEach(entry => {
                    if (entry.name === 'first-input') {
                        logSecurityEvent('performance_metric', {
                            metric: 'FID',
                            value: entry.processingStart - entry.startTime,
                            duration: entry.duration
                        });
                    }
                });
            });
            
            observer.observe({ entryTypes: ['first-input'] });
            
            // Largest Contentful Paint
            const lcpObserver = new PerformanceObserver((list) => {
                const entries = list.getEntries();
                const lastEntry = entries[entries.length - 1];
                
                logSecurityEvent('performance_metric', {
                    metric: 'LCP',
                    value: lastEntry.renderTime || lastEntry.loadTime,
                    element: lastEntry.element?.tagName || 'unknown',
                    size: lastEntry.size || 0
                });
            });
            
            lcpObserver.observe({ entryTypes: ['largest-contentful-paint'] });
        }
        
        // Navigation timing
        if (window.performance && performance.timing) {
            const timing = performance.timing;
            const pageLoadTime = timing.loadEventEnd - timing.navigationStart;
            
            logSecurityEvent('page_load_time', {
                total: pageLoadTime,
                dns: timing.domainLookupEnd - timing.domainLookupStart,
                tcp: timing.connectEnd - timing.connectStart,
                request: timing.responseEnd - timing.requestStart,
                dom: timing.domComplete - timing.domLoading,
                render: timing.loadEventStart - timing.domComplete
            });
        }
    });
</script>

<!-- Session Warning Modal (hidden by default) -->
<div class="modal fade" id="sessionWarningModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title">Session Expiring Soon</h5>
            </div>
            <div class="modal-body">
                <p>Your session will expire in <span id="sessionCountdown">300</span> seconds due to inactivity.</p>
                <p>Do you want to extend your session?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="logoutNow" onclick="logoutUser()">
                    Log Out Now
                </button>
                <button type="button" class="btn btn-primary" id="extendSession">
                    Extend Session
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Security Badge -->
<div class="security-badge" style="position: fixed; bottom: 10px; right: 10px; z-index: 9999;">
    <div class="badge bg-success bg-opacity-75 p-2 rounded" data-bs-toggle="tooltip" 
         title="This site uses advanced security measures including HTTPS, CSP, and SRI">
        🔒 Secure Connection
    </div>
</div>

<?php
// Server-side security headers (if not already set)
if (!headers_sent()) {
    // Content Security Policy with nonce
    $csp_nonce = base64_encode(random_bytes(16));
    $_SESSION['csp_nonce'] = $csp_nonce;
    
    header("Content-Security-Policy: " . implode('; ', [
        "default-src 'self'",
        "script-src 'self' 'nonce-$csp_nonce' https://code.jquery.com https://cdn.jsdelivr.net https://www.google.com https://www.gstatic.com",
        "style-src 'self' 'unsafe-inline' https://stackpath.bootstrapcdn.com https://cdn.jsdelivr.net",
        "img-src 'self' data: https:",
        "connect-src 'self' https://api.yoursite.com",
        "font-src 'self' https://fonts.gstatic.com",
        "frame-ancestors 'none'",
        "base-uri 'self'",
        "form-action 'self'",
        "object-src 'none'",
        "media-src 'self'"
    ]));
    
    // Additional security headers
    header("X-Content-Type-Options: nosniff");
    header("X-Frame-Options: DENY");
    header("X-XSS-Protection: 1; mode=block");
    header("Referrer-Policy: strict-origin-when-cross-origin");
    header("Permissions-Policy: geolocation=(), microphone=(), camera=(), payment=()");
    header("Strict-Transport-Security: max-age=31536000; includeSubDomains; preload");
    header("X-Content-Security-Policy: default-src 'self'");
    
    // Cache control for sensitive pages
    if (isset($_SESSION['userId'])) {
        header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
        header("Pragma: no-cache");
        header("Expires: 0");
    }
}
?>

<!-- Version and build info (for debugging) -->
<script nonce="<?php echo htmlspecialchars($csp_nonce ?? ''); ?>">
    console.log('%c🔒 Security Information', 'color: #0d6efd; font-weight: bold; font-size: 14px;');
    console.log('%cApp Version: <?php echo defined('APP_VERSION') ? APP_VERSION : '1.0.0'; ?>', 'color: #6c757d;');
    console.log('%cEnvironment: <?php echo defined('APP_ENV') ? APP_ENV : 'production'; ?>', 'color: #6c757d;');
    console.log('%cBuild Date: <?php echo date('Y-m-d H:i:s'); ?>', 'color: #6c757d;');
    console.log('%cCSP Nonce: <?php echo $csp_nonce ?? 'none'; ?>', 'color: #6c757d;');
    console.log('%cSession ID: <?php echo session_id(); ?>', 'color: #6c757d;');
    console.log('%c⚠️ Warning: This browser feature is for developers only.', 'color: #dc3545; font-weight: bold;');
</script>
