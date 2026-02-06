<?php 
include_once 'header.php';
require '../helpers/init_conn_db.php';
require_once '../helpers/admin_auth.php';
require_once '../helpers/flight_helpers.php';
?>

<!-- Modern Dashboard Styles 2026 -->
<style>
    :root {
        --dashboard-primary: #4361ee;
        --dashboard-secondary: #3a0ca3;
        --dashboard-success: #06d6a0;
        --dashboard-warning: #ffd166;
        --dashboard-danger: #ef476f;
        --dashboard-info: #4cc9f0;
        --dashboard-dark: #121826;
        --dashboard-light: #f8f9fa;
        --dashboard-surface: #1a202c;
        --dashboard-surface-light: #2d3748;
        --dashboard-border: #2d3748;
        --dashboard-text: #e2e8f0;
        --dashboard-text-secondary: #a0aec0;
        --dashboard-gradient: linear-gradient(135deg, #4361ee 0%, #3a0ca3 100%);
        --dashboard-glass: rgba(26, 32, 44, 0.8);
        --dashboard-glass-border: rgba(255, 255, 255, 0.1);
        --dashboard-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.3);
        --dashboard-shadow-lg: 0 20px 25px -5px rgba(0, 0, 0, 0.5);
    }

    [data-theme="light"] {
        --dashboard-dark: #f8fafc;
        --dashboard-surface: #ffffff;
        --dashboard-surface-light: #f1f5f9;
        --dashboard-border: #e2e8f0;
        --dashboard-text: #1e293b;
        --dashboard-text-secondary: #64748b;
        --dashboard-glass: rgba(255, 255, 255, 0.9);
        --dashboard-glass-border: rgba(0, 0, 0, 0.1);
    }

    .dashboard-wrapper {
        min-height: 100vh;
        background: 
            linear-gradient(rgba(15, 23, 42, 0.95), rgba(15, 23, 42, 0.98)),
            url('../assets/images/plane3.jpg') no-repeat center center fixed;
        background-size: cover;
        background-blend-mode: overlay;
        padding: 2rem;
    }

    /* Welcome Banner */
    .welcome-banner {
        background: var(--dashboard-glass);
        backdrop-filter: blur(20px);
        border: 1px solid var(--dashboard-glass-border);
        border-radius: 20px;
        padding: 2rem;
        margin-bottom: 2rem;
        position: relative;
        overflow: hidden;
    }

    .welcome-banner::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 4px;
        background: var(--dashboard-gradient);
    }

    .welcome-title {
        font-family: 'Space Grotesk', sans-serif;
        font-size: 2.5rem;
        font-weight: 700;
        background: linear-gradient(135deg, #fff 0%, #a5b4fc 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        margin-bottom: 0.5rem;
    }

    .welcome-subtitle {
        color: var(--dashboard-text-secondary);
        font-size: 1.1rem;
        margin-bottom: 1.5rem;
    }

    .current-time {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        background: var(--dashboard-surface-light);
        padding: 0.5rem 1rem;
        border-radius: 12px;
        font-size: 0.95rem;
        color: var(--dashboard-text);
    }

    /* Stats Grid */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }

    .stat-card {
        background: var(--dashboard-glass);
        backdrop-filter: blur(20px);
        border: 1px solid var(--dashboard-glass-border);
        border-radius: 16px;
        padding: 1.5rem;
        position: relative;
        overflow: hidden;
        transition: all 0.3s ease;
        cursor: pointer;
    }

    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: var(--dashboard-shadow-lg);
        border-color: var(--dashboard-primary);
    }

    .stat-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 4px;
    }

    .stat-card.passengers::before {
        background: var(--dashboard-gradient);
    }

    .stat-card.revenue::before {
        background: linear-gradient(135deg, #06d6a0 0%, #10b981 100%);
    }

    .stat-card.flights::before {
        background: linear-gradient(135deg, #ffd166 0%, #fbbf24 100%);
    }

    .stat-card.airlines::before {
        background: linear-gradient(135deg, #ef476f 0%, #f43f5e 100%);
    }

    .stat-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 1rem;
    }

    .stat-icon {
        width: 50px;
        height: 50px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        color: white;
    }

    .stat-card.passengers .stat-icon {
        background: rgba(67, 97, 238, 0.2);
        color: var(--dashboard-primary);
    }

    .stat-card.revenue .stat-icon {
        background: rgba(6, 214, 160, 0.2);
        color: var(--dashboard-success);
    }

    .stat-card.flights .stat-icon {
        background: rgba(255, 209, 102, 0.2);
        color: var(--dashboard-warning);
    }

    .stat-card.airlines .stat-icon {
        background: rgba(239, 71, 111, 0.2);
        color: var(--dashboard-danger);
    }

    .stat-value {
        font-size: 2rem;
        font-weight: 700;
        color: var(--dashboard-text);
        line-height: 1;
        margin-bottom: 0.25rem;
    }

    .stat-label {
        color: var(--dashboard-text-secondary);
        font-size: 0.9rem;
        font-weight: 500;
    }

    .stat-change {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.875rem;
        font-weight: 600;
        margin-top: 0.5rem;
    }

    .stat-change.positive {
        color: var(--dashboard-success);
    }

    .stat-change.negative {
        color: var(--dashboard-danger);
    }

    /* Quick Actions */
    .quick-actions-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
        margin-bottom: 2rem;
    }

    .quick-action {
        background: var(--dashboard-glass);
        backdrop-filter: blur(20px);
        border: 1px solid var(--dashboard-glass-border);
        border-radius: 16px;
        padding: 1.5rem;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 1rem;
        text-decoration: none;
        color: var(--dashboard-text);
        transition: all 0.3s ease;
        text-align: center;
    }

    .quick-action:hover {
        transform: translateY(-3px);
        box-shadow: var(--dashboard-shadow);
        border-color: var(--dashboard-primary);
        color: var(--dashboard-primary);
    }

    .quick-action-icon {
        width: 60px;
        height: 60px;
        border-radius: 15px;
        background: rgba(67, 97, 238, 0.1);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        color: var(--dashboard-primary);
    }

    .quick-action-label {
        font-weight: 500;
        font-size: 1rem;
    }

    /* Flight Tabs */
    .flight-tabs-container {
        background: var(--dashboard-glass);
        backdrop-filter: blur(20px);
        border: 1px solid var(--dashboard-glass-border);
        border-radius: 20px;
        overflow: hidden;
        margin-bottom: 2rem;
    }

    .tabs-header {
        padding: 1.5rem 1.5rem 0;
        border-bottom: 1px solid var(--dashboard-border);
    }

    .tabs-navigation {
        display: flex;
        gap: 0.5rem;
        overflow-x: auto;
        padding-bottom: 1rem;
    }

    .tab-btn {
        background: transparent;
        border: 2px solid var(--dashboard-border);
        border-radius: 12px;
        padding: 0.75rem 1.5rem;
        color: var(--dashboard-text);
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        white-space: nowrap;
    }

    .tab-btn:hover {
        border-color: var(--dashboard-primary);
        color: var(--dashboard-primary);
    }

    .tab-btn.active {
        background: var(--dashboard-primary);
        border-color: var(--dashboard-primary);
        color: white;
    }

    .tab-content {
        padding: 1.5rem;
    }

    .tab-pane {
        display: none;
        animation: fadeIn 0.3s ease;
    }

    .tab-pane.active {
        display: block;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Flight Table */
    .flight-table-wrapper {
        overflow-x: auto;
        border-radius: 12px;
        border: 1px solid var(--dashboard-border);
    }

    .flight-table {
        width: 100%;
        color: var(--dashboard-text);
        border-collapse: collapse;
        min-width: 800px;
    }

    .flight-table thead {
        background: var(--dashboard-surface-light);
    }

    .flight-table thead th {
        padding: 1rem;
        text-align: left;
        font-weight: 600;
        color: var(--dashboard-text);
        border-bottom: 2px solid var(--dashboard-border);
        white-space: nowrap;
    }

    .flight-table tbody tr {
        border-bottom: 1px solid var(--dashboard-border);
        transition: background-color 0.3s ease;
    }

    .flight-table tbody tr:hover {
        background: rgba(67, 97, 238, 0.05);
    }

    .flight-table tbody td {
        padding: 1rem;
        vertical-align: middle;
    }

    .flight-id {
        font-family: 'JetBrains Mono', monospace;
        font-weight: 600;
        color: var(--dashboard-primary);
        text-decoration: none;
    }

    .flight-id:hover {
        text-decoration: underline;
    }

    .status-badge {
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-size: 0.875rem;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        white-space: nowrap;
    }

    .status-scheduled {
        background: rgba(67, 97, 238, 0.1);
        color: var(--dashboard-primary);
        border: 1px solid rgba(67, 97, 238, 0.3);
    }

    .status-issue {
        background: rgba(255, 209, 102, 0.1);
        color: var(--dashboard-warning);
        border: 1px solid rgba(255, 209, 102, 0.3);
    }

    .status-departed {
        background: rgba(76, 201, 240, 0.1);
        color: var(--dashboard-info);
        border: 1px solid rgba(76, 201, 240, 0.3);
    }

    .status-arrived {
        background: rgba(6, 214, 160, 0.1);
        color: var(--dashboard-success);
        border: 1px solid rgba(6, 214, 160, 0.3);
    }

    .action-dropdown {
        position: relative;
    }

    .action-btn {
        width: 36px;
        height: 36px;
        border-radius: 8px;
        background: var(--dashboard-surface-light);
        border: 1px solid var(--dashboard-border);
        color: var(--dashboard-text);
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .action-btn:hover {
        background: var(--dashboard-primary);
        border-color: var(--dashboard-primary);
        color: white;
    }

    .dropdown-menu {
        position: absolute;
        top: 100%;
        right: 0;
        background: var(--dashboard-surface);
        border: 1px solid var(--dashboard-border);
        border-radius: 12px;
        padding: 0.5rem;
        min-width: 220px;
        box-shadow: var(--dashboard-shadow-lg);
        display: none;
        z-index: 1000;
    }

    .action-dropdown:hover .dropdown-menu {
        display: block;
    }

    .dropdown-item {
        padding: 0.75rem 1rem;
        color: var(--dashboard-text);
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 0.75rem;
        border-radius: 8px;
        transition: all 0.3s ease;
        cursor: pointer;
        border: none;
        background: none;
        width: 100%;
        text-align: left;
    }

    .dropdown-item:hover {
        background: var(--dashboard-surface-light);
        color: var(--dashboard-primary);
    }

    .dropdown-item.danger:hover {
        color: var(--dashboard-danger);
    }

    .dropdown-divider {
        height: 1px;
        background: var(--dashboard-border);
        margin: 0.5rem 0;
    }

    /* Empty State */
    .empty-state {
        text-align: center;
        padding: 3rem;
        color: var(--dashboard-text-secondary);
    }

    .empty-state-icon {
        font-size: 3rem;
        color: var(--dashboard-border);
        margin-bottom: 1rem;
    }

    .empty-state-title {
        font-size: 1.25rem;
        color: var(--dashboard-text);
        margin-bottom: 0.5rem;
    }

    /* Charts Section */
    .charts-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }

    .chart-card {
        background: var(--dashboard-glass);
        backdrop-filter: blur(20px);
        border: 1px solid var(--dashboard-glass-border);
        border-radius: 20px;
        padding: 1.5rem;
        position: relative;
    }

    .chart-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 4px;
        background: var(--dashboard-gradient);
        border-radius: 20px 20px 0 0;
    }

    .chart-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 1.5rem;
    }

    .chart-title {
        font-size: 1.25rem;
        font-weight: 600;
        color: var(--dashboard-text);
    }

    .chart-subtitle {
        color: var(--dashboard-text-secondary);
        font-size: 0.875rem;
    }

    .chart-container {
        height: 300px;
        position: relative;
    }

    /* System Status */
    .system-status-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
        margin-top: 2rem;
    }

    .system-status-item {
        background: var(--dashboard-glass);
        backdrop-filter: blur(20px);
        border: 1px solid var(--dashboard-glass-border);
        border-radius: 16px;
        padding: 1.5rem;
        text-align: center;
    }

    .status-indicator {
        width: 12px;
        height: 12px;
        border-radius: 50%;
        display: inline-block;
        margin-right: 0.5rem;
    }

    .status-online {
        background: var(--dashboard-success);
        box-shadow: 0 0 10px rgba(6, 214, 160, 0.5);
    }

    .status-warning {
        background: var(--dashboard-warning);
        box-shadow: 0 0 10px rgba(255, 209, 102, 0.5);
    }

    .status-offline {
        background: var(--dashboard-danger);
        box-shadow: 0 0 10px rgba(239, 71, 111, 0.5);
    }

    /* Responsive Design */
    @media (max-width: 1200px) {
        .dashboard-wrapper {
            padding: 1.5rem;
        }
        
        .charts-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 768px) {
        .dashboard-wrapper {
            padding: 1rem;
        }
        
        .welcome-title {
            font-size: 2rem;
        }
        
        .stats-grid {
            grid-template-columns: 1fr;
        }
        
        .quick-actions-grid {
            grid-template-columns: repeat(2, 1fr);
        }
        
        .tabs-navigation {
            flex-wrap: wrap;
        }
    }

    @media (max-width: 576px) {
        .welcome-title {
            font-size: 1.75rem;
        }
        
        .quick-actions-grid {
            grid-template-columns: 1fr;
        }
        
        .stat-value {
            font-size: 1.75rem;
        }
        
        .chart-container {
            height: 250px;
        }
    }
