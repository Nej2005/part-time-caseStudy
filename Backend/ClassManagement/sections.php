<?php
header('Content-Type: application/json');
include '../db_connect.php';

// Get all sections with course info
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $query = "
        SELECT s.*, c.program_name 
        FROM Sections s
        LEFT JOIN Courses c ON s.course_id = c.course_id
        ORDER BY s.section_name
    ";

    $result = $conn->query($query);
    if ($result) {
        $sections = [];
        while ($row = $result->fetch_assoc()) {
            $sections[] = $row;
        }
        echo json_encode($sections);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to fetch sections: ' . $conn->error]);
    }
    exit;
}

// Add new section
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    $required = ['section_code', 'section_name', 'course_id'];
    foreach ($required as $field) {
        if (!isset($data[$field])) {
            http_response_code(400);
            echo json_encode(['error' => "Missing required field: $field"]);
            exit;
        }
    }

    $stmt = $conn->prepare("INSERT INTO Sections (section_code, section_name, course_id) 
                           VALUES (?, ?, ?)");
    $stmt->bind_param("ssi", $data['section_code'], $data['section_name'], $data['course_id']);

    if ($stmt->execute()) {
        $newSectionId = $stmt->insert_id;
        echo json_encode([
            'success' => 'Section added successfully',
            'section_id' => $newSectionId
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to add section: ' . $stmt->error]);
    }
    $stmt->close();
    exit;
}

// Delete section
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $section_id = $_GET['id'] ?? null;

    if (!$section_id) {
        http_response_code(400);
        echo json_encode(['error' => 'Section ID is required']);
        exit;
    }

    $stmt = $conn->prepare("DELETE FROM Sections WHERE section_id = ?");
    $stmt->bind_param("i", $section_id);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo json_encode(['success' => 'Section deleted successfully']);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Section not found']);
        }
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to delete section: ' . $stmt->error]);
    }
    $stmt->close();
    exit;
}

//update sections
if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    if (!$data) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON data']);
        exit;
    }

    if (!isset($data['section_id'], $data['section_code'], $data['section_name'], $data['course_id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields']);
        exit;
    }

    $stmt = $conn->prepare("UPDATE Sections SET section_code = ?, section_name = ?, course_id = ? WHERE section_id = ?");
    $stmt->bind_param("ssii", $data['section_code'], $data['section_name'], $data['course_id'], $data['section_id']);

    if ($stmt->execute()) {
        echo json_encode(['success' => 'Section updated successfully']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update section: ' . $stmt->error]);
    }
    $stmt->close();
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
exit;
?>