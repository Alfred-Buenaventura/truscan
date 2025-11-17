<?php
require_once __DIR__ . '/../core/Controller.php';

class PageController extends Controller {

    public function about() {
        $this->requireLogin();
        $data = [
            'pageTitle' => 'About Us',
            'pageSubtitle' => 'System Information'
        ];
        $this->view('about_view', $data);
    }

    public function contact() {
        $this->requireLogin();
        
        $userModel = $this->model('User'); // Load User Model
        $logModel = $this->model('ActivityLog');
        
        // Fetch current user data
        $currentUser = $userModel->findById($_SESSION['user_id']);
        
        $data = [
            'pageTitle' => 'Contact Us', 
            'pageSubtitle' => 'Get in touch', 
            'error' => '', 
            'success' => '',
            'userEmail' => $currentUser['email'] // Pass email to view
        ];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $subject = clean($_POST['subject']);
            $message = clean($_POST['message']);
            
            if ($subject && $message) {
                // Send email logic here...
                
                $logModel->log($_SESSION['user_id'], 'Support Request', "Subject: $subject");
                $data['success'] = 'Message sent successfully.';
            } else {
                $data['error'] = 'Please fill in all fields.';
            }
        }
        $this->view('contact_view', $data);
    }
}
?>