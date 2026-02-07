<?php
declare(strict_types=1);

/**
 * Secure Autoloader with Security Enhancements for 2026
 * 
 * This file includes advanced security measures:
 * - PHP version validation
 * - Extension verification
 * - Composer integrity checking
 * - Environment validation
 * - Security audit logging
 * - Malware scanning
 * 
 * @version 2026.1.0
 * @package SecureFlightBooking
 * @license MIT
 */

// ============================================================================
// SECURITY BOOTSTRAP & VALIDATION LAYER
// ============================================================================

// Prevent direct access to this file
if (basename(__FILE__) === 'autoload.php' && 
    strpos($_SERVER['SCRIPT_FILENAME'] ?? '', 'vendor/autoload.php') !== false) {
    header('HTTP/1.1 403 Forbidden');
    exit('Direct access to autoloader is not permitted.');
}

// Start security timer
$security_start_time = microtime(true);

// ============================================================================
// PHP VERSION & EXTENSION VALIDATION
// ============================================================================

/**
 * Validate PHP version and extensions
 */
function validatePhpEnvironment(): void {
    // Minimum PHP version for 2026
    $requiredPhpVersion = '8.2.0';
    $currentPhpVersion = PHP_VERSION;
    
    if (version_compare($currentPhpVersion, $requiredPhpVersion, '<')) {
        $errorMessage = sprintf(
            'PHP %s or higher is required. Current version: %s. ' .
            'Please upgrade your PHP installation.',
            $requiredPhpVersion,
            $currentPhpVersion
        );
        
        logSecurityEvent('php_version_invalid', $errorMessage);
        
        if (php_sapi_name() === 'cli') {
            fwrite(STDERR, "ERROR: $errorMessage\n");
            exit(1);
        } else {
            header('HTTP/1.1 503 Service Unavailable');
            exit('<h1>Server Configuration Error</h1><p>' . htmlspecialchars($errorMessage) . '</p>');
        }
    }
    
    // Required PHP extensions for 2026 security
    $requiredExtensions = [
        'openssl',      // Cryptography
        'json',         // Data handling
        'mbstring',     // String operations
        'filter',       // Input validation
        'hash',         // Hashing algorithms
        'session',      // Session management
        'pdo',          // Database abstraction
        'curl',         // HTTP requests
        'gd',           // Image processing (if needed)
        'zip',          // Compression (if needed)
        'zlib',         // Compression
        'intl',         // Internationalization
    ];
    
    $missingExtensions = [];
    foreach ($requiredExtensions as $extension) {
        if (!extension_loaded($extension)) {
            $missingExtensions[] = $extension;
        }
    }
    
    if (!empty($missingExtensions)) {
        $errorMessage = sprintf(
            'Required PHP extensions are missing: %s. ' .
            'Please install/enable these extensions.',
            implode(', ', $missingExtensions)
        );
        
        logSecurityEvent('php_extensions_missing', $errorMessage);
        
        if (php_sapi_name() === 'cli') {
            fwrite(STDERR, "ERROR: $errorMessage\n");
            exit(1);
        } else {
            header('HTTP/1.1 503 Service Unavailable');
            exit('<h1>Server Configuration Error</h1><p>' . htmlspecialchars($errorMessage) . '</p>');
        }
    }
    
    // Verify critical PHP configuration
    $requiredConfigs = [
        'allow_url_fopen' => '0',
        'expose_php' => '0',
        'display_errors' => '0',
        'log_errors' => '1',
        'error_log' => '1',
    ];
    
    $configErrors = [];
    foreach ($requiredConfigs as $config => $expected) {
        $current = ini_get($config);
        if ($current !== $expected && $expected === '0' && $current !== '') {
            $configErrors[] = "$config should be $expected, got '$current'";
        }
    }
    
    if (!empty($configErrors)) {
        logSecurityEvent('php_config_warnings', implode('; ', $configErrors));
    }
}

// ============================================================================
// COMPOSER INTEGRITY VALIDATION
// ============================================================================

/**
 * Validate Composer installation integrity
 */
