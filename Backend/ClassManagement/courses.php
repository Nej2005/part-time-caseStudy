<?php
header('Content-Type: application/json');
include '../db_connect.php';

// Get all courses
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $result = $conn->query("SELECT * FROM Courses ORDER BY program_name");
    if ($result) {
        $courses = [];
        while ($row = $result->fetch_assoc()) {
            $courses[] = $row;
        }
        echo json_encode($courses);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to fetch courses: ' . $conn->error]);
    }
    exit;
}

// Add new course
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['department'], $data['program_name'], $data['department_head'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields']);
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO Courses (department, program_name, department_head) 
                           VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $data['department'], $data['program_name'], $data['department_head']);

    if ($stmt->execute()) {
        echo json_encode(['success' => 'Course added successfully']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to add course: ' . $stmt->error]);
    }
    $stmt->close();
    exit;
}

// Delete course
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    if (!$data || !isset($data['id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Course ID is required']);
        exit;
    }

    $course_id = $data['id'];

    // First check if there are sections referencing this course
    $checkStmt = $conn->prepare("SELECT COUNT(*) FROM Sections WHERE course_id = ?");
    $checkStmt->bind_param("i", $course_id);
    $checkStmt->execute();
    $checkStmt->bind_result($sectionCount);
    $checkStmt->fetch();
    $checkStmt->close();

    if ($sectionCount > 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Cannot delete course because it has sections assigned']);
        exit;
    }

    $stmt = $conn->prepare("DELETE FROM Courses WHERE course_id = ?");
    $stmt->bind_param("i", $course_id);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo json_encode(['success' => 'Course deleted successfully']);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Course not found']);
        }
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to delete course: ' . $stmt->error]);
    }
    $stmt->close();
    exit;
}

//update courses
if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    if (!$data) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON data']);
        exit;
    }

    if (!isset($data['course_id'], $data['department'], $data['program_name'], $data['department_head'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields']);
        exit;
    }

    $stmt = $conn->prepare("UPDATE Courses SET department = ?, program_name = ?, department_head = ? WHERE course_id = ?");
    $stmt->bind_param("sssi", $data['department'], $data['program_name'], $data['department_head'], $data['course_id']);

    if ($stmt->execute()) {
        echo json_encode(['success' => 'Course updated successfully']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update course: ' . $stmt->error]);
    }
    $stmt->close();
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
exit;
?>