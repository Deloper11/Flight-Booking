<?php

declare(strict_types=1);

namespace App\Includes\Admin;

use App\Helpers\Database;
use App\Services\FlightStatusService;
use App\Services\NotificationService;
use App\Services\AuditLogger;
use App\Exceptions\AdminException;
use App\Security\CSRFToken;
use App\Security\InputValidator;
use App\Security\RateLimiter;

/**
 * Admin Flight Management Controller - 2026 Edition
 * 
 * Features:
 * - Modern PHP 8.2+ with typed properties
 * - Dependency injection
 * - AI-powered anomaly detection
 * - Real-time notifications
 * - Comprehensive audit logging
 * - Rate limiting for security
 * - Atomic database transactions
 * 
 * @version 2026.1.0
 */
class AdminFlightController
{
    private Database $db;
    private FlightStatusService $flightService;
    private NotificationService $notificationService;
    private AuditLogger $auditLogger;
    private RateLimiter $rateLimiter;
    private array $config;

    public function __construct()
    {
        // Load configuration
        $this->config = require __DIR__ . '/../../config/admin.php';
        
        // Initialize dependencies
        $this->db = Database::getInstance();
        $this->flightService = new FlightStatusService($this->db);
        $this->notificationService = new NotificationService();
        $this->auditLogger = new AuditLogger();
        $this->rateLimiter = new RateLimiter('admin_actions', 100, 3600);
    }

    /**
     * Process flight status updates with AI validation
     * 
     * @throws AdminException
     */
    public function processFlightStatus(): void
    {
        // Start session with secure settings
        $this->startSecureSession();
        
        // Check admin authentication
        $this->validateAdminAccess();
        
        // Validate CSRF token
        CSRFToken::validate();
        
        // Check rate limiting
        $adminId = $_SESSION['adminId'];
        if (!$this->rateLimiter->check($adminId)) {
            $this->auditLogger->logRateLimitExceeded($adminId);
            throw new AdminException('Too many requests. Please wait before trying again.', 429);
        }
        
        // Validate and process request
        $action = $this->getValidatedAction();
        
        try {
            // Start database transaction
            $this->db->beginTransaction();
            
            switch ($action) {
                case 'depart':
                    $this->processDeparture();
                    break;
                    
                case 'issue':
                    $this->processDelayIssue();
                    break;
                    
                case 'resolve_issue':
                    $this->resolveIssue();
                    break;
                    
                case 'arrive':
                    $this->processArrival();
                    break;
                    
                default:
                    throw new AdminException('Invalid action specified', 400);
            }
            
            // Commit transaction
            $this->db->commit();
            
            // Log successful action
            $this->auditLogger->logAction(
                $adminId,
                $action,
                ['flight_id' => $_POST['flight_id'] ?? null],
                true
            );
            
            // Send success response
            $this->sendSuccessResponse($action);
            
        } catch (\Throwable $e) {
            // Rollback transaction on error
            $this->db->rollBack();
            
            // Log error
            $this->auditLogger->logAction(
                $adminId,
                $action,
                ['error' => $e->getMessage()],
                false
            );
            
            // Send error response
            $this->sendErrorResponse($e);
        }
    }

    /**
     * Process flight departure
     * 
     * @throws AdminException
     */
    private function processDeparture(): void
    {
        $flightId = $this->validateFlightId();
        
        // Check if departure is valid using AI prediction
        $prediction = $this->flightService->predictDepartureFeasibility($flightId);
        
        if (!$prediction['feasible']) {
            throw new AdminException(
                "Departure not recommended: " . $prediction['reason'],
                400
            );
        }
        
        // Update flight status to departed
        $result = $this->flightService->updateStatus($flightId, 'departed');
        
        if (!$result) {
            throw new AdminException('Failed to update departure status', 500);
        }
        
        // Send real-time notifications
        $this->notificationService->sendFlightDepartedNotification(
            $flightId,
            $adminId = $_SESSION['adminId']
        );
        
        // Trigger webhook for integrations
        $this->triggerWebhook('flight.departed', [
            'flight_id' => $flightId,
            'timestamp' => time(),
            'admin_id' => $adminId
        ]);
    }

    /**
     * Process flight delay with AI-powered delay analysis
     * 
     * @throws AdminException
     */
    private function processDelayIssue(): void
    {
        $flightId = $this->validateFlightId();
        $issueMinutes = $this->validateIssueMinutes();
        $issueReason = $_POST['issue_reason'] ?? 'Unspecified';
        
        // Analyze delay pattern using AI
        $analysis = $this->flightService->analyzeDelayPattern(
            $flightId,
            $issueMinutes,
            $issueReason
        );
        
        // Check for unusual delay patterns
        if ($analysis['is_anomaly']) {
            $this->notificationService->sendAnomalyAlert([
                'flight_id' => $flightId,
                'delay_minutes' => $issueMinutes,
                'pattern' => $analysis['pattern'],
                'confidence' => $analysis['confidence']
            ]);
        }
        
        // Calculate new times
        $newTimes = $this->calculateDelayedTimes($flightId, $issueMinutes);
        
        // Update flight with delay
        $result = $this->flightService->updateFlightDelay(
            $flightId,
            $issueMinutes,
            $issueReason,
            $newTimes['departure'],
            $newTimes['arrival']
        );
        
        if (!$result) {
            throw new AdminException('Failed to update flight delay', 500);
        }
        
        // Send notifications to affected passengers
        $this->notificationService->sendDelayNotifications(
            $flightId,
            $issueMinutes,
            $issueReason
        );
    }

