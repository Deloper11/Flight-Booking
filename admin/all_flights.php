<?php
session_start();
require_once '../helpers/init_conn_db.php';

if (!isset($_SESSION['adminId'])) {
    http_response_code(401);
    exit('Unauthorized');
}

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="flights_export_' . date('Y-m-d') . '.csv"');

$output = fopen('php://output', 'w');

// CSV headers
fputcsv($output, [
    'Flight ID',
    'Airline',
    'Source',
    'Destination',
    'Departure',
    'Arrival',
    'Seats',
    'Available Seats',
    'Price',
    'Status'
]);

// Build query with filters
$sql = 'SELECT * FROM Flight WHERE 1=1';
// ... same filter logic as all_flights.php

$stmt = mysqli_prepare($conn, $sql);
// ... bind parameters
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

while ($row = mysqli_fetch_assoc($result)) {
    $available = calculateAvailableSeats($conn, $row['flight_id']);
    $status = getFlightStatus($row['departure'], $row['arrivale']);
    
    fputcsv($output, [
        $row['flight_id'],
        $row['airline'],
        $row['source'],
        $row['Destination'],
        $row['departure'],
        $row['arrivale'],
        $row['Seats'],
        $available,
        $row['Price'],
        $status
    ]);
}

fclose($output);
?>
