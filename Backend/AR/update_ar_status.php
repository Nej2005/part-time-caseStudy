<?php
include '../db_connect.php';

$data = json_decode(file_get_contents('php://input'), true);
$professorId = $data['professor_id'];
$status = $data['status'];
$currentMonth = date('m');
$currentYear = date('Y');

try {
    // Update both status and last update month/year
    $sql = "UPDATE PartTime_Professor 
            SET progress_status = ?, 
                last_status_update_month = ?,
                last_status_update_year = ?
            WHERE professor_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("siii", $status, $currentMonth, $currentYear, $professorId);
    $stmt->execute();
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
$conn->close();
?>