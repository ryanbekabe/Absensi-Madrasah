<?php
/**
 * Jadwal Pelajaran - Admin
 */
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireRole('admin');
$pageTitle   = 'Jadwal Pelajaran';
$activeMenu  = 'jadwal';
$breadcrumbs = [['label' => 'Jadwal Pelajaran']];
$db = getDB();

$hariList    = ['Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
$guruList    = $db->query("SELECT id,nama FROM guru WHERE aktif=1 ORDER BY nama")->fetchAll();
$mapelList   = $db->query("SELECT id,kode,nama FROM mata_pelajaran ORDER BY kode")->fetchAll();
$kelasList   = $db->query("
    SELECT k.id, k.nama_kelas FROM kelas k
    JOIN tahun_ajaran ta ON k.tahun_ajaran_id=ta.id AND ta.aktif=1
    ORDER BY k.tingkat, k.nama_kelas
")->fetchAll();

$kelasFilter = cleanInt($_GET['kelas_id'] ?? ($kelasList[0]['id'] ?? 0));

// Handle POST (tambah/edit jadwal)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $act       = clean($_POST['act'] ?? 'tambah');
    $jid       = cleanInt($_POST['id'] ?? 0);
    $kelasId   = cleanInt($_POST['kelas_id'] ?? 0);
    $mapelId   = cleanInt($_POST['mapel_id'] ?? 0);
    $guruId    = cleanInt($_POST['guru_id'] ?? 0);
    $hari      = in_array($_POST['hari']??'', $hariList) ? $_POST['hari'] : 'Senin';
    $jamMulai  = clean($_POST['jam_mulai'] ?? '07:00');
    $jamSelesai= clean($_POST['jam_selesai'] ?? '08:00');

    if ($kelasId && $mapelId && $guruId && $jamMulai && $jamSelesai) {
        if ($act === 'edit' && $jid) {
            $db->prepare("UPDATE jadwal SET kelas_id=?,mapel_id=?,guru_id=?,hari=?,jam_mulai=?,jam_selesai=? WHERE id=?")
               ->execute([$kelasId,$mapelId,$guruId,$hari,$jamMulai,$jamSelesai,$jid]);
            setFlash('success','Jadwal berhasil diperbarui.');
        } else {
            $db->prepare("INSERT INTO jadwal (kelas_id,mapel_id,guru_id,hari,jam_mulai,jam_selesai) VALUES (?,?,?,?,?,?)")
               ->execute([$kelasId,$mapelId,$guruId,$hari,$jamMulai,$jamSelesai]);
            setFlash('success','Jadwal berhasil ditambahkan.');
        }
    } else {
        setFlash('danger','Semua field wajib diisi.');
    }
    header('Location: ' . APP_URL . '/admin/jadwal/?kelas_id=' . $kelasFilter); exit;
}

// Handle hapus
if (isset($_GET['hapus']) && isset($_GET['csrf_token'])) {
    verifyCsrf(); $id = cleanInt($_GET['hapus']);
    $db->prepare("DELETE FROM jadwal WHERE id=?")->execute([$id]);
    setFlash('success','Jadwal berhasil dihapus.');
    header('Location: ' . APP_URL . '/admin/jadwal/?kelas_id=' . $kelasFilter); exit;
}

// Ambil jadwal kelas terpilih
$jadwalList = [];
if ($kelasFilter) {
    $stmt = $db->prepare("
        SELECT j.*, mp.nama AS mapel_nama, mp.kode AS mapel_kode, g.nama AS guru_nama
        FROM jadwal j
        JOIN mata_pelajaran mp ON j.mapel_id=mp.id
        JOIN guru g ON j.guru_id=g.id
        WHERE j.kelas_id=?
        ORDER BY FIELD(j.hari,'Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'), j.jam_mulai
    ");
    $stmt->execute([$kelasFilter]); $jadwalList = $stmt->fetchAll();
}

// Edit data
$editData = null;
if (isset($_GET['edit'])) {
    $e = $db->prepare("SELECT * FROM jadwal WHERE id=?"); $e->execute([cleanInt($_GET['edit'])]); $editData = $e->fetch();
}

include __DIR__ . '/../../includes/header.php';
?>
<div class="page-header">
    <div><h1 class="page-title">Jadwal Pelajaran</h1><p class="page-subtitle">Kelola jadwal pelajaran per kelas</p></div>
</div>

<div class="grid" style="grid-template-columns:320px 1fr;gap:20px;">
    <!-- Form Tambah/Edit -->
    <div class="card" style="align-self:start;">
        <div class="card-header"><div class="card-title"><i class="bi bi-calendar3 text-primary"></i> <?= $editData?'Edit':'Tambah' ?> Jadwal</div></div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= generateCsrf() ?>">
                <input type="hidden" name="act" value="<?= $editData?'edit':'tambah' ?>">
                <?php if($editData): ?><input type="hidden" name="id" value="<?= $editData['id'] ?>"><?php endif; ?>

                <div class="form-group">
                    <label class="form-label">Kelas <span class="required">*</span></label>
                    <select class="form-select" name="kelas_id" required>
                        <option value="">-- Pilih Kelas --</option>
                        <?php foreach ($kelasList as $k): ?>
                        <option value="<?= $k['id'] ?>" <?= ($editData['kelas_id']??$kelasFilter)==$k['id']?'selected':'' ?>><?= e($k['nama_kelas']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Mata Pelajaran <span class="required">*</span></label>
                    <select class="form-select" name="mapel_id" required>
                        <option value="">-- Pilih Mapel --</option>
                        <?php foreach ($mapelList as $m): ?>
                        <option value="<?= $m['id'] ?>" <?= ($editData['mapel_id']??0)==$m['id']?'selected':'' ?>>[<?= e($m['kode']) ?>] <?= e($m['nama']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Guru <span class="required">*</span></label>
                    <select class="form-select" name="guru_id" required>
                        <option value="">-- Pilih Guru --</option>
                        <?php foreach ($guruList as $g): ?>
                        <option value="<?= $g['id'] ?>" <?= ($editData['guru_id']??0)==$g['id']?'selected':'' ?>><?= e($g['nama']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Hari <span class="required">*</span></label>
                    <select class="form-select" name="hari" required>
                        <?php foreach ($hariList as $h): ?>
                        <option value="<?= $h ?>" <?= ($editData['hari']??'Senin')===$h?'selected':'' ?>><?= $h ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="grid grid-2">
                    <div class="form-group">
                        <label class="form-label">Jam Mulai <span class="required">*</span></label>
                        <input type="time" class="form-control" name="jam_mulai" value="<?= e($editData['jam_mulai']??'07:00') ?>" required>
                    </div>
                    <div class="form-group" style="margin-bottom:0;">
                        <label class="form-label">Jam Selesai <span class="required">*</span></label>
                        <input type="time" class="form-control" name="jam_selesai" value="<?= e($editData['jam_selesai']??'08:00') ?>" required>
                    </div>
                </div>
                <div style="display:flex;gap:8px;margin-top:12px;">
                    <button type="submit" class="btn btn-primary" style="flex:1;"><i class="bi bi-save"></i> <?= $editData?'Perbarui':'Simpan' ?></button>
                    <?php if($editData): ?><a href="?kelas_id=<?= $kelasFilter ?>" class="btn btn-secondary"><i class="bi bi-x"></i></a><?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Jadwal Kelas -->
    <div>
        <!-- Pilih kelas filter -->
        <div class="card mb-16">
            <div class="card-body" style="padding:12px 16px;">
                <form method="GET" style="display:flex;align-items:center;gap:10px;">
                    <label class="form-label" style="margin:0;white-space:nowrap;">Lihat Jadwal Kelas:</label>
                    <select name="kelas_id" class="form-select" style="max-width:200px;" data-auto-submit>
                        <option value="">-- Pilih --</option>
                        <?php foreach ($kelasList as $k): ?>
                        <option value="<?= $k['id'] ?>" <?= $kelasFilter==$k['id']?'selected':'' ?>><?= e($k['nama_kelas']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-eye"></i> Tampilkan</button>
                </form>
            </div>
        </div>

        <!-- Jadwal per hari -->
        <?php foreach ($hariList as $hari):
            $jadwalHari = array_filter($jadwalList, fn($j) => $j['hari'] === $hari);
            if (empty($jadwalHari) && !$kelasFilter) continue;
        ?>
        <div class="card mb-16">
            <div class="card-header">
                <div class="card-title"><i class="bi bi-calendar-day text-primary"></i> <?= $hari ?></div>
                <span class="badge bg-secondary"><?= count($jadwalHari) ?> sesi</span>
            </div>
            <?php if (empty($jadwalHari)): ?>
            <div class="card-body" style="padding:12px 20px;">
                <span style="font-size:13px;color:var(--text-muted);">Tidak ada jadwal pada hari <?= $hari ?>.</span>
            </div>
            <?php else: ?>
            <div class="table-wrapper">
                <table class="table" style="margin:0;">
                    <tbody>
                        <?php foreach ($jadwalHari as $j): ?>
                        <tr>
                            <td style="width:100px;font-weight:600;color:var(--primary-light);">
                                <?= substr($j['jam_mulai'],0,5) ?> – <?= substr($j['jam_selesai'],0,5) ?>
                            </td>
                            <td>
                                <div style="font-weight:600;color:var(--text-primary);"><?= e($j['mapel_nama']) ?></div>
                                <div style="font-size:11px;color:var(--text-muted);">Guru: <?= e($j['guru_nama']) ?></div>
                            </td>
                            <td style="text-align:right;">
                                <div class="actions" style="justify-content:flex-end;">
                                    <a href="?edit=<?= $j['id'] ?>&kelas_id=<?= $kelasFilter ?>" class="btn btn-sm btn-warning"><i class="bi bi-pencil"></i></a>
                                    <a href="?hapus=<?= $j['id'] ?>&kelas_id=<?= $kelasFilter ?>&csrf_token=<?= generateCsrf() ?>" class="btn btn-sm btn-danger"
                                       data-confirm="Hapus jadwal ini?"><i class="bi bi-trash"></i></a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>

        <?php if (empty($jadwalList) && $kelasFilter): ?>
        <div class="empty-state" style="background:var(--bg-card);border:1px solid var(--border-color);border-radius:var(--radius-lg);padding:60px;">
            <div class="empty-icon"><i class="bi bi-calendar3"></i></div>
            <p class="empty-title">Belum Ada Jadwal</p>
            <p class="empty-desc">Tambahkan jadwal pelajaran menggunakan form di samping kiri.</p>
        </div>
        <?php elseif (!$kelasFilter): ?>
        <div class="alert alert-info"><i class="bi bi-info-circle-fill"></i><span>Pilih kelas untuk melihat jadwal.</span></div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
