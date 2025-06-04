<?php
session_start();

// Check if the user is an admin
if (!isset($_SESSION['isAdmin']) || $_SESSION['isAdmin'] !== true) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access.']);
    exit();
}

require('connect.php');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = [];

    // Sanitize and validate input
    $violationType = isset($_POST['ViolationType']) ? filter_var($_POST['ViolationType'], FILTER_SANITIZE_STRING) : null;
    $studentID = isset($_POST['StudentID']) ? filter_var($_POST['StudentID'], FILTER_SANITIZE_STRING) : null;
    $studentFolderName = isset($_POST['StudentFolderName']) ? filter_var($_POST['StudentFolderName'], FILTER_SANITIZE_STRING) : null;
    $fileName = isset($_POST['FileName']) ? filter_var($_POST['FileName'], FILTER_SANITIZE_STRING) : null;

    $baseFolder = __DIR__ . '/content/manual_uploads';

    $folder = $baseFolder . '/' . $studentFolderName;
    $dbFilePath = "content/manual_uploads" . '/' . $studentFolderName . '/' . $fileName;
    $violated = $violationType == "WithoutUniform" ? 1 : 0;
    $violatedDate = date('Y-m-d');
    $notes = 'Manual Processed';

    if (!$studentID || !$studentFolderName || !$fileName) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'Fill up fields.'
        ]);
        exit();
    }

    try {
        // Directly insert the record without checking for duplicates
        $sql = "INSERT INTO DailyRecords 
        (StudentID, ViolationDate, Attendance, Violated, ViolationType, Notes, ViolationPicture, ViolationStatus) 
        VALUES 
        (:StudentID, :ViolationDate, 0, :Violated, :ViolationType, :Notes, :ViolationPicture, 'Pending')";

        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':StudentID', $studentID, PDO::PARAM_STR);
        $stmt->bindParam(':ViolationDate', $violatedDate, PDO::PARAM_STR);
        $stmt->bindParam(':Violated', $violated, PDO::PARAM_INT);
        $stmt->bindParam(':ViolationType', $violationType, PDO::PARAM_STR);
        $stmt->bindParam(':Notes', $notes, PDO::PARAM_STR);
        $stmt->bindParam(':ViolationPicture', $dbFilePath, PDO::PARAM_STR);

        if ($stmt->execute()) {
            $response = [
                'status' => 'success',
                'message' => 'Violation added successfully!'
            ];
        } else {
            $response = [
                'status' => 'error',
                'message' => 'Failed to add the violation. Please try again.'
            ];
        }
    } catch (PDOException $e) {
        http_response_code(500);
        $response = [
            'status' => 'error',
            'message' => 'Database error: ' . $e->getMessage()
        ];
    }

    echo json_encode($response);
} else {
    http_response_code(405);
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid request method. Use POST.'
    ]);
}
?>
