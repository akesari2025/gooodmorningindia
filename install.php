<?php
/**
 * One-time setup: creates tables and default admin user
 * Run once, then delete or restrict access to this file
 */

$config = include __DIR__ . '/config.php';

try {
    $dsn = sprintf('mysql:host=%s;charset=%s', $config['host'], $config['charset']);
    $pdo = new PDO($dsn, $config['username'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);

    // Create database if not exists
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$config['database']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `{$config['database']}`");

    // Create tables
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS followers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(255) NOT NULL UNIQUE,
            name VARCHAR(255) DEFAULT NULL,
            image VARCHAR(255) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");

    // Create uploads directory for images
    $uploadDir = __DIR__ . '/uploads';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
        file_put_contents($uploadDir . '/.gitignore', "*\n!.gitignore\n");
    }

    // Migrate old schema (instagram_id -> username) if needed
    try {
        $cols = $pdo->query("SHOW COLUMNS FROM followers")->fetchAll(PDO::FETCH_COLUMN);
        if (in_array('instagram_id', $cols) && !in_array('username', $cols)) {
            $pdo->exec("ALTER TABLE followers ADD COLUMN username VARCHAR(255), ADD COLUMN name VARCHAR(255), ADD COLUMN image VARCHAR(255)");
            $pdo->exec("UPDATE followers SET username = instagram_id WHERE username IS NULL");
            $pdo->exec("ALTER TABLE followers MODIFY username VARCHAR(255) NOT NULL");
            $pdo->exec("ALTER TABLE followers DROP COLUMN instagram_id");
        } elseif (in_array('instagram_id', $cols)) {
            $pdo->exec("ALTER TABLE followers ADD COLUMN name VARCHAR(255), ADD COLUMN image VARCHAR(255)");
            $pdo->exec("UPDATE followers SET username = instagram_id WHERE username IS NULL OR username = ''");
        }
    } catch (PDOException $e) { /* migration optional */ }
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS admins (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS daily_winners (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(255) NOT NULL,
            name VARCHAR(255) DEFAULT NULL,
            image VARCHAR(255) DEFAULT NULL,
            winner_date DATE NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");

    // Create default admin if no admins exist
    $stmt = $pdo->query("SELECT COUNT(*) FROM admins");
    if ((int) $stmt->fetchColumn() === 0) {
        $hash = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO admins (username, password) VALUES (?, ?)");
        $stmt->execute(['admin', $hash]);
        $msg = "Database and tables created. Default admin: username <strong>admin</strong>, password <strong>admin123</strong>. Change this after first login.";
    } else {
        $msg = "Database already set up. No changes made.";
    }

    echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Setup Complete</title></head><body>";
    echo "<h1>Setup Complete</h1><p>" . $msg . "</p>";
    echo "<p><a href='login.php'>Go to Login</a></p>";
    echo "</body></html>";
} catch (PDOException $e) {
    http_response_code(500);
    echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Setup Error</title></head><body>";
    echo "<h1>Setup Error</h1>";
    echo "<p>Ensure config.local.php has correct MySQL credentials and the database user has CREATE privileges.</p>";
    echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
    echo "</body></html>";
}
