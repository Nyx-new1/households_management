<?php
// C:\Users\THOMAS\.gemini\antigravity\scratch\household_services_platform\customer\logout.php
session_start();
session_destroy();
header("Location: login.php");
exit;
?>
