<?php
/**
 * Tambah Guru - Admin
 */
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireRole('admin');
$pageTitle   = 'Tambah Guru';
$activeMenu  = 'guru';
$breadcrumbs = [['label'=>'Data Guru','url'=>APP_URL.'/admin/guru/'],['label'=>'Tambah Guru']];
$db = getDB();
$errors = [];
$data = ['nip'=>'','nama'=>'','jenis_kelamin'=>'L','tempat_lahir'=>'','tanggal_lahir'=>'','status'=>'GTT','telepon'=>'','email'=>'','alamat'=>''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $data = [
        'nip'           => clean($_POST['nip'] ?? ''),
        'nama'          => clean($_POST['nama'] ?? ''),
        'jenis_kelamin' => in_array($_POST['jenis_kelamin']??'',['L','P'])?$_POST['jenis_kelamin']:'L',
        'tempat_lahir'  => clean($_POST['tempat_lahir'] ?? ''),
        'tanggal_lahir' => clean($_POST['tanggal_lahir'] ?? ''),
        'status'        => clean($_POST['status'] ?? 'GTT'),
        'telepon'       => clean($_POST['telepon'] ?? ''),
        'email'         => clean($_POST['email'] ?? ''),
        'alamat'        => clean($_POST['alamat'] ?? ''),
    ];

    if (!$data['nama']) $errors['nama'] = 'Nama wajib diisi.';
    if ($data['nip']) {
        $c = $db->prepare("SELECT id FROM guru WHERE nip=?"); $c->execute([$data['nip']]);
        if ($c->fetch()) $errors['nip'] = 'NIP sudah terdaftar.';
    }

    $fotoPath = null;
    if (!empty($_FILES['foto']['name'])) {
        $fotoPath = uploadFoto($_FILES['foto'],'guru');
        if (!$fotoPath) $errors['foto'] = 'Gagal upload foto.';
    }

    // Buat username: guru + 2 huruf nama + angka random
    $baseUN  = 'guru' . strtolower(preg_replace('/[^a-zA-Z0-9]/', '', explode(' ',$data['nama'])[0]));
    $username = $baseUN;
    $counter  = 1;
    while (true) {
        $cx = $db->prepare("SELECT id FROM users WHERE username=?"); $cx->execute([$username]);
        if (!$cx->fetch()) break;
        $username = $baseUN . $counter++;
    }
    $password = password_hash('Guru123!', PASSWORD_DEFAULT);

    if (empty($errors)) {
        $db->beginTransaction();
        try {
            $db->prepare("INSERT INTO users (username,password,role,nama,email,aktif) VALUES (?,?,?,?,?,1)")
               ->execute([$username, $password, 'guru', $data['nama'], $data['email']?:null]);
            $uid = $db->lastInsertId();
            $db->prepare("INSERT INTO guru (user_id,nip,nama,jenis_kelamin,tempat_lahir,tanggal_lahir,status,telepon,email,alamat,foto) VALUES (?,?,?,?,?,?,?,?,?,?,?)")
               ->execute([$uid,$data['nip']?:null,$data['nama'],$data['jenis_kelamin'],
                 $data['tempat_lahir']?:null,$data['tanggal_lahir']?:null,$data['status'],
                 $data['telepon']?:null,$data['email']?:null,$data['alamat']?:null,$fotoPath]);
            $db->commit();
            writeAuditLog('INSERT','guru',$db->lastInsertId(),'',json_encode($data));
            redirectWith(APP_URL.'/admin/guru/','success',"Guru <strong>{$data['nama']}</strong> ditambahkan. Login: <code>$username</code> / <code>Guru123!</code>");
        } catch (Throwable $e) { $db->rollBack(); $errors['db']=$e->getMessage(); }
    }
}
include __DIR__ . '/../../includes/header.php';
?>
<div class="page-header">
    <div><h1 class="page-title">Tambah Guru</h1></div>
    <a href="<?= APP_URL ?>/admin/guru/" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Kembali</a>
</div>
<?php if(isset($errors['db'])): ?><div class="alert alert-danger mb-16"><i class="bi bi-exclamation-circle-fill"></i> <?= e($errors['db']) ?></div><?php endif; ?>

