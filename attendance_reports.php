<?php
session_start();
require_once 'app/init.php';
require_once 'app/controllers/attendancecontroller.php';

$controller = new AttendanceController();
$controller->index();
?>