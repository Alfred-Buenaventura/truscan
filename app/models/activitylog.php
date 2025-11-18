<?php
require_once __DIR__ . '/../core/database.php';

class ActivityLog {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    // This is the method causing the error - ensuring it exists
    public function log($userId, $action, $details = '') {
        // Handle cases where IP might not be set (e.g. CLI)
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'; 
        
        $sql = "INSERT INTO activity_logs (user_id, action, description, ip_address) VALUES (?, ?, ?, ?)";
        $this->db->query($sql, [$userId, $action, $details, $ip], "isss");
    }

    public function getRecentLogs($limit = 5, $userId = null) {
        $sql = "SELECT al.*, u.first_name, u.last_name 
                FROM activity_logs al 
                LEFT JOIN users u ON al.user_id = u.id";
        
        if ($userId) {
            $sql .= " WHERE al.user_id = ? ORDER BY al.created_at DESC LIMIT ?";
            $stmt = $this->db->query($sql, [$userId, $limit], "ii");
        } else {
            $sql .= " ORDER BY al.created_at DESC LIMIT ?";
            $stmt = $this->db->query($sql, [$limit], "i");
        }
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getTotalCount() {
        $res = $this->db->query("SELECT COUNT(*) as total FROM activity_logs");
        return $res->get_result()->fetch_assoc()['total'] ?? 0;
    }

    public function getPaginated($limit, $offset) {
        $sql = "SELECT al.*, u.first_name, u.last_name 
                FROM activity_logs al 
                LEFT JOIN users u ON al.user_id = u.id 
                ORDER BY al.created_at DESC LIMIT ? OFFSET ?";
        return $this->db->query($sql, [$limit, $offset], "ii")->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}
?>