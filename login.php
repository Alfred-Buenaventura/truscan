<?php
require_once 'app/init.php';
require_once 'app/controllers/AuthController.php';

$auth = new AuthController();
$auth->login();
?>