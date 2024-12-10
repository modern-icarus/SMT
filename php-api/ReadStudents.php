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
        // Sanitize inputs
        $searchStudentID = isset($_GET['StudentID']) ? filter_var($_GET['StudentID'], FILTER_SANITIZE_STRING) : '';
        $searchStudentName = isset($_GET['StudentName']) ? filter_var($_GET['StudentName'], FILTER_SANITIZE_STRING) : '';
        $searchYear = isset($_GET['Year']) ? filter_var($_GET['Year'], FILTER_SANITIZE_STRING) : '';
        $searchProgramID = isset($_GET['ProgramID']) ? filter_var($_GET['ProgramID'], FILTER_SANITIZE_STRING) : '';

        $whereClauses = [];
        $params = [];

        if ($searchStudentID) {
            $whereClauses[] = "Students.StudentID LIKE :StudentID";
            $params[':StudentID'] = "%" . $searchStudentID . "%";
        }

        if ($searchStudentName) {
            $whereClauses[] = "Students.StudentName LIKE :StudentName";
            $params[':StudentName'] = "%" . $searchStudentName . "%";
        }

        if ($searchYear) {
            $whereClauses[] = "Students.Year LIKE :Year";
            $params[':Year'] = "%" . $searchYear . "%";
        }

        if ($searchProgramID) {
            $whereClauses[] = "Students.ProgramID LIKE :ProgramID";
            $params[':ProgramID'] = "%" . $searchProgramID . "%";
        }

        // Start constructing SQL query
        $sql = 'SELECT Students.StudentID, Students.StudentName, Students.Year, Students.ProgramID, 
		               Programs.ProgramName, Programs.ProgramCode,
		               COALESCE(COUNT(ViolationRecord.ViolationID), 0) AS ViolationCount,
		               SUM(CASE WHEN ViolationRecord.ViolationType LIKE "%WithoutUniform%" THEN 1 ELSE 0 END) AS WithoutUniformCount,
		               SUM(CASE WHEN ViolationRecord.ViolationType LIKE "%WithoutID%" THEN 1 ELSE 0 END) AS WithoutIDCount
		        FROM Students
		        LEFT JOIN ViolationRecord ON Students.StudentID = ViolationRecord.StudentID
		        LEFT JOIN Programs ON Students.ProgramID = Programs.ProgramID';

        // Add where clauses if applicable
        if (count($whereClauses) > 0) {
            $sql .= ' WHERE ' . implode(' AND ', $whereClauses);
        }

        // Group by StudentID for aggregation
        $sql .= ' GROUP BY Students.StudentID';

        try {
            // Prepare and execute the query
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);

            // Fetch the data
            $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (count($students) > 0) {
                $response = [
                    'status' => 'success',
                    'message' => 'Data fetched successfully.',
                    'data' => $students
                ];
            } else {
                $response = [
                    'status' => 'failed',
                    'message' => 'No records found.'
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
