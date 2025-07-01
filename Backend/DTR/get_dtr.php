<?php
header('Content-Type: application/json');
require_once '../db_connect.php';

// Get and validate parameters
$year = $_GET['year'] ?? '';
$monthNumber = $_GET['month'] ?? '';
$professor_id = isset($_GET['professor_id']) ? (int)$_GET['professor_id'] : 0;

if (empty($year) || empty($monthNumber) || $professor_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid parameters']);
    exit;
}

// Month number to name mapping
$monthNames = [
    1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
    5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
    9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
];

if (!isset($monthNames[(int)$monthNumber])) {
    echo json_encode(['success' => false, 'error' => 'Invalid month number']);
    exit;
}

$monthName = $monthNames[(int)$monthNumber];

// First try with exact month name match
$query = "SELECT * FROM DTR_Header WHERE year = ? AND month = ? AND professor_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ssi", $year, $monthName, $professor_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // If not found, try with case-insensitive search
    $query = "SELECT * FROM DTR_Header WHERE year = ? AND LOWER(month) = LOWER(?) AND professor_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ssi", $year, $monthName, $professor_id);
    $stmt->execute();
    $result = $stmt->get_result();
}

if ($result->num_rows === 0) {
    // If still not found, try with month number conversion
    $query = "SELECT * FROM DTR_Header 
             WHERE year = ? 
             AND MONTH(STR_TO_DATE(CONCAT('01-', month, '-', year), '%d-%M-%Y')) = ?
             AND professor_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sii", $year, $monthNumber, $professor_id);
    $stmt->execute();
    $result = $stmt->get_result();
}

if ($result->num_rows === 0) {
    echo json_encode([
        'success' => false, 
        'error' => 'No DTR found for the selected professor, month and year',
        'debug' => [
            'year' => $year,
            'monthNumber' => $monthNumber,
            'monthName' => $monthName,
            'professor_id' => $professor_id
        ]
    ]);
    exit;
}

$dtrHeader = $result->fetch_assoc();
$dtr_id = $dtrHeader['dtr_id'];

// Get DTR Details
$queryDetails = "SELECT * FROM DTR_Details WHERE dtr_id = ? ORDER BY date";
$stmt = $conn->prepare($queryDetails);
$stmt->bind_param("i", $dtr_id);
$stmt->execute();
$resultDetails = $stmt->get_result();

$dtrDetails = [];
while ($row = $resultDetails->fetch_assoc()) {
    $dtrDetails[] = $row;
}

echo json_encode([
    'success' => true,
    'dtrHeader' => $dtrHeader,
    'dtrDetails' => $dtrDetails
]);
?>