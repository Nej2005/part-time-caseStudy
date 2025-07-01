<?php
session_start();
require __DIR__ . '/../../phpspreadsheet/vendor/autoload.php';
include '../db_connect.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

if (!isset($_GET['professor_id'])) {
    header("Location: emp-ar.php");
    exit();
}

if (!isset($_SESSION['user_id'])) {
    die("Unauthorized access");
}

$professor_id = $_GET['professor_id'] ?? null;
if (!$professor_id) {
    die("Invalid professor ID");
}

// Validate the requesting user owns this professor_id
if ($_SESSION['professor_id'] != $professor_id && $_SESSION['role'] != 'admin') {
    die("Unauthorized access to this data");
}


$professor_id = $_GET['professor_id'];

// Fetch all the data (similar to your existing code)
$currentMonth = date('F');
$currentYear = date('Y');
$days_in_month = cal_days_in_month(CAL_GREGORIAN, date('n'), $currentYear);
$month_display = "$currentMonth 1-$days_in_month, $currentYear";

// Fetch AR Header
$stmt_header = $conn->prepare("SELECT * FROM AR_Header WHERE professor_id = ? AND month = ? AND year = ?");
$stmt_header->bind_param("iss", $professor_id, $currentMonth, $currentYear);
$stmt_header->execute();
$header_result = $stmt_header->get_result();
$ar_header = $header_result->fetch_assoc();

// Fetch professor details
$stmt_prof = $conn->prepare("SELECT p.*, u.employee_id FROM PartTime_Professor p JOIN Users u ON p.email_address = u.email_address WHERE p.professor_id = ?");
$stmt_prof->bind_param("i", $professor_id);
$stmt_prof->execute();
$prof_result = $stmt_prof->get_result();
$professor = $prof_result->fetch_assoc();

// Department head
$department_mapping = [
    'College of Computer Studies' => 'computer-studies',
    'College of Nursing' => 'nursing',
    'College of Engineering' => 'engineering',
    'College of Education' => 'education',
    'College of Arts and Science' => 'arts-science',
    'College of Business and Accountancy' => 'business-accountancy',
    'College of Hospitality Management' => 'hospitality-management',
];
$db_department = isset($department_mapping[$professor['department']])
    ? $department_mapping[$professor['department']]
    : $professor['department'];
$stmt_dept = $conn->prepare("SELECT department_head FROM Courses WHERE department = ? LIMIT 1");
$stmt_dept->bind_param("s", $db_department);
$stmt_dept->execute();
$result_dept = $stmt_dept->get_result();
$department_head = "Dean/Dept. Head name";
if ($result_dept->num_rows > 0) {
    $dept_data = $result_dept->fetch_assoc();
    $department_head = $dept_data['department_head'];
}

// Fetch AR Details
$ar_details = [];
if ($ar_header) {
    $stmt_details = $conn->prepare("SELECT * FROM AR_Details WHERE ar_id = ?");
    $stmt_details->bind_param("i", $ar_header['ar_id']);
    $stmt_details->execute();
    $details_result = $stmt_details->get_result();
    $ar_details = $details_result->fetch_all(MYSQLI_ASSOC);
}

// Calculate total hours
$total_hours = 0;
if ($ar_details) {
    foreach ($ar_details as $detail) {
        $total_hours += $detail['hours_rendered'];
    }
}

// Set semester and academic year
$semester = $ar_header['semester'] ?? '1st Semester';
$academic_year = $ar_header['academic_year'] ?? date('Y') . '-' . (date('Y') + 1);
$period = $ar_header['period'] ?? $month_display;

// Faculty name
$faculty_name = htmlspecialchars($professor['first_name']) . ' ' .
    (!empty($professor['middle_initial']) ? htmlspecialchars($professor['middle_initial']) . '. ' : '') .
    htmlspecialchars($professor['last_name']);

// Load the template
$templatePath = '../../AR_Template.xlsx';
$spreadsheet = IOFactory::load($templatePath);
$sheet = $spreadsheet->getActiveSheet();

// Fill in the data
$sheet->setCellValue('B8', convertSemesterText($semester) . " A.Y. " . $academic_year);
$sheet->setCellValue('F9', $period);
$sheet->setCellValue('C11', $faculty_name);
$sheet->setCellValue('C12', $professor['department']);

$dayOrder = [
    'Monday' => 1,
    'Tuesday' => 2,
    'Wednesday' => 3,
    'Thursday' => 4,
    'Friday' => 5,
    'Saturday' => 6
];

// Sort AR details by day of week (Monday to Saturday)
usort($ar_details, function ($a, $b) use ($dayOrder) {
    $dayA = $dayOrder[$a['day_of_week'] ?? 7]; // Unknown days go last
    $dayB = $dayOrder[$b['day_of_week'] ?? 7];
    return $dayA - $dayB;
});

