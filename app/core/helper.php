<?php
class Helper {
    
    // Clean user input
    public static function clean($data) {
        return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
    }

    // Check if user is Admin
    public static function isAdmin() {
        return isset($_SESSION['role']) && $_SESSION['role'] === 'Admin';
    }

    // Check if user is Logged In
    public static function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }

    // Return JSON response (for API)
    public static function jsonResponse($success, $message, $data = null) {
        header('Content-Type: application/json');
        $response = ['success' => $success, 'message' => $message];
        if ($data) $response['data'] = $data;
        echo json_encode($response);
        exit;
    }
}
?>