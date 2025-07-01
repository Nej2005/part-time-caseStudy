<?php
session_start();
include '../../Backend/db_connect.php';

header('Content-Type: application/json');

try {
    // Get the POST data
    $professor_id = $_POST['professor_id'] ?? null;
    $month = $_POST['month'] ?? null;
    $year = $_POST['year'] ?? null;
    $head_signature = $_POST['head_signature'] ?? null;
    $checked_by = $_POST['checked_by'] ?? null;

    if (!$professor_id || !$month || !$year) {
        throw new Exception('Missing required parameters');
    }

    // Check if AR header exists
    $stmt = $conn->prepare("SELECT ar_id FROM AR_Header WHERE professor_id = ? AND month = ? AND year = ?");
    $stmt->bind_param("iss", $professor_id, $month, $year);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $ar = $result->fetch_assoc();
        $stmt = $conn->prepare("UPDATE AR_Header SET head_signature = ?, checked_by = ?, updated_at = NOW() WHERE ar_id = ?");
        $stmt->bind_param("ssi", $head_signature, $checked_by, $ar['ar_id']);
    } else {
        $stmt = $conn->prepare("INSERT INTO AR_Header (professor_id, month, year, head_signature, checked_by, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())");
        $stmt->bind_param("issss", $professor_id, $month, $year, $head_signature, $checked_by);
    }

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        throw new Exception('Failed to save data');
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>