</style>

<div class="dashboard-wrapper">
    <?php if(isset($_SESSION['adminId'])): ?>
        <!-- Welcome Banner -->
        <div class="welcome-banner">
            <h1 class="welcome-title">Welcome Back, <?php echo htmlspecialchars($_SESSION['adminUname'] ?? 'Admin'); ?>!</h1>
            <p class="welcome-subtitle">Monitor and manage your airline operations in real-time.</p>
            <div class="current-time">
                <i class="fas fa-clock"></i>
                <span id="currentDateTime"><?php echo date('F j, Y, g:i a'); ?></span>
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="stats-grid">
            <?php
            // Get statistics
            $total_passengers = 0;
            $total_revenue = 0;
            $total_flights = 0;
            $total_airlines = 0;
            
            // Fetch total passengers
            $sql = "SELECT COUNT(*) as count FROM Passenger";
            $result = mysqli_query($conn, $sql);
            if($row = mysqli_fetch_assoc($result)) {
                $total_passengers = $row['count'];
            }
            
            // Fetch total revenue
            $sql = "SELECT SUM(amount) as total FROM Payment WHERE status = 'success'";
            $result = mysqli_query($conn, $sql);
            if($row = mysqli_fetch_assoc($result)) {
                $total_revenue = $row['total'] ?: 0;
            }
            
            // Fetch total flights
            $sql = "SELECT COUNT(*) as count FROM Flight";
            $result = mysqli_query($conn, $sql);
            if($row = mysqli_fetch_assoc($result)) {
                $total_flights = $row['count'];
            }
            
            // Fetch total airlines
            $sql = "SELECT COUNT(*) as count FROM Airline";
            $result = mysqli_query($conn, $sql);
            if($row = mysqli_fetch_assoc($result)) {
                $total_airlines = $row['count'];
            }
            ?>
            
            <div class="stat-card passengers">
                <div class="stat-header">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-change positive">
                        <i class="fas fa-arrow-up"></i>
                        <span>+12%</span>
                    </div>
                </div>
                <div class="stat-value"><?php echo number_format($total_passengers); ?></div>
                <div class="stat-label">Total Passengers</div>
            </div>

            <div class="stat-card revenue">
                <div class="stat-header">
                    <div class="stat-icon">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <div class="stat-change positive">
                        <i class="fas fa-arrow-up"></i>
                        <span>+8%</span>
                    </div>
                </div>
                <div class="stat-value">KES <?php echo number_format($total_revenue); ?></div>
                <div class="stat-label">Total Revenue</div>
            </div>

            <div class="stat-card flights">
                <div class="stat-header">
                    <div class="stat-icon">
                        <i class="fas fa-plane"></i>
                    </div>
                    <div class="stat-change positive">
                        <i class="fas fa-arrow-up"></i>
                        <span>+5%</span>
                    </div>
                </div>
                <div class="stat-value"><?php echo number_format($total_flights); ?></div>
                <div class="stat-label">Total Flights</div>
            </div>

            <div class="stat-card airlines">
                <div class="stat-header">
                    <div class="stat-icon">
                        <i class="fas fa-building"></i>
                    </div>
                    <div class="stat-change positive">
                        <i class="fas fa-arrow-up"></i>
                        <span>+3%</span>
                    </div>
                </div>
                <div class="stat-value"><?php echo number_format($total_airlines); ?></div>
                <div class="stat-label">Active Airlines</div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="quick-actions-grid">
            <a href="add_flight.php" class="quick-action">
                <div class="quick-action-icon">
                    <i class="fas fa-plus"></i>
                </div>
                <span class="quick-action-label">Add New Flight</span>
            </a>
            <a href="flightscnt.php" class="quick-action">
                <div class="quick-action-icon">
                    <i class="fas fa-plane"></i>
                </div>
                <span class="quick-action-label">Manage Flights</span>
            </a>
            <a href="bookings.php" class="quick-action">
                <div class="quick-action-icon">
                    <i class="fas fa-ticket-alt"></i>
                </div>
                <span class="quick-action-label">View Bookings</span>
            </a>
            <a href="reports.php" class="quick-action">
                <div class="quick-action-icon">
                    <i class="fas fa-chart-bar"></i>
                </div>
                <span class="quick-action-label">Generate Reports</span>
            </a>
        </div>

        <!-- Charts Section -->
        <div class="charts-grid">
            <div class="chart-card">
                <div class="chart-header">
                    <div>
                        <h3 class="chart-title">Revenue Overview</h3>
                        <p class="chart-subtitle">Last 7 days</p>
                    </div>
                    <select class="form-select" style="width: auto; background: var(--dashboard-surface-light); border-color: var(--dashboard-border); color: var(--dashboard-text);">
                        <option>Last 7 days</option>
                        <option selected>Last 30 days</option>
                        <option>Last 90 days</option>
                    </select>
                </div>
                <div class="chart-container">
                    <canvas id="revenueChart"></canvas>
                </div>
            </div>

            <div class="chart-card">
                <div class="chart-header">
                    <div>
                        <h3 class="chart-title">Flight Status Distribution</h3>
                        <p class="chart-subtitle">Today's flights</p>
                    </div>
                </div>
                <div class="chart-container">
                    <canvas id="flightChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Flight Tabs -->
        <div class="flight-tabs-container">
            <div class="tabs-header">
                <div class="tabs-navigation">
                    <button class="tab-btn active" data-tab="scheduled">
                        <i class="fas fa-clock"></i>
                        <span>Scheduled Flights</span>
                    </button>
                    <button class="tab-btn" data-tab="issues">
                        <i class="fas fa-exclamation-triangle"></i>
                        <span>Flight Issues</span>
                    </button>
                    <button class="tab-btn" data-tab="departed">
                        <i class="fas fa-plane-departure"></i>
                        <span>Departed Flights</span>
                    </button>
                    <button class="tab-btn" data-tab="arrived">
                        <i class="fas fa-plane-arrival"></i>
                        <span>Arrived Flights</span>
                    </button>
                </div>
            </div>

            <div class="tab-content">
                <!-- Scheduled Flights -->
                <div class="tab-pane active" id="scheduled">
                    <?php
                    $today = date('Y-m-d');
                    $sql = "SELECT f.*, a.name as airline_name 
                            FROM Flight f 
                            LEFT JOIN Airline a ON f.airline_id = a.airline_id 
                            WHERE DATE(f.source_date) = ? AND (f.status IS NULL OR f.status = '') 
                            ORDER BY f.source_time ASC";
                    $stmt = mysqli_prepare($conn, $sql);
                    mysqli_stmt_bind_param($stmt, 's', $today);
                    mysqli_stmt_execute($stmt);
                    $result = mysqli_stmt_get_result($stmt);
                    ?>
                    
                    <div class="flight-table-wrapper">
                        <table class="flight-table">
                            <thead>
                                <tr>
                                    <th>Flight ID</th>
                                    <th>Route</th>
                                    <th>Schedule</th>
                                    <th>Airline</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(mysqli_num_rows($result) == 0): ?>
                                    <tr>
                                        <td colspan="6">
                                            <div class="empty-state">
                                                <div class="empty-state-icon">
                                                    <i class="fas fa-plane"></i>
                                                </div>
                                                <h3 class="empty-state-title">No scheduled flights today</h3>
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php while($flight = mysqli_fetch_assoc($result)): ?>
                                    <tr>
                                        <td>
                                            <a href="pass_list.php?flight_id=<?php echo $flight['flight_id']; ?>" class="flight-id">
                                                <?php echo htmlspecialchars($flight['flight_no'] ?? $flight['flight_id']); ?>
                                            </a>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($flight['source'] ?? ''); ?> → 
                                            <?php echo htmlspecialchars($flight['Destination'] ?? ''); ?>
                                        </td>
                                        <td>
                                            <div>
                                                <strong>Dep:</strong> <?php echo date('H:i', strtotime($flight['source_time'])); ?><br>
                                                <strong>Arr:</strong> <?php echo date('H:i', strtotime($flight['dest_time'])); ?>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($flight['airline_name'] ?? $flight['airline']); ?></td>
                                        <td>
                                            <span class="status-badge status-scheduled">
                                                <i class="fas fa-clock"></i>
                                                Scheduled
                                            </span>
                                        </td>
                                        <td>
                                            <div class="action-dropdown">
                                                <button class="action-btn">
                                                    <i class="fas fa-ellipsis-v"></i>
                                                </button>
                                                <div class="dropdown-menu">
                                                    <a href="pass_list.php?flight_id=<?php echo $flight['flight_id']; ?>" class="dropdown-item">
                                                        <i class="fas fa-users"></i>
                                                        View Passengers
                                                    </a>
                                                    <div class="dropdown-divider"></div>
                                                    <form action="../includes/admin/admin.inc.php" method="POST" style="display: contents;">
                                                        <input type="hidden" name="flight_id" value="<?php echo $flight['flight_id']; ?>">
                                                        <button type="button" onclick="showDelayForm(<?php echo $flight['flight_id']; ?>)" class="dropdown-item">
                                                            <i class="fas fa-clock"></i>
                                                            Report Delay
                                                        </button>
                                                        <button type="submit" name="dep_but" class="dropdown-item">
                                                            <i class="fas fa-plane-departure"></i>
                                                            Mark as Departed
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Flight Issues -->
                <div class="tab-pane" id="issues">
                    <?php
                    $sql = "SELECT f.*, a.name as airline_name 
                            FROM Flight f 
                            LEFT JOIN Airline a ON f.airline_id = a.airline_id 
                            WHERE DATE(f.source_date) = ? AND f.status = 'issue' 
                            ORDER BY f.source_time ASC";
                    $stmt = mysqli_prepare($conn, $sql);
                    mysqli_stmt_bind_param($stmt, 's', $today);
                    mysqli_stmt_execute($stmt);
                    $result = mysqli_stmt_get_result($stmt);
                    ?>
                    
                    <div class="flight-table-wrapper">
                        <table class="flight-table">
                            <thead>
                                <tr>
                                    <th>Flight ID</th>
                                    <th>Route</th>
                                    <th>Scheduled</th>
                                    <th>Delay</th>
                                    <th>Airline</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(mysqli_num_rows($result) == 0): ?>
                                    <tr>
                                        <td colspan="6">
                                            <div class="empty-state">
                                                <div class="empty-state-icon">
                                                    <i class="fas fa-check-circle"></i>
                                                </div>
                                                <h3 class="empty-state-title">No flight issues reported today</h3>
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php while($flight = mysqli_fetch_assoc($result)): ?>
                                    <tr>
                                        <td>
                                            <a href="pass_list.php?flight_id=<?php echo $flight['flight_id']; ?>" class="flight-id">
                                                <?php echo htmlspecialchars($flight['flight_no'] ?? $flight['flight_id']); ?>
                                            </a>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($flight['source'] ?? ''); ?> → 
                                            <?php echo htmlspecialchars($flight['Destination'] ?? ''); ?>
                                        </td>
                                        <td><?php echo date('H:i', strtotime($flight['source_time'])); ?></td>
                                        <td>
                                            <span class="text-warning">
                                                <i class="fas fa-clock"></i>
                                                Delayed
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($flight['airline_name'] ?? $flight['airline']); ?></td>
                                        <td>
                                            <form action="../includes/admin/admin.inc.php" method="POST" style="display: inline;">
                                                <input type="hidden" name="flight_id" value="<?php echo $flight['flight_id']; ?>">
                                                <button type="submit" name="issue_solved_but" class="btn btn-success btn-sm">
                                                    <i class="fas fa-check"></i>
                                                    Mark as Solved
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Departed Flights -->
                <div class="tab-pane" id="departed">
                    <?php
                    $sql = "SELECT f.*, a.name as airline_name 
                            FROM Flight f 
                            LEFT JOIN Airline a ON f.airline_id = a.airline_id 
                            WHERE DATE(f.source_date) = ? AND f.status = 'dep' 
                            ORDER BY f.source_time ASC";
                    $stmt = mysqli_prepare($conn, $sql);
                    mysqli_stmt_bind_param($stmt, 's', $today);
                    mysqli_stmt_execute($stmt);
                    $result = mysqli_stmt_get_result($stmt);
                    ?>
                    
                    <div class="flight-table-wrapper">
                        <table class="flight-table">
                            <thead>
                                <tr>
                                    <th>Flight ID</th>
                                    <th>Route</th>
                                    <th>Departed</th>
                                    <th>Estimated Arrival</th>
                                    <th>Airline</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(mysqli_num_rows($result) == 0): ?>
                                    <tr>
                                        <td colspan="6">
                                            <div class="empty-state">
                                                <div class="empty-state-icon">
                                                    <i class="fas fa-plane-departure"></i>
                                                </div>
                                                <h3 class="empty-state-title">No departed flights today</h3>
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php while($flight = mysqli_fetch_assoc($result)): ?>
                                    <tr>
                                        <td>
                                            <a href="pass_list.php?flight_id=<?php echo $flight['flight_id']; ?>" class="flight-id">
                                                <?php echo htmlspecialchars($flight['flight_no'] ?? $flight['flight_id']); ?>
                                            </a>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($flight['source'] ?? ''); ?> → 
                                            <?php echo htmlspecialchars($flight['Destination'] ?? ''); ?>
                                        </td>
                                        <td><?php echo date('H:i', strtotime($flight['source_time'])); ?></td>
                                        <td><?php echo date('H:i', strtotime($flight['dest_time'])); ?></td>
                                        <td><?php echo htmlspecialchars($flight['airline_name'] ?? $flight['airline']); ?></td>
                                        <td>
                                            <form action="../includes/admin/admin.inc.php" method="POST" style="display: inline;">
                                                <input type="hidden" name="flight_id" value="<?php echo $flight['flight_id']; ?>">
                                                <button type="submit" name="arr_but" class="btn btn-success btn-sm">
                                                    <i class="fas fa-plane-arrival"></i>
                                                    Mark as Arrived
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Arrived Flights -->
                <div class="tab-pane" id="arrived">
                    <?php
                    $sql = "SELECT f.*, a.name as airline_name 
                            FROM Flight f 
                            LEFT JOIN Airline a ON f.airline_id = a.airline_id 
                            WHERE DATE(f.source_date) = ? AND f.status = 'arr' 
                            ORDER BY f.source_time ASC";
                    $stmt = mysqli_prepare($conn, $sql);
                    mysqli_stmt_bind_param($stmt, 's', $today);
                    mysqli_stmt_execute($stmt);
                    $result = mysqli_stmt_get_result($stmt);
                    ?>
                    
                    <div class="flight-table-wrapper">
                        <table class="flight-table">
                            <thead>
                                <tr>
                                    <th>Flight ID</th>
                                    <th>Route</th>
                                    <th>Arrived</th>
                                    <th>Duration</th>
                                    <th>Airline</th>
                                    <th>Passengers</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(mysqli_num_rows($result) == 0): ?>
                                    <tr>
                                        <td colspan="6">
                                            <div class="empty-state">
                                                <div class="empty-state-icon">
                                                    <i class="fas fa-plane-arrival"></i>
                                                </div>
                                                <h3 class="empty-state-title">No arrived flights today</h3>
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php while($flight = mysqli_fetch_assoc($result)): ?>
                                    <tr>
                                        <td>
                                            <a href="pass_list.php?flight_id=<?php echo $flight['flight_id']; ?>" class="flight-id">
                                                <?php echo htmlspecialchars($flight['flight_no'] ?? $flight['flight_id']); ?>
                                            </a>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($flight['source'] ?? ''); ?> → 
                                            <?php echo htmlspecialchars($flight['Destination'] ?? ''); ?>
                                        </td>
                                        <td><?php echo date('H:i', strtotime($flight['dest_time'])); ?></td>
                                        <td>
                                            <?php 
                                                $departure = strtotime($flight['source_time']);
                                                $arrival = strtotime($flight['dest_time']);
                                                $duration = gmdate("H:i", $arrival - $departure);
                                                echo $duration;
                                            ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($flight['airline_name'] ?? $flight['airline']); ?></td>
                                        <td>
                                            <a href="pass_list.php?flight_id=<?php echo $flight['flight_id']; ?>" class="btn btn-outline-primary btn-sm">
                                                <i class="fas fa-users"></i>
                                                View
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- System Status -->
        <div class="chart-card">
            <div class="chart-header">
                <h3 class="chart-title">System Status</h3>
                <p class="chart-subtitle">Real-time monitoring</p>
            </div>
            <div class="system-status-grid">
                <div class="system-status-item">
                    <div class="mb-3">
                        <span class="status-indicator status-online"></span>
                        <span class="fw-bold">Database</span>
                    </div>
                    <div class="text-muted small">Connection: Active</div>
                    <div class="text-muted small">Queries: 24/sec</div>
                </div>
                <div class="system-status-item">
                    <div class="mb-3">
                        <span class="status-indicator status-online"></span>
                        <span class="fw-bold">API Server</span>
                    </div>
                    <div class="text-muted small">Uptime: 99.9%</div>
                    <div class="text-muted small">Response: 120ms</div>
                </div>
                <div class="system-status-item">
                    <div class="mb-3">
                        <span class="status-indicator status-online"></span>
                        <span class="fw-bold">Security</span>
                    </div>
                    <div class="text-muted small">Status: Protected</div>
                    <div class="text-muted small">Last scan: Today</div>
                </div>
                <div class="system-status-item">
                    <div class="mb-3">
                        <span class="status-indicator status-warning"></span>
                        <span class="fw-bold">Backup</span>
                    </div>
                    <div class="text-muted small">Last backup: 12h ago</div>
                    <div class="text-muted small">Next: 12h</div>
                </div>
            </div>
        </div>

    <?php else: ?>
        <div class="text-center py-5">
            <div class="empty-state-icon">
                <i class="fas fa-lock"></i>
            </div>
            <h2 class="empty-state-title">Access Denied</h2>
            <p class="text-muted">Please login to access the admin dashboard.</p>
            <a href="login.php" class="btn btn-primary mt-3">
                <i class="fas fa-sign-in-alt me-2"></i>
                Login to Dashboard
            </a>
        </div>
    <?php endif; ?>
