<?php
/**
 * Logout - Sistem Absensi Sekolah
 */
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

session_unset();
session_destroy();

// Hapus cookie session
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

header('Location: ' . APP_URL . '/login.php?logout=1');
exit;
