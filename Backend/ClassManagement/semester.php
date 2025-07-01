<?php
header('Content-Type: application/json');
include '../db_connect.php';

// Get all semesters
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $query = "SELECT * FROM Semesters ORDER BY academic_year DESC, semester";
    
    $result = $conn->query($query);
    if ($result) {
        $semesters = [];
        while ($row = $result->fetch_assoc()) {
            $semesters[] = $row;
        }
        echo json_encode($semesters);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to fetch semesters: ' . $conn->error]);
    }
    exit;
}

// Add new semester
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    $required = ['academic_year', 'semester', 'date_from', 'date_to'];
    foreach ($required as $field) {
        if (!isset($data[$field])) {
            http_response_code(400);
            echo json_encode(['error' => "Missing required field: $field"]);
            exit;
        }
    }

    $stmt = $conn->prepare("INSERT INTO Semesters (academic_year, semester, date_from, date_to) 
                           VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", 
        $data['academic_year'], 
        $data['semester'], 
        $data['date_from'], 
        $data['date_to']
    );

    if ($stmt->execute()) {
        $newSemesterId = $stmt->insert_id;
        echo json_encode([
            'success' => 'Semester added successfully',
            'semester_id' => $newSemesterId
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to add semester: ' . $stmt->error]);
    }
    $stmt->close();
    exit;
}

// Delete semester
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $semester_id = $_GET['id'] ?? null;

    if (!$semester_id) {
        http_response_code(400);
        echo json_encode(['error' => 'Semester ID is required']);
        exit;
    }

    $stmt = $conn->prepare("DELETE FROM Semesters WHERE semester_id = ?");
    $stmt->bind_param("i", $semester_id);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo json_encode(['success' => 'Semester deleted successfully']);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Semester not found']);
        }
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to delete semester: ' . $stmt->error]);
    }
    $stmt->close();
    exit;
}

// Update semester
if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    if (!$data) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON data']);
        exit;
    }

    $required = ['semester_id', 'academic_year', 'semester', 'date_from', 'date_to'];
    foreach ($required as $field) {
        if (!isset($data[$field])) {
            http_response_code(400);
            echo json_encode(['error' => "Missing required field: $field"]);
            exit;
        }
    }

    $stmt = $conn->prepare("UPDATE Semesters SET 
        academic_year = ?, 
        semester = ?, 
        date_from = ?, 
        date_to = ? 
        WHERE semester_id = ?");
    
    $stmt->bind_param("ssssi", 
        $data['academic_year'], 
        $data['semester'], 
        $data['date_from'], 
        $data['date_to'], 
        $data['semester_id']
    );

    if ($stmt->execute()) {
        echo json_encode(['success' => 'Semester updated successfully']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update semester: ' . $stmt->error]);
    }
    $stmt->close();
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
exit;
?>