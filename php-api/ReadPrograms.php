<?php
session_start();

if (!isset($_SESSION['isAdmin']) || $_SESSION['isAdmin'] !== true) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access.']);
    exit();
}

require('connect.php');

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    try {
        $sql = 'SELECT ProgramID, ProgramName, ProgramCategory FROM Programs ORDER BY ProgramCategory, ProgramName';
        $stmt = $conn->prepare($sql);
        $stmt->execute();

        $programs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $groupedPrograms = [];
        foreach ($programs as $program) {
            $category = $program['ProgramCategory'];
            if (!isset($groupedPrograms[$category])) {
                $groupedPrograms[$category] = [];
            }
            $groupedPrograms[$category][] = [
                'ProgramID' => $program['ProgramID'],
                'ProgramName' => $program['ProgramName']
            ];
        }

        if ($groupedPrograms) {
            $response = [
                'status' => 'success',
                'message' => 'Programs fetched successfully.',
                'data' => $groupedPrograms
            ];
        } else {
            $response = [
                'status' => 'error',
                'message' => 'No programs found.'
            ];
        }

        header('Content-Type: application/json');
        echo json_encode($response);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
}
?>