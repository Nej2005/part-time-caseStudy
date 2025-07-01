<?php
session_start();
include 'db_connect.php';

header('Content-Type: application/json');

// Validate input
if (!isset($_POST['form_id'])) {
    echo json_encode(['success' => false, 'message' => 'Form ID is required']);
    exit;
}

$form_id = (int)$_POST['form_id'];

// Verify the form exists and belongs to the professor
$verify_query = "SELECT professor_id FROM Form_Loads WHERE form_id = ?";
$stmt = $conn->prepare($verify_query);
$stmt->bind_param("i", $form_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Form not found']);
    exit;
}

// Begin transaction
$conn->begin_transaction();

try {
    // First delete the details
    $delete_details = "DELETE FROM Form_Load_Details WHERE form_id = ?";
    $stmt = $conn->prepare($delete_details);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("i", $form_id);
    if (!$stmt->execute()) {
        throw new Exception("Failed to delete form details: " . $stmt->error);
    }

    // Then delete the form
    $delete_form = "DELETE FROM Form_Loads WHERE form_id = ?";
    $stmt = $conn->prepare($delete_form);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("i", $form_id);
    if (!$stmt->execute()) {
        throw new Exception("Failed to delete form: " . $stmt->error);
    }

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Form deleted successfully']);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}