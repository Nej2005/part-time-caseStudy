<?php
require_once '../db_connect.php';

// Get current month and year
$currentMonth = date('F');
$currentYear = date('Y');

// Get all professors who haven't submitted AR for current month
$sql = "SELECT p.professor_id, p.first_name, p.last_name, p.email_address 
        FROM PartTime_Professor p
        WHERE NOT EXISTS (
            SELECT 1 FROM AR_Header a 
            WHERE a.professor_id = p.professor_id 
            AND a.month = ? 
            AND a.year = ?
        )";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $currentMonth, $currentYear);
$stmt->execute();
$result = $stmt->get_result();

$pendingProfessors = [];
while ($row = $result->fetch_assoc()) {
    $pendingProfessors[] = $row;
}

// Here you would typically send email notifications
// For now, we'll just return the count
$response = [
    'success' => true,
    'message' => 'Notifications processed',
    'count' => count($pendingProfessors)
];

header('Content-Type: application/json');
echo json_encode($response);

$stmt->close();
$conn->close();
?>