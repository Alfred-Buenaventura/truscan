<?php
class Controller {
    
    // Helper to load a view (HTML) file
    public function view($viewName, $data = []) {
        extract($data);
        
        // Force lowercase filename for views
        $viewFile = __DIR__ . '/../views/' . strtolower($viewName) . '.php';
        
        if (file_exists($viewFile)) {
            require_once $viewFile;
        } else {
            // Fallback try original name
             $viewFileOrig = __DIR__ . '/../views/' . $viewName . '.php';
             if (file_exists($viewFileOrig)) {
                 require_once $viewFileOrig;
             } else {
                 die("View does not exist: " . $viewName);
             }
        }
    }

    // Helper to load a model
    public function model($modelName) {
        // FORCE LOWERCASE for model files (e.g., 'User' -> 'user.php')
        // Ensure you have renamed User.php to user.php on the server!
        require_once __DIR__ . '/../models/' . strtolower($modelName) . '.php';
        return new $modelName();
    }

    // Security Check: Require Login
    public function requireLogin() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: login.php');
            exit;
        }
        
        $currentPage = basename($_SERVER['PHP_SELF']);
        if (isset($_SESSION['force_password_change']) && $_SESSION['force_password_change'] === 1) {
             if ($currentPage !== 'change_password.php' && $currentPage !== 'logout.php') {
                header('Location: change_password.php?first_login=1');
                exit;
            }
        }
    }

    public function requireAdmin() {
        $this->requireLogin();
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
            die('Access Denied');
        }
    }

    public function markAllNotificationsRead() {
    header('Content-Type: application/json');
    
    if (session_status() === PHP_SESSION_NONE) session_start();
    
    if (!isset($_SESSION['user_id'])) { 
        echo json_encode(['success'=>false, 'message' => 'Unauthorized']); 
        exit; 
    }
    
    $db = new Database();
    $db->query(
        "UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0", 
        [$_SESSION['user_id']], 
        "i"
    );
    
    echo json_encode(['success' => true, 'message' => 'All notifications marked as read']);
    exit;
    }
}
?>