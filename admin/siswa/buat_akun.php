<?php
/**
 * Buat Akun Siswa Otomatis - Admin
 */
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireRole('admin');

$db = getDB();

// Handle Bulk Sync
if (isset($_GET['action']) && $_GET['action'] === 'sync_all') {
    $siswaList = $db->query("SELECT id, nis, nama FROM siswa WHERE user_id IS NULL AND aktif = 1")->fetchAll();
    $count = 0;
    
    foreach ($siswaList as $s) {
        $username = 'siswa' . $s['nis'];
        
        // Cek jika username sudah ada (antisipasi NIS bentrok di tabel users)
        $cekU = $db->prepare("SELECT id FROM users WHERE username = ?");
        $cekU->execute([$username]);
        if ($cekU->fetch()) continue;

        try {
            $db->beginTransaction();
            
            $password = password_hash('Siswa123!', PASSWORD_DEFAULT);
            $stmtU = $db->prepare("INSERT INTO users (username, password, role, nama, aktif) VALUES (?, ?, 'siswa', ?, 1)");
            $stmtU->execute([$username, $password, $s['nama']]);
            $userId = $db->lastInsertId();
            
            $stmtS = $db->prepare("UPDATE siswa SET user_id = ? WHERE id = ?");
            $stmtS->execute([$userId, $s['id']]);
            
            $db->commit();
            $count++;
        } catch (Exception $e) {
            $db->rollBack();
        }
    }
    
    redirectWith(APP_URL . '/admin/siswa/index.php', 'success', "Berhasil membuat <strong>$count</strong> akun login siswa baru.");
}

// Handle Single Sync
$id = cleanInt($_GET['id'] ?? 0);
if (!$id) redirectWith(APP_URL . '/admin/siswa/', 'danger', 'ID Siswa tidak valid.');

$stmt = $db->prepare("SELECT id, nis, nama, user_id FROM siswa WHERE id = ?");
$stmt->execute([$id]);
$siswa = $stmt->fetch();

if (!$siswa) redirectWith(APP_URL . '/admin/siswa/', 'danger', 'Data siswa tidak ditemukan.');
if ($siswa['user_id']) redirectWith(APP_URL . '/admin/siswa/', 'warning', 'Siswa ini sudah memiliki akun login.');

$username = 'siswa' . $siswa['nis'];
$password = password_hash('Siswa123!', PASSWORD_DEFAULT);

try {
    $db->beginTransaction();
    
    // Insert ke tabel users
    $stmtU = $db->prepare("INSERT INTO users (username, password, role, nama, aktif) VALUES (?, ?, 'siswa', ?, 1)");
    $stmtU->execute([$username, $password, $siswa['nama']]);
    $userId = $db->lastInsertId();
    
    // Update tabel siswa
    $stmtS = $db->prepare("UPDATE siswa SET user_id = ? WHERE id = ?");
    $stmtS->execute([$userId, $siswa['id']]);
    
    $db->commit();
    writeAuditLog('INSERT_ACCOUNT', 'users', $userId, '', "Account created for student {$siswa['nama']}");
    
    redirectWith(APP_URL . '/admin/siswa/', 'success', "Akun login untuk <strong>{$siswa['nama']}</strong> berhasil dibuat.<br>Username: <code>$username</code><br>Password: <code>Siswa123!</code>");
} catch (Exception $e) {
    $db->rollBack();
    redirectWith(APP_URL . '/admin/siswa/', 'danger', 'Gagal membuat akun: ' . $e->getMessage());
}
