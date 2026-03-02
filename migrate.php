<?php
/**
 * One-time migration: add username, name, image columns to followers table
 * Run if you have the old schema (instagram_id only)
 */
require_once __DIR__ . '/includes/db.php';

header('Content-Type: text/html; charset=utf-8');

try {
    $pdo = get_db();
    $cols = $pdo->query("SHOW COLUMNS FROM followers")->fetchAll(PDO::FETCH_COLUMN);

    if (in_array('instagram_id', $cols) && !in_array('username', $cols)) {
        $pdo->exec("ALTER TABLE followers ADD COLUMN username VARCHAR(255), ADD COLUMN name VARCHAR(255), ADD COLUMN image VARCHAR(255)");
        $pdo->exec("UPDATE followers SET username = instagram_id WHERE username IS NULL OR username = ''");
        $pdo->exec("ALTER TABLE followers MODIFY username VARCHAR(255) NOT NULL");
        $pdo->exec("ALTER TABLE followers DROP COLUMN instagram_id");
        echo "<p>Migration complete. You can delete this file.</p>";
    } elseif (in_array('instagram_id', $cols) && in_array('username', $cols)) {
        $pdo->exec("UPDATE followers SET username = instagram_id WHERE username IS NULL OR username = ''");
        $pdo->exec("ALTER TABLE followers DROP COLUMN instagram_id");
        echo "<p>Migration complete. You can delete this file.</p>";
    } else {
        echo "<p>Schema already up to date. No migration needed.</p>";
    }

    // Add UNIQUE constraint on username if not present
    try {
        $indexes = $pdo->query("SHOW INDEX FROM followers WHERE Column_name = 'username' AND Non_unique = 0")->fetchAll();
        if (empty($indexes)) {
            $pdo->exec("ALTER TABLE followers ADD UNIQUE KEY unique_username (username)");
            echo "<p>Added UNIQUE constraint on username.</p>";
        }
    } catch (PDOException $e) {
        echo "<p>Note: Could not add unique constraint (duplicates may exist): " . htmlspecialchars($e->getMessage()) . "</p>";
    }

    if (!is_dir(__DIR__ . '/uploads')) {
        mkdir(__DIR__ . '/uploads', 0755, true);
        echo "<p>Created uploads directory.</p>";
    }

    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS daily_winners (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(255) NOT NULL,
            name VARCHAR(255) DEFAULT NULL,
            image VARCHAR(255) DEFAULT NULL,
            winner_date DATE NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        echo "<p>daily_winners table ready.</p>";
    } catch (PDOException $e) { /* table may exist */ }

    echo "<p><a href='admin/dashboard.php'>Go to Dashboard</a></p>";
} catch (PDOException $e) {
    echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
