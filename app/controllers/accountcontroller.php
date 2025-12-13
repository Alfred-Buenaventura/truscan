<?php
require_once __DIR__ . '/../core/controller.php';

class AccountController extends Controller {

    public function index() {
        $this->requireAdmin(); // Security Check

        $userModel = $this->model('User');
        $logModel = $this->model('ActivityLog');
        $notifModel = $this->model('Notification');
        
        // Initialize View Data
        $data = [
            'pageTitle' => 'Create New Account',
            'pageSubtitle' => 'Create user accounts individually or import in bulk via CSV',
            'activeTab' => $_GET['tab'] ?? 'csv',
            'flashMessage' => $_SESSION['flash_message'] ?? null,
            'flashType' => $_SESSION['flash_type'] ?? null
        ];
        // Clear flash session
        unset($_SESSION['flash_message'], $_SESSION['flash_type']);

        // --- HANDLE POST REQUESTS ---
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            
            // 1. Handle CSV Import
            if (isset($_FILES['csvFile'])) {
                $this->handleCsvImport($_FILES['csvFile'], $userModel, $logModel, $notifModel);
            }
            
            // 2. Handle Single User Creation
            if (isset($_POST['create_user'])) {
                $this->handleCreateUser($_POST, $userModel, $logModel, $notifModel);
            }

            // 3. Handle User Edits
            if (isset($_POST['edit_user'])) {
                $userModel->update($_POST['user_id'], clean($_POST['first_name']), clean($_POST['last_name']), clean($_POST['middle_name']), clean($_POST['email']), clean($_POST['phone']));
                $logModel->log($_SESSION['user_id'], 'User Updated', "Updated user ID: " . $_POST['user_id']);
                $this->setFlash('User information updated successfully!', 'success', 'view');
            }

            // 4. Handle Archive/Restore/Delete
            if (isset($_POST['archive_user'])) {
                $userModel->setStatus($_POST['user_id'], 'archived');
                $logModel->log($_SESSION['user_id'], 'User Archived', "Archived user ID: " . $_POST['user_id']);
                $this->setFlash('User archived successfully!', 'success', 'view');
            }
            if (isset($_POST['restore_user'])) {
                $userModel->setStatus($_POST['user_id'], 'active');
                $logModel->log($_SESSION['user_id'], 'User Restored', "Restored user ID: " . $_POST['user_id']);
                $this->setFlash('User restored successfully!', 'success', 'view');
            }
            if (isset($_POST['delete_user'])) {
                $userModel->delete($_POST['user_id']);
                $logModel->log($_SESSION['user_id'], 'User Deleted', "Permanently deleted user ID: " . $_POST['user_id']);
                $this->setFlash('User permanently deleted!', 'success', 'view');
            }
        }

        // --- PREPARE DATA FOR VIEW ---
        $data['stats'] = $userModel->getStats();
        $data['activeUsers'] = $userModel->getAllActive();
        $data['archivedUsers'] = $userModel->getAllArchived();

