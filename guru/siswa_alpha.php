<?php
/**
 * Siswa Alpha Warning - Guru (kelas yang diampu)
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole('guru');
$pageTitle   = 'Siswa Peringatan Alpha';
$activeMenu  = 'siswa_alpha';
$breadcrumbs = [['label' => 'Siswa Peringatan Alpha']];
$db   = getDB();
$user = currentUser();

$gs = $db->prepare("SELECT id FROM guru WHERE user_id=? AND aktif=1");
$gs->execute([$user['id']]); $guru = $gs->fetch();

$kelasList = $guru ? getKelasByGuru($guru['id']) : [];
$kelasId   = cleanInt($_GET['kelas_id'] ?? ($kelasList[0]['id'] ?? 0));
$bulan     = cleanInt($_GET['bulan'] ?? date('m'));
$tahun     = cleanInt($_GET['tahun'] ?? date('Y'));
$threshold = cleanInt($_GET['threshold'] ?? ALPHA_WARNING_THRESHOLD);
if ($threshold < 1) $threshold = 1;

$alphaList = [];
if ($kelasId) {
    $stmt = $db->prepare("
        SELECT s.id, s.nis, s.nama, s.nama_wali, s.telepon_wali,
               SUM(ab.status='A') jumlah_alpha,
               SUM(ab.status='H') hadir,
               SUM(ab.status='I') izin,
               SUM(ab.status='S') sakit,
               COUNT(ab.id) total
        FROM siswa s
        LEFT JOIN absensi_siswa ab ON ab.siswa_id=s.id
            AND MONTH(ab.tanggal)=? AND YEAR(ab.tanggal)=?
        WHERE s.kelas_id=? AND s.aktif=1
        GROUP BY s.id
        HAVING jumlah_alpha >= ?
        ORDER BY jumlah_alpha DESC, s.nama
    ");
    $stmt->execute([$bulan, $tahun, $kelasId, $threshold]);
    $alphaList = $stmt->fetchAll();
}

// Info kelas
$kelasInfo = null;
if ($kelasId) {
    $k = $db->prepare("SELECT nama_kelas FROM kelas WHERE id=?"); $k->execute([$kelasId]); $kelasInfo = $k->fetch();
}

include __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
    <div>
        <h1 class="page-title">⚠ Siswa Peringatan Alpha</h1>
        <p class="page-subtitle">
            <?= $kelasInfo ? 'Kelas '.e($kelasInfo['nama_kelas']) : 'Semua kelas' ?> — <?= namaBulan($bulan) ?> <?= $tahun ?>
        </p>
    </div>
    <a href="javascript:window.print()" class="btn btn-secondary no-print"><i class="bi bi-printer"></i> Cetak</a>
</div>

<!-- Filter -->
<div class="card mb-16 no-print">
    <div class="card-body" style="padding:14px 20px;">
        <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;">
            <div><label class="form-label">Kelas</label>
                <select name="kelas_id" class="form-select" data-auto-submit>
                    <?php foreach ($kelasList as $k): ?>
                    <option value="<?= $k['id'] ?>" <?= $kelasId==$k['id']?'selected':'' ?>><?= e($k['nama_kelas']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div><label class="form-label">Bulan</label>
                <select name="bulan" class="form-select">
                    <?php for($m=1;$m<=12;$m++): ?>
                    <option value="<?= $m ?>" <?= $bulan==$m?'selected':'' ?>><?= namaBulan($m) ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div><label class="form-label">Tahun</label>
                <select name="tahun" class="form-select">
                    <?php for($y=date('Y');$y>=date('Y')-2;$y--): ?>
                    <option value="<?= $y ?>" <?= $tahun==$y?'selected':'' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div style="min-width:90px;"><label class="form-label">Batas ≥</label>
                <input type="number" name="threshold" class="form-control" value="<?= $threshold ?>" min="1" max="30">
            </div>
            <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> Cari</button>
        </form>
    </div>
</div>

<!-- Alert -->
<?php if (empty($kelasList)): ?>
<div class="alert alert-warning">
    <i class="bi bi-exclamation-triangle-fill"></i>
    <span>Anda belum menjadi wali kelas. Hubungi admin untuk penetapan kelas.</span>
</div>
<?php elseif (!empty($alphaList)): ?>
<div class="alert alert-danger mb-16">
    <i class="bi bi-exclamation-triangle-fill"></i>
    <strong><?= count($alphaList) ?> siswa</strong> memiliki alpha ≥ <?= $threshold ?> kali di <?= namaBulan($bulan) ?> <?= $tahun ?>.
    Segera koordinasikan dengan orang tua.
</div>
<?php else: ?>
<div class="alert alert-success mb-16">
    <i class="bi bi-check-circle-fill"></i>
    <span>Tidak ada siswa dengan alpha ≥ <?= $threshold ?> kali. 🎉</span>
</div>
<?php endif; ?>

<!-- Tabel -->
<div class="card">
    <div class="card-header">
        <div class="card-title"><i class="bi bi-exclamation-triangle text-warning"></i> Daftar Siswa Bermasalah Alpha</div>
    </div>
    <div class="table-wrapper">
        <table class="table">
            <thead>
                <tr>
                    <th>#</th><th>NIS</th><th>Nama Siswa</th><th>Wali Murid</th>
                    <th>Telepon Wali</th><th class="text-center">H</th>
                    <th class="text-center">I</th><th class="text-center">S</th>
                    <th class="text-center">Alpha</th><th class="text-center">% Hadir</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($alphaList as $i => $a):
                    $pct = hitungPersentase((int)$a['hadir'], (int)$a['total']);
                ?>
                <tr>
                    <td class="text-muted"><?= $i+1 ?></td>
                    <td><code style="font-size:11px;color:var(--primary-light);"><?= e($a['nis']) ?></code></td>
                    <td style="font-weight:600;color:var(--text-primary);"><?= e($a['nama']) ?></td>
                    <td style="font-size:12px;"><?= e($a['nama_wali']??'-') ?></td>
                    <td style="font-size:12px;"><?= e($a['telepon_wali']??'-') ?></td>
                    <td class="text-center" style="color:var(--success);font-weight:600;"><?= $a['hadir'] ?></td>
                    <td class="text-center" style="color:var(--warning);font-weight:600;"><?= $a['izin'] ?></td>
                    <td class="text-center" style="color:var(--info);font-weight:600;"><?= $a['sakit'] ?></td>
                    <td class="text-center">
                        <span style="background:rgba(239,68,68,0.2);color:var(--danger);font-weight:800;font-size:16px;padding:4px 12px;border-radius:6px;"><?= $a['jumlah_alpha'] ?></span>
                    </td>
                    <td class="text-center">
                        <span style="color:var(--<?= warnaPersentase($pct) ?>);font-weight:700;"><?= $pct ?>%</span>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($alphaList) && !empty($kelasList)): ?>
                <tr><td colspan="10">
                    <div class="empty-state" style="padding:40px;">
                        <div style="font-size:56px;margin-bottom:12px;">🎉</div>
                        <p class="empty-title">Semua Siswa Baik</p>
                        <p class="empty-desc">Tidak ada siswa dengan alpha ≥ <?= $threshold ?> kali.</p>
                    </div>
                </td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
