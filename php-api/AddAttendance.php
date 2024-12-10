<?php
require('connect.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST'){
  $studentID = $_POST['studentID'];
  $attendanceDate = $_POST['attendanceDate'];

  try {
    $sql = "INSERT INTO attendancerecord(attendanceDate, studentID) 
    VALUES (:attendanceDate, :studentID)";

    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':studentID' => $studentID,
        ':attendanceDate' => $attendanceDate
    ]);
    echo json_encode(['status' => 'success']);
  } catch (PDOException $e) {
      echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
  }
}

?>