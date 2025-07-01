<?php
session_start();
require_once '../db_connect.php';

// Check if user is authenticated
if (!isset($_SESSION['professor_id'])) {
    header('Content-Type: application/json');
    die(json_encode(['success' => false, 'error' => 'Not authenticated']));
}

$professor_id = $_SESSION['professor_id'];
$year = $_GET['year'] ?? '';
$month = $_GET['month'] ?? '';

error_log("Fetching AR data for professor_id: $professor_id, year: $year, month: $month");

// Validate required parameters
if (empty($year) || empty($month)) {
    header('Content-Type: application/json');
    die(json_encode(['success' => false, 'error' => 'Year and month are required']));
}

// Convert month number to name if needed
if (is_numeric($month)) {
    $monthNum = (int)$month;
    if ($monthNum >= 1 && $monthNum <= 12) {
        $dateObj = DateTime::createFromFormat('!m', $monthNum);
        $month = $dateObj->format('F');
    }
}

$conn->begin_transaction();

try {
    // Get AR header with all necessary fields including semester
    $stmt = $conn->prepare("
        SELECT 
            ar_id, professor_id, employee_id, full_name, department, 
            no_of_units, academic_year, semester, month, year, 
            period, total_hours_rendered, faculty_signature, head_signature, checked_by,
            created_at, updated_at
        FROM AR_Header 
        WHERE professor_id = ? 
        AND year = ? 
        AND month = ?
    ");
    $stmt->bind_param("iss", $professor_id, $year, $month);
    $stmt->execute();
    $header = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$header) {
        throw new Exception('AR not found');
    }

    // Get AR details with all fields
    $stmt = $conn->prepare("
        SELECT 
            detail_id, ar_id, form_load_detail_id, subject_description,
            no_of_units, inclusive_dates, class_time_schedule,
            day_of_week, hours_rendered, created_at
        FROM AR_Details 
        WHERE ar_id = ?
        ORDER BY detail_id
    ");
    $stmt->bind_param("i", $header['ar_id']);
    $stmt->execute();
    $details = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Format the response data
    $response = [
        'success' => true,
        'header' => [
            'ar_id' => $header['ar_id'],
            'professor_id' => $header['professor_id'],
            'employee_id' => $header['employee_id'],
            'full_name' => $header['full_name'],
            'department' => $header['department'],
            'no_of_units' => $header['no_of_units'],
            'academic_year' => $header['academic_year'],
            'semester' => $header['semester'], // Include semester in response
            'month' => $header['month'],
            'year' => $header['year'],
            'period' => $header['period'],
            'total_hours_rendered' => $header['total_hours_rendered'],
            'faculty_signature' => $header['faculty_signature'],
            'head_signature' => $header['head_signature'],
            'checked_by' => $header['checked_by'],
            'created_at' => $header['created_at'],
            'updated_at' => $header['updated_at']
        ],
        'details' => array_map(function($detail) {
            return [
                'detail_id' => $detail['detail_id'],
                'ar_id' => $detail['ar_id'],
                'form_load_detail_id' => $detail['form_load_detail_id'],
                'subject_description' => $detail['subject_description'],
                'no_of_units' => $detail['no_of_units'],
                'inclusive_dates' => $detail['inclusive_dates'],
                'class_time_schedule' => $detail['class_time_schedule'],
                'day_of_week' => $detail['day_of_week'],
                'hours_rendered' => $detail['hours_rendered'],
                'created_at' => $detail['created_at']
            ];
        }, $details)
    ];

    $conn->commit();
    
    header('Content-Type: application/json');
    echo json_encode($response);

} catch (Exception $e) {
    $conn->rollback();
    error_log("Error fetching AR data: " . $e->getMessage());
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

$conn->close();
?>