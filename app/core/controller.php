<?php
class Controller {
    
    // Helper to load a view (HTML) file
    public function view($viewName, $data = []) {
        // Extract data array to variables (e.g., ['error' => 'Msg'] becomes $error = 'Msg')
        extract($data);
        
        // Check if file exists
        if (file_exists(__DIR__ . '/../views/' . $viewName . '.php')) {
            require_once __DIR__ . '/../views/' . $viewName . '.php';
        } else {
            die("View does not exist: " . $viewName);
        }
    }

    // Helper to load a model
    public function model($modelName) {
        require_once __DIR__ . '/../models/' . $modelName . '.php';
        return new $modelName();
    }

    // Security Check: Require Login (Migrated from config.php)
    public function requireLogin() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: login.php');
            exit;
        }
        
        // Force password change check
        $currentPage = basename($_SERVER['PHP_SELF']);
        if (isset($_SESSION['force_password_change']) && $_SESSION['force_password_change'] === 1) {
             if ($currentPage !== 'change_password.php' && $currentPage !== 'logout.php') {
                header('Location: change_password.php?first_login=1');
                exit;
            }
        }
    }

    // Security Check: Require Admin (Migrated from config.php)
    public function requireAdmin() {
        $this->requireLogin();
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
            die('Access Denied');
        }
    }
}
?>