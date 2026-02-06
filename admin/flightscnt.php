<?php
session_start();
require_once '../helpers/init_conn_db.php';
require_once '../helpers/admin_auth.php';
require_once '../helpers/flight_helpers.php';

// Check admin authentication
if (!isset($_SESSION['adminId'])) {
    header('Location: login.php');
    exit();
}

// Check admin permissions
if (!checkAdminPermission('view_flights')) {
    header('Location: dashboard.php?error=unauthorized');
    exit();
}

// Handle actions
$action = $_GET['action'] ?? '';
$flight_id = $_GET['id'] ?? 0;
$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';
$message = '';

// Handle flight deletion
if ($action === 'delete' && $flight_id) {
    if (!checkAdminPermission('manage_flights')) {
        header('Location: flightscnt.php?error=unauthorized');
        exit();
    }
    
    $result = deleteFlight($conn, $flight_id);
    if ($result['success']) {
        header('Location: flightscnt.php?success=flight_deleted');
        exit();
    } else {
        header('Location: flightscnt.php?error=' . urlencode($result['message']));
        exit();
    }
}

// Handle flight status change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_status'])) {
    if (!checkAdminPermission('manage_flights')) {
        header('Location: flightscnt.php?error=unauthorized');
        exit();
    }
    
    $flight_id = $_POST['flight_id'];
    $new_status = $_POST['status'];
    
    $result = updateFlightStatus($conn, $flight_id, $new_status);
    if ($result['success']) {
        header('Location: flightscnt.php?success=status_updated');
        exit();
    } else {
        header('Location: flightscnt.php?error=' . urlencode($result['message']));
        exit();
    }
}

// Handle bulk actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_action'])) {
    if (!checkAdminPermission('manage_flights')) {
        header('Location: flightscnt.php?error=unauthorized');
        exit();
    }
    
    $bulk_action = $_POST['bulk_action'];
    $selected_flights = $_POST['selected_flights'] ?? [];
    
    if (empty($selected_flights)) {
        $error = 'No flights selected';
    } else {
        switch ($bulk_action) {
            case 'delete':
                $success_count = 0;
                foreach ($selected_flights as $flight_id) {
                    $result = deleteFlight($conn, $flight_id);
                    if ($result['success']) {
                        $success_count++;
                    }
                }
                $message = "Successfully deleted {$success_count} flights";
                break;
                
            case 'activate':
                $success_count = 0;
                foreach ($selected_flights as $flight_id) {
                    $result = updateFlightStatus($conn, $flight_id, 'scheduled');
                    if ($result['success']) {
                        $success_count++;
                    }
                }
                $message = "Successfully activated {$success_count} flights";
                break;
                
            case 'cancel':
                $success_count = 0;
                foreach ($selected_flights as $flight_id) {
                    $result = updateFlightStatus($conn, $flight_id, 'cancelled');
                    if ($result['success']) {
                        $success_count++;
                    }
                }
                $message = "Successfully cancelled {$success_count} flights";
                break;
        }
        
        if ($success_count > 0) {
            header('Location: flightscnt.php?success=bulk_action_completed&message=' . urlencode($message));
            exit();
        }
    }
}

