<?php
/**
 * CRUD Kelas - Admin
 */
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireRole('admin');
$pageTitle = 'Data Kelas'; $activeMenu = 'kelas';
$breadcrumbs = [['label'=>'Data Kelas']];
$db = getDB();

// Handle POST (Tambah/Edit)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $act        = clean($_POST['act'] ?? 'tambah');
    $id         = cleanInt($_POST['id'] ?? 0);
    $nama_kelas = clean($_POST['nama_kelas'] ?? '');
    $tingkat    = cleanInt($_POST['tingkat'] ?? 7);
    $wali_id    = cleanInt($_POST['wali_kelas_id'] ?? 0) ?: null;
    $ta_id      = cleanInt($_POST['tahun_ajaran_id'] ?? 0);
    $ket        = clean($_POST['keterangan'] ?? '');

    if ($nama_kelas && $ta_id) {
        if ($act === 'edit' && $id) {
            $db->prepare("UPDATE kelas SET nama_kelas=?,tingkat=?,wali_kelas_id=?,tahun_ajaran_id=?,keterangan=? WHERE id=?")
               ->execute([$nama_kelas,$tingkat,$wali_id,$ta_id,$ket?:null,$id]);
            setFlash('success',"Kelas <strong>$nama_kelas</strong> berhasil diperbarui.");
        } else {
            $db->prepare("INSERT INTO kelas (nama_kelas,tingkat,wali_kelas_id,tahun_ajaran_id,keterangan) VALUES (?,?,?,?,?)")
               ->execute([$nama_kelas,$tingkat,$wali_id,$ta_id,$ket?:null]);
            setFlash('success',"Kelas <strong>$nama_kelas</strong> berhasil ditambahkan.");
        }
    } else {
        setFlash('danger','Nama kelas dan tahun ajaran wajib diisi.');
    }
    header('Location: ' . APP_URL . '/admin/kelas/'); exit;
}

// Handle hapus
if (isset($_GET['hapus']) && isset($_GET['csrf_token'])) {
    verifyCsrf();
    $id = cleanInt($_GET['hapus']);
    $k  = $db->prepare("SELECT nama_kelas FROM kelas WHERE id=?"); $k->execute([$id]); $kls = $k->fetch();
    if ($kls) { $db->prepare("DELETE FROM kelas WHERE id=?")->execute([$id]); setFlash('success',"Kelas <strong>{$kls['nama_kelas']}</strong> dihapus."); }
    header('Location: ' . APP_URL . '/admin/kelas/'); exit;
}

// Data
$taList    = $db->query("SELECT * FROM tahun_ajaran ORDER BY aktif DESC, nama DESC")->fetchAll();
$guruList  = $db->query("SELECT id,nama FROM guru WHERE aktif=1 ORDER BY nama")->fetchAll();
$ta_filter = cleanInt($_GET['ta_id'] ?? 0);
if (!$ta_filter) { foreach ($taList as $t) { if ($t['aktif']) { $ta_filter = $t['id']; break; } } }

