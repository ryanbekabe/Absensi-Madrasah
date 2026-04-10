<?php
/**
 * Rekap Kelas - Guru (Monthly)
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole('guru');
$pageTitle   = 'Rekap Kelas';
$activeMenu  = 'rekap';
$breadcrumbs = [['label' => 'Rekap Kelas']];
$db   = getDB();
$user = currentUser();

$gs = $db->prepare("SELECT id FROM guru WHERE user_id=? AND aktif=1");
$gs->execute([$user['id']]); $guru = $gs->fetch();
$kelasList = $guru ? getKelasByGuru($guru['id']) : [];

$kelasId = cleanInt($_GET['kelas_id'] ?? ($kelasList[0]['id'] ?? 0));
$bulan   = cleanInt($_GET['bulan'] ?? date('m'));
$tahun   = cleanInt($_GET['tahun'] ?? date('Y'));

$rekapList = [];
if ($kelasId) {
    $stmt = $db->prepare("
        SELECT s.id, s.nis, s.nama, s.jenis_kelamin,
               SUM(ab.status='H') hadir, SUM(ab.status='I') izin,
               SUM(ab.status='S') sakit, SUM(ab.status='A') alpha, COUNT(ab.id) total
        FROM siswa s
        LEFT JOIN absensi_siswa ab ON ab.siswa_id=s.id AND MONTH(ab.tanggal)=? AND YEAR(ab.tanggal)=? AND ab.kelas_id=?
        WHERE s.kelas_id=? AND s.aktif=1
        GROUP BY s.id ORDER BY s.nama
    ");
    $stmt->execute([$bulan, $tahun, $kelasId, $kelasId]);
    $rekapList = $stmt->fetchAll();
}

include __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
    <div><h1 class="page-title">Rekap Kelas saya</h1><p class="page-subtitle">Rekap kehadiran siswa per kelas selama satu bulan</p></div>
    <a href="javascript:window.print()" class="btn btn-secondary no-print"><i class="bi bi-printer"></i> Cetak</a>
</div>

<div class="card mb-16 no-print">
    <div class="card-body" style="padding:14px 20px;">
        <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;">
            <div><label class="form-label">Kelas</label>
                <select name="kelas_id" class="form-select" data-auto-submit>
                    <?php foreach ($kelasList as $k): ?><option value="<?= $k['id'] ?>" <?= $kelasId==$k['id']?'selected':'' ?>><?= e($k['nama_kelas']) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div><label class="form-label">Bulan</label>
                <select name="bulan" class="form-select">
                    <?php for($m=1;$m<=12;$m++): ?><option value="<?= $m ?>" <?= $bulan==$m?'selected':'' ?>><?= namaBulan($m) ?></option><?php endfor; ?>
                </select>
            </div>
            <div><label class="form-label">Tahun</label>
                <select name="tahun" class="form-select">
                    <?php for($y=date('Y');$y>=date('Y')-2;$y--): ?><option value="<?= $y ?>" <?= $tahun==$y?'selected':'' ?>><?= $y ?></option><?php endfor; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> Tampilkan</button>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <div class="card-title"><i class="bi bi-table text-primary"></i> Rekap Bulan <?= namaBulan($bulan) ?> <?= $tahun ?></div>
    </div>
    <div class="table-wrapper">
        <table class="table">
            <thead><tr><th>#</th><th>NIS</th><th>Nama Siswa</th><th class="text-center">H</th><th class="text-center">I</th><th class="text-center">S</th><th class="text-center">A</th><th class="text-center">Total</th><th class="text-center">% Hadir</th></tr></thead>
            <tbody>
                <?php foreach ($rekapList as $i => $r):
                    $pct = hitungPersentase((int)$r['hadir'], (int)$r['total']);
                    $warna = warnaPersentase($pct);
                ?>
                <tr>
                    <td class="text-muted"><?= $i+1 ?></td>
                    <td><code style="font-size:11px;color:var(--primary-light);"><?= e($r['nis']) ?></code></td>
                    <td style="font-weight:500;color:var(--text-primary);"><?= e($r['nama']) ?>
                        <?php if ($r['alpha'] >= ALPHA_WARNING_THRESHOLD): ?><span class="badge bg-danger" style="font-size:9px;margin-left:4px;">⚠ Alpha</span><?php endif; ?>
                    </td>
                    <td class="text-center" style="color:var(--success);font-weight:600;"><?= $r['hadir'] ?></td>
                    <td class="text-center" style="color:var(--warning);font-weight:600;"><?= $r['izin'] ?></td>
                    <td class="text-center" style="color:var(--info);font-weight:600;"><?= $r['sakit'] ?></td>
                    <td class="text-center" style="color:<?= $r['alpha']>0?'var(--danger)':'var(--text-muted)' ?>;font-weight:600;"><?= $r['alpha'] ?></td>
                    <td class="text-center"><?= $r['total'] ?></td>
                    <td class="text-center">
                        <div style="display:flex;align-items:center;gap:8px;min-width:90px;">
                            <div class="progress" style="flex:1;"><div class="progress-bar <?= $warna ?>" style="width:<?= $pct ?>%"></div></div>
                            <span style="font-size:12px;font-weight:600;color:var(--<?= $warna ?>);"><?= $pct ?>%</span>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($rekapList)): ?>
                <tr><td colspan="9"><div class="empty-state"><div class="empty-icon"><i class="bi bi-people"></i></div><p class="empty-title">Tidak Ada Data</p><p class="empty-desc"><?= $kelasId?'Belum ada data absensi.':'Pilih kelas terlebih dahulu.' ?></p></div></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
