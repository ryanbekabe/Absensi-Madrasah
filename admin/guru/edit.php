<?php
/**
 * Edit Guru - Admin
 */
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireRole('admin');
$db = getDB();
$id = cleanInt($_GET['id'] ?? 0);
$stmt = $db->prepare("SELECT * FROM guru WHERE id = ?"); $stmt->execute([$id]);
$guru = $stmt->fetch();
if (!$guru) redirectWith(APP_URL.'/admin/guru/','danger','Data guru tidak ditemukan.');

$pageTitle   = 'Edit Guru';
$activeMenu  = 'guru';
$breadcrumbs = [['label'=>'Data Guru','url'=>APP_URL.'/admin/guru/'],['label'=>'Edit: '.$guru['nama']]];
$errors = []; $data = $guru;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $data = array_merge($guru, [
        'nip'           => clean($_POST['nip']??''),
        'nama'          => clean($_POST['nama']??''),
        'jenis_kelamin' => in_array($_POST['jenis_kelamin']??'',['L','P'])?$_POST['jenis_kelamin']:'L',
        'tempat_lahir'  => clean($_POST['tempat_lahir']??''),
        'tanggal_lahir' => clean($_POST['tanggal_lahir']??''),
        'status'        => clean($_POST['status']??'GTT'),
        'telepon'       => clean($_POST['telepon']??''),
        'email'         => clean($_POST['email']??''),
        'alamat'        => clean($_POST['alamat']??''),
        'aktif'         => cleanInt($_POST['aktif']??1),
    ]);
    if (!$data['nama']) $errors['nama']='Nama wajib diisi.';
    if ($data['nip']) {
        $c=$db->prepare("SELECT id FROM guru WHERE nip=? AND id!=?"); $c->execute([$data['nip'],$id]);
        if ($c->fetch()) $errors['nip']='NIP sudah digunakan guru lain.';
    }
    $fotoPath = $guru['foto'];
    if (!empty($_FILES['foto']['name'])) {
        $nf = uploadFoto($_FILES['foto'],'guru');
        if (!$nf) $errors['foto']='Gagal upload foto.';
        else { hapusFoto($guru['foto']); $fotoPath = $nf; }
    }
    if (empty($errors)) {
        $old = json_encode($guru);
        $db->prepare("UPDATE guru SET nip=?,nama=?,jenis_kelamin=?,tempat_lahir=?,tanggal_lahir=?,status=?,telepon=?,email=?,alamat=?,foto=?,aktif=? WHERE id=?")
           ->execute([$data['nip']?:null,$data['nama'],$data['jenis_kelamin'],$data['tempat_lahir']?:null,$data['tanggal_lahir']?:null,$data['status'],$data['telepon']?:null,$data['email']?:null,$data['alamat']?:null,$fotoPath,$data['aktif'],$id]);
        if ($guru['user_id']) $db->prepare("UPDATE users SET nama=?,email=?,aktif=? WHERE id=?")->execute([$data['nama'],$data['email']?:null,$data['aktif'],$guru['user_id']]);
        writeAuditLog('UPDATE','guru',$id,$old,json_encode($data));
        redirectWith(APP_URL.'/admin/guru/','success',"Data guru <strong>{$data['nama']}</strong> berhasil diperbarui.");
    }
}
include __DIR__ . '/../../includes/header.php';
?>
<div class="page-header">
    <div><h1 class="page-title">Edit Guru</h1><p class="page-subtitle"><?= e($guru['nama']) ?></p></div>
    <a href="<?= APP_URL ?>/admin/guru/" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Kembali</a>
