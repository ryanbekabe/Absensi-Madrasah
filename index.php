<?php
/**
 * Entry Point - Sistem Absensi Sekolah
 * Redirect sesuai status login
 */
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

if (isLoggedIn()) {
    redirectToDashboard();
} else {
    header('Location: ' . APP_URL . '/login.php');
    exit;
}
