<?php
require __DIR__ . '/../../phpspreadsheet/vendor/autoload.php';
include '../db_connect.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Get the POST data
$data = json_decode(file_get_contents('php://input'), true);

try {
    // Load the template file
    $templatePath = $data['templatePath'];
    $spreadsheet = IOFactory::load($templatePath);
    $sheet = $spreadsheet->getActiveSheet();

    // Trim whitespace from all input data
    $name = trim($data['data']['name']);
    $status = trim($data['data']['status']);
    $period = trim($data['data']['period']);
    $collegeDepartment = trim($data['data']['collegeDepartment']);
    
    // Convert semester to full text while preserving the rest of the string
    $semesterSubtitle = trim($data['data']['semesterSubtitle']);
    if (preg_match('/^(1st|2nd)(.*)/i', $semesterSubtitle, $matches)) {
        $semesterPart = ($matches[1] === '1st') ? 'First' : 'Second';
        $semesterSubtitle = $semesterPart . $matches[2];
    }

    // Fill in the header information with trimmed values
    $sheet->setCellValue('A325', $semesterSubtitle);
    $sheet->setCellValue('B328', $name);
    $sheet->setCellValue('B329', $status);
    $sheet->setCellValue('M328', $period);
    $sheet->setCellValue('M329', $collegeDepartment);
    $sheet->setCellValue('A31', $name);
    $sheet->setCellValue('A349', $name);

    // Fill in the subject data starting at row 333
    $row = 333;
    foreach ($data['data']['subjects'] as $subject) {
        if (empty(trim($subject['code'])) && empty(trim($subject['description']))) {
            continue;
        }

        // Trim all subject data
        $sheet->setCellValue('A' . $row, trim($subject['code']));
        $sheet->setCellValue('B' . $row, trim($subject['description']));
        $sheet->setCellValue('E' . $row, trim($subject['lec']));
        $sheet->setCellValue('F' . $row, trim($subject['lab']));
        $sheet->setCellValue('G' . $row, trim($subject['hrsPerWeek'])); 
        
        // Schedule columns (H to N)
        $sheet->setCellValue('H' . $row, trim($subject['mon']));
        $sheet->setCellValue('I' . $row, trim($subject['tue']));
        $sheet->setCellValue('J' . $row, trim($subject['wed']));
        $sheet->setCellValue('K' . $row, trim($subject['thu']));
        $sheet->setCellValue('L' . $row, trim($subject['fri']));
        $sheet->setCellValue('M' . $row, trim($subject['sat']));
        $sheet->setCellValue('N' . $row, trim($subject['sun']));
        
        $sheet->setCellValue('O' . $row, trim($subject['room']));
        $sheet->setCellValue('P' . $row, trim($subject['section']));
        $sheet->setCellValue('Q' . $row, trim($subject['period']));
        
        $row++;
    }

    // Fill in the totals
    $sheet->setCellValue('E337', $data['data']['totals']['lec']);
    $sheet->setCellValue('F337', $data['data']['totals']['lab']);
    $sheet->setCellValue('G337', $data['data']['totals']['hrsWk']);

    // Fill in the calculations
    $sheet->setCellValue('E339', $data['data']['calculations']['numPreparations']);
    $sheet->setCellValue('E340', $data['data']['calculations']['lowestHrs']);
    $sheet->setCellValue('E341', $data['data']['calculations']['highestHrs']);
    $sheet->setCellValue('O339', $data['data']['calculations']['totalLoadUnits']);
    $sheet->setCellValue('O340', $data['data']['calculations']['totalLoadHrs']);

    // Set headers for file download
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="Faculty_Loading_Form.xlsx"');
    header('Cache-Control: max-age=0');

    // Create writer and output to browser
    $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
    $writer->save('php://output');
    exit;

} catch (Exception $e) {
    // Handle errors
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}