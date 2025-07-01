<?php
session_start();

error_log("Storing session - Semester: " . $_POST['semester'] . ", AY: " . $_POST['ay']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $validSemesters = ['1st Semester', '2nd Semester', 'Summer', '1st', '2nd'];
    $response = ['success' => true];
    
    if (isset($_POST['semester'])) {
        // Convert short codes to full names
        $semester = $_POST['semester'];
        if ($semester === '1st') {
            $semester = '1st Semester';
        } elseif ($semester === '2nd') {
            $semester = '2nd Semester';
        }
        
        if (in_array($semester, ['1st Semester', '2nd Semester', 'Summer'])) {
            $_SESSION['current_semester'] = $semester;
            $response['semester'] = $semester;
        } else {
            $response['success'] = false;
            $response['message'] = 'Invalid semester value';
        }
    }
    
    if (isset($_POST['ay'])) {
        // Basic AY format validation (e.g., 2023-2024)
        if (preg_match('/^\d{4}-\d{4}$/', $_POST['ay'])) {
            $_SESSION['current_ay'] = $_POST['ay'];
            $response['ay'] = $_POST['ay'];
        } else {
            $response['success'] = false;
            $response['message'] = 'Invalid academic year format';
        }
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}