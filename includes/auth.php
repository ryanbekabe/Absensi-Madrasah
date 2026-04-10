<?php
/**
 * Helper Autentikasi & Role Guard
 * Sistem Absensi Sekolah
 */

require_once __DIR__ . '/../config/db.php';

// ============================================================
// Cek apakah sudah login
// ============================================================
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// ============================================================
// Ambil data user yang sedang login
// ============================================================
function currentUser(): ?array {
    if (!isLoggedIn()) return null;
    return [
        'id'       => $_SESSION['user_id'],
        'username' => $_SESSION['username'] ?? '',
        'nama'     => $_SESSION['nama'] ?? '',
        'role'     => $_SESSION['role'] ?? '',
        'foto'     => $_SESSION['foto'] ?? null,
    ];
}

// ============================================================
// Redirect ke login jika belum login
// ============================================================
function requireLogin(): void {
    if (!isLoggedIn()) {
        $_SESSION['flash_warning'] = 'Silakan login terlebih dahulu.';
        header('Location: ' . APP_URL . '/login.php');
        exit;
    }
}

// ============================================================
// Guard berdasarkan role
// ============================================================
function requireRole(string ...$roles): void {
    requireLogin();
    $userRole = $_SESSION['role'] ?? '';
    if (!in_array($userRole, $roles)) {
        $_SESSION['flash_danger'] = 'Akses ditolak. Anda tidak memiliki izin untuk halaman ini.';
        header('Location: ' . APP_URL . '/index.php');
        exit;
    }
}

function isAdmin(): bool {
    return ($_SESSION['role'] ?? '') === 'admin';
}

function isGuru(): bool {
    return ($_SESSION['role'] ?? '') === 'guru';
}

function isSiswa(): bool {
    return ($_SESSION['role'] ?? '') === 'siswa';
}

// ============================================================
// CSRF Token Management
// ============================================================
function generateCsrf(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrf(): void {
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        $_SESSION['flash_danger'] = 'Token keamanan tidak valid. Silakan coba lagi.';
        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? APP_URL . '/index.php'));
        exit;
    }
}

// ============================================================
// Flash Messages
// ============================================================
function setFlash(string $type, string $message): void {
    $_SESSION["flash_{$type}"] = $message;
}

function getFlash(string $type): ?string {
    if (isset($_SESSION["flash_{$type}"])) {
        $msg = $_SESSION["flash_{$type}"];
        unset($_SESSION["flash_{$type}"]);
        return $msg;
    }
    return null;
}

function hasFlash(string $type): bool {
    return isset($_SESSION["flash_{$type}"]);
}

// ============================================================
// Audit Log
// ============================================================
function writeAuditLog(string $action, string $table = '', ?int $recordId = null, string $old = '', string $new = ''): void {
    if (!isLoggedIn()) return;
    $db = getDB();
    $stmt = $db->prepare("
        INSERT INTO audit_log (user_id, action, table_name, record_id, old_value, new_value, ip_address)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $_SESSION['user_id'],
        $action,
        $table,
        $recordId,
        $old ?: null,
        $new ?: null,
        $_SERVER['REMOTE_ADDR'] ?? null,
    ]);
}

// ============================================================
// Dashboard Redirect berdasarkan role
// ============================================================
function redirectToDashboard(): void {
    $role = $_SESSION['role'] ?? '';
    switch ($role) {
        case 'admin':
            header('Location: ' . APP_URL . '/admin/dashboard.php');
            break;
        case 'guru':
            header('Location: ' . APP_URL . '/guru/dashboard.php');
            break;
        case 'siswa':
            header('Location: ' . APP_URL . '/siswa/dashboard.php');
            break;
        default:
            header('Location: ' . APP_URL . '/login.php');
    }
    exit;
}