function validateComposerIntegrity(): void {
    $composerLockPath = dirname(__DIR__, 2) . '/composer.lock';
    $vendorPath = __DIR__ . '/..';
    
    // Check if vendor directory exists and is readable
    if (!is_dir($vendorPath) || !is_readable($vendorPath)) {
        logSecurityEvent('vendor_directory_inaccessible', "Vendor directory not accessible: $vendorPath");
        return;
    }
    
    // Check for suspicious files in vendor directory
    $suspiciousPatterns = [
        '*.php' => ['eval(', 'system(', 'exec(', 'passthru(', 'shell_exec(', '`'],
        '*.phar' => 'all',
        '*.exe' => 'all',
        '*.bat' => 'all',
        '*.sh' => 'all',
        '*.py' => 'all',
        '*.js' => ['eval('],
    ];
    
    $suspiciousFilesFound = [];
    
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($vendorPath, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    
    foreach ($iterator as $file) {
        if ($file->isFile()) {
            $filename = $file->getFilename();
            $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            
            foreach ($suspiciousPatterns as $pattern => $checks) {
                if (fnmatch($pattern, $filename) || 
                    ($extension && fnmatch("*.$extension", $filename))) {
                    
                    if ($checks === 'all') {
                        $suspiciousFilesFound[] = $file->getPathname();
                    } elseif (is_array($checks)) {
                        $content = file_get_contents($file->getPathname());
                        foreach ($checks as $check) {
                            if (strpos($content, $check) !== false) {
                                $suspiciousFilesFound[] = $file->getPathname();
                                break;
                            }
                        }
                    }
                }
            }
        }
    }
    
    if (!empty($suspiciousFilesFound)) {
        logSecurityEvent('suspicious_vendor_files', implode(', ', $suspiciousFilesFound));
    }
    
    // Verify composer.lock integrity if exists
    if (file_exists($composerLockPath)) {
        $lockContent = file_get_contents($composerLockPath);
        $lockData = json_decode($lockContent, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            logSecurityEvent('composer_lock_corrupted', 'Invalid JSON in composer.lock');
        } elseif (isset($lockData['_readme'])) {
            // Check for known vulnerabilities in dependencies
            checkDependencyVulnerabilities($lockData);
        }
    }
}

/**
 * Check for known vulnerabilities in dependencies
 */
function checkDependencyVulnerabilities(array $lockData): void {
    $knownVulnerablePackages = [
        'symfony/symfony' => '<4.4.0',
        'laravel/framework' => '<6.0.0',
        'guzzlehttp/guzzle' => '<6.5.0',
        'monolog/monolog' => '<2.0.0',
        'swiftmailer/swiftmailer' => '<6.0.0',
        'twig/twig' => '<3.0.0',
    ];
    
    if (isset($lockData['packages'])) {
        foreach ($lockData['packages'] as $package) {
            $name = $package['name'] ?? '';
            $version = $package['version'] ?? '';
            
            if (isset($knownVulnerablePackages[$name])) {
                $minVersion = $knownVulnerablePackages[$name];
                if (version_compare($version, $minVersion, '<')) {
                    logSecurityEvent('vulnerable_dependency', "$name $version is vulnerable, require $minVersion+");
                }
            }
        }
    }
}

// ============================================================================
// ENVIRONMENT VALIDATION
// ============================================================================

/**
 * Validate application environment
 */
function validateApplicationEnvironment(): void {
    $requiredDirs = [
        dirname(__DIR__, 2) . '/logs',
        dirname(__DIR__, 2) . '/cache',
        dirname(__DIR__, 2) . '/uploads',
        dirname(__DIR__, 2) . '/config',
    ];
    
    foreach ($requiredDirs as $dir) {
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
            logSecurityEvent('directory_created', "Created directory: $dir");
        }
        
        // Set directory permissions securely
        if (is_dir($dir)) {
            $perms = fileperms($dir) & 0777;
            if ($perms !== 0755) {
                @chmod($dir, 0755);
            }
        }
    }
    
    // Validate writable directories
    $writableDirs = [
        dirname(__DIR__, 2) . '/logs',
        dirname(__DIR__, 2) . '/cache',
        dirname(__DIR__, 2) . '/uploads',
    ];
    
    foreach ($writableDirs as $dir) {
        if (is_dir($dir) && !is_writable($dir)) {
            logSecurityEvent('directory_not_writable', "Directory not writable: $dir");
        }
    }
    
    // Check for development files in production
    if (getenv('APP_ENV') === 'production' || 
        (!getenv('APP_ENV') && file_exists(dirname(__DIR__, 2) . '/.env.local'))) {
        
        $developmentFiles = [
            '.env.local',
            'docker-compose.yml',
            'Vagrantfile',
            'composer-dev.json',
            'webpack.config.js',
            'package.json',
        ];
        
        foreach ($developmentFiles as $file) {
            $filePath = dirname(__DIR__, 2) . '/' . $file;
            if (file_exists($filePath)) {
                logSecurityEvent('development_file_in_production', $filePath);
            }
        }
    }
}

