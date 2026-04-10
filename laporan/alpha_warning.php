<?php
/**
 * Peringatan Alpha - Laporan
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole('admin','guru');
$pageTitle   = 'Peringatan Alpha';
$activeMenu  = 'alpha_warning';
$breadcrumbs = [['label'=>'Laporan'],['label'=>'Peringatan Alpha']];
$db    = getDB();

$bulan   = cleanInt($_GET['bulan'] ?? date('m'));
$tahun   = cleanInt($_GET['tahun'] ?? date('Y'));
$kelasId = cleanInt($_GET['kelas_id'] ?? 0);
$threshold = cleanInt($_GET['threshold'] ?? ALPHA_WARNING_THRESHOLD);
if ($threshold < 1) $threshold = 1;

$kelasList = $db->query("
    SELECT k.id, k.nama_kelas FROM kelas k
    JOIN tahun_ajaran ta ON k.tahun_ajaran_id=ta.id AND ta.aktif=1
    ORDER BY k.tingkat, k.nama_kelas
")->fetchAll();

// Ambil siswa alpha
$kelasWhere = $kelasId ? " AND s.kelas_id = $kelasId" : '';
$stmt = $db->prepare("
    SELECT s.id, s.nis, s.nama, s.nama_wali, s.telepon_wali, k.nama_kelas,
           SUM(ab.status='A') AS jumlah_alpha,
           SUM(ab.status='H') AS hadir,
           SUM(ab.status='I') AS izin,
           SUM(ab.status='S') AS sakit,
           COUNT(ab.id) AS total
    FROM siswa s
    JOIN kelas k ON s.kelas_id=k.id
    LEFT JOIN absensi_siswa ab ON ab.siswa_id=s.id AND MONTH(ab.tanggal)=? AND YEAR(ab.tanggal)=?
    WHERE s.aktif=1 $kelasWhere
    GROUP BY s.id
    HAVING jumlah_alpha >= ?
    ORDER BY jumlah_alpha DESC, s.nama
");
$stmt->execute([$bulan, $tahun, $threshold]);
$alphaList = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">⚠ Peringatan Alpha Siswa</h1>
        <p class="page-subtitle"><?= namaBulan($bulan) ?> <?= $tahun ?> — Alpha ≥ <?= $threshold ?> kali</p>
    </div>
    <a href="javascript:window.print()" class="btn btn-secondary no-print"><i class="bi bi-printer"></i> Cetak</a>
</div>

<!-- Filter -->
<div class="card mb-16 no-print">
    <div class="card-body" style="padding:14px 20px;">
        <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;">
            <div>
                <label class="form-label">Bulan</label>
                <select name="bulan" class="form-select">
                    <?php for($m=1;$m<=12;$m++): ?><option value="<?= $m ?>" <?= $bulan==$m?'selected':'' ?>><?= namaBulan($m) ?></option><?php endfor; ?>
                </select>
            </div>
            <div>
                <label class="form-label">Tahun</label>
                <select name="tahun" class="form-select">
                    <?php for($y=date('Y');$y>=date('Y')-3;$y--): ?><option value="<?= $y ?>" <?= $tahun==$y?'selected':'' ?>><?= $y ?></option><?php endfor; ?>
                </select>
            </div>
            <div style="min-width:160px;">
                <label class="form-label">Kelas</label>
                <select name="kelas_id" class="form-select">
                    <option value="">Semua Kelas</option>
                    <?php foreach ($kelasList as $k): ?><option value="<?= $k['id'] ?>" <?= $kelasId==$k['id']?'selected':'' ?>><?= e($k['nama_kelas']) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div style="min-width:100px;">
                <label class="form-label">Batas Alpha (≥)</label>
                <input type="number" name="threshold" class="form-control" value="<?= $threshold ?>" min="1" max="30">
            </div>
            <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> Tampilkan</button>
        </form>
    </div>
</div>

<!-- Alert -->
<?php if (!empty($alphaList)): ?>
<div class="alert alert-danger mb-16">
    <i class="bi bi-exclamation-triangle-fill"></i>
    <strong><?= count($alphaList) ?> siswa</strong> memiliki alpha ≥ <?= $threshold ?> kali pada <?= namaBulan($bulan) ?> <?= $tahun ?>.
    Segera tindaklanjuti!
</div>
<?php else: ?>
<div class="alert alert-success mb-16">
    <i class="bi bi-check-circle-fill"></i>
    <span>Tidak ada siswa dengan alpha ≥ <?= $threshold ?> kali pada periode ini. 🎉</span>
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
                    <th>#</th>
                    <th>NIS</th>
                    <th>Nama Siswa</th>
                    <th>Kelas</th>
                    <th>Wali Murid</th>
                    <th>Telepon Wali</th>
                    <th class="text-center">H</th>
                    <th class="text-center">I</th>
                    <th class="text-center">S</th>
                    <th class="text-center">Alpha</th>
                    <th class="text-center">% Hadir</th>
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
                    <td><span class="badge bg-primary"><?= e($a['nama_kelas']) ?></span></td>
                    <td style="font-size:12px;"><?= e($a['nama_wali']??'-') ?></td>
                    <td style="font-size:12px;"><?= e($a['telepon_wali']??'-') ?></td>
                    <td class="text-center" style="color:var(--success);font-weight:600;"><?= $a['hadir'] ?></td>
                    <td class="text-center" style="color:var(--warning);font-weight:600;"><?= $a['izin'] ?></td>
                    <td class="text-center" style="color:var(--info);font-weight:600;"><?= $a['sakit'] ?></td>
                    <td class="text-center">
                        <span style="background:rgba(239,68,68,0.2);color:var(--danger);font-weight:800;font-size:16px;padding:4px 10px;border-radius:6px;">
                            <?= $a['jumlah_alpha'] ?>
                        </span>
                    </td>
                    <td class="text-center">
                        <span style="color:var(--<?= warnaPersentase($pct) ?>);font-weight:600;"><?= $pct ?>%</span>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($alphaList)): ?>
                <tr><td colspan="11">
                    <div class="empty-state" style="padding:40px;">
                        <div style="font-size:56px;margin-bottom:12px;">🎉</div>
                        <p class="empty-title">Tidak Ada Siswa Bermasalah</p>
                        <p class="empty-desc">Semua siswa memiliki alpha di bawah batas.</p>
                    </div>
                </td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
