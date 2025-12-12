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
    $db = new Database();
    $conn = $db->conn;
    
    $today = date('Y-m-d');
    $now = date('H:i:s');
    $currentTs = strtotime("$today $now");
    $noonTs = strtotime("$today 12:00:00");
    
    $status = "";
    $isWarning = false;

    // 1. Fetch User
    $userStmt = $conn->prepare("SELECT * FROM users WHERE id = ? AND status = 'active'");
    $userStmt->bind_param("i", $userId);
    $userStmt->execute();
    $user = $userStmt->get_result()->fetch_assoc();
    $userStmt->close();

    if (!$user) {
        echo json_encode(['success' => false, 'message' => "User not found."]);
        exit;
    }

    // 2. Determine Session
    $isAM = ($currentTs < $noonTs);
    $sessionLabel = $isAM ? "(AM)" : "(PM)";

    // 3. Find Record for this session
    $sql = "SELECT id, time_in, time_out FROM attendance_records 
            WHERE user_id = ? AND date = ? AND " . 
            ($isAM ? "time_in < '12:00:00'" : "time_in >= '12:00:00'") . 
            " LIMIT 1";

    $checkStmt = $conn->prepare($sql);
    $checkStmt->bind_param("is", $userId, $today);
    $checkStmt->execute();
    $record = $checkStmt->get_result()->fetch_assoc();
    $checkStmt->close();

    // --- LOGIC ---

    if (!$record) {
        // TIME IN
        $timeInStatus = "Present";
        $amGrace = strtotime("$today 07:10:00");
        $pmGrace = strtotime("$today 13:10:00");

        if ($isAM) {
            if ($currentTs > $amGrace) $timeInStatus = "Late";
        } else {
            if ($currentTs > $pmGrace) $timeInStatus = "Late";
        }

        $insertStmt = $conn->prepare("INSERT INTO attendance_records (user_id, date, time_in, status) VALUES (?, ?, ?, ?)");
        $insertStmt->bind_param("isss", $userId, $today, $now, $timeInStatus);
        $insertStmt->execute();
        
        $status = ($timeInStatus === "Late") ? "Time In (Late)" : "Time In";

    } elseif ($record['time_out'] === null) {
        // TIME OUT
        $timeInTs = strtotime($record['time_in']);
        
        // Cooldown (60s)
        if (($currentTs - $timeInTs) < 60) {
            $status = "Already Timed In $sessionLabel";
            $isWarning = true;
        } else {
            // --- CALCULATE WORKING HOURS ---
            $durationSeconds = $currentTs - $timeInTs;
            $workingHours = $durationSeconds / 3600.0;

            // Safety: If a single session is > 5 hours (e.g., 7am to 5pm without scanning out at lunch),
            // deduct 1 hour automatically to handle the missed scan.
            if ($workingHours > 5) { 
                $workingHours -= 1; 
            }

            $updateStmt = $conn->prepare("UPDATE attendance_records SET time_out = ?, working_hours = ? WHERE id = ?");
            $updateStmt->bind_param("sdi", $now, $workingHours, $record['id']);
            $updateStmt->execute();
            
            $status = "Time Out";
        }
    } else {
        $status = "Already Timed Out $sessionLabel";
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