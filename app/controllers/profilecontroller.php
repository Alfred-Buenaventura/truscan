<?php
require_once __DIR__ . '/../core/Controller.php';

class ProfileController extends Controller {

    public function index() {
        $this->requireLogin();

        $userModel = $this->model('User');
        $logModel = $this->model('ActivityLog');
        
        $userId = $_SESSION['user_id'];
        $data = [
            'pageTitle' => 'My Profile',
            'pageSubtitle' => 'View and edit your account information',
            'error' => '',
            'success' => ''
        ];

        // Handle POST Request (Update Profile)
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
            $firstName = trim($_POST['first_name']);
            $lastName = trim($_POST['last_name']);
            $middleName = trim($_POST['middle_name']);
            $email = trim($_POST['email']);
            $phone = trim($_POST['phone']);

            if ($userModel->updateProfile($userId, $firstName, $lastName, $middleName, $email, $phone)) {
                // Update Session Variables
                $_SESSION['full_name'] = $firstName . ' ' . $lastName;
                $_SESSION['first_name'] = $firstName;
                $_SESSION['last_name'] = $lastName;

                $logModel->log($userId, 'Profile Updated', 'User updated their profile');
                $data['success'] = 'Profile updated successfully!';
            } else {
                $data['error'] = 'Failed to update profile.';
            }
        }

        // Fetch User Data & Logs for View
        $data['user'] = $userModel->findById($userId);
        $data['activities'] = $logModel->getRecentLogs(10, $userId);

        $this->view('profile_view', $data);
    }
}
?>