// ============================================================================
// SECURITY LOGGING
// ============================================================================

/**
 * Log security events
 */
function logSecurityEvent(string $event, string $message): void {
    $logDir = dirname(__DIR__, 2) . '/logs';
    $logFile = $logDir . '/security_autoload.log';
    
    // Ensure log directory exists
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'CLI';
    $uri = $_SERVER['REQUEST_URI'] ?? 'CLI';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'CLI';
    
    $logEntry = sprintf(
        "[%s] [%s] [%s] [%s] %s: %s\n",
        $timestamp,
        $ip,
        substr($userAgent, 0, 100),
        $uri,
        $event,
        $message
    );
    
    // Write to log file
    if (is_writable($logDir)) {
        file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }
    
    // Send critical events to syslog
    if (in_array($event, ['php_version_invalid', 'php_extensions_missing', 'vulnerable_dependency'])) {
        openlog('secure_autoload', LOG_PID | LOG_PERROR, LOG_LOCAL0);
        syslog(LOG_WARNING, "Security Event: $event - $message");
        closelog();
    }
}

// ============================================================================
// PERFORMANCE MONITORING
// ============================================================================

/**
 * Monitor autoload performance
 */
function monitorPerformance(float $startTime): void {
    $loadTime = microtime(true) - $startTime;
    
    // Log if autoload takes too long
    if ($loadTime > 0.5) { // 500ms threshold
        logSecurityEvent('autoload_slow', sprintf('Autoload took %.3f seconds', $loadTime));
        
        // Log memory usage
        $memory = memory_get_usage(true) / 1024 / 1024; // MB
        logSecurityEvent('memory_usage', sprintf('Memory: %.2f MB', $memory));
    }
    
    // Record peak memory
    register_shutdown_function(function() {
        $peakMemory = memory_get_peak_usage(true) / 1024 / 1024;
        if ($peakMemory > 100) { // 100MB threshold
            logSecurityEvent('high_memory_usage', sprintf('Peak memory: %.2f MB', $peakMemory));
        }
    });
}

// ============================================================================
// EXECUTION SECURITY CONTROLS
// ============================================================================

/**
 * Apply execution security controls
 */
function applySecurityControls(): void {
    // Disable dangerous functions in production
    if (getenv('APP_ENV') === 'production') {
        $dangerousFunctions = [
            'eval',
            'exec',
            'system',
            'passthru',
            'shell_exec',
            'proc_open',
            'popen',
            'pcntl_exec',
            'assert',
            'create_function',
        ];
        
        foreach ($dangerousFunctions as $func) {
            if (function_exists($func)) {
                ini_set('disable_functions', ini_get('disable_functions') . ",$func");
            }
        }
    }
    
    // Set secure PHP.ini values
    $secureConfigs = [
        'session.cookie_secure' => '1',
        'session.cookie_httponly' => '1',
        'session.cookie_samesite' => 'Strict',
        'session.use_strict_mode' => '1',
        'session.use_only_cookies' => '1',
        'session.use_trans_sid' => '0',
        'session.cache_limiter' => 'nocache',
        'session.sid_length' => '256',
        'session.sid_bits_per_character' => '6',
        'session.hash_function' => 'sha256',
    ];
    
    foreach ($secureConfigs as $key => $value) {
        if (ini_get($key) !== $value) {
            ini_set($key, $value);
        }
    }
    
    // Register shutdown function for error handling
    register_shutdown_function(function() {
        $error = error_get_last();
        if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            logSecurityEvent('fatal_error', sprintf(
                '%s in %s on line %d',
                $error['message'],
                $error['file'],
                $error['line']
            ));
        }
    });
}

// ============================================================================
// MAIN EXECUTION
// ============================================================================

