<?php
require __DIR__ . '/../../phpspreadsheet/vendor/autoload.php';
//require '../FinalProject/phpspreadsheet/vendor/phpoffice/phpspreadsheet/src/PhpSpreadsheet/IOFactory.php';
require '../db_connect.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

try {
  // Verify request method
  if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    throw new Exception("Invalid request method. Only POST is allowed.");
  }

  // Get JSON input
  $json = file_get_contents('php://input');
  if (empty($json)) {
    throw new Exception("No input data received");
  }

  $data = json_decode($json, true);
  if (json_last_error() !== JSON_ERROR_NONE) {
    throw new Exception("Invalid JSON: " . json_last_error_msg());
  }

  // Validate required fields
  if (empty($data['employees'])) {
    throw new Exception("No employee data provided");
  }

  // Use relative path for template
  $templatePath = __DIR__ . '/../../EmployeeManagement.xlsx';
  if (!file_exists($templatePath)) {
    // Return JSON error instead of HTML
    header('Content-Type: application/json');
    http_response_code(404);
    echo json_encode([
      'success' => false,
      'error' => "Template file not found at: " . $templatePath
    ]);
    exit;
  }

  // Load template
  $spreadsheet = IOFactory::load($templatePath);
  $sheet = $spreadsheet->getActiveSheet();

  // Set headers and metadata
  $sheet->setCellValue('B7', $data['department'] ?? 'Department Not Specified');
  $sheet->setCellValue('B8', 'For the Month of ' . ($data['monthRange'] ?? 'N/A'));

  // Populate employee data
  $row = 10;
  foreach ($data['employees'] as $index => $employee) {
    $sheet->setCellValue('B' . $row, $index + 1); // Serial number
    $sheet->setCellValue('C' . $row, $employee['employeeId'] ?? '');
    $sheet->setCellValue('D' . $row, $employee['name'] ?? '');
    $row++;
  }

  // Generate filename
  $filename = 'EmployeeManagement_' . date('F_Y') . '.xlsx';
  $filename = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $filename);

  // Output to browser
  header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
  header('Content-Disposition: attachment; filename="' . $filename . '"');
  header('Cache-Control: max-age=0');

  $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
  $writer->save('php://output');
  exit;
} catch (Exception $e) {
  // Log the error
  error_log("Export Error: " . $e->getMessage());

  // Return JSON error response
  header('Content-Type: application/json');
  http_response_code(500);
  echo json_encode([
    'success' => false,
    'error' => $e->getMessage()
  ]);
  exit;
}
