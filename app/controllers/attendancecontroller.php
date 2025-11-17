<?php
require_once __DIR__ . '/../core/Controller.php';

class AttendanceController extends Controller {

    public function index() {
        $this->requireLogin(); // Security Check
        
        $attModel = $this->model('Attendance');
        $userModel = $this->model('User');
        
        $data = [
            'isAdmin' => ($_SESSION['role'] === 'Admin'),
            'error' => ''
        ];

        // Default Filters
        $filters = [
            'start_date' => $_GET['start_date'] ?? date('Y-m-d', strtotime('-7 days')),
            'end_date'   => $_GET['end_date']   ?? date('Y-m-d'),
            'search'     => $_GET['search']     ?? '',
            'user_id'    => $_GET['user_id']    ?? ''
        ];

        // Logic Split: Admin vs User
        if ($data['isAdmin']) {
            $data['pageTitle'] = 'Attendance Reports';
            $data['pageSubtitle'] = 'View and manage all user attendance records';
            
            // Fetch data for dropdown
            $data['allUsers'] = $userModel->getAllStaff();
            
            // Fetch global stats
            $data['stats'] = $attModel->getStats(); 
        } else {
            $data['pageTitle'] = 'My Attendance';
            $data['pageSubtitle'] = 'View your personal attendance history';
            
            // Force user_id to current user
            $filters['user_id'] = $_SESSION['user_id'];
            
            // Fetch personal stats
            $data['stats'] = $attModel->getStats($_SESSION['user_id']);
        }

        // Fetch filtered records
        $data['records'] = $attModel->getRecords($filters);
        $data['totalRecords'] = count($data['records']);
        $data['filters'] = $filters; // Pass filters back to view to keep inputs filled

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
        
        // Need a method to get ALL records in range for DTR
        $filters = [
            'start_date' => $_GET['start_date'] ?? date('Y-m-01'),
            'end_date' => $_GET['end_date'] ?? date('Y-m-t'),
            'user_id' => $userId
        ];
        
        // Using existing getRecords, but you might want to process this data
        // into an array keyed by day for the calendar view in DTR
        $records = $attModel->getRecords($filters); 
        
        // Transform records for DTR view (Key by Day)
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
}
?>