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

    if (!is_dir(__DIR__ . '/uploads')) {
        mkdir(__DIR__ . '/uploads', 0755, true);
        echo "<p>Created uploads directory.</p>";
    }

    echo "<p><a href='admin/dashboard.php'>Go to Dashboard</a></p>";
} catch (PDOException $e) {
    echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
