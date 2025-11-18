<?php
require_once __DIR__ . '/../core/controller.php';

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
                // --- EMAIL SENDING LOGIC ---
                
                // 1. Get the admin/system email from .env to serve as the recipient
                $adminEmail = getenv('SMTP_USER'); 

                if ($adminEmail) {
                    // 2. Construct the email content
                    $emailSubject = "Support Request: " . $subject;
                    
                    $emailBody = "<h3>New Support Message</h3>";
                    $emailBody .= "<p><strong>From:</strong> " . htmlspecialchars($_SESSION['full_name']) . " (" . htmlspecialchars($currentUser['email']) . ")</p>";
                    $emailBody .= "<p><strong>Faculty ID:</strong> " . htmlspecialchars($currentUser['faculty_id']) . "</p>";
                    $emailBody .= "<hr>";
                    $emailBody .= "<p><strong>Subject:</strong> " . htmlspecialchars($subject) . "</p>";
                    $emailBody .= "<p><strong>Message:</strong><br>" . nl2br(htmlspecialchars($message)) . "</p>";

                    // 3. Send the email using the helper function
                    if (sendEmail($adminEmail, $emailSubject, $emailBody)) {
                        $logModel->log($_SESSION['user_id'], 'Support Request', "Subject: $subject");
                        $data['success'] = 'Your message has been sent successfully. We will contact you shortly.';
                    } else {
                        $data['error'] = 'Failed to send message. Please check your internet connection or try again later.';
                    }
                } else {
                    $data['error'] = 'System configuration error: Admin email not set.';
                }

            } else {
                $data['error'] = 'Please fill in all fields.';
            }
        }
        $this->view('contact_view', $data);
    }
}
?>