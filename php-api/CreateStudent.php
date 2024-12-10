<?php
session_start();

if (!isset($_SESSION['isAdmin']) || $_SESSION['isAdmin'] !== true) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access.']);
    exit();
}

require('connect.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    $studentID = isset($data['studentID']) ? trim($data['studentID']) : '';
    $studentName = isset($data['studentName']) ? trim($data['studentName']) : '';
    $year = isset($data['year']) ? trim($data['year']) : '';
    $programID = isset($data['programID']) ? trim($data['programID']) : '';

    if (empty($studentID) || empty($studentName) || empty($year) || empty($programID)) {
        echo json_encode([
            'status' => 'error',
            'message' => 'All fields are required.'
        ]);
        exit();
    }

    try {
        $stmt = $conn->prepare("SELECT COUNT(*) FROM Students WHERE StudentID = :studentID");
        $stmt->bindParam(':studentID', $studentID, PDO::PARAM_STR);
        $stmt->execute();

        if ($stmt->fetchColumn() > 0) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Student ID already exists.'
            ]);
            exit();
        }

        $stmt = $conn->prepare("SELECT ProgramCode FROM Programs WHERE ProgramID = :programID");
        $stmt->bindParam(':programID', $programID, PDO::PARAM_INT);
        $stmt->execute();
        $programCode = $stmt->fetchColumn();

        if (!$programCode) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Invalid Program ID.'
            ]);
            exit();
        }

        $stmt = $conn->prepare("
            INSERT INTO Students (StudentID, StudentName, Year, ProgramID) 
            VALUES (:studentID, :studentName, :year, :programID)
        ");
        $stmt->bindParam(':studentID', $studentID, PDO::PARAM_STR);
        $stmt->bindParam(':studentName', $studentName, PDO::PARAM_STR);
        $stmt->bindParam(':year', $year, PDO::PARAM_STR);
        $stmt->bindParam(':programID', $programID, PDO::PARAM_INT);

        $stmt->execute();

        echo json_encode([
            'status' => 'success',
            'message' => 'Student added successfully.'
        ]);
    } catch (PDOException $e) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Database error: ' . $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid request method.'
    ]);
}
?>