<?php
// 1. Secure Session Start
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Set Timezone
date_default_timezone_set('Asia/Manila');

// 3. Load Core Classes
require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/core/Controller.php';
require_once __DIR__ . '/core/Helper.php';
require_once __DIR__ . '/core/Mailer.php';

// --- NEW: Load .env file ---
Helper::loadEnv(__DIR__ . '/../.env');

// 4. Load Models (Optional: You can load these in controllers, but loading common ones here helps)
require_once __DIR__ . '/models/User.php';
require_once __DIR__ . '/models/ActivityLog.php';
require_once __DIR__ . '/models/Notification.php';

// 5. Define Global Helper Functions (To keep views compatible without changing code)
// This allows you to keep using clean(), isAdmin() in your views without rewriting them to Helper::clean()

if (!function_exists('clean')) {
    function clean($data) { return Helper::clean($data); }
}

if (!function_exists('isAdmin')) {
    function isAdmin() { return Helper::isAdmin(); }
}

if (!function_exists('isLoggedIn')) {
    function isLoggedIn() { return Helper::isLoggedIn(); }
}

if (!function_exists('jsonResponse')) {
    function jsonResponse($s, $m, $d=null) { Helper::jsonResponse($s, $m, $d); }
}

if (!function_exists('sendEmail')) {
    function sendEmail($to, $sub, $msg) { return Mailer::send($to, $sub, $msg); }
}

// 6. Database Helper for Views (If views still use raw db calls, though they shouldn't)
function db() {
    $database = new Database();
    return $database->conn;
}
?>