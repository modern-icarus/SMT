<?php
session_start();

if (!isset($_SESSION['isAdmin']) || $_SESSION['isAdmin'] !== true) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access.']);
    exit();
}

require('connect.php');

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    $response = array();

    if ($_SESSION['isAdmin']) {
        // Sanitize the student ID from the GET request
        $studentID = isset($_GET['StudentID']) ? filter_var($_GET['StudentID'], FILTER_SANITIZE_STRING) : '';

        if (!$studentID) {
            $response = [
                'status' => 'error',
                'message' => 'Student ID is required.'
            ];
            echo json_encode($response);
            exit();
        }

        // SQL query to fetch student details and their violations along with notes and ViolationStatus
        $sql = 'SELECT Students.StudentID, Students.StudentName, Students.Year, Students.ProgramID,
                       Programs.ProgramName, Programs.ProgramCode,
                       ViolationRecord.ViolationID, ViolationRecord.ViolationType, ViolationRecord.ViolationDate, 
                       ViolationRecord.Notes, ViolationRecord.ViolationStatus
                FROM Students
                INNER JOIN ViolationRecord ON Students.StudentID = ViolationRecord.StudentID
                LEFT JOIN Programs ON Students.ProgramID = Programs.ProgramID
                WHERE Students.StudentID = :StudentID
                ORDER BY ViolationRecord.ViolationDate DESC';

        try {
            // Prepare and execute the query
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':StudentID', $studentID, PDO::PARAM_STR);
            $stmt->execute();

            // Fetch the data
            $studentViolations = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (count($studentViolations) > 0) {
                // Get the student information
                $studentInfo = $studentViolations[0];
                $violations = [];

                // Format the violation data with notes and ViolationStatus
                foreach ($studentViolations as $violation) {
                    $violations[] = [
                        'ViolationID' => $violation['ViolationID'],
                        'ViolationType' => $violation['ViolationType'],
                        'ViolationDate' => $violation['ViolationDate'],
                        'Notes' => $violation['Notes'], // Include the notes for each violation
                        'ViolationStatus' => $violation['ViolationStatus'] // Include the ViolationStatus for each violation
                    ];
                }

                // Return the response with student details and violations
                $response = [
                    'status' => 'success',
                    'message' => 'Data fetched successfully.',
                    'student' => [
                        'StudentID' => $studentInfo['StudentID'],
                        'StudentName' => $studentInfo['StudentName'],
                        'Year' => $studentInfo['Year'],
                        'ProgramID' => $studentInfo['ProgramID'],
                        'ProgramName' => $studentInfo['ProgramName'],
                        'ProgramCode' => $studentInfo['ProgramCode']
                    ],
                    'violations' => $violations
                ];
            } else {
                $response = [
                    'status' => 'failed',
                    'message' => 'No violations found for this student.'
                ];
            }
        } catch (PDOException $e) {
            // Handle database errors
            $response = [
                'status' => 'error',
                'message' => 'Database error: ' . $e->getMessage()
            ];
            http_response_code(500);
        }
    } else {
        $response = [
            'status' => 'error',
            'message' => 'Access denied. Admins only.'
        ];
        http_response_code(403);
    }

    // Set Content-Type header for JSON response
    header('Content-Type: application/json');
    echo json_encode($response);
}
?>
