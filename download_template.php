<?php
require_once 'app/init.php';
require_once 'app/controllers/AccountController.php';

$controller = new AccountController();
$controller->downloadTemplate();
?>