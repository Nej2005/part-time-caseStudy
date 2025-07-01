<?php
session_start();
include 'db_connect.php';

header('Content-Type: application/json');

$professor_id = $_GET['professor_id'] ?? null;
$school_year = $_GET['school_year'] ?? null;
$semester = $_GET['semester'] ?? null;

if (!$professor_id || !$school_year || !$semester) {
    die(json_encode(['success' => false, 'message' => 'Missing parameters']));
}

try {
    $query = "SELECT 1 FROM Form_Loads 
              WHERE professor_id = ? 
              AND school_year = ? 
              AND semester = ? 
              LIMIT 1";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iss", $professor_id, $school_year, $semester);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $hasData = $result->num_rows > 0;
    
    echo json_encode([
        'success' => true,
        'hasData' => $hasData,
        'professor_id' => $professor_id,
        'school_year' => $school_year,
        'semester' => $semester
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}

$conn->close();
?>