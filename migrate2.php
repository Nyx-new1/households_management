<?php
require_once 'config/db_connect.php';

try {
    // Add location to users
    $pdo->exec("ALTER TABLE users ADD COLUMN location VARCHAR(255) NULL");
    echo "Added location to users.\n";
} catch (Exception $e) {
    echo "Location column might already exist: " . $e->getMessage() . "\n";
}

try {
    // Add rejection reason to provider_details
    $pdo->exec("ALTER TABLE provider_details ADD COLUMN rejection_reason TEXT NULL");
    echo "Added rejection_reason to provider_details.\n";
} catch (Exception $e) {
    echo "Rejection reason column might already exist: " . $e->getMessage() . "\n";
}
?>
