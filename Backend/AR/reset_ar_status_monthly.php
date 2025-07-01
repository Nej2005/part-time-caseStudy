<?php
include '../db_connect.php';

$currentMonth = date('m');
$currentYear = date('Y');

try {
    $sql = "UPDATE PartTime_Professor 
            SET progress_status = 'pending', 
                last_status_update_month = ?,
                last_status_update_year = ?
            WHERE last_status_update_month != ? OR last_status_update_year != ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiii", $currentMonth, $currentYear, $currentMonth, $currentYear);
    $stmt->execute();
    
    // Log success
    file_put_contents('ar_reset_log.txt', date('Y-m-d H:i:s') . " - AR statuses reset for new month\n", FILE_APPEND);
} catch (Exception $e) {
    // Log error
    file_put_contents('ar_reset_log.txt', date('Y-m-d H:i:s') . " - Error: " . $e->getMessage() . "\n", FILE_APPEND);
}
$conn->close();
?>