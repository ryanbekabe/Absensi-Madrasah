<?php
/**
 * Profil Pengguna - Semua Role
 */
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

requireLogin();
$pageTitle   = 'Profil Saya';
$activeMenu  = 'profile';
$breadcrumbs = [['label' => 'Profil Saya']];
$db   = getDB();
$user = currentUser();
$tab  = clean($_GET['tab'] ?? 'profil');
$errors = [];

// Proses update profil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profil'])) {
    verifyCsrf();
    $nama  = clean($_POST['nama'] ?? '');
    $email = clean($_POST['email'] ?? '');

    if (!$nama) $errors['nama'] = 'Nama tidak boleh kosong.';
    if (empty($errors)) {
        $fotoPath = $user['foto'];
        if (!empty($_FILES['foto']['name'])) {
            $nf = uploadFoto($_FILES['foto'], 'users');
            if (!$nf) $errors['foto'] = 'Gagal upload foto.';
            else { hapusFoto($user['foto']); $fotoPath = $nf; }
        }
        if (empty($errors)) {
            $db->prepare("UPDATE users SET nama=?,email=?,foto=? WHERE id=?")->execute([$nama,$email?:null,$fotoPath,$user['id']]);
            $_SESSION['nama'] = $nama; $_SESSION['foto'] = $fotoPath;
            redirectWith(APP_URL.'/profile.php','success','Profil berhasil diperbarui.');
        }
    }
}

// Proses ganti password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ganti_password'])) {
    verifyCsrf();
    $tab = 'password';
    $oldPass = $_POST['old_password'] ?? '';
    $newPass = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    $stmtU = $db->prepare("SELECT password FROM users WHERE id=?"); $stmtU->execute([$user['id']]); $u = $stmtU->fetch();

    if (!password_verify($oldPass, $u['password'])) { $errors['old_password'] = 'Password lama salah.'; }
    if (strlen($newPass) < 6) { $errors['new_password'] = 'Password baru minimal 6 karakter.'; }
    if ($newPass !== $confirm) { $errors['confirm_password'] = 'Konfirmasi password tidak cocok.'; }

    if (empty($errors)) {
        $hash = password_hash($newPass, PASSWORD_DEFAULT);
        $db->prepare("UPDATE users SET password=? WHERE id=?")->execute([$hash, $user['id']]);
        redirectWith(APP_URL.'/profile.php?tab=password','success','Password berhasil diubah.');
    }
}

// Ambil data lengkap
$stmtU = $db->prepare("SELECT * FROM users WHERE id=?"); $stmtU->execute([$user['id']]); $userData = $stmtU->fetch();

// Cek data tambahan berdasarkan role
$extraData = null;
if ($user['role'] === 'guru') {
    $g = $db->prepare("SELECT * FROM guru WHERE user_id=?"); $g->execute([$user['id']]); $extraData = $g->fetch();
} elseif ($user['role'] === 'siswa') {
    $s = $db->prepare("SELECT s.*,k.nama_kelas FROM siswa s JOIN kelas k ON s.kelas_id=k.id WHERE s.user_id=? LIMIT 1"); $s->execute([$user['id']]); $extraData = $s->fetch();
}

$words = explode(' ', $userData['nama']??'U');
$initials = strtoupper(substr($words[0],0,1).(isset($words[1])?substr($words[1],0,1):''));

include __DIR__ . '/includes/header.php';
?>
<div class="page-header">
    <div><h1 class="page-title">Profil Saya</h1><p class="page-subtitle">Kelola informasi akun Anda</p></div>
</div>

