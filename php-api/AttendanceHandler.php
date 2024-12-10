<?php
session_start();
require('connect.php'); // Ensure this connects to your database

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = $_POST;
    $studentID = isset($data['studentID']) ? trim($data['studentID']) : '';

    try {
        // Check if the student exists
        $checkStudentSQL = "SELECT * FROM students WHERE studentID = :studentID";
        $stmt = $conn->prepare($checkStudentSQL);
        $stmt->execute([':studentID' => $studentID]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($student) {
            // Check if attendance already exists for the same student on the same date (ignoring time)
            $attendanceDate = date('Y-m-d'); // Get today's date
            $checkAttendanceSQL = "SELECT * FROM attendancerecord WHERE studentID = :studentID AND DATE(attendanceDate) = :attendanceDate";
            $stmt = $conn->prepare($checkAttendanceSQL);
            $stmt->execute([':studentID' => $studentID, ':attendanceDate' => $attendanceDate]);
            $attendance = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($attendance) {
                // Redirect to StudentInformation.php if attendance already exists for today
                $_SESSION['student'] = $student;
                echo json_encode(['status' => 'success', 'message' => 'Already attended!', 'student' => $student]);
                exit();
            } else {
                // Insert attendance record if not found for today
                $attendanceDateTime = date('Y-m-d H:i:s'); // Current datetime
                $insertAttendanceSQL = "INSERT INTO attendancerecord (attendanceDate, studentID) VALUES (:attendanceDate, :studentID)";
                $stmt = $conn->prepare($insertAttendanceSQL);
                $stmt->execute([
                    ':attendanceDate' => $attendanceDateTime,
                    ':studentID' => $studentID
                ]);

                // Save student info in session
                $_SESSION['student'] = $student;

                // Send JSON success response
                echo json_encode(['status' => 'success', 'message' => 'Attendance recorded', 'student' => $student]);
            }
        } else {
            // Send JSON error response if student does not exist
            echo json_encode(['status' => 'failed', 'message' => 'Student not found']);
        }
    } catch (PDOException $e) {
        // Send JSON error response for database errors
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
}
?>
