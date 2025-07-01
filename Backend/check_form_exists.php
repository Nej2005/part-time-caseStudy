<?php
session_start();
include 'db_connect.php';

$professor_id = $_GET['professor_id'] ?? null;
$school_year = $_GET['school_year'] ?? null;
$semester = $_GET['semester'] ?? null;

$query = "SELECT 1 FROM Form_Loads WHERE professor_id = ?";
$params = [$professor_id];
$types = "i";

if ($school_year && $semester) {
    $query .= " AND school_year = ? AND semester = ?";
    $params[] = $school_year;
    $params[] = $semester;
    $types .= "ss";
}

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

echo json_encode(['exists' => $result->num_rows > 0]);

$conn->close();
?>