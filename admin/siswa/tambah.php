<?php
/**
 * Tambah Siswa - Admin
 */
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireRole('admin');

$pageTitle   = 'Tambah Siswa';
$activeMenu  = 'siswa';
$breadcrumbs = [
    ['label' => 'Data Siswa', 'url' => APP_URL . '/admin/siswa/'],
    ['label' => 'Tambah Siswa'],
];

$db = getDB();
$kelasList = $db->query("
    SELECT k.id, k.nama_kelas FROM kelas k 
    JOIN tahun_ajaran ta ON k.tahun_ajaran_id = ta.id AND ta.aktif = 1
    ORDER BY k.tingkat, k.nama_kelas
")->fetchAll();

$errors = [];
$data   = ['nis'=>'','nama'=>'','kelas_id'=>'','jenis_kelamin'=>'L','tempat_lahir'=>'','tanggal_lahir'=>'','alamat'=>'','telepon'=>'','nama_wali'=>'','telepon_wali'=>''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $data = [
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
    ];

    // Validasi
    if (!$data['nis'])    $errors['nis']    = 'NIS wajib diisi.';
    if (!$data['nama'])   $errors['nama']   = 'Nama wajib diisi.';
    if (!$data['kelas_id']) $errors['kelas_id'] = 'Pilih kelas.';

    // Cek NIS duplikat
    if (!isset($errors['nis'])) {
        $cek = $db->prepare("SELECT id FROM siswa WHERE nis = ?");
        $cek->execute([$data['nis']]);
        if ($cek->fetch()) $errors['nis'] = 'NIS sudah digunakan.';
    }

    // Upload foto
    $fotoPath = null;
    if (!empty($_FILES['foto']['name'])) {
        $fotoPath = uploadFoto($_FILES['foto'], 'siswa');
        if (!$fotoPath) $errors['foto'] = 'Gagal upload foto. Format: jpg/png/webp, maks 2MB.';
    }

    // Buat akun user otomatis
    $username = 'siswa' . $data['nis'];
    $password = password_hash('Siswa123!', PASSWORD_DEFAULT);

    if (empty($errors)) {
        try {
            $db->beginTransaction();

            // Insert user
            $stmtU = $db->prepare("INSERT INTO users (username,password,role,nama,aktif) VALUES (?,?,?,?,1)");
            $stmtU->execute([$username, $password, 'siswa', $data['nama']]);
            $userId = $db->lastInsertId();

            // Insert siswa
            $stmtS = $db->prepare("
                INSERT INTO siswa (user_id,nis,nama,kelas_id,jenis_kelamin,tempat_lahir,tanggal_lahir,alamat,telepon,nama_wali,telepon_wali,foto)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?)
            ");
            $stmtS->execute([
                $userId, $data['nis'], $data['nama'], $data['kelas_id'], $data['jenis_kelamin'],
                $data['tempat_lahir'] ?: null, $data['tanggal_lahir'] ?: null, $data['alamat'] ?: null,
                $data['telepon'] ?: null, $data['nama_wali'] ?: null, $data['telepon_wali'] ?: null, $fotoPath
            ]);

            $db->commit();
            writeAuditLog('INSERT', 'siswa', $db->lastInsertId(), '', json_encode($data));
            redirectWith(APP_URL . '/admin/siswa/', 'success', "Siswa <strong>{$data['nama']}</strong> berhasil ditambahkan. Akun login: <code>$username</code> / <code>Siswa123!</code>");
        } catch (Throwable $e) {
            $db->rollBack();
            $errors['db'] = 'Gagal menyimpan: ' . $e->getMessage();
        }
    }
}

include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Tambah Siswa</h1>
        <p class="page-subtitle">Akun login akan dibuat otomatis.</p>
    </div>
    <a href="<?= APP_URL ?>/admin/siswa/" class="btn btn-secondary">
        <i class="bi bi-arrow-left"></i> Kembali
    </a>
</div>

<?php if (isset($errors['db'])): ?>
<div class="alert alert-danger mb-16"><i class="bi bi-exclamation-circle-fill"></i> <?= e($errors['db']) ?></div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?= generateCsrf() ?>">

    <div class="grid" style="grid-template-columns:2fr 1fr;gap:20px;">

        <!-- Data Siswa -->
        <div class="card">
            <div class="card-header"><div class="card-title"><i class="bi bi-person text-primary"></i> Data Siswa</div></div>
            <div class="card-body">
                <div class="grid grid-2">
                    <div class="form-group">
                        <label class="form-label" for="nis">NIS <span class="required">*</span></label>
                        <input type="text" class="form-control" id="nis" name="nis" value="<?= e($data['nis']) ?>" placeholder="Nomor Induk Siswa" required>
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
                        <?php if (isset($errors['kelas_id'])): ?><div class="form-text" style="color:var(--danger);"><?= e($errors['kelas_id']) ?></div><?php endif; ?>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="nama">Nama Lengkap <span class="required">*</span></label>
                    <input type="text" class="form-control" id="nama" name="nama" value="<?= e($data['nama']) ?>" placeholder="Nama lengkap siswa" required>
                </div>

                <div class="grid grid-2">
                    <div class="form-group">
                        <label class="form-label">Jenis Kelamin</label>
                        <div style="display:flex;gap:16px;margin-top:8px;">
                            <div class="form-check">
                                <input type="radio" class="form-check-input" id="jk_l" name="jenis_kelamin" value="L" <?= $data['jenis_kelamin'] === 'L' ? 'checked' : '' ?>>
                                <label class="form-check-label" for="jk_l">Laki-laki</label>
                            </div>
                            <div class="form-check">
                                <input type="radio" class="form-check-input" id="jk_p" name="jenis_kelamin" value="P" <?= $data['jenis_kelamin'] === 'P' ? 'checked' : '' ?>>
                                <label class="form-check-label" for="jk_p">Perempuan</label>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="tanggal_lahir">Tanggal Lahir</label>
                        <input type="date" class="form-control" id="tanggal_lahir" name="tanggal_lahir" value="<?= e($data['tanggal_lahir']) ?>">
                    </div>
                </div>

                <div class="grid grid-2">
                    <div class="form-group">
                        <label class="form-label" for="tempat_lahir">Tempat Lahir</label>
                        <input type="text" class="form-control" id="tempat_lahir" name="tempat_lahir" value="<?= e($data['tempat_lahir']) ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="telepon">Telepon Siswa</label>
                        <input type="text" class="form-control" id="telepon" name="telepon" value="<?= e($data['telepon']) ?>" data-numeric>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="alamat">Alamat</label>
                    <textarea class="form-control" id="alamat" name="alamat" rows="2" placeholder="Alamat lengkap..."><?= e($data['alamat']) ?></textarea>
                </div>
            </div>
        </div>

        <!-- Kolom Kanan -->
        <div style="display:flex;flex-direction:column;gap:16px;">

            <!-- Foto -->
            <div class="card">
                <div class="card-header"><div class="card-title"><i class="bi bi-image text-primary"></i> Foto Siswa</div></div>
                <div class="card-body" style="text-align:center;">
                    <img id="preview-foto" src="#" alt="preview" style="display:none;width:100px;height:100px;object-fit:cover;border-radius:50%;margin:0 auto 12px;border:3px solid var(--border-color);">
                    <div class="avatar avatar-xl" id="avatar-placeholder" style="margin:0 auto 12px;">F</div>
                    <input type="file" class="form-control" id="foto" name="foto" accept="image/*" data-preview="preview-foto" style="font-size:12px;" onchange="document.getElementById('avatar-placeholder').style.display='none';document.getElementById('preview-foto').style.display='block';">
                    <div class="form-text">Format: JPG/PNG/WebP, maks 2MB</div>
                    <?php if (isset($errors['foto'])): ?><div class="form-text" style="color:var(--danger);"><?= e($errors['foto']) ?></div><?php endif; ?>
                </div>
            </div>

            <!-- Data Wali -->
            <div class="card">
                <div class="card-header"><div class="card-title"><i class="bi bi-people text-primary"></i> Data Wali Murid</div></div>
                <div class="card-body">
                    <div class="form-group">
                        <label class="form-label" for="nama_wali">Nama Wali</label>
                        <input type="text" class="form-control" id="nama_wali" name="nama_wali" value="<?= e($data['nama_wali']) ?>">
                    </div>
                    <div class="form-group" style="margin-bottom:0;">
                        <label class="form-label" for="telepon_wali">Telepon Wali</label>
                        <input type="text" class="form-control" id="telepon_wali" name="telepon_wali" value="<?= e($data['telepon_wali']) ?>" data-numeric>
                    </div>
                </div>
            </div>

            <!-- Info Akun -->
            <div class="card" style="border:1px solid rgba(99,102,241,0.3);background:rgba(99,102,241,0.05);">
                <div class="card-body" style="font-size:13px;">
                    <div style="font-weight:600;color:var(--primary-light);margin-bottom:8px;">
                        <i class="bi bi-info-circle"></i> Info Akun Login
                    </div>
                    <p style="color:var(--text-secondary);margin-bottom:6px;">Akun login otomatis dibuat:</p>
                    <div style="margin-bottom:4px;"><span style="color:var(--text-muted);">Username:</span> <code style="color:var(--primary-light);">siswa[NIS]</code></div>
                    <div><span style="color:var(--text-muted);">Password:</span> <code style="color:var(--primary-light);">Siswa123!</code></div>
                    <p style="color:var(--text-muted);margin-top:8px;font-size:11px;">Ganti password setelah login pertama.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Buttons -->
    <div style="margin-top:20px;display:flex;gap:10px;justify-content:flex-end;">
        <a href="<?= APP_URL ?>/admin/siswa/" class="btn btn-secondary">
            <i class="bi bi-x-circle"></i> Batal
        </a>
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-save"></i> Simpan Siswa
        </button>
    </div>
</form>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
