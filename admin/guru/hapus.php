<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
requireRole('admin'); verifyCsrf();
$db = getDB(); $id = cleanInt($_GET['id'] ?? 0);
$stmt = $db->prepare("SELECT * FROM guru WHERE id=?"); $stmt->execute([$id]); $guru = $stmt->fetch();
if (!$guru) redirectWith(APP_URL.'/admin/guru/','danger','Data tidak ditemukan.');
$db->prepare("UPDATE guru SET aktif=0 WHERE id=?")->execute([$id]);
if ($guru['user_id']) $db->prepare("UPDATE users SET aktif=0 WHERE id=?")->execute([$guru['user_id']]);
writeAuditLog('DELETE','guru',$id,json_encode($guru),'{"aktif":0}');
redirectWith(APP_URL.'/admin/guru/','success',"Guru <strong>{$guru['nama']}</strong> telah dinonaktifkan.");
