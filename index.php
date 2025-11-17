<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/app/init.php';
require_once 'app/controllers/dashboardcontroller.php';

$dashboard = new DashboardController();
$dashboard->index();
?>