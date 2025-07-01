<?php
require_once '../db_connect.php';

session_start();

if (!isset($_SESSION['professor_id'])) {
    die(json_encode(['error' => 'Not authenticated']));
}

$professor_id = $_SESSION['professor_id'];

// Get distinct years with ARs
$stmt = $conn->prepare("SELECT DISTINCT year FROM AR_Header WHERE professor_id = ? ORDER BY year DESC");
$stmt->bind_param("i", $professor_id);
$stmt->execute();
$years = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// For each year, get available months
$ar_periods = [];
foreach ($years as $year_row) {
    $year = $year_row['year'];
    $stmt = $conn->prepare("SELECT DISTINCT month FROM AR_Header WHERE professor_id = ? AND year = ? ORDER BY 
        CASE month
            WHEN 'January' THEN 1
            WHEN 'February' THEN 2
            WHEN 'March' THEN 3
            WHEN 'April' THEN 4
            WHEN 'May' THEN 5
            WHEN 'June' THEN 6
            WHEN 'July' THEN 7
            WHEN 'August' THEN 8
            WHEN 'September' THEN 9
            WHEN 'October' THEN 10
            WHEN 'November' THEN 11
            WHEN 'December' THEN 12
        END");
    $stmt->bind_param("is", $professor_id, $year);
    $stmt->execute();
    $months = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    $ar_periods[$year] = array_column($months, 'month');
}

echo json_encode(['ar_periods' => $ar_periods]);

$conn->close();
?>