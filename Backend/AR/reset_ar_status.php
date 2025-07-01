<?php
include '../db_connect.php';

try {
    // Reset all progress_status to 'pending' in PartTime_Professor table
    $resetSql = "UPDATE PartTime_Professor SET progress_status = 'pending'";
    $conn->query($resetSql);
    
    // You might also want to log this notification event
    $logSql = "INSERT INTO NotificationLog (action, performed_by, timestamp) 
               VALUES ('Reset all AR statuses to Pending', ?, NOW())";
    $stmt = $conn->prepare($logSql);
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
$conn->close();
?>