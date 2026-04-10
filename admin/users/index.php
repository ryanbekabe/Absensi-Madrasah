<?php
/**
 * Manajemen Users - Admin
 */
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireRole('admin');
$pageTitle   = 'Pengguna';
$activeMenu  = 'users';
$breadcrumbs = [['label' => 'Pengguna']];
$db = getDB();

// Reset password
if (isset($_GET['reset']) && isset($_GET['csrf_token'])) {
    verifyCsrf(); $uid = cleanInt($_GET['reset']);
    $u = $db->prepare("SELECT username,role FROM users WHERE id=?"); $u->execute([$uid]); $usr = $u->fetch();
    if ($usr) {
        $defPass = ['admin'=>'Admin123!','guru'=>'Guru123!','siswa'=>'Siswa123!'][$usr['role']] ?? 'Password123!';
        $hash = password_hash($defPass, PASSWORD_DEFAULT);
        $db->prepare("UPDATE users SET password=? WHERE id=?")->execute([$hash,$uid]);
        setFlash('success',"Password <strong>{$usr['username']}</strong> direset ke default: <code>$defPass</code>");
    }
    header('Location: ' . APP_URL . '/admin/users/'); exit;
}

// Toggle aktif
if (isset($_GET['toggle']) && isset($_GET['csrf_token'])) {
    verifyCsrf(); $uid = cleanInt($_GET['toggle']);
    $u = $db->prepare("SELECT aktif,nama,id FROM users WHERE id=? AND id != ?"); $u->execute([$uid,$_SESSION['user_id']]); $usr = $u->fetch();
    if ($usr) {
        $newAktif = $usr['aktif'] ? 0 : 1;
        $db->prepare("UPDATE users SET aktif=? WHERE id=?")->execute([$newAktif,$uid]);
        setFlash('success',"Akun <strong>{$usr['nama']}</strong> ".($newAktif?'diaktifkan.':'dinonaktifkan.'));
    }
    header('Location: ' . APP_URL . '/admin/users/'); exit;
}

$search = clean($_GET['q'] ?? '');
$role   = clean($_GET['role'] ?? '');
$where  = "WHERE 1=1";
$params = [];
if ($search) { $where .= " AND (nama LIKE ? OR username LIKE ?)"; $params[]="%$search%"; $params[]="%$search%"; }
if ($role)   { $where .= " AND role=?"; $params[] = $role; }

$stmt = $db->prepare("SELECT * FROM users $where ORDER BY role,nama LIMIT 100");
$stmt->execute($params); $users = $stmt->fetchAll();

include __DIR__ . '/../../includes/header.php';
?>
<div class="page-header">
    <div><h1 class="page-title">Manajemen Pengguna</h1><p class="page-subtitle">Total: <?= count($users) ?> pengguna</p></div>
</div>

<div class="card mb-16">
    <div class="card-body" style="padding:14px 20px;">
        <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;">
            <div style="flex:1;min-width:200px;"><label class="form-label">Cari</label><input type="text" name="q" class="form-control" placeholder="Nama / username..." value="<?= e($search) ?>"></div>
            <div style="min-width:130px;"><label class="form-label">Role</label>
                <select name="role" class="form-select" data-auto-submit>
                    <option value="">Semua Role</option>
                    <option value="admin" <?= $role==='admin'?'selected':'' ?>>Admin</option>
                    <option value="guru" <?= $role==='guru'?'selected':'' ?>>Guru</option>
                    <option value="siswa" <?= $role==='siswa'?'selected':'' ?>>Siswa</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> Cari</button>
            <a href="<?= APP_URL ?>/admin/users/" class="btn btn-secondary"><i class="bi bi-x-circle"></i> Reset</a>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-wrapper">
        <table class="table">
            <thead><tr><th>#</th><th>Username</th><th>Nama</th><th>Role</th><th>Email</th><th>Login Terakhir</th><th class="text-center">Status</th><th class="text-center">Aksi</th></tr></thead>
            <tbody>
                <?php foreach ($users as $i => $u): ?>
                <tr>
                    <td class="text-muted"><?= $i+1 ?></td>
                    <td><code style="font-size:12px;color:var(--primary-light);"><?= e($u['username']) ?></code></td>
                    <td style="font-weight:500;color:var(--text-primary);"><?= e($u['nama']) ?></td>
                    <td>
                        <?php $rc=['admin'=>'primary','guru'=>'success','siswa'=>'info'][$u['role']]??'secondary'; ?>
                        <span class="badge bg-<?= $rc ?>"><?= ucfirst(e($u['role'])) ?></span>
                    </td>
                    <td style="font-size:12px;color:var(--text-muted);"><?= e($u['email']??'-') ?></td>
                    <td style="font-size:11px;color:var(--text-muted);"><?= $u['last_login']?formatTanggal($u['last_login'],'d/m/Y H:i'):'Belum pernah' ?></td>
                    <td class="text-center">
                        <?= $u['aktif'] ? '<span class="badge bg-success">Aktif</span>' : '<span class="badge bg-secondary">Non-aktif</span>' ?>
                    </td>
                    <td>
                        <div class="actions">
                            <a href="?reset=<?= $u['id'] ?>&csrf_token=<?= generateCsrf() ?>" class="btn btn-sm btn-warning"
                               data-confirm="Reset password <?= e($u['username']) ?> ke password default?"
                               data-tooltip="Reset Password"><i class="bi bi-key"></i></a>
                            <?php if ($u['id'] !== (int)$_SESSION['user_id']): ?>
                            <a href="?toggle=<?= $u['id'] ?>&csrf_token=<?= generateCsrf() ?>" class="btn btn-sm <?= $u['aktif']?'btn-danger':'btn-success' ?>"
                               data-confirm="<?= $u['aktif']?'Nonaktifkan':'Aktifkan' ?> akun <?= e($u['username']) ?>?"
                               data-tooltip="<?= $u['aktif']?'Nonaktifkan':'Aktifkan' ?>">
                                <i class="bi bi-<?= $u['aktif']?'person-slash':'person-check' ?>"></i>
                            </a>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($users)): ?>
                <tr><td colspan="8"><div class="empty-state"><div class="empty-icon"><i class="bi bi-people"></i></div><p class="empty-title">Tidak Ada Pengguna</p></div></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
