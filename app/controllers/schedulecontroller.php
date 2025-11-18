<?php
require_once __DIR__ . '/../core/controller.php';
require_once __DIR__ . '/../models/schedule.php';
require_once __DIR__ . '/../models/user.php';

class ScheduleController extends Controller {

    public function index() {
        $this->requireLogin();
        
        $scheduleModel = new Schedule();
        $userModel = new User();
        $notifModel = $this->model('Notification');
        $logModel = $this->model('ActivityLog');

        $data = [
            'isAdmin' => Helper::isAdmin(),
            'error' => '',
            'success' => '',
            'activeTab' => $_GET['tab'] ?? 'manage',
            'filters' => ['user_id' => $_GET['user_id'] ?? null],
            'users' => [],
            'approvedSchedules' => [],
            'groupedApprovedSchedules' => [],
            'pendingSchedules' => [],
            'stats' => [],
            'selectedUserId' => $_GET['user_id'] ?? null,
            'selectedUserInfo' => null,
            'userStats' => null
        ];

        $adminId = $_SESSION['user_id'];

        try {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                
                // --- APPROVAL / DECLINE ACTIONS ---
                if (isset($_POST['approve_schedule']) || isset($_POST['decline_schedule']) || isset($_POST['bulk_action_type'])) {
                    $this->requireAdmin();
                    
                    $itemsToProcess = [];
                    $action = ''; 

                    // Determine if Bulk or Single
                    if (isset($_POST['bulk_action_type'])) {
                        $action = $_POST['bulk_action_type']; // 'approve' or 'decline'
                        $itemsToProcess = $_POST['selected_schedules'] ?? [];
                    } else {
                        $action = isset($_POST['approve_schedule']) ? 'approve' : 'decline';
                        $itemsToProcess[] = $_POST['schedule_id'];
                    }

                    if (empty($itemsToProcess)) {
                        $data['error'] = "No schedules selected.";
                    } else {
                        $processedCount = 0;
                        $usersToNotify = []; // Structure: [user_id => [day1, day2]]

                        foreach ($itemsToProcess as $schedId) {
                            // 1. FETCH DETAILS BEFORE ACTION
                            $schedInfo = $scheduleModel->findById($schedId);
                            
                            if ($schedInfo) {
                                $success = false;
                                if ($action === 'approve') {
                                    $success = $scheduleModel->updateStatus($schedId, 'approved');
                                } else {
                                    $success = $scheduleModel->delete($schedId, $schedInfo['user_id'], true);
                                }

                                if ($success) {
                                    $processedCount++;
                                    // Store day for notification (e.g., "Monday")
                                    $usersToNotify[$schedInfo['user_id']][] = $schedInfo['day_of_week'];
                                }
                            }
                        }

                        // 2. SEND EMAILS & NOTIFICATIONS
                        foreach ($usersToNotify as $uId => $days) {
                            $user = $userModel->findById($uId);
                            if (!$user) continue;

                            // Create a nice list: "Monday, Wednesday"
                            $uniqueDays = array_unique($days);
                            $daysString = implode(', ', $uniqueDays);

                            // Dashboard Notification
                            $notifMsg = ($action === 'approve') 
                                ? "Your Schedule for $daysString has been approved." 
                                : "Your Schedule for $daysString has been declined.";
                            $notifType = ($action === 'approve') ? 'success' : 'error';
                            $notifModel->create($uId, $notifMsg, $notifType);

                            // Email Notification
                            if (!empty($user['email'])) {
                                if ($action === 'approve') {
                                    $emailSubject = "Schedule Approved";
                                    $emailBody = "Dear " . htmlspecialchars($user['first_name']) . ",<br><br>";
                                    $emailBody .= "Your Schedule for <strong>" . htmlspecialchars($daysString) . "</strong> has been approved, please check your account.<br><br>";
                                    $emailBody .= "Best Regards,<br>BPC Admin";
                                } else {
                                    $emailSubject = "Schedule Declined";
                                    $emailBody = "Dear " . htmlspecialchars($user['first_name']) . ",<br><br>";
                                    $emailBody .= "Your Schedule for <strong>" . htmlspecialchars($daysString) . "</strong> has been declined.<br><br>";
                                    $emailBody .= "Best Regards,<br>BPC Admin";
                                }

                                // Send
                                sendEmail($user['email'], $emailSubject, $emailBody);
                            }
                        }

                        if ($processedCount > 0) {
                            $data['success'] = ucfirst($action) . "d $processedCount schedule(s) successfully.";
                            $data['activeTab'] = 'pending';
                            $logModel->log($adminId, "Schedule " . ucfirst($action), "Processed $processedCount items");
                        }
                    }
                }

                // ... (Keep Add/Edit/Delete Logic same as before) ...
                // (I'm keeping this block concise, assume Add/Edit/Delete are here as provided previously)
                elseif (isset($_POST['add_schedule'])) {
                    // ... Add Logic ...
                    $userId = $_POST['user_id'] ?? $_SESSION['user_id'];
                    $schedules = [];
                    for ($i = 0; $i < count($_POST['day_of_week']); $i++) {
                        $schedules[] = [ 'day' => $_POST['day_of_week'][$i], 'subject' => $_POST['subject'][$i], 'start' => $_POST['start_time'][$i], 'end' => $_POST['end_time'][$i], 'room' => $_POST['room'][$i] ];
                    }
                    if ($scheduleModel->create($userId, $schedules, $data['isAdmin'])) {
                        $data['success'] = "Schedule(s) added.";
                    }
                }
                elseif (isset($_POST['delete_schedule'])) {
                    if ($scheduleModel->delete($_POST['schedule_id_delete'], $_POST['user_id_delete'], $data['isAdmin'])) {
                        $data['success'] = "Schedule deleted.";
                    }
                }
                elseif (isset($_POST['edit_schedule'])) {
                    $this->requireAdmin();
                    if ($scheduleModel->update($_POST['schedule_id_edit'], $_POST['day_of_week_edit'], $_POST['subject_edit'], $_POST['start_time_edit'], $_POST['end_time_edit'], $_POST['room_edit'])) {
                        $data['success'] = "Schedule updated.";
                    }
                }
            }
        } catch (Exception $e) {
            $data['error'] = 'System error: ' . $e->getMessage();
        }

        // --- LOAD VIEW DATA (Unchanged) ---
        if ($data['isAdmin']) {
            $data['users'] = $userModel->getAllStaff();
            $data['pendingSchedules'] = $scheduleModel->getAllByStatus('pending');
            $data['stats'] = $scheduleModel->getAdminStats();
            
            if (!empty($data['selectedUserId'])) {
                $data['approvedSchedules'] = $scheduleModel->getByUser($data['selectedUserId'], 'approved');
                $data['selectedUserInfo'] = $userModel->findById($data['selectedUserId']);
                $data['userStats'] = $scheduleModel->getUserStats($data['selectedUserId']);
            } else {
                $data['groupedApprovedSchedules'] = $scheduleModel->getAllApprovedGroupedByUser();
            }
        } else {
            $userId = $_SESSION['user_id'];
            $data['approvedSchedules'] = $scheduleModel->getByUser($userId, 'approved');
            $data['pendingSchedules'] = $scheduleModel->getByUser($userId, 'pending');
            $data['userStats'] = $scheduleModel->getUserStats($userId);
            $data['selectedUserInfo'] = $userModel->findById($userId);
        }

        $this->view('schedule_view', $data);
    }
}
?>