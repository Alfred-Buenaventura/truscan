<?php
session_start();
require_once __DIR__ . '/../app/init.php';

header('Content-Type: application/json');

$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput);

if (!$data || !isset($data->user_id)) {
    echo json_encode(['success' => false, 'message' => "Invalid user ID."]);
    exit;
}

$userId = (int)$data->user_id;

try {
    $db = Database::getInstance(); // Use Singleton
    
    $today = date('Y-m-d');
    $now = date('H:i:s');
    $currentTs = strtotime("$today $now");
    $noonTs = strtotime("$today 12:00:00");
    
    $status = "";
    $isWarning = false;

    // 1. Fetch User
    $userStmt = $db->query("SELECT * FROM users WHERE id = ? AND status = 'active'", [$userId], "i");
    $user = $userStmt->get_result()->fetch_assoc();

    if (!$user) {
        echo json_encode(['success' => false, 'message' => "User not found."]);
        exit;
    }

    // 2. Determine Session (AM/PM)
    $isAM = ($currentTs < $noonTs);
    $sessionLabel = $isAM ? "(AM)" : "(PM)";

    // 3. Find Existing Record for this session
    // Logic: If AM, find record with time_in < 12:00. If PM, time_in >= 12:00
    $sql = "SELECT id, time_in, time_out FROM attendance_records 
            WHERE user_id = ? AND date = ? AND " . 
            ($isAM ? "time_in < '12:00:00'" : "time_in >= '12:00:00'") . 
            " LIMIT 1";

    $checkStmt = $db->query($sql, [$userId, $today], "is");
    $record = $checkStmt->get_result()->fetch_assoc();

    // --- MAIN ATTENDANCE LOGIC ---

    if (!$record) {
        // === TIME IN LOGIC ===
        
        $timeInStatus = "On-time"; // Default
        $dayOfWeek = date('l'); 

        // A. Fetch Configurable Grace Period (default 15 mins)
        $graceStmt = $db->query("SELECT setting_value FROM system_settings WHERE setting_key = 'late_threshold_minutes'");
        $graceRow = $graceStmt->get_result()->fetch_assoc();
        $graceMinutes = $graceRow ? (int)$graceRow['setting_value'] : 15;
        $graceSeconds = $graceMinutes * 60;

        // B. Check User's Schedule for Today
        // We get the EARLIEST start time for the current day
        $scheduleStmt = $db->query(
            "SELECT MIN(start_time) AS first_class_start 
             FROM class_schedules 
             WHERE user_id = ? AND day_of_week = ? AND status = 'approved'",
            [$userId, $dayOfWeek], "is"
        );
        $schedule = $scheduleStmt->get_result()->fetch_assoc();

        // C. Determine Status
        if ($schedule && $schedule['first_class_start']) {
            $scheduleStartTs = strtotime($today . ' ' . $schedule['first_class_start']);
            $lateThreshold = $scheduleStartTs + $graceSeconds;

            if ($currentTs > $lateThreshold) {
                $timeInStatus = "Late";
            }
            // Note: If $currentTs <= $lateThreshold (including early arrivals), it remains "On-time"
        }

        // D. Insert Record
        $db->query(
            "INSERT INTO attendance_records (user_id, date, time_in, status) VALUES (?, ?, ?, ?)", 
            [$userId, $today, $now, $timeInStatus], 
            "isss"
        );
        
        $status = "Time In - " . $timeInStatus;

    } elseif ($record['time_out'] === null) {
        // === TIME OUT or COOLDOWN LOGIC ===
        
        $timeInTs = strtotime($record['time_in']);
        
        // Check Cooldown (60 Seconds)
        if (($currentTs - $timeInTs) < 60) {
            // User scanned again too quickly
            $status = "Already Timed In";
            $isWarning = true;
        } else {
            // Valid Time Out
            $durationSeconds = $currentTs - $timeInTs;
            $workingHours = $durationSeconds / 3600.0;
            
            // Deduct lunch break if long duration (>5 hours)
            if ($workingHours > 5) { 
                $workingHours -= 1; 
            }

            $db->query(
                "UPDATE attendance_records SET time_out = ?, working_hours = ? WHERE id = ?", 
                [$now, $workingHours, $record['id']], 
                "sdi"
            );
            
            $status = "Time Out";
        }
    } else {
        // Record exists AND Time Out exists -> Already done for this session
        $status = "Already Timed Out";
        $isWarning = true;
    }

    echo json_encode([
        'success' => true, 
        'message' => "Attendance processed", 
        'data' => [
            "type"   => "attendance",
            "name"   => $user['first_name'] . ' ' . $user['last_name'],
            "status" => $status,
            "time"   => date('h:i A', $currentTs),
            "date"   => date('l, F j, Y'),
            "is_warning" => $isWarning
        ]
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => "System error: " . $e->getMessage()]);
}
?>