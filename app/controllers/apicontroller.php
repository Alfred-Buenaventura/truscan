<?php
require_once __DIR__ . '/../core/Controller.php';

class ApiController extends Controller {

    public function __construct() {
        // Constructor
    }

    /**
     * Records attendance from the fingerprint kiosk.
     * Formerly: api/record_attendance.php
     */
    public function recordAttendance() {
        header('Content-Type: application/json');
        
        $data = json_decode(file_get_contents('php://input'));

        if (!$data || !isset($data->user_id)) {
            echo json_encode(['success' => false, 'message' => "Invalid user ID."]);
            exit;
        }

        $userId = (int)$data->user_id;
        $db = new Database();

        // 1. Fetch User
        $userQuery = $db->query("SELECT * FROM users WHERE id = ?", [$userId], "i");
        $user = $userQuery->get_result()->fetch_assoc();

        if (!$user) {
            echo json_encode(['success' => false, 'message' => "User not found."]);
            exit;
        }

        $today = date('Y-m-d');
        $now = date('H:i:s');
        $status = "";

        // 2. Check for existing record today
        $stmt = $db->query("SELECT id, time_in FROM attendance_records WHERE user_id = ? AND date = ?", [$userId, $today], "is");
        $record = $stmt->get_result()->fetch_assoc();

        if ($record) {
            // TIME OUT
            $db->query("UPDATE attendance_records SET time_out = ? WHERE id = ?", [$now, $record['id']], "si");
            $status = "Time Out";
        } else {
            // TIME IN
            $timeInStatus = "On-time"; // Default status
            $dayOfWeek = date('l'); 

            // Check Schedule for Lateness
            $scheduleStmt = $db->query(
                "SELECT MIN(start_time) AS first_class_start 
                 FROM class_schedules 
                 WHERE user_id = ? AND day_of_week = ? AND status = 'approved'",
                [$userId, $dayOfWeek], "is"
            );
            $schedule = $scheduleStmt->get_result()->fetch_assoc();

            if ($schedule && $schedule['first_class_start']) {
                $firstClassStart = strtotime($schedule['first_class_start']);
                $currentTime = strtotime($now);
                $gracePeriodSeconds = 15 * 60; // 15 minutes grace period

                if ($currentTime > ($firstClassStart + $gracePeriodSeconds)) {
                    $timeInStatus = "Late";
                }
            }

            $db->query("INSERT INTO attendance_records (user_id, date, time_in, status) VALUES (?, ?, ?, ?)", 
                [$userId, $today, $now, $timeInStatus], "isss");
            
            $status = ($timeInStatus === "Late") ? "Time In (Late)" : "Time In";
        }

        // 3. Return Data for Display
        echo json_encode([
            'success' => true,
            'message' => "Attendance recorded",
            'data' => [
                "type"   => "attendance",
                "name"   => $user['first_name'] . ' ' . $user['last_name'],
                "status" => $status,
                "time"   => date('h:i A', strtotime($now)),
                "date"   => date('l, F j, Y'),
                "full_timestamp" => date('c')
            ]
        ]);
        exit;
    }

    /**
     * Fetches fingerprint templates for the C# Bridge.
     * Formerly: api/get_all_templates.php
     */
    public function getFingerprintTemplates() {
        header('Content-Type: application/json');
        $db = new Database();
        
        $sql = "SELECT id, fingerprint_data FROM users WHERE fingerprint_data IS NOT NULL AND status = 'active'";
        $result = $db->query($sql);

        if ($result) {
            $templates = [];
            $res = $result->get_result();
            while ($row = $res->fetch_assoc()) {
                $templates[] = [
                    'id' => $row['id'], 
                    'fingerprint_template' => $row['fingerprint_data'] 
                ];
            }
            echo json_encode(['success' => true, 'message' => "Templates fetched", 'data' => $templates]);
        } else {
            echo json_encode(['success' => false, 'message' => "Database error"]);
        }
        exit;
    }

    /**
     * Marks a notification as read.
     * Formerly: mark_notification_read.php
     */
    public function markNotificationRead() {
        header('Content-Type: application/json');
        
        if (session_status() === PHP_SESSION_NONE) session_start();
        
        if (!isset($_SESSION['user_id'])) { 
            echo json_encode(['success'=>false, 'message' => 'Unauthorized']); 
            exit; 
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        $notifId = $data['notification_id'] ?? null;
        
        $db = new Database();
        $db->query("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?", [$notifId, $_SESSION['user_id']], "ii");
        
        echo json_encode(['success' => true]);
        exit;
    }
}
?>