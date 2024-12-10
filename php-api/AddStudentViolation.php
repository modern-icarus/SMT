<?php
session_start();

// Check if the user is an admin
if (!isset($_SESSION['isAdmin']) || $_SESSION['isAdmin'] !== true) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access.']);
    exit();
}

require('connect.php');

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $response = array();

    // Sanitize and validate input
    $studentID = isset($_POST['StudentID']) ? filter_var($_POST['StudentID'], FILTER_SANITIZE_NUMBER_INT) : null;
    $violationType = isset($_POST['ViolationType']) ? filter_var($_POST['ViolationType'], FILTER_SANITIZE_STRING) : null;
    $violationDate = isset($_POST['ViolationDate']) ? filter_var($_POST['ViolationDate'], FILTER_SANITIZE_STRING) : null;
    $notes = isset($_POST['Notes']) ? filter_var($_POST['Notes'], FILTER_SANITIZE_STRING) : null;
    $violationStatus = 'Pending'; // Default status is 'Pending'

    // Check if the required input is present
    if (!$studentID || !$violationType || !$violationDate) {
        $response = [
            'status' => 'error',
            'message' => 'Missing required fields. Please provide StudentID, ViolationType, and ViolationDate.'
        ];
        http_response_code(400);
    } else {
        try {
            // Insert the new violation into the database
            $sql = 'INSERT INTO ViolationRecord (StudentID, ViolationType, ViolationDate, Notes, ViolationStatus) 
                    VALUES (:StudentID, :ViolationType, :ViolationDate, :Notes, :ViolationStatus)';

            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':StudentID', $studentID, PDO::PARAM_INT);
            $stmt->bindParam(':ViolationType', $violationType, PDO::PARAM_STR);
            $stmt->bindParam(':ViolationDate', $violationDate, PDO::PARAM_STR);
            $stmt->bindParam(':Notes', $notes, PDO::PARAM_STR);
            $stmt->bindParam(':ViolationStatus', $violationStatus, PDO::PARAM_STR);

            if ($stmt->execute()) {
                $response = [
                    'status' => 'success',
                    'message' => 'Violation added successfully.',
                    'violationID' => $conn->lastInsertId()
                ];
            } else {
                $response = [
                    'status' => 'error',
                    'message' => 'Failed to add violation. Please try again.'
                ];
            }
        } catch (PDOException $e) {
            $response = [
                'status' => 'error',
                'message' => 'Database error: ' . $e->getMessage()
            ];
            http_response_code(500);
        }
    }

    header('Content-Type: application/json');
    echo json_encode($response);
} else {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method. Use POST.']);
}
?>