</div>
<?php if(!empty($errors)): ?><div class="alert alert-danger mb-16"><i class="bi bi-exclamation-circle-fill"></i><div><?= implode('<br>',array_map('e',$errors)) ?></div></div><?php endif; ?>
<form method="POST" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?= generateCsrf() ?>">
    <div class="grid" style="grid-template-columns:2fr 1fr;gap:20px;">
        <div class="card">
            <div class="card-header"><div class="card-title"><i class="bi bi-person-badge text-primary"></i> Data Guru</div></div>
            <div class="card-body">
                <div class="grid grid-2">
                    <div class="form-group"><label class="form-label">NIP</label><input type="text" class="form-control" name="nip" value="<?= e($data['nip']??'') ?>"></div>
                    <div class="form-group"><label class="form-label">Status</label>
                        <select class="form-select" name="status"><?php foreach(['PNS','GTT','Honorer','Kontrak'] as $st): ?><option value="<?= $st ?>" <?= ($data['status']??'')===$st?'selected':'' ?>><?= $st ?></option><?php endforeach; ?></select>
                    </div>
                </div>
                <div class="form-group"><label class="form-label">Nama Lengkap <span class="required">*</span></label><input type="text" class="form-control" name="nama" value="<?= e($data['nama']) ?>" required></div>
                <div class="grid grid-2">
                    <div class="form-group">
                        <label class="form-label">Jenis Kelamin</label>
                        <div style="display:flex;gap:16px;margin-top:8px;">
                            <label class="form-check"><input type="radio" class="form-check-input" name="jenis_kelamin" value="L" <?= ($data['jenis_kelamin']??'')=='L'?'checked':'' ?>> Laki-laki</label>
                            <label class="form-check"><input type="radio" class="form-check-input" name="jenis_kelamin" value="P" <?= ($data['jenis_kelamin']??'')=='P'?'checked':'' ?>> Perempuan</label>
                        </div>
                    </div>
                    <div class="form-group"><label class="form-label">Tanggal Lahir</label><input type="date" class="form-control" name="tanggal_lahir" value="<?= e($data['tanggal_lahir']??'') ?>"></div>
                </div>
                <div class="grid grid-2">
                    <div class="form-group"><label class="form-label">Tempat Lahir</label><input type="text" class="form-control" name="tempat_lahir" value="<?= e($data['tempat_lahir']??'') ?>"></div>
                    <div class="form-group"><label class="form-label">Telepon</label><input type="text" class="form-control" name="telepon" value="<?= e($data['telepon']??'') ?>" data-numeric></div>
                </div>
                <div class="form-group"><label class="form-label">Email</label><input type="email" class="form-control" name="email" value="<?= e($data['email']??'') ?>"></div>
                <div class="form-group"><label class="form-label">Alamat</label><textarea class="form-control" name="alamat" rows="2"><?= e($data['alamat']??'') ?></textarea></div>
                <div class="form-group" style="margin-bottom:0;"><label class="form-label">Status Akun</label>
                    <select class="form-select" name="aktif"><option value="1" <?= ($data['aktif']??1)?'selected':'' ?>>Aktif</option><option value="0" <?= !($data['aktif']??1)?'selected':'' ?>>Non-aktif</option></select>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-header"><div class="card-title"><i class="bi bi-image text-primary"></i> Foto</div></div>
            <div class="card-body" style="text-align:center;">
                <?php if($data['foto']): ?>
                <img src="<?= APP_URL ?>/<?= e($data['foto']) ?>" id="preview-foto" style="width:100px;height:100px;object-fit:cover;border-radius:50%;margin:0 auto 12px;display:block;border:3px solid var(--border-color);">
                <?php else: ?>
                <div class="avatar avatar-xl" style="margin:0 auto 12px;"><?= strtoupper(substr($data['nama'],0,2)) ?></div>
                <img id="preview-foto" src="#" style="display:none;width:100px;height:100px;object-fit:cover;border-radius:50%;margin:0 auto 12px;border:3px solid var(--border-color);">
                <?php endif; ?>
                <input type="file" class="form-control" name="foto" accept="image/*" data-preview="preview-foto" style="font-size:12px;">
                <div class="form-text">Kosongkan jika tidak ganti foto</div>
            </div>
        </div>
    </div>
    <div style="margin-top:20px;display:flex;gap:10px;justify-content:flex-end;">
        <a href="<?= APP_URL ?>/admin/guru/" class="btn btn-secondary"><i class="bi bi-x-circle"></i> Batal</a>
        <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Simpan Perubahan</button>
    </div>
</form>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
