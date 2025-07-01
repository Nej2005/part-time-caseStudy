<?php
session_start();
require_once 'db_connect.php';

// Get parameters
$professor_id = $_POST['professor_id'] ?? null;
$semester = $_POST['semester'] ?? null;
$school_year = $_POST['school_year'] ?? null;
$month = $_POST['month'] ?? null;
$year = $_POST['year'] ?? null;

// Debug logging
error_log("Fetching form load data for professor_id: $professor_id, semester: $semester, school_year: $school_year");

if (!isset($_SESSION['professor_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

if ($_SESSION['user_type'] != 'Admin_Secretary' && $_SESSION['professor_id'] != $professor_id) {
    error_log("Unauthorized access attempt: Session professor_id={$_SESSION['professor_id']}, Request professor_id=$professor_id");
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

if (!$professor_id || !$semester || !$school_year) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

$stmt = $conn->prepare("SELECT COUNT(*) as count FROM PartTime_Professor WHERE professor_id = ?");
$stmt->bind_param("i", $professor_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$stmt->close();

if ($row['count'] == 0) {
    echo json_encode(['success' => false, 'message' => 'Professor not found']);
    exit;
}

// Get professor details
$professor = [];
$stmt = $conn->prepare("SELECT first_name, last_name, middle_initial, department FROM PartTime_Professor WHERE professor_id = ?");
$stmt->bind_param("i", $professor_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $professor = $result->fetch_assoc();
    $professor['full_name'] = htmlspecialchars($professor['first_name'] . ' ' . 
        ($professor['middle_initial'] ? $professor['middle_initial'] . '. ' : '') . 
        $professor['last_name']);
}
$stmt->close();

// Get department head
$department_head = "Dean/Dept. Head name";
$department_mapping = [
    'College of Computer Studies' => 'computer-studies',
    'College of Nursing' => 'nursing',
    'College of Engineering' => 'engineering',
    'College of Education' => 'education',
    'College of Arts and Science' => 'arts-science',
    'College of Business and Accountancy' => 'business-accountancy',
    'College of Hospitality Management' => 'hospitality-management',
];

$courses_department = $department_mapping[$professor['department']] ?? strtolower(str_replace(' ', '-', $professor['department']));

$stmt = $conn->prepare("SELECT department_head FROM Courses WHERE department = ? LIMIT 1");
$stmt->bind_param("s", $courses_department);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $department_head = htmlspecialchars($row['department_head']);
}
$stmt->close();

// Get subjects from formload for the current semester
$subjects = [];
$stmt = $conn->prepare("SELECT 
    fld.detail_id,
    fld.subject_code, 
    s.subject_name, 
    fld.subject_description, 
    fld.lec_hours, 
    fld.lab_hours, 
    fld.hrs_per_week, 
    GROUP_CONCAT(DISTINCT fld.section SEPARATOR ', ') AS sections,
    fld.monday, 
    fld.tuesday, 
    fld.wednesday, 
    fld.thursday, 
    fld.friday, 
    fld.saturday, 
    fld.sunday,
    GROUP_CONCAT(DISTINCT 
        CASE 
            WHEN fld.sunday != '' THEN CONCAT('Sun ', fld.sunday)
            WHEN fld.saturday != '' THEN CONCAT('Sat ', fld.saturday)
            WHEN fld.friday != '' THEN CONCAT('Fri ', fld.friday)
            WHEN fld.thursday != '' THEN CONCAT('Thu ', fld.thursday)
            WHEN fld.wednesday != '' THEN CONCAT('Wed ', fld.wednesday)
            WHEN fld.tuesday != '' THEN CONCAT('Tue ', fld.tuesday)
            WHEN fld.monday != '' THEN CONCAT('Mon ', fld.monday)
        END ORDER BY 
            CASE 
                WHEN fld.sunday != '' THEN 1
                WHEN fld.saturday != '' THEN 2
                WHEN fld.friday != '' THEN 3
                WHEN fld.thursday != '' THEN 4
                WHEN fld.wednesday != '' THEN 5
                WHEN fld.tuesday != '' THEN 6
                WHEN fld.monday != '' THEN 7
            END SEPARATOR ', ') AS schedule,
    s.units
    FROM Form_Load_Details fld
    JOIN Form_Loads fl ON fld.form_id = fl.form_id
    JOIN Subjects s ON fld.subject_code = s.subject_code
    WHERE fl.professor_id = ?
    AND fl.school_year = ?
    AND fl.semester = ?
    AND (fld.disabled IS NULL OR fld.disabled = 0)
    GROUP BY 
        fld.detail_id,
        fld.subject_code, 
        s.subject_name, 
        fld.subject_description,
        fld.lec_hours, 
        fld.lab_hours, 
        fld.hrs_per_week,
        fld.monday,
        fld.tuesday,
        fld.wednesday,
        fld.thursday,
        fld.friday,
        fld.saturday,
        fld.sunday,
        s.units");

$stmt->bind_param("iss", $professor_id, $school_year, $semester);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $subjects[] = $row;
}
$stmt->close();

// Calculate total units
$total_units = 0;
foreach ($subjects as $subject) {
    $total_units += $subject['units'];
}

// Get current month display
$monthNames = ['January', 'February', 'March', 'April', 'May', 'June', 
               'July', 'August', 'September', 'October', 'November', 'December'];
$monthName = $monthNames[$month - 1] ?? 'Month';
$days_in_month = cal_days_in_month(CAL_GREGORIAN, $month, $year);
$month_display = "$monthName 1-$days_in_month, $year";

// Get DTR data for the professor
$dtrData = [];
$stmt = $conn->prepare("
    SELECT h.dtr_id, h.date_from, h.date_to, h.month, h.year,
           d.date, d.time_in_am, d.time_out_am, d.time_in_pm, d.time_out_pm,
           d.hours_rendered, d.minutes_rendered
    FROM DTR_Header h
    JOIN DTR_Details d ON h.dtr_id = d.dtr_id
    WHERE h.professor_id = ?
    AND h.year = ?
    AND (h.month = ? OR h.month = ?)
");
$stmt->bind_param("isis", $professor_id, $year, $month, $monthName);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $dtrData[] = $row;
}
$stmt->close();

$conn->close();

echo json_encode([
    'success' => true,
    'professor' => $professor,
    'department_head' => $department_head,
    'month_display' => $month_display,
    'subjects' => $subjects,
    'total_units' => $total_units,
    'dtr_data' => $dtrData
]);
?>