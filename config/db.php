<?php
/**
 * Konfigurasi Database - Sistem Absensi Sekolah
 * Menggunakan PDO untuk keamanan (prepared statements)
 */

// ============================================================
// Konstanta Konfigurasi
// ============================================================
define('DB_HOST',    'localhost');
define('DB_NAME',    'absensi_sekolah');
define('DB_USER',    'root');
define('DB_PASS',    'Root12342022!');
define('DB_CHARSET', 'utf8mb4');
define('DB_PORT',    '3306');

// ============================================================
// Konstanta Aplikasi
// ============================================================
define('APP_NAME',    'Absensi Sekolah');
define('APP_VERSION', '1.0.0');
// Deteksi URL secara dinamis agar bisa diakses via IP Lokal maupun Domain Publik
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
$host     = $_SERVER['HTTP_HOST'] ?? '192.168.88.100';
define('APP_URL', $protocol . $host . '/web/absensi_sekolah');
define('APP_ROOT',    dirname(__DIR__));

// Batas alpha sebelum peringatan dikirim
define('ALPHA_WARNING_THRESHOLD', 3);

// ============================================================
// Timezone
// ============================================================
date_default_timezone_set('Asia/Jakarta');

// ============================================================
// Session Config (sebelum session_start)
// ============================================================
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_strict_mode', 1);
    ini_set('session.gc_maxlifetime', 7200); // 2 jam
    session_start();
}

// ============================================================
// Koneksi Database (PDO Singleton)
// ============================================================
function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            DB_HOST, DB_PORT, DB_NAME, DB_CHARSET
        );
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Log error tanpa expose credentials
            error_log('[DB Error] ' . $e->getMessage());
            die('<div style="font-family:sans-serif;padding:40px;text-align:center;">
                <h2 style="color:#dc3545;">⚠ Koneksi Database Gagal</h2>
                <p>Tidak dapat terhubung ke database. Hubungi administrator sistem.</p>
                </div>');
        }
    }
    return $pdo;
}

// Alias singkat
$db = getDB();
