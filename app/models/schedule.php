<?php
require_once __DIR__ . '/../core/database.php';

class Schedule {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }
    
    // --- NEW: Helper to get single schedule details (REQUIRED for Emails) ---
    public function findById($id) {
        $sql = "SELECT * FROM class_schedules WHERE id = ?";
        $stmt = $this->db->query($sql, [$id], "i");
        return $stmt->get_result()->fetch_assoc();
    }

    public function update($id, $day, $subject, $start, $end, $room) {
        $sql = "UPDATE class_schedules 
                SET day_of_week = ?, subject = ?, start_time = ?, end_time = ?, room = ?
                WHERE id = ?";
        $stmt = $this->db->query($sql, [$day, $subject, $start, $end, $room, $id], "sssssi");
        return $stmt->affected_rows > 0;
    }

    public function create($userId, $schedules, $isAdmin) {
        $status = $isAdmin ? 'approved' : 'pending';
        $sql = "INSERT INTO class_schedules (user_id, day_of_week, subject, start_time, end_time, room, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        foreach ($schedules as $s) {
            if (empty($s['subject']) || empty($s['start']) || empty($s['end'])) continue;
            $this->db->query($sql, [
                $userId, $s['day'], $s['subject'], $s['start'], $s['end'], $s['room'], $status
            ], "issssss");
        }
        return true;
    }

    public function delete($scheduleId, $userId, $isAdmin) {
        $sql = "DELETE FROM class_schedules WHERE id = ?";
        if (!$isAdmin) {
            $sql .= " AND user_id = ?";
            $stmt = $this->db->query($sql, [$scheduleId, $userId], "ii");
        } else {
            $stmt = $this->db->query($sql, [$scheduleId], "i");
        }
        return $stmt->affected_rows > 0;
    }

    public function updateStatus($scheduleId, $status) {
        $sql = "UPDATE class_schedules SET status = ? WHERE id = ?";
        $stmt = $this->db->query($sql, [$status, $scheduleId], "si");
        return $stmt->affected_rows > 0;
    }

    public function getByUser($userId, $status) {
        $sql = "SELECT * FROM class_schedules WHERE user_id = ? AND status = ? ORDER BY day_of_week, start_time";
        return $this->db->query($sql, [$userId, $status], "is")->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    // UPDATED: Only fetch Pending schedules for ACTIVE users
    public function getAllByStatus($status) {
        $sql = "SELECT cs.*, u.first_name, u.last_name, u.faculty_id 
                FROM class_schedules cs
                JOIN users u ON cs.user_id = u.id
                WHERE cs.status = ? 
                AND u.status = 'active' 
                ORDER BY u.last_name, cs.day_of_week, cs.start_time";
        return $this->db->query($sql, [$status], "s")->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    // UPDATED: Only fetch Approved schedules for ACTIVE users
    public function getAllApprovedGroupedByUser() {
        $sql = "SELECT cs.*, u.first_name, u.last_name, u.faculty_id 
                FROM class_schedules cs
                JOIN users u ON cs.user_id = u.id
                WHERE cs.status = 'approved' 
                AND u.status = 'active'
                ORDER BY u.last_name, cs.day_of_week, cs.start_time";
        
        $result = $this->db->query($sql)->get_result()->fetch_all(MYSQLI_ASSOC);
        
        $grouped = [];
        foreach ($result as $row) {
            $userId = $row['user_id'];
            if (!isset($grouped[$userId])) {
                $grouped[$userId] = [
                    'user_info' => [
                        'first_name' => $row['first_name'],
                        'last_name' => $row['last_name'],
                        'faculty_id' => $row['faculty_id']
                    ],
                    'schedules' => [],
                    'stats' => ['total_hours' => 0]
                ];
            }
            $grouped[$userId]['schedules'][] = $row;
            $duration = (strtotime($row['end_time']) - strtotime($row['start_time'])) / 3600;
            $grouped[$userId]['stats']['total_hours'] += $duration;
        }
        return $grouped;
    }

    // UPDATED: Stats should only count ACTIVE users' schedules
    public function getAdminStats() {
        // 1. Total Approved Schedules (Filtered by active users)
        $sql_schedules = "SELECT COUNT(cs.id) as total 
                          FROM class_schedules cs 
                          JOIN users u ON cs.user_id = u.id 
                          WHERE cs.status='approved' AND u.status='active'";
        $schedules = $this->db->query($sql_schedules)->get_result()->fetch_assoc()['total'] ?? 0;

        // 2. Total Unique Subjects (Filtered by active users)
        $sql_subjects = "SELECT COUNT(DISTINCT cs.subject) as total 
                         FROM class_schedules cs 
                         JOIN users u ON cs.user_id = u.id 
                         WHERE cs.status='approved' AND u.status='active'";
        $subjects = $this->db->query($sql_subjects)->get_result()->fetch_assoc()['total'] ?? 0;
        
        return [
            'total_schedules' => $schedules,
            'total_subjects' => $subjects
        ];
    }
    
    public function getUserStats($userId) {
        $sql = "SELECT start_time, end_time FROM class_schedules WHERE user_id = ? AND status='approved'";
        $schedules = $this->db->query($sql, [$userId], "i")->get_result()->fetch_all(MYSQLI_ASSOC);
        
        $totalHours = 0;
        $minTime = PHP_INT_MAX;
        $maxTime = 0;
        
        foreach($schedules as $s) {
            $start = strtotime($s['start_time']);
            $end = strtotime($s['end_time']);
            $totalHours += ($end - $start) / 3600;
            if ($start < $minTime) $minTime = $start;
            if ($end > $maxTime) $maxTime = $end;
        }
        
        $dutySpan = ($maxTime > $minTime) ? ($maxTime - $minTime) / 3600 : 0;
        
        return [
            'total_hours' => $totalHours,
            'duty_span' => $dutySpan
        ];
    }
}
?>