// Get filter parameters
$filter_departure = $_GET['departure'] ?? '';
$filter_arrival = $_GET['arrival'] ?? '';
$filter_date = $_GET['date'] ?? '';
$filter_status = $_GET['status'] ?? '';
$filter_airline = $_GET['airline'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

// Get flights with filters
$flights = getFlightsWithFilters($conn, [
    'departure' => $filter_departure,
    'arrival' => $filter_arrival,
    'date' => $filter_date,
    'status' => $filter_status,
    'airline' => $filter_airline,
    'limit' => $limit,
    'offset' => $offset
]);

// Get total count for pagination
$total_flights = getTotalFlightsCount($conn, [
    'departure' => $filter_departure,
    'arrival' => $filter_arrival,
    'date' => $filter_date,
    'status' => $filter_status,
    'airline' => $filter_airline
]);

$total_pages = ceil($total_flights / $limit);

// Get filter options
$airlines = getAirlines($conn);
$cities = getCities($conn);
$statuses = [
    'scheduled' => 'Scheduled',
    'active' => 'Active',
    'boarding' => 'Boarding',
    'departed' => 'Departed',
    'arrived' => 'Arrived',
    'delayed' => 'Delayed',
    'cancelled' => 'Cancelled'
];

// Handle success/error messages
if ($success === 'flight_deleted') {
    $message = 'Flight successfully deleted';
} elseif ($success === 'status_updated') {
    $message = 'Flight status updated successfully';
} elseif ($success === 'bulk_action_completed') {
    $message = $_GET['message'] ?? 'Bulk action completed successfully';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Flights | Admin Dashboard 2026</title>
    
    <!-- Modern CSS Framework -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css">
    
    <!-- Custom Admin CSS -->
    <link rel="stylesheet" href="../assets/css/admin.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Space+Grotesk:wght@400;500;600;700&family=JetBrains+Mono:wght@300;400&display=swap" rel="stylesheet">
    
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
            --admin-gray: #6c757d;
            --admin-border: #2d3748;
            --admin-surface: #1a202c;
            --admin-surface-light: #2d3748;
            --admin-text: #e2e8f0;
            --admin-text-secondary: #a0aec0;
            --admin-gradient: linear-gradient(135deg, #4361ee 0%, #3a0ca3 100%);
            --admin-gradient-light: linear-gradient(135deg, #4cc9f0 0%, #4361ee 100%);
            --admin-glass: rgba(26, 32, 44, 0.7);
            --admin-glass-border: rgba(255, 255, 255, 0.1);
            --admin-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.3);
            --admin-shadow-lg: 0 20px 25px -5px rgba(0, 0, 0, 0.4);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: 
                linear-gradient(rgba(15, 23, 42, 0.95), rgba(15, 23, 42, 0.98)),
                url('../assets/images/plane3.jpg') no-repeat center center fixed;
            background-size: cover;
            background-blend-mode: overlay;
            color: var(--admin-text);
            min-height: 100vh;
        }
        
        /* Animated Background */
        .bg-animation {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: var(--admin-gradient);
            opacity: 0.05;
            z-index: -1;
            animation: gradientShift 20s ease infinite;
        }
        
        @keyframes gradientShift {
            0%, 100% { transform: scale(1); opacity: 0.05; }
            50% { transform: scale(1.1); opacity: 0.08; }
        }
        
        /* Main Container */
        .admin-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        /* Header */
        .page-header {
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--admin-border);
        }
        
        .page-title {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 2.5rem;
            font-weight: 700;
            background: linear-gradient(135deg, #fff 0%, #a5b4fc 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 0.5rem;
        }
        
        .page-subtitle {
            color: var(--admin-text-secondary);
            font-size: 1.1rem;
        }
        
        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: var(--admin-glass);
            backdrop-filter: blur(20px);
            border: 1px solid var(--admin-glass-border);
            border-radius: 16px;
            padding: 1.5rem;
            position: relative;
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--admin-shadow-lg);
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: var(--admin-gradient);
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-bottom: 1rem;
        }
        
        .stat-icon.scheduled { background: rgba(67, 97, 238, 0.1); color: var(--admin-primary); }
        .stat-icon.active { background: rgba(6, 214, 160, 0.1); color: var(--admin-success); }
        .stat-icon.delayed { background: rgba(255, 209, 102, 0.1); color: var(--admin-warning); }
        .stat-icon.cancelled { background: rgba(239, 71, 111, 0.1); color: var(--admin-danger); }
        
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: white;
            margin-bottom: 0.25rem;
        }
        
        .stat-label {
            color: var(--admin-text-secondary);
            font-size: 0.95rem;
        }
        
        .stat-change {
            position: absolute;
            top: 1.5rem;
            right: 1.5rem;
            font-size: 0.875rem;
            font-weight: 600;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
        }
        
        .stat-change.positive {
            background: rgba(6, 214, 160, 0.1);
            color: var(--admin-success);
        }
        
        .stat-change.negative {
            background: rgba(239, 71, 111, 0.1);
            color: var(--admin-danger);
        }
        
        /* Action Bar */
        .action-bar {
            background: var(--admin-glass);
            backdrop-filter: blur(20px);
            border: 1px solid var(--admin-glass-border);
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .search-box {
            position: relative;
            flex: 1;
            min-width: 300px;
        }
        
        .search-input {
            width: 100%;
            background: var(--admin-surface-light);
            border: 2px solid var(--admin-border);
            border-radius: 12px;
            color: var(--admin-text);
            padding: 0.875rem 1rem 0.875rem 3rem;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .search-input:focus {
            outline: none;
            border-color: var(--admin-primary);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
        }
        
        .search-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--admin-text-secondary);
        }
        
        .action-buttons {
            display: flex;
            gap: 1rem;
            align-items: center;
        }
        
        .btn {
            padding: 0.875rem 1.5rem;
            border-radius: 12px;
            border: none;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        
        .btn-primary {
            background: var(--admin-gradient);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: var(--admin-shadow);
        }
        
        .btn-secondary {
            background: var(--admin-surface-light);
            color: var(--admin-text);
            border: 1px solid var(--admin-border);
        }
        
        .btn-secondary:hover {
            background: var(--admin-surface);
            transform: translateY(-2px);
        }
        
        .btn-success {
            background: var(--admin-success);
            color: white;
        }
        
        .btn-danger {
            background: var(--admin-danger);
            color: white;
        }
        
        .btn-warning {
            background: var(--admin-warning);
            color: var(--admin-dark);
        }
        
        /* Filter Panel */
        .filter-panel {
            background: var(--admin-glass);
            backdrop-filter: blur(20px);
            border: 1px solid var(--admin-glass-border);
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .filter-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1rem;
        }
        
        .filter-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: white;
        }
        
        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }
        
        .form-group {
            margin-bottom: 0;
        }
        
        .form-label {
            display: block;
            color: var(--admin-text);
            font-weight: 500;
            margin-bottom: 0.5rem;
            font-size: 0.95rem;
        }
        
        .form-select, .form-control {
            width: 100%;
            background: var(--admin-surface-light);
            border: 2px solid var(--admin-border);
            border-radius: 12px;
            color: var(--admin-text);
            padding: 0.75rem 1rem;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }
        
        .form-select:focus, .form-control:focus {
            outline: none;
            border-color: var(--admin-primary);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
        }
        
        /* Table Container */
        .table-container {
            background: var(--admin-glass);
            backdrop-filter: blur(20px);
            border: 1px solid var(--admin-glass-border);
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            overflow: hidden;
            position: relative;
        }
        
        .table-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: var(--admin-gradient);
        }
        
        .table-responsive {
            overflow-x: auto;
        }
        
        .flights-table {
            width: 100%;
            color: var(--admin-text);
            border-collapse: separate;
            border-spacing: 0;
        }
        
        .flights-table thead th {
            background: var(--admin-surface-light);
            color: var(--admin-text);
            font-weight: 600;
            padding: 1rem;
            text-align: left;
            border-bottom: 2px solid var(--admin-border);
            white-space: nowrap;
        }
        
        .flights-table tbody tr {
            transition: background-color 0.3s ease;
        }
        
        .flights-table tbody tr:hover {
            background: rgba(67, 97, 238, 0.05);
        }
        
        .flights-table tbody td {
            padding: 1rem;
            border-bottom: 1px solid var(--admin-border);
            vertical-align: middle;
        }
        
        .flight-number {
            font-family: 'JetBrains Mono', monospace;
            font-weight: 600;
            color: white;
            background: var(--admin-surface-light);
            padding: 0.25rem 0.75rem;
            border-radius: 6px;
            display: inline-block;
        }
        
        .route-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .route-icon {
            color: var(--admin-primary);
            font-size: 1.25rem;
        }
        
        .route-cities {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }
        
        .city-pair {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .departure-city, .arrival-city {
            font-weight: 600;
            color: white;
        }
        
        .city-code {
            color: var(--admin-text-secondary);
            font-size: 0.875rem;
        }
        
        .airline-info {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .airline-logo {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            background: var(--admin-surface-light);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
        }
        
        .airline-name {
            font-weight: 600;
            color: white;
        }
        
        /* Status Badges */
        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .status-badge.scheduled {
            background: rgba(67, 97, 238, 0.1);
            color: var(--admin-primary);
            border: 1px solid rgba(67, 97, 238, 0.3);
        }
        
        .status-badge.active {
            background: rgba(6, 214, 160, 0.1);
            color: var(--admin-success);
            border: 1px solid rgba(6, 214, 160, 0.3);
        }
        
        .status-badge.boarding {
            background: rgba(255, 209, 102, 0.1);
            color: var(--admin-warning);
            border: 1px solid rgba(255, 209, 102, 0.3);
        }
        
        .status-badge.departed {
            background: rgba(76, 201, 240, 0.1);
            color: var(--admin-info);
            border: 1px solid rgba(76, 201, 240, 0.3);
        }
        
        .status-badge.arrived {
            background: rgba(6, 214, 160, 0.1);
            color: var(--admin-success);
            border: 1px solid rgba(6, 214, 160, 0.3);
        }
        
        .status-badge.delayed {
            background: rgba(255, 209, 102, 0.1);
            color: var(--admin-warning);
            border: 1px solid rgba(255, 209, 102, 0.3);
        }
        
        .status-badge.cancelled {
            background: rgba(239, 71, 111, 0.1);
            color: var(--admin-danger);
            border: 1px solid rgba(239, 71, 111, 0.3);
        }
        
        /* Action Buttons */
        .action-buttons-cell {
            display: flex;
            gap: 0.5rem;
        }
        
        .btn-icon {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.875rem;
        }
        
        .btn-icon.view {
            background: rgba(76, 201, 240, 0.1);
            color: var(--admin-info);
        }
        
        .btn-icon.edit {
            background: rgba(6, 214, 160, 0.1);
            color: var(--admin-success);
        }
        
        .btn-icon.delete {
            background: rgba(239, 71, 111, 0.1);
            color: var(--admin-danger);
        }
        
        .btn-icon:hover {
            transform: translateY(-2px);
            box-shadow: var(--admin-shadow);
        }
        
        /* Bulk Actions */
        .bulk-actions {
            background: var(--admin-glass);
            backdrop-filter: blur(20px);
            border: 1px solid var(--admin-glass-border);
            border-radius: 16px;
            padding: 1rem 1.5rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .bulk-select {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .bulk-options {
            display: flex;
            gap: 0.5rem;
        }
        
        /* Checkbox */
        .checkbox-wrapper {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
        }
        
        .custom-checkbox {
            width: 20px;
            height: 20px;
            border: 2px solid var(--admin-border);
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }
        
        .custom-checkbox.checked {
            background: var(--admin-primary);
            border-color: var(--admin-primary);
        }
        
        .custom-checkbox.checked::after {
            content: '✓';
            color: white;
            font-size: 12px;
        }
        
        /* Pagination */
        .pagination-container {
            display: flex;
            justify-content: center;
            margin-top: 2rem;
        }
        
        .pagination {
            display: flex;
            gap: 0.5rem;
            list-style: none;
        }
        
        .page-item {
            margin: 0;
        }
        
        .page-link {
            background: var(--admin-surface-light);
            border: 1px solid var(--admin-border);
            color: var(--admin-text);
            padding: 0.75rem 1rem;
            border-radius: 8px;
            text-decoration: none;
            transition: all 0.3s ease;
            display: block;
        }
        
        .page-link:hover {
            background: var(--admin-surface);
            border-color: var(--admin-primary);
        }
        
        .page-item.active .page-link {
            background: var(--admin-primary);
            border-color: var(--admin-primary);
            color: white;
        }
        
        .page-item.disabled .page-link {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: var(--admin-text-secondary);
        }
        
        .empty-state-icon {
            font-size: 4rem;
            color: var(--admin-border);
            margin-bottom: 1rem;
        }
        
        .empty-state-title {
            font-size: 1.5rem;
            color: var(--admin-text);
            margin-bottom: 0.5rem;
        }
        
        /* Alerts */
        .alert {
            padding: 1rem 1.5rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            animation: slideDown 0.3s ease;
        }
        
        .alert-success {
            background: rgba(6, 214, 160, 0.1);
            border: 1px solid rgba(6, 214, 160, 0.3);
            color: #06d6a0;
        }
        
        .alert-error {
            background: rgba(239, 71, 111, 0.1);
            border: 1px solid rgba(239, 71, 111, 0.3);
            color: #ef476f;
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Modal */
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
        }
        
        .modal-overlay.active {
            display: flex;
        }
        
        .modal {
            background: var(--admin-surface);
            border-radius: 20px;
            border: 1px solid var(--admin-border);
            width: 90%;
            max-width: 500px;
            overflow: hidden;
        }
        
        .modal-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--admin-border);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .modal-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: white;
        }
        
        .modal-close {
            background: none;
            border: none;
            color: var(--admin-text-secondary);
            font-size: 1.5rem;
            cursor: pointer;
            padding: 0;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .modal-close:hover {
            background: var(--admin-surface-light);
            color: white;
        }
        
        .modal-body {
            padding: 1.5rem;
        }
        
        .modal-footer {
            padding: 1.5rem;
            border-top: 1px solid var(--admin-border);
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
        }
        
        /* Responsive Design */
        @media (max-width: 1200px) {
            .admin-container {
                padding: 1rem;
            }
        }
        
        @media (max-width: 768px) {
            .page-title {
                font-size: 2rem;
            }
            
            .action-bar {
                flex-direction: column;
                align-items: stretch;
            }
            
            .search-box {
                min-width: 100%;
            }
            
            .action-buttons {
                justify-content: center;
            }
            
            .filter-grid {
                grid-template-columns: 1fr;
            }
            
            .bulk-actions {
                flex-direction: column;
                align-items: stretch;
            }
            
            .bulk-select, .bulk-options {
                width: 100%;
            }
            
            .bulk-options {
                justify-content: center;
            }
            
            .flights-table thead {
                display: none;
            }
            
            .flights-table tbody td {
                display: block;
                padding: 0.75rem;
                border-bottom: none;
            }
            
            .flights-table tbody tr {
                display: block;
                margin-bottom: 1rem;
                border: 1px solid var(--admin-border);
                border-radius: 12px;
                padding: 1rem;
            }
            
            .action-buttons-cell {
                justify-content: center;
                margin-top: 1rem;
            }
        }
        
        @media (max-width: 480px) {
            .page-title {
                font-size: 1.75rem;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .action-buttons {
                flex-direction: column;
                width: 100%;
            }
            
            .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <!-- Animated Background -->
    <div class="bg-animation"></div>
    
    <!-- Main Container -->
    <div class="admin-container">
        <!-- Back Navigation -->
        <div class="mb-4">
            <a href="dashboard.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>
                Back to Dashboard
            </a>
        </div>
        
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-title">Flight Management</h1>
            <p class="page-subtitle">Manage and monitor all flights in the system</p>
        </div>
        
        <!-- Alerts -->
        <?php if ($message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close ms-auto" onclick="this.parentElement.style.display='none'">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close ms-auto" onclick="this.parentElement.style.display='none'">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        <?php endif; ?>
        
        <!-- Stats Cards -->
        <div class="stats-grid">
            <?php
            $stats = getFlightStats($conn);
            $stat_cards = [
                [
                    'icon' => 'fa-plane',
                    'class' => 'scheduled',
                    'value' => $stats['total_flights'] ?? 0,
                    'label' => 'Total Flights',
                    'change' => '+12%',
                    'trend' => 'positive'
                ],
                [
                    'icon' => 'fa-plane-departure',
                    'class' => 'active',
                    'value' => $stats['active_flights'] ?? 0,
                    'label' => 'Active Today',
                    'change' => '+5%',
                    'trend' => 'positive'
                ],
                [
                    'icon' => 'fa-clock',
                    'class' => 'delayed',
                    'value' => $stats['delayed_flights'] ?? 0,
                    'label' => 'Delayed',
                    'change' => '-3%',
                    'trend' => 'negative'
                ],
                [
                    'icon' => 'fa-ban',
                    'class' => 'cancelled',
                    'value' => $stats['cancelled_flights'] ?? 0,
                    'label' => 'Cancelled',
                    'change' => '+2%',
                    'trend' => 'negative'
                ]
            ];
            
            foreach ($stat_cards as $card):
            ?>
            <div class="stat-card">
                <div class="stat-icon <?php echo $card['class']; ?>">
                    <i class="fas <?php echo $card['icon']; ?>"></i>
                </div>
                <div class="stat-value"><?php echo $card['value']; ?></div>
                <div class="stat-label"><?php echo $card['label']; ?></div>
                <div class="stat-change <?php echo $card['trend']; ?>">
                    <?php echo $card['change']; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Action Bar -->
        <div class="action-bar">
            <div class="search-box">
                <i class="fas fa-search search-icon"></i>
                <input type="text" class="search-input" placeholder="Search flights..." id="searchInput">
            </div>
            
            <div class="action-buttons">
                <a href="add_flight.php" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>
                    Add New Flight
                </a>
                <a href="#" class="btn btn-secondary" id="exportBtn">
                    <i class="fas fa-download me-2"></i>
                    Export
                </a>
            </div>
        </div>
        
        <!-- Filter Panel -->
        <div class="filter-panel">
            <div class="filter-header">
                <h3 class="filter-title">Filters</h3>
                <button type="button" class="btn btn-secondary btn-sm" id="clearFilters">
                    <i class="fas fa-times me-2"></i>
                    Clear Filters
                </button>
            </div>
            
            <form method="GET" action="" id="filterForm">
                <div class="filter-grid">
                    <div class="form-group">
                        <label class="form-label">Departure City</label>
                        <select name="departure" class="form-select" id="filterDeparture">
                            <option value="">All Cities</option>
                            <?php foreach ($cities as $city): ?>
                            <option value="<?php echo htmlspecialchars($city['city']); ?>" 
                                <?php echo ($filter_departure === $city['city']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($city['city']); ?>
                                <?php if (!empty($city['code'])): ?>
                                    (<?php echo htmlspecialchars($city['code']); ?>)
                                <?php endif; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Arrival City</label>
                        <select name="arrival" class="form-select" id="filterArrival">
                            <option value="">All Cities</option>
                            <?php foreach ($cities as $city): ?>
                            <option value="<?php echo htmlspecialchars($city['city']); ?>"
                                <?php echo ($filter_arrival === $city['city']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($city['city']); ?>
                                <?php if (!empty($city['code'])): ?>
                                    (<?php echo htmlspecialchars($city['code']); ?>)
                                <?php endif; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Date</label>
                        <input type="date" 
                               name="date" 
                               class="form-control" 
                               id="filterDate"
                               value="<?php echo htmlspecialchars($filter_date); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select" id="filterStatus">
                            <option value="">All Statuses</option>
                            <?php foreach ($statuses as $key => $label): ?>
                            <option value="<?php echo $key; ?>"
                                <?php echo ($filter_status === $key) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($label); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Airline</label>
                        <select name="airline" class="form-select" id="filterAirline">
                            <option value="">All Airlines</option>
                            <?php foreach ($airlines as $airline): ?>
                            <option value="<?php echo htmlspecialchars($airline['name']); ?>"
                                <?php echo ($filter_airline === $airline['name']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($airline['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">&nbsp;</label>
                        <button type="submit" class="btn btn-primary" style="width: 100%;">
                            <i class="fas fa-filter me-2"></i>
                            Apply Filters
                        </button>
                    </div>
                </div>
            </form>
        </div>
        
        <!-- Bulk Actions -->
        <form method="POST" action="" id="bulkForm">
            <div class="bulk-actions">
                <div class="bulk-select">
                    <label class="checkbox-wrapper">
                        <span class="custom-checkbox" id="selectAllCheckbox"></span>
                        <span>Select All</span>
                    </label>
                    <span id="selectedCount" style="margin-left: 1rem; color: var(--admin-text-secondary);">0 selected</span>
                </div>
                
                <div class="bulk-options">
                    <select name="bulk_action" class="form-select" style="width: auto;">
                        <option value="">Bulk Actions</option>
                        <option value="delete">Delete Selected</option>
                        <option value="activate">Activate Selected</option>
                        <option value="cancel">Cancel Selected</option>
                    </select>
                    <button type="submit" class="btn btn-secondary">
                        Apply
                    </button>
                </div>
            </div>
            
            <!-- Table Container -->
            <div class="table-container">
                <div class="table-responsive">
                    <table class="flights-table">
                        <thead>
                            <tr>
                                <th style="width: 40px;">
                                    <label class="checkbox-wrapper">
                                        <span class="custom-checkbox" id="headerCheckbox"></span>
                                    </label>
                                </th>
                                <th>Flight</th>
                                <th>Route</th>
                                <th>Schedule</th>
                                <th>Airline</th>
                                <th>Status</th>
                                <th>Seats</th>
                                <th>Price</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($flights)): ?>
                            <tr>
                                <td colspan="9">
                                    <div class="empty-state">
                                        <div class="empty-state-icon">
                                            <i class="fas fa-plane-slash"></i>
                                        </div>
                                        <h3 class="empty-state-title">No flights found</h3>
                                        <p>Try adjusting your filters or add a new flight</p>
                                        <a href="add_flight.php" class="btn btn-primary mt-3">
                                            <i class="fas fa-plus me-2"></i>
                                            Add New Flight
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($flights as $flight): ?>
                            <tr>
                                <td>
                                    <label class="checkbox-wrapper">
                                        <input type="checkbox" 
                                               name="selected_flights[]" 
                                               value="<?php echo $flight['flight_id']; ?>"
                                               class="flight-checkbox"
                                               style="display: none;">
                                        <span class="custom-checkbox"></span>
                                    </label>
                                </td>
                                <td>
                                    <span class="flight-number"><?php echo htmlspecialchars($flight['flight_no']); ?></span>
                                </td>
                                <td>
                                    <div class="route-info">
                                        <div class="route-icon">
                                            <i class="fas fa-route"></i>
                                        </div>
                                        <div class="route-cities">
                                            <div class="city-pair">
                                                <span class="departure-city"><?php echo htmlspecialchars($flight['dep_city']); ?></span>
                                                <span class="city-code"><?php echo htmlspecialchars($flight['dep_code'] ?? ''); ?></span>
                                                <i class="fas fa-long-arrow-alt-right text-muted mx-2"></i>
                                                <span class="arrival-city"><?php echo htmlspecialchars($flight['arr_city']); ?></span>
                                                <span class="city-code"><?php echo htmlspecialchars($flight['arr_code'] ?? ''); ?></span>
                                            </div>
                                            <small class="text-muted">
                                                Duration: <?php echo htmlspecialchars($flight['duration']); ?>
                                            </small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div style="display: flex; flex-direction: column; gap: 0.25rem;">
                                        <div>
                                            <small class="text-muted">Departure:</small><br>
                                            <strong><?php echo date('M d, Y', strtotime($flight['source_date'])); ?></strong>
                                            <span class="text-primary"><?php echo date('H:i', strtotime($flight['source_time'])); ?></span>
                                        </div>
                                        <div>
                                            <small class="text-muted">Arrival:</small><br>
                                            <strong><?php echo date('M d, Y', strtotime($flight['dest_date'])); ?></strong>
                                            <span class="text-primary"><?php echo date('H:i', strtotime($flight['dest_time'])); ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="airline-info">
                                        <div class="airline-logo">
                                            <i class="fas fa-plane"></i>
                                        </div>
                                        <div>
                                            <div class="airline-name"><?php echo htmlspecialchars($flight['airline_name']); ?></div>
                                            <small class="text-muted"><?php echo htmlspecialchars($flight['airline_code'] ?? ''); ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="status-badge <?php echo $flight['status'] ?? 'scheduled'; ?>">
                                        <i class="fas 
                                            <?php 
                                            $status_icons = [
                                                'scheduled' => 'fa-clock',
                                                'active' => 'fa-play-circle',
                                                'boarding' => 'fa-users',
                                                'departed' => 'fa-plane-departure',
                                                'arrived' => 'fa-plane-arrival',
                                                'delayed' => 'fa-clock',
                                                'cancelled' => 'fa-ban'
                                            ];
                                            echo $status_icons[$flight['status'] ?? 'scheduled'] ?? 'fa-clock';
                                            ?>
                                        "></i>
                                        <?php echo $statuses[$flight['status'] ?? 'scheduled'] ?? 'Scheduled'; ?>
                                    </span>
                                </td>
                                <td>
                                    <div>
                                        <strong><?php echo $flight['seats']; ?></strong> seats<br>
                                        <small class="text-muted">
                                            Available: <?php echo $flight['available_seats'] ?? $flight['seats']; ?>
                                        </small>
                                    </div>
                                </td>
                                <td>
                                    <strong>KES <?php echo number_format($flight['price'], 0); ?></strong><br>
                                    <small class="text-muted">Economy</small>
                                </td>
                                <td>
                                    <div class="action-buttons-cell">
                                        <a href="view_flight.php?id=<?php echo $flight['flight_id']; ?>" 
                                           class="btn-icon view"
                                           title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="edit_flight.php?id=<?php echo $flight['flight_id']; ?>" 
                                           class="btn-icon edit"
                                           title="Edit Flight">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button type="button" 
                                                class="btn-icon delete"
                                                onclick="confirmDelete(<?php echo $flight['flight_id']; ?>)"
                                                title="Delete Flight">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </form>
        
        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <div class="pagination-container">
            <ul class="pagination">
                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?<?php echo buildPaginationUrl($page - 1); ?>">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                </li>
                
                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                    <a class="page-link" href="?<?php echo buildPaginationUrl($i); ?>">
                        <?php echo $i; ?>
                    </a>
                </li>
                <?php endfor; ?>
                
                <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?<?php echo buildPaginationUrl($page + 1); ?>">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                </li>
            </ul>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Delete Confirmation Modal -->
    <div class="modal-overlay" id="deleteModal">
        <div class="modal">
            <div class="modal-header">
                <h3 class="modal-title">Confirm Deletion</h3>
                <button type="button" class="modal-close" onclick="closeModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this flight? This action cannot be undone.</p>
                <p class="text-muted" style="margin-top: 0.5rem;">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    All bookings for this flight will also be cancelled.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">
                    Cancel
                </button>
                <a href="#" id="confirmDeleteBtn" class="btn btn-danger">
                    Delete Flight
                </a>
            </div>
        </div>
    </div>
    
    <!-- Status Change Modal -->
    <div class="modal-overlay" id="statusModal">
        <div class="modal">
            <form method="POST" action="">
                <input type="hidden" name="flight_id" id="statusFlightId">
                <div class="modal-header">
                    <h3 class="modal-title">Change Flight Status</h3>
                    <button type="button" class="modal-close" onclick="closeModal()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label">Select New Status</label>
                        <select name="status" class="form-select" required>
                            <?php foreach ($statuses as $key => $label): ?>
                            <option value="<?php echo $key; ?>">
                                <?php echo htmlspecialchars($label); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group mt-3">
                        <label class="form-label">Status Message (Optional)</label>
                        <textarea name="status_message" 
                                  class="form-control" 
                                  rows="3"
                                  placeholder="Add any relevant notes about this status change..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">
                        Cancel
                    </button>
                    <button type="submit" name="change_status" class="btn btn-primary">
                        Update Status
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
        // DOM Ready
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize Select2
            $('.form-select').select2({
                theme: 'dark',
                width: '100%'
            });
            
            // Initialize date picker
            flatpickr('#filterDate', {
                dateFormat: 'Y-m-d',
                theme: 'dark'
            });
            
            // Search functionality
            const searchInput = document.getElementById('searchInput');
            if (searchInput) {
                searchInput.addEventListener('input', function(e) {
                    const searchTerm = e.target.value.toLowerCase();
                    const rows = document.querySelectorAll('.flights-table tbody tr');
                    
                    rows.forEach(row => {
                        const text = row.textContent.toLowerCase();
                        row.style.display = text.includes(searchTerm) ? '' : 'none';
                    });
                });
            }
            
            // Checkbox functionality
            const headerCheckbox = document.getElementById('headerCheckbox');
            const selectAllCheckbox = document.getElementById('selectAllCheckbox');
            const flightCheckboxes = document.querySelectorAll('.flight-checkbox');
            const selectedCount = document.getElementById('selectedCount');
            
            function updateSelectedCount() {
                const checked = document.querySelectorAll('.flight-checkbox:checked');
                selectedCount.textContent = `${checked.length} selected`;
            }
            
            function toggleAllCheckboxes(checked) {
                flightCheckboxes.forEach(checkbox => {
                    checkbox.checked = checked;
                    const customCheckbox = checkbox.closest('.checkbox-wrapper').querySelector('.custom-checkbox');
                    customCheckbox.classList.toggle('checked', checked);
                });
                updateSelectedCount();
            }
            
            if (headerCheckbox && selectAllCheckbox) {
                headerCheckbox.addEventListener('click', function() {
                    const isChecked = this.classList.toggle('checked');
                    toggleAllCheckboxes(isChecked);
                });
                
                selectAllCheckbox.addEventListener('click', function() {
                    const isChecked = this.classList.toggle('checked');
                    toggleAllCheckboxes(isChecked);
                });
            }
            
            flightCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    const customCheckbox = this.closest('.checkbox-wrapper').querySelector('.custom-checkbox');
                    customCheckbox.classList.toggle('checked', this.checked);
                    updateSelectedCount();
                    
                    // Update header checkbox state
                    const allChecked = document.querySelectorAll('.flight-checkbox:checked').length === flightCheckboxes.length;
                    if (headerCheckbox) {
                        headerCheckbox.classList.toggle('checked', allChecked);
                    }
                    if (selectAllCheckbox) {
                        selectAllCheckbox.classList.toggle('checked', allChecked);
                    }
                });
            });
            
            // Bulk form submission
            const bulkForm = document.getElementById('bulkForm');
            if (bulkForm) {
                bulkForm.addEventListener('submit', function(e) {
                    const selected = document.querySelectorAll('.flight-checkbox:checked').length;
                    const action = this.querySelector('[name="bulk_action"]').value;
                    
                    if (selected === 0) {
                        e.preventDefault();
                        Swal.fire({
                            icon: 'warning',
                            title: 'No flights selected',
                            text: 'Please select at least one flight to perform this action.',
                            confirmButtonColor: '#4361ee'
                        });
                    } else if (!action) {
                        e.preventDefault();
                        Swal.fire({
                            icon: 'warning',
                            title: 'No action selected',
                            text: 'Please select an action to perform.',
                            confirmButtonColor: '#4361ee'
                        });
                    } else if (action === 'delete') {
                        e.preventDefault();
                        Swal.fire({
                            icon: 'warning',
                            title: 'Confirm Bulk Delete',
                            html: `Are you sure you want to delete ${selected} flight(s)?<br><br>
                                  <small class="text-muted">This action cannot be undone and will cancel all bookings for these flights.</small>`,
                            showCancelButton: true,
                            confirmButtonText: 'Yes, delete them',
                            cancelButtonText: 'Cancel',
                            confirmButtonColor: '#ef476f',
                            cancelButtonColor: '#4361ee'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                this.submit();
                            }
                        });
                    }
                });
            }
            
            // Clear filters
            const clearFiltersBtn = document.getElementById('clearFilters');
            if (clearFiltersBtn) {
                clearFiltersBtn.addEventListener('click', function() {
                    window.location.href = 'flightscnt.php';
                });
            }
            
            // Export functionality
            const exportBtn = document.getElementById('exportBtn');
            if (exportBtn) {
                exportBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const params = new URLSearchParams(window.location.search);
                    params.set('export', 'csv');
                    window.location.href = 'flightscnt.php?' + params.toString();
                });
            }
            
            // Keyboard shortcuts
            document.addEventListener('keydown', function(e) {
                // Ctrl/Cmd + F to focus search
                if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
                    e.preventDefault();
                    if (searchInput) {
                        searchInput.focus();
                    }
                }
                
                // Ctrl/Cmd + A to select all
                if ((e.ctrlKey || e.metaKey) && e.key === 'a') {
                    e.preventDefault();
                    if (headerCheckbox) {
                        headerCheckbox.click();
                    }
                }
                
                // Escape to clear search
                if (e.key === 'Escape' && searchInput && document.activeElement === searchInput) {
                    searchInput.value = '';
                    searchInput.dispatchEvent(new Event('input'));
                }
            });
            
            // Auto-refresh every 60 seconds for real-time updates
            setInterval(() => {
                const searchActive = searchInput && searchInput.value.length > 0;
                if (!searchActive) {
                    window.location.reload();
                }
            }, 60000);
        });
        
        // Modal functions
        function openModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.add('active');
                document.body.style.overflow = 'hidden';
            }
        }
        
        function closeModal() {
            const modals = document.querySelectorAll('.modal-overlay');
            modals.forEach(modal => {
                modal.classList.remove('active');
            });
            document.body.style.overflow = '';
        }
        
        // Delete confirmation
        let flightToDelete = null;
        
        function confirmDelete(flightId) {
            flightToDelete = flightId;
            const deleteBtn = document.getElementById('confirmDeleteBtn');
            if (deleteBtn) {
                deleteBtn.href = `flightscnt.php?action=delete&id=${flightId}`;
            }
            openModal('deleteModal');
        }
        
        // Status change
        function changeStatus(flightId) {
            const statusInput = document.getElementById('statusFlightId');
            if (statusInput) {
                statusInput.value = flightId;
            }
            openModal('statusModal');
        }
        
        // Close modals on outside click
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('modal-overlay')) {
                closeModal();
            }
        });
        
        // Update status badges in real-time
        function updateStatusBadges() {
            const statusBadges = document.querySelectorAll('.status-badge');
            statusBadges.forEach(badge => {
                const status = badge.textContent.trim().toLowerCase();
                const icon = badge.querySelector('i');
                
                // Update icon based on status
                switch(status) {
                    case 'active':
                        if (icon) icon.className = 'fas fa-play-circle me-2';
                        break;
                    case 'boarding':
                        if (icon) icon.className = 'fas fa-users me-2';
                        break;
                    case 'departed':
                        if (icon) icon.className = 'fas fa-plane-departure me-2';
                        break;
                    case 'arrived':
                        if (icon) icon.className = 'fas fa-plane-arrival me-2';
                        break;
                    case 'delayed':
                        if (icon) icon.className = 'fas fa-clock me-2';
                        break;
                    case 'cancelled':
                        if (icon) icon.className = 'fas fa-ban me-2';
                        break;
                }
            });
        }
        
        // Initialize on load
        updateStatusBadges();
    </script>
</body>
</html>
