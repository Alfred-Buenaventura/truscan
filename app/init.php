<?php
// 1. Secure Session Start
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Set Timezone
date_default_timezone_set('Asia/Manila');

// 3. Load Core Classes (Use LOWERCASE filenames)
require_once __DIR__ . '/core/database.php';
require_once __DIR__ . '/core/controller.php';
require_once __DIR__ . '/core/helper.php';  // <--- This loads the Helper class
require_once __DIR__ . '/core/mailer.php';

// 4. Load .env (Ensure the path is correct)
// This looks for .env one folder UP from app/init.php (i.e., in the root)
Helper::loadEnv(__DIR__ . '/../.env');
// Load Models (LOWERCASE FILENAMES)
require_once __DIR__ . '/models/user.php';
require_once __DIR__ . '/models/activitylog.php';
require_once __DIR__ . '/models/notification.php';

// Global Helper Functions
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
function db() {
    $database = new Database();
    return $database->conn;
}
?>