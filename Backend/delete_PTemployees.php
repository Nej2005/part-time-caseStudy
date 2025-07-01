<?php
include '../Backend/db_connect.php';  

$data = json_decode(file_get_contents("php://input"));

if (isset($data->professor_id)) {
    $professor_id = $data->professor_id;

    $conn->begin_transaction();

    try {
        // First get the email address from PartTime_Professor table
        $queryEmail = "SELECT email_address FROM PartTime_Professor WHERE professor_id = ?";
        $stmtEmail = $conn->prepare($queryEmail);
        $stmtEmail->bind_param("i", $professor_id);
        $stmtEmail->execute();
        $stmtEmail->store_result();

        if ($stmtEmail->num_rows > 0) {
            // Fetch email address
            $stmtEmail->bind_result($email_address);
            $stmtEmail->fetch();
            
            // 1. Delete AR Details records
            $deleteArDetails = "DELETE FROM ar_details WHERE ar_id IN (SELECT ar_id FROM ar_header WHERE professor_id = ?)";
            $stmtArDetails = $conn->prepare($deleteArDetails);
            $stmtArDetails->bind_param("i", $professor_id);
            $stmtArDetails->execute();
            
            // 2. Delete AR Header records
            $deleteArHeader = "DELETE FROM ar_header WHERE professor_id = ?";
            $stmtArHeader = $conn->prepare($deleteArHeader);
            $stmtArHeader->bind_param("i", $professor_id);
            $stmtArHeader->execute();
            
            // 3. Delete DTR Details records
            $deleteDtrDetails = "DELETE FROM dtr_details WHERE dtr_id IN (SELECT dtr_id FROM dtr_header WHERE professor_id = ?)";
            $stmtDtrDetails = $conn->prepare($deleteDtrDetails);
            $stmtDtrDetails->bind_param("i", $professor_id);
            $stmtDtrDetails->execute();
            
            // 4. Delete DTR Header records
            $deleteDtrHeader = "DELETE FROM dtr_header WHERE professor_id = ?";
            $stmtDtrHeader = $conn->prepare($deleteDtrHeader);
            $stmtDtrHeader->bind_param("i", $professor_id);
            $stmtDtrHeader->execute();
            
            // 5. Delete Form Load Details records
            $deleteFormLoadDetails = "DELETE FROM form_load_details WHERE form_id IN (SELECT form_id FROM form_loads WHERE professor_id = ?)";
            $stmtFormLoadDetails = $conn->prepare($deleteFormLoadDetails);
            $stmtFormLoadDetails->bind_param("i", $professor_id);
            $stmtFormLoadDetails->execute();
            
            // 6. Delete Form Loads records
            $deleteFormLoads = "DELETE FROM form_loads WHERE professor_id = ?";
            $stmtFormLoads = $conn->prepare($deleteFormLoads);
            $stmtFormLoads->bind_param("i", $professor_id);
            $stmtFormLoads->execute();
            
            // Now delete the professor record
            $query1 = "DELETE FROM PartTime_Professor WHERE professor_id = ?";
            $stmt1 = $conn->prepare($query1);
            $stmt1->bind_param("i", $professor_id);
            $stmt1->execute();

            // Finally delete the user record
            $query2 = "DELETE FROM Users WHERE email_address = ?";
            $stmt2 = $conn->prepare($query2);
            $stmt2->bind_param("s", $email_address); 
            $stmt2->execute();

            $conn->commit();

            echo json_encode(['success' => true]);

            // Close all statements
            $stmtArDetails->close();
            $stmtArHeader->close();
            $stmtDtrDetails->close();
            $stmtDtrHeader->close();
            $stmtFormLoadDetails->close();
            $stmtFormLoads->close();
            $stmt1->close();
            $stmt2->close();
        } else {
            echo json_encode(['success' => false, 'message' => 'Professor not found in PartTime_Professor table']);
        }

        $stmtEmail->close();
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Error deleting professor: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Professor ID is required']);
}

$conn->close();
?>