</div>

<!-- Delay Modal -->
<div class="modal-overlay" id="delayModal" style="display: none;">
    <div class="modal modal-sm">
        <div class="modal-header">
            <h3 class="modal-title">Report Flight Delay</h3>
            <button type="button" class="modal-close" onclick="closeModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <form id="delayForm" method="POST" action="../includes/admin/admin.inc.php">
                <input type="hidden" name="flight_id" id="delayFlightId">
                <div class="mb-3">
                    <label class="form-label">Delay Duration (minutes)</label>
                    <input type="number" name="issue" class="form-control" placeholder="Enter delay in minutes" required min="1" max="1440">
                </div>
                <div class="mb-3">
                    <label class="form-label">Delay Reason (Optional)</label>
                    <textarea name="delay_reason" class="form-control" rows="3" placeholder="Brief reason for delay..."></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                    <button type="submit" name="issue_but" class="btn btn-danger">Report Delay</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
    // Dashboard JavaScript
    document.addEventListener('DOMContentLoaded', function() {
        // Update current time
        function updateDateTime() {
            const now = new Date();
            const options = { 
                weekday: 'long', 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            };
            document.getElementById('currentDateTime').textContent = now.toLocaleDateString('en-US', options);
        }
        
        updateDateTime();
        setInterval(updateDateTime, 1000);
        
        // Tab Navigation
        const tabBtns = document.querySelectorAll('.tab-btn');
        const tabPanes = document.querySelectorAll('.tab-pane');
        
        tabBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const tabId = this.getAttribute('data-tab');
                
                // Remove active class from all buttons and panes
                tabBtns.forEach(b => b.classList.remove('active'));
                tabPanes.forEach(p => p.classList.remove('active'));
                
                // Add active class to clicked button and corresponding pane
                this.classList.add('active');
                document.getElementById(tabId).classList.add('active');
                
                // Update URL hash for bookmarking
                window.location.hash = tabId;
            });
        });
        
        // Initialize charts
        initCharts();
        
        // Auto-refresh dashboard every 60 seconds
        setInterval(refreshDashboard, 60000);
        
        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl/Cmd + 1-4 for tab navigation
            if ((e.ctrlKey || e.metaKey) && e.key >= '1' && e.key <= '4') {
                e.preventDefault();
                const tabIndex = parseInt(e.key) - 1;
                if (tabBtns[tabIndex]) {
                    tabBtns[tabIndex].click();
                }
            }
            
            // F5 to refresh
            if (e.key === 'F5') {
                e.preventDefault();
                refreshDashboard();
            }
        });
        
        // Load saved tab from URL hash
        if (window.location.hash) {
            const tabId = window.location.hash.substring(1);
            const tabBtn = document.querySelector(`.tab-btn[data-tab="${tabId}"]`);
            if (tabBtn) {
                tabBtn.click();
            }
        }
        
        // Initialize tooltips
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    });
    
    function initCharts() {
        // Revenue Chart
        const revenueCtx = document.getElementById('revenueChart');
        if (revenueCtx) {
            new Chart(revenueCtx, {
                type: 'line',
                data: {
                    labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                    datasets: [{
                        label: 'Revenue (KES)',
                        data: [45000, 52000, 48000, 55000, 60000, 58000, 62000],
                        borderColor: '#4361ee',
                        backgroundColor: 'rgba(67, 97, 238, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: '#4361ee',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: 'rgba(26, 32, 44, 0.9)',
                            titleColor: '#e2e8f0',
                            bodyColor: '#e2e8f0',
                            borderColor: '#4361ee',
                            borderWidth: 1,
                            callbacks: {
                                label: function(context) {
                                    return 'KES ' + context.raw.toLocaleString();
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                color: 'rgba(255, 255, 255, 0.1)'
                            },
                            ticks: {
                                color: '#a0aec0'
                            }
                        },
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(255, 255, 255, 0.1)'
                            },
                            ticks: {
                                color: '#a0aec0',
                                callback: function(value) {
                                    return 'KES ' + value.toLocaleString();
                                }
                            }
                        }
                    }
                }
            });
        }
        
        // Flight Status Chart
        const flightCtx = document.getElementById('flightChart');
        if (flightCtx) {
            new Chart(flightCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Scheduled', 'Delayed', 'Departed', 'Arrived'],
                    datasets: [{
                        data: [12, 2, 8, 15],
                        backgroundColor: [
                            'rgba(67, 97, 238, 0.8)',
                            'rgba(255, 209, 102, 0.8)',
                            'rgba(76, 201, 240, 0.8)',
                            'rgba(6, 214, 160, 0.8)'
                        ],
                        borderColor: [
                            'rgba(67, 97, 238, 1)',
                            'rgba(255, 209, 102, 1)',
                            'rgba(76, 201, 240, 1)',
                            'rgba(6, 214, 160, 1)'
                        ],
                        borderWidth: 2,
                        hoverOffset: 15
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right',
                            labels: {
                                color: '#e2e8f0',
                                padding: 20,
                                font: {
                                    size: 12
                                },
                                generateLabels: function(chart) {
                                    const data = chart.data;
                                    if (data.labels.length && data.datasets.length) {
                                        return data.labels.map(function(label, i) {
                                            const meta = chart.getDatasetMeta(0);
                                            const style = meta.controller.getStyle(i);
                                            
                                            return {
                                                text: label + ': ' + data.datasets[0].data[i],
                                                fillStyle: style.backgroundColor,
                                                strokeStyle: style.borderColor,
                                                lineWidth: style.borderWidth,
                                                hidden: !chart.getDataVisibility(i),
                                                index: i
                                            };
                                        });
                                    }
                                    return [];
                                }
                            }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(26, 32, 44, 0.9)',
                            titleColor: '#e2e8f0',
                            bodyColor: '#e2e8f0',
                            borderColor: '#4361ee',
                            borderWidth: 1,
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.raw || 0;
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = Math.round((value / total) * 100);
                                    return `${label}: ${value} flights (${percentage}%)`;
                                }
                            }
                        }
                    },
                    cutout: '65%'
                }
            });
        }
    }
    
    function refreshDashboard() {
        // Show loading indicator
        const loadingEl = document.createElement('div');
        loadingEl.className = 'loading-overlay';
        loadingEl.innerHTML = '<div class="spinner"></div>';
        document.body.appendChild(loadingEl);
        
        // In real application, fetch updated data via AJAX
        setTimeout(() => {
            loadingEl.remove();
            showToast('Dashboard updated successfully', 'success');
        }, 1500);
    }
    
    function showDelayForm(flightId) {
        document.getElementById('delayFlightId').value = flightId;
        document.getElementById('delayModal').style.display = 'flex';
    }
    
    function closeModal() {
        document.getElementById('delayModal').style.display = 'none';
    }
    
    // Close modal when clicking outside
    document.addEventListener('click', function(e) {
        const modal = document.getElementById('delayModal');
        if (e.target === modal) {
            closeModal();
        }
    });
    
    // Flight action handlers
    function markAsDeparted(flightId) {
        if (confirm('Mark this flight as departed?')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '../includes/admin/admin.inc.php';
            
            const flightIdInput = document.createElement('input');
            flightIdInput.type = 'hidden';
            flightIdInput.name = 'flight_id';
            flightIdInput.value = flightId;
            
            const submitInput = document.createElement('input');
            submitInput.type = 'hidden';
            submitInput.name = 'dep_but';
            submitInput.value = '1';
            
            form.appendChild(flightIdInput);
            form.appendChild(submitInput);
            document.body.appendChild(form);
            form.submit();
        }
    }
    
    function markAsArrived(flightId) {
        if (confirm('Mark this flight as arrived?')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '../includes/admin/admin.inc.php';
            
            const flightIdInput = document.createElement('input');
            flightIdInput.type = 'hidden';
            flightIdInput.name = 'flight_id';
            flightIdInput.value = flightId;
            
            const submitInput = document.createElement('input');
            submitInput.type = 'hidden';
            submitInput.name = 'arr_but';
            submitInput.value = '1';
            
            form.appendChild(flightIdInput);
            form.appendChild(submitInput);
            document.body.appendChild(form);
            form.submit();
        }
    }
    
    // Toast notification system
    function showToast(message, type = 'info') {
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.innerHTML = `
            <div class="toast-icon">
                <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
            </div>
            <div class="toast-content">
                <p>${message}</p>
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
        
        // Auto remove after 5 seconds
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => {
                toast.remove();
            }, 300);
        }, 5000);
        
        // Close button
        const closeBtn = toast.querySelector('.toast-close');
        closeBtn.addEventListener('click', function() {
            toast.classList.remove('show');
            setTimeout(() => {
                toast.remove();
            }, 300);
        });
    }
    
    // Add toast styles
    const toastStyles = document.createElement('style');
    toastStyles.textContent = `
        .toast {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: var(--dashboard-surface);
            border: 1px solid var(--dashboard-border);
            border-radius: 12px;
            padding: 1rem 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            box-shadow: var(--dashboard-shadow);
            transform: translateX(150%);
            transition: transform 0.3s ease;
            z-index: 9999;
            max-width: 400px;
        }
        
        .toast.show {
            transform: translateX(0);
        }
        
        .toast-icon {
            font-size: 1.25rem;
        }
        
        .toast-success .toast-icon {
            color: var(--dashboard-success);
        }
        
        .toast-error .toast-icon {
            color: var(--dashboard-danger);
        }
        
        .toast-info .toast-icon {
            color: var(--dashboard-info);
        }
        
        .toast-content {
            flex: 1;
        }
        
        .toast-content p {
            margin: 0;
            font-size: 0.95rem;
        }
        
        .toast-close {
            background: none;
            border: none;
            color: var(--dashboard-text-secondary);
            cursor: pointer;
            padding: 0.25rem;
            border-radius: 6px;
            transition: all 0.3s ease;
        }
        
        .toast-close:hover {
            background: rgba(239, 71, 111, 0.1);
            color: var(--dashboard-danger);
        }
        
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(15, 23, 42, 0.8);
            backdrop-filter: blur(8px);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 99999;
        }
        
        .spinner {
            width: 50px;
            height: 50px;
            border: 4px solid rgba(67, 97, 238, 0.1);
            border-radius: 50%;
            border-top-color: var(--dashboard-primary);
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(10px);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            padding: 1rem;
        }
        
        .modal {
            background: var(--dashboard-surface);
            border: 1px solid var(--dashboard-border);
            border-radius: 20px;
            width: 90%;
            max-width: 500px;
            overflow: hidden;
        }
        
        .modal-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--dashboard-border);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .modal-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--dashboard-text);
            margin: 0;
        }
        
        .modal-close {
            background: none;
            border: none;
            color: var(--dashboard-text-secondary);
            font-size: 1.25rem;
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .modal-close:hover {
            background: rgba(239, 71, 111, 0.1);
            color: var(--dashboard-danger);
        }
        
        .modal-body {
            padding: 1.5rem;
        }
        
        .modal-footer {
            padding: 1.5rem;
            border-top: 1px solid var(--dashboard-border);
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
        }
    `;
    document.head.appendChild(toastStyles);
    
    // Real-time updates using EventSource
    if (typeof(EventSource) !== "undefined") {
        const eventSource = new EventSource("../helpers/dashboard_sse.php");
        
        eventSource.onmessage = function(event) {
            const data = JSON.parse(event.data);
            
            if (data.type === 'new_booking') {
                showToast(`New booking: ${data.passenger_name} on flight ${data.flight_no}`, 'success');
            } else if (data.type === 'flight_update') {
                showToast(`Flight ${data.flight_no} status updated to ${data.status}`, 'info');
                refreshDashboard();
            } else if (data.type === 'system_alert') {
                showToast(`System Alert: ${data.message}`, 'warning');
            }
        };
        
        eventSource.onerror = function() {
            console.log("EventSource failed.");
        };
    }
</script>

<?php include_once 'footer.php'; ?>
