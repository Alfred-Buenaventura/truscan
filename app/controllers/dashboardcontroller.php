<?php
require_once __DIR__ . '/../core/Controller.php';

class DashboardController extends Controller {

    public function index() {
        $this->requireLogin(); // Security check

        $data = [];
        $userModel = $this->model('User');
        $logModel = $this->model('ActivityLog');
        $attModel = $this->model('Attendance');

        // Common Data
        $data['pageTitle'] = 'Dashboard';

        if ($_SESSION['role'] === 'Admin') {
            // --- ADMIN DASHBOARD DATA ---
            $data['pageSubtitle'] = 'Welcome back, System Administrator!';
            $data['totalUsers'] = $userModel->countActive();
            $data['pendingRegistrations'] = $userModel->countPendingFingerprint();
            $data['activeToday'] = $attModel->countActiveToday();
            $data['activityLogs'] = $logModel->getRecentLogs(5); // Global logs
            $data['isAdmin'] = true;
        } else {
            // --- USER DASHBOARD DATA ---
            $firstName = $_SESSION['first_name'] ?? 'User';
            $data['pageSubtitle'] = "Welcome back, " . htmlspecialchars($firstName) . "!";
            
            $data['fingerprint_registered'] = $userModel->getFingerprintStatus($_SESSION['user_id']);
            $data['attendance'] = $attModel->getTodayRecord($_SESSION['user_id']);
            $data['activityLogs'] = $logModel->getRecentLogs(5, $_SESSION['user_id']); // Personal logs
            $data['isAdmin'] = false;
        }

        $this->view('dashboard_view', $data);
    }
}
?>