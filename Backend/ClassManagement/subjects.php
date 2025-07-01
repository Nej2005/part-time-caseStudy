<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

include '../db_connect.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!$conn) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

// Get all subjects
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $result = $conn->query("SELECT * FROM Subjects ORDER BY subject_name");
    if ($result) {
        $subjects = [];
        while ($row = $result->fetch_assoc()) {
            $subjects[] = $row;
        }
        echo json_encode($subjects);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to fetch subjects: ' . $conn->error]);
    }
    exit;
}

// Add new subject
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the raw POST data
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    if (!$data) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON data']);
        exit;
    }

    if (!isset($data['subject_code'], $data['subject_name'], $data['units'], $data['department'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields']);
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO Subjects (subject_code, subject_name, units, department) 
                           VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssis", $data['subject_code'], $data['subject_name'], $data['units'], $data['department']);

    if ($stmt->execute()) {
        echo json_encode(['success' => 'Subject added successfully']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to add subject: ' . $stmt->error]);
    }
    $stmt->close();
    exit;
}

// Delete subject
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $subject_id = $_GET['id'] ?? null;

    if (!$subject_id) {
        http_response_code(400);
        echo json_encode(['error' => 'Subject ID is required']);
        exit;
    }

    $stmt = $conn->prepare("DELETE FROM Subjects WHERE subject_id = ?");
    $stmt->bind_param("i", $subject_id);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo json_encode(['success' => 'Subject deleted successfully']);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Subject not found']);
        }
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to delete subject: ' . $stmt->error]);
    }
    $stmt->close();
    exit;
}

// Update subject
if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    if (!$data) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON data']);
        exit;
    }

    if (!isset($data['subject_id'], $data['subject_code'], $data['subject_name'], $data['units'], $data['department'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields']);
        exit;
    }

    $stmt = $conn->prepare("UPDATE Subjects SET subject_code = ?, subject_name = ?, units = ?, department = ? WHERE subject_id = ?");
    $stmt->bind_param("ssisi", $data['subject_code'], $data['subject_name'], $data['units'], $data['department'], $data['subject_id']);

    if ($stmt->execute()) {
        echo json_encode(['success' => 'Subject updated successfully']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update subject: ' . $stmt->error]);
    }
    $stmt->close();
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
exit;
?>