// Function to format day abbreviation
function formatDayAbbreviation($day)
{
    $abbreviations = [
        'Monday' => 'Mon',
        'Tuesday' => 'Tues',
        'Wednesday' => 'Wed',
        'Thursday' => 'Thur',
        'Friday' => 'Fri',
        'Saturday' => 'Sat'
    ];
    return $abbreviations[$day] ?? substr($day, 0, 3);
}

function cleanSubjectDescription($description) {
    $cleaned = preg_replace_callback('/\((?!LAB|LEC)([^)]*)\)/', function($matches) {
        return ''; 
    }, $description);
    
    $cleaned = trim($cleaned);
    
    $cleaned = preg_replace('/\s+/', ' ', $cleaned);
    
    return $cleaned;
}

$row = 18;
foreach ($ar_details as $detail) {
    if ($row > 30) break; // Don't go beyond row 30

    $subjectDescription = cleanSubjectDescription($detail['subject_description']);
    $sheet->setCellValue('B' . $row, $subjectDescription);

    $sheet->setCellValue('D' . $row, $detail['no_of_units']);
    $sheet->setCellValue('F' . $row, $detail['inclusive_dates']);
    $sheet->setCellValue('H' . $row, $detail['class_time_schedule']);

    // Format the day and hours as "Mon- 12"
    $dayAbbr = formatDayAbbreviation($detail['day_of_week']);
    $hoursFormatted = $dayAbbr . '- ' . $detail['hours_rendered'];
    $sheet->setCellValue('J' . $row, $hoursFormatted);

    $row++;
}

$sheet->setCellValue('J32', $total_hours);
$sheet->setCellValue('B36', $faculty_name);
$sheet->setCellValue('F36', $department_head);
$sheet->setCellValue('F41', $ar_header['checked_by'] ?? 'ALBERT P. CABIAO');

// Handle signatures if they exist
if (!empty($ar_header['faculty_signature'])) {
    $signatureData = $ar_header['faculty_signature'];
    if (strpos($signatureData, 'data:image/png;base64,') === 0) {
        $signatureData = str_replace('data:image/png;base64,', '', $signatureData);
    }
    $signaturePath = tempnam(sys_get_temp_dir(), 'faculty_sig');
    file_put_contents($signaturePath, base64_decode($signatureData));

    $facultyDrawing = new \PhpOffice\PhpSpreadsheet\Worksheet\Drawing();
    $facultyDrawing->setPath($signaturePath);
    $facultyDrawing->setCoordinates('B34');
    $facultyDrawing->setWidth(220);
    $facultyDrawing->setHeight(40);
    $facultyDrawing->setOffsetX(90); // Increased offset for better centering
    $facultyDrawing->setOffsetY(5);
    $facultyDrawing->setWorksheet($sheet);

    // Clean up temp file
    register_shutdown_function(function () use ($signaturePath) {
        if (file_exists($signaturePath)) {
            unlink($signaturePath);
        }
    });
}

if (!empty($ar_header['head_signature'])) {
    $signatureData = $ar_header['head_signature'];
    if (strpos($signatureData, 'data:image/png;base64,') === 0) {
        $signatureData = str_replace('data:image/png;base64,', '', $signatureData);
    }
    $signaturePath = tempnam(sys_get_temp_dir(), 'head_sig');
    file_put_contents($signaturePath, base64_decode($signatureData));

    // Create drawing object for head signature
    $headDrawing = new \PhpOffice\PhpSpreadsheet\Worksheet\Drawing();
    $headDrawing->setPath($signaturePath);
    $headDrawing->setCoordinates('F34'); // Changed from F35 to F34
    $headDrawing->setWidth(100);
    $headDrawing->setHeight(40);
    $headDrawing->setOffsetX(90); // Increased offset for better centering
    $headDrawing->setOffsetY(5);
    $headDrawing->setWorksheet($sheet);

    // Clean up temp file
    register_shutdown_function(function () use ($signaturePath) {
        if (file_exists($signaturePath)) {
            unlink($signaturePath);
        }
    });
}

// Set headers and output the file
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="AR_Report_' . $faculty_name . '_' . $currentMonth . '_' . $currentYear . '.xlsx"');
header('Cache-Control: max-age=0');

$writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
$writer->save('php://output');
exit;

function convertSemesterText($semester)
{
    $conversions = [
        '1st Semester' => 'First Semester',
        '2nd Semester' => 'Second Semester',
        'Summer' => 'Summer'
    ];
    return $conversions[$semester] ?? $semester;
}

