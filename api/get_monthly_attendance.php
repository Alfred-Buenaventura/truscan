<?php
require_once '../config.php';
requireAdmin(); // Only Admins can access this

$userId = $_GET['user_id'] ?? 0;
$startDate = $_GET['start_date'] ?? date('Y-m-01');

if (empty($userId)) {
    jsonResponse(false, 'No user ID provided.');
    exit;
}

try {
    $start = new DateTime($startDate);
    $month = $start->format('m');
    $year = $start->format('Y');
    
    $daysInMonth = (int)$start->format('t');
    $endDate = $start->format('Y-m-t');

    $db = db();

    // 1. Get all existing records for the month
    $stmt = $db->prepare(
        "SELECT id, date, time_in, time_out, status, remarks 
         FROM attendance_records 
         WHERE user_id = ? AND date BETWEEN ? AND ?"
    );
    $stmt->bind_param("iss", $userId, $startDate, $endDate);
    $stmt->execute();
    $dbRecords = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // 2. Process records into a day-keyed array for easy lookup
    $attendanceData = [];
    foreach ($dbRecords as $rec) {
        $dayOfMonth = (int)(new DateTime($rec['date']))->format('j');
        $attendanceData[$dayOfMonth] = $rec;
    }

    // 3. Create a full 31-day array
    $fullMonthData = [];
    for ($day = 1; $day <= $daysInMonth; $day++) {
        $date = "$year-$month-" . str_pad($day, 2, '0', STR_PAD_LEFT);
        
        if (isset($attendanceData[$day])) {
            // A record exists for this day
            $rec = $attendanceData[$day];
            $fullMonthData[] = [
                'day' => $day,
                'date' => $rec['date'],
                'record_id' => $rec['id'],
                'time_in' => $rec['time_in'] ? date('H:i:s', strtotime($rec['time_in'])) : null,
                'time_out' => $rec['time_out'] ? date('H:i:s', strtotime($rec['time_out'])) : null,
                'status' => $rec['status'],
                'remarks' => $rec['remarks'],
                'exists' => true
            ];
        } else {
            // No record exists for this day
            $fullMonthData[] = [
                'day' => $day,
                'date' => $date,
                'record_id' => null,
                'time_in' => null,
                'time_out' => null,
                'status' => null,
                'remarks' => null,
                'exists' => false // Flag to show it's a new, empty row
            ];
        }
    }
    
    // 4. Fill in the rest of the 31 days as disabled
    for ($day = $daysInMonth + 1; $day <= 31; $day++) {
         $fullMonthData[] = [
            'day' => $day,
            'date' => null,
            'record_id' => null,
            'time_in' => null,
            'time_out' => null,
            'status' => null,
            'remarks' => null,
            'exists' => false,
            'disabled' => true
        ];
    }

    jsonResponse(true, 'Data fetched', $fullMonthData);

} catch (Exception $e) {
    jsonResponse(false, 'An error occurred: ' . $e->getMessage());
}
?>