<?php
require_once '../config.php';

// Get the POSTed data (which is JSON sent from display.php)
$data = json_decode(file_get_contents('php://input'));

if (!$data || !isset($data->user_id)) {
    jsonResponse(false, "Invalid user ID.");
    exit;
}

$userId = (int)$data->user_id;
$db = db();

// Get current date/time in PH time
$today = date('Y-m-d');
$now = date('H:i:s');
$status = "";

// Fetch the user's details
$user = getUser($userId);
if (!$user) {
    jsonResponse(false, "User not found.");
    exit;
}

// Check if an attendance record for today already exists
$stmt = $db->prepare("SELECT id, time_in FROM attendance_records WHERE user_id = ? AND date = ?");
$stmt->bind_param("is", $userId, $today);
$stmt->execute();
$record = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($record) {
    // RECORD EXISTS: This is a TIME OUT
    // We update the existing record with the time_out
    $stmt = $db->prepare("UPDATE attendance_records SET time_out = ? WHERE id = ?");
    $stmt->bind_param("si", $now, $record['id']);
    $stmt->execute();
    $stmt->close();
    $status = "Time Out";

} else {
    // NO RECORD: This is a TIME IN
    
    // --- NEW: Logic to determine if user is 'Late' or 'On-time' ---
    $timeInStatus = "On-time"; // Default
    $dayOfWeek = date('l'); // Get current day name, e.g., "Monday"

    // 1. Find the user's *first* scheduled class for today
    $scheduleStmt = $db->prepare(
        "SELECT MIN(start_time) AS first_class_start 
         FROM class_schedules 
         WHERE user_id = ? AND day_of_week = ? AND status = 'approved'"
    );
    $scheduleStmt->bind_param("is", $userId, $dayOfWeek);
    $scheduleStmt->execute();
    $schedule = $scheduleStmt->get_result()->fetch_assoc();
    $scheduleStmt->close();

    if ($schedule && $schedule['first_class_start']) {
        // We have a schedule for today
        $firstClassStart = strtotime($schedule['first_class_start']);
        $currentTime = strtotime($now);
        
        // --- GRACE PERIOD (e.g., 15 minutes) ---
        $gracePeriodSeconds = 15 * 60; // 15 minutes
        $lateThreshold = $firstClassStart + $gracePeriodSeconds;
        // --- END GRACE PERIOD ---

        if ($currentTime > $lateThreshold) {
            $timeInStatus = "Late";
        }
    } else {
        // No schedule found for today.
        // They are "On-time" by default since there's no schedule to be late for.
        $timeInStatus = "On-time";
    }
    // --- END NEW LOGIC ---

    // 2. Insert the new record with the correct status
    $stmt = $db->prepare("INSERT INTO attendance_records (user_id, date, time_in, status) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $userId, $today, $now, $timeInStatus);
    $stmt->execute();
    $stmt->close();
    
    // 3. Set the status message for the display
    if ($timeInStatus === "Late") {
        $status = "Time In (Late)";
    } else {
        $status = "Time In";
    }
}

// Prepare the data to send back to display.php
// This matches the format showScanEvent() expects
$displayData = [
    "type"   => "attendance",
    "name"   => $user['first_name'] . ' ' . $user['last_name'],
    "status" => $status,
    "time"   => date('h:i A', strtotime($now)),
    "date"   => date('l, F j, Y') // e.g., "Saturday, November 08, 2025"
];

// Send a successful response with the display data
jsonResponse(true, "Attendance recorded", $displayData);
?>