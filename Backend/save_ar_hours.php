<?php
session_start();
require_once 'db_connect.php';

$data = json_decode(file_get_contents('php://input'), true);

$professorId = $data['professorId'] ?? null;
$semester = $data['semester'] ?? null;
$ay = $data['ay'] ?? null;
$month = $data['month'] ?? null;
$year = $data['year'] ?? null;
$subjects = $data['subjects'] ?? [];

if (!$professorId || !$semester || !$ay || !$month || !$year) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

try {
    $conn->begin_transaction();
    
    // First, check if we have a DTR header for this period
    $stmt = $conn->prepare("SELECT dtr_id FROM DTR_Header WHERE professor_id = ? AND month = ? AND year = ?");
    $stmt->bind_param("iss", $professorId, $month, $year);
    $stmt->execute();
    $result = $stmt->get_result();
    $dtrHeader = $result->fetch_assoc();
    $stmt->close();
    
    if (!$dtrHeader) {
        // Create a new DTR header if none exists
        $stmt = $conn->prepare("
            INSERT INTO DTR_Header 
            (professor_id, employee_id, full_name, department, date_from, date_to, month, year)
            SELECT 
                ?, employee_id, CONCAT(first_name, ' ', IFNULL(CONCAT(middle_initial, '. '), '', last_name), department, 
                ?, ?, ?, ?
            FROM PartTime_Professor 
            WHERE professor_id = ?
        ");
        
        // Calculate date_from and date_to (first and last day of month)
        $dateFrom = date('Y-m-01', mktime(0, 0, 0, $month, 1, $year));
        $dateTo = date('Y-m-t', mktime(0, 0, 0, $month, 1, $year));
        
        $stmt->bind_param("issssi", $professorId, $dateFrom, $dateTo, $month, $year, $professorId);
        $stmt->execute();
        $dtrId = $conn->insert_id;
        $stmt->close();
    } else {
        $dtrId = $dtrHeader['dtr_id'];
    }
    
    // For each subject, update the DTR details with the hours rendered
    foreach ($subjects as $subject) {
        // Get all dates for this subject's day in the month
        $day = strtolower($subject['day']);
        $dates = [];
        
        // Create a date range for the month
        $start = new DateTime("$year-$month-01");
        $end = clone $start;
        $end->modify('last day of this month');
        $interval = new DateInterval('P1D');
        $period = new DatePeriod($start, $interval, $end);
        
        foreach ($period as $date) {
            if (strtolower($date->format('l')) === $day) {
                $dates[] = $date->format('Y-m-d');
            }
        }
        
        // For each date, update or create DTR detail record
        foreach ($dates as $date) {
            $stmt = $conn->prepare("
                INSERT INTO DTR_Details 
                (dtr_id, date, hours_rendered, minutes_rendered)
                VALUES (?, ?, ?, 0)
                ON DUPLICATE KEY UPDATE
                hours_rendered = VALUES(hours_rendered),
                minutes_rendered = VALUES(minutes_rendered)
            ");
            $hours = $subject['hoursRendered'] / count($dates); 
            $stmt->bind_param("isd", $dtrId, $date, $hours);
            $stmt->execute();
            $stmt->close();
        }
    }
    
    $conn->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>