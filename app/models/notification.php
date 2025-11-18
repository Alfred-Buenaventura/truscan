<?php
require_once __DIR__ . '/../core/database.php';

class Notification {
    
    // No constructor needed for static methods

    /**
     * Creates a new notification (static method)
     * FIX: Made this method 'static' and instantiated its own DB.
     */
    public static function create($userId, $message, $type = 'info') {
        // Instantiate database within the static method
        $db = new Database();
        
        $sql = "INSERT INTO notifications (user_id, message, type) VALUES (?, ?, ?)";
        // Use the db instance to query
        return $db->query($sql, [$userId, $message, $type], "iss");
    }
}
?>