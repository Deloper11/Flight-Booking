<?php

declare(strict_types=1);

namespace App\Includes\Admin;

use App\Helpers\Database;
use App\Services\AirlineService;
use App\Services\ValidationService;
use App\Services\NotificationService;
use App\Services\AuditLogger;
use App\Services\AIService;
use App\Exceptions\AdminException;
use App\Security\CSRFToken;
use App\Security\InputValidator;
use App\Security\RateLimiter;
use App\Models\Airline;
use App\DTO\AirlineDTO;

/**
 * Airline Management Controller - 2026 Edition
 * 
 * Features:
 * - AI-powered airline performance predictions
 * - Blockchain verification for airline data
 * - Real-time market analysis
 * - Sustainability scoring
 * - Smart contract integration
 * - Advanced validation with machine learning
 * 
 * @version 2026.1.0
 */
class AirlineController
{
    private Database $db;
    private AirlineService $airlineService;
    private ValidationService $validationService;
    private NotificationService $notificationService;
    private AuditLogger $auditLogger;
    private AIService $aiService;
    private RateLimiter $rateLimiter;
    private array $config;

    public function __construct()
    {
        // Load configuration
        $this->config = require __DIR__ . '/../../config/airline.php';
        
        // Initialize dependencies
        $this->db = Database::getInstance();
        $this->airlineService = new AirlineService($this->db);
        $this->validationService = new ValidationService();
        $this->notificationService = new NotificationService();
        $this->auditLogger = new AuditLogger();
        $this->aiService = new AIService();
        $this->rateLimiter = new RateLimiter('airline_management', 50, 3600);
        
        // Start secure session
        $this->startSecureSession();
    }

    /**
     * Process airline creation with AI validation
     * 
     * @throws AdminException
     */
    public function createAirline(): void
    {
        try {
            // Check admin authentication
            $this->validateAdminAccess();
            
            // Validate CSRF token
            CSRFToken::validate();
            
            // Check rate limiting
            $adminId = $_SESSION['adminId'];
            if (!$this->rateLimiter->check($adminId)) {
                throw new AdminException('Rate limit exceeded. Please try again later.', 429);
            }
            
            // Validate and sanitize input
            $airlineData = $this->validateAndSanitizeInput();
            
            // AI-powered validation
            $aiValidation = $this->aiService->validateAirlineData($airlineData);
            
            if (!$aiValidation['valid']) {
                throw new AdminException(
                    'AI Validation Failed: ' . $aiValidation['message'],
                    400
                );
            }
            
            // Check for duplicate airline using fuzzy matching
            $duplicateCheck = $this->airlineService->checkForSimilarAirlines(
                $airlineData['name'],
                0.85 // Similarity threshold
            );
            
            if ($duplicateCheck['similarity'] > 0.9) {
                throw new AdminException(
                    'Airline already exists: ' . $duplicateCheck['match'],
                    400
                );
            }
            
            // Generate airline code (IATA/ICAO)
            $airlineCodes = $this->generateAirlineCodes($airlineData['name']);
            
            // Calculate sustainability score
            $sustainabilityScore = $this->calculateSustainabilityScore($airlineData);
            
            // Market analysis prediction
            $marketAnalysis = $this->aiService->analyzeMarketEntry($airlineData);
            
            // Start database transaction
            $this->db->beginTransaction();
            
            // Create airline with additional metadata
            $airlineId = $this->airlineService->createAirline(
                $airlineData,
                $airlineCodes,
                $sustainabilityScore,
                $marketAnalysis,
                $adminId
            );
            
            if (!$airlineId) {
                throw new AdminException('Failed to create airline', 500);
            }
            
            // Generate smart contract for airline (blockchain)
            $contractAddress = $this->generateSmartContract($airlineId, $airlineData);
            
            // Upload airline documentation
            $documentation = $this->handleAirlineDocumentation();
            
            // Create airline fleet prediction
            $fleetPrediction = $this->aiService->predictOptimalFleet($airlineData['seats']);
            
            // Send real-time notifications
            $this->sendCreationNotifications($airlineId, $airlineData);
            
            // Log to blockchain (optional)
            if ($this->config['blockchain_enabled']) {
                $this->logToBlockchain($airlineId, $airlineData);
            }
            
            // Commit transaction
            $this->db->commit();
            
            // Log successful creation
            $this->auditLogger->logAirlineCreation(
                $adminId,
                $airlineId,
                $airlineData,
                $airlineCodes
            );
            
            // Generate airline performance dashboard
            $dashboardData = $this->generatePerformanceDashboard($airlineId);
            
            // Send success response
            $this->sendSuccessResponse($airlineId, $airlineData['name'], $dashboardData);
            
        } catch (AdminException $e) {
            $this->handleError($e);
        } catch (\Throwable $e) {
            $this->handleUnexpectedError($e);
        }
    }

