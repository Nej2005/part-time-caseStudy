<?php
session_start();
include 'db_connect.php';

header('Content-Type: application/json');

// Enable detailed error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Log incoming request data for debugging
error_log("Incoming POST data: " . print_r($_POST, true));

// Validate required parameters first
$required = ['form_id', 'professor_id', 'school_year', 'semester', 'period', 'college_department'];
foreach ($required as $field) {
    if (!isset($_POST[$field]) || empty(trim($_POST[$field]))) {
        echo json_encode(['success' => false, 'message' => "Missing required field: $field"]);
        exit;
    }
}

// Sanitize and validate input data
$form_id = (int)$_POST['form_id'];
$professor_id = (int)$_POST['professor_id'];
$school_year = trim($_POST['school_year']);
$semester = strtolower(trim($_POST['semester']));
$period = trim($_POST['period']);
$college_department = trim($_POST['college_department']);

// Verify the form exists and belongs to this professor
$stmt = $conn->prepare("SELECT school_year, semester FROM Form_Loads WHERE form_id = ? AND professor_id = ?");
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => "Database error: " . $conn->error]);
    exit;
}

$stmt->bind_param("ii", $form_id, $professor_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Form not found or access denied']);
    exit;
}

$form_data = $result->fetch_assoc();

// Verify semester/year matches the form's data
if (
    strtolower($form_data['semester']) !== strtolower($semester) ||
    $form_data['school_year'] !== $school_year
) {

    // If mismatch, check if this is a new semester/year combination
    $checkNew = $conn->prepare("SELECT COUNT(*) FROM Form_Loads 
                               WHERE professor_id = ? 
                               AND school_year = ? 
                               AND semester = ?");
    $checkNew->bind_param("iss", $professor_id, $school_year, $semester);
    $checkNew->execute();
    $checkNew->bind_result($count);
    $checkNew->fetch();
    $checkNew->close();

    if ($count == 0) {
        $createNew = $conn->prepare("INSERT INTO Form_Loads 
                                    (professor_id, school_year, semester, period, college_department) 
                                    VALUES (?, ?, ?, ?, ?)");
        $createNew->bind_param("issss", $professor_id, $school_year, $semester, $period, $college_department);
        if (!$createNew->execute()) {
            echo json_encode(['success' => false, 'message' => 'Failed to create new form']);
            exit;
        }
        $form_id = $conn->insert_id;
    } else {
        // Mismatch with existing data - update the form_id to the correct one
        $getCorrectForm = $conn->prepare("SELECT form_id FROM Form_Loads 
                                         WHERE professor_id = ? 
                                         AND school_year = ? 
                                         AND semester = ? 
                                         ORDER BY created_at DESC LIMIT 1");
        $getCorrectForm->bind_param("iss", $professor_id, $school_year, $semester);
        $getCorrectForm->execute();
        $getCorrectForm->bind_result($correct_form_id);
        $getCorrectForm->fetch();
        $getCorrectForm->close();

        if ($correct_form_id) {
            $form_id = $correct_form_id;
        } else {
            // If we still can't find the correct form, return an error
            echo json_encode([
                'success' => false,
                'message' => 'Mismatch between form data and selected semester',
                'form_data' => [
                    'school_year' => $form_data['school_year'],
                    'semester' => $form_data['semester']
                ],
                'submitted_data' => [
                    'school_year' => $school_year,
                    'semester' => $semester
                ]
            ]);
            exit;
        }
    }
}

// Validate subject codes exist
if (!isset($_POST['subject_codes'])) {
    echo json_encode(['success' => false, 'message' => 'No subject codes provided']);
    exit;
}

// Get valid subjects from database
$valid_subjects = [];
$subject_result = $conn->query("SELECT subject_code FROM Subjects");
if ($subject_result) {
    while ($row = $subject_result->fetch_assoc()) {
        $valid_subjects[$row['subject_code']] = true;
    }
}

// Validate each row's data
$rowCount = count($_POST['subject_codes']);
for ($i = 0; $i < $rowCount; $i++) {
    $subject_code = trim($_POST['subject_codes'][$i]);

    if (empty($subject_code)) {
        echo json_encode(['success' => false, 'message' => "Subject code is required for row " . ($i + 1)]);
        exit;
    }

    if (!isset($valid_subjects[$subject_code])) {
        echo json_encode(['success' => false, 'message' => "Invalid subject code '{$subject_code}' for row " . ($i + 1)]);
        exit;
    }

    // Validate numeric fields
    $numeric_fields = ['lec_hours', 'lab_hours', 'hrs_per_week'];
    foreach ($numeric_fields as $field) {
        if (isset($_POST[$field][$i])) {
            $value = str_replace(',', '.', $_POST[$field][$i]);
            if (!is_numeric($value)) {
                echo json_encode(['success' => false, 'message' => "Invalid numeric value for {$field} in row " . ($i + 1)]);
                exit;
            }
        }
    }
}

// Begin transaction
$conn->begin_transaction();

try {
    // Update the main form
    $update_form = "UPDATE Form_Loads 
                   SET period = ?, college_department = ?, updated_at = NOW()
                   WHERE form_id = ?";
    $stmt = $conn->prepare($update_form);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("ssi", $period, $college_department, $form_id);
    if (!$stmt->execute()) {
        throw new Exception("Failed to update form: " . $stmt->error);
    }

    // Delete existing details
    $delete_details = "DELETE FROM Form_Load_Details WHERE form_id = ?";
    $stmt = $conn->prepare($delete_details);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("i", $form_id);
    if (!$stmt->execute()) {
        throw new Exception("Failed to delete existing details: " . $stmt->error);
    }

    // Insert new details
    $insert_detail = "INSERT INTO Form_Load_Details 
                (form_id, subject_code, subject_base_description, subject_type,
                 lec_hours, lab_hours, hrs_per_week, 
                 monday, tuesday, wednesday, thursday, friday, saturday, sunday,
                 room, section, disabled)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($insert_detail);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    for ($i = 0; $i < $rowCount; $i++) {
        $subject_code = trim($_POST['subject_codes'][$i]);
        $base_desc = isset($_POST['subject_base_description'][$i]) ?
            trim($_POST['subject_base_description'][$i]) : '';
        $type = isset($_POST['subject_type'][$i]) ?
            trim($_POST['subject_type'][$i]) : '';

        if ($type !== 'LEC' && $type !== 'LAB') {
            $type = '';
        }

        // Clean up description
        $base_desc = preg_replace('/\s*\(LEC\)|\s*\(LAB\)|\s*\(\)/', '', $base_desc);
        $base_desc = trim($base_desc);

        // Get numeric values with proper formatting
        $lec_hours = isset($_POST['lec_hours'][$i]) ?
            (float)str_replace(',', '.', $_POST['lec_hours'][$i]) : 0.0;
        $lab_hours = isset($_POST['lab_hours'][$i]) ?
            (float)str_replace(',', '.', $_POST['lab_hours'][$i]) : 0.0;
        $hrs_per_week = isset($_POST['hrs_per_week'][$i]) ?
            (float)str_replace(',', '.', $_POST['hrs_per_week'][$i]) : 0.0;

        // Get day values
        $days = [];
        foreach (['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'] as $day) {
            $days[$day] = isset($_POST[$day][$i]) ? trim($_POST[$day][$i]) : '';
        }

        $room = isset($_POST['room'][$i]) ? trim($_POST['room'][$i]) : '';
        $section = isset($_POST['section'][$i]) ? trim($_POST['section'][$i]) : '';
        $disabled = isset($_POST['disabled_rows'][$i]) ? (int)$_POST['disabled_rows'][$i] : 0;

        $stmt->bind_param(
            "isssddssssssssssi",
            $form_id,
            $subject_code,
            $base_desc,
            $type,
            $lec_hours,
            $lab_hours,
            $hrs_per_week,
            $days['monday'],
            $days['tuesday'],
            $days['wednesday'],
            $days['thursday'],
            $days['friday'],
            $days['saturday'],
            $days['sunday'],
            $room,
            $section,
            $disabled
        );

        if (!$stmt->execute()) {
            throw new Exception("Failed to insert detail for row " . ($i + 1) . ": " . $stmt->error);
        }
    }

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Form saved successfully']);
} catch (Exception $e) {
    $conn->rollback();
    error_log("Error in save_formload.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'error_details' => $conn->error
    ]);
}
