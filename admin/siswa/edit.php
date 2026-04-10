<?php
/**
 * Edit Siswa - Admin
 */
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireRole('admin');

$db = getDB();
$id = cleanInt($_GET['id'] ?? 0);

$stmt = $db->prepare("SELECT * FROM siswa WHERE id = ?");
$stmt->execute([$id]);
$siswa = $stmt->fetch();
if (!$siswa) redirectWith(APP_URL . '/admin/siswa/', 'danger', 'Data siswa tidak ditemukan.');

$pageTitle   = 'Edit Siswa';
$activeMenu  = 'siswa';
$breadcrumbs = [
    ['label' => 'Data Siswa', 'url' => APP_URL . '/admin/siswa/'],
    ['label' => 'Edit: ' . $siswa['nama']],
];

$kelasList = $db->query("
    SELECT k.id, k.nama_kelas FROM kelas k 
    JOIN tahun_ajaran ta ON k.tahun_ajaran_id = ta.id AND ta.aktif = 1
    ORDER BY k.tingkat, k.nama_kelas
")->fetchAll();

$errors = [];
$data   = $siswa;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $data = array_merge($siswa, [
        'nis'           => clean($_POST['nis'] ?? ''),
        'nama'          => clean($_POST['nama'] ?? ''),
        'kelas_id'      => cleanInt($_POST['kelas_id'] ?? 0),
        'jenis_kelamin' => in_array($_POST['jenis_kelamin'] ?? '', ['L','P']) ? $_POST['jenis_kelamin'] : 'L',
        'tempat_lahir'  => clean($_POST['tempat_lahir'] ?? ''),
        'tanggal_lahir' => clean($_POST['tanggal_lahir'] ?? ''),
        'alamat'        => clean($_POST['alamat'] ?? ''),
        'telepon'       => clean($_POST['telepon'] ?? ''),
        'nama_wali'     => clean($_POST['nama_wali'] ?? ''),
        'telepon_wali'  => clean($_POST['telepon_wali'] ?? ''),
        'aktif'         => cleanInt($_POST['aktif'] ?? 1),
    ]);

    if (!$data['nis'])  $errors['nis']  = 'NIS wajib diisi.';
    if (!$data['nama']) $errors['nama'] = 'Nama wajib diisi.';
    if (!$data['kelas_id']) $errors['kelas_id'] = 'Pilih kelas.';

    if (!isset($errors['nis'])) {
        $cek = $db->prepare("SELECT id FROM siswa WHERE nis = ? AND id != ?");
        $cek->execute([$data['nis'], $id]);
        if ($cek->fetch()) $errors['nis'] = 'NIS sudah digunakan siswa lain.';
    }

    $fotoPath = $siswa['foto'];
    if (!empty($_FILES['foto']['name'])) {
        $newFoto = uploadFoto($_FILES['foto'], 'siswa');
        if (!$newFoto) {
            $errors['foto'] = 'Gagal upload foto. Format: jpg/png/webp, maks 2MB.';
        } else {
            hapusFoto($siswa['foto']);
            $fotoPath = $newFoto;
        }
    }

    if (empty($errors)) {
        $old = json_encode($siswa);
        $db->prepare("
            UPDATE siswa SET nis=?,nama=?,kelas_id=?,jenis_kelamin=?,tempat_lahir=?,tanggal_lahir=?,
            alamat=?,telepon=?,nama_wali=?,telepon_wali=?,foto=?,aktif=?
            WHERE id=?
        ")->execute([
            $data['nis'], $data['nama'], $data['kelas_id'], $data['jenis_kelamin'],
            $data['tempat_lahir'] ?: null, $data['tanggal_lahir'] ?: null, $data['alamat'] ?: null,
            $data['telepon'] ?: null, $data['nama_wali'] ?: null, $data['telepon_wali'] ?: null,
            $fotoPath, $data['aktif'], $id
        ]);
        // Update nama di tabel users
        if ($siswa['user_id']) {
            $db->prepare("UPDATE users SET nama=?,aktif=? WHERE id=?")->execute([$data['nama'], $data['aktif'], $siswa['user_id']]);
        }
        writeAuditLog('UPDATE', 'siswa', $id, $old, json_encode($data));
        redirectWith(APP_URL . '/admin/siswa/', 'success', "Data siswa <strong>{$data['nama']}</strong> berhasil diperbarui.");
    }
}

include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Edit Siswa</h1>
        <p class="page-subtitle"><?= e($siswa['nama']) ?> — NIS: <?= e($siswa['nis']) ?></p>
    </div>
    <a href="<?= APP_URL ?>/admin/siswa/" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Kembali</a>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger mb-16">
    <i class="bi bi-exclamation-circle-fill"></i>
    <div><?= implode('<br>', array_map('e', $errors)) ?></div>
