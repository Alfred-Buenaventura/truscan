<?php
session_start();
require_once 'app/init.php';
require_once 'app/controllers/ActivityController.php';
$controller = new ActivityController();
$controller->index();
?>