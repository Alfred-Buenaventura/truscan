<?php
require_once 'app/init.php';
require_once 'app/controllers/attendancecontroller.php';
(new AttendanceController())->export();
?>