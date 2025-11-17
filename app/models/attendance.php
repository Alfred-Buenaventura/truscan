<?php
require_once __DIR__ . '/../core/database.php'; // Lowercase

class Attendance {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    // --- DASHBOARD METHODS ---
    public function getTodayRecord($userId) {
        $today = date('Y-m-d');
        $sql = "SELECT * FROM attendance_records WHERE user_id = ? AND date = ?";
        $stmt = $this->db->query($sql, [$userId, $today], "is");
        return $stmt->get_result()->fetch_assoc();
    }
    
    public function countActiveToday() {
        $today = date('Y-m-d');
        $res = $this->db->query("SELECT COUNT(DISTINCT user_id) as c FROM attendance_records WHERE date = '$today' AND time_in IS NOT NULL");
        return $res->get_result()->fetch_assoc()['c'] ?? 0;
    }

    // --- ATTENDANCE REPORTS METHODS ---
    
    // 1. Fetch records with filters
    public function getRecords($filters) {
        $sql = "SELECT ar.*, u.faculty_id, u.first_name, u.last_name, u.role
                FROM attendance_records ar
                JOIN users u ON ar.user_id = u.id
                WHERE 1=1";
        
        $params = [];
        $types = "";

        // Filter by User ID
        if (!empty($filters['user_id'])) {
            $sql .= " AND ar.user_id = ?";
            $params[] = $filters['user_id'];
            $types .= "i";
        }

        // Filter by Search Term
        if (!empty($filters['search'])) {
            $searchTerm = "%" . $filters['search'] . "%";
            $sql .= " AND (u.first_name LIKE ? OR u.last_name LIKE ? OR u.faculty_id LIKE ?)";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $types .= "sss";
        }

        // Filter by Date Range
        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $sql .= " AND ar.date BETWEEN ? AND ?";
            $params[] = $filters['start_date'];
            $params[] = $filters['end_date'];
            $types .= "ss";
        } elseif (!empty($filters['start_date'])) {
            $sql .= " AND ar.date = ?";
            $params[] = $filters['start_date'];
            $types .= "s";
        }

        $sql .= " ORDER BY ar.date DESC, ar.time_in ASC";

        return $this->db->query($sql, $params, $types)->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    // 2. Get Stats for the Cards (The Missing Method)
    public function getStats($userId = null) {
        $today = date('Y-m-d');
        $stats = [];
        
        if ($userId) {
            // User specific stats
            $sql = "SELECT time_in, time_out FROM attendance_records WHERE date = ? AND user_id = ?";
            $res = $this->db->query($sql, [$today, $userId], "si")->get_result()->fetch_assoc();
            
            $stats['entries'] = ($res && $res['time_in']) ? 1 : 0;
            $stats['exits'] = ($res && $res['time_out']) ? 1 : 0;
            
            // Count total days present
            $presSql = "SELECT COUNT(*) as c FROM attendance_records WHERE user_id = ? AND time_in IS NOT NULL";
            $stats['present_total'] = $this->db->query($presSql, [$userId], "i")->get_result()->fetch_assoc()['c'] ?? 0;
        } else {
            // Admin global stats
            $sqlEntries = "SELECT COUNT(*) as c FROM attendance_records WHERE date = ? AND time_in IS NOT NULL";
            $stats['entries'] = $this->db->query($sqlEntries, [$today], "s")->get_result()->fetch_assoc()['c'] ?? 0;
            
            $sqlExits = "SELECT COUNT(*) as c FROM attendance_records WHERE date = ? AND time_out IS NOT NULL";
            $stats['exits'] = $this->db->query($sqlExits, [$today], "s")->get_result()->fetch_assoc()['c'] ?? 0;
            
            $sqlPresent = "SELECT COUNT(DISTINCT user_id) as c FROM attendance_records WHERE date = ? AND time_in IS NOT NULL";
            $stats['present_total'] = $this->db->query($sqlPresent, [$today], "s")->get_result()->fetch_assoc()['c'] ?? 0;
        }
        return $stats;
    }
}
?>