<?php
require_once 'app/init.php';
require_once 'app/controllers/AttendanceController.php';
(new AttendanceController())->printDtr();
?>