<?php
session_start();
require '../helpers/init_conn_db.php';

// Enable error reporting for development (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't show errors to users

// Set headers for API response
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Adjust for production
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');

// Function to send JSON response
function jsonResponse($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

// Function to log errors
function logError($message, $context = []) {
    $logEntry = date('Y-m-d H:i:s') . " - ERROR: $message";
    if (!empty($context)) {
        $logEntry .= " - Context: " . json_encode($context);
    }
    error_log($logEntry . PHP_EOL, 3, '../logs/error.log');
}

try {
    // Check for admin authentication
    if (!isset($_SESSION['adminId'])) {
        jsonResponse([
            'success' => false,
            'message' => 'Unauthorized access',
            'code' => 'UNAUTHORIZED',
            'timestamp' => date('c')
        ], 401);
    }

    // Check database connection
    if (!$conn) {
        logError('Database connection failed', ['session' => $_SESSION]);
        jsonResponse([
            'success' => false,
            'message' => 'Database connection failed',
            'code' => 'DB_CONNECTION_ERROR',
            'timestamp' => date('c')
        ], 500);
    }

    // Get additional statistics based on query parameters
    $stats_type = $_GET['type'] ?? 'total';
    $timeframe = $_GET['timeframe'] ?? 'all';
    $date_from = $_GET['from'] ?? null;
    $date_to = $_GET['to'] ?? null;

    // Prepare response structure
    $response = [
        'success' => true,
        'data' => [],
        'metadata' => [
            'requested_at' => date('c'),
            'timezone' => date_default_timezone_get(),
            'version' => '2026.1.0'
        ]
    ];

    switch ($stats_type) {
        case 'total':
            // Get total user count
            $sql = "SELECT COUNT(*) as total_users FROM users";
            $stmt = mysqli_prepare($conn, $sql);
            
            if (!$stmt) {
                logError('Failed to prepare total users query', ['sql' => $sql]);
                throw new Exception('Database query preparation failed');
            }
            
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $row = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);
            
            $response['data'] = [
                'total_users' => (int)$row['total_users'],
                'display_text' => number_format($row['total_users']) . ' users'
            ];
            break;

        case 'detailed':
            // Get detailed statistics
            $detailed_stats = [];
            
            // Total users
            $sql_total = "SELECT COUNT(*) as count FROM users";
            $result_total = mysqli_query($conn, $sql_total);
            $detailed_stats['total'] = (int)mysqli_fetch_assoc($result_total)['count'];
            
            // Active users (based on last login)
            $sql_active = "SELECT COUNT(*) as count FROM users WHERE last_login >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
            $result_active = mysqli_query($conn, $sql_active);
            $detailed_stats['active'] = (int)mysqli_fetch_assoc($result_active)['count'];
            
            // New users this month
            $sql_new = "SELECT COUNT(*) as count FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
            $result_new = mysqli_query($conn, $sql_new);
            $detailed_stats['new_this_month'] = (int)mysqli_fetch_assoc($result_new)['count'];
            
            // Verified users
            $sql_verified = "SELECT COUNT(*) as count FROM users WHERE email_verified = 1";
            $result_verified = mysqli_query($conn, $sql_verified);
            $detailed_stats['verified'] = (int)mysqli_fetch_assoc($result_verified)['count'];
            
            // Users by status
            $sql_status = "SELECT status, COUNT(*) as count FROM users GROUP BY status";
            $result_status = mysqli_query($conn, $sql_status);
            $detailed_stats['by_status'] = [];
            while ($status_row = mysqli_fetch_assoc($result_status)) {
                $detailed_stats['by_status'][$status_row['status']] = (int)$status_row['count'];
            }
            
            $response['data'] = $detailed_stats;
            break;

        case 'growth':
            // Get user growth statistics
            $growth_stats = [];
            
            // Monthly growth
            $sql_monthly = "
                SELECT 
                    DATE_FORMAT(created_at, '%Y-%m') as month,
                    COUNT(*) as count
                FROM users 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
                GROUP BY DATE_FORMAT(created_at, '%Y-%m')
                ORDER BY month DESC
            ";
            $result_monthly = mysqli_query($conn, $sql_monthly);
            $growth_stats['monthly'] = [];
            while ($monthly_row = mysqli_fetch_assoc($result_monthly)) {
                $growth_stats['monthly'][] = [
                    'month' => $monthly_row['month'],
                    'count' => (int)$monthly_row['count']
                ];
            }
            
            // Daily signups for last 30 days
            $sql_daily = "
                SELECT 
                    DATE(created_at) as date,
                    COUNT(*) as count
                FROM users 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                GROUP BY DATE(created_at)
                ORDER BY date DESC
            ";
            $result_daily = mysqli_query($conn, $sql_daily);
            $growth_stats['daily'] = [];
            while ($daily_row = mysqli_fetch_assoc($result_daily)) {
                $growth_stats['daily'][] = [
                    'date' => $daily_row['date'],
                    'count' => (int)$daily_row['count']
                ];
            }
            
            $response['data'] = $growth_stats;
            break;

        case 'timeframe':
            // Filter by timeframe
            $timeframe_stats = [];
            
            switch ($timeframe) {
                case 'today':
                    $sql = "SELECT COUNT(*) as count FROM users WHERE DATE(created_at) = CURDATE()";
                    break;
                case 'yesterday':
                    $sql = "SELECT COUNT(*) as count FROM users WHERE DATE(created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
                    break;
                case 'this_week':
                    $sql = "SELECT COUNT(*) as count FROM users WHERE YEARWEEK(created_at, 1) = YEARWEEK(CURDATE(), 1)";
                    break;
                case 'this_month':
                    $sql = "SELECT COUNT(*) as count FROM users WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())";
                    break;
                case 'this_year':
                    $sql = "SELECT COUNT(*) as count FROM users WHERE YEAR(created_at) = YEAR(CURDATE())";
                    break;
                case 'custom':
                    if ($date_from && $date_to) {
                        $sql = "SELECT COUNT(*) as count FROM users WHERE DATE(created_at) BETWEEN '$date_from' AND '$date_to'";
                    } else {
                        jsonResponse([
                            'success' => false,
                            'message' => 'Missing date range parameters for custom timeframe',
                            'code' => 'INVALID_PARAMETERS'
                        ], 400);
                    }
                    break;
                default:
                    $sql = "SELECT COUNT(*) as count FROM users";
            }
            
            $result = mysqli_query($conn, $sql);
            $row = mysqli_fetch_assoc($result);
            
            $timeframe_stats = [
                'timeframe' => $timeframe,
                'count' => (int)$row['count'],
                'from_date' => $date_from,
                'to_date' => $date_to
            ];
            
            $response['data'] = $timeframe_stats;
            break;

        case 'demographics':
            // Get demographic statistics
            $demographics = [];
            
            // By age groups
            $sql_age = "
                SELECT 
                    CASE
                        WHEN TIMESTAMPDIFF(YEAR, dob, CURDATE()) < 18 THEN 'Under 18'
                        WHEN TIMESTAMPDIFF(YEAR, dob, CURDATE()) BETWEEN 18 AND 25 THEN '18-25'
                        WHEN TIMESTAMPDIFF(YEAR, dob, CURDATE()) BETWEEN 26 AND 35 THEN '26-35'
                        WHEN TIMESTAMPDIFF(YEAR, dob, CURDATE()) BETWEEN 36 AND 50 THEN '36-50'
                        WHEN TIMESTAMPDIFF(YEAR, dob, CURDATE()) > 50 THEN '50+'
                        ELSE 'Unknown'
                    END as age_group,
                    COUNT(*) as count
                FROM Passenger_profile 
                GROUP BY age_group
                ORDER BY count DESC
            ";
            $result_age = mysqli_query($conn, $sql_age);
            $demographics['age_groups'] = [];
            while ($age_row = mysqli_fetch_assoc($result_age)) {
                $demographics['age_groups'][] = [
                    'group' => $age_row['age_group'],
                    'count' => (int)$age_row['count']
                ];
            }
            
            // By gender (if available)
            $sql_gender = "
                SELECT 
                    COALESCE(gender, 'Not specified') as gender,
                    COUNT(*) as count
                FROM Passenger_profile 
                GROUP BY gender
            ";
            $result_gender = mysqli_query($conn, $sql_gender);
            $demographics['gender'] = [];
            while ($gender_row = mysqli_fetch_assoc($result_gender)) {
                $demographics['gender'][] = [
                    'gender' => $gender_row['gender'],
                    'count' => (int)$gender_row['count']
                ];
            }
            
            $response['data'] = $demographics;
            break;

        default:
            jsonResponse([
                'success' => false,
                'message' => 'Invalid statistics type requested',
                'code' => 'INVALID_TYPE',
                'available_types' => ['total', 'detailed', 'growth', 'timeframe', 'demographics']
            ], 400);
    }

    // Add cache headers
    header('Cache-Control: private, max-age=300'); // Cache for 5 minutes
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
    
    // Send successful response
    jsonResponse($response);

} catch (Exception $e) {
    logError($e->getMessage(), [
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ]);
    
    jsonResponse([
        'success' => false,
        'message' => 'An unexpected error occurred',
        'code' => 'INTERNAL_ERROR',
        'timestamp' => date('c'),
        'reference_id' => uniqid('ERR_', true)
    ], 500);
} finally {
    // Close database connection if it exists
    if (isset($conn) && $conn) {
        mysqli_close($conn);
    }
}
<?php
session_start();
require '../helpers/init_conn_db.php';

if (!isset($_SESSION['adminId'])) {
    header('Location: login.php');
    exit;
}

$type = $_GET['type'] ?? 'total';
$format = $_GET['format'] ?? 'csv';

// Get data based on type
$data = [];
// ... fetch data similar to psngrcnt.php

if ($format === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="passenger_stats_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    // Write CSV headers and data
    fclose($output);
    
} elseif ($format === 'pdf') {
    // PDF generation using TCPDF or similar
}
