<?php
require_once __DIR__ . '/../core/Controller.php';

class ActivityController extends Controller {
    
    public function index() {
        $this->requireAdmin();
        $logModel = $this->model('ActivityLog');

        $limit = 10;
        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $offset = ($page - 1) * $limit;

        $data = [
            'pageTitle' => 'Activity Log',
            'pageSubtitle' => 'View all system and user activities',
            'logs' => $logModel->getPaginated($limit, $offset),
            'page' => $page,
            'totalPages' => ceil($logModel->getTotalCount() / $limit)
        ];

        $this->view('activity_log_view', $data);
    }
}
?>