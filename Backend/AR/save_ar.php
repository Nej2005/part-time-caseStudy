<?php
session_start();
require_once '../db_connect.php';

// Get the JSON data from the request
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Invalid data format']);
    exit;
}

// Validate required fields
if (!isset($data['header']['total_hours_rendered']) || $data['header']['total_hours_rendered'] === '') {
    echo json_encode(['success' => false, 'message' => 'Total hours rendered is required']);
    exit;
}

// Convert semester to full format if needed
$semester = $data['header']['semester'];
if ($semester === '1st') {
    $semester = '1st Semester';
} elseif ($semester === '2nd') {
    $semester = '2nd Semester';
} elseif ($semester === 'Summer') {
    $semester = 'Summer';
}

$conn->begin_transaction();

try {
    // Insert into AR_Header
    $header = $data['header'];
    $stmt = $conn->prepare("
        INSERT INTO AR_Header (
            professor_id, employee_id, full_name, department, no_of_units, 
            academic_year, semester, month, year, period, 
            total_hours_rendered, faculty_signature, checked_by
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    // Convert total_hours_rendered to float to ensure proper type
    $total_hours = (float)$header['total_hours_rendered'];
    
    $stmt->bind_param(
        "isssisssssiss", 
        $header['professor_id'],
        $header['employee_id'],
        $header['full_name'],
        $header['department'],
        $header['no_of_units'],
        $header['academic_year'],
        $semester, 
        $header['month'],
        $header['year'],
        $header['period'],
        $total_hours, 
        $header['faculty_signature'],
        $header['checked_by'] 
    );
    
    if (!$stmt->execute()) {
        throw new Exception("Error saving AR header: " . $stmt->error);
    }
    
    $ar_id = $conn->insert_id;
    $stmt->close();
    
    // Insert into AR_Details
    foreach ($data['details'] as $detail) {
        $stmt = $conn->prepare("
            INSERT INTO AR_Details (
                ar_id, subject_description, no_of_units, 
                inclusive_dates, class_time_schedule, day_of_week, hours_rendered
            ) VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->bind_param(
            "isisssd", 
            $ar_id,
            $detail['subject_description'],
            $detail['no_of_units'],
            $detail['inclusive_dates'],
            $detail['class_time_schedule'],
            $detail['day_of_week'],
            $detail['hours_rendered']
        );
        
        if (!$stmt->execute()) {
            throw new Exception("Error saving AR detail: " . $stmt->error);
        }
        
        $stmt->close();
    }
    
    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'AR saved successfully']);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>