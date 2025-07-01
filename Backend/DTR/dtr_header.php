<?php
header('Content-Type: application/json');
require_once '../db_connect.php';

// Get the posted data
$data = json_decode(file_get_contents('php://input'), true);

try {
    // Validate required fields
    $requiredFields = ['name', 'employee_number', 'department', 'date_from', 'date_to', 'year'];
    foreach ($requiredFields as $field) {
        if (empty($data[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }

    // Validate year (must be 4 digits between 2000-2100)
    if (!preg_match('/^\d{4}$/', $data['year']) || $data['year'] < 2000 || $data['year'] > 2100) {
        throw new Exception('Invalid year. Must be between 2000-2100');
    }

    // Use provided professor_id or look it up if not provided
    $professor_id = $data['professor_id'] ?? null;
    
    if (!$professor_id) {
        // Lookup professor_id from employee_number
        $stmtUser = $conn->prepare("SELECT user_id FROM Users WHERE employee_id = ?");
        $stmtUser->bind_param("s", $data['employee_number']);
        $stmtUser->execute();
        $result = $stmtUser->get_result();
        
        if ($row = $result->fetch_assoc()) {
            $professor_id = $row['user_id'];
        } else {
            throw new Exception('Professor not found with employee number: ' . $data['employee_number']);
        }
    }

    // Verify date_from is within the specified year
    $dateFrom = new DateTime($data['date_from']);
    if ($dateFrom->format('Y') != $data['year']) {
        throw new Exception('Date From year does not match the specified year');
    }

    // Get month name from date_from (e.g. "February")
    $month = $dateFrom->format('F');

    // Insert DTR Header
    $stmt = $conn->prepare("INSERT INTO DTR_Header 
        (professor_id, employee_id, full_name, department, date_from, date_to, year, month)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

    $stmt->bind_param(
        "isssssss",
        $professor_id,
        $data['employee_number'], // Using employee_number from input
        $data['name'],
        $data['department'],
        $data['date_from'],
        $data['date_to'],
        $data['year'], // Using the explicitly provided year
        $month
    );

    if (!$stmt->execute()) {
        throw new Exception('Failed to save DTR header: ' . $stmt->error);
    }

    $dtr_id = $stmt->insert_id;

    echo json_encode([
        'success' => true,
        'message' => 'DTR header saved successfully',
        'dtr_id' => $dtr_id,
        'professor_id' => $professor_id
    ]);

} catch (Exception $e) {
    http_response_code(400); // Bad Request
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>