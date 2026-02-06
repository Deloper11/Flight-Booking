<?php
/**
 * Revenue & Analytics API Endpoint
 * Version: 2026.1.0
 * Modernized with real-time analytics, multi-dimensional insights, and enterprise-grade features
 */

declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// Security headers
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: geolocation=(), microphone=(), camera=()');

// CORS headers
header('Access-Control-Allow-Origin: ' . ($_SERVER['HTTP_ORIGIN'] ?? '*'));
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-API-Key, X-Request-ID');
header('Access-Control-Max-Age: 86400');
header('Access-Control-Allow-Credentials: true');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit();
}

// Session with enhanced security
session_set_cookie_params([
    'lifetime' => 86400,
    'path' => '/',
    'domain' => $_SERVER['HTTP_HOST'] ?? '',
    'secure' => true,
    'httponly' => true,
    'samesite' => 'Strict'
]);
session_start();

// Include dependencies
require_once '../helpers/init_conn_db.php';
require_once '../helpers/security.php';
require_once '../helpers/analytics.php';

// Check database connection
if (!$conn) {
    http_response_code(503);
    echo json_encode([
        'status' => 'error',
        'code' => 'DB_UNAVAILABLE',
        'message' => 'Database service temporarily unavailable',
        'timestamp' => time(),
        'correlation_id' => uniqid('corr_', true),
        'data' => null
    ]);
    exit();
}

// Authentication & Authorization Middleware
function authenticate() {
    // API Key authentication
    $apiKey = $_SERVER['HTTP_X_API_KEY'] ?? $_GET['api_key'] ?? null;
    
    // Session authentication
    $isAuthenticated = isset($_SESSION['adminId']) || validateApiKey($apiKey);
    
    if (!$isAuthenticated) {
        http_response_code(401);
        echo json_encode([
            'status' => 'error',
            'code' => 'UNAUTHORIZED',
            'message' => 'Valid authentication required',
            'timestamp' => time(),
            'data' => null
        ]);
        exit();
    }
    
    // Rate limiting
    rateLimit('revenue_api', 100, 60); // 100 requests per minute
}

// Rate Limiting Middleware
function rateLimit(string $key, int $limit, int $window): void {
    $ip = getClientIP();
    $cacheKey = "ratelimit_{$key}_{$ip}";
    
    if (!isset($_SESSION[$cacheKey])) {
        $_SESSION[$cacheKey] = [
            'count' => 1,
            'timestamp' => time(),
            'window' => $window
        ];
    } else {
        $current = $_SESSION[$cacheKey];
        
        if (time() - $current['timestamp'] > $window) {
            $_SESSION[$cacheKey] = [
                'count' => 1,
                'timestamp' => time(),
                'window' => $window
            ];
        } else {
            $_SESSION[$cacheKey]['count']++;
            
            if ($_SESSION[$cacheKey]['count'] > $limit) {
                http_response_code(429);
                header('Retry-After: ' . ($window - (time() - $current['timestamp'])));
                echo json_encode([
                    'status' => 'error',
                    'code' => 'RATE_LIMIT_EXCEEDED',
                    'message' => 'Too many requests. Please try again later.',
                    'retry_after' => $window - (time() - $current['timestamp']),
                    'timestamp' => time(),
                    'data' => null
                ]);
                exit();
            }
        }
    }
}

// Validate API Key
function validateApiKey(?string $apiKey): bool {
    if (!$apiKey) return false;
    
    $validKeys = [
        'admin_' . hash('sha256', 'revenue_analytics_2026'),
        'dashboard_' . hash('sha256', 'revenue_dashboard_2026'),
        'api_' . hash('sha256', 'revenue_api_2026')
    ];
    
    return in_array($apiKey, $validKeys, true);
}

