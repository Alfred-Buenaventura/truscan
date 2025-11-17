<?php
require_once 'app/init.php'; // Loads everything
require_once 'app/controllers/DashboardController.php';

$dashboard = new DashboardController();
$dashboard->index();
?>