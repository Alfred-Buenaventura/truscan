<?php
require_once __DIR__ . '/../core/Controller.php';

class AccountAdminController extends Controller {

    public function create() {
        $this->requireAdmin();
        $userModel = $this->model('User');
        $logModel = $this->model('ActivityLog');
        
        $data = ['pageTitle' => 'Admin Management', 'pageSubtitle' => 'Create a new Administrator account', 'error' => '', 'success' => ''];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $facultyId = clean($_POST['faculty_id']);
            
            if ($userModel->exists($facultyId)) {
                $data['error'] = 'Faculty ID already exists.';
            } else {
                $adminData = [
                    'faculty_id' => $facultyId,
                    'username' => strtolower($facultyId),
                    'password' => password_hash('DefaultPass123!', PASSWORD_DEFAULT),
                    'first_name' => clean($_POST['first_name']),
                    'last_name' => clean($_POST['last_name']),
                    'middle_name' => clean($_POST['middle_name']),
                    'email' => clean($_POST['email']),
                    'phone' => clean($_POST['phone']),
                    'role' => 'Admin'
                ];

                if ($userModel->create($adminData)) {
                    $logModel->log($_SESSION['user_id'], 'Admin Created', "Created admin: $facultyId");
                    $data['success'] = "Admin account created successfully!";
                } else {
                    $data['error'] = 'Database error.';
                }
            }
        }
        $this->view('admin_create_view', $data);
    }
}
?>