<?php
session_start();
require_once 'app/init.php';
require_once 'app/controllers/schedulecontroller.php';

$controller = new ScheduleController();
$controller->index();
?>