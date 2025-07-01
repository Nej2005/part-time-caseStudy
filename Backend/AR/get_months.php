<?php
session_start();
include '../db_connect.php';

$professor_id = $_GET['professor_id'] ?? 0;
$year = $_GET['year'] ?? date('Y');

// Query to get distinct months that have AR with head_signature for the selected year
$monthQuery = "SELECT DISTINCT month FROM AR_Header 
               WHERE professor_id = ? 
               AND year = ?
               AND head_signature IS NOT NULL
               ORDER BY FIELD(month, 
                   'January', 'February', 'March', 'April', 
                   'May', 'June', 'July', 'August', 
                   'September', 'October', 'November', 'December')";
$stmt = $conn->prepare($monthQuery);
$stmt->bind_param("is", $professor_id, $year);
$stmt->execute();
$monthResult = $stmt->get_result();

$months = [];
while ($row = $monthResult->fetch_assoc()) {
    $months[] = $row['month'];
}

// Always include current month if it's the current year
if ($year == date('Y') && !in_array(date('F'), $months)) {
    array_unshift($months, date('F'));
}

header('Content-Type: application/json');
echo json_encode(['months' => $months]);

$conn->close();
?>