    /**
     * Resolve flight issue
     * 
     * @throws AdminException
     */
    private function resolveIssue(): void
    {
        $flightId = $this->validateFlightId();
        
        // Update flight status and mark issue as resolved
        $result = $this->flightService->resolveIssue($flightId);
        
        if (!$result) {
            throw new AdminException('Failed to resolve flight issue', 500);
        }
        
        // Send resolution notifications
        $this->notificationService->sendIssueResolvedNotification($flightId);
    }

    /**
     * Process flight arrival
     * 
     * @throws AdminException
     */
    private function processArrival(): void
    {
        $flightId = $this->validateFlightId();
        
        // Validate arrival time consistency
        $validation = $this->flightService->validateArrivalTime($flightId);
        
        if (!$validation['valid']) {
            throw new AdminException(
                "Arrival validation failed: " . $validation['message'],
                400
            );
        }
        
        // Update flight status to arrived
        $result = $this->flightService->updateStatus($flightId, 'arrived');
        
        if (!$result) {
            throw new AdminException('Failed to update arrival status', 500);
        }
        
        // Generate arrival analytics
        $analytics = $this->flightService->generateArrivalAnalytics($flightId);
        
        // Send arrival notifications
        $this->notificationService->sendFlightArrivedNotification(
            $flightId,
            $analytics
        );
        
        // Update passenger loyalty points
        $this->updatePassengerLoyalty($flightId);
    }

    /**
     * Validate admin access and permissions
     * 
     * @throws AdminException
     */
    private function validateAdminAccess(): void
    {
        if (!isset($_SESSION['adminId'])) {
            $this->auditLogger->logUnauthorizedAccess($_SERVER['REMOTE_ADDR']);
            throw new AdminException('Unauthorized access', 401);
        }
        
        // Check if admin has required permissions
        $permissions = $this->flightService->getAdminPermissions($_SESSION['adminId']);
        
        if (!in_array('manage_flights', $permissions, true)) {
            throw new AdminException('Insufficient permissions', 403);
        }
        
        // Check for session hijacking
        if (!$this->validateSessionSecurity()) {
            session_regenerate_id(true);
            throw new AdminException('Security validation failed', 403);
        }
    }

    /**
     * Start secure session with modern settings
     */
    private function startSecureSession(): void
    {
        session_start([
            'cookie_secure' => true,
            'cookie_httponly' => true,
            'cookie_samesite' => 'Strict',
            'use_strict_mode' => true,
            'use_only_cookies' => true,
            'cookie_lifetime' => 7200, // 2 hours
            'gc_maxlifetime' => 7200,
        ]);
    }

    /**
     * Validate session security
     */
    private function validateSessionSecurity(): bool
    {
        if (!isset($_SESSION['created'])) {
            $_SESSION['created'] = time();
        } elseif (time() - $_SESSION['created'] > 1800) {
            // Regenerate session ID every 30 minutes
            session_regenerate_id(true);
            $_SESSION['created'] = time();
        }
        
        // Validate user agent consistency
        if (isset($_SESSION['user_agent'])) {
            if ($_SESSION['user_agent'] !== $_SERVER['HTTP_USER_AGENT']) {
                return false;
            }
        } else {
            $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
        }
        
        // Validate IP address (optional, can be commented for proxy environments)
        // if (isset($_SESSION['ip_address'])) {
        //     if ($_SESSION['ip_address'] !== $_SERVER['REMOTE_ADDR']) {
        //         return false;
        //     }
        // } else {
        //     $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'];
        // }
        
        return true;
    }

    /**
     * Get validated action from POST data
     * 
     * @return string Validated action
     * @throws AdminException
     */
    private function getValidatedAction(): string
    {
        $validator = new InputValidator();
        
        if (isset($_POST['dep_but'])) {
            return 'depart';
        } elseif (isset($_POST['issue_but'])) {
            return 'issue';
        } elseif (isset($_POST['issue_solved_but'])) {
            return 'resolve_issue';
        } elseif (isset($_POST['arr_but'])) {
            return 'arrive';
        } else {
            throw new AdminException('No valid action specified', 400);
        }
    }