// Get client IP with proxy support
function getClientIP(): string {
    $headers = [
        'HTTP_CLIENT_IP',
        'HTTP_X_FORWARDED_FOR',
        'HTTP_X_FORWARDED',
        'HTTP_X_CLUSTER_CLIENT_IP',
        'HTTP_FORWARDED_FOR',
        'HTTP_FORWARDED',
        'REMOTE_ADDR'
    ];
    
    foreach ($headers as $header) {
        if (isset($_SERVER[$header])) {
            foreach (explode(',', $_SERVER[$header]) as $ip) {
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
    }
    
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

// Get Revenue Analytics
function getRevenueAnalytics($conn, array $params = []): array {
    $cacheKey = 'revenue_analytics_' . md5(serialize($params));
    $cacheDuration = 300; // 5 minutes
    
    // Check cache (Redis in production)
    if (isset($_SESSION[$cacheKey]) && 
        (time() - $_SESSION[$cacheKey]['timestamp']) < $cacheDuration) {
        return $_SESSION[$cacheKey]['data'];
    }
    
    try {
        // Set timezone for date calculations
        date_default_timezone_set('UTC');
        
        // Get base statistics
        $stats = getBaseRevenueStats($conn);
        
        // Get time-based analytics
        $timeAnalytics = getTimeBasedAnalytics($conn, $params);
        
        // Get predictive analytics
        $predictiveAnalytics = getPredictiveAnalytics($conn);
        
        // Get comparative analytics
        $comparativeAnalytics = getComparativeAnalytics($conn, $params);
        
        // Compile response
        $response = [
            'metadata' => [
                'version' => '2026.1.0',
                'generated_at' => date('c'),
                'currency' => 'KES',
                'timezone' => 'UTC',
                'cache_status' => 'MISS',
                'request_id' => uniqid('req_', true)
            ],
            'summary' => $stats,
            'time_series' => $timeAnalytics,
            'predictive_insights' => $predictiveAnalytics,
            'comparative_analysis' => $comparativeAnalytics,
            'anomalies' => detectAnomalies($conn),
            'recommendations' => generateRecommendations($stats, $timeAnalytics)
        ];
        
        // Cache the response
        $_SESSION[$cacheKey] = [
            'data' => $response,
            'timestamp' => time()
        ];
        
        return $response;
        
    } catch (Exception $e) {
        error_log('Revenue analytics error: ' . $e->getMessage());
        
        // Fallback to minimal data
        return [
            'metadata' => [
                'version' => '2026.1.0',
                'generated_at' => date('c'),
                'error' => 'Partial data available',
                'cache_status' => 'ERROR'
            ],
            'summary' => getMinimalRevenueStats($conn),
            'time_series' => [],
            'predictive_insights' => [],
            'comparative_analysis' => [],
            'anomalies' => [],
            'recommendations' => []
        ];
    }
}

// Get Base Revenue Statistics
function getBaseRevenueStats($conn): array {
    $stats = [];
    
    // Total Revenue
    $sql = "SELECT 
                COALESCE(SUM(cost), 0) as total_revenue,
                COUNT(*) as total_tickets,
                COALESCE(AVG(cost), 0) as average_ticket_value,
                COALESCE(MIN(cost), 0) as min_ticket_value,
                COALESCE(MAX(cost), 0) as max_ticket_value,
                COALESCE(STDDEV(cost), 0) as revenue_std_dev
            FROM ticket";
    
    $stmt = prepareAndExecute($conn, $sql);
    $stats['overall'] = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Revenue by Class
    $sql = "SELECT 
                class,
                COALESCE(SUM(cost), 0) as revenue,
                COUNT(*) as ticket_count,
                COALESCE(AVG(cost), 0) as avg_value
            FROM ticket 
            GROUP BY class 
            ORDER BY revenue DESC";
    
    $stmt = prepareAndExecute($conn, $sql);
    $stats['by_class'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Revenue by Airline
    $sql = "SELECT 
                f.airline,
                COALESCE(SUM(t.cost), 0) as revenue,
                COUNT(*) as ticket_count,
                COALESCE(AVG(t.cost), 0) as avg_ticket_value
            FROM ticket t
            JOIN Flight f ON t.flight_id = f.flight_id
            GROUP BY f.airline 
            ORDER BY revenue DESC 
            LIMIT 10";
    
    $stmt = prepareAndExecute($conn, $sql);
    $stats['by_airline'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Revenue by Route
    $sql = "SELECT 
                CONCAT(f.source, ' → ', f.Destination) as route,
                COALESCE(SUM(t.cost), 0) as revenue,
                COUNT(*) as ticket_count,
                COALESCE(AVG(t.cost), 0) as avg_ticket_value
            FROM ticket t
            JOIN Flight f ON t.flight_id = f.flight_id
            GROUP BY f.source, f.Destination 
            ORDER BY revenue DESC 
            LIMIT 10";
    
    $stmt = prepareAndExecute($conn, $sql);
    $stats['by_route'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Today's Revenue
    $sql = "SELECT 
                COALESCE(SUM(cost), 0) as revenue,
                COUNT(*) as ticket_count
            FROM ticket 
            WHERE DATE(booking_date) = CURDATE()";
    
    $stmt = prepareAndExecute($conn, $sql);
    $stats['today'] = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // This Month's Revenue
    $sql = "SELECT 
                COALESCE(SUM(cost), 0) as revenue,
                COUNT(*) as ticket_count,
                COALESCE(AVG(cost), 0) as avg_ticket_value
            FROM ticket 
            WHERE YEAR(booking_date) = YEAR(CURDATE()) 
            AND MONTH(booking_date) = MONTH(CURDATE())";
    
    $stmt = prepareAndExecute($conn, $sql);
    $stats['this_month'] = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Year-to-Date Revenue
    $sql = "SELECT 
                COALESCE(SUM(cost), 0) as revenue,
                COUNT(*) as ticket_count
            FROM ticket 
            WHERE YEAR(booking_date) = YEAR(CURDATE())";
    
    $stmt = prepareAndExecute($conn, $sql);
    $stats['ytd'] = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $stats;
}

// Get Time-Based Analytics
function getTimeBasedAnalytics($conn, array $params): array {
    $period = $params['period'] ?? 'monthly';
    $limit = min($params['limit'] ?? 12, 24);
    
    switch ($period) {
        case 'hourly':
            return getHourlyRevenue($conn, $limit);
        case 'daily':
            return getDailyRevenue($conn, $limit);
        case 'weekly':
            return getWeeklyRevenue($conn, $limit);
        case 'monthly':
        default:
            return getMonthlyRevenue($conn, $limit);
    }
}

// Get Monthly Revenue
function getMonthlyRevenue($conn, int $limit = 12): array {
    $sql = "SELECT 
                DATE_FORMAT(booking_date, '%Y-%m') as period,
                COALESCE(SUM(cost), 0) as revenue,
                COUNT(*) as ticket_count,
                COALESCE(AVG(cost), 0) as avg_ticket_value,
                COALESCE(MIN(cost), 0) as min_ticket_value,
                COALESCE(MAX(cost), 0) as max_ticket_value
            FROM ticket 
            WHERE booking_date >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)
            GROUP BY DATE_FORMAT(booking_date, '%Y-%m')
            ORDER BY period DESC 
            LIMIT ?";
    
    $stmt = prepareAndExecute($conn, $sql, [$limit, $limit]);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate growth rate
    $result = [];
    foreach ($data as $index => $row) {
        $growth = 0;
        if ($index < count($data) - 1) {
            $prev = $data[$index + 1]['revenue'] ?? 0;
            $current = $row['revenue'];
            $growth = $prev > 0 ? (($current - $prev) / $prev) * 100 : 100;
        }
        
        $result[] = [
            'period' => $row['period'],
            'revenue' => (float) $row['revenue'],
            'ticket_count' => (int) $row['ticket_count'],
            'avg_ticket_value' => (float) $row['avg_ticket_value'],
            'min_ticket_value' => (float) $row['min_ticket_value'],
            'max_ticket_value' => (float) $row['max_ticket_value'],
            'growth_rate' => round($growth, 2),
            'revenue_formatted' => formatCurrency($row['revenue'])
        ];
    }
    
    return array_reverse($result); // Return chronological order
}

// Get Daily Revenue
function getDailyRevenue($conn, int $limit = 30): array {
    $sql = "SELECT 
                DATE(booking_date) as period,
                COALESCE(SUM(cost), 0) as revenue,
                COUNT(*) as ticket_count,
                DAYNAME(booking_date) as day_name
            FROM ticket 
            WHERE booking_date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
            GROUP BY DATE(booking_date)
            ORDER BY period DESC 
            LIMIT ?";
    
    $stmt = prepareAndExecute($conn, $sql, [$limit, $limit]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get Predictive Analytics
function getPredictiveAnalytics($conn): array {
    $predictions = [];
    
    // Next month prediction based on historical data
    $sql = "SELECT 
                COALESCE(AVG(revenue), 0) as avg_monthly_revenue,
                COALESCE(STDDEV(revenue), 0) as std_dev,
                COUNT(*) as months_count
            FROM (
                SELECT 
                    DATE_FORMAT(booking_date, '%Y-%m') as month,
                    COALESCE(SUM(cost), 0) as revenue
                FROM ticket 
                WHERE booking_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
                GROUP BY DATE_FORMAT(booking_date, '%Y-%m')
            ) monthly_revenue";
    
    $stmt = prepareAndExecute($conn, $sql);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $avg = (float) $data['avg_monthly_revenue'];
    $stdDev = (float) $data['std_dev'];
    $months = (int) $data['months_count'];
    
    if ($months >= 3) {
        $predictions['next_month'] = [
            'expected' => $avg,
            'range_low' => max(0, $avg - $stdDev),
            'range_high' => $avg + $stdDev,
            'confidence' => min(95, $months * 8),
            'formatted_expected' => formatCurrency($avg)
        ];
        
        // Seasonal adjustment (simplified)
        $currentMonth = date('n');
        $seasonalMultiplier = getSeasonalMultiplier($currentMonth + 1);
        $seasonalAdjusted = $avg * $seasonalMultiplier;
        
        $predictions['next_month_seasonal'] = [
            'adjusted' => $seasonalAdjusted,
            'multiplier' => $seasonalMultiplier,
            'formatted_adjusted' => formatCurrency($seasonalAdjusted)
        ];
    }
    
    // Weekend vs Weekday analysis
    $sql = "SELECT 
                CASE 
                    WHEN DAYOFWEEK(booking_date) IN (1,7) THEN 'weekend'
                    ELSE 'weekday'
                END as day_type,
                COALESCE(SUM(cost), 0) as revenue,
                COUNT(*) as ticket_count,
                COALESCE(AVG(cost), 0) as avg_ticket_value
            FROM ticket 
            WHERE booking_date >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)
            GROUP BY day_type";
    
    $stmt = prepareAndExecute($conn, $sql);
    $predictions['day_type_analysis'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return $predictions;
}

// Get Comparative Analytics
function getComparativeAnalytics($conn, array $params): array {
    $comparisons = [];
    
    // Month-over-Month comparison
    $sql = "SELECT 
                DATE_FORMAT(booking_date, '%Y-%m') as period,
                COALESCE(SUM(cost), 0) as revenue
            FROM ticket 
            WHERE booking_date >= DATE_SUB(CURDATE(), INTERVAL 2 MONTH)
            GROUP BY DATE_FORMAT(booking_date, '%Y-%m')
            ORDER BY period DESC 
            LIMIT 2";
    
    $stmt = prepareAndExecute($conn, $sql);
    $months = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($months) >= 2) {
        $current = $months[0]['revenue'] ?? 0;
        $previous = $months[1]['revenue'] ?? 0;
        $growth = $previous > 0 ? (($current - $previous) / $previous) * 100 : 100;
        
        $comparisons['month_over_month'] = [
            'current' => (float) $current,
            'previous' => (float) $previous,
            'growth_rate' => round($growth, 2),
            'trend' => $growth >= 0 ? 'positive' : 'negative',
            'current_formatted' => formatCurrency($current),
            'previous_formatted' => formatCurrency($previous)
        ];
    }
    
    // Year-over-Year comparison
    $sql = "SELECT 
                YEAR(booking_date) as year,
                COALESCE(SUM(cost), 0) as revenue
            FROM ticket 
            WHERE YEAR(booking_date) >= YEAR(CURDATE()) - 1
            GROUP BY YEAR(booking_date)
            ORDER BY year DESC 
            LIMIT 2";
    
    $stmt = prepareAndExecute($conn, $sql);
    $years = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($years) >= 2) {
        $current = $years[0]['revenue'] ?? 0;
        $previous = $years[1]['revenue'] ?? 0;
        $growth = $previous > 0 ? (($current - $previous) / $previous) * 100 : 100;
        
        $comparisons['year_over_year'] = [
            'current' => (float) $current,
            'previous' => (float) $previous,
            'growth_rate' => round($growth, 2),
            'trend' => $growth >= 0 ? 'positive' : 'negative'
        ];
    }
    
    // Benchmark against average
    $sql = "SELECT 
                COALESCE(AVG(monthly_revenue), 0) as benchmark
            FROM (
                SELECT 
                    DATE_FORMAT(booking_date, '%Y-%m') as month,
                    COALESCE(SUM(cost), 0) as monthly_revenue
                FROM ticket 
                WHERE booking_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
                GROUP BY DATE_FORMAT(booking_date, '%Y-%m')
            ) monthly_data";
    
    $stmt = prepareAndExecute($conn, $sql);
    $benchmark = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $currentMonthRevenue = getCurrentMonthRevenue($conn);
    $benchmarkValue = (float) ($benchmark['benchmark'] ?? 0);
    $performance = $benchmarkValue > 0 ? ($currentMonthRevenue / $benchmarkValue) * 100 : 100;
    
    $comparisons['benchmark_analysis'] = [
        'current_month' => $currentMonthRevenue,
        'benchmark' => $benchmarkValue,
        'performance_percentage' => round($performance, 2),
        'status' => $performance >= 100 ? 'above_benchmark' : 'below_benchmark',
        'current_month_formatted' => formatCurrency($currentMonthRevenue),
        'benchmark_formatted' => formatCurrency($benchmarkValue)
    ];
    
    return $comparisons;
}

// Helper Functions
function prepareAndExecute($conn, string $sql, array $params = []) {
    $stmt = $conn->prepare($sql);
    if ($params) {
        $stmt->execute($params);
    } else {
        $stmt->execute();
    }
    return $stmt;
}

function formatCurrency(float $amount): string {
    return 'KES ' . number_format($amount, 2);
}

function getSeasonalMultiplier(int $month): float {
    // Simple seasonal adjustment (1.0 = average)
    $multipliers = [
        1 => 1.1,  // January - high
        2 => 0.9,  // February - low
        3 => 1.0,  // March - average
        4 => 1.0,  // April - average
        5 => 1.1,  // May - high
        6 => 1.2,  // June - peak
        7 => 1.3,  // July - peak
        8 => 1.2,  // August - peak
        9 => 1.0,  // September - average
        10 => 0.9, // October - low
        11 => 1.0, // November - average
        12 => 1.2  // December - peak
    ];
    
    return $multipliers[$month] ?? 1.0;
}

function getCurrentMonthRevenue($conn): float {
    $sql = "SELECT COALESCE(SUM(cost), 0) as revenue
            FROM ticket 
            WHERE YEAR(booking_date) = YEAR(CURDATE()) 
            AND MONTH(booking_date) = MONTH(CURDATE())";
    
    $stmt = prepareAndExecute($conn, $sql);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
    return (float) ($data['revenue'] ?? 0);
}

function getMinimalRevenueStats($conn): array {
    $sql = "SELECT COALESCE(SUM(cost), 0) as total_revenue 
            FROM ticket";
    
    $stmt = prepareAndExecute($conn, $sql);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return [
        'total_revenue' => (float) ($data['total_revenue'] ?? 0),
        'total_revenue_formatted' => formatCurrency($data['total_revenue'] ?? 0)
    ];
}

function detectAnomalies($conn): array {
    // Simple anomaly detection based on recent trends
    $anomalies = [];
    
    // Check for sudden drops in daily revenue
    $sql = "SELECT 
                DATE(booking_date) as date,
                COALESCE(SUM(cost), 0) as revenue
            FROM ticket 
            WHERE booking_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
            GROUP BY DATE(booking_date)
            ORDER BY date DESC 
            LIMIT 7";
    
    $stmt = prepareAndExecute($conn, $sql);
    $dailyData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($dailyData) >= 3) {
        $last = $dailyData[0]['revenue'] ?? 0;
        $secondLast = $dailyData[1]['revenue'] ?? 0;
        $thirdLast = $dailyData[2]['revenue'] ?? 0;
        
        $avgPrevTwo = ($secondLast + $thirdLast) / 2;
        
        if ($avgPrevTwo > 0 && $last < ($avgPrevTwo * 0.5)) {
            $anomalies[] = [
                'type' => 'revenue_drop',
                'severity' => 'high',
                'message' => 'Significant revenue drop detected for ' . $dailyData[0]['date'],
                'current' => $last,
                'expected' => $avgPrevTwo,
                'deviation' => round((($last - $avgPrevTwo) / $avgPrevTwo) * 100, 2)
            ];
        }
    }
    
    return $anomalies;
}

function generateRecommendations(array $stats, array $timeSeries): array {
    $recommendations = [];
    
    $totalRevenue = $stats['overall']['total_revenue'] ?? 0;
    $avgTicketValue = $stats['overall']['average_ticket_value'] ?? 0;
    
    // Analyze class distribution
    $classRevenue = [];
    foreach ($stats['by_class'] ?? [] as $class) {
        $classRevenue[$class['class']] = $class['revenue'] ?? 0;
    }
    
    $totalClassRevenue = array_sum($classRevenue);
    
    if ($totalClassRevenue > 0) {
        foreach ($classRevenue as $class => $revenue) {
            $percentage = ($revenue / $totalClassRevenue) * 100;
            
            if ($percentage < 10 && in_array($class, ['B', 'F'])) {
                $recommendations[] = [
                    'type' => 'class_optimization',
                    'priority' => 'medium',
                    'message' => "Consider promoting $class class bookings (currently ${percentage}% of revenue)",
                    'action' => 'increase_marketing',
                    'potential_impact' => 'high'
                ];
            }
        }
    }
    
    // Check for declining trends
    if (count($timeSeries) >= 3) {
        $lastThree = array_slice($timeSeries, -3);
        $trend = ($lastThree[2]['revenue'] ?? 0) - ($lastThree[0]['revenue'] ?? 0);
        
        if ($trend < 0 && abs($trend) > ($lastThree[0]['revenue'] * 0.1)) {
            $recommendations[] = [
                'type' => 'declining_trend',
                'priority' => 'high',
                'message' => 'Declining revenue trend detected over last 3 periods',
                'action' => 'analyze_causes',
                'potential_impact' => 'critical'
            ];
        }
    }
    
    // Check average ticket value
    if ($avgTicketValue < 10000) { // Example threshold
        $recommendations[] = [
            'type' => 'ticket_value',
            'priority' => 'low',
            'message' => 'Average ticket value is below optimal level',
            'action' => 'upsell_premium_services',
            'potential_impact' => 'medium'
        ];
    }
    
    return $recommendations;
}

// Main Request Handler
try {
    // Apply middleware
    authenticate();
    
    // Get query parameters
    $format = $_GET['format'] ?? 'json';
    $period = $_GET['period'] ?? 'monthly';
    $limit = min((int) ($_GET['limit'] ?? 12), 36);
    $detailed = filter_var($_GET['detailed'] ?? true, FILTER_VALIDATE_BOOLEAN);
    
    // Get analytics data
    $analytics = getRevenueAnalytics($conn, [
        'period' => $period,
        'limit' => $limit,
        'detailed' => $detailed
    ]);
    
    // Format response
    $response = [
        'status' => 'success',
        'code' => 200,
        'message' => 'Revenue analytics retrieved successfully',
        'timestamp' => time(),
        'correlation_id' => uniqid('corr_', true),
        'request_parameters' => [
            'period' => $period,
            'limit' => $limit,
            'detailed' => $detailed,
            'format' => $format
        ],
        'data' => $analytics
    ];
    
    // Output based on format
    if ($format === 'xml') {
        header('Content-Type: application/xml; charset=utf-8');
        echo arrayToXml($response);
    } elseif ($format === 'csv') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="revenue_analytics_' . date('Y-m-d') . '.csv"');
        echo arrayToCsv($response['data']);
    } else {
        // Default JSON with pretty print option
        $flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
        if (isset($_GET['pretty'])) {
            $flags |= JSON_PRETTY_PRINT;
        }
        
        echo json_encode($response, $flags);
    }
    
} catch (Exception $e) {
    // Global error handler
    error_log('Revenue API error: ' . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'code' => 'INTERNAL_SERVER_ERROR',
        'message' => 'An unexpected error occurred',
        'error_id' => uniqid('ERR_', true),
        'timestamp' => time(),
        'correlation_id' => uniqid('corr_', true),
        'data' => null
    ]);
}

// Utility functions for different formats
function arrayToXml($array, $rootElement = null, $xml = null): string {
    if ($xml === null) {
        $xml = new SimpleXMLElement($rootElement ?: '<revenue_analytics/>');
    }
    
    foreach ($array as $key => $value) {
        if (is_array($value)) {
            if (is_numeric($key)) {
                $key = 'item_' . $key;
            }
            arrayToXml($value, $key, $xml->addChild($key));
        } else {
            if (is_numeric($key)) {
                $key = 'item_' . $key;
            }
            $xml->addChild($key, htmlspecialchars($value));
        }
    }
    
    return $xml->asXML();
}

function arrayToCsv($array): string {
    $output = fopen('php://output', 'w');
    
    // Flatten array for CSV
    $flattened = flattenArray($array);
    
    fputcsv($output, ['Metric', 'Value', 'Type']);
    
    foreach ($flattened as $key => $value) {
        fputcsv($output, [$key, $value, gettype($value)]);
    }
    
    fclose($output);
    return ob_get_clean();
}

function flattenArray($array, $prefix = ''): array {
    $result = [];
    
    foreach ($array as $key => $value) {
        $newKey = $prefix . (empty($prefix) ? '' : '.') . $key;
        
        if (is_array($value)) {
            $result = array_merge($result, flattenArray($value, $newKey));
        } else {
            $result[$newKey] = $value;
        }
    }
    
    return $result;
}

// Close database connection
if ($conn) {
    $conn = null;
}
?>
<?php
/**
 * Analytics Helper Functions
 * Version: 2026.1.0
 */

function calculateGrowthRate(float $current, float $previous): float {
    if ($previous == 0) {
        return $current > 0 ? 100 : 0;
    }
    return (($current - $previous) / $previous) * 100;
}

function calculateMovingAverage(array $data, int $period = 7): array {
    $result = [];
    $count = count($data);
    
    for ($i = 0; $i < $count; $i++) {
        $start = max(0, $i - $period + 1);
        $slice = array_slice($data, $start, $period);
        $result[] = array_sum($slice) / count($slice);
    }
    
    return $result;
}

function detectTrend(array $data): string {
    if (count($data) < 3) {
        return 'insufficient_data';
    }
    
    $first = $data[0];
    $last = end($data);
    $percentageChange = (($last - $first) / $first) * 100;
    
    if ($percentageChange > 10) {
        return 'strong_positive';
    } elseif ($percentageChange > 2) {
        return 'positive';
    } elseif ($percentageChange < -10) {
        return 'strong_negative';
    } elseif ($percentageChange < -2) {
        return 'negative';
    } else {
        return 'stable';
    }
}

function calculatePercentile(array $data, float $percentile): float {
    sort($data);
    $index = ($percentile / 100) * (count($data) - 1);
    
    if (floor($index) == $index) {
        return $data[$index];
    }
    
    $lower = floor($index);
    $upper = ceil($index);
    $weight = $index - $lower;
    
    return ($data[$lower] * (1 - $weight)) + ($data[$upper] * $weight);
}

function forecastNextPeriod(array $historicalData, int $periods = 1): array {
    $count = count($historicalData);
    
    if ($count < 3) {
        return ['forecast' => end($historicalData) ?? 0, 'confidence' => 0];
    }
    
    // Simple linear regression
    $sumX = $sumY = $sumXY = $sumX2 = 0;
    
    for ($i = 0; $i < $count; $i++) {
        $x = $i + 1;
        $y = $historicalData[$i];
        
        $sumX += $x;
        $sumY += $y;
        $sumXY += $x * $y;
        $sumX2 += $x * $x;
    }
    
    $slope = ($count * $sumXY - $sumX * $sumY) / ($count * $sumX2 - $sumX * $sumX);
    $intercept = ($sumY - $slope * $sumX) / $count;
    
    $forecast = $intercept + $slope * ($count + $periods);
    
    // Calculate confidence (R-squared)
    $ssRes = $ssTot = 0;
    $meanY = $sumY / $count;
    
    for ($i = 0; $i < $count; $i++) {
        $x = $i + 1;
        $y = $historicalData[$i];
        $predicted = $intercept + $slope * $x;
        
        $ssRes += pow($y - $predicted, 2);
        $ssTot += pow($y - $meanY, 2);
    }
    
    $rSquared = $ssTot > 0 ? 1 - ($ssRes / $ssTot) : 0;
    $confidence = max(0, min(100, $rSquared * 100));
    
    return [
        'forecast' => max(0, $forecast),
        'confidence' => round($confidence, 2),
        'trend' => $slope >= 0 ? 'upward' : 'downward',
        'trend_strength' => abs($slope)
    ];
}

function formatLargeNumber(float $number): string {
    if ($number >= 1000000) {
        return round($number / 1000000, 2) . 'M';
    } elseif ($number >= 1000) {
        return round($number / 1000, 2) . 'K';
    }
    return round($number, 2);
}

function calculateVariance(array $data): float {
    $count = count($data);
    if ($count < 2) {
        return 0;
    }
    
    $mean = array_sum($data) / $count;
    $variance = 0;
    
    foreach ($data as $value) {
        $variance += pow($value - $mean, 2);
    }
    
    return $variance / ($count - 1);
}

function getDataQualityScore(array $data): int {
    $score = 100;
    
    // Check for null values
    $nullCount = count(array_filter($data, 'is_null'));
    if ($nullCount > 0) {
        $score -= ($nullCount / count($data)) * 30;
    }
    
    // Check for zeros (might indicate missing data)
    $zeroCount = count(array_filter($data, function($v) { return $v == 0; }));
    if ($zeroCount > count($data) * 0.5) {
        $score -= 20;
    }
    
    // Check data recency
    // This would depend on your data structure
    
    return max(0, min(100, (int) $score));
}
?>
<?php
/**
 * Security Helper Functions
 * Version: 2026.1.0
 */

function sanitizeInput(string $input): string {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

function validateEmail(string $email): bool {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function generateCSRFToken(): string {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCSRFToken(string $token): bool {
    if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        error_log('CSRF token validation failed');
        return false;
    }
    return true;
}

function encryptData(string $data, string $key): string {
    $iv = random_bytes(openssl_cipher_iv_length('aes-256-gcm'));
    $tag = '';
    $encrypted = openssl_encrypt(
        $data,
        'aes-256-gcm',
        $key,
        OPENSSL_RAW_DATA,
        $iv,
        $tag,
        '',
        16
    );
    return base64_encode($iv . $tag . $encrypted);
}

function decryptData(string $data, string $key): string {
    $data = base64_decode($data);
    $iv = substr($data, 0, 12);
    $tag = substr($data, 12, 16);
    $encrypted = substr($data, 28);
    
    return openssl_decrypt(
        $encrypted,
        'aes-256-gcm',
        $key,
        OPENSSL_RAW_DATA,
        $iv,
        $tag
    );
}

function getRequestId(): string {
    return $_SERVER['HTTP_X_REQUEST_ID'] ?? uniqid('req_', true);
}

function logSecurityEvent(string $event, array $details = []): void {
    $logEntry = [
        'timestamp' => date('c'),
        'event' => $event,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        'details' => $details
    ];
    
    error_log('SECURITY: ' . json_encode($logEntry));
}
?>
