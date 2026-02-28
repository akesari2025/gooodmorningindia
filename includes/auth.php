<?php
/**
 * Session-based admin authentication
 */

function session_init() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

function is_logged_in() {
    session_init();
    return !empty($_SESSION['admin_id']);
}

function require_login() {
    if (!is_logged_in()) {
        $config = include __DIR__ . '/../config.php';
        $base = rtrim($config['base_path'] ?? '', '/');
        header('Location: ' . $base . '/login.php');
        exit;
    }
}

function get_admin_id() {
    session_init();
    return $_SESSION['admin_id'] ?? null;
}

function login_admin($admin_id) {
    session_init();
    session_regenerate_id(true);
    $_SESSION['admin_id'] = $admin_id;
}

function logout_admin() {
    session_init();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}