    /**
     * Validate and sanitize input data
     * 
     * @return array Validated airline data
     * @throws AdminException
     */
    private function validateAndSanitizeInput(): array
    {
        $validator = new InputValidator();
        
        // Required fields
        $requiredFields = ['airline', 'seats'];
        foreach ($requiredFields as $field) {
            if (!isset($_POST[$field]) || empty(trim($_POST[$field]))) {
                throw new AdminException("Missing required field: {$field}", 400);
            }
        }
        
        // Airline name validation
        $airlineName = trim($_POST['airline']);
        if (!$validator->validateString($airlineName, 2, 100)) {
            throw new AdminException(
                'Airline name must be between 2 and 100 characters',
                400
            );
        }
        
        // Check for offensive content
        if ($this->aiService->containsOffensiveContent($airlineName)) {
            throw new AdminException('Airline name contains inappropriate content', 400);
        }
        
        // Seats validation
        $seats = filter_var($_POST['seats'], FILTER_VALIDATE_INT, [
            'options' => [
                'min_range' => 10,
                'max_range' => 1000
            ]
        ]);
        
        if ($seats === false) {
            throw new AdminException('Seats must be between 10 and 1000', 400);
        }
        
        // Additional optional fields
        $data = [
            'name' => $airlineName,
            'seats' => $seats,
            'country' => $_POST['country'] ?? null,
            'founded_year' => isset($_POST['founded_year']) ? 
                filter_var($_POST['founded_year'], FILTER_VALIDATE_INT, [
                    'options' => [
                        'min_range' => 1900,
                        'max_range' => date('Y')
                    ]
                ]) : null,
            'website' => isset($_POST['website']) ? 
                filter_var($_POST['website'], FILTER_VALIDATE_URL) : null,
            'hub_airport' => $_POST['hub_airport'] ?? null,
            'alliance' => $_POST['alliance'] ?? null,
            'fleet_type' => $_POST['fleet_type'] ?? null
        ];
        
        // Validate URL if provided
        if ($data['website'] && !$validator->validateUrl($data['website'])) {
            throw new AdminException('Invalid website URL', 400);
        }
        
        // Validate country code
        if ($data['country'] && !$validator->validateCountryCode($data['country'])) {
            throw new AdminException('Invalid country code', 400);
        }
        
        return $data;
    }

    /**
     * Generate IATA and ICAO codes for airline
     * 
     * @param string $airlineName Airline name
     * @return array Generated codes
     */
    private function generateAirlineCodes(string $airlineName): array
    {
        // Generate IATA code (2 letters)
        $iataCode = $this->generateIATACode($airlineName);
        
        // Generate ICAO code (3 letters)
        $icaoCode = $this->generateICAOCode($airlineName);
        
        // Check if codes are unique
        $existingCodes = $this->airlineService->checkExistingCodes($iataCode, $icaoCode);
        
        if ($existingCodes['iata_exists']) {
            $iataCode = $this->generateAlternativeIATACode($airlineName);
        }
        
        if ($existingCodes['icao_exists']) {
            $icaoCode = $this->generateAlternativeICAOCode($airlineName);
        }
        
        return [
            'iata' => $iataCode,
            'icao' => $icaoCode,
            'callsign' => $this->generateCallsign($airlineName)
        ];
    }