$stmt = $db->prepare("
    SELECT k.*, ta.nama AS tahun_ajaran, ta.semester,
           g.nama AS wali_kelas_nama,
           (SELECT COUNT(*) FROM siswa WHERE kelas_id=k.id AND aktif=1) AS jml_siswa
    FROM kelas k
    JOIN tahun_ajaran ta ON k.tahun_ajaran_id = ta.id
    LEFT JOIN guru g ON k.wali_kelas_id = g.id
    WHERE k.tahun_ajaran_id = ?
    ORDER BY k.tingkat, k.nama_kelas
");
$stmt->execute([$ta_filter]);
$kelasList = $stmt->fetchAll();

// Edit data
$editData = null;
if (isset($_GET['edit'])) {
    $e = $db->prepare("SELECT * FROM kelas WHERE id=?"); $e->execute([cleanInt($_GET['edit'])]); $editData = $e->fetch();
}

include __DIR__ . '/../../includes/header.php';
?>
<div class="page-header">
    <div><h1 class="page-title">Data Kelas</h1><p class="page-subtitle">Kelola kelas per tahun ajaran</p></div>
</div>

<div class="grid" style="grid-template-columns:1fr 2fr;gap:20px;">

    <!-- Form Tambah/Edit -->
    <div class="card">
        <div class="card-header"><div class="card-title"><i class="bi bi-door-open text-primary"></i> <?= $editData ? 'Edit Kelas' : 'Tambah Kelas' ?></div></div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= generateCsrf() ?>">
                <input type="hidden" name="act" value="<?= $editData ? 'edit' : 'tambah' ?>">
                <?php if ($editData): ?><input type="hidden" name="id" value="<?= $editData['id'] ?>"><?php endif; ?>

                <div class="form-group">
                    <label class="form-label">Tahun Ajaran <span class="required">*</span></label>
                    <select class="form-select" name="tahun_ajaran_id" required>
                        <option value="">-- Pilih --</option>
                        <?php foreach ($taList as $ta): ?>
                        <option value="<?= $ta['id'] ?>" <?= ($editData['tahun_ajaran_id']??$ta_filter)==$ta['id']?'selected':'' ?>>
                            <?= e($ta['nama']) ?> - <?= e($ta['semester']) ?> <?= $ta['aktif']?'(Aktif)':'' ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Nama Kelas <span class="required">*</span></label>
                    <input type="text" class="form-control" name="nama_kelas" value="<?= e($editData['nama_kelas']??'') ?>" placeholder="Contoh: 7A, 8B, 9C" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Tingkat</label>
                    <select class="form-select" name="tingkat">
                        <?php foreach([7,8,9,10,11,12] as $t): ?>
                        <option value="<?= $t ?>" <?= ($editData['tingkat']??7)==$t?'selected':'' ?>>Kelas <?= $t ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Wali Kelas</label>
                    <select class="form-select" name="wali_kelas_id">
                        <option value="">-- Belum Ditentukan --</option>
                        <?php foreach ($guruList as $g): ?>
                        <option value="<?= $g['id'] ?>" <?= ($editData['wali_kelas_id']??0)==$g['id']?'selected':'' ?>><?= e($g['nama']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" style="margin-bottom:0;">
                    <label class="form-label">Keterangan</label>
                    <input type="text" class="form-control" name="keterangan" value="<?= e($editData['keterangan']??'') ?>" placeholder="Opsional">
                </div>
                <div style="display:flex;gap:8px;margin-top:16px;">
                    <button type="submit" class="btn btn-primary" style="flex:1;">
                        <i class="bi bi-save"></i> <?= $editData ? 'Perbarui' : 'Simpan' ?>
                    </button>
                    <?php if ($editData): ?>
                    <a href="<?= APP_URL ?>/admin/kelas/" class="btn btn-secondary"><i class="bi bi-x-circle"></i></a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Daftar Kelas -->
    <div class="card">
        <div class="card-header">
            <div class="card-title"><i class="bi bi-list-ul text-primary"></i> Daftar Kelas</div>
            <form method="GET" style="display:flex;gap:8px;">
                <select name="ta_id" class="form-select" style="font-size:12px;padding:5px 30px 5px 10px;" data-auto-submit>
                    <?php foreach ($taList as $ta): ?>
                    <option value="<?= $ta['id'] ?>" <?= $ta_filter==$ta['id']?'selected':'' ?>><?= e($ta['nama']) ?> - <?= e($ta['semester']) ?></option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>
        <div class="table-wrapper">
            <table class="table">
                <thead><tr><th>Nama Kelas</th><th>Tingkat</th><th>Wali Kelas</th><th class="text-center">Siswa</th><th class="text-center">Aksi</th></tr></thead>
                <tbody>
                    <?php foreach ($kelasList as $k): ?>
                    <tr>
                        <td><strong style="color:var(--text-primary);"><?= e($k['nama_kelas']) ?></strong>
                            <?php if ($k['keterangan']): ?><br><small style="color:var(--text-muted);"><?= e($k['keterangan']) ?></small><?php endif; ?>
                        </td>
                        <td><?= $k['tingkat'] ?></td>
                        <td><?= $k['wali_kelas_nama'] ? e($k['wali_kelas_nama']) : '<span class="text-muted">-</span>' ?></td>
                        <td class="text-center"><span class="badge bg-primary"><?= $k['jml_siswa'] ?></span></td>
                        <td>
                            <div class="actions">
                                <a href="?edit=<?= $k['id'] ?>#form" class="btn btn-sm btn-warning"><i class="bi bi-pencil"></i></a>
                                <a href="?hapus=<?= $k['id'] ?>&csrf_token=<?= generateCsrf() ?>" class="btn btn-sm btn-danger"
                                   data-confirm="Hapus kelas <?= e($k['nama_kelas']) ?>? Siswa di kelas ini tidak akan terhapus."><i class="bi bi-trash"></i></a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($kelasList)): ?>
                    <tr><td colspan="5"><div class="empty-state" style="padding:30px;"><div class="empty-icon"><i class="bi bi-door-closed"></i></div><p class="empty-title">Belum Ada Kelas</p></div></td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
