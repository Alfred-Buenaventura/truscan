<?php
require_once 'app/init.php';
require_once 'app/controllers/authcontroller.php';
(new AuthController())->forgotPassword();
?>