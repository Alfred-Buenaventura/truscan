<?php
require_once __DIR__ . '/../core/database.php';

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
    
    public function getRecords($filters) {
        $sql = "SELECT ar.*, u.faculty_id, u.first_name, u.last_name, u.role
                FROM attendance_records ar
                JOIN users u ON ar.user_id = u.id
                WHERE 1=1";
        
        $params = [];
        $types = "";

        if (!empty($filters['user_id'])) {
            $sql .= " AND ar.user_id = ?";
            $params[] = $filters['user_id'];
            $types .= "i";
        }

        if (!empty($filters['search'])) {
            $searchTerm = "%" . $filters['search'] . "%";
            $sql .= " AND (u.first_name LIKE ? OR u.last_name LIKE ? OR u.faculty_id LIKE ?)";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $types .= "sss";
        }

        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $sql .= " AND ar.date BETWEEN ? AND ?";
            $params[] = $filters['start_date'];
            $params[] = $filters['end_date'];
            $types .= "ss";
        }

        // --- NEW: Filter by Status for History Page ---
        if (!empty($filters['status_type'])) {
            if ($filters['status_type'] === 'Late') {
                $sql .= " AND ar.status LIKE '%Late%'";
            } elseif ($filters['status_type'] === 'Absent') {
                $sql .= " AND ar.status = 'Absent'";
            } elseif ($filters['status_type'] === 'Present') {
                $sql .= " AND ar.status != 'Absent'";
            }
        }

        $sql .= " ORDER BY ar.date DESC, ar.time_in ASC";

        return $this->db->query($sql, $params, $types)->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    // --- NEW: Get History Stats for Cards ---
    public function getHistoryStats($filters) {
        $sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status != 'Absent' THEN 1 ELSE 0 END) as present,
                    SUM(CASE WHEN status LIKE '%Late%' THEN 1 ELSE 0 END) as late,
                    SUM(CASE WHEN status = 'Absent' THEN 1 ELSE 0 END) as absent
                FROM attendance_records ar
                WHERE 1=1";
        
        $params = [];
        $types = "";

        if (!empty($filters['user_id'])) {
            $sql .= " AND ar.user_id = ?";
            $params[] = $filters['user_id'];
            $types .= "i";
        }

        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $sql .= " AND ar.date BETWEEN ? AND ?";
            $params[] = $filters['start_date'];
            $params[] = $filters['end_date'];
            $types .= "ss";
        }

        return $this->db->query($sql, $params, $types)->get_result()->fetch_assoc();
    }

    // Get Stats for the Cards (Existing Method)
    public function getStats($userId = null) {
        $today = date('Y-m-d');
        $stats = [];
        
        if ($userId) {
            $sql = "SELECT time_in, time_out FROM attendance_records WHERE date = ? AND user_id = ?";
            $res = $this->db->query($sql, [$today, $userId], "si")->get_result()->fetch_assoc();
            $stats['entries'] = ($res && $res['time_in']) ? 1 : 0;
            $stats['exits'] = ($res && $res['time_out']) ? 1 : 0;
            $presSql = "SELECT COUNT(*) as c FROM attendance_records WHERE user_id = ? AND time_in IS NOT NULL";
            $stats['present_total'] = $this->db->query($presSql, [$userId], "i")->get_result()->fetch_assoc()['c'] ?? 0;
        } else {
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