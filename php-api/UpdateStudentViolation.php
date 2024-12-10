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
    // Response array to send back to the client
    $response = array();

    // Sanitize and validate input
    $violationID = isset($_POST['ViolationID']) ? filter_var($_POST['ViolationID'], FILTER_SANITIZE_NUMBER_INT) : null;
    $violationDate = isset($_POST['ViolationDate']) ? filter_var($_POST['ViolationDate'], FILTER_SANITIZE_STRING) : null;
    $notes = isset($_POST['Notes']) ? filter_var($_POST['Notes'], FILTER_SANITIZE_STRING) : null;
    $violationStatus = isset($_POST['ViolationStatus']) ? filter_var($_POST['ViolationStatus'], FILTER_SANITIZE_STRING) : null;

    // Check if required inputs are present
    if (!$violationID || !$violationDate || !$violationStatus) {
        $response = [
            'status' => 'error',
            'message' => 'Missing required fields. Please provide ViolationID, ViolationDate, and ViolationStatus.'
        ];
        http_response_code(400);
    } else {
        try {
            // Update the ViolationRecord table with the new data
            $sql = 'UPDATE ViolationRecord 
                    SET ViolationDate = :ViolationDate, 
                        Notes = :Notes, 
                        ViolationStatus = :ViolationStatus 
                    WHERE ViolationID = :ViolationID';

            // Prepare and execute the SQL statement
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':ViolationDate', $violationDate, PDO::PARAM_STR);
            $stmt->bindParam(':Notes', $notes, PDO::PARAM_STR);
            $stmt->bindParam(':ViolationStatus', $violationStatus, PDO::PARAM_STR);
            $stmt->bindParam(':ViolationID', $violationID, PDO::PARAM_INT);

            // Execute the statement and check if any rows were updated
            if ($stmt->execute()) {
                if ($stmt->rowCount() > 0) {
                    $response = [
                        'status' => 'success',
                        'message' => 'Violation updated successfully.'
                    ];
                } else {
                    $response = [
                        'status' => 'failed',
                        'message' => 'No changes made to the violation. Please check if the data is different.'
                    ];
                }
            } else {
                $response = [
                    'status' => 'error',
                    'message' => 'Failed to update violation. Please try again.'
                ];
            }
        } catch (PDOException $e) {
            // Handle any database errors
            $response = [
                'status' => 'error',
                'message' => 'Database error: ' . $e->getMessage()
            ];
            http_response_code(500);
        }
    }

    // Set Content-Type header for JSON response
    header('Content-Type: application/json');
    echo json_encode($response);
} else {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method. Use POST.']);
}
?>
