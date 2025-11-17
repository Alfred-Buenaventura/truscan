<?php
require_once __DIR__ . '/../core/controller.php';
require_once __DIR__ . '/../models/schedule.php';
require_once __DIR__ . '/../models/user.php';

class ScheduleController extends Controller {

    public function index() {
        $this->requireLogin();
        
        $scheduleModel = new Schedule();
        $userModel = new User();
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
        $logModel = $this->model('ActivityLog');

        // --- POST ACTIONS ---
        try {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                if (isset($_POST['add_schedule'])) {
                    $userId = $_POST['user_id'] ?? $_SESSION['user_id'];
                    $schedules = [];
                    for ($i = 0; $i < count($_POST['day_of_week']); $i++) {
                        $schedules[] = [
                            'day' => $_POST['day_of_week'][$i],
                            'subject' => $_POST['subject'][$i],
                            'start' => $_POST['start_time'][$i],
                            'end' => $_POST['end_time'][$i],
                            'room' => $_POST['room'][$i],
                        ];
                    }
                    if ($scheduleModel->create($userId, $schedules, $data['isAdmin'])) {
                        $data['success'] = "Schedule(s) submitted for approval.";
                        if (!$data['isAdmin']) {
                            $logModel->log($userId, 'Schedule Submitted', count($schedules) . ' schedule(s) added');
                        }
                    } else {
                        $data['error'] = "Failed to add schedules.";
                    }
                }
                
                // --- NEW EDIT LOGIC ---
                if (isset($_POST['edit_schedule'])) {
                    $this->requireAdmin(); // Only admins can edit
                    $scheduleId = $_POST['schedule_id_edit'];
                    $userId = $_POST['user_id_edit'];
                    $day = $_POST['day_of_week_edit'];
                    $subject = $_POST['subject_edit'];
                    $start = $_POST['start_time_edit'];
                    $end = $_POST['end_time_edit'];
                    $room = $_POST['room_edit'];

                    if ($scheduleModel->update($scheduleId, $day, $subject, $start, $end, $room)) {
                        $data['success'] = "Schedule updated successfully.";
                        $logModel->log($adminId, 'Schedule Edited', "Edited schedule ID $scheduleId for user $userId");
                    } else {
                        $data['error'] = "Failed to update schedule.";
                    }
                }
                // --- END NEW EDIT LOGIC ---

                if (isset($_POST['delete_schedule'])) {
                    $scheduleId = $_POST['schedule_id_delete'];
                    $userId = $_POST['user_id_delete'];
                    if ($scheduleModel->delete($scheduleId, $userId, $data['isAdmin'])) {
                        $data['success'] = "Schedule deleted.";
                        $logModel->log($adminId, 'Schedule Deleted', "Deleted schedule ID $scheduleId for user $userId");
                    } else {
                        $data['error'] = "Failed to delete schedule.";
                    }
                }
                if (isset($_POST['approve_schedule'])) {
                    $this->requireAdmin();
                    $scheduleId = $_POST['schedule_id'];
                    $userId = $_POST['user_id'];
                    if ($scheduleModel->updateStatus($scheduleId, 'approved')) {
                        $data['success'] = "Schedule approved.";
                        $logModel->log($adminId, 'Schedule Approved', "Approved schedule ID $scheduleId for user $userId");
                    } else {
                        $data['error'] = "Failed to approve schedule.";
                    }
                }
                if (isset($_POST['decline_schedule'])) {
                    $this->requireAdmin();
                    $scheduleId = $_POST['schedule_id'];
                    $userId = $_POST['user_id'];
                    if ($scheduleModel->delete($scheduleId, $userId, true)) {
                        $data['success'] = "Schedule declined and removed.";
                        $logModel->log($adminId, 'Schedule Declined', "Declined schedule ID $scheduleId for user $userId");
                    } else {
                        $data['error'] = "Failed to decline schedule.";
                    }
                }
            }
        } catch (Exception $e) {
            $data['error'] = $e->getMessage();
        }

        // --- GET DATA FOR VIEW ---
        if ($data['isAdmin']) {
            $data['users'] = $userModel->getAllStaff();
            $data['pendingSchedules'] = $scheduleModel->getAllByStatus('pending');
            $data['stats'] = $scheduleModel->getAdminStats();
            
            if (!empty($data['selectedUserId'])) {
                // Admin is filtering by a specific user
                $data['approvedSchedules'] = $scheduleModel->getByUser($data['selectedUserId'], 'approved');
                $data['selectedUserInfo'] = $userModel->findById($data['selectedUserId']);
                $data['userStats'] = $scheduleModel->getUserStats($data['selectedUserId']);
            } else {
                // Admin is viewing all users (Accordion view)
                $data['groupedApprovedSchedules'] = $scheduleModel->getAllApprovedGroupedByUser();
            }
        } else {
            // Non-admin view
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