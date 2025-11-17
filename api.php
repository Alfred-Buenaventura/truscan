<?php
/**
 * api.php
 * Central entry point for all AJAX and API requests.
 * Routes requests to the appropriate Controller methods.
 */

require_once 'app/init.php';

// Load necessary controllers
require_once 'app/controllers/apicontroller.php';
require_once 'app/controllers/attendancecontroller.php';
require_once 'app/controllers/registrationcontroller.php';
require_once 'app/controllers/displaycontroller.php';

$action = $_GET['action'] ?? '';

switch ($action) {
    // --- Kiosk & Device Endpoints (No Login Required) ---
    case 'record_attendance':
        (new ApiController())->recordAttendance();
        break;
        
    case 'get_templates':
        (new ApiController())->getFingerprintTemplates();
        break;
    
    // --- User/UI AJAX Endpoints (Login Checked in Controller) ---
    case 'mark_notification_read':
        // Moved logic to ApiController or keep in DisplayController
        (new ApiController())->markNotificationRead();
        break;
        
    case 'notify_pending_users':
        (new RegistrationController())->notify();
        break;

    // --- Admin DTR Editor Endpoints ---
    case 'get_monthly_dtr':
        (new AttendanceController())->getMonthlyDtr();
        break;
        
    case 'save_monthly_dtr':
        (new AttendanceController())->saveMonthlyDtr();
        break;

    default:
        header('HTTP/1.1 404 Not Found');
        echo json_encode(['success' => false, 'message' => 'Invalid API Action']);
        break;
}
?>