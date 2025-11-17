<?php
session_start();
require_once 'app/init.php'; // Loads everything
require_once 'app/controllers/createadmincontroller.php';
$controller = new AccountAdminController();
$controller->create();
?>