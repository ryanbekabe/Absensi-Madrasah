<?php
/**
 * Absensi Guru - redirect ke halaman absensi siswa (shared)
 * Guru mengakses melalui path /guru/absensi.php
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('guru');

$qs = $_SERVER['QUERY_STRING'] ?? '';
header('Location: ' . APP_URL . '/admin/absensi_siswa.php' . ($qs ? '?' . $qs : ''));
exit;
