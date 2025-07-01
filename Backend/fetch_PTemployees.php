<?php
session_start();
include 'db_connect.php';

ob_start();

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized access']);
    ob_end_flush();
    exit();
}

try {
    // Query professors and employee IDs
    $sql = "SELECT p.*, u.employee_id 
            FROM PartTime_Professor p
            JOIN Users u ON p.email_address = u.email_address";

    $result = mysqli_query($conn, $sql);

    if (!$result) {
        throw new Exception('Database query failed: ' . mysqli_error($conn));
    }

    $professors = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $professors[] = $row;
    }
    
    // Clear any potential output
    ob_end_clean();
    echo json_encode(['success' => true, 'data' => $professors]);
    
} catch (Exception $e) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
} finally {
    if (isset($conn)) {
        mysqli_close($conn);
    }
}
?>