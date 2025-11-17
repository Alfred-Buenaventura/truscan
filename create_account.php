<?php
session_start();
require_once 'app/init.php'; // Loads everything
require_once 'app/controllers/accountcontroller.php';

$controller = new AccountController();
$controller->index();
?>