<?php
/**
 * Rekap Harian - Laporan
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole('admin','guru');
$pageTitle   = 'Rekap Harian';
$activeMenu  = 'rekap_harian';
$breadcrumbs = [['label'=>'Laporan'],['label'=>'Rekap Harian']];
$db    = getDB();
$today = date('Y-m-d');
$tanggal = clean($_GET['tanggal'] ?? $today);
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/',$tanggal)) $tanggal = $today;

// Filter kelas
$kelasId = cleanInt($_GET['kelas_id'] ?? 0);
$kelasList = $db->query("
    SELECT k.id, k.nama_kelas FROM kelas k
    JOIN tahun_ajaran ta ON k.tahun_ajaran_id=ta.id AND ta.aktif=1
    ORDER BY k.tingkat, k.nama_kelas
")->fetchAll();

// Data absensi
$where  = "WHERE ab.tanggal = ?";
$params = [$tanggal];
if ($kelasId) { $where .= " AND ab.kelas_id=?"; $params[] = $kelasId; }

$stmt = $db->prepare("
    SELECT s.nis, s.nama, k.nama_kelas, ab.status, ab.keterangan, ab.jam_masuk, ab.jam_pulang,
           CONCAT(u.nama) AS dicatat_oleh, ab.updated_at
    FROM absensi_siswa ab
    JOIN siswa s ON ab.siswa_id = s.id
    JOIN kelas k ON ab.kelas_id = k.id
    LEFT JOIN users u ON ab.dicatat_oleh = u.id
    $where
    ORDER BY k.tingkat, k.nama_kelas, s.nama
");
$stmt->execute($params);
$absensiList = $stmt->fetchAll();

// Hitung rekap
$rekap = ['H'=>0,'I'=>0,'S'=>0,'A'=>0];
foreach ($absensiList as $a) $rekap[$a['status']]++;
$total = array_sum($rekap);

include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Rekap Absensi Harian</h1>
        <p class="page-subtitle"><?= namaHari($tanggal) ?>, <?= formatTanggal($tanggal) ?></p>
    </div>
    <div style="display:flex;gap:8px;">
        <a href="?tanggal=<?= $tanggal ?>&kelas_id=<?= $kelasId ?>&print=1" class="btn btn-secondary no-print" onclick="window.print();return false;">
            <i class="bi bi-printer"></i> Cetak
        </a>
        <a href="<?= APP_URL ?>/laporan/export_excel.php?type=harian&tanggal=<?= $tanggal ?>&kelas_id=<?= $kelasId ?>" class="btn btn-success no-print">
            <i class="bi bi-file-earmark-excel"></i> Excel
        </a>
    </div>
</div>

<!-- Filter -->
<div class="card mb-16 no-print">
    <div class="card-body" style="padding:14px 20px;">
        <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;">
            <div style="min-width:180px;">
                <label class="form-label">Tanggal</label>
                <input type="date" name="tanggal" class="form-control" value="<?= e($tanggal) ?>">
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

<!-- Rekap -->
<div class="grid grid-4 mb-16">
    <?php
    $items = [['Hadir','H','success','check-circle'],['Izin','I','warning','clipboard'],['Sakit','S','info','thermometer-half'],['Alpha','A','danger','x-circle']];
    foreach ($items as [$lbl,$k,$color,$icon]):
    ?>
    <div class="stat-card <?= $color ?>">
        <div class="stat-icon <?= $color ?>"><i class="bi bi-<?= $icon ?>"></i></div>
        <div>
            <div class="stat-value"><?= $rekap[$k] ?></div>
            <div class="stat-label"><?= $lbl ?></div>
            <div class="stat-change"><?= $total > 0 ? round($rekap[$k]/$total*100,1).'%' : '0%' ?></div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Tabel -->
<div class="card">
    <div class="card-header">
        <div class="card-title"><i class="bi bi-table text-primary"></i> Detail Absensi — <?= formatTanggal($tanggal) ?></div>
        <span style="font-size:12px;color:var(--text-muted);"><?= $total ?> catatan</span>
    </div>
    <div class="table-wrapper">
        <table class="table">
            <thead>
                <tr><th>#</th><th>NIS</th><th>Nama Siswa</th><th>Kelas</th><th class="text-center">Status</th><th>Masuk</th><th>Pulang</th><th>Keterangan</th><th>Dicatat Oleh</th></tr>
            </thead>
            <tbody>
                <?php foreach ($absensiList as $i => $a): ?>
                <tr>
                    <td class="text-muted"><?= $i+1 ?></td>
                    <td><code style="font-size:11px;color:var(--primary-light);"><?= e($a['nis']) ?></code></td>
                    <td style="font-weight:500;color:var(--text-primary);"><?= e($a['nama']) ?></td>
                    <td><span class="badge bg-primary"><?= e($a['nama_kelas']) ?></span></td>
                    <td class="text-center"><?= statusLabel($a['status']) ?></td>
                    <td style="font-size:12px;color:var(--success);font-weight:600;"><?= $a['jam_masuk'] ? substr($a['jam_masuk'],0,5) : '-' ?></td>
                    <td style="font-size:12px;color:var(--info);font-weight:600;"><?= $a['jam_pulang'] ? substr($a['jam_pulang'],0,5) : '-' ?></td>
                    <td style="color:var(--text-muted);font-size:12px;"><?= e($a['keterangan']??'-') ?></td>
                    <td style="color:var(--text-muted);font-size:11px;"><?= e($a['dicatat_oleh']??'-') ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($absensiList)): ?>
                <tr>
                    <td colspan="7">
                        <div class="empty-state"><div class="empty-icon"><i class="bi bi-calendar-x"></i></div>
                        <p class="empty-title">Belum Ada Data</p><p class="empty-desc">Tidak ada absensi pada tanggal <?= formatTanggal($tanggal) ?>.</p></div>
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
