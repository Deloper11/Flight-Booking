<?php
session_start();
require '../helpers/init_conn_db.php';

// Security check for admin access
if (!isset($_SESSION['adminId'])) {
    header('Location: ../index.php');
    exit();
}

// Handle airline deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['del_airlines'])) {
    $airline_id = filter_var($_POST['airline_id'], FILTER_VALIDATE_INT);
    
    if ($airline_id) {
        // Check if airline has existing flights before deletion
        $check_sql = 'SELECT COUNT(*) as flight_count FROM flight WHERE airline_id = ?';
        $check_stmt = mysqli_prepare($conn, $check_sql);
        mysqli_stmt_bind_param($check_stmt, 'i', $airline_id);
        mysqli_stmt_execute($check_stmt);
        $result = mysqli_stmt_get_result($check_stmt);
        $row = mysqli_fetch_assoc($result);
        
        if ($row['flight_count'] > 0) {
            $_SESSION['error'] = 'Cannot delete airline with active flights. Delete flights first.';
            mysqli_stmt_close($check_stmt);
            mysqli_close($conn);
            header('Location: list_airlines.php');
            exit();
        }
        mysqli_stmt_close($check_stmt);
        
        // Proceed with deletion
        $sql = 'DELETE FROM airline WHERE airline_id = ?';
        $stmt = mysqli_prepare($conn, $sql);
        
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'i', $airline_id);
            
            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['success'] = 'Airline deleted successfully!';
            } else {
                $_SESSION['error'] = 'Error deleting airline. Please try again.';
            }
            
            mysqli_stmt_close($stmt);
        }
    }
    
    mysqli_close($conn);
    header('Location: list_airlines.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Airlines Management | Admin Dashboard</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <?php include_once 'admin_sidebar.php'; ?>
    
    <div class="main-content">
        <?php include_once 'admin_header.php'; ?>
        
        <div class="content-wrapper">
            <div class="page-header">
                <h1><i class="fas fa-plane"></i> Airlines Management</h1>
                <div class="header-actions">
                    <a href="add_airline.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add New Airline
                    </a>
                </div>
            </div>
            
            <!-- Success/Error Messages -->
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success">
                    <?php 
                    echo $_SESSION['success']; 
                    unset($_SESSION['success']);
                    ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger">
                    <?php 
                    echo $_SESSION['error']; 
                    unset($_SESSION['error']);
                    ?>
                </div>
            <?php endif; ?>
            
            <!-- Airlines List -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-list"></i> Airlines List</h3>
                    <div class="search-box">
                        <input type="text" id="searchInput" placeholder="Search airlines...">
                        <i class="fas fa-search"></i>
                    </div>
                </div>
                
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="modern-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Airline Name</th>
                                    <th>IATA Code</th>
                                    <th>Total Seats</th>
                                    <th>Available Seats</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $sql = 'SELECT * FROM airline ORDER BY created_at DESC';
                                $result = mysqli_query($conn, $sql);
                                $cnt = 1;
                                
                                if (mysqli_num_rows($result) > 0) {
                                    while ($row = mysqli_fetch_assoc($result)) {
                                        // Calculate available seats (example logic - adjust based on your database)
                                        $sql_flights = 'SELECT SUM(seats_reserved) as reserved FROM flight WHERE airline_id = ?';
                                        $stmt = mysqli_prepare($conn, $sql_flights);
                                        mysqli_stmt_bind_param($stmt, 'i', $row['airline_id']);
                                        mysqli_stmt_execute($stmt);
                                        $flight_result = mysqli_stmt_get_result($stmt);
                                        $flight_data = mysqli_fetch_assoc($flight_result);
                                        $reserved_seats = $flight_data['reserved'] ?? 0;
                                        $available_seats = $row['seats'] - $reserved_seats;
                                        
                                        // Status based on availability
                                        $status = $available_seats > 0 ? 'active' : 'full';
                                        
                                        echo "
                                        <tr>
                                            <td>{$cnt}</td>
                                            <td>
                                                <div class='airline-info'>
                                                    <div class='airline-name'>{$row['name']}</div>
                                                    <small class='text-muted'>ID: AL{$row['airline_id']}</small>
                                                </div>
                                            </td>
                                            <td><span class='badge badge-secondary'>" . ($row['iata_code'] ?? 'N/A') . "</span></td>
                                            <td>
                                                <div class='seats-info'>
                                                    <span class='seats-total'>{$row['seats']}</span>
                                                </div>
                                            </td>
                                            <td>
                                                <div class='progress-wrapper'>
                                                    <div class='progress'>
                                                        <div class='progress-bar " . ($available_seats < 10 ? 'bg-danger' : 'bg-success') . "' 
                                                             style='width: " . (($available_seats / $row['seats']) * 100) . "%'>
                                                        </div>
                                                    </div>
                                                    <span class='seats-available'>$available_seats</span>
                                                </div>
                                            </td>
                                            <td>
                                                <span class='status-badge status-{$status}'>
                                                    <i class='fas fa-circle'></i> " . ucfirst($status) . "
                                                </span>
                                            </td>
                                            <td>
                                                <div class='action-buttons'>
                                                    <a href='edit_airline.php?id={$row['airline_id']}' class='btn-action btn-edit' title='Edit'>
                                                        <i class='fas fa-edit'></i>
                                                    </a>
                                                    <button type='button' class='btn-action btn-view' title='View Details' 
                                                            onclick=\"showAirlineDetails({$row['airline_id']})\">
                                                        <i class='fas fa-eye'></i>
                                                    </button>
                                                    <form method='POST' class='d-inline' onsubmit=\"return confirmDelete()\">
                                                        <input type='hidden' name='airline_id' value='{$row['airline_id']}'>
                                                        <button type='submit' name='del_airlines' class='btn-action btn-delete' title='Delete'>
                                                            <i class='fas fa-trash'></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                        ";
                                        $cnt++;
                                        mysqli_stmt_close($stmt);
                                    }
                                } else {
                                    echo "
                                    <tr>
                                        <td colspan='7' class='text-center py-4'>
                                            <div class='empty-state'>
                                                <i class='fas fa-plane-slash fa-3x'></i>
                                                <h4>No Airlines Found</h4>
                                                <p>Add your first airline to get started</p>
                                                <a href='add_airline.php' class='btn btn-primary'>
                                                    <i class='fas fa-plus'></i> Add Airline
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    ";
                                }
                                mysqli_close($conn);
                                ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Statistics Cards -->
                    <div class="stats-grid mt-4">
                        <div class="stat-card">
                            <div class="stat-icon bg-primary">
                                <i class="fas fa-plane"></i>
                            </div>
                            <div class="stat-info">
                                <h3><?php echo $cnt-1; ?></h3>
                                <p>Total Airlines</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon bg-success">
                                <i class="fas fa-chair"></i>
                            </div>
                            <div class="stat-info">
                                <h3><?php 
                                $sql_total = 'SELECT SUM(seats) as total FROM airline';
                                $result_total = mysqli_query($conn, $sql_total);
                                $total = mysqli_fetch_assoc($result_total);
                                echo number_format($total['total'] ?? 0);
                                ?></h3>
                                <p>Total Capacity</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Airline Details Modal -->
    <div class="modal fade" id="airlineModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Airline Details</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body" id="airlineDetails">
                    <!-- Details loaded via AJAX -->
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="../assets/js/admin.js"></script>
    <script>
    // Search functionality
    document.getElementById('searchInput').addEventListener('keyup', function() {
        const filter = this.value.toLowerCase();
        const rows = document.querySelectorAll('.modern-table tbody tr');
        
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(filter) ? '' : 'none';
        });
    });
    
    // Confirm deletion
    function confirmDelete() {
        return confirm('Are you sure you want to delete this airline? This action cannot be undone.');
    }
    
    // Show airline details
    function showAirlineDetails(airlineId) {
        // In a real application, you would fetch details via AJAX
        // For now, we'll show a simple alert
        alert(`Airline ID: ${airlineId}\nDetails would be loaded via AJAX in a full implementation.`);
    }
    </script>
</body>
</html>
