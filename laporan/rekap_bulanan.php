<?php
/**
 * Rekap Bulanan - Laporan
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole('admin','guru');
$pageTitle   = 'Rekap Bulanan';
$activeMenu  = 'rekap_bulanan';
$breadcrumbs = [['label'=>'Laporan'],['label'=>'Rekap Bulanan']];
$db    = getDB();

$bulan   = cleanInt($_GET['bulan'] ?? date('m'));
$tahun   = cleanInt($_GET['tahun'] ?? date('Y'));
$kelasId = cleanInt($_GET['kelas_id'] ?? 0);

$kelasList = $db->query("
    SELECT k.id, k.nama_kelas FROM kelas k
    JOIN tahun_ajaran ta ON k.tahun_ajaran_id=ta.id AND ta.aktif=1
    ORDER BY k.tingkat, k.nama_kelas
")->fetchAll();

// Data rekap per siswa per bulan
$where  = "WHERE MONTH(ab.tanggal)=? AND YEAR(ab.tanggal)=?";
$params = [$bulan, $tahun];
if ($kelasId) { $where .= " AND ab.kelas_id=?"; $params[] = $kelasId; }

$stmt = $db->prepare("
    SELECT s.id, s.nis, s.nama, k.nama_kelas,
           COUNT(ab.id) AS total_hari,
           SUM(ab.status='H') AS hadir,
           SUM(ab.status='I') AS izin,
           SUM(ab.status='S') AS sakit,
           SUM(ab.status='A') AS alpha
    FROM siswa s
    JOIN kelas k ON s.kelas_id=k.id
    JOIN tahun_ajaran ta ON k.tahun_ajaran_id=ta.id AND ta.aktif=1
    LEFT JOIN absensi_siswa ab ON ab.siswa_id=s.id AND MONTH(ab.tanggal)=? AND YEAR(ab.tanggal)=?
    WHERE s.aktif=1 " . ($kelasId ? " AND s.kelas_id=?" : "") . "
    GROUP BY s.id
    ORDER BY k.tingkat, k.nama_kelas, s.nama
");
$p2 = [$bulan,$tahun]; if ($kelasId) $p2[] = $kelasId;
$stmt->execute($p2);
$rekapList = $stmt->fetchAll();

// Hitung total
$totalHadir = array_sum(array_column($rekapList,'hadir'));
$totalIzin  = array_sum(array_column($rekapList,'izin'));
$totalSakit = array_sum(array_column($rekapList,'sakit'));
$totalAlpha = array_sum(array_column($rekapList,'alpha'));

include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Rekap Absensi Bulanan</h1>
        <p class="page-subtitle"><?= namaBulan($bulan) ?> <?= $tahun ?></p>
    </div>
    <div style="display:flex;gap:8px;">
        <a href="javascript:window.print()" class="btn btn-secondary no-print"><i class="bi bi-printer"></i> Cetak</a>
        <a href="<?= APP_URL ?>/laporan/export_excel.php?type=bulanan&bulan=<?= $bulan ?>&tahun=<?= $tahun ?>&kelas_id=<?= $kelasId ?>" class="btn btn-success no-print"><i class="bi bi-file-earmark-excel"></i> Excel</a>
    </div>
</div>

<!-- Filter -->
<div class="card mb-16 no-print">
    <div class="card-body" style="padding:14px 20px;">
        <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;">
            <div style="min-width:130px;">
                <label class="form-label">Bulan</label>
                <select name="bulan" class="form-select">
                    <?php for ($m=1;$m<=12;$m++): ?>
                    <option value="<?= $m ?>" <?= $bulan==$m?'selected':'' ?>><?= namaBulan($m) ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div style="min-width:100px;">
                <label class="form-label">Tahun</label>
                <select name="tahun" class="form-select">
                    <?php for ($y=date('Y');$y>=date('Y')-3;$y--): ?>
                    <option value="<?= $y ?>" <?= $tahun==$y?'selected':'' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div style="min-width:160px;">
                <label class="form-label">Kelas</label>
                <select name="kelas_id" class="form-select">
                    <option value="">Semua Kelas</option>
                    <?php foreach ($kelasList as $k): ?>
                    <option value="<?= $k['id'] ?>" <?= $kelasId==$k['id']?'selected':'' ?>><?= e($k['nama_kelas']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> Tampilkan</button>
        </form>
    </div>
</div>

<!-- Summary Cards -->
<div class="grid grid-4 mb-16">
    <div class="stat-card success"><div class="stat-icon success"><i class="bi bi-check-circle"></i></div><div><div class="stat-value"><?= $totalHadir ?></div><div class="stat-label">Total Hadir</div></div></div>
    <div class="stat-card warning"><div class="stat-icon warning"><i class="bi bi-clipboard"></i></div><div><div class="stat-value"><?= $totalIzin ?></div><div class="stat-label">Total Izin</div></div></div>
    <div class="stat-card info"><div class="stat-icon info"><i class="bi bi-thermometer-half"></i></div><div><div class="stat-value"><?= $totalSakit ?></div><div class="stat-label">Total Sakit</div></div></div>
    <div class="stat-card danger"><div class="stat-icon danger"><i class="bi bi-x-circle"></i></div><div><div class="stat-value"><?= $totalAlpha ?></div><div class="stat-label">Total Alpha</div></div></div>
</div>

<!-- Tabel -->
<div class="card">
    <div class="card-header">
        <div class="card-title"><i class="bi bi-table text-primary"></i> Rekap Tiap Siswa — <?= namaBulan($bulan) ?> <?= $tahun ?></div>
        <span style="font-size:12px;color:var(--text-muted);"><?= count($rekapList) ?> siswa</span>
    </div>
    <div class="table-wrapper">
        <table class="table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>NIS</th>
                    <th>Nama Siswa</th>
                    <th>Kelas</th>
                    <th class="text-center">H</th>
                    <th class="text-center">I</th>
                    <th class="text-center">S</th>
                    <th class="text-center">A</th>
                    <th class="text-center">Total</th>
                    <th class="text-center">% Hadir</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rekapList as $i => $r):
                    $pct = hitungPersentase((int)$r['hadir'], (int)$r['total_hari']);
                    $warna = warnaPersentase($pct);
                ?>
                <tr>
                    <td class="text-muted"><?= $i+1 ?></td>
                    <td><code style="font-size:11px;color:var(--primary-light);"><?= e($r['nis']) ?></code></td>
                    <td style="font-weight:500;color:var(--text-primary);"><?= e($r['nama']) ?>
                        <?php if ($r['alpha'] >= ALPHA_WARNING_THRESHOLD): ?>
                        <span class="badge bg-danger" style="margin-left:4px;font-size:9px;"><i class="bi bi-exclamation-triangle"></i> Alpha!</span>
                        <?php endif; ?>
                    </td>
                    <td><span class="badge bg-primary"><?= e($r['nama_kelas']) ?></span></td>
                    <td class="text-center"><span style="color:var(--success);font-weight:600;"><?= $r['hadir'] ?></span></td>
                    <td class="text-center"><span style="color:var(--warning);font-weight:600;"><?= $r['izin'] ?></span></td>
                    <td class="text-center"><span style="color:var(--info);font-weight:600;"><?= $r['sakit'] ?></span></td>
                    <td class="text-center"><span style="color:<?= $r['alpha']>0?'var(--danger)':'var(--text-muted)' ?>;font-weight:600;"><?= $r['alpha'] ?></span></td>
                    <td class="text-center"><?= $r['total_hari'] ?></td>
                    <td class="text-center">
                        <div style="display:flex;align-items:center;gap:8px;min-width:100px;">
                            <div class="progress" style="flex:1;"><div class="progress-bar <?= $warna ?>" style="width:<?= $pct ?>%"></div></div>
                            <span style="font-size:12px;font-weight:600;color:var(--<?= $warna ?>);min-width:38px;"><?= $pct ?>%</span>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($rekapList)): ?>
                <tr><td colspan="10"><div class="empty-state"><div class="empty-icon"><i class="bi bi-calendar-x"></i></div><p class="empty-title">Tidak Ada Data</p></div></td></tr>
                <?php endif; ?>
            </tbody>
            <?php if (!empty($rekapList)): ?>
            <tfoot>
                <tr style="background:rgba(255,255,255,0.04);">
                    <td colspan="4" style="font-weight:700;color:var(--text-primary);padding:12px 16px;">TOTAL</td>
                    <td class="text-center" style="font-weight:700;color:var(--success);"><?= $totalHadir ?></td>
                    <td class="text-center" style="font-weight:700;color:var(--warning);"><?= $totalIzin ?></td>
                    <td class="text-center" style="font-weight:700;color:var(--info);"><?= $totalSakit ?></td>
                    <td class="text-center" style="font-weight:700;color:var(--danger);"><?= $totalAlpha ?></td>
                    <td class="text-center" style="font-weight:700;"><?= $totalHadir+$totalIzin+$totalSakit+$totalAlpha ?></td>
                    <td></td>
                </tr>
            </tfoot>
            <?php endif; ?>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
