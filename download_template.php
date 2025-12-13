<?php
require_once 'app/init.php';
require_once 'app/controllers/accountcontroller.php';

$controller = new AccountController();
$controller->requireAdmin();

// UPDATED HEADER: Removed 'Username' column
$csv = "Faculty ID,Last Name,First Name,Middle Name,Role,Email,Phone Number\n";

// UPDATED SAMPLE DATA
$csv .= "FAC001,Dela Cruz,Juan,P.,Full Time Teacher,juan.delacruz@bpc.edu.ph,09123456789\n";
$csv .= "STAFF001,Garcia,Ana,L.,Guidance Office,ana.garcia@bpc.edu.ph,09171234567\n";

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="bpc_user_import_template.csv"');
header('Pragma: no-cache');
header('Expires: 0');

echo $csv;
exit;
?>