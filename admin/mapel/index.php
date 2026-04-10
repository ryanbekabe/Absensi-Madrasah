<?php
/**
 * CRUD Mata Pelajaran - Admin
 */
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireRole('admin');
$pageTitle = 'Mata Pelajaran'; $activeMenu = 'mapel';
$breadcrumbs = [['label'=>'Mata Pelajaran']];
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $act  = clean($_POST['act']??'tambah');
    $id   = cleanInt($_POST['id']??0);
    $kode = strtoupper(clean($_POST['kode']??''));
    $nama = clean($_POST['nama']??'');
    $ket  = clean($_POST['keterangan']??'');

    if ($kode && $nama) {
        if ($act==='edit'&&$id) {
            $db->prepare("UPDATE mata_pelajaran SET kode=?,nama=?,keterangan=? WHERE id=?")->execute([$kode,$nama,$ket?:null,$id]);
            setFlash('success',"Mapel <strong>$nama</strong> diperbarui.");
        } else {
            $c=$db->prepare("SELECT id FROM mata_pelajaran WHERE kode=?");$c->execute([$kode]);
            if ($c->fetch()) { setFlash('danger',"Kode <strong>$kode</strong> sudah digunakan."); }
            else {
                $db->prepare("INSERT INTO mata_pelajaran (kode,nama,keterangan) VALUES (?,?,?)")->execute([$kode,$nama,$ket?:null]);
                setFlash('success',"Mapel <strong>$nama</strong> ditambahkan.");
            }
        }
    } else { setFlash('danger','Kode dan nama wajib diisi.'); }
    header('Location: ' . APP_URL . '/admin/mapel/'); exit;
}

if (isset($_GET['hapus'])&&isset($_GET['csrf_token'])) {
    verifyCsrf(); $id=cleanInt($_GET['hapus']);
    $m=$db->prepare("SELECT nama FROM mata_pelajaran WHERE id=?");$m->execute([$id]);$mp=$m->fetch();
    if ($mp) { $db->prepare("DELETE FROM mata_pelajaran WHERE id=?")->execute([$id]); setFlash('success',"Mapel <strong>{$mp['nama']}</strong> dihapus."); }
    header('Location: ' . APP_URL . '/admin/mapel/'); exit;
}

$mapelList = $db->query("SELECT * FROM mata_pelajaran ORDER BY kode")->fetchAll();
$editData  = null;
if (isset($_GET['edit'])) { $e=$db->prepare("SELECT * FROM mata_pelajaran WHERE id=?");$e->execute([cleanInt($_GET['edit'])]);$editData=$e->fetch(); }

include __DIR__ . '/../../includes/header.php';
?>
<div class="page-header"><div><h1 class="page-title">Mata Pelajaran</h1><p class="page-subtitle">Total: <?= count($mapelList) ?> mata pelajaran</p></div></div>

<div class="grid" style="grid-template-columns:1fr 2fr;gap:20px;">
    <div class="card">
        <div class="card-header"><div class="card-title"><i class="bi bi-book text-primary"></i> <?= $editData?'Edit':'Tambah' ?> Mapel</div></div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= generateCsrf() ?>">
                <input type="hidden" name="act" value="<?= $editData?'edit':'tambah' ?>">
                <?php if($editData): ?><input type="hidden" name="id" value="<?= $editData['id'] ?>"><?php endif; ?>
                <div class="form-group">
                    <label class="form-label">Kode <span class="required">*</span></label>
                    <input type="text" class="form-control" name="kode" value="<?= e($editData['kode']??'') ?>" placeholder="MTK, BIN, IPA..." required style="text-transform:uppercase;" maxlength="10">
                </div>
                <div class="form-group">
                    <label class="form-label">Nama Mapel <span class="required">*</span></label>
                    <input type="text" class="form-control" name="nama" value="<?= e($editData['nama']??'') ?>" placeholder="Nama mata pelajaran" required>
                </div>
                <div class="form-group" style="margin-bottom:16px;">
                    <label class="form-label">Keterangan</label>
                    <textarea class="form-control" name="keterangan" rows="2"><?= e($editData['keterangan']??'') ?></textarea>
                </div>
                <div style="display:flex;gap:8px;">
                    <button type="submit" class="btn btn-primary" style="flex:1;"><i class="bi bi-save"></i> <?= $editData?'Perbarui':'Simpan' ?></button>
                    <?php if($editData): ?><a href="<?= APP_URL ?>/admin/mapel/" class="btn btn-secondary"><i class="bi bi-x"></i></a><?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><div class="card-title"><i class="bi bi-list-ul text-primary"></i> Daftar Mata Pelajaran</div></div>
        <div class="table-wrapper">
            <table class="table">
                <thead><tr><th>#</th><th>Kode</th><th>Nama Mata Pelajaran</th><th>Keterangan</th><th class="text-center">Aksi</th></tr></thead>
                <tbody>
                    <?php foreach ($mapelList as $i=>$mp): ?>
                    <tr>
                        <td class="text-muted"><?= $i+1 ?></td>
                        <td><span class="badge bg-primary"><?= e($mp['kode']) ?></span></td>
                        <td style="font-weight:600;color:var(--text-primary);"><?= e($mp['nama']) ?></td>
                        <td style="color:var(--text-muted);font-size:12px;"><?= e($mp['keterangan']??'-') ?></td>
                        <td>
                            <div class="actions">
                                <a href="?edit=<?= $mp['id'] ?>" class="btn btn-sm btn-warning"><i class="bi bi-pencil"></i></a>
                                <a href="?hapus=<?= $mp['id'] ?>&csrf_token=<?= generateCsrf() ?>" class="btn btn-sm btn-danger" data-confirm="Hapus mapel <?= e($mp['nama']) ?>?"><i class="bi bi-trash"></i></a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if(empty($mapelList)): ?><tr><td colspan="5" class="text-center text-muted" style="padding:30px;">Belum ada mata pelajaran.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
