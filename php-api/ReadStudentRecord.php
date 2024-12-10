<?php
session_start();

require('connect.php');

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    $response = array();

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

    // SQL query to fetch student details and their violations, including those with 0 violations
    $sql = 'SELECT Students.StudentID, Students.StudentName, Students.Year, Students.ProgramID,
                   Programs.ProgramName, Programs.ProgramCode,
                   ViolationRecord.ViolationID, ViolationRecord.ViolationType, ViolationRecord.ViolationDate, 
                   ViolationRecord.Notes, ViolationRecord.ViolationStatus
            FROM Students
            LEFT JOIN ViolationRecord ON Students.StudentID = ViolationRecord.StudentID
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

        // Check if we have any results
        if ($studentViolations) {
            // Get the student information (first record)
            $studentInfo = [
                'StudentID' => $studentViolations[0]['StudentID'],
                'StudentName' => $studentViolations[0]['StudentName'],
                'Year' => $studentViolations[0]['Year'],
                'ProgramID' => $studentViolations[0]['ProgramID'],
                'ProgramName' => $studentViolations[0]['ProgramName'],
                'ProgramCode' => $studentViolations[0]['ProgramCode']
            ];

            // Initialize an empty array for violations
            $violations = [];

            // Format the violation data with notes and ViolationStatus, if any
            foreach ($studentViolations as $violation) {
                // Add violation data only if it exists
                if ($violation['ViolationID']) {
                    $violations[] = [
                        'ViolationID' => $violation['ViolationID'],
                        'ViolationType' => $violation['ViolationType'],
                        'ViolationDate' => $violation['ViolationDate'],
                        'Notes' => $violation['Notes'], // Include the notes for each violation
                        'ViolationStatus' => $violation['ViolationStatus'] // Include the ViolationStatus for each violation
                    ];
                }
            }

            // Return the response with student details and violations (if any)
            $response = [
                'status' => 'success',
                'message' => 'Data fetched successfully.',
                'student' => $studentInfo,
                'violations' => $violations
            ];
        } else {
            // No violations found, but still output student information
            $response = [
                'status' => 'success',
                'message' => 'Student found but no violations recorded.',
                'student' => [
                    'StudentID' => $studentID,
                    'StudentName' => 'Unknown',  // In case no violations, default 'Unknown'
                    'Year' => 'Unknown',
                    'ProgramID' => 'Unknown',
                    'ProgramName' => 'Unknown',
                    'ProgramCode' => 'Unknown'
                ],
                'violations' => [] // No violations, so empty array
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

    // Set Content-Type header for JSON response
    header('Content-Type: application/json');
    echo json_encode($response);
}
?>
