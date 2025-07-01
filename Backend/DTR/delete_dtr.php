<?php
header('Content-Type: application/json');
require_once '../db_connect.php';

$data = json_decode(file_get_contents('php://input'), true);

if (empty($data['dtr_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'DTR ID is required'
    ]);
    exit;
}

$dtr_id = intval($data['dtr_id']);

// Delete the DTR (cascade delete will handle the details)
$query = "DELETE FROM DTR_Header WHERE dtr_id = $dtr_id";
if (mysqli_query($conn, $query)) {
    if (mysqli_affected_rows($conn) > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'DTR deleted successfully'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'No DTR record found to delete'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Error deleting DTR: ' . mysqli_error($conn)
    ]);
}
?>
