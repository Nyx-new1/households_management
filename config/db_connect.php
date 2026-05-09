<?php
// C:\Users\THOMAS\.gemini\antigravity\scratch\household_services_platform\config\db_connect.php

$host = 'localhost';
$db_name = 'household_services';
$username = 'root'; // Default XAMPP username
$password = '';     // Default XAMPP password

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>
