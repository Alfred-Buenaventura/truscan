<?php
session_start();
require_once __DIR__ . '/../app/init.php';

header('Content-Type: application/json');

// Get input data
$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput);

// Validate input
if (!$data || !isset($data->user_id)) {
    echo json_encode([
        'success' => false, 
        'message' => "Invalid user ID.",
        'debug' => [
            'raw_input' => $rawInput,
            'decoded' => $data
        ]
    ]);
    exit;
}

$userId = (int)$data->user_id;

// Validate user ID
if ($userId <= 0) {
    echo json_encode([
        'success' => false, 
        'message' => "Invalid user ID: $userId"
    ]);
    exit;
}

try {
    // Create database connection
    $db = new Database();
    $conn = $db->conn;
    
    // Check connection
    if (!$conn) {
        throw new Exception("Database connection failed");
    }

    // Get current date/time in PH time
    $today = date('Y-m-d');
    $now = date('H:i:s');
    $status = "";

    // Fetch the user's details
    $userStmt = $conn->prepare("SELECT * FROM users WHERE id = ? AND status = 'active'");
    if (!$userStmt) {
        throw new Exception("Failed to prepare user query: " . $conn->error);
    }
    
    $userStmt->bind_param("i", $userId);
    $userStmt->execute();
    $userResult = $userStmt->get_result();
    $user = $userResult->fetch_assoc();
    $userStmt->close();

    if (!$user) {
        echo json_encode([
            'success' => false, 
            'message' => "User not found or inactive: ID $userId"
        ]);
        exit;
    }

    // Check if an attendance record for today already exists
    $checkStmt = $conn->prepare("SELECT id, time_in, time_out FROM attendance_records WHERE user_id = ? AND date = ?");
    if (!$checkStmt) {
        throw new Exception("Failed to prepare check query: " . $conn->error);
    }
    
    $checkStmt->bind_param("is", $userId, $today);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    $record = $checkResult->fetch_assoc();
    $checkStmt->close();

    if ($record) {
        // RECORD EXISTS: This is a TIME OUT
        if ($record['time_out']) {
            // Already timed out today
            $status = "Already Timed Out";
        } else {
            // Update with time out
            $updateStmt = $conn->prepare("UPDATE attendance_records SET time_out = ? WHERE id = ?");
            if (!$updateStmt) {
                throw new Exception("Failed to prepare update query: " . $conn->error);
            }
            
            $updateStmt->bind_param("si", $now, $record['id']);
            $updateStmt->execute();
            $updateStmt->close();
            $status = "Time Out";
        }
    } else {
        // NO RECORD: This is a TIME IN
        $timeInStatus = "On-time"; // Default
        $dayOfWeek = date('l'); // Get current day name

        // Check schedule for lateness
        $scheduleStmt = $conn->prepare(
            "SELECT MIN(start_time) AS first_class_start 
             FROM class_schedules 
             WHERE user_id = ? AND day_of_week = ? AND status = 'approved'"
        );
        
        if ($scheduleStmt) {
            $scheduleStmt->bind_param("is", $userId, $dayOfWeek);
            $scheduleStmt->execute();
            $scheduleResult = $scheduleStmt->get_result();
            $schedule = $scheduleResult->fetch_assoc();
            $scheduleStmt->close();

            if ($schedule && $schedule['first_class_start']) {
                $firstClassStart = strtotime($schedule['first_class_start']);
                $currentTime = strtotime($now);
                $gracePeriodSeconds = 15 * 60; // 15 minutes grace period

                if ($currentTime > ($firstClassStart + $gracePeriodSeconds)) {
                    $timeInStatus = "Late";
                }
            }
        }

        // Insert the new record
        $insertStmt = $conn->prepare("INSERT INTO attendance_records (user_id, date, time_in, status) VALUES (?, ?, ?, ?)");
        if (!$insertStmt) {
            throw new Exception("Failed to prepare insert query: " . $conn->error);
        }
        
        $insertStmt->bind_param("isss", $userId, $today, $now, $timeInStatus);
        $insertStmt->execute();
        $insertStmt->close();
        
        $status = ($timeInStatus === "Late") ? "Time In (Late)" : "Time In";
    }

    // Prepare the response data
    $displayData = [
        "type"   => "attendance",
        "name"   => $user['first_name'] . ' ' . $user['last_name'],
        "status" => $status,
        "time"   => date('h:i A', strtotime($now)),
        "date"   => date('l, F j, Y'),
        "full_timestamp" => date('c') // ISO 8601 format
    ];

    // Send success response
    echo json_encode([
        'success' => true, 
        'message' => "Attendance recorded", 
        'data' => $displayData
    ]);

} catch (Exception $e) {
    // Log the error
    error_log("Attendance API Error: " . $e->getMessage());
    
    // Send error response
    echo json_encode([
        'success' => false,
        'message' => "System error: " . $e->getMessage()
    ]);
}
?>