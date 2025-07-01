<?php
session_start();
include '../db_connect.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

if (!isset($_GET['ar_id'])) {
    echo json_encode(['success' => false, 'message' => 'AR ID not provided']);
    exit();
}

$ar_id = $_GET['ar_id'];

try {
    // Begin transaction
    $conn->begin_transaction();
    
    // First delete details
    $stmt_details = $conn->prepare("DELETE FROM AR_Details WHERE ar_id = ?");
    $stmt_details->bind_param("i", $ar_id);
    $stmt_details->execute();
    
    // Then delete header
    $stmt_header = $conn->prepare("DELETE FROM AR_Header WHERE ar_id = ?");
    $stmt_header->bind_param("i", $ar_id);
    $stmt_header->execute();
    
    // Commit transaction
    $conn->commit();
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>