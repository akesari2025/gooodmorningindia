<?php
/**
 * Database configuration for Good Morning India
 * Copy this file to config.local.php and update credentials
 */

$db_config = [
    'host'     => 'localhost',
    'database' => 'your_database_name',
    'username' => 'root',
    'password' => '',
    'charset'  => 'utf8mb4',
];

// Load local overrides if present
if (file_exists(__DIR__ . '/config.local.php')) {
    $local = include __DIR__ . '/config.local.php';
    $db_config = array_merge($db_config, $local);
}

// Base URL path (e.g. '' for root, or '/GooodMorningIndia' for subdir)
$db_config['base_path'] = $db_config['base_path'] ?? '';

return $db_config;