    /**
     * Validate and sanitize flight ID
     * 
     * @return int Valid flight ID
     * @throws AdminException
     */
    private function validateFlightId(): int
    {
        if (!isset($_POST['flight_id'])) {
            throw new AdminException('Flight ID is required', 400);
        }
        
        $flightId = filter_var($_POST['flight_id'], FILTER_VALIDATE_INT);
        
        if ($flightId === false || $flightId <= 0) {
            throw new AdminException('Invalid Flight ID', 400);
        }
        
        // Verify flight exists
        if (!$this->flightService->flightExists($flightId)) {
            throw new AdminException('Flight not found', 404);
        }
        
        return $flightId;
    }

    /**
     * Validate and sanitize issue minutes
     * 
     * @return int Valid delay minutes
     * @throws AdminException
     */
    private function validateIssueMinutes(): int
    {
        if (!isset($_POST['issue'])) {
            throw new AdminException('Delay minutes are required', 400);
        }
        
        $minutes = filter_var($_POST['issue'], FILTER_VALIDATE_INT, [
            'options' => [
                'min_range' => 1,
                'max_range' => 1440 // 24 hours max delay
            ]
        ]);
        
        if ($minutes === false) {
            throw new AdminException('Delay must be between 1 and 1440 minutes', 400);
        }
        
        return $minutes;
    }

    /**
     * Calculate new departure and arrival times with delay
     * 
     * @param int $flightId Flight ID
     * @param int $delayMinutes Delay in minutes
     * @return array New departure and arrival times
     * @throws AdminException
     */
    private function calculateDelayedTimes(int $flightId, int $delayMinutes): array
    {
        $flight = $this->flightService->getFlightDetails($flightId);
        
        if (!$flight) {
            throw new AdminException('Flight details not found', 404);
        }
        
        try {
            $departure = new \DateTime($flight['departure']);
            $arrival = new \DateTime($flight['arrival']);
            
            $interval = new \DateInterval("PT{$delayMinutes}M");
            
            $newDeparture = $departure->add($interval);
            $newArrival = $arrival->add($interval);
            
            return [
                'departure' => $newDeparture->format('Y-m-d H:i:s'),
                'arrival' => $newArrival->format('Y-m-d H:i:s')
            ];
            
        } catch (\Exception $e) {
            throw new AdminException('Failed to calculate new flight times: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Update passenger loyalty points after successful arrival
     * 
     * @param int $flightId Flight ID
     */
    private function updatePassengerLoyalty(int $flightId): void
    {
        try {
            $this->flightService->updatePassengerLoyaltyPoints($flightId);
        } catch (\Exception $e) {
            // Log error but don't fail the main operation
            error_log("Failed to update loyalty points for flight {$flightId}: " . $e->getMessage());
        }
    }

    /**
     * Trigger webhook for external integrations
     * 
     * @param string $event Event name
     * @param array $data Event data
     */
    private function triggerWebhook(string $event, array $data): void
    {
        if (!$this->config['webhooks_enabled']) {
            return;
        }
        
        $payload = json_encode([
            'event' => $event,
            'data' => $data,
            'timestamp' => time(),
            'signature' => hash_hmac('sha256', json_encode($data), $this->config['webhook_secret'])
        ]);
        
        $ch = curl_init($this->config['webhook_url']);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_SSL_VERIFYPEER => true
        ]);
        
        // Execute asynchronously
        curl_exec($ch);
        curl_close($ch);
    }

    /**
     * Send success response
     * 
     * @param string $action Performed action
     */
    private function sendSuccessResponse(string $action): void
    {
        $messages = [
            'depart' => 'Flight departed successfully',
            'issue' => 'Flight delay recorded successfully',
            'resolve_issue' => 'Flight issue resolved successfully',
            'arrive' => 'Flight arrived successfully'
        ];
        
        $_SESSION['flash_message'] = [
            'type' => 'success',
            'message' => $messages[$action] ?? 'Action completed successfully',
            'timestamp' => time()
        ];
        
        header('Location: /admin/dashboard.php');
        exit;
    }

    /**
     * Send error response
     * 
     * @param \Throwable $e Exception
     */
    private function sendErrorResponse(\Throwable $e): void
    {
        http_response_code($e->getCode() ?: 500);
        
        if ($this->config['debug_mode']) {
            $_SESSION['flash_message'] = [
                'type' => 'error',
                'message' => $e->getMessage(),
                'details' => $e->getTraceAsString(),
                'timestamp' => time()
            ];
        } else {
            $_SESSION['flash_message'] = [
                'type' => 'error',
                'message' => 'An error occurred while processing your request',
                'timestamp' => time()
            ];
        }
        
        header('Location: /admin/dashboard.php?error=1');
        exit;
    }
}

// Usage in your existing file:
try {
    $controller = new AdminFlightController();
    $controller->processFlightStatus();
} catch (\Throwable $e) {
    // Global error handler
    http_response_code(500);
    
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $_SESSION['flash_message'] = [
        'type' => 'error',
        'message' => 'System error occurred',
        'timestamp' => time()
    ];
    
    header('Location: /admin/login.php');
    exit;
}
