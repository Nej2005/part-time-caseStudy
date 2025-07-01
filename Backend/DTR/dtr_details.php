<?php
header('Content-Type: application/json');
require_once '../db_connect.php';

$data = json_decode(file_get_contents('php://input'), true);

error_log("Received data: " . print_r($data, true));

// Validate required fields
if (empty($data['professor_id'])) {
    throw new Exception('Professor ID is required');
}
if (empty($data['dtr_id'])) {
    throw new Exception('DTR ID is required');
}
if (empty($data['attendance'])) {
    throw new Exception('Attendance data is required');
}

try {
    $dtr_id = $data['dtr_id'];
    $attendanceData = $data['attendance'];

    // Get the year from DTR_Header to ensure consistency
    $stmtHeader = $conn->prepare("SELECT year, date_from FROM DTR_Header WHERE dtr_id = ?");
    $stmtHeader->bind_param("i", $dtr_id);
    $stmtHeader->execute();
    $resultHeader = $stmtHeader->get_result();
    
    if ($resultHeader->num_rows === 0) {
        throw new Exception('Invalid DTR ID');
    }
    
    $header = $resultHeader->fetch_assoc();
    $expectedYear = $header['year'];
    $dateFrom = new DateTime($header['date_from']);

    $totalHours = 0;
    $totalMinutes = 0;

    // Prepare the insert statement
    $stmt = $conn->prepare("INSERT INTO DTR_Details 
        (dtr_id, date, time_in_am, time_out_am, time_in_pm, time_out_pm, hours_rendered, minutes_rendered) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

    foreach ($attendanceData as $record) {
        // Skip records that don't have any time entries
        if (empty($record['In']) && empty($record['Out']) && empty($record['In1']) && empty($record['Out1'])) {
            continue;
        }

        // Parse the date and ensure it uses the correct year
        $dateStr = $record['Date'];
        $date = null;
        
        // Handle different date formats
        if (preg_match('/^[A-Za-z]{3}-\d{2}$/', $dateStr)) {
            // Format like "FEB-01" - use year from DTR header
            $date = DateTime::createFromFormat('M-d', $dateStr);
            $date->setDate($expectedYear, $date->format('m'), $date->format('d'));
        } elseif (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateStr)) {
            // Already in YYYY-MM-DD format
            $date = new DateTime($dateStr);
        } else {
            // Try to parse as-is
            $date = new DateTime($dateStr);
        }
        
        // Ensure the date uses the correct year
        $formattedDate = $date->format('Y-m-d');
        if ($date->format('Y') != $expectedYear) {
            error_log("Date year mismatch: {$date->format('Y')} (expected $expectedYear)");
            // Force the correct year
            $date->setDate($expectedYear, $date->format('m'), $date->format('d'));
            $formattedDate = $date->format('Y-m-d');
        }

        // Handle null values for time fields
        $timeInAM = !empty($record['In']) ? $record['In'] : null;
        $timeOutAM = !empty($record['Out']) ? $record['Out'] : null;
        $timeInPM = !empty($record['In1']) ? $record['In1'] : null;
        $timeOutPM = !empty($record['Out1']) ? $record['Out1'] : null;

        // Use calculated hours/minutes if provided, otherwise calculate them
        $hours = (int)($record['Hours'] ?? 0);
        $minutes = (int)($record['Minutes'] ?? 0);

        // Only insert if there's at least one time entry
        if ($timeInAM || $timeOutAM || $timeInPM || $timeOutPM) {
            $stmt->bind_param(
                "isssssii",
                $dtr_id,
                $formattedDate,  // Use the properly formatted date with correct year
                $timeInAM,
                $timeOutAM,
                $timeInPM,
                $timeOutPM,
                $hours,
                $minutes
            );

            $stmt->execute();

            $totalHours += $hours;
            $totalMinutes += $minutes;
        }
    }

    // Normalize total minutes
    $totalHours += floor($totalMinutes / 60);
    $totalMinutes = $totalMinutes % 60;

    // Update the header with totals
    $updateStmt = $conn->prepare("UPDATE DTR_Header 
        SET total_hours = ?, total_minutes = ? WHERE dtr_id = ?");
    $updateStmt->bind_param("iii", $totalHours, $totalMinutes, $dtr_id);
    $updateStmt->execute();

    echo json_encode([
        'success' => true,
        'message' => 'DTR details saved successfully',
        'total_hours' => $totalHours,
        'total_minutes' => $totalMinutes
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}