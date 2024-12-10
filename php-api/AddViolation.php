<?php
require('connect.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $studentID = $_POST['StudentID'];
    $violationDate = $_POST['ViolationDate'];
    $violationType = $_POST['ViolationType'];
    $notes = $_POST['Notes'];
    $violationStatus = 'Pending';

    // Handle file upload
    $violationPicture = 'images/placeholder.png';
    if (!empty($_FILES['ViolationPicture']['name'])) {
        $targetDir = '../images/';
        $fileName = basename($_FILES['ViolationPicture']['name']);
        $targetFilePath = $targetDir . $fileName;
        
        if (move_uploaded_file($_FILES['ViolationPicture']['tmp_name'], $targetFilePath)) {
            $violationPicture = 'images/' . $fileName;
        }
    }

    try {
        $sql = 'INSERT INTO ViolationRecord (StudentID, ViolationDate, ViolationType, Notes, ViolationPicture, ViolationStatus) 
                VALUES (:StudentID, :ViolationDate, :ViolationType, :Notes, :ViolationPicture, :ViolationStatus)';

        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':StudentID' => $studentID,
            ':ViolationDate' => $violationDate,
            ':ViolationType' => $violationType,
            ':Notes' => $notes,
            ':ViolationPicture' => $violationPicture,
            ':ViolationStatus' => $violationStatus
        ]);

        echo json_encode(['status' => 'success']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}
?>
