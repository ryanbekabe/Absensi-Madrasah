<?php
/**
 * Buat Akun Guru Otomatis - Admin
 */
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireRole('admin');

$db = getDB();

// Handle Single Sync
$id = cleanInt($_GET['id'] ?? 0);
if (!$id) redirectWith(APP_URL . '/admin/guru/', 'danger', 'ID Guru tidak valid.');

$stmt = $db->prepare("SELECT id, nip, nama, user_id FROM guru WHERE id = ?");
$stmt->execute([$id]);
$guru = $stmt->fetch();

if (!$guru) redirectWith(APP_URL . '/admin/guru/', 'danger', 'Data guru tidak ditemukan.');
if ($guru['user_id']) redirectWith(APP_URL . '/admin/guru/', 'warning', 'Guru ini sudah memiliki akun login.');

// Gunakan NIP sebagai username, jika tidak ada pakai nama tanpa spasi
$username = $guru['nip'] ? 'guru' . $guru['nip'] : strtolower(str_replace(' ', '', $guru['nama']));
$password = password_hash('Guru123!', PASSWORD_DEFAULT);

try {
    $db->beginTransaction();
    
    // Insert ke tabel users
    $stmtU = $db->prepare("INSERT INTO users (username, password, role, nama, aktif) VALUES (?, ?, 'guru', ?, 1)");
    $stmtU->execute([$username, $password, $guru['nama']]);
    $userId = $db->lastInsertId();
    
    // Update tabel guru
    $stmtG = $db->prepare("UPDATE guru SET user_id = ? WHERE id = ?");
    $stmtG->execute([$userId, $guru['id']]);
    
    $db->commit();
    writeAuditLog('INSERT_ACCOUNT', 'users', $userId, '', "Account created for teacher {$guru['nama']}");
    
    redirectWith(APP_URL . '/admin/guru/', 'success', "Akun login untuk <strong>{$guru['nama']}</strong> berhasil dibuat.<br>Username: <code>$username</code><br>Password: <code>Guru123!</code>");
} catch (Exception $e) {
    $db->rollBack();
    redirectWith(APP_URL . '/admin/guru/', 'danger', 'Gagal membuat akun: ' . $e->getMessage());
}