        $this->view('account_view', $data);
    }

    // --- HELPER METHODS ---

    private function handleCreateUser($post, $userModel, $logModel, $notifModel) {
        try {
            $facultyId = clean($post['faculty_id']);
            
            if ($post['role'] === 'Admin') {
                $this->setFlash('Admin accounts must be created from the Admin Management page.', 'error', 'create');
                return;
            }

            if ($userModel->exists($facultyId)) {
                $this->setFlash("An account with this Faculty ID ($facultyId) already exists.", 'duplicate', 'create');
                return;
            }

            $userData = [
                'faculty_id' => $facultyId,
                'username' => strtolower($facultyId),
                'password' => password_hash('DefaultPass123!', PASSWORD_DEFAULT),
                'first_name' => clean($post['first_name']),
                'last_name' => clean($post['last_name']),
                'middle_name' => clean($post['middle_name']),
                'email' => clean($post['email']),
                'phone' => clean($post['phone']),
                'role' => clean($post['role'])
            ];

            $newId = $userModel->create($userData);
            
            if ($newId) {
            $logModel->log($_SESSION['user_id'], 'User Created', "Created user: $facultyId");
            $notifModel->create($newId, "Welcome! Your account has been created.", 'success');
    
            $subject = "Welcome to Bulacan Polytechnic College - Account Credentials";
                $msg = "
                <html>
                <body style='font-family: Arial, sans-serif; color: #333;'>
                    <div style='padding: 20px; border: 1px solid #ddd; border-radius: 5px; max-width: 600px;'>
                        <h2 style='color: #047857;'>Welcome to Bulacan Polytechnic College</h2>
                        <p>Dear " . $userData['first_name'] . ",</p>
                        <p>Your account for the <strong>Attendance Monitoring System</strong> has been successfully created.</p>
                        
                        <div style='background: #f9fafb; padding: 15px; border-radius: 5px; margin: 20px 0;'>
                            <p style='margin: 5px 0;'><strong>Username:</strong> " . $userData['username'] . "</p>
                            <p style='margin: 5px 0;'><strong>Temporary Password:</strong> DefaultPass123!</p>
                        </div>
                        
                        <p>For security purposes, please log in and change your password immediately.</p>
                        
                        <hr style='border: 0; border-top: 1px solid #eee; margin: 20px 0;'>
                        <p style='font-size: 12px; color: #777;'>This is an automated message. Please do not reply.</p>
                    </div>
                </body>
                </html>
                ";
    
            sendEmail($userData['email'], "BPC Attendance System Account Successfully Created!", $msg);
            $this->setFlash("User Account for {$userData['first_name']} created!", 'success', 'create');
            }

        } catch (Exception $e) {
            $this->setFlash('Error creating user: ' . $e->getMessage(), 'error', 'create');
        }
    }

    private function handleCsvImport($file, $userModel, $logModel, $notifModel) {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $this->setFlash('Error uploading file.', 'error', 'csv');
            return;
        }

        $handle = fopen($file['tmp_name'], 'r');
        fgetcsv($handle); // Skip header
        
        $imported = 0; 
        $skipped = 0;

        while (($data = fgetcsv($handle)) !== false) {
            // Adjusted count check since we removed 1 column
            if (count($data) < 7) continue; 
            
            $facultyId = clean($data[0]);
            
            // CSV Structure:
            // 0: Faculty ID, 1: Last, 2: First, 3: Middle, 4: Role, 5: Email, 6: Phone
            
            // Logic: Username is same as Faculty ID
            $username = $facultyId;

            if ($userModel->exists($facultyId) || strtolower(clean($data[4])) === 'admin') {
                $skipped++;
                continue;
            }

            $userData = [
                'faculty_id' => $facultyId,
                'last_name' => clean($data[1]),
                'first_name' => clean($data[2]),
                'middle_name' => clean($data[3]),
                'username' => $username, // Auto-set
                'role' => clean($data[4]), // Shifted index
                'email' => clean($data[5]), // Shifted index
                'phone' => $data[6] ?? '',  // Shifted index
                'password' => password_hash('DefaultPass123!', PASSWORD_DEFAULT)
            ];

            $newId = $userModel->create($userData);
            if ($newId) {
                $imported++;
                $notifModel->create($newId, "Welcome! Please change your password.", 'success');
                // Updated Email to not show random username but ID
                sendEmail($userData['email'], "Your BPC Account", "Welcome! Credentials: {$userData['username']} / DefaultPass123!");
            }
        }
        fclose($handle);

        $logModel->log($_SESSION['user_id'], 'CSV Import', "Imported $imported users.");
        $this->setFlash("Successfully imported $imported users. Skipped $skipped.", 'success', 'csv');
    }

    private function setFlash($message, $type, $tab) {
        $_SESSION['flash_message'] = $message;
        $_SESSION['flash_type'] = $type;
        header("Location: create_account.php?tab=$tab");
        exit;
    }

    public function downloadTemplate() {
        $this->requireAdmin();

        $csv = "Faculty ID,Last Name,First Name,Middle Name,Username,Role,Email,Phone Number\n";
        $csv .= "FAC001,Dela Cruz,Juan,P.,jdelacruz,Full Time Teacher,juan.delacruz@bpc.edu.ph,09123456789\n";
        $csv .= "FAC002,Santos,Maria,R.,msantos,Registrar,maria.santos@bpc.edu.ph,09198765432\n";
        $csv .= "STAFF001,Garcia,Ana,L.,agarcia,Guidance Office,ana.garcia@bpc.edu.ph,09171234567\n";
        $csv .= "STAFF002,Reyes,Pedro,M.,preyes,Finance,pedro.reyes@bpc.edu.ph,09221234567\n";

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="bpc_user_import_template.csv"');
        header('Pragma: no-cache');
        header('Expires: 0');

        echo $csv;
        exit;
    }
}
?>