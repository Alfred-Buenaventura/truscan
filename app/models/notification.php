<?php
require_once __DIR__ . '/../core/database.php';

class Notification {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    public function create($userId, $message, $type = 'info') {
        $sql = "INSERT INTO notifications (user_id, message, type) VALUES (?, ?, ?)";
        return $this->db->query($sql, [$userId, $message, $type], "iss");
    }
}
?>