try {
    // Apply security controls first
    applySecurityControls();
    
    // Validate PHP environment
    validatePhpEnvironment();
    
    // Validate application environment
    validateApplicationEnvironment();
    
    // Validate Composer integrity
    validateComposerIntegrity();
    
    // Load Composer autoloader
    $composerAutoloader = __DIR__ . '/composer/autoload_real.php';
    
    if (!file_exists($composerAutoloader)) {
        $errorMessage = 'Composer autoloader not found. Run `composer install` to install dependencies.';
        logSecurityEvent('composer_autoload_missing', $errorMessage);
        
        if (php_sapi_name() === 'cli') {
            fwrite(STDERR, "ERROR: $errorMessage\n");
            exit(1);
        } else {
            header('HTTP/1.1 503 Service Unavailable');
            exit('<h1>Application Error</h1><p>' . htmlspecialchars($errorMessage) . '</p>');
        }
    }
    
    // Verify composer autoloader integrity
    $composerContent = file_get_contents($composerAutoloader);
    if (strpos($composerContent, 'eval(') !== false || 
        strpos($composerContent, 'base64_decode(') !== false) {
        logSecurityEvent('suspicious_autoloader_content', 'Potentially malicious content in autoloader');
    }
    
    // Include the Composer autoloader
    require_once $composerAutoloader;
    
    // Get the loader
    $loader = ComposerAutoloaderInit58c4f24ad8d9b882f33f784c0f510064::getLoader();
    
    // Monitor performance
    monitorPerformance($security_start_time);
    
    // Log successful autoload
    logSecurityEvent('autoload_success', 'Autoloader initialized successfully');
    
    // Register custom autoloaders for security
    registerSecurityAutoloaders($loader);
    
    return $loader;
    
} catch (Throwable $e) {
    // Catch any errors during autoload initialization
    logSecurityEvent('autoload_fatal_error', sprintf(
        '%s: %s in %s on line %d',
        get_class($e),
        $e->getMessage(),
        $e->getFile(),
        $e->getLine()
    ));
    
    if (php_sapi_name() === 'cli') {
        fwrite(STDERR, "FATAL ERROR during autoload: " . $e->getMessage() . "\n");
        exit(1);
    } else {
        header('HTTP/1.1 503 Service Unavailable');
        
        if (getenv('APP_ENV') === 'development') {
            exit('<h1>Autoload Error</h1><pre>' . htmlspecialchars($e->getMessage()) . '</pre>');
        } else {
            exit('<h1>Application Error</h1><p>Please try again later or contact support.</p>');
        }
    }
}

// ============================================================================
// SECURITY AUTOLOADER REGISTRATION
// ============================================================================

/**
 * Register security-related autoloaders
 */
function registerSecurityAutoloaders(Composer\Autoload\ClassLoader $loader): void {
    // Register security namespace
    $loader->setPsr4('Security\\', [
        dirname(__DIR__, 2) . '/src/Security/',
    ]);
    
    // Register additional security classes
    $securityClasses = [
        'Security\\Encryption\\AES256' => dirname(__DIR__, 2) . '/src/Security/Encryption/AES256.php',
        'Security\\Validation\\InputValidator' => dirname(__DIR__, 2) . '/src/Security/Validation/InputValidator.php',
        'Security\\Audit\\Logger' => dirname(__DIR__, 2) . '/src/Security/Audit/Logger.php',
        'Security\\CSRF\\TokenManager' => dirname(__DIR__, 2) . '/src/Security/CSRF/TokenManager.php',
        'Security\\RateLimit\\Limiter' => dirname(__DIR__, 2) . '/src/Security/RateLimit/Limiter.php',
    ];
    
    foreach ($securityClasses as $class => $file) {
        if (file_exists($file)) {
            $loader->addClassMap([$class => $file]);
        }
    }
    
    // Register fallback autoloader for security classes
    spl_autoload_register(function($class) {
        $securityPrefix = 'Security\\';
        if (strpos($class, $securityPrefix) === 0) {
            $relativeClass = substr($class, strlen($securityPrefix));
            $file = dirname(__DIR__, 2) . '/src/Security/' . str_replace('\\', '/', $relativeClass) . '.php';
            
            if (file_exists($file)) {
                require $file;
                
                // Verify class exists after loading
                if (!class_exists($class, false) && !interface_exists($class, false) && !trait_exists($class, false)) {
                    logSecurityEvent('security_class_not_found', "Security class not found after autoload: $class");
                }
            }
        }
    }, true, true);
}

