<?php
require('connect.php');

$search = $_GET['search'] ?? '';

try {
    $sql = 'SELECT 
                Students.StudentID, 
                Students.StudentName, 
                Programs.ProgramName, 
                Programs.ProgramCode, 
                Students.Year 
            FROM Students 
            JOIN Programs ON Students.ProgramID = Programs.ProgramID
            WHERE 
                Students.StudentID LIKE :search 
                OR Students.StudentName LIKE :search 
                OR Programs.ProgramName LIKE :search 
                OR Programs.ProgramCode LIKE :search 
            LIMIT 10';

    $stmt = $conn->prepare($sql);
    $stmt->bindValue(':search', '%' . $search . '%');
    $stmt->execute();
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['students' => $students]);
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
