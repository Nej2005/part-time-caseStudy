<?php
include 'db_connect.php';

header('Content-Type: application/json');

if (!isset($_GET['professor_id'])) {
    echo json_encode(['error' => 'Professor ID not provided']);
    exit();
}

$professor_id = $_GET['professor_id'];

// Check if professor has any records in Form_Loads table
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM Form_Loads WHERE professor_id = ?");
$stmt->bind_param("i", $professor_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

echo json_encode(['hasRecords' => $row['count'] > 0]);

$conn->close();
?>