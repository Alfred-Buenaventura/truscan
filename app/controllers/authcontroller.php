<?php
require_once __DIR__ . '/../core/Controller.php';

class AuthController extends Controller {

    public function login() {
        $data = ['error' => ''];
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
            $username = trim($_POST['username']);
            $password = $_POST['password'];

            if (!empty($username) && !empty($password)) {
                $userModel = $this->model('User'); 
                $user = $userModel->findUserByUsername($username);

                if ($user && password_verify($password, $user['password'])) {
                    // --- FIX START: Set all necessary session variables ---
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['faculty_id'] = $user['faculty_id'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['first_name'] = $user['first_name']; // Added
                    $_SESSION['last_name'] = $user['last_name'];   // Added
                    $_SESSION['full_name'] = $user['first_name'] . ' ' . $user['last_name'];
                    $_SESSION['force_password_change'] = (int)$user['force_password_change'];
                    // --- FIX END ---

                    $logModel = $this->model('ActivityLog');
                    $logModel->log($user['id'], 'Login', 'User logged in successfully');

                    if ($_SESSION['force_password_change']) {
                        header('Location: change_password.php');
                    } else {
                        header('Location: index.php');
                    }
                    exit;
                } else {
                    $data['error'] = 'Invalid username or password.';
                }
            } else {
                $data['error'] = 'Please enter both username and password.';
            }
        }
        
        $this->view('login_view', $data);
    }
    
    public function logout() {
        if (isset($_SESSION['user_id'])) {
            $logModel = $this->model('ActivityLog');
            $logModel->log($_SESSION['user_id'], 'Logout', 'User logged out');
        }
        session_unset();
        session_destroy();
        header('Location: login.php');
        exit;
    }

    public function changePassword() {
        $this->requireLogin();
        $userModel = $this->model('User');
        $logModel = $this->model('ActivityLog');
        
        $data = [
            'error' => '', 
            'success' => '', 
            'pageTitle' => 'Change Password',
            'firstLogin' => ($_SESSION['force_password_change'] ?? 0)
        ];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $current = $_POST['current_password'];
            $new = $_POST['new_password'];
            $confirm = $_POST['confirm_password'];
            
            $user = $userModel->findById($_SESSION['user_id']);

            if (!password_verify($current, $user['password'])) {
                $data['error'] = 'Current password is incorrect.';
            } elseif ($new !== $confirm) {
                $data['error'] = 'New passwords do not match.';
            } elseif (strlen($new) < 8) {
                $data['error'] = 'Password must be at least 8 characters.';
            } else {
                $hashed = password_hash($new, PASSWORD_DEFAULT);
                $db = new Database();
                $db->query("UPDATE users SET password=?, force_password_change=0 WHERE id=?", [$hashed, $_SESSION['user_id']], "si");
                
                $_SESSION['force_password_change'] = 0;
                $logModel->log($_SESSION['user_id'], 'Password Changed', 'User changed password');
                
                if ($data['firstLogin']) {
                    header('Location: index.php'); exit;
                }
                $data['success'] = 'Password changed successfully!';
            }
        }
        $this->view('change_password_view', $data);
    }

    public function forgotPassword() {
        if (isset($_SESSION['user_id'])) {
            header('Location: index.php');
            exit;
        }

        $data = ['step' => 1, 'error' => '', 'success' => ''];
        $OTP_VALIDITY_SECONDS = 300;

        if (isset($_SESSION['reset_user_id']) && isset($_SESSION['reset_time'])) {
            if ((time() - $_SESSION['reset_time']) >= $OTP_VALIDITY_SECONDS) {
                unset($_SESSION['reset_otp'], $_SESSION['reset_user_id'], $_SESSION['reset_time'], $_SESSION['reset_otp_verified']);
                $data['error'] = 'Session expired. Please try again.';
                $data['step'] = 1;
            } else {
                $data['step'] = (isset($_SESSION['reset_otp_verified']) && $_SESSION['reset_otp_verified']) ? 3 : 2;
            }
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_POST['send_otp'])) {
                $email = clean($_POST['email']);
                $db = new Database();
                $res = $db->query("SELECT * FROM users WHERE email = ? AND status = 'active'", [$email], "s");
                $user = $res->get_result()->fetch_assoc();

                if ($user) {
                    $otp = strtoupper(substr(md5(time()), 0, 6));
                    $body = "Your OTP is: <strong>$otp</strong>. Valid for 5 mins.";
                    
                    if (sendEmail($email, 'Password Reset', $body)) {
                        $_SESSION['reset_user_id'] = $user['id'];
                        $_SESSION['reset_otp'] = $otp;
                        $_SESSION['reset_time'] = time();
                        $_SESSION['reset_otp_verified'] = false;
                        $data['success'] = "OTP sent to your email.";
                        $data['step'] = 2;
                    } else {
                        $data['error'] = "Could not send email.";
                    }
                } else {
                    $data['error'] = "Email not found.";
                }
            }

            if (isset($_POST['verify_otp'])) {
                $otp = strtoupper(clean($_POST['otp']));
                if ($otp === ($_SESSION['reset_otp'] ?? '')) {
                    $_SESSION['reset_otp_verified'] = true;
                    $data['success'] = "OTP Verified.";
                    $data['step'] = 3;
                } else {
                    $data['error'] = "Invalid OTP.";
                    $data['step'] = 2;
                }
            }

            if (isset($_POST['reset_password'])) {
                $new = $_POST['new_password'];
                $confirm = $_POST['confirm_password'];

                if ($new !== $confirm) {
                    $data['error'] = "Passwords do not match.";
                    $data['step'] = 3;
                } elseif (strlen($new) < 8) {
                    $data['error'] = "Password too short.";
                    $data['step'] = 3;
                } else {
                    $hashed = password_hash($new, PASSWORD_DEFAULT);
                    $uid = $_SESSION['reset_user_id'];
                    
                    $db = new Database();
                    $db->query("UPDATE users SET password = ? WHERE id = ?", [$hashed, $uid], "si");
                    
                    unset($_SESSION['reset_otp'], $_SESSION['reset_user_id'], $_SESSION['reset_time'], $_SESSION['reset_otp_verified']);
                    
                    $data['success'] = "Password reset successfully.";
                    $data['step'] = 4;
                }
            }
        }

        if (isset($_GET['action']) && $_GET['action'] === 'backtologin') {
             unset($_SESSION['reset_otp'], $_SESSION['reset_user_id'], $_SESSION['reset_time'], $_SESSION['reset_otp_verified']);
             header('Location: login.php'); exit;
        }

        $this->view('forgot_password_view', $data);
    }
}
?>