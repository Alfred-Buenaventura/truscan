<?php
// LOWERCASE REQUIRE
require_once __DIR__ . '/../core/database.php';

class User {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    // --- AUTHENTICATION ---
    public function findUserByUsername($username) {
        $sql = "SELECT * FROM users WHERE (username = ? OR faculty_id = ?) AND status = 'active'";
        $stmt = $this->db->query($sql, [$username, $username], "ss");
        return $stmt->get_result()->fetch_assoc();
    }

    public function findById($id) {
        $sql = "SELECT * FROM users WHERE id = ? AND status = 'active'";
        $stmt = $this->db->query($sql, [$id], "i");
        return $stmt->get_result()->fetch_assoc();
    }

    // --- PROFILE UPDATES ---
    public function updateProfile($id, $firstName, $lastName, $middleName, $email, $phone) {
        $sql = "UPDATE users SET first_name=?, last_name=?, middle_name=?, email=?, phone=? WHERE id=?";
        $stmt = $this->db->query($sql, [$firstName, $lastName, $middleName, $email, $phone, $id], "sssssi");
        return $stmt->affected_rows >= 0;
    }

    // --- DASHBOARD STATS ---
    public function countActive() {
        $res = $this->db->query("SELECT COUNT(*) as c FROM users WHERE status='active'");
        return $res->get_result()->fetch_assoc()['c'] ?? 0;
    }

    public function countPendingFingerprint() {
        $res = $this->db->query("SELECT COUNT(*) as c FROM users WHERE status='active' AND fingerprint_registered=0");
        return $res->get_result()->fetch_assoc()['c'] ?? 0;
    }

    public function getFingerprintStatus($userId) {
        $stmt = $this->db->query("SELECT fingerprint_registered FROM users WHERE id = ?", [$userId], "i");
        $row = $stmt->get_result()->fetch_assoc();
        return $row['fingerprint_registered'] ?? 0;
    }

    // --- ACCOUNT MANAGEMENT ---
    public function exists($facultyId) {
        $sql = "SELECT id FROM users WHERE faculty_id = ?";
        $res = $this->db->query($sql, [$facultyId], "s");
        return $res->get_result()->num_rows > 0;
    }

    public function create($data) {
        $sql = "INSERT INTO users (faculty_id, username, password, first_name, last_name, middle_name, email, phone, role, force_password_change) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)";
        $stmt = $this->db->query($sql, [
            $data['faculty_id'],
            $data['username'],
            $data['password'],
            $data['first_name'],
            $data['last_name'],
            $data['middle_name'],
            $data['email'],
            $data['phone'],
            $data['role']
        ], "sssssssss");
        return $this->db->conn->insert_id;
    }

    public function update($id, $firstName, $lastName, $middleName, $email, $phone) {
        $sql = "UPDATE users SET first_name=?, last_name=?, middle_name=?, email=?, phone=? WHERE id=?";
        $this->db->query($sql, [$firstName, $lastName, $middleName, $email, $phone, $id], "sssssi");
        return true;
    }

    public function setStatus($id, $status) {
        $sql = "UPDATE users SET status=? WHERE id=?";
        $this->db->query($sql, [$status, $id], "si");
        return true;
    }

    /**
     * Permanently deletes a user and ALL their associated records.
     */
    public function delete($id) {
        // 1. Delete Schedules
        $this->db->query("DELETE FROM class_schedules WHERE user_id=?", [$id], "i");

        // 2. Delete Attendance Records
        $this->db->query("DELETE FROM attendance_records WHERE user_id=?", [$id], "i");

        // 3. Delete Notifications
        $this->db->query("DELETE FROM notifications WHERE user_id=?", [$id], "i");

        // 4. Delete Activity Logs (Optional: remove if you want to keep audit history for deleted users)
        $this->db->query("DELETE FROM activity_logs WHERE user_id=?", [$id], "i");

        // 5. Finally, delete the User (Fingerprint data is stored in this table, so it gets removed here)
        $sql = "DELETE FROM users WHERE id=?";
        $this->db->query($sql, [$id], "i");
        
        return true;
    }

    public function getAllActive() {
        $result = $this->db->query("SELECT * FROM users WHERE status='active' ORDER BY created_at DESC");
        return $result->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getAllArchived() {
        $result = $this->db->query("SELECT * FROM users WHERE status='archived' ORDER BY created_at DESC");
        return $result->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getStats() {
        $sql = "SELECT
            COUNT(*) as total_active,
            SUM(CASE WHEN role != 'Admin' THEN 1 ELSE 0 END) as non_admin_active,
            SUM(CASE WHEN role = 'Admin' THEN 1 ELSE 0 END) as admin_active
        FROM users WHERE status = 'active'";
        return $this->db->query($sql)->get_result()->fetch_assoc();
    }
    
    public function getAllStaff() {
        $sql = "SELECT id, faculty_id, first_name, last_name FROM users WHERE status='active' AND role != 'Admin' ORDER BY first_name";
        return $this->db->query($sql)->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function updateFingerprint($userId, $fingerprintData) {
        $sql = "UPDATE users SET fingerprint_data=?, fingerprint_registered=1, fingerprint_registered_at=NOW() WHERE id=?";
        return $this->db->query($sql, [$fingerprintData, $userId], "si");
    }

    public function getPendingUsers() {
        return $this->db->query("SELECT * FROM users WHERE status='active' AND fingerprint_registered=0 ORDER BY created_at DESC")->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getRegisteredUsers() {
        return $this->db->query("SELECT * FROM users WHERE status='active' AND fingerprint_registered=1 ORDER BY first_name ASC")->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}
?>