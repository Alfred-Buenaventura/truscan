<?php
// Display errors for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/app/init.php';
require_once __DIR__ . '/app/controllers/authcontroller.php'; // Lowercase

$auth = new AuthController();
$auth->login();
?>