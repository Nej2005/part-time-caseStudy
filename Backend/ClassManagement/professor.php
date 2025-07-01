<?php
header('Content-Type: application/json');
include '../db_connect.php';

// Get all professors for dropdown
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $result = $conn->query("SELECT professor_id, CONCAT(first_name, ' ', last_name) AS full_name 
                           FROM PartTime_Professor 
                           ORDER BY last_name, first_name");
    if ($result) {
        $professors = [];
        while ($row = $result->fetch_assoc()) {
            $professors[] = $row;
        }
        echo json_encode($professors);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to fetch professors: ' . $conn->error]);
    }
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
exit;
?>