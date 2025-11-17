<?php
require_once __DIR__ . '/../core/Controller.php';

class AttendanceController extends Controller {

    public function index() {
        $this->requireLogin(); 
        
        $attModel = $this->model('Attendance');
        $userModel = $this->model('User');
        
        $data = [
            'isAdmin' => ($_SESSION['role'] === 'Admin'),
            'error' => ''
        ];

        $filters = [
            'start_date' => $_GET['start_date'] ?? date('Y-m-d', strtotime('-7 days')),
            'end_date'   => $_GET['end_date']   ?? date('Y-m-d'),
            'search'     => $_GET['search']     ?? '',
            'user_id'    => $_GET['user_id']    ?? ''
        ];

        if ($data['isAdmin']) {
            $data['pageTitle'] = 'Attendance Reports';
            $data['pageSubtitle'] = 'View and manage all user attendance records';
            $data['allUsers'] = $userModel->getAllStaff();
            $data['stats'] = $attModel->getStats(); 
        } else {
            $data['pageTitle'] = 'My Attendance';
            $data['pageSubtitle'] = 'View your personal attendance history';
            $filters['user_id'] = $_SESSION['user_id'];
            $data['stats'] = $attModel->getStats($_SESSION['user_id']);
        }

        $data['records'] = $attModel->getRecords($filters);
        $data['totalRecords'] = count($data['records']);
        $data['filters'] = $filters;

        $this->view('attendance_view', $data);
    }

    public function export() {
        $this->requireLogin();
        $attModel = $this->model('Attendance');
        
        $filters = [
            'start_date' => $_GET['start_date'] ?? date('Y-m-01'),
            'end_date' => $_GET['end_date'] ?? date('Y-m-d'),
            'user_id' => isAdmin() ? ($_GET['user_id'] ?? '') : $_SESSION['user_id'],
            'search' => $_GET['search'] ?? ''
        ];

        $records = $attModel->getRecords($filters);

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="attendance_report_' . date('Y-m-d') . '.csv"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        $output = fopen('php://output', 'w');
        fputcsv($output, ['Date', 'Faculty ID', 'Name', 'Role', 'Time In', 'Time Out', 'Status']);
        
        foreach ($records as $row) {
            fputcsv($output, [
                $row['date'], 
                $row['faculty_id'], 
                $row['first_name'] . ' ' . $row['last_name'],
                $row['role'], 
                $row['time_in'], 
                $row['time_out'], 
                $row['status']
            ]);
        }
        fclose($output);
        exit;
    }

    public function printDtr() {
        $this->requireLogin();
        $userId = $_GET['user_id'] ?? $_SESSION['user_id'];
        
        if (!isAdmin() && $userId != $_SESSION['user_id']) {
            die('Access Denied');
        }

        $userModel = $this->model('User');
        $attModel = $this->model('Attendance');
        
        $filters = [
            'start_date' => $_GET['start_date'] ?? date('Y-m-01'),
            'end_date' => $_GET['end_date'] ?? date('Y-m-t'),
            'user_id' => $userId
        ];
        
        $records = $attModel->getRecords($filters); 
        
        $dtrData = [];
        foreach($records as $r) {
            $day = (int)date('d', strtotime($r['date']));
            $dtrData[$day] = $r;
        }

        $data = [
            'user' => $userModel->findById($userId),
            'startDate' => $filters['start_date'],
            'endDate' => $filters['end_date'],
            'dtrRecords' => $dtrData,
            'isPreview' => isset($_GET['preview'])
        ];

        $this->view('print_dtr_view', $data);
    }

    // --- NEW API METHODS ---

