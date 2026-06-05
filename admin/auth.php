<?php
// admin/auth.php — Admin session guard

function requireAdmin() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['admin_id'])) {
        header('Location: ' . (strpos($_SERVER['REQUEST_URI'], '/admin/') !== false ? 'login.php' : 'admin/login.php'));
        exit;
    }
}

function adminLoggedIn() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    return !empty($_SESSION['admin_id']);
}

function currentAdmin() {
    return [
        'id'   => $_SESSION['admin_id'] ?? 0,
        'name' => $_SESSION['admin_name'] ?? '',
        'role' => $_SESSION['admin_role'] ?? '',
    ];
}