</div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?= generateCsrf() ?>">

    <div class="grid" style="grid-template-columns:2fr 1fr;gap:20px;">
        <div class="card">
            <div class="card-header"><div class="card-title"><i class="bi bi-person text-primary"></i> Data Siswa</div></div>
            <div class="card-body">
                <div class="grid grid-2">
                    <div class="form-group">
                        <label class="form-label" for="nis">NIS <span class="required">*</span></label>
                        <input type="text" class="form-control" id="nis" name="nis" value="<?= e($data['nis']) ?>" required>
                        <?php if (isset($errors['nis'])): ?><div class="form-text" style="color:var(--danger);"><?= e($errors['nis']) ?></div><?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="kelas_id">Kelas <span class="required">*</span></label>
                        <select class="form-select" id="kelas_id" name="kelas_id" required>
                            <option value="">-- Pilih Kelas --</option>
                            <?php foreach ($kelasList as $k): ?>
                            <option value="<?= $k['id'] ?>" <?= $data['kelas_id'] == $k['id'] ? 'selected' : '' ?>><?= e($k['nama_kelas']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label" for="nama">Nama Lengkap <span class="required">*</span></label>
                    <input type="text" class="form-control" id="nama" name="nama" value="<?= e($data['nama']) ?>" required>
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
                        <label class="form-label" for="tanggal_lahir">Tanggal Lahir</label>
                        <input type="date" class="form-control" id="tanggal_lahir" name="tanggal_lahir" value="<?= e($data['tanggal_lahir'] ?? '') ?>">
                    </div>
                </div>
                <div class="grid grid-2">
                    <div class="form-group">
                        <label class="form-label" for="tempat_lahir">Tempat Lahir</label>
                        <input type="text" class="form-control" id="tempat_lahir" name="tempat_lahir" value="<?= e($data['tempat_lahir'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="telepon">Telepon</label>
                        <input type="text" class="form-control" id="telepon" name="telepon" value="<?= e($data['telepon'] ?? '') ?>" data-numeric>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label" for="alamat">Alamat</label>
                    <textarea class="form-control" id="alamat" name="alamat" rows="2"><?= e($data['alamat'] ?? '') ?></textarea>
                </div>
                <div class="form-group" style="margin-bottom:0;">
                    <label class="form-label">Status</label>
                    <select class="form-select" name="aktif">
                        <option value="1" <?= $data['aktif'] ? 'selected' : '' ?>>Aktif</option>
                        <option value="0" <?= !$data['aktif'] ? 'selected' : '' ?>>Non-aktif</option>
                    </select>
                </div>
            </div>
        </div>

        <div style="display:flex;flex-direction:column;gap:16px;">
            <div class="card">
                <div class="card-header"><div class="card-title"><i class="bi bi-image text-primary"></i> Foto</div></div>
                <div class="card-body" style="text-align:center;">
                    <?php if ($data['foto']): ?>
                    <img src="<?= APP_URL ?>/<?= e($data['foto']) ?>" id="preview-foto" style="width:100px;height:100px;object-fit:cover;border-radius:50%;margin:0 auto 12px;display:block;border:3px solid var(--border-color);">
                    <?php else: ?>
                    <div class="avatar avatar-xl" id="avatar-placeholder" style="margin:0 auto 12px;"><?= strtoupper(substr($data['nama'], 0, 2)) ?></div>
                    <img id="preview-foto" src="#" alt="preview" style="display:none;width:100px;height:100px;object-fit:cover;border-radius:50%;margin:0 auto 12px;border:3px solid var(--border-color);">
                    <?php endif; ?>
                    <input type="file" class="form-control" id="foto" name="foto" accept="image/*" data-preview="preview-foto" style="font-size:12px;">
                    <div class="form-text">Kosongkan jika tidak ganti foto</div>
                </div>
            </div>
            <div class="card">
                <div class="card-header"><div class="card-title"><i class="bi bi-people text-primary"></i> Data Wali</div></div>
                <div class="card-body">
                    <div class="form-group">
                        <label class="form-label" for="nama_wali">Nama Wali</label>
                        <input type="text" class="form-control" id="nama_wali" name="nama_wali" value="<?= e($data['nama_wali'] ?? '') ?>">
                    </div>
                    <div class="form-group" style="margin-bottom:0;">
                        <label class="form-label" for="telepon_wali">Telepon Wali</label>
                        <input type="text" class="form-control" id="telepon_wali" name="telepon_wali" value="<?= e($data['telepon_wali'] ?? '') ?>" data-numeric>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div style="margin-top:20px;display:flex;gap:10px;justify-content:flex-end;">
        <a href="<?= APP_URL ?>/admin/siswa/" class="btn btn-secondary"><i class="bi bi-x-circle"></i> Batal</a>
        <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Simpan Perubahan</button>
    </div>
</form>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