<div class="grid" style="grid-template-columns:280px 1fr;gap:20px;">
    <!-- Left: Avatar + info -->
    <div style="display:flex;flex-direction:column;gap:16px;">
        <div class="card">
            <div class="card-body" style="text-align:center;padding:28px 20px;">
                <div class="avatar" style="width:80px;height:80px;font-size:28px;margin:0 auto 14px;">
                    <?php if ($userData['foto']): ?><img src="<?= APP_URL ?>/<?= e($userData['foto']) ?>" alt="foto" style="width:100%;height:100%;object-fit:cover;border-radius:50%;"><?php else: ?><?= e($initials) ?><?php endif; ?>
                </div>
                <div style="font-size:17px;font-weight:700;color:var(--text-primary);"><?= e($userData['nama']) ?></div>
                <div style="font-size:12px;color:var(--primary-light);text-transform:capitalize;margin-top:4px;"><?= e($userData['role']) ?></div>
                <div style="font-size:12px;color:var(--text-muted);margin-top:4px;">@<?= e($userData['username']) ?></div>
                <?php if ($extraData && isset($extraData['nama_kelas'])): ?>
                <div style="margin-top:10px;"><span class="badge bg-primary">Kelas <?= e($extraData['nama_kelas']) ?></span></div>
                <?php endif; ?>
                <?php if ($extraData && isset($extraData['nip'])): ?>
                <div style="font-size:11px;color:var(--text-muted);margin-top:8px;">NIP: <?= e($extraData['nip']??'-') ?></div>
                <?php endif; ?>
                <?php if ($extraData && isset($extraData['nis'])): ?>
                <div style="font-size:11px;color:var(--text-muted);margin-top:8px;">NIS: <?= e($extraData['nis']??'-') ?></div>
                <?php endif; ?>
            </div>
        </div>
        <!-- Nav tabs -->
        <div class="card" style="overflow:hidden;">
            <a href="?tab=profil" style="display:flex;align-items:center;gap:10px;padding:12px 16px;color:<?= $tab==='profil'?'var(--primary-light)':'var(--text-secondary)' ?>;text-decoration:none;background:<?= $tab==='profil'?'rgba(99,102,241,0.1)':'' ?>;border-left:3px solid <?= $tab==='profil'?'var(--primary)':'transparent' ?>;font-size:13px;font-weight:500;transition:var(--transition);">
                <i class="bi bi-person"></i> Informasi Profil
            </a>
            <a href="?tab=password" style="display:flex;align-items:center;gap:10px;padding:12px 16px;color:<?= $tab==='password'?'var(--primary-light)':'var(--text-secondary)' ?>;text-decoration:none;background:<?= $tab==='password'?'rgba(99,102,241,0.1)':'' ?>;border-left:3px solid <?= $tab==='password'?'var(--primary)':'transparent' ?>;font-size:13px;font-weight:500;transition:var(--transition);">
                <i class="bi bi-shield-lock"></i> Ganti Password
            </a>
        </div>
    </div>

    <!-- Right: Form -->
    <div class="card">
        <?php if ($tab === 'profil'): ?>
        <div class="card-header"><div class="card-title"><i class="bi bi-person text-primary"></i> Informasi Profil</div></div>
        <div class="card-body">
            <?php if (!empty($errors)): ?><div class="alert alert-danger mb-16"><i class="bi bi-exclamation-circle-fill"></i><div><?= implode('<br>',array_map('e',$errors)) ?></div></div><?php endif; ?>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= generateCsrf() ?>">
                <input type="hidden" name="update_profil" value="1">
                <div class="form-group">
                    <label class="form-label">Username</label>
                    <input type="text" class="form-control" value="<?= e($userData['username']) ?>" disabled style="opacity:0.5;">
                    <div class="form-text">Username tidak dapat diubah.</div>
                </div>
                <div class="form-group">
                    <label class="form-label">Nama Lengkap <span class="required">*</span></label>
                    <input type="text" class="form-control" name="nama" value="<?= e($userData['nama']) ?>" required>
                    <?php if (isset($errors['nama'])): ?><div class="form-text" style="color:var(--danger);"><?= e($errors['nama']) ?></div><?php endif; ?>
                </div>
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" class="form-control" name="email" value="<?= e($userData['email']??'') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Role</label>
                    <input type="text" class="form-control" value="<?= ucfirst(e($userData['role'])) ?>" disabled style="opacity:0.5;text-transform:capitalize;">
                </div>
                <div class="form-group">
                    <label class="form-label">Foto Profil</label>
                    <div style="display:flex;align-items:center;gap:16px;margin-bottom:10px;">
                        <div class="avatar avatar-lg">
                            <?php if ($userData['foto']): ?><img src="<?= APP_URL ?>/<?= e($userData['foto']) ?>" id="preview-foto" alt="foto" style="width:100%;height:100%;object-fit:cover;border-radius:50%;"><?php else: ?><?= e($initials) ?><?php endif; ?>
                        </div>
                        <input type="file" class="form-control" name="foto" accept="image/*" data-preview="preview-foto" style="flex:1;">
                    </div>
                    <div class="form-text">JPG/PNG/WebP, maks 2MB. Kosongkan jika tidak ganti.</div>
                </div>
                <div style="display:flex;justify-content:flex-end;"><button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Simpan Profil</button></div>
            </form>
        </div>

        <?php else: ?>
        <div class="card-header"><div class="card-title"><i class="bi bi-shield-lock text-primary"></i> Ganti Password</div></div>
        <div class="card-body">
            <?php if (!empty($errors)): ?><div class="alert alert-danger mb-16"><i class="bi bi-exclamation-circle-fill"></i><div><?= implode('<br>',array_map('e',$errors)) ?></div></div><?php endif; ?>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= generateCsrf() ?>">
                <input type="hidden" name="ganti_password" value="1">
                <div class="form-group">
                    <label class="form-label">Password Lama <span class="required">*</span></label>
                    <input type="password" class="form-control" name="old_password" required autocomplete="current-password">
                    <?php if (isset($errors['old_password'])): ?><div class="form-text" style="color:var(--danger);"><?= e($errors['old_password']) ?></div><?php endif; ?>
                </div>
                <div class="form-group">
                    <label class="form-label">Password Baru <span class="required">*</span></label>
                    <input type="password" class="form-control" name="new_password" required minlength="6" autocomplete="new-password">
                    <?php if (isset($errors['new_password'])): ?><div class="form-text" style="color:var(--danger);"><?= e($errors['new_password']) ?></div><?php endif; ?>
                    <div class="form-text">Minimal 6 karakter.</div>
                </div>
                <div class="form-group" style="margin-bottom:24px;">
                    <label class="form-label">Konfirmasi Password Baru <span class="required">*</span></label>
                    <input type="password" class="form-control" name="confirm_password" required autocomplete="new-password">
                    <?php if (isset($errors['confirm_password'])): ?><div class="form-text" style="color:var(--danger);"><?= e($errors['confirm_password']) ?></div><?php endif; ?>
                </div>
                <div style="display:flex;justify-content:flex-end;"><button type="submit" class="btn btn-primary"><i class="bi bi-shield-check"></i> Ubah Password</button></div>
            </form>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
