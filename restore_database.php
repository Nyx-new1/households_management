<?php
// restore_database.php

$host = 'localhost';
$username = 'root'; // Default XAMPP username
$password = '';     // Default XAMPP password
$db_name = 'household_services';

echo "<h1>Database Restoration Tool</h1>";
echo "<pre>";

try {
    // 1. Connect without database to create it if it doesn't exist
    $pdo = new PDO("mysql:host=$host;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Connected to MySQL server successfully.\n";
    
    // 2. Create Database
    $pdo->exec("DROP DATABASE IF EXISTS `$db_name`");
    $pdo->exec("CREATE DATABASE `$db_name`");
    echo "Database `$db_name` dropped and recreated for a clean start.\n";
    
    // 3. Connect to the specific database
    $pdo->exec("USE `$db_name`");
    
    // 4. Run database.sql
    $sql_file = __DIR__ . '/database.sql';
    if (file_exists($sql_file)) {
        $sql = file_get_contents($sql_file);
        $pdo->exec($sql);
        echo "Successfully executed database.sql.\n";
    } else {
        echo "Warning: database.sql not found.\n";
    }
    
    // 5. Run migrations
    echo "\n--- Running Migrations ---\n";
    
    // Migrate 2
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN location VARCHAR(255) NULL");
        echo "migrate2: Added location to users.\n";
    } catch (Exception $e) {
        echo "migrate2: Location column might already exist.\n";
    }

    try {
        $pdo->exec("ALTER TABLE provider_details ADD COLUMN rejection_reason TEXT NULL");
        echo "migrate2: Added rejection_reason to provider_details.\n";
    } catch (Exception $e) {
        echo "migrate2: Rejection reason column might already exist.\n";
    }
    
    // Migrate 3
    try {
        $sql3 = "CREATE TABLE IF NOT EXISTS notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            message TEXT NOT NULL,
            link VARCHAR(255) NULL,
            is_read TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )";
        $pdo->exec($sql3);
        echo "migrate3: Notifications table created successfully.\n";
    } catch (Exception $e) {
        echo "migrate3 Error: " . $e->getMessage() . "\n";
    }
    
    // Migrate 4
    try {
        $sql4 = "CREATE TABLE IF NOT EXISTS messages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            sender_id INT NOT NULL,
            receiver_id INT NOT NULL,
            booking_id INT NOT NULL,
            message TEXT NOT NULL,
            is_read TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE
        )";
        $pdo->exec($sql4);
        echo "migrate4: Messages table created successfully.\n";
    } catch (Exception $e) {
        echo "migrate4 Error: " . $e->getMessage() . "\n";
    }
    
    // Migrate 5
    try {
        $sql5 = "CREATE TABLE IF NOT EXISTS system_settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            setting_key VARCHAR(50) UNIQUE NOT NULL,
            setting_value VARCHAR(255) NOT NULL,
            description TEXT
        )";
        $pdo->exec($sql5);
        $pdo->exec("INSERT IGNORE INTO system_settings (setting_key, setting_value, description) VALUES ('usd_to_tzs_rate', '2500', 'Exchange rate from USD to TZS')");
        echo "migrate5: system_settings table created successfully.\n";
    } catch (Exception $e) {
        echo "migrate5 Error: " . $e->getMessage() . "\n";
    }
    
    echo "\n<h3>Restoration Complete!</h3>";
    echo "You can now <a href='index.php'>go to the homepage</a> or <a href='admin/login.php'>log in as admin</a> (admin@example.com / password123).";

} catch (PDOException $e) {
    die("\nDatabase Error: " . $e->getMessage());
}

echo "</pre>";
?>
