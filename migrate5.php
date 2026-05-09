<?php
require_once 'config/db_connect.php';

try {
    $sql = "CREATE TABLE IF NOT EXISTS system_settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        setting_key VARCHAR(50) UNIQUE NOT NULL,
        setting_value VARCHAR(255) NOT NULL,
        description TEXT
    )";
    $pdo->exec($sql);
    echo "system_settings table created successfully.\n";

    // Insert default rate if not exists
    $pdo->exec("INSERT IGNORE INTO system_settings (setting_key, setting_value, description) VALUES ('usd_to_tzs_rate', '2500', 'Exchange rate from USD to TZS')");
    echo "Default USD to TZS rate inserted.\n";

} catch (Exception $e) {
    echo "Error creating table: " . $e->getMessage() . "\n";
}
?>
