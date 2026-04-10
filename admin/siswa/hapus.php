<?php
/**
 * Hapus Siswa - Admin (soft delete)
 */
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireRole('admin');
verifyCsrf();

$db = getDB();
$id = cleanInt($_GET['id'] ?? 0);

$stmt = $db->prepare("SELECT * FROM siswa WHERE id = ?");
$stmt->execute([$id]);
$siswa = $stmt->fetch();

if (!$siswa) {
    redirectWith(APP_URL . '/admin/siswa/', 'danger', 'Data siswa tidak ditemukan.');
}

// Soft delete
$db->prepare("UPDATE siswa SET aktif = 0 WHERE id = ?")->execute([$id]);
if ($siswa['user_id']) {
    $db->prepare("UPDATE users SET aktif = 0 WHERE id = ?")->execute([$siswa['user_id']]);
}

writeAuditLog('DELETE', 'siswa', $id, json_encode($siswa), '{"aktif":0}');
redirectWith(APP_URL . '/admin/siswa/', 'success', "Siswa <strong>{$siswa['nama']}</strong> telah dinonaktifkan.");
