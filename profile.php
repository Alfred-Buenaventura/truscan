<?php
require_once 'app/init.php'; // Loads everything
require_once 'app/controllers/profilecontroller.php';

$dashboard = new ProfileController();
$dashboard->index();
?>