<?php
require_once __DIR__ . '/../core/controller.php';

class AccountAdminController extends Controller {

    public function create() {
        $this->requireAdmin();
        $userModel = $this->model('User');
        $logModel = $this->model('ActivityLog');
        
        $data = ['pageTitle' => 'Admin Management', 'pageSubtitle' => 'Create a new Administrator account', 'error' => '', 'success' => ''];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // ... (validation code) ...

            // SECURITY UPDATE: Updated default password for Admins
            $defaultPass = "@adminpass123"; 

            $adminData = [
                'faculty_id' => clean($_POST['faculty_id']),
                'username' => strtolower(clean($_POST['faculty_id'])),
                'password' => password_hash($defaultPass, PASSWORD_DEFAULT),
                // ... (rest of fields)
                'first_name' => clean($_POST['first_name']),
                'last_name' => clean($_POST['last_name']),
                'middle_name' => clean($_POST['middle_name']),
                'email' => clean($_POST['email']),
                'phone' => clean($_POST['phone']),
                'role' => 'Admin'
            ];

            if ($userModel->create($adminData)) {
                $logModel->log($_SESSION['user_id'], 'Admin Created', "Created admin: {$adminData['faculty_id']}");
                
                // Professional Email for Admins
                $subject = "BPC Admin Access Granted";
                $msg = "
                <html>
                <body style='font-family: Arial, sans-serif;'>
                    <h3>Welcome Admin " . $adminData['first_name'] . ",</h3>
                    <p>Your administrator privileges have been set up.</p>
                    <p><strong>Login Credentials:</strong></p>
                    <ul>
                        <li>Username: " . $adminData['username'] . "</li>
                        <li>Temporary Password: <strong>" . $defaultPass . "</strong></li>
                    </ul>
                    <p style='color:red;'>Important: You must change this password immediately upon login.</p>
                </body>
                </html>
                ";
                
                sendEmail($adminData['email'], $subject, $msg);
                $data['success'] = "Admin created. Credentials sent via email.";
            }
        }
        $this->view('admin_create_view', $data);
    }
}
?>