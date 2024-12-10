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

    // Check if the required input is present
    if (!$violationID) {
        $response = [
            'status' => 'error',
            'message' => 'Missing required field. Please provide ViolationID.'
        ];
        http_response_code(400);
    } else {
        try {
            // Delete the violation record from the database
            $sql = 'DELETE FROM ViolationRecord WHERE ViolationID = :ViolationID';

            // Prepare the SQL statement
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':ViolationID', $violationID, PDO::PARAM_INT);

            // Execute the statement and check if any rows were deleted
            if ($stmt->execute()) {
                if ($stmt->rowCount() > 0) {
                    $response = [
                        'status' => 'success',
                        'message' => 'Violation deleted successfully.'
                    ];
                } else {
                    $response = [
                        'status' => 'failed',
                        'message' => 'No violation found with the provided ViolationID.'
                    ];
                }
            } else {
                $response = [
                    'status' => 'error',
                    'message' => 'Failed to delete violation. Please try again.'
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
