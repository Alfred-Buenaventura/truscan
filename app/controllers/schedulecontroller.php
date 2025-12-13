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
            'searchQuery' => $_GET['search'] ?? '', // <--- ADD THIS LINE
            'filters' => ['user_id' => $_GET['user_id'] ?? null],
            'users' => [],
            'approvedSchedules' => [],
            'groupedApprovedSchedules' => [],
            'groupedPendingSchedules' => [], 
            'pendingSchedules' => [],
            'pendingCount' => 0,
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

                    if (isset($_POST['bulk_action_type'])) {
                        $action = $_POST['bulk_action_type'];
                        $itemsToProcess = $_POST['selected_schedules'] ?? [];
                    } else {
                        $action = isset($_POST['approve_schedule']) ? 'approve' : 'decline';
                        $itemsToProcess[] = $_POST['schedule_id'];
                    }

                    if (empty($itemsToProcess)) {
                        $data['error'] = "No schedules selected.";
                    } else {
                        $processedCount = 0;
                        $usersToNotify = [];

                        foreach ($itemsToProcess as $schedId) {
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
                                    if (!isset($usersToNotify[$schedInfo['user_id']])) {
                                        $usersToNotify[$schedInfo['user_id']] = ['days' => [], 'schedules' => []];
                                    }
                                    $usersToNotify[$schedInfo['user_id']]['days'][] = $schedInfo['day_of_week'];
                                    $usersToNotify[$schedInfo['user_id']]['schedules'][] = [
                                        'day' => $schedInfo['day_of_week'],
                                        'subject' => $schedInfo['subject'],
                                        'start_time' => $schedInfo['start_time'],
                                        'end_time' => $schedInfo['end_time'],
                                        'room' => $schedInfo['room']
                                    ];
                                }
                            }
                        }

                        // 2. SEND EMAILS & NOTIFICATIONS
                        foreach ($usersToNotify as $uId => $userData) {
                            $user = $userModel->findById($uId);
                            if (!$user) continue;

                            // Create a nice list: "Monday, Wednesday"
                            $uniqueDays = array_unique($userData['days']);
                            $daysString = implode(', ', $uniqueDays);

                            // Dashboard Notification
                            $notifMsg = ($action === 'approve') 
                                ? "Your schedule for $daysString has been approved." 
                                : "Your schedule for $daysString has been declined.";
                            $notifType = ($action === 'approve') ? 'success' : 'error';
                            
                            // Create notification
                            Notification::create($uId, $notifMsg, $notifType);

                            // EMAIL NOTIFICATION
                            if (!empty($user['email'])) {
                                if ($action === 'approve') {
                                    $emailSent = $this->sendApprovalEmail($user, $userData['schedules']);
                                    
                                    if (!$emailSent) {
                                        error_log("Failed to send approval email to: " . $user['email']);
                                    }
                                } else {
                                    $emailSent = $this->sendDeclineEmail($user, $daysString);
                                    
                                    if (!$emailSent) {
                                        error_log("Failed to send decline email to: " . $user['email']);
                                    }
                                }
                            }
                        }

                        if ($processedCount > 0) {
                            $data['success'] = ucfirst($action) . "d $processedCount schedule(s) successfully.";
                            $data['activeTab'] = 'pending';
                            $logModel->log($adminId, "Schedule " . ucfirst($action), "Processed $processedCount items");
                        }
                    }
                }

                // --- ADD SCHEDULE ---
                elseif (isset($_POST['add_schedule'])) {
                     $userId = $_POST['user_id'] ?? $_SESSION['user_id'];
                    $schedules = [];
                    for ($i = 0; $i < count($_POST['day_of_week']); $i++) {
                        $schedules[] = [ 
                            'day' => $_POST['day_of_week'][$i], 
                            'subject' => $_POST['subject'][$i], 
                            'start' => $_POST['start_time'][$i], 
                            'end' => $_POST['end_time'][$i], 
                            'room' => $_POST['room'][$i] 
                        ];
                    }
                    if ($scheduleModel->create($userId, $schedules, $data['isAdmin'])) {
                        $data['success'] = "Schedule(s) added successfully.";
                        if (!$data['isAdmin']) { $this->notifyAdminsOfPendingSchedule($userId, $schedules); }
                    } else { $data['error'] = "Failed to add schedule(s)."; }
                }
                // --- DELETE SCHEDULE ---
                elseif (isset($_POST['delete_schedule'])) {
                     if ($scheduleModel->delete($_POST['schedule_id_delete'], $_POST['user_id_delete'], $data['isAdmin'])) {
                        $data['success'] = "Schedule deleted successfully.";
                    } else { $data['error'] = "Failed to delete schedule."; }
                }
                // --- EDIT SCHEDULE ---
                elseif (isset($_POST['edit_schedule'])) {
                    $this->requireAdmin();
                    if ($scheduleModel->update($_POST['schedule_id_edit'], $_POST['day_of_week_edit'], $_POST['subject_edit'], $_POST['start_time_edit'], $_POST['end_time_edit'], $_POST['room_edit'])) {
                        $data['success'] = "Schedule updated successfully.";
                    } else { $data['error'] = "Failed to update schedule."; }
                }
            }
        } catch (Exception $e) {
            $data['error'] = 'System error: ' . $e->getMessage();
        }

        if ($data['isAdmin']) {
            $data['stats'] = $scheduleModel->getAdminStats();
            
            // Get raw count for badge before filtering
            $allPending = $scheduleModel->getAllByStatus('pending');
            $data['pendingCount'] = count($allPending);

            // Fetch Grouped Data with Search Query
            $data['groupedApprovedSchedules'] = $scheduleModel->getGroupedSchedulesByStatus('approved', $data['searchQuery']);
            $data['groupedPendingSchedules'] = $scheduleModel->getGroupedSchedulesByStatus('pending', $data['searchQuery']);
            
        } else {
            // User View (Unchanged)
            $userId = $_SESSION['user_id'];
            $data['approvedSchedules'] = $scheduleModel->getByUser($userId, 'approved');
            $pendingRaw = $scheduleModel->getByUser($userId, 'pending');
            $data['pendingCount'] = count($pendingRaw);
            // Transform user pending to grouped format for consistency in view if desired, 
            // but standard table is usually fine for single user. 
            // We'll keep standard table for single user to save space.
            $data['pendingSchedules'] = $pendingRaw; 
            
            $data['userStats'] = $scheduleModel->getUserStats($userId);
            $data['selectedUserInfo'] = $userModel->findById($userId);
        }

        $this->view('schedule_view', $data);
    }

    /**
     * Send approval email with schedule details
     * 
     * @param array $user User data array
     * @param array $schedules Array of approved schedules
     * @return bool True if email sent successfully
     */
    private function sendApprovalEmail($user, $schedules) {
        $firstName = htmlspecialchars($user['first_name']);
        $emailSubject = "Schedule Approved - BPC Attendance System";
        
        // Build HTML email body
        $emailBody = "<!DOCTYPE html>";
        $emailBody .= "<html><head><style>";
        $emailBody .= "body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }";
        $emailBody .= ".container { max-width: 600px; margin: 0 auto; padding: 20px; }";
        $emailBody .= ".header { background: #059669; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }";
        $emailBody .= ".content { background: #f9fafb; padding: 30px; border: 1px solid #e5e7eb; }";
        $emailBody .= ".schedule-table { width: 100%; border-collapse: collapse; margin: 20px 0; background: white; }";
        $emailBody .= ".schedule-table th { background: #ecfdf5; color: #065f46; padding: 12px; text-align: left; border-bottom: 2px solid #059669; }";
        $emailBody .= ".schedule-table td { padding: 10px 12px; border-bottom: 1px solid #e5e7eb; }";
        $emailBody .= ".footer { background: #f3f4f6; padding: 20px; text-align: center; font-size: 14px; color: #6b7280; border-radius: 0 0 8px 8px; }";
        $emailBody .= ".success-badge { display: inline-block; background: #10b981; color: white; padding: 8px 16px; border-radius: 20px; font-weight: bold; margin: 10px 0; }";
        $emailBody .= "</style></head><body>";
        
        $emailBody .= "<div class='container'>";
        $emailBody .= "<div class='header'>";
        $emailBody .= "<h1 style='margin: 0;'>✓ Schedule Approved</h1>";
        $emailBody .= "</div>";
        
        $emailBody .= "<div class='content'>";
        $emailBody .= "<p>Dear <strong>{$firstName}</strong>,</p>";
        $emailBody .= "<p>Great news! Your class schedule has been <span class='success-badge'>APPROVED</span></p>";
        $emailBody .= "<p>Below are the details of your approved schedule:</p>";
        
        // Schedule Table
        $emailBody .= "<table class='schedule-table'>";
        $emailBody .= "<thead><tr>";
        $emailBody .= "<th>Day</th>";
        $emailBody .= "<th>Subject</th>";
        $emailBody .= "<th>Time</th>";
        $emailBody .= "<th>Room</th>";
        $emailBody .= "</tr></thead>";
        $emailBody .= "<tbody>";
        
        // Sort schedules by day
        $dayOrder = ['Monday'=>1, 'Tuesday'=>2, 'Wednesday'=>3, 'Thursday'=>4, 'Friday'=>5, 'Saturday'=>6];
        usort($schedules, function($a, $b) use ($dayOrder) {
            return ($dayOrder[$a['day']] ?? 7) - ($dayOrder[$b['day']] ?? 7);
        });
        
        foreach ($schedules as $schedule) {
            $day = htmlspecialchars($schedule['day']);
            $subject = htmlspecialchars($schedule['subject']);
            $startTime = date('g:i A', strtotime($schedule['start_time']));
            $endTime = date('g:i A', strtotime($schedule['end_time']));
            $room = htmlspecialchars($schedule['room']);
            
            $emailBody .= "<tr>";
            $emailBody .= "<td><strong>{$day}</strong></td>";
            $emailBody .= "<td>{$subject}</td>";
            $emailBody .= "<td>{$startTime} - {$endTime}</td>";
            $emailBody .= "<td>{$room}</td>";
            $emailBody .= "</tr>";
        }
        
        $emailBody .= "</tbody></table>";
        
        $emailBody .= "<p style='margin-top: 20px;'>Your schedule is now active and will be used for attendance tracking.</p>";
        $emailBody .= "<p>If you have any questions or need to make changes, please contact the administration office.</p>";
        $emailBody .= "</div>";
        
        $emailBody .= "<div class='footer'>";
        $emailBody .= "<p><strong>Bulacan Polytechnic College</strong><br>";
        $emailBody .= "Attendance Monitoring System</p>";
        $emailBody .= "<p style='font-size: 12px; color: #9ca3af;'>This is an automated message. Please do not reply to this email.</p>";
        $emailBody .= "</div>";
        $emailBody .= "</div>";
        
        $emailBody .= "</body></html>";
        
        // Send email using the global helper function
        return sendEmail($user['email'], $emailSubject, $emailBody);
    }

    /**
     * Send decline email notification
     * 
     * @param array $user User data array
     * @param string $daysString Comma-separated list of days
     * @return bool True if email sent successfully
     */
    private function sendDeclineEmail($user, $daysString) {
        $firstName = htmlspecialchars($user['first_name']);
        $emailSubject = "Schedule Declined - BPC Attendance System";
        
        $emailBody = "<!DOCTYPE html>";
        $emailBody .= "<html><head><style>";
        $emailBody .= "body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }";
        $emailBody .= ".container { max-width: 600px; margin: 0 auto; padding: 20px; }";
        $emailBody .= ".header { background: #dc2626; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }";
        $emailBody .= ".content { background: #f9fafb; padding: 30px; border: 1px solid #e5e7eb; }";
        $emailBody .= ".footer { background: #f3f4f6; padding: 20px; text-align: center; font-size: 14px; color: #6b7280; border-radius: 0 0 8px 8px; }";
        $emailBody .= ".warning-box { background: #fef2f2; border-left: 4px solid #dc2626; padding: 15px; margin: 20px 0; }";
        $emailBody .= "</style></head><body>";
        
        $emailBody .= "<div class='container'>";
        $emailBody .= "<div class='header'>";
        $emailBody .= "<h1 style='margin: 0;'>⚠ Schedule Declined</h1>";
        $emailBody .= "</div>";
        
        $emailBody .= "<div class='content'>";
        $emailBody .= "<p>Dear <strong>{$firstName}</strong>,</p>";
        $emailBody .= "<div class='warning-box'>";
        $emailBody .= "<p style='margin: 0;'><strong>Your schedule submission for {$daysString} has been declined by the administration.</strong></p>";
        $emailBody .= "</div>";
        $emailBody .= "<p>This may be due to:</p>";
        $emailBody .= "<ul>";
        $emailBody .= "<li>Schedule conflicts</li>";
        $emailBody .= "<li>Room availability issues</li>";
        $emailBody .= "<li>Administrative requirements not met</li>";
        $emailBody .= "</ul>";
        $emailBody .= "<p>Please contact the administration office for more details or to resubmit your schedule with corrections.</p>";
        $emailBody .= "</div>";
        
        $emailBody .= "<div class='footer'>";
        $emailBody .= "<p><strong>Bulacan Polytechnic College</strong><br>";
        $emailBody .= "Attendance Monitoring System</p>";
        $emailBody .= "<p style='font-size: 12px; color: #9ca3af;'>This is an automated message. Please do not reply to this email.</p>";
        $emailBody .= "</div>";
        $emailBody .= "</div>";
        
        $emailBody .= "</body></html>";
        
        return sendEmail($user['email'], $emailSubject, $emailBody);
    }

    /**
     * Notify all admin users when a new schedule is submitted for approval
     * 
     * @param int $userId The ID of the user who submitted the schedule
     * @param array $schedules Array of schedule data
     */
    private function notifyAdminsOfPendingSchedule($userId, $schedules) {
        $userModel = $this->model('User');
        $db = Database::getInstance();
        
        // Get the user who submitted the schedule
        $submitter = $userModel->findById($userId);
        if (!$submitter) return;
        
        $submitterName = $submitter['first_name'] . ' ' . $submitter['last_name'];
        $scheduleCount = count($schedules);
        
        // Get list of unique days
        $days = array_unique(array_column($schedules, 'day'));
        $daysString = implode(', ', $days);
        
        // Craft notification message
        $message = "{$submitterName} has submitted {$scheduleCount} schedule(s) for {$daysString} pending your approval.";
        
        // Fetch all admin user IDs
        $stmt = $db->query(
            "SELECT id FROM users WHERE role = 'Admin' AND status = 'active'",
            [],
            ""
        );
        $admins = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        // Send notification to each admin
        foreach ($admins as $admin) {
            Notification::create($admin['id'], $message, 'warning');
        }
        
        // Optional: Log this action
        $logModel = $this->model('ActivityLog');
        $logModel->log(
            $userId, 
            'Schedule Submitted', 
            "User submitted {$scheduleCount} schedule(s) for {$daysString} - pending admin approval"
        );
    }
}