    /**
     * Calculate sustainability score based on various factors
     * 
     * @param array $airlineData Airline information
     * @return int Sustainability score (0-100)
     */
    private function calculateSustainabilityScore(array $airlineData): int
    {
        $score = 50; // Base score
        
        // Adjust based on seats (efficiency)
        if ($airlineData['seats'] > 200) {
            $score += 10; // Larger planes are often more efficient per passenger
        }
        
        // Adjust based on fleet type
        if (isset($airlineData['fleet_type'])) {
            $ecoFriendlyTypes = ['electric', 'hydrogen', 'hybrid', 'biofuel'];
            if (in_array(strtolower($airlineData['fleet_type']), $ecoFriendlyTypes)) {
                $score += 20;
            }
        }
        
        // Adjust based on country's environmental policies
        if (isset($airlineData['country'])) {
            $countryScore = $this->getCountryEnvironmentalScore($airlineData['country']);
            $score += $countryScore;
        }
        
        // Use AI to predict carbon footprint
        $carbonPrediction = $this->aiService->predictCarbonFootprint(
            $airlineData['seats'],
            $airlineData['fleet_type'] ?? 'standard'
        );
        
        $score -= ($carbonPrediction['co2_per_passenger'] > 100) ? 15 : 0;
        
        return min(max($score, 0), 100);
    }

    /**
     * Handle airline documentation upload
     * 
     * @return array Documentation metadata
     * @throws AdminException
     */
    private function handleAirlineDocumentation(): array
    {
        $documents = [];
        
        if (!empty($_FILES['documents'])) {
            $uploader = new DocumentUploader();
            
            foreach ($_FILES['documents']['tmp_name'] as $key => $tmpName) {
                if ($_FILES['documents']['error'][$key] === UPLOAD_ERR_OK) {
                    $document = $uploader->upload(
                        $tmpName,
                        $_FILES['documents']['name'][$key],
                        $_FILES['documents']['type'][$key],
                        ['pdf', 'doc', 'docx', 'jpg', 'png']
                    );
                    
                    $documents[] = [
                        'type' => $_POST['document_types'][$key] ?? 'other',
                        'file_path' => $document['path'],
                        'hash' => $document['hash'],
                        'uploaded_at' => date('Y-m-d H:i:s')
                    ];
                }
            }
        }
        
        return $documents;
    }