<form method="POST" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?= generateCsrf() ?>">
    <div class="grid" style="grid-template-columns:2fr 1fr;gap:20px;">
        <div class="card">
            <div class="card-header"><div class="card-title"><i class="bi bi-person-badge text-primary"></i> Data Guru</div></div>
            <div class="card-body">
                <div class="grid grid-2">
                    <div class="form-group">
                        <label class="form-label">NIP</label>
                        <input type="text" class="form-control" name="nip" value="<?= e($data['nip']) ?>" placeholder="Nomor Induk Pegawai">
                        <?php if(isset($errors['nip'])): ?><div class="form-text" style="color:var(--danger);"><?= e($errors['nip']) ?></div><?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status Kepegawaian</label>
                        <select class="form-select" name="status">
                            <?php foreach(['PNS','GTT','Honorer','Kontrak'] as $st): ?>
                            <option value="<?= $st ?>" <?= $data['status']===$st?'selected':'' ?>><?= $st ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Nama Lengkap <span class="required">*</span></label>
                    <input type="text" class="form-control" name="nama" value="<?= e($data['nama']) ?>" required>
                    <?php if(isset($errors['nama'])): ?><div class="form-text" style="color:var(--danger);"><?= e($errors['nama']) ?></div><?php endif; ?>
                </div>
                <div class="grid grid-2">
                    <div class="form-group">
                        <label class="form-label">Jenis Kelamin</label>
                        <div style="display:flex;gap:16px;margin-top:8px;">
                            <label class="form-check"><input type="radio" class="form-check-input" name="jenis_kelamin" value="L" <?= $data['jenis_kelamin']==='L'?'checked':'' ?>> Laki-laki</label>
                            <label class="form-check"><input type="radio" class="form-check-input" name="jenis_kelamin" value="P" <?= $data['jenis_kelamin']==='P'?'checked':'' ?>> Perempuan</label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tanggal Lahir</label>
                        <input type="date" class="form-control" name="tanggal_lahir" value="<?= e($data['tanggal_lahir']) ?>">
                    </div>
                </div>
                <div class="grid grid-2">
                    <div class="form-group">
                        <label class="form-label">Tempat Lahir</label>
                        <input type="text" class="form-control" name="tempat_lahir" value="<?= e($data['tempat_lahir']) ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Telepon</label>
                        <input type="text" class="form-control" name="telepon" value="<?= e($data['telepon']) ?>" data-numeric>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" class="form-control" name="email" value="<?= e($data['email']) ?>">
                </div>
                <div class="form-group" style="margin-bottom:0;">
                    <label class="form-label">Alamat</label>
                    <textarea class="form-control" name="alamat" rows="2"><?= e($data['alamat']) ?></textarea>
                </div>
            </div>
        </div>
        <div style="display:flex;flex-direction:column;gap:16px;">
            <div class="card">
                <div class="card-header"><div class="card-title"><i class="bi bi-image text-primary"></i> Foto</div></div>
                <div class="card-body" style="text-align:center;">
                    <div class="avatar avatar-xl" id="avatar-placeholder" style="margin:0 auto 12px;">G</div>
                    <img id="preview-foto" src="#" alt="preview" style="display:none;width:100px;height:100px;object-fit:cover;border-radius:50%;margin:0 auto 12px;border:3px solid var(--border-color);">
                    <input type="file" class="form-control" name="foto" accept="image/*" data-preview="preview-foto" style="font-size:12px;" onchange="document.getElementById('avatar-placeholder').style.display='none';document.getElementById('preview-foto').style.display='block';">
                    <div class="form-text">JPG/PNG/WebP, maks 2MB</div>
                </div>
            </div>
            <div class="card" style="border:1px solid rgba(99,102,241,0.3);background:rgba(99,102,241,0.05);">
                <div class="card-body" style="font-size:13px;">
                    <div style="font-weight:600;color:var(--primary-light);margin-bottom:8px;"><i class="bi bi-info-circle"></i> Akun Login</div>
                    <div style="color:var(--text-secondary);margin-bottom:6px;">Dibuat otomatis:</div>
                    <div><span style="color:var(--text-muted);">Username:</span> <code style="color:var(--primary-light);">guru[nama]</code></div>
                    <div><span style="color:var(--text-muted);">Password:</span> <code style="color:var(--primary-light);">Guru123!</code></div>
                </div>
            </div>
        </div>
    </div>
    <div style="margin-top:20px;display:flex;gap:10px;justify-content:flex-end;">
        <a href="<?= APP_URL ?>/admin/guru/" class="btn btn-secondary"><i class="bi bi-x-circle"></i> Batal</a>
        <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Simpan Guru</button>
    </div>
</form>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
