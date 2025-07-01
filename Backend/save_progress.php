<?php
// Clean all output buffers
while (ob_get_level()) ob_end_clean();

// Set headers first
header('Content-Type: application/json');
header('Cache-Control: no-cache');

// Initialize response
$response = ['success' => false];

try {
    // Only accept POST requests
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Only POST requests allowed', 405);
    }

    // Get and validate input
    $input = json_decode(file_get_contents('php://input'), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON input', 400);
    }

    if (empty($input['professor_id']) || empty($input['status'])) {
        throw new Exception('Missing required parameters', 400);
    }

    $professorId = (int)$input['professor_id'];
    $status = $input['status'];

    // Validate status value
    if (!in_array($status, ['completed', 'in-progress', 'pending'])) {
        throw new Exception('Invalid status value', 400);
    }

    // Include database connection
    require_once '../Backend/db_connect.php';

    // Prepare and execute update
    $stmt = $conn->prepare("UPDATE PartTime_Professor SET progress_status = ? WHERE professor_id = ?");
    if (!$stmt) {
        throw new Exception('Database error: ' . $conn->error, 500);
    }

    $stmt->bind_param("si", $status, $professorId);

    if (!$stmt->execute()) {
        throw new Exception('Update failed: ' . $stmt->error, 500);
    }

    // Success response
    $response = [
        'success' => true,
        'affected_rows' => $stmt->affected_rows,
        'new_status' => $status
    ];

} catch (Exception $e) {
    http_response_code($e->getCode() ?: 400);
    $response['error'] = $e->getMessage();
} finally {
    // Clean up
    if (isset($stmt)) $stmt->close();
    if (isset($conn)) $conn->close();
    
    // Output JSON and terminate
    exit(json_encode($response));
}