    /**
     * Generate smart contract for airline on blockchain
     * 
     * @param int $airlineId Airline ID
     * @param array $airlineData Airline information
     * @return string|null Contract address
     */
    private function generateSmartContract(int $airlineId, array $airlineData): ?string
    {
        if (!$this->config['blockchain_enabled']) {
            return null;
        }
        
        try {
            $blockchainService = new BlockchainService();
            
            $contractData = [
                'airline_id' => $airlineId,
                'name' => $airlineData['name'],
                'seats' => $airlineData['seats'],
                'created_at' => date('Y-m-d H:i:s'),
                'created_by' => $_SESSION['adminId'],
                'sustainability_score' => $this->calculateSustainabilityScore($airlineData)
            ];
            
            $contractAddress = $blockchainService->deployAirlineContract($contractData);
            
            // Store contract address in database
            $this->airlineService->updateAirlineContract($airlineId, $contractAddress);
            
            return $contractAddress;
            
        } catch (\Exception $e) {
            // Log error but don't fail the main operation
            error_log("Failed to generate smart contract: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Send notifications for airline creation
     * 
     * @param int $airlineId New airline ID
     * @param array $airlineData Airline information
     */
    private function sendCreationNotifications(int $airlineId, array $airlineData): void
    {
        // Notify admin team
        $this->notificationService->sendAdminNotification(
            'airline_created',
            [
                'airline_id' => $airlineId,
                'name' => $airlineData['name'],
                'admin_id' => $_SESSION['adminId']
            ]
        );
        
        // Notify relevant departments
        $departments = ['operations', 'scheduling', 'maintenance', 'marketing'];
        foreach ($departments as $department) {
            $this->notificationService->sendDepartmentNotification(
                $department,
                'new_airline',
                [
                    'airline_id' => $airlineId,
                    'name' => $airlineData['name'],
                    'seats' => $airlineData['seats']
                ]
            );
        }
        
        // Send to external partners via webhook
        $this->triggerWebhook('airline.created', [
            'airline_id' => $airlineId,
            'data' => $airlineData,
            'timestamp' => time()
        ]);
    }

    /**
     * Log airline creation to blockchain
     * 
     * @param int $airlineId Airline ID
     * @param array $airlineData Airline information
     */
    private function logToBlockchain(int $airlineId, array $airlineData): void
    {
        try {
            $blockchainLogger = new BlockchainLogger();
            
            $blockchainLogger->logEvent(
                'airline_creation',
                [
                    'airline_id' => $airlineId,
                    'name' => $airlineData['name'],
                    'timestamp' => time(),
                    'block' => $blockchainLogger->getCurrentBlockNumber()
                ]
            );
            
        } catch (\Exception $e) {
            error_log("Failed to log to blockchain: " . $e->getMessage());
        }
    }

    /**
     * Generate performance dashboard for new airline
     * 
     * @param int $airlineId Airline ID
     * @return array Dashboard data
     */
    private function generatePerformanceDashboard(int $airlineId): array
    {
        $dashboard = $this->aiService->generateInitialDashboard($airlineId);
        
        // Store dashboard configuration
        $this->airlineService->saveDashboardConfig($airlineId, $dashboard);
        
        return $dashboard;
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
        
        // Check admin permissions
        if (!$this->airlineService->hasPermission($_SESSION['adminId'], 'create_airline')) {
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
        if (session_status() === PHP_SESSION_NONE) {
            session_start([
                'cookie_secure' => true,
                'cookie_httponly' => true,
                'cookie_samesite' => 'Strict',
                'use_strict_mode' => true,
                'use_only_cookies' => true,
                'cookie_lifetime' => 7200,
                'gc_maxlifetime' => 7200,
            ]);
        }
    }

    /**
     * Validate session security
     */
    private function validateSessionSecurity(): bool
    {
        if (!isset($_SESSION['created'])) {
            $_SESSION['created'] = time();
            return true;
        }
        
        // Regenerate session ID every 30 minutes
        if (time() - $_SESSION['created'] > 1800) {
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
        
        return true;
    }

    /**
     * Send success response with dashboard redirect
     * 
     * @param int $airlineId Created airline ID
     * @param string $airlineName Airline name
     * @param array $dashboardData Dashboard configuration
     */
    private function sendSuccessResponse(int $airlineId, string $airlineName, array $dashboardData): void
    {
        // Store success data in session
        $_SESSION['airline_creation_success'] = [
            'airline_id' => $airlineId,
            'name' => $airlineName,
            'dashboard' => $dashboardData,
            'timestamp' => time()
        ];
        
        // Redirect to airline dashboard
        header('Location: /admin/airlines/dashboard.php?id=' . $airlineId);
        exit;
    }

    /**
     * Handle expected errors
     * 
     * @param AdminException $e Exception
     */
    private function handleError(AdminException $e): void
    {
        // Rollback transaction if active
        if ($this->db->inTransaction()) {
            $this->db->rollBack();
        }
        
        // Log error
        $this->auditLogger->logError(
            $_SESSION['adminId'] ?? null,
            'airline_creation',
            $e->getMessage(),
            $e->getCode()
        );
        
        // Store error in session
        $_SESSION['airline_creation_error'] = [
            'message' => $e->getMessage(),
            'code' => $e->getCode(),
            'timestamp' => time()
        ];
        
        // Redirect back with error
        header('Location: /admin/airlines/create.php?error=' . $e->getCode());
        exit;
    }

    /**
     * Handle unexpected errors
     * 
     * @param \Throwable $e Exception
     */
    private function handleUnexpectedError(\Throwable $e): void
    {
        // Rollback transaction if active
        if ($this->db->inTransaction()) {
            $this->db->rollBack();
        }
        
        // Log detailed error
        error_log("Unexpected error in airline creation: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        
        // Store generic error
        $_SESSION['airline_creation_error'] = [
            'message' => 'An unexpected error occurred',
            'code' => 500,
            'timestamp' => time()
        ];
        
        // Redirect to error page
        header('Location: /admin/error.php?code=500');
        exit;
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
        
        // Execute asynchronously
        $ch = curl_init($this->config['webhook_url']);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_SSL_VERIFYPEER => true
        ]);
        
        curl_exec($ch);
        curl_close($ch);
    }
}

// Main execution
try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['air_but'])) {
        $controller = new AirlineController();
        $controller->createAirline();
    } else {
        // Not a POST request or button not clicked
        header('Location: /admin/dashboard.php');
        exit;
    }
} catch (\Throwable $e) {
    // Global error handler
    http_response_code(500);
    
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $_SESSION['global_error'] = [
        'message' => 'System error occurred',
        'timestamp' => time()
    ];
    
    header('Location: /admin/error.php');
    exit;
}
