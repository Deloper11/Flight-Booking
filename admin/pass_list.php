<?php
session_start();
require '../helpers/init_conn_db.php';

// Security check for admin access
if (!isset($_SESSION['adminId'])) {
    header('Location: ../index.php');
    exit();
}

// Validate and sanitize flight_id
$flight_id = isset($_GET['flight_id']) ? filter_var($_GET['flight_id'], FILTER_VALIDATE_INT) : null;

if (!$flight_id) {
    header('Location: flights.php?error=invalid_flight');
    exit();
}

// Get flight details
$flight_sql = 'SELECT f.*, a.name as airline_name, a.logo as airline_logo 
               FROM flight f 
               JOIN airline a ON f.airline_id = a.airline_id 
               WHERE f.flight_id = ?';
$flight_stmt = mysqli_prepare($conn, $flight_sql);
mysqli_stmt_bind_param($flight_stmt, 'i', $flight_id);
mysqli_stmt_execute($flight_stmt);
$flight_result = mysqli_stmt_get_result($flight_stmt);
$flight = mysqli_fetch_assoc($flight_result);
mysqli_stmt_close($flight_stmt);

if (!$flight) {
    header('Location: flights.php?error=flight_not_found');
    exit();
}

// Get passenger data with a single optimized query
$sql = 'SELECT 
        t.ticket_id,
        p.passenger_id,
        p.f_name,
        p.m_name,
        p.l_name,
        p.mobile,
        p.dob,
        p.passport_no,
        p.email,
        p.seat_number,
        u.user_id,
        u.username,
        u.email as user_email,
        py.amount,
        py.payment_date,
        py.payment_method,
        py.transaction_id,
        py.status as payment_status
        FROM Ticket t
        JOIN Passenger_profile p ON t.passenger_id = p.passenger_id
        JOIN Users u ON p.user_id = u.user_id
        LEFT JOIN PAYMENT py ON py.flight_id = t.flight_id AND py.user_id = u.user_id
        WHERE t.flight_id = ?
        ORDER BY p.l_name, p.f_name';

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 'i', $flight_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Calculate statistics
$total_passengers = mysqli_num_rows($result);
$total_revenue = 0;
$passengers_by_class = [
    'economy' => 0,
    'business' => 0,
    'first' => 0
];

// Fetch all data
$passengers = [];
while ($row = mysqli_fetch_assoc($result)) {
    $passengers[] = $row;
    $total_revenue += $row['amount'];
    
    // Determine class based on amount (example logic - adjust as needed)
    if ($row['amount'] > 50000) {
        $passengers_by_class['first']++;
    } elseif ($row['amount'] > 30000) {
        $passengers_by_class['business']++;
    } else {
        $passengers_by_class['economy']++;
    }
}
mysqli_stmt_close($stmt);
mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Flight Passenger Manifest | Admin Dashboard</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <style>
        .flight-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 15px;
            padding: 30px;
            color: white;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .flight-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .flight-info-item {
            background: rgba(255,255,255,0.1);
            padding: 15px;
            border-radius: 10px;
            backdrop-filter: blur(10px);
        }
        .passenger-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 14px;
        }
        .seat-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .seat-economy {
            background: rgba(16, 185, 129, 0.1);
            color: #10b981;
            border: 1px solid rgba(16, 185, 129, 0.2);
        }
        .seat-business {
            background: rgba(99, 102, 241, 0.1);
            color: #6366f1;
            border: 1px solid rgba(99, 102, 241, 0.2);
        }
        .seat-first {
            background: rgba(245, 158, 11, 0.1);
            color: #f59e0b;
            border: 1px solid rgba(245, 158, 11, 0.2);
        }
        .payment-status {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        .payment-status.paid {
            background: rgba(16, 185, 129, 0.1);
            color: #10b981;
        }
        .payment-status.pending {
            background: rgba(245, 158, 11, 0.1);
            color: #f59e0b;
        }
        .payment-status.failed {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
        }
        .manifest-actions {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }
        .stat-card .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: #1e293b;
            margin: 10px 0;
        }
        .stat-card .stat-label {
            color: #64748b;
            font-size: 0.9rem;
        }
        .passenger-details-modal {
            max-width: 800px;
        }
        .qr-code {
            width: 120px;
            height: 120px;
            background: #f8fafc;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
            border: 1px solid #e2e8f0;
        }
        .boarding-pass {
            background: white;
            border: 2px dashed #e2e8f0;
            border-radius: 12px;
            padding: 20px;
            margin-top: 20px;
        }
        .manifest-print {
            background: #f8fafc;
            padding: 20px;
            border-radius: 12px;
            margin-top: 30px;
        }
        @media print {
            .no-print {
                display: none !important;
            }
            .manifest-print {
                background: white;
                border: none;
                padding: 0;
            }
            table {
                font-size: 12px;
            }
        }
    </style>
