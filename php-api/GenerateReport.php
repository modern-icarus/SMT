<?php
header('Content-Type: application/json');
require_once 'connect.php';

$reportDate = $_POST['reportDate'] ?? null;
$programId  = $_POST['programId'] ?? null;

if (!$reportDate || !$programId) {
    echo json_encode([
        'success' => false,
        'message' => 'Missing required parameters.'
    ]);
    exit;
}

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $reportDate)) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid date format.'
    ]);
    exit;
}

$programName = "All Programs";
$programCode = "ALL";
$whereProgramSql = "";
$params = [':date' => $reportDate];

if ($programId !== 'all') {
    $programId = intval($programId);
    
    // Fetch program name and code
    $stmtProg = $conn->prepare("SELECT ProgramName, ProgramCode FROM Program WHERE ProgramID = :programId");
    $stmtProg->execute([':programId' => $programId]);
    $programRow = $stmtProg->fetch(PDO::FETCH_ASSOC);
    
    if ($programRow) {
        $programName = $programRow['ProgramName'];
        $programCode = $programRow['ProgramCode'] ?? $programRow['ProgramName']; // fallback to name if code doesn't exist
    }

    $whereProgramSql = "AND s.ProgramID = :programId";
    $params[':programId'] = $programId;
}

try {
    // Base SQL fragment reused in each query
    $baseSQL = "
        FROM StudentArchive AS sa
        INNER JOIN Students AS s ON sa.StudentID = s.StudentID
        WHERE sa.ViolationDate = :date
        $whereProgramSql
    ";

    // Attended
    $sqlAttended = "SELECT COUNT(*) AS cnt $baseSQL AND sa.Attendance = 1";
    $stmt = $conn->prepare($sqlAttended);
    $stmt->execute($params);
    $cntAttended = $stmt->fetchColumn();

    // Total Violations
    $sqlTotal = "SELECT COUNT(*) AS cnt $baseSQL AND sa.Violated = 1";
    $stmt = $conn->prepare($sqlTotal);
    $stmt->execute($params);
    $cntTotalViol = $stmt->fetchColumn();

    // Pending Violations
    $sqlPending = "SELECT COUNT(*) AS cnt $baseSQL AND sa.Violated = 1 AND sa.ViolationStatus = 'Pending'";
    $stmt = $conn->prepare($sqlPending);
    $stmt->execute($params);
    $cntPending = $stmt->fetchColumn();

    // Reviewed Violations
    $sqlReviewed = "SELECT COUNT(*) AS cnt $baseSQL AND sa.Violated = 1 AND sa.ViolationStatus <> 'Pending'";
    $stmt = $conn->prepare($sqlReviewed);
    $stmt->execute($params);
    $cntReviewed = $stmt->fetchColumn();

    // Final JSON response - now includes programCode
    echo json_encode([
        'success' => true,
        'data' => [
            'programName' => $programName,
            'programCode' => $programCode,
            'date'        => $reportDate,
            'stats'       => [
                'attended'           => intval($cntAttended),
                'totalViolations'    => intval($cntTotalViol),
                'pendingViolations'  => intval($cntPending),
                'reviewedViolations' => intval($cntReviewed)
            ]
        ]
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>