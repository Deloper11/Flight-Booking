<?php
session_start();
require '../helpers/init_conn_db.php';

// Security check for admin access
if (!isset($_SESSION['adminId'])) {
    header('Location: login.php');
    exit();
}

// Get filter parameters
$rating_filter = isset($_GET['rating']) ? (int)$_GET['rating'] : 0;
$date_filter = isset($_GET['date']) ? $_GET['date'] : 'all';
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';

// Build query with filters
$sql = 'SELECT f.*, 
        u.username,
        u.profile_image,
        (SELECT COUNT(*) FROM booking WHERE user_id = f.user_id) as user_bookings
        FROM feedback f 
        LEFT JOIN users u ON f.user_id = u.user_id 
        WHERE 1=1';

if (!empty($search)) {
    $sql .= " AND (f.email LIKE '%$search%' 
                OR f.q1 LIKE '%$search%' 
                OR f.q2 LIKE '%$search%' 
                OR f.q3 LIKE '%$search%'
                OR u.username LIKE '%$search%')";
}

if ($rating_filter > 0) {
    $sql .= " AND f.rate = $rating_filter";
}

if ($date_filter === 'today') {
    $sql .= " AND DATE(f.created_at) = CURDATE()";
} elseif ($date_filter === 'week') {
    $sql .= " AND f.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
} elseif ($date_filter === 'month') {
    $sql .= " AND f.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
}

// Add sorting
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';
switch ($sort) {
    case 'oldest':
        $sql .= " ORDER BY f.created_at ASC";
        break;
    case 'highest':
        $sql .= " ORDER BY f.rate DESC, f.created_at DESC";
        break;
    case 'lowest':
        $sql .= " ORDER BY f.rate ASC, f.created_at DESC";
        break;
    default:
        $sql .= " ORDER BY f.created_at DESC";
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 12;
$offset = ($page - 1) * $limit;

// Get total count for pagination
$count_sql = str_replace('SELECT f.*', 'SELECT COUNT(*) as total', $sql);
$count_result = mysqli_query($conn, $count_sql);
$total_rows = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_rows / $limit);

// Add limit and offset to main query
$sql .= " LIMIT $limit OFFSET $offset";

// Execute main query
$result = mysqli_query($conn, $sql);

// Calculate statistics
$stats_sql = "SELECT 
                COUNT(*) as total,
                AVG(rate) as avg_rating,
                COUNT(CASE WHEN rate = 5 THEN 1 END) as five_star,
                COUNT(CASE WHEN rate = 4 THEN 1 END) as four_star,
                COUNT(CASE WHEN rate = 3 THEN 1 END) as three_star,
                COUNT(CASE WHEN rate = 2 THEN 1 END) as two_star,
                COUNT(CASE WHEN rate = 1 THEN 1 END) as one_star
              FROM feedback";

$stats_result = mysqli_query($conn, $stats_sql);
$stats = mysqli_fetch_assoc($stats_result);

// Sentiment analysis (simple version based on rating)
$sentiment_sql = "SELECT 
                    CASE 
                        WHEN rate >= 4 THEN 'positive'
                        WHEN rate = 3 THEN 'neutral'
                        ELSE 'negative'
                    END as sentiment,
                    COUNT(*) as count
                  FROM feedback
                  GROUP BY sentiment";
$sentiment_result = mysqli_query($conn, $sentiment_sql);
$sentiment_data = [];
while ($row = mysqli_fetch_assoc($sentiment_result)) {
    $sentiment_data[$row['sentiment']] = $row['count'];
}

// Recent trends
$trends_sql = "SELECT 
                DATE_FORMAT(created_at, '%Y-%m') as month,
                COUNT(*) as count,
                AVG(rate) as avg_rating
               FROM feedback
               WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
               GROUP BY DATE_FORMAT(created_at, '%Y-%m')
               ORDER BY month DESC";
