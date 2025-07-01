<?php
header('Content-Type: application/json');
require_once '../db_connect.php';

// Get professor_id from query parameters
$professor_id = isset($_GET['professor_id']) ? (int)$_GET['professor_id'] : 0;

if ($professor_id <= 0) {
    echo json_encode([
        'success' => false,
        'error' => 'Invalid professor ID',
        'availableMonths' => []
    ]);
    exit;
}

$query = "
    SELECT 
        year, 
        month,
        CASE 
            WHEN month = 'January' THEN 1
            WHEN month = 'February' THEN 2
            WHEN month = 'March' THEN 3
            WHEN month = 'April' THEN 4
            WHEN month = 'May' THEN 5
            WHEN month = 'June' THEN 6
            WHEN month = 'July' THEN 7
            WHEN month = 'August' THEN 8
            WHEN month = 'September' THEN 9
            WHEN month = 'October' THEN 10
            WHEN month = 'November' THEN 11
            WHEN month = 'December' THEN 12
        END as month_number
    FROM DTR_Header
    WHERE professor_id = $professor_id
    ORDER BY year DESC, month_number DESC
";

$result = mysqli_query($conn, $query);

if (!$result) {
    echo json_encode([
        'success' => false,
        'error' => 'Query failed: ' . mysqli_error($conn),
        'availableMonths' => []
    ]);
    exit;
}

$availableMonths = [];
while ($row = mysqli_fetch_assoc($result)) {
    $availableMonths[] = [
        'year' => $row['year'],
        'month_name' => $row['month'],
        'month_number' => $row['month_number']
    ];
}

echo json_encode([
    'success' => true,
    'availableMonths' => $availableMonths
]);
?>