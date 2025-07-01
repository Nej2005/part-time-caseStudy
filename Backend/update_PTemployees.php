<?php
include '../Backend/db_connect.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);

if (
  isset($data['professor_id']) &&
  isset($data['email']) &&
  isset($data['first_name']) &&
  isset($data['last_name']) &&
  isset($data['middle_initial']) &&
  isset($data['department'])
) {
  $professor_id = $data['professor_id'];
  $email = $data['email'];
  $fname = $data['first_name'];
  $lname = $data['last_name'];
  $mi = $data['middle_initial'];
  $department = $data['department'];

  try {
    $conn->begin_transaction();

    // First get the current email to find the user record
    $getEmail = $conn->prepare("SELECT email_address FROM PartTime_Professor WHERE professor_id = ?");
    $getEmail->bind_param("i", $professor_id);
    $getEmail->execute();
    $emailResult = $getEmail->get_result();

    if ($emailResult->num_rows === 0) {
      throw new Exception("No professor found with ID: $professor_id");
    }

    $oldEmail = $emailResult->fetch_assoc()['email_address'];

    // Update Users table using the email as the key
    $stmt1 = $conn->prepare("UPDATE Users SET email_address = ? WHERE email_address = ?");
    $stmt1->bind_param("ss", $email, $oldEmail);
    $stmt1->execute();
    $users_updated = $stmt1->affected_rows;

    // Update PartTime_Professor table
    $stmt2 = $conn->prepare("UPDATE PartTime_Professor SET email_address = ?, first_name = ?, last_name = ?, middle_initial = ?, department = ? WHERE professor_id = ?");
    $stmt2->bind_param("sssssi", $email, $fname, $lname, $mi, $department, $professor_id);
    $stmt2->execute();
    $professors_updated = $stmt2->affected_rows;

    $conn->commit();

    $changes_made = ($users_updated > 0 || $professors_updated > 0);

    echo json_encode(['success' => true, 'changes' => $changes_made]);
  } catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Update failed: ' . $e->getMessage()]);
  }
} else {
  echo json_encode(['success' => false, 'message' => 'Missing required fields']);
}

$conn->close();
