<?php
require_once '../config.php';
requireAdmin(); // Double-check security

$data = json_decode(file_get_contents('php://input'), true);
$userId = $data['user_id'] ?? 0;
$records = $data['records'] ?? [];
$adminId = $_SESSION['user_id'];

if (empty($userId) || empty($records)) {
    jsonResponse(false, 'Invalid data submitted.');
    exit;
}

$db = db();
$db->begin_transaction();

try {
    $stmt_update = $db->prepare(
        "UPDATE attendance_records SET time_in=?, time_out=?, status=?, working_hours=?, remarks=? WHERE id=?"
    );
    $stmt_insert = $db->prepare(
        "INSERT INTO attendance_records (user_id, date, time_in, time_out, status, working_hours, remarks) 
         VALUES (?, ?, ?, ?, ?, ?, ?)"
    );

    $logDetails = [];

    foreach ($records as $rec) {
        $recordId = $rec['record_id'];
        $date = $rec['date'];
        $timeIn = !empty($rec['time_in']) ? $rec['time_in'] : null;
        $timeOut = !empty($rec['time_out']) ? $rec['time_out'] : null;
        $status = !empty($rec['status']) ? $rec['status'] : null;
        $remarks = !empty($rec['remarks']) ? $rec['remarks'] : null;
        $day = $rec['day'];
        
        // Skip if all fields are empty and no record exists
        if (empty($recordId) && empty($timeIn) && empty($timeOut) && empty($status) && empty($remarks)) {
            continue;
        }
        
        // Calculate working hours
        $workingHours = null;
        if ($timeIn && $timeOut) {
            $in = new DateTime($timeIn);
            $out = new DateTime($timeOut);
            $span = $out->getTimestamp() - $in->getTimestamp();
            $workingHours = $span / 3600.0;
            if ($workingHours > 5) {
                $workingHours -= 1; // 1-hour lunch break
            }
        }

        if (!empty($recordId)) {
            // This is an existing record, UPDATE it
            $stmt_update->bind_param("sssdsi", $timeIn, $timeOut, $status, $workingHours, $remarks, $recordId);
            $stmt_update->execute();
        } else {
            // This is a new record, INSERT it
            $stmt_insert->bind_param("issssds", $userId, $date, $timeIn, $timeOut, $status, $workingHours, $remarks);
            $stmt_insert->execute();
        }
        
        // Add to log
        $logDetails[] = "Day $day: [T-In:$timeIn, T-Out:$timeOut, S:$status, R:$remarks]";
    }

    // All queries succeeded, commit the transaction
    $db->commit();
    
    // Log the entire batch edit as one action
    logActivity(
        $adminId, 
        'DTR Edited', 
        "Admin edited DTR for user ID $userId. Changes: " . implode('; ', $logDetails)
    );
    
    jsonResponse(true, 'DTR updated successfully!');

} catch (Exception $e) {
    $db->rollback();
    jsonResponse(false, 'An error occurred during update: ' . $e->getMessage());
}
?>