// ============================================================================
// ENVIRONMENT DETECTION
// ============================================================================

/**
 * Detect and set environment
 */
function detectEnvironment(): void {
    // Check for environment file
    $envFiles = [
        '.env.production',
        '.env.staging',
        '.env.development',
        '.env.local',
        '.env',
    ];
    
    $envLoaded = false;
    foreach ($envFiles as $envFile) {
        $envPath = dirname(__DIR__, 2) . '/' . $envFile;
        if (file_exists($envPath)) {
            // In production, we'd use vlucas/phpdotenv or similar
            // For now, just set APP_ENV from filename
            $envName = str_replace('.env.', '', $envFile);
            if ($envName === $envFile) {
                $envName = 'production'; // Default .env file
            }
            
            if (!getenv('APP_ENV')) {
                putenv("APP_ENV=$envName");
                $_ENV['APP_ENV'] = $envName;
                $_SERVER['APP_ENV'] = $envName;
            }
            
            $envLoaded = true;
            break;
        }
    }
    
    if (!$envLoaded && !getenv('APP_ENV')) {
        // Default to production for security
        putenv('APP_ENV=production');
        $_ENV['APP_ENV'] = 'production';
        $_SERVER['APP_ENV'] = 'production';
        
        logSecurityEvent('environment_not_set', 'No environment file found, defaulting to production');
    }
}

// Initialize environment detection
detectEnvironment();

// ============================================================================
// ADDITIONAL SECURITY CHECKS
// ============================================================================

/**
 * Check for common vulnerabilities
 */
function performSecurityScan(): void {
    // Scan for common web vulnerabilities
    $checks = [
        'directory_traversal' => ['../', '..\\', '%2e%2e%2f'],
        'xss_attempt' => ['<script>', 'javascript:', 'onload=', 'onerror='],
        'sql_injection' => ["' OR '1'='1", 'UNION SELECT', 'DROP TABLE', 'INSERT INTO'],
        'command_injection' => [';', '|', '`', '$('],
        'file_inclusion' => ['/etc/passwd', 'C:\\Windows\\', '../../'],
    ];
    
    $foundVulnerabilities = [];
    
    // Check GET parameters
    foreach ($_GET as $key => $value) {
        foreach ($checks as $type => $patterns) {
            foreach ($patterns as $pattern) {
                if (stripos($value, $pattern) !== false) {
                    $foundVulnerabilities[] = "$type detected in GET parameter '$key'";
                }
            }
        }
    }
    
    // Check POST parameters
    foreach ($_POST as $key => $value) {
        foreach ($checks as $type => $patterns) {
            foreach ($patterns as $pattern) {
                if (stripos($value, $pattern) !== false) {
                    $foundVulnerabilities[] = "$type detected in POST parameter '$key'";
                }
            }
        }
    }
    
    // Log any found vulnerabilities
    if (!empty($foundVulnerabilities)) {
        logSecurityEvent('security_scan_detected', implode('; ', $foundVulnerabilities));
    }
}

// Perform security scan (only in web context)
if (php_sapi_name() !== 'cli') {
    performSecurityScan();
}

// ============================================================================
// FINAL INITIALIZATION
// ============================================================================

/**
 * Final initialization tasks
 */
function finalInitialization(): void {
    // Set timezone to UTC for consistency
    if (!date_default_timezone_get()) {
        date_default_timezone_set('UTC');
    }
    
    // Set locale for internationalization
    setlocale(LC_ALL, 'en_US.UTF-8');
    
    // Define some security constants if not already defined
    if (!defined('SECURITY_AUTOLOAD_INITIALIZED')) {
        define('SECURITY_AUTOLOAD_INITIALIZED', true);
        define('SECURITY_AUTOLOAD_VERSION', '2026.1.0');
        define('SECURITY_AUTOLOAD_TIMESTAMP', time());
    }
    
    // Initialize encryption if needed
    if (extension_loaded('openssl')) {
        // Ensure we have a strong random source
        if (!function_exists('random_bytes')) {
            require_once __DIR__ . '/composer/paragonie/random_compat/lib/random.php';
        }
    }
}

// Run final initialization
finalInitialization();;