$trends_result = mysqli_query($conn, $trends_sql);
$monthly_trends = [];
while ($row = mysqli_fetch_assoc($trends_result)) {
    $monthly_trends[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Reviews Dashboard | Admin</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/apexcharts@3.35.0/dist/apexcharts.css">
    <style>
        .reviews-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 15px;
            padding: 30px;
            color: white;
            margin-bottom: 30px;
        }
        .review-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.08);
            transition: transform 0.3s ease;
            height: 100%;
            position: relative;
        }
        .review-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 25px rgba(0,0,0,0.15);
        }
        .review-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 18px;
            margin-right: 15px;
        }
        .review-header {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }
        .review-user-info h5 {
            margin: 0;
            font-weight: 600;
        }
        .review-user-info small {
            color: #64748b;
        }
        .star-rating {
            color: #fbbf24;
            font-size: 16px;
            margin: 10px 0;
        }
        .review-content {
            margin: 15px 0;
            color: #475569;
            line-height: 1.6;
        }
        .review-question {
            font-weight: 600;
            color: #334155;
            margin-top: 15px;
        }
        .review-answer {
            color: #64748b;
            margin-bottom: 10px;
            font-size: 14px;
            line-height: 1.5;
        }
        .review-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #e2e8f0;
            font-size: 12px;
            color: #94a3b8;
        }
        .review-actions {
            position: absolute;
            top: 20px;
            right: 20px;
        }
        .review-tag {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            margin-left: 10px;
        }
        .tag-positive {
            background: rgba(16, 185, 129, 0.1);
            color: #10b981;
        }
        .tag-neutral {
            background: rgba(245, 158, 11, 0.1);
            color: #f59e0b;
        }
        .tag-negative {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
        }
        .stats-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }
        .stats-card .stat-value {
            font-size: 2.5rem;
            font-weight: 700;
            color: #1e293b;
            margin: 10px 0;
        }
        .stats-card .stat-label {
            color: #64748b;
            font-size: 0.9rem;
        }
        .rating-filter {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .rating-btn {
            padding: 8px 20px;
            border-radius: 20px;
            border: 2px solid #e2e8f0;
            background: white;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: all 0.3s;
        }
        .rating-btn:hover, .rating-btn.active {
            border-color: #6366f1;
            background: #6366f1;
            color: white;
        }
        .sentiment-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            margin-right: 10px;
        }
        .sentiment-positive {
            background: rgba(16, 185, 129, 0.1);
            color: #10b981;
        }
        .sentiment-neutral {
            background: rgba(245, 158, 11, 0.1);
            color: #f59e0b;
        }
        .sentiment-negative {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
        }
        .review-insights {
            background: #f8fafc;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 30px;
        }
        .empty-reviews {
            text-align: center;
            padding: 60px 20px;
            color: #64748b;
        }
        .empty-reviews i {
            font-size: 4rem;
            margin-bottom: 20px;
            color: #cbd5e1;
        }
        @media (max-width: 768px) {
            .review-header {
                flex-direction: column;
                text-align: center;
            }
            .review-avatar {
                margin-right: 0;
                margin-bottom: 10px;
            }
        }
    </style>