</head>
<body>
    <?php include_once 'admin_sidebar.php'; ?>
    
    <div class="main-content">
        <?php include_once 'admin_header.php'; ?>
        
        <div class="content-wrapper">
            <!-- Flight Header -->
            <div class="flight-header">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h1 class="mb-2"><i class="fas fa-plane-departure"></i> Flight Passenger Manifest</h1>
                        <p class="mb-0 opacity-75">Complete passenger list for flight <?php echo htmlspecialchars($flight['flight_id']); ?></p>
                    </div>
                    <div class="text-end">
                        <div class="airline-logo mb-2">
                            <i class="fas fa-plane fa-3x"></i>
                        </div>
                        <h3 class="mb-0"><?php echo htmlspecialchars($flight['airline_name']); ?></h3>
                    </div>
                </div>
                
                <div class="flight-info-grid">
                    <div class="flight-info-item">
                        <small class="opacity-75">Flight Number</small>
                        <div class="fw-bold"><?php echo htmlspecialchars($flight['flight_id']); ?></div>
                    </div>
                    <div class="flight-info-item">
                        <small class="opacity-75">From</small>
                        <div class="fw-bold"><?php echo htmlspecialchars($flight['source']); ?></div>
                    </div>
                    <div class="flight-info-item">
                        <small class="opacity-75">To</small>
                        <div class="fw-bold"><?php echo htmlspecialchars($flight['Destination']); ?></div>
                    </div>
                    <div class="flight-info-item">
                        <small class="opacity-75">Departure</small>
                        <div class="fw-bold"><?php echo date('M d, Y H:i', strtotime($flight['departure'])); ?></div>
                    </div>
                    <div class="flight-info-item">
                        <small class="opacity-75">Arrival</small>
                        <div class="fw-bold"><?php echo date('M d, Y H:i', strtotime($flight['arrivale'])); ?></div>
                    </div>
                    <div class="flight-info-item">
                        <small class="opacity-75">Duration</small>
                        <div class="fw-bold">
                            <?php 
                            $departure = new DateTime($flight['departure']);
                            $arrival = new DateTime($flight['arrivale']);
                            $interval = $departure->diff($arrival);
                            echo $interval->format('%hh %im');
                            ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Statistics Row -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="stat-card">
                        <i class="fas fa-users text-primary fa-2x"></i>
                        <div class="stat-value"><?php echo $total_passengers; ?></div>
                        <div class="stat-label">Total Passengers</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <i class="fas fa-money-bill-wave text-success fa-2x"></i>
                        <div class="stat-value">KES <?php echo number_format($total_revenue); ?></div>
                        <div class="stat-label">Total Revenue</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <i class="fas fa-chair text-warning fa-2x"></i>
                        <div class="stat-value"><?php echo $passengers_by_class['economy']; ?></div>
                        <div class="stat-label">Economy Class</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <i class="fas fa-crown text-danger fa-2x"></i>
                        <div class="stat-value"><?php echo $passengers_by_class['business'] + $passengers_by_class['first']; ?></div>
                        <div class="stat-label">Premium Class</div>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="manifest-actions no-print">
                <button class="btn btn-primary" onclick="window.print()">
                    <i class="fas fa-print"></i> Print Manifest
                </button>
                <a href="export_manifest.php?flight_id=<?php echo $flight_id; ?>&format=pdf" class="btn btn-danger">
                    <i class="fas fa-file-pdf"></i> Export PDF
                </a>
                <a href="export_manifest.php?flight_id=<?php echo $flight_id; ?>&format=excel" class="btn btn-success">
                    <i class="fas fa-file-excel"></i> Export Excel
                </a>
                <a href="send_manifest.php?flight_id=<?php echo $flight_id; ?>" class="btn btn-info">
                    <i class="fas fa-paper-plane"></i> Email Manifest
                </a>
                <a href="flights.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Flights
                </a>
            </div>

            <!-- Passenger Manifest Table -->
            <div class="card">
                <div class="card-header">
                    <h3 class="mb-0"><i class="fas fa-list"></i> Passenger Manifest</h3>
                    <div class="text-muted">Showing <?php echo $total_passengers; ?> passengers</div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover" id="manifestTable">
                            <thead>
                                <tr>
                                    <th width="50">#</th>
                                    <th>Passenger</th>
                                    <th>Contact Info</th>
                                    <th>Seat & Class</th>
                                    <th>Booking Info</th>
                                    <th>Payment</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($total_passengers > 0): ?>
                                    <?php $counter = 1; ?>
                                    <?php foreach ($passengers as $passenger): ?>
                                        <?php
                                        $initials = strtoupper(substr($passenger['f_name'], 0, 1) . substr($passenger['l_name'], 0, 1));
                                        $full_name = $passenger['f_name'] . ' ' . $passenger['m_name'] . ' ' . $passenger['l_name'];
                                        $age = date_diff(date_create($passenger['dob']), date_create('today'))->y;
                                        $seat_class = $passenger['amount'] > 50000 ? 'first' : ($passenger['amount'] > 30000 ? 'business' : 'economy');
                                        $seat_label = $passenger['seat_number'] ?? 'N/A';
                                        ?>
                                        <tr>
                                            <td><?php echo $counter; ?></td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="passenger-avatar me-3">
                                                        <?php echo $initials; ?>
                                                    </div>
                                                    <div>
                                                        <div class="fw-bold"><?php echo htmlspecialchars($passenger['f_name'] . ' ' . $passenger['l_name']); ?></div>
                                                        <small class="text-muted">
                                                            <?php echo $age; ?> years • 
                                                            <?php echo date('d/m/Y', strtotime($passenger['dob'])); ?>
                                                        </small>
                                                        <?php if ($passenger['passport_no']): ?>
                                                            <div class="mt-1">
                                                                <small><i class="fas fa-passport me-1"></i> <?php echo htmlspecialchars($passenger['passport_no']); ?></small>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div>
                                                    <div><i class="fas fa-phone me-2"></i><?php echo htmlspecialchars($passenger['mobile']); ?></div>
                                                    <div><i class="fas fa-envelope me-2"></i><?php echo htmlspecialchars($passenger['email']); ?></div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    <span class="seat-badge seat-<?php echo $seat_class; ?>">
                                                        <?php echo ucfirst($seat_class); ?>
                                                    </span>
                                                    <span class="fw-bold"><?php echo $seat_label; ?></span>
                                                </div>
                                            </td>
                                            <td>
                                                <div>
                                                    <div class="fw-bold"><?php echo htmlspecialchars($passenger['username']); ?></div>
                                                    <small class="text-muted">Booked by</small>
                                                    <div class="mt-1">
                                                        <small><i class="fas fa-ticket-alt me-1"></i> Ticket: <?php echo $passenger['ticket_id']; ?></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div>
                                                    <div class="fw-bold">KES <?php echo number_format($passenger['amount']); ?></div>
                                                    <span class="payment-status <?php echo strtolower($passenger['payment_status']); ?>">
                                                        <i class="fas fa-<?php echo $passenger['payment_status'] === 'paid' ? 'check-circle' : 'clock'; ?>"></i>
                                                        <?php echo ucfirst($passenger['payment_status']); ?>
                                                    </span>
                                                    <?php if ($passenger['transaction_id']): ?>
                                                        <div class="mt-1">
                                                            <small><i class="fas fa-receipt me-1"></i> <?php echo substr($passenger['transaction_id'], 0, 12) . '...'; ?></small>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="dropdown">
                                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                                        <i class="fas fa-ellipsis-v"></i>
                                                    </button>
                                                    <ul class="dropdown-menu">
                                                        <li>
                                                            <button class="dropdown-item" onclick="showPassengerDetails(<?php echo htmlspecialchars(json_encode($passenger)); ?>)">
                                                                <i class="fas fa-eye me-2"></i> View Details
                                                            </button>
                                                        </li>
                                                        <li>
                                                            <a class="dropdown-item" href="#" onclick="printBoardingPass(<?php echo $passenger['passenger_id']; ?>)">
                                                                <i class="fas fa-ticket-alt me-2"></i> Boarding Pass
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <a class="dropdown-item" href="mailto:<?php echo $passenger['email']; ?>">
                                                                <i class="fas fa-envelope me-2"></i> Send Email
                                                            </a>
                                                        </li>
                                                        <li><hr class="dropdown-divider"></li>
                                                        <li>
                                                            <a class="dropdown-item text-danger" href="#" onclick="confirmRemoval(<?php echo $passenger['ticket_id']; ?>)">
                                                                <i class="fas fa-user-slash me-2"></i> Remove from Flight
                                                            </a>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php $counter++; ?>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-5">
                                            <div class="empty-state">
                                                <i class="fas fa-users-slash fa-4x text-muted mb-3"></i>
                                                <h4>No Passengers Found</h4>
                                                <p class="text-muted">This flight has no booked passengers yet.</p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Print Version -->
            <div class="manifest-print d-none d-print-block">
                <div class="text-center mb-4">
                    <h2>Flight Passenger Manifest</h2>
                    <h4><?php echo htmlspecialchars($flight['airline_name']); ?> • Flight <?php echo $flight_id; ?></h4>
                    <p><?php echo htmlspecialchars($flight['source']); ?> to <?php echo htmlspecialchars($flight['Destination']); ?> • <?php echo date('M d, Y', strtotime($flight['departure'])); ?></p>
                </div>
                
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Passenger Name</th>
                            <th>Passport</th>
                            <th>Seat</th>
                            <th>Class</th>
                            <th>Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($passengers as $index => $passenger): ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td><?php echo htmlspecialchars($passenger['f_name'] . ' ' . $passenger['l_name']); ?></td>
                                <td><?php echo htmlspecialchars($passenger['passport_no'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($passenger['seat_number'] ?? 'N/A'); ?></td>
                                <td><?php echo $seat_class; ?></td>
                                <td>KES <?php echo number_format($passenger['amount']); ?></td>
                                <td><?php echo ucfirst($passenger['payment_status']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <div class="mt-4 pt-4 border-top">
                    <div class="row">
                        <div class="col">
                            <p><strong>Total Passengers:</strong> <?php echo $total_passengers; ?></p>
                        </div>
                        <div class="col">
                            <p><strong>Total Revenue:</strong> KES <?php echo number_format($total_revenue); ?></p>
                        </div>
                    </div>
                    <div class="mt-3 text-muted text-end">
                        <small>Printed on: <?php echo date('Y-m-d H:i:s'); ?></small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Passenger Details Modal -->
    <div class="modal fade" id="passengerModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Passenger Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="passengerDetails">
                    <!-- Content loaded via JavaScript -->
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="../assets/js/admin.js"></script>
    <script>
    $(document).ready(function() {
        // Initialize DataTable
        $('#manifestTable').DataTable({
            pageLength: 25,
            lengthMenu: [10, 25, 50, 100],
            order: [[0, 'asc']],
            language: {
                search: "Search passengers:",
                lengthMenu: "Show _MENU_ entries",
                info: "Showing _START_ to _END_ of _TOTAL_ passengers",
                paginate: {
                    first: "First",
                    last: "Last",
                    next: "Next",
                    previous: "Previous"
                }
            }
        });

        // Initialize tooltips
        $('[data-bs-toggle="tooltip"]').tooltip();
    });

    // Show passenger details
    function showPassengerDetails(passenger) {
        const dob = new Date(passenger.dob);
        const age = Math.floor((new Date() - dob) / (365.25 * 24 * 60 * 60 * 1000));
        
        const modalContent = `
            <div class="row">
                <div class="col-md-4">
                    <div class="text-center mb-4">
                        <div class="passenger-avatar mx-auto" style="width: 80px; height: 80px; font-size: 24px;">
                            ${passenger.f_name.charAt(0)}${passenger.l_name.charAt(0)}
                        </div>
                        <h4 class="mt-3">${passenger.f_name} ${passenger.l_name}</h4>
                        <p class="text-muted">Passenger ID: ${passenger.passenger_id}</p>
                    </div>
                    
                    <div class="qr-code">
                        <i class="fas fa-qrcode fa-3x text-muted"></i>
                    </div>
                </div>
                
                <div class="col-md-8">
                    <div class="row">
                        <div class="col-6">
                            <p><strong>First Name:</strong><br>${passenger.f_name}</p>
                        </div>
                        <div class="col-6">
                            <p><strong>Last Name:</strong><br>${passenger.l_name}</p>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-6">
                            <p><strong>Date of Birth:</strong><br>${new Date(passenger.dob).toLocaleDateString()} (${age} years)</p>
                        </div>
                        <div class="col-6">
                            <p><strong>Mobile:</strong><br>${passenger.mobile}</p>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-12">
                            <p><strong>Email:</strong><br>${passenger.email}</p>
                        </div>
                    </div>
                    
                    ${passenger.passport_no ? `
                    <div class="row">
                        <div class="col-12">
                            <p><strong>Passport Number:</strong><br>${passenger.passport_no}</p>
                        </div>
                    </div>
                    ` : ''}
                    
                    <hr>
                    
                    <h6 class="mb-3">Flight Information</h6>
                    <div class="row">
                        <div class="col-6">
                            <p><strong>Seat Number:</strong><br>${passenger.seat_number || 'Not assigned'}</p>
                        </div>
                        <div class="col-6">
                            <p><strong>Ticket ID:</strong><br>${passenger.ticket_id}</p>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <h6 class="mb-3">Payment Information</h6>
                    <div class="row">
                        <div class="col-6">
                            <p><strong>Amount:</strong><br>KES ${passenger.amount.toLocaleString()}</p>
                        </div>
                        <div class="col-6">
                            <p><strong>Status:</strong><br>
                                <span class="payment-status ${passenger.payment_status.toLowerCase()}">
                                    <i class="fas fa-${passenger.payment_status === 'paid' ? 'check-circle' : 'clock'}"></i>
                                    ${passenger.payment_status}
                                </span>
                            </p>
                        </div>
                    </div>
                    
                    ${passenger.transaction_id ? `
                    <div class="row">
                        <div class="col-12">
                            <p><strong>Transaction ID:</strong><br>${passenger.transaction_id}</p>
                        </div>
                    </div>
                    ` : ''}
                    
                    <div class="row">
                        <div class="col-12">
                            <p><strong>Payment Method:</strong><br>${passenger.payment_method || 'Not specified'}</p>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <h6 class="mb-3">Booked By</h6>
                    <div class="row">
                        <div class="col-6">
                            <p><strong>Username:</strong><br>${passenger.username}</p>
                        </div>
                        <div class="col-6">
                            <p><strong>User Email:</strong><br>${passenger.user_email}</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="boarding-pass">
                <div class="text-center">
                    <h5>Boarding Pass</h5>
                    <small class="text-muted">Flight ${<?php echo $flight_id; ?>} • ${<?php echo htmlspecialchars($flight['source']); ?>} to ${<?php echo htmlspecialchars($flight['Destination']); ?>}</small>
                </div>
            </div>
        `;
        
        document.getElementById('passengerDetails').innerHTML = modalContent;
        const modal = new bootstrap.Modal(document.getElementById('passengerModal'));
        modal.show();
    }

    // Confirm passenger removal
    function confirmRemoval(ticketId) {
        if (confirm('Are you sure you want to remove this passenger from the flight? This action cannot be undone.')) {
            window.location.href = `remove_passenger.php?ticket_id=${ticketId}&flight_id=<?php echo $flight_id; ?>`;
        }
    }

    // Print boarding pass
    function printBoardingPass(passengerId) {
        const printWindow = window.open('', '_blank');
        printWindow.document.write(`
            <html>
                <head>
                    <title>Boarding Pass</title>
                    <style>
                        body { font-family: Arial, sans-serif; }
                        .boarding-pass { border: 2px solid #000; padding: 20px; width: 800px; }
                        .header { text-align: center; margin-bottom: 20px; }
                        .details { display: flex; justify-content: space-between; }
                        .qr-code { text-align: center; margin: 20px 0; }
                    </style>
                </head>
                <body>
                    <div class="boarding-pass">
                        <div class="header">
                            <h2>BOARDING PASS</h2>
                            <h3><?php echo htmlspecialchars($flight['airline_name']); ?></h3>
                        </div>
                        <div class="details">
                            <div>
                                <p><strong>Passenger ID:</strong> ${passengerId}</p>
                                <p><strong>Flight:</strong> <?php echo $flight_id; ?></p>
                                <p><strong>From:</strong> <?php echo htmlspecialchars($flight['source']); ?></p>
                            </div>
                            <div>
                                <p><strong>To:</strong> <?php echo htmlspecialchars($flight['Destination']); ?></p>
                                <p><strong>Departure:</strong> <?php echo date('M d, Y H:i', strtotime($flight['departure'])); ?></p>
                                <p><strong>Gate:</strong> To be announced</p>
                            </div>
                        </div>
                        <div class="qr-code">
                            <div style="width: 150px; height: 150px; background: #000; margin: 0 auto;"></div>
                            <p>Scan QR code at gate</p>
                        </div>
                        <div style="text-align: center; margin-top: 20px;">
                            <small>Please arrive at the gate 45 minutes before departure</small>
                        </div>
                    </div>
                </body>
            </html>
        `);
        printWindow.document.close();
        printWindow.print();
    }

    // Export functions
    function exportToPDF() {
        window.open(`export_manifest.php?flight_id=<?php echo $flight_id; ?>&format=pdf`, '_blank');
    }

    function exportToExcel() {
        window.open(`export_manifest.php?flight_id=<?php echo $flight_id; ?>&format=excel`, '_blank');
    }
    </script>
</body>
</html>
