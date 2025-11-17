<?php
session_start();
require_once 'app/init.php';
require_once 'app/controllers/activitycontroller.php';
$controller = new activitycontroller();
$controller->index();
?>