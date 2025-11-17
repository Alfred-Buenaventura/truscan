<?php
require_once __DIR__ . '/../core/Controller.php';

class ScheduleController extends Controller {

    public function index() {
        $this->requireLogin();
        
        $scheduleModel = $this->model('Schedule');
        $userModel = $this->model('User');
        $logModel = $this->model('ActivityLog');
        $notifModel = $this->model('Notification');

        $userId = $_SESSION['user_id'];
        $isAdmin = ($_SESSION['role'] === 'Admin');
        $data = [
            'pageTitle' => 'Schedule Management',
            'pageSubtitle' => $isAdmin ? 'Manage class schedules and working hours' : 'Manage your class schedule',
            'isAdmin' => $isAdmin,
            'error' => '',
            'success' => '',
            'activeTab' => 'manage' 
        ];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_POST['add_schedule'])) {
                $this->handleAdd($userId, $isAdmin, $scheduleModel, $logModel, $data);
            }
            if (isset($_POST['edit_schedule'])) {
                $this->handleEdit($userId, $isAdmin, $scheduleModel, $logModel, $data);
            }
            if (isset($_POST['delete_schedule'])) {
                $this->handleDelete($userId, $isAdmin, $scheduleModel, $logModel, $data);
            }
            if (isset($_POST['approve_schedule']) && $isAdmin) {
                $this->handleStatusChange($_POST['schedule_id'], 'approved', $_POST['user_id'], $_POST['subject'], $scheduleModel, $logModel, $notifModel, $data);
            }
            if (isset($_POST['decline_schedule']) && $isAdmin) {
                $this->handleStatusChange($_POST['schedule_id'], 'declined', $_POST['user_id'], $_POST['subject'], $scheduleModel, $logModel, $notifModel, $data);
            }
        }

        $filters = [
            'day_of_week' => $_GET['day_of_week'] ?? '',
            'user_id' => ($isAdmin) ? ($_GET['user_id'] ?? '') : $userId
        ];

        $data['users'] = ($isAdmin) ? $userModel->getAllStaff() : [];
        $data['selectedUserId'] = $filters['user_id'];
        $data['filters'] = $filters;
        $data['pendingSchedules'] = $scheduleModel->getPending($isAdmin ? null : $userId);

        $approvedRaw = $scheduleModel->getApproved($filters, $isAdmin);
        $data['approvedSchedules'] = $approvedRaw; 

        if ($isAdmin && empty($filters['user_id'])) {
            $data['groupedApprovedSchedules'] = $this->groupSchedulesByUser($approvedRaw);
            $data['stats'] = $scheduleModel->getGeneralStats();
        } 
        elseif (!empty($filters['user_id'])) {
            $allSchedules = $scheduleModel->getApproved(['user_id' => $filters['user_id']], $isAdmin);
            $data['userStats'] = $this->calculateUserStats($allSchedules);
            
            if ($isAdmin) {
                $data['selectedUserInfo'] = $userModel->findById($filters['user_id']);
            } else {
                // --- FIX START: Handle missing session keys gracefully ---
                $data['selectedUserInfo'] = [
                    'first_name' => $_SESSION['first_name'] ?? 'User', 
                    'last_name' => $_SESSION['last_name'] ?? '',
                    'faculty_id' => $_SESSION['faculty_id'] ?? ''
                ];
                // --- FIX END ---
            }
        }

        $this->view('schedule_view', $data);
    }

    private function handleAdd($userId, $isAdmin, $model, $logModel, &$data) {
        if ($isAdmin) {
            $data['error'] = 'Admins cannot create schedules.';
            return;
        }
        $days = $_POST['day_of_week'] ?? [];
        $subjects = $_POST['subject'] ?? [];
        $starts = $_POST['start_time'] ?? [];
        $ends = $_POST['end_time'] ?? [];
        $rooms = $_POST['room'] ?? [];
        $count = 0;

        foreach ($days as $i => $day) {
            if (!empty($subjects[$i])) {
                $model->add($userId, $day, clean($subjects[$i]), $starts[$i], $ends[$i], clean($rooms[$i]));
                $count++;
            }
        }
        if ($count > 0) {
            $logModel->log($userId, 'Schedule Submitted', "Submitted $count schedules.");
            $data['success'] = "Submitted $count schedules for approval.";
            $data['activeTab'] = 'pending';
        }
    }

    private function handleEdit($userId, $isAdmin, $model, $logModel, &$data) {
        $id = (int)$_POST['schedule_id'];
        $targetUser = (int)$_POST['user_id_edit'];
        
        if (!$isAdmin && $userId !== $targetUser) {
             $data['error'] = 'Access Denied.'; return;
        }
        
        if ($model->update($id, $targetUser, $_POST['day_of_week'], clean($_POST['subject']), $_POST['start_time'], $_POST['end_time'], clean($_POST['room']), $isAdmin)) {
            $logModel->log($userId, 'Schedule Updated', "ID: $id");
            $data['success'] = 'Schedule updated and re-submitted for approval.';
            $data['activeTab'] = 'pending';
        }
    }

    private function handleDelete($userId, $isAdmin, $model, $logModel, &$data) {
        $id = (int)$_POST['schedule_id_delete'];
        $targetUser = (int)$_POST['user_id_delete'];
        
        if (!$isAdmin && $userId !== $targetUser) {
             $data['error'] = 'Access Denied.'; return;
        }

        if ($model->delete($id, $targetUser, $isAdmin)) {
            $logModel->log($userId, 'Schedule Deleted', "ID: $id");
            $data['success'] = 'Schedule deleted.';
        }
    }

    private function handleStatusChange($id, $status, $targetId, $subject, $model, $logModel, $notifModel, &$data) {
        if ($model->setStatus($id, $status)) {
            $action = ucfirst($status); 
            $logModel->log($_SESSION['user_id'], "Schedule $action", "ID: $id");
            $notifModel->create($targetId, "Your schedule for '$subject' has been $status.", ($status == 'approved' ? 'success' : 'warning'));
            
            $data['success'] = "Schedule $status successfully.";
            $data['activeTab'] = 'pending';
        }
    }

    private function calculateUserStats($schedules) {
        $grouped = [];
        foreach ($schedules as $s) $grouped[$s['day_of_week']][] = $s;

        $totalHours = 0;
        $vacantHours = 0;
        $dutySpan = 0;

        foreach ($grouped as $daySchedules) {
            $dailyScheduled = 0;
            $dailyVacant = 0;
            $firstIn = strtotime($daySchedules[0]['start_time']);
            $lastOut = strtotime($daySchedules[count($daySchedules)-1]['end_time']);

            for ($i = 0; $i < count($daySchedules); $i++) {
                $start = strtotime($daySchedules[$i]['start_time']);
                $end = strtotime($daySchedules[$i]['end_time']);
                $dailyScheduled += ($end - $start);

                if ($i < count($daySchedules) - 1) {
                    $next = strtotime($daySchedules[$i+1]['start_time']);
                    if (($next - $end) > 0) $dailyVacant += ($next - $end);
                }
            }
            $totalHours += ($dailyScheduled / 3600);
            $vacantHours += ($dailyVacant / 3600);
            $dutySpan += (($lastOut - $firstIn) / 3600);
        }
        
        return [
            'total_hours' => $totalHours,
            'vacant_hours' => $vacantHours,
            'duty_span' => $dutySpan
        ];
    }

    private function groupSchedulesByUser($flatSchedules) {
        $grouped = [];
        foreach ($flatSchedules as $sched) {
            $uid = $sched['user_id'];
            if (!isset($grouped[$uid])) {
                $grouped[$uid] = [
                    'user_info' => [
                        'first_name' => $sched['first_name'],
                        'last_name' => $sched['last_name'],
                        'faculty_id' => $sched['faculty_id']
                    ],
                    'schedules' => []
                ];
            }
            $grouped[$uid]['schedules'][] = $sched;
        }

        foreach ($grouped as $uid => &$userData) {
            $userData['stats'] = $this->calculateUserStats($userData['schedules']);
        }
        return $grouped;
    }
}
?>