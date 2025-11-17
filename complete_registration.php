<?php
session_start();
require_once 'app/init.php'; // Loads everything
require_once 'app/controllers/registrationcontroller.php';
$controller = new RegistrationController();
$controller->index();
?>