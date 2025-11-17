<?php
require_once 'app/init.php';
require_once 'app/controllers/registrationcontroller.php';

$controller = new RegistrationController();
$controller->enroll();
?>