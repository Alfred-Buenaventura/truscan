<?php
require_once __DIR__ . '/../core/database.php';

class Schedule {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    // --- CRUD OPERATIONS ---
    public function add($userId, $day, $subject, $startTime, $endTime, $room) {
        $sql = "INSERT INTO class_schedules (user_id, day_of_week, subject, start_time, end_time, room, status) VALUES (?, ?, ?, ?, ?, ?, 'pending')";
        return $this->db->query($sql, [$userId, $day, $subject, $startTime, $endTime, $room], "isssss");
    }

    public function update($id, $userId, $day, $subject, $startTime, $endTime, $room, $isAdmin) {
        $sql = "UPDATE class_schedules SET day_of_week=?, subject=?, start_time=?, end_time=?, room=?, status='pending' WHERE id=?";
        $params = [$day, $subject, $startTime, $endTime, $room, $id];
        $types = "sssssi";

        if (!$isAdmin) {
            $sql .= " AND user_id=?";
            $params[] = $userId;
            $types .= "i";
        }
        
        return $this->db->query($sql, $params, $types);
    }

    public function delete($id, $userId, $isAdmin) {
        $sql = "DELETE FROM class_schedules WHERE id=?";
        $params = [$id];
        $types = "i";

        if (!$isAdmin) {
            $sql .= " AND user_id=?";
            $params[] = $userId;
            $types .= "i";
        }
        return $this->db->query($sql, $params, $types);
    }

    // --- APPROVAL WORKFLOW ---
    public function setStatus($id, $status) {
        $sql = "UPDATE class_schedules SET status=? WHERE id=?";
        return $this->db->query($sql, [$status, $id], "si");
    }

    // --- FETCH METHODS ---
    
    // Fetch Pending Schedules (Admin sees all, User sees own)
    public function getPending($userId = null) {
        $sql = "SELECT cs.*, u.first_name, u.last_name, u.faculty_id 
                FROM class_schedules cs 
                JOIN users u ON cs.user_id = u.id 
                WHERE cs.status = 'pending'";
        
        $params = [];
        $types = "";

        if ($userId) {
            $sql .= " AND cs.user_id = ?";
            $params[] = $userId;
            $types .= "i";
        }

        $sql .= " ORDER BY cs.created_at ASC";
        return $this->db->query($sql, $params, $types)->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    // Fetch Approved Schedules with Filters
    public function getApproved($filters, $isAdmin) {
        // Base Query
        if ($isAdmin) {
            $sql = "SELECT cs.*, u.first_name, u.last_name, u.faculty_id 
                    FROM class_schedules cs 
                    JOIN users u ON cs.user_id = u.id 
                    WHERE cs.status = 'approved'";
        } else {
            // FIX: Added 'cs' alias here so filters using 'cs.user_id' work
            $sql = "SELECT cs.*, null as first_name, null as last_name, null as faculty_id 
                    FROM class_schedules cs
                    WHERE cs.status = 'approved'";
        }

        $params = [];
        $types = "";

        // Apply Filters
        if (!empty($filters['user_id'])) {
            $sql .= " AND cs.user_id = ?";
            $params[] = $filters['user_id'];
            $types .= "i";
        }
        if (!empty($filters['day_of_week'])) {
            $sql .= " AND cs.day_of_week = ?";
            $params[] = $filters['day_of_week'];
            $types .= "s";
        }

        // Sorting
        if ($isAdmin) {
             $sql .= " ORDER BY u.last_name, u.first_name, FIELD(day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'), start_time";
        } else {
             $sql .= " ORDER BY FIELD(day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'), start_time";
        }

        return $this->db->query($sql, $params, $types)->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    // Fetch raw stats counts (Admin Dashboard logic)
    public function getGeneralStats() {
        $schedules = $this->db->query("SELECT COUNT(*) as c FROM class_schedules WHERE status='approved'")->get_result()->fetch_assoc()['c'] ?? 0;
        $users = $this->db->query("SELECT COUNT(DISTINCT user_id) as c FROM class_schedules WHERE status='approved'")->get_result()->fetch_assoc()['c'] ?? 0;
        return ['total_schedules' => $schedules, 'total_users_with_schedules' => $users];
    }
}
?>