</head>
<body>
    <?php include_once 'admin_sidebar.php'; ?>
    
    <div class="main-content">
        <?php include_once 'admin_header.php'; ?>
        
        <div class="content-wrapper">
            <!-- Page Header -->
            <div class="reviews-header">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h1 class="mb-2"><i class="fas fa-comments"></i> Customer Reviews Dashboard</h1>
                        <p class="mb-0 opacity-75">Analyze customer feedback and improve service quality</p>
                    </div>
                    <div class="text-end">
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-white text-dark">
                                <i class="fas fa-sync-alt"></i> Real-time
                            </span>
                            <button class="btn btn-light" onclick="exportReviews()">
                                <i class="fas fa-download"></i> Export
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Statistics Row -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="stats-card">
                        <i class="fas fa-star text-warning fa-2x"></i>
                        <div class="stat-value"><?php echo number_format($stats['total']); ?></div>
                        <div class="stat-label">Total Reviews</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card">
                        <i class="fas fa-chart-line text-primary fa-2x"></i>
                        <div class="stat-value"><?php echo number_format($stats['avg_rating'], 1); ?></div>
                        <div class="stat-label">Average Rating</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card">
                        <i class="fas fa-thumbs-up text-success fa-2x"></i>
                        <div class="stat-value"><?php echo number_format($sentiment_data['positive'] ?? 0); ?></div>
                        <div class="stat-label">Positive Reviews</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card">
                        <i class="fas fa-calendar-alt text-info fa-2x"></i>
                        <div class="stat-value"><?php echo count($monthly_trends); ?></div>
                        <div class="stat-label">Months Tracked</div>
                    </div>
                </div>
            </div>

            <!-- Filters and Search -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-4">
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                                <input type="text" class="form-control" name="search" placeholder="Search reviews..." value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" name="rating">
                                <option value="0">All Ratings</option>
                                <option value="5" <?php echo $rating_filter == 5 ? 'selected' : ''; ?>>5 Stars</option>
                                <option value="4" <?php echo $rating_filter == 4 ? 'selected' : ''; ?>>4 Stars</option>
                                <option value="3" <?php echo $rating_filter == 3 ? 'selected' : ''; ?>>3 Stars</option>
                                <option value="2" <?php echo $rating_filter == 2 ? 'selected' : ''; ?>>2 Stars</option>
                                <option value="1" <?php echo $rating_filter == 1 ? 'selected' : ''; ?>>1 Star</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" name="date">
                                <option value="all" <?php echo $date_filter == 'all' ? 'selected' : ''; ?>>All Time</option>
                                <option value="today" <?php echo $date_filter == 'today' ? 'selected' : ''; ?>>Today</option>
                                <option value="week" <?php echo $date_filter == 'week' ? 'selected' : ''; ?>>Last 7 Days</option>
                                <option value="month" <?php echo $date_filter == 'month' ? 'selected' : ''; ?>>Last 30 Days</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-filter"></i> Apply
                            </button>
                        </div>
                    </form>

                    <!-- Quick Rating Filters -->
                    <div class="rating-filter mt-3">
                        <small class="text-muted d-block mb-2">Quick filters:</small>
                        <a href="?rating=5" class="rating-btn <?php echo $rating_filter == 5 ? 'active' : ''; ?>">
                            <i class="fas fa-star"></i> 5 Stars (<?php echo $stats['five_star']; ?>)
                        </a>
                        <a href="?rating=4" class="rating-btn <?php echo $rating_filter == 4 ? 'active' : ''; ?>">
                            <i class="fas fa-star"></i> 4 Stars (<?php echo $stats['four_star']; ?>)
                        </a>
                        <a href="?rating=3" class="rating-btn <?php echo $rating_filter == 3 ? 'active' : ''; ?>">
                            <i class="fas fa-star"></i> 3 Stars (<?php echo $stats['three_star']; ?>)
                        </a>
                        <a href="?rating=2" class="rating-btn <?php echo $rating_filter == 2 ? 'active' : ''; ?>">
                            <i class="fas fa-star"></i> 2 Stars (<?php echo $stats['two_star']; ?>)
                        </a>
                        <a href="?rating=1" class="rating-btn <?php echo $rating_filter == 1 ? 'active' : ''; ?>">
                            <i class="fas fa-star"></i> 1 Star (<?php echo $stats['one_star']; ?>)
                        </a>
                        <a href="review.php" class="rating-btn">
                            <i class="fas fa-times"></i> Clear All
                        </a>
                    </div>
                </div>
            </div>

            <!-- Sentiment Analysis -->
            <div class="review-insights">
                <h5 class="mb-3"><i class="fas fa-chart-pie"></i> Sentiment Analysis</h5>
                <div class="row">
                    <div class="col-md-6">
                        <div class="d-flex align-items-center mb-3">
                            <span class="sentiment-badge sentiment-positive">
                                <i class="fas fa-smile"></i> Positive
                            </span>
                            <div class="progress flex-grow-1" style="height: 10px;">
                                <div class="progress-bar bg-success" style="width: <?php echo $stats['total'] > 0 ? (($sentiment_data['positive'] ?? 0) / $stats['total'] * 100) : 0; ?>%"></div>
                            </div>
                            <span class="ms-2"><?php echo $sentiment_data['positive'] ?? 0; ?> reviews</span>
                        </div>
                        <div class="d-flex align-items-center mb-3">
                            <span class="sentiment-badge sentiment-neutral">
                                <i class="fas fa-meh"></i> Neutral
                            </span>
                            <div class="progress flex-grow-1" style="height: 10px;">
                                <div class="progress-bar bg-warning" style="width: <?php echo $stats['total'] > 0 ? (($sentiment_data['neutral'] ?? 0) / $stats['total'] * 100) : 0; ?>%"></div>
                            </div>
                            <span class="ms-2"><?php echo $sentiment_data['neutral'] ?? 0; ?> reviews</span>
                        </div>
                        <div class="d-flex align-items-center">
                            <span class="sentiment-badge sentiment-negative">
                                <i class="fas fa-frown"></i> Negative
                            </span>
                            <div class="progress flex-grow-1" style="height: 10px;">
                                <div class="progress-bar bg-danger" style="width: <?php echo $stats['total'] > 0 ? (($sentiment_data['negative'] ?? 0) / $stats['total'] * 100) : 0; ?>%"></div>
                            </div>
                            <span class="ms-2"><?php echo $sentiment_data['negative'] ?? 0; ?> reviews</span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h6>Recent Trends</h6>
                        <div id="trendsChart" style="height: 200px;"></div>
                    </div>
                </div>
            </div>

            <!-- Reviews Grid -->
            <div class="row">
                <?php if (mysqli_num_rows($result) > 0): ?>
                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                        <?php
                        // Determine sentiment
                        $sentiment = match((int)$row['rate']) {
                            5, 4 => 'positive',
                            3 => 'neutral',
                            default => 'negative'
                        };
                        
                        // Get user initials for avatar
                        $initials = strtoupper(substr($row['email'], 0, 1));
                        if (!empty($row['username'])) {
                            $initials = strtoupper(substr($row['username'], 0, 1));
                        }
                        
                        // Format date
                        $date = new DateTime($row['created_at']);
                        $now = new DateTime();
                        $interval = $date->diff($now);
                        
                        if ($interval->days == 0) {
                            $time_ago = 'Today';
                        } elseif ($interval->days == 1) {
                            $time_ago = 'Yesterday';
                        } elseif ($interval->days < 7) {
                            $time_ago = $interval->days . ' days ago';
                        } elseif ($interval->days < 30) {
                            $time_ago = floor($interval->days / 7) . ' weeks ago';
                        } else {
                            $time_ago = $date->format('M d, Y');
                        }
                        ?>
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="review-card">
                                <div class="review-actions">
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                            <i class="fas fa-ellipsis-v"></i>
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li>
                                                <button class="dropdown-item" onclick="showReviewDetails(<?php echo $row['feed_id']; ?>)">
                                                    <i class="fas fa-eye me-2"></i> View Details
                                                </button>
                                            </li>
                                            <li>
                                                <a class="dropdown-item" href="mailto:<?php echo $row['email']; ?>">
                                                    <i class="fas fa-reply me-2"></i> Reply
                                                </a>
                                            </li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <button class="dropdown-item text-danger" onclick="deleteReview(<?php echo $row['feed_id']; ?>)">
                                                    <i class="fas fa-trash me-2"></i> Delete
                                                </button>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                                
                                <div class="review-header">
                                    <div class="review-avatar">
                                        <?php echo $initials; ?>
                                    </div>
                                    <div class="review-user-info">
                                        <h5><?php echo htmlspecialchars($row['username'] ?? 'Anonymous'); ?></h5>
                                        <small><?php echo htmlspecialchars($row['email']); ?></small>
                                        <?php if ($row['user_bookings']): ?>
                                            <div>
                                                <small class="text-muted">
                                                    <i class="fas fa-ticket-alt"></i> 
                                                    <?php echo $row['user_bookings']; ?> booking(s)
                                                </small>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="star-rating">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="fas fa-star<?php echo $i <= $row['rate'] ? '' : '-o'; ?>"></i>
                                    <?php endfor; ?>
                                    <span class="review-tag tag-<?php echo $sentiment; ?> ms-2">
                                        <?php echo ucfirst($sentiment); ?>
                                    </span>
                                </div>
                                
                                <div class="review-content">
                                    <div class="review-question">First impression:</div>
                                    <div class="review-answer"><?php echo htmlspecialchars($row['q1']); ?></div>
                                    
                                    <div class="review-question">How you heard about us:</div>
                                    <div class="review-answer"><?php echo htmlspecialchars($row['q2']); ?></div>
                                    
                                    <div class="review-question">Suggestions for improvement:</div>
                                    <div class="review-answer"><?php echo htmlspecialchars($row['q3']); ?></div>
                                </div>
                                
                                <div class="review-meta">
                                    <div>
                                        <i class="far fa-clock"></i> <?php echo $time_ago; ?>
                                    </div>
                                    <div>
                                        <span class="badge bg-light text-dark">
                                            ID: <?php echo $row['feed_id']; ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="col-12">
                        <div class="empty-reviews">
                            <i class="fas fa-comments-slash"></i>
                            <h4>No Reviews Found</h4>
                            <p>No reviews match your current filters. Try adjusting your search criteria.</p>
                            <a href="review.php" class="btn btn-primary">
                                <i class="fas fa-redo"></i> Clear Filters
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <nav aria-label="Page navigation" class="mt-4">
                <ul class="pagination justify-content-center">
                    <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    </li>
                    
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <?php if ($i == 1 || $i == $total_pages || ($i >= $page - 2 && $i <= $page + 2)): ?>
                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php elseif ($i == $page - 3 || $i == $page + 3): ?>
                            <li class="page-item disabled">
                                <span class="page-link">...</span>
                            </li>
                        <?php endif; ?>
                    <?php endfor; ?>
                    
                    <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    </li>
                </ul>
            </nav>
            <?php endif; ?>
        </div>
    </div>

    <!-- Review Details Modal -->
    <div class="modal fade" id="reviewModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Review Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="reviewDetails">
                    <!-- Content loaded via JavaScript -->
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts@3.35.0"></script>
    <script src="../assets/js/admin.js"></script>
    <script>
    // Initialize charts
    document.addEventListener('DOMContentLoaded', function() {
        // Trends chart
        const trendsData = <?php echo json_encode($monthly_trends); ?>;
        const trendsChart = new ApexCharts(document.querySelector("#trendsChart"), {
            series: [{
                name: 'Reviews',
                data: trendsData.map(item => item.count)
            }, {
                name: 'Avg Rating',
                data: trendsData.map(item => parseFloat(item.avg_rating))
            }],
            chart: {
                type: 'line',
                height: '100%',
                toolbar: { show: false }
            },
            colors: ['#667eea', '#fbbf24'],
            stroke: { width: [2, 2] },
            markers: { size: 4 },
            xaxis: {
                categories: trendsData.map(item => item.month),
                labels: { rotate: -45 }
            },
            yaxis: [{
                title: { text: 'Reviews' }
            }, {
                opposite: true,
                title: { text: 'Avg Rating' },
                min: 0,
                max: 5
            }]
        });
        trendsChart.render();

        // Initialize tooltips
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    });

    // Show review details
    function showReviewDetails(reviewId) {
        fetch(`get_review_details.php?id=${reviewId}`)
            .then(response => response.json())
            .then(data => {
                const modalContent = `
                    <div class="review-detail">
                        <div class="row mb-4">
                            <div class="col-md-8">
                                <h6>Reviewer Information</h6>
                                <p><strong>Email:</strong> ${data.email}</p>
                                ${data.username ? `<p><strong>Username:</strong> ${data.username}</p>` : ''}
                                <p><strong>Submitted:</strong> ${new Date(data.created_at).toLocaleString()}</p>
                            </div>
                            <div class="col-md-4 text-end">
                                <div class="star-rating fs-4">
                                    ${Array(5).fill().map((_, i) => 
                                        `<i class="fas fa-star${i < data.rate ? '' : '-o'}"></i>`
                                    ).join('')}
                                </div>
                                <div class="mt-2">
                                    <span class="badge ${data.rate >= 4 ? 'bg-success' : data.rate == 3 ? 'bg-warning' : 'bg-danger'}">
                                        ${data.rate >= 4 ? 'Positive' : data.rate == 3 ? 'Neutral' : 'Negative'}
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card mb-3">
                            <div class="card-header">
                                <strong>What was your first impression when you entered the website?</strong>
                            </div>
                            <div class="card-body">
                                <p class="mb-0">${data.q1}</p>
                            </div>
                        </div>
                        
                        <div class="card mb-3">
                            <div class="card-header">
                                <strong>How did you first hear about us?</strong>
                            </div>
                            <div class="card-body">
                                <p class="mb-0">${data.q2}</p>
                            </div>
                        </div>
                        
                        <div class="card mb-3">
                            <div class="card-header">
                                <strong>Is there anything missing on this page?</strong>
                            </div>
                            <div class="card-body">
                                <p class="mb-0">${data.q3}</p>
                            </div>
                        </div>
                        
                        <div class="mt-4">
                            <button class="btn btn-primary" onclick="replyToReview('${data.email}')">
                                <i class="fas fa-reply"></i> Reply via Email
                            </button>
                            <button class="btn btn-outline-secondary ms-2" onclick="markAsRead(${reviewId})">
                                <i class="fas fa-check"></i> Mark as Read
                            </button>
                        </div>
                    </div>
                `;
                
                document.getElementById('reviewDetails').innerHTML = modalContent;
                const modal = new bootstrap.Modal(document.getElementById('reviewModal'));
                modal.show();
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to load review details');
            });
    }

    // Delete review with confirmation
    function deleteReview(reviewId) {
        if (confirm('Are you sure you want to delete this review? This action cannot be undone.')) {
            fetch(`delete_review.php?id=${reviewId}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Failed to delete review: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while deleting the review');
            });
        }
    }

    // Reply to review
    function replyToReview(email) {
        window.location.href = `mailto:${email}?subject=Regarding your feedback`;
    }

    // Mark review as read
    function markAsRead(reviewId) {
        fetch(`mark_review_read.php?id=${reviewId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Review marked as read');
                    const modal = bootstrap.Modal.getInstance(document.getElementById('reviewModal'));
                    modal.hide();
                }
            });
    }

    // Export reviews
    function exportReviews() {
        const params = new URLSearchParams(window.location.search);
        window.open(`export_reviews.php?${params.toString()}`, '_blank');
    }

    // Real-time updates (polling every 60 seconds)
    setInterval(() => {
        fetch('check_new_reviews.php')
            .then(response => response.json())
            .then(data => {
                if (data.new_reviews > 0) {
                    // Show notification
                    const badge = document.createElement('span');
                    badge.className = 'badge bg-danger position-absolute top-0 start-100 translate-middle';
                    badge.textContent = data.new_reviews;
                    badge.style.fontSize = '10px';
                    
                    const reviewsLink = document.querySelector('a[href="review.php"]');
                    if (reviewsLink) {
                        const existingBadge = reviewsLink.querySelector('.badge');
                        if (existingBadge) {
                            existingBadge.remove();
                        }
                        reviewsLink.style.position = 'relative';
                        reviewsLink.appendChild(badge);
                    }
                }
            });
    }, 60000);
    </script>
