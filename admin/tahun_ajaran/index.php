<?php
/**
 * CRUD Tahun Ajaran - Admin
 */
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireRole('admin');
$pageTitle = 'Tahun Ajaran'; $activeMenu = 'tahun_ajaran';
$breadcrumbs = [['label'=>'Tahun Ajaran']];
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $act   = clean($_POST['act']??'tambah');
    $id    = cleanInt($_POST['id']??0);
    $nama  = clean($_POST['nama']??'');
    $sem   = in_array($_POST['semester']??'',['Ganjil','Genap'])?$_POST['semester']:'Ganjil';
    $mulai = clean($_POST['tanggal_mulai']??'');
    $sel   = clean($_POST['tanggal_selesai']??'');
    $aktif = cleanInt($_POST['aktif']??0);

    if ($nama && $mulai && $sel) {
        if ($aktif) $db->query("UPDATE tahun_ajaran SET aktif=0"); // hanya 1 aktif
        if ($act==='edit'&&$id) {
            $db->prepare("UPDATE tahun_ajaran SET nama=?,semester=?,tanggal_mulai=?,tanggal_selesai=?,aktif=? WHERE id=?")
               ->execute([$nama,$sem,$mulai,$sel,$aktif,$id]);
            setFlash('success',"Tahun Ajaran <strong>$nama - $sem</strong> diperbarui.");
        } else {
            $db->prepare("INSERT INTO tahun_ajaran (nama,semester,tanggal_mulai,tanggal_selesai,aktif) VALUES (?,?,?,?,?)")
               ->execute([$nama,$sem,$mulai,$sel,$aktif]);
            setFlash('success',"Tahun Ajaran <strong>$nama - $sem</strong> ditambahkan.");
        }
    } else { setFlash('danger','Semua field wajib diisi.'); }
    header('Location: ' . APP_URL . '/admin/tahun_ajaran/'); exit;
}

if (isset($_GET['hapus'])&&isset($_GET['csrf_token'])) {
    verifyCsrf(); $id=cleanInt($_GET['hapus']);
    $t=$db->prepare("SELECT nama,semester FROM tahun_ajaran WHERE id=?");$t->execute([$id]);$ta=$t->fetch();
    if ($ta) { $db->prepare("DELETE FROM tahun_ajaran WHERE id=?")->execute([$id]); setFlash('success',"Tahun ajaran <strong>{$ta['nama']}</strong> dihapus."); }
    header('Location: ' . APP_URL . '/admin/tahun_ajaran/'); exit;
}

if (isset($_GET['aktifkan'])&&isset($_GET['csrf_token'])) {
    verifyCsrf(); $id=cleanInt($_GET['aktifkan']);
    $db->query("UPDATE tahun_ajaran SET aktif=0");
    $db->prepare("UPDATE tahun_ajaran SET aktif=1 WHERE id=?")->execute([$id]);
    setFlash('success','Tahun ajaran diaktifkan.');
    header('Location: ' . APP_URL . '/admin/tahun_ajaran/'); exit;
}

$taList = $db->query("SELECT * FROM tahun_ajaran ORDER BY nama DESC, semester")->fetchAll();
$editData = null;
if (isset($_GET['edit'])) { $e=$db->prepare("SELECT * FROM tahun_ajaran WHERE id=?");$e->execute([cleanInt($_GET['edit'])]);$editData=$e->fetch(); }

include __DIR__ . '/../../includes/header.php';
?>
<div class="page-header"><div><h1 class="page-title">Tahun Ajaran</h1></div></div>

<div class="grid" style="grid-template-columns:1fr 2fr;gap:20px;">
    <div class="card">
        <div class="card-header"><div class="card-title"><i class="bi bi-calendar2-range text-primary"></i> <?= $editData?'Edit':'Tambah' ?></div></div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= generateCsrf() ?>">
                <input type="hidden" name="act" value="<?= $editData?'edit':'tambah' ?>">
                <?php if($editData): ?><input type="hidden" name="id" value="<?= $editData['id'] ?>"><?php endif; ?>
                <div class="form-group">
                    <label class="form-label">Tahun Ajaran <span class="required">*</span></label>
                    <input type="text" class="form-control" name="nama" value="<?= e($editData['nama']??'') ?>" placeholder="Contoh: 2025/2026" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Semester</label>
                    <select class="form-select" name="semester">
                        <option value="Ganjil" <?= ($editData['semester']??'Ganjil')==='Ganjil'?'selected':'' ?>>Ganjil</option>
                        <option value="Genap" <?= ($editData['semester']??'')==='Genap'?'selected':'' ?>>Genap</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Tanggal Mulai <span class="required">*</span></label>
                    <input type="date" class="form-control" name="tanggal_mulai" value="<?= e($editData['tanggal_mulai']??'') ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Tanggal Selesai <span class="required">*</span></label>
                    <input type="date" class="form-control" name="tanggal_selesai" value="<?= e($editData['tanggal_selesai']??'') ?>" required>
                </div>
                <div class="form-group" style="margin-bottom:16px;">
                    <label class="form-check" style="cursor:pointer;">
                        <input type="checkbox" class="form-check-input" name="aktif" value="1" <?= ($editData['aktif']??0)?'checked':'' ?>>
                        Jadikan Tahun Ajaran Aktif
                    </label>
                </div>
                <div style="display:flex;gap:8px;">
                    <button type="submit" class="btn btn-primary" style="flex:1;"><i class="bi bi-save"></i> <?= $editData?'Perbarui':'Simpan' ?></button>
                    <?php if($editData): ?><a href="<?= APP_URL ?>/admin/tahun_ajaran/" class="btn btn-secondary"><i class="bi bi-x"></i></a><?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><div class="card-title"><i class="bi bi-list-ul text-primary"></i> Daftar Tahun Ajaran</div></div>
        <div class="table-wrapper">
            <table class="table">
                <thead><tr><th>Tahun Ajaran</th><th>Semester</th><th>Periode</th><th class="text-center">Status</th><th class="text-center">Aksi</th></tr></thead>
                <tbody>
                    <?php foreach ($taList as $ta): ?>
                    <tr>
                        <td><strong style="color:var(--text-primary);"><?= e($ta['nama']) ?></strong></td>
                        <td><?= e($ta['semester']) ?></td>
                        <td style="font-size:12px;"><?= formatTanggal($ta['tanggal_mulai'],'d M Y') ?> — <?= formatTanggal($ta['tanggal_selesai'],'d M Y') ?></td>
                        <td class="text-center">
                            <?php if ($ta['aktif']): ?>
                                <span class="badge bg-success"><i class="bi bi-check2"></i> Aktif</span>
                            <?php else: ?>
                                <a href="?aktifkan=<?= $ta['id'] ?>&csrf_token=<?= generateCsrf() ?>" class="badge bg-secondary" style="text-decoration:none;cursor:pointer;" data-confirm="Aktifkan tahun ajaran ini?">Aktifkan</a>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="actions">
                                <a href="?edit=<?= $ta['id'] ?>" class="btn btn-sm btn-warning"><i class="bi bi-pencil"></i></a>
                                <?php if (!$ta['aktif']): ?>
                                <a href="?hapus=<?= $ta['id'] ?>&csrf_token=<?= generateCsrf() ?>" class="btn btn-sm btn-danger" data-confirm="Hapus tahun ajaran ini?"><i class="bi bi-trash"></i></a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if(empty($taList)): ?><tr><td colspan="5" class="text-center text-muted" style="padding:30px;">Belum ada tahun ajaran.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
