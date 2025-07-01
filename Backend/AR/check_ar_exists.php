<?php
require_once '../db_connect.php';

session_start();

if (!isset($_SESSION['professor_id'])) {
    die(json_encode(['error' => 'Not authenticated']));
}

$professor_id = $_SESSION['professor_id'];
$current_month = date('F');
$current_year = date('Y');

$stmt = $conn->prepare("SELECT ar_id FROM AR_Header 
                       WHERE professor_id = ? 
                       AND month = ? 
                       AND year = ?");
$stmt->bind_param("iss", $professor_id, $current_month, $current_year);
$stmt->execute();
$result = $stmt->get_result();

echo json_encode(['exists' => $result->num_rows > 0]);

$stmt->close();
$conn->close();
?>