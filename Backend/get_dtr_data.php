<?php
require_once 'db_connect.php';

session_start();

$professor_id = $_GET['professor_id'] ?? null;
$year = $_GET['year'] ?? date('Y');
$month = $_GET['month'] ?? date('n');

if (!$professor_id) {
    die(json_encode(['error' => 'Professor ID required']));
}

try {
    $stmt = $conn->prepare("
        SELECT h.dtr_id, h.date_from, h.date_to, h.month, h.year,
               d.date, d.time_in_am, d.time_out_am, d.time_in_pm, d.time_out_pm,
               d.hours_rendered, d.minutes_rendered
        FROM DTR_Header h
        JOIN DTR_Details d ON h.dtr_id = d.dtr_id
        WHERE h.professor_id = ?
        AND h.year = ?
        AND (h.month = ? OR h.month = ?)
    ");
    
    $monthName = date('F', mktime(0, 0, 0, $month, 1));
    $stmt->bind_param("isis", $professor_id, $year, $month, $monthName);
    $stmt->execute();
    $result = $stmt->get_result();

    $dtrData = [];
    while ($row = $result->fetch_assoc()) {
        $dtrData[] = $row;
    }

    echo json_encode($dtrData);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}