</body>
</html>
<?php
session_start();
require '../helpers/init_conn_db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['adminId'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$review_id = filter_var($_GET['id'], FILTER_VALIDATE_INT);

if (!$review_id) {
    echo json_encode(['error' => 'Invalid review ID']);
    exit;
}

$sql = 'SELECT f.*, u.username 
        FROM feedback f 
        LEFT JOIN users u ON f.user_id = u.user_id 
        WHERE f.feed_id = ?';

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 'i', $review_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($row = mysqli_fetch_assoc($result)) {
    echo json_encode($row);
} else {
    echo json_encode(['error' => 'Review not found']);
}

mysqli_stmt_close($stmt);
mysqli_close($conn);
<?php
session_start();
require '../helpers/init_conn_db.php';

if (!isset($_SESSION['adminId'])) {
    header('Location: login.php');
    exit;
}

// Similar filtering logic as main page
// Generate CSV or PDF export

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="reviews_' . date('Y-m-d') . '.csv"');

$output = fopen('php://output', 'w');
fputcsv($output, ['ID', 'Email', 'Rating', 'First Impression', 'Source', 'Improvements', 'Date']);

$sql = 'SELECT * FROM feedback ORDER BY created_at DESC';
$result = mysqli_query($conn, $sql);

while ($row = mysqli_fetch_assoc($result)) {
    fputcsv($output, [
        $row['feed_id'],
        $row['email'],
        $row['rate'],
        $row['q1'],
        $row['q2'],
        $row['q3'],
        $row['created_at']
    ]);
}

fclose($output);
mysqli_close($conn);
<?php
session_start();
require '../helpers/init_conn_db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['adminId'])) {
    echo json_encode(['new_reviews' => 0]);
    exit;
}

// Check for new reviews since last check
$last_check = $_SESSION['last_review_check'] ?? date('Y-m-d H:i:s', strtotime('-1 hour'));
$sql = "SELECT COUNT(*) as new_reviews FROM feedback WHERE created_at > ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 's', $last_check);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($result);

// Update last check time
$_SESSION['last_review_check'] = date('Y-m-d H:i:s');

echo json_encode(['new_reviews' => (int)$row['new_reviews']]);

mysqli_stmt_close($stmt);
mysqli_close($conn);