    /**
     * Fetches monthly attendance for DTR Editor (Admin only)
     * Formerly: api/get_monthly_attendance.php
     */
    public function getMonthlyDtr() {
        $this->requireAdmin();
        header('Content-Type: application/json');

        $userId = $_GET['user_id'] ?? 0;
        $startDate = $_GET['start_date'] ?? date('Y-m-01');

        if (empty($userId)) {
            echo json_encode(['success' => false, 'message' => 'No user ID.']); exit;
        }

        try {
            $db = new Database();
            $start = new DateTime($startDate);
            $month = $start->format('m');
            $year = $start->format('Y');
            $daysInMonth = (int)$start->format('t');
            $endDate = $start->format('Y-m-t');

            $stmt = $db->query(
                "SELECT id, date, time_in, time_out, status, remarks 
                 FROM attendance_records 
                 WHERE user_id = ? AND date BETWEEN ? AND ?",
                 [$userId, $startDate, $endDate], "iss"
            );
            $dbRecords = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

            $attendanceData = [];
            foreach ($dbRecords as $rec) {
                $dayOfMonth = (int)(new DateTime($rec['date']))->format('j');
                $attendanceData[$dayOfMonth] = $rec;
            }

            $fullMonthData = [];
            for ($day = 1; $day <= $daysInMonth; $day++) {
                $date = "$year-$month-" . str_pad($day, 2, '0', STR_PAD_LEFT);
                
                if (isset($attendanceData[$day])) {
                    $rec = $attendanceData[$day];
                    $fullMonthData[] = [
                        'day' => $day, 'date' => $rec['date'], 'record_id' => $rec['id'],
                        'time_in' => $rec['time_in'] ? date('H:i:s', strtotime($rec['time_in'])) : null,
                        'time_out' => $rec['time_out'] ? date('H:i:s', strtotime($rec['time_out'])) : null,
                        'status' => $rec['status'], 'remarks' => $rec['remarks'], 'exists' => true
                    ];
                } else {
                    $fullMonthData[] = [
                        'day' => $day, 'date' => $date, 'record_id' => null,
                        'time_in' => null, 'time_out' => null, 'status' => null, 'remarks' => null, 'exists' => false
                    ];
                }
            }
            
            echo json_encode(['success' => true, 'data' => $fullMonthData]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    /**
     * Saves edited monthly attendance (Admin only)
     * Formerly: api/save_monthly_attendance.php
     */
    public function saveMonthlyDtr() {
        $this->requireAdmin();
        header('Content-Type: application/json');

        $data = json_decode(file_get_contents('php://input'), true);
        $userId = $data['user_id'] ?? 0;
        $records = $data['records'] ?? [];

        if (empty($userId) || empty($records)) {
            echo json_encode(['success' => false, 'message' => 'Invalid data.']); exit;
        }

        $db = new Database();
        $conn = $db->conn;
        $conn->begin_transaction();

        try {
            $stmt_update = $conn->prepare("UPDATE attendance_records SET time_in=?, time_out=?, status=?, working_hours=?, remarks=? WHERE id=?");
            $stmt_insert = $conn->prepare("INSERT INTO attendance_records (user_id, date, time_in, time_out, status, working_hours, remarks) VALUES (?, ?, ?, ?, ?, ?, ?)");

            foreach ($records as $rec) {
                $recordId = $rec['record_id'];
                $date = $rec['date'];
                $timeIn = !empty($rec['time_in']) ? $rec['time_in'] : null;
                $timeOut = !empty($rec['time_out']) ? $rec['time_out'] : null;
                $status = !empty($rec['status']) ? $rec['status'] : null;
                $remarks = !empty($rec['remarks']) ? $rec['remarks'] : null;

                // Skip if all fields are empty and no record exists
                if (empty($recordId) && empty($timeIn) && empty($timeOut) && empty($status) && empty($remarks)) {
                    continue;
                }
                
                $workingHours = 0; 
                if ($timeIn && $timeOut) {
                    $span = (new DateTime($timeOut))->getTimestamp() - (new DateTime($timeIn))->getTimestamp();
                    $workingHours = $span / 3600.0;
                    if ($workingHours > 5) $workingHours -= 1; // 1 hour break deduction logic
                }

                if (!empty($recordId)) {
                    $stmt_update->bind_param("sssdsi", $timeIn, $timeOut, $status, $workingHours, $remarks, $recordId);
                    $stmt_update->execute();
                } else {
                    $stmt_insert->bind_param("issssds", $userId, $date, $timeIn, $timeOut, $status, $workingHours, $remarks);
                    $stmt_insert->execute();
                }
            }

            $conn->commit();
            
            // Log activity (Optional)
            $logModel = $this->model('ActivityLog');
            $logModel->log($_SESSION['user_id'], 'DTR Edited', "Admin edited DTR for user ID $userId");

            echo json_encode(['success' => true, 'message' => 'DTR updated successfully!']);

        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }
}
?>