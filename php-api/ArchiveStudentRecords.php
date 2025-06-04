<?php
session_start();

if (!isset($_SESSION['isAdmin']) || $_SESSION['isAdmin'] !== true) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access.']);
    exit();
}

require('connect.php');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method. Use POST.']);
    exit();
}

function movePhotosToArchive() {
    $manualUploadsPath = './content/manual_uploads/';
    $archivedPhotosPath = './content/archived_photos/';
    $currentDate = date('Y-m-d');
    $archiveSubfolder = $archivedPhotosPath . $currentDate . '/';
    
    $movedFiles = 0;
    $errors = [];
    
    try {
        // Create archived_photos directory if it doesn't exist
        if (!is_dir($archivedPhotosPath)) {
            if (!mkdir($archivedPhotosPath, 0755, true)) {
                throw new Exception("Failed to create archived_photos directory");
            }
        }
        
        // Create date-specific subfolder
        if (!is_dir($archiveSubfolder)) {
            if (!mkdir($archiveSubfolder, 0755, true)) {
                throw new Exception("Failed to create archive subfolder for date: $currentDate");
            }
        }
        
        // Check if manual_uploads directory exists
        if (!is_dir($manualUploadsPath)) {
            return ['moved' => 0, 'errors' => ['manual_uploads directory does not exist']];
        }
        
        // Get all items in manual_uploads
        $items = scandir($manualUploadsPath);
        
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            
            $sourcePath = $manualUploadsPath . $item;
            $destinationPath = $archiveSubfolder . $item;
            
            if (is_dir($sourcePath)) {
                // Move entire directory
                if (rename($sourcePath, $destinationPath)) {
                    $movedFiles++;
                } else {
                    $errors[] = "Failed to move directory: $item";
                }
            } elseif (is_file($sourcePath)) {
                // Move individual file
                if (rename($sourcePath, $destinationPath)) {
                    $movedFiles++;
                } else {
                    $errors[] = "Failed to move file: $item";
                }
            }
        }
        
        return ['moved' => $movedFiles, 'errors' => $errors];
        
    } catch (Exception $e) {
        return ['moved' => $movedFiles, 'errors' => [$e->getMessage()]];
    }
}

try {
    $currentYear = date('Y');
    $currentMonth = date('n');
    $currentDate = date('Y-m-d');
    $archiveImages = isset($_POST['archiveImages']) && $_POST['archiveImages'];

    // Copy matching records from DailyRecords to StudentArchive with updated paths
    $insertSql = "
        INSERT INTO StudentArchive (
            StudentID, ViolationDate, Attendance, TimeIn, TimeOut,
            Violated, ViolationType, Notes, ViolationPicture, ViolationStatus
        )
        SELECT 
            StudentID, ViolationDate, Attendance, TimeIn, TimeOut,
            Violated, ViolationType, Notes, 
            CASE 
                WHEN ViolationPicture IS NOT NULL AND ViolationPicture != '' 
                THEN REPLACE(ViolationPicture, 'content/manual_uploads/', 'content/archived_photos/$currentDate/')
                ELSE ViolationPicture
            END as ViolationPicture,
            ViolationStatus
        FROM DailyRecords
        WHERE YEAR(ViolationDate) = :year AND MONTH(ViolationDate) = :month
    ";
    $stmtInsert = $conn->prepare($insertSql);
    $stmtInsert->execute([':year' => $currentYear, ':month' => $currentMonth]);

    // Delete those same records from DailyRecords
    $deleteSql = "
        DELETE FROM DailyRecords
        WHERE YEAR(ViolationDate) = :year AND MONTH(ViolationDate) = :month
    ";
    $stmtDelete = $conn->prepare($deleteSql);
    $stmtDelete->execute([':year' => $currentYear, ':month' => $currentMonth]);

    $archivedCount = $stmtInsert->rowCount();
    $message = "Archived and cleaned up $archivedCount records for $currentMonth/$currentYear.";

    // Handle photo archiving if requested
    if ($archiveImages) {
        $photoResult = movePhotosToArchive();
        $movedPhotos = $photoResult['moved'];
        $photoErrors = $photoResult['errors'];
        
        if ($movedPhotos > 0) {
            $message .= " Moved $movedPhotos items from manual_uploads to archived_photos.";
        }
        
        if (!empty($photoErrors)) {
            $message .= " Photo archive warnings: " . implode(', ', $photoErrors);
        }
    }

    echo json_encode([
        'success' => true,
        'status' => 'success',
        'message' => $message
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'status' => 'error',
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>