<?php
require_once __DIR__ . '/../core/Controller.php';

class DisplayController extends Controller {
    public function index() {
        $this->view('display_view');
    }
    
    public function markRead() {
        header('Content-Type: application/json');
        if (!isset($_SESSION['user_id'])) { echo json_encode(['success'=>false]); exit; }
        
        $data = json_decode(file_get_contents('php://input'), true);
        $notifId = $data['notification_id'] ?? null;
        
        $db = new Database();
        $db->query("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?", [$notifId, $_SESSION['user_id']], "ii");
        
        echo json_encode(['success' => true]);
        exit;
    }
}
?>