<?php
session_start();
require_once 'app/init.php';
require_once 'app/controllers/AttendanceController.php';

$controller = new AttendanceController();
$controller->index();
?>