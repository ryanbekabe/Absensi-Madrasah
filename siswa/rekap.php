<?php
/**
 * Rekap Kehadiran - Siswa
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole('siswa');
$pageTitle   = 'Rekap Kehadiran Saya';
$activeMenu  = 'rekap';
$breadcrumbs = [['label' => 'Rekap Kehadiran']];
$db   = getDB();
$user = currentUser();

$ss = $db->prepare("SELECT s.*,k.nama_kelas FROM siswa s JOIN kelas k ON s.kelas_id=k.id WHERE s.user_id=? LIMIT 1");
$ss->execute([$user['id']]); $siswa = $ss->fetch();

$bulan = cleanInt($_GET['bulan'] ?? date('m'));
$tahun = cleanInt($_GET['tahun'] ?? date('Y'));
$perPage = 20;
$page  = max(1, cleanInt($_GET['page'] ?? 1));

$riwayat = []; $rekap = []; $pg = ['total'=>0,'total_pages'=>0,'current_page'=>1,'offset'=>0,'per_page'=>$perPage];

if ($siswa) {
    $rekap = rekapAbsensiSiswa($siswa['id'], str_pad($bulan,2,'0',STR_PAD_LEFT), $tahun);
    $total = $db->prepare("SELECT COUNT(*) FROM absensi_siswa WHERE siswa_id=? AND MONTH(tanggal)=? AND YEAR(tanggal)=?");
    $total->execute([$siswa['id'], $bulan, $tahun]);
    $pg = paginate($total->fetchColumn(), $perPage, $page);

    $stmt = $db->prepare("SELECT tanggal,status,keterangan FROM absensi_siswa WHERE siswa_id=? AND MONTH(tanggal)=? AND YEAR(tanggal)=? ORDER BY tanggal DESC LIMIT ? OFFSET ?");
    $stmt->execute([$siswa['id'], $bulan, $tahun, $perPage, $pg['offset']]);
    $riwayat = $stmt->fetchAll();
}

include __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
    <div><h1 class="page-title">Rekap Kehadiran Saya</h1>
        <p class="page-subtitle"><?= $siswa ? e($siswa['nama']).' — Kelas '.e($siswa['nama_kelas']) : 'Data siswa tidak ditemukan' ?></p>
    </div>
</div>

<?php if (!$siswa): ?>
<div class="alert alert-warning"><i class="bi bi-exclamation-triangle-fill"></i><span>Data siswa Anda belum terdaftar. Hubungi administrator.</span></div>
<?php else: ?>

<!-- Filter -->
<div class="card mb-16">
    <div class="card-body" style="padding:14px 20px;">
        <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;">
            <div><label class="form-label">Bulan</label>
                <select name="bulan" class="form-select" data-auto-submit>
                    <?php for($m=1;$m<=12;$m++): ?><option value="<?= $m ?>" <?= $bulan==$m?'selected':'' ?>><?= namaBulan($m) ?></option><?php endfor; ?>
                </select>
            </div>
            <div><label class="form-label">Tahun</label>
                <select name="tahun" class="form-select" data-auto-submit>
                    <?php for($y=date('Y');$y>=date('Y')-2;$y--): ?><option value="<?= $y ?>" <?= $tahun==$y?'selected':'' ?>><?= $y ?></option><?php endfor; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> Tampilkan</button>
        </form>
    </div>
</div>

<!-- Rekap -->
<div class="grid grid-4 mb-16">
    <?php foreach ([['Hadir','hadir','success','check-circle'],['Izin','izin','warning','clipboard'],['Sakit','sakit','info','thermometer-half'],['Alpha','alpha','danger','x-circle']] as [$lbl,$key,$color,$icon]): ?>
    <div class="stat-card <?= $color ?>">
        <div class="stat-icon <?= $color ?>"><i class="bi bi-<?= $icon ?>"></i></div>
        <div><div class="stat-value"><?= $rekap[$key] ?? 0 ?></div><div class="stat-label"><?= $lbl ?></div></div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Progress -->
<div class="card mb-16">
    <div class="card-body">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
            <span style="font-size:14px;font-weight:600;color:var(--text-primary);">Persentase Kehadiran — <?= namaBulan($bulan) ?> <?= $tahun ?></span>
            <span style="font-size:26px;font-weight:800;color:var(--<?= warnaPersentase($rekap['persen_hadir']??0) ?>);"><?= $rekap['persen_hadir']??0 ?>%</span>
        </div>
        <div class="progress" style="height:10px;">
            <div class="progress-bar <?= warnaPersentase($rekap['persen_hadir']??0) ?>" style="width:<?= $rekap['persen_hadir']??0 ?>%"></div>
        </div>
        <div style="font-size:12px;color:var(--text-muted);margin-top:8px;">
            <?= $rekap['hadir']??0 ?> hadir dari <?= $rekap['total']??0 ?> hari tercatat
            <?php if (($rekap['alpha']??0) >= ALPHA_WARNING_THRESHOLD): ?>
            &nbsp;<span style="color:var(--danger);font-weight:600;">⚠ Alpha Anda melebihi batas (<?= ALPHA_WARNING_THRESHOLD ?>x)! Segera hubungi wali kelas.</span>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Tabel Riwayat -->
<div class="card">
    <div class="card-header">
        <div class="card-title"><i class="bi bi-calendar3 text-primary"></i> Detail Absensi — <?= namaBulan($bulan) ?> <?= $tahun ?></div>
        <span style="font-size:12px;color:var(--text-muted);"><?= $pg['total'] ?> catatan</span>
    </div>
    <div class="table-wrapper">
        <table class="table">
            <thead><tr><th>Tanggal</th><th>Hari</th><th class="text-center">Status</th><th>Keterangan</th></tr></thead>
            <tbody>
                <?php foreach ($riwayat as $r): ?>
                <tr>
                    <td><?= formatTanggal($r['tanggal']) ?></td>
                    <td><?= namaHari($r['tanggal']) ?></td>
                    <td class="text-center"><?= statusLabel($r['status']) ?></td>
                    <td style="color:var(--text-muted);font-size:12px;"><?= e($r['keterangan']??'-') ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($riwayat)): ?>
                <tr><td colspan="4"><div class="empty-state" style="padding:30px;"><div class="empty-icon"><i class="bi bi-calendar-x"></i></div><p class="empty-title">Belum Ada Data</p><p class="empty-desc">Data absensi bulan ini belum tersedia.</p></div></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if ($pg['total_pages'] > 1): ?>
    <div style="padding:14px 20px;border-top:1px solid var(--border-color);display:flex;justify-content:center;">
        <div class="pagination">
            <?php $base = APP_URL.'/siswa/rekap.php?bulan='.$bulan.'&tahun='.$tahun.'&page='; ?>
            <a class="page-link <?= $pg['current_page']<=1?'disabled':'' ?>" href="<?= $base.($pg['current_page']-1) ?>"><i class="bi bi-chevron-left"></i></a>
            <?php for($p=max(1,$pg['current_page']-2);$p<=min($pg['total_pages'],$pg['current_page']+2);$p++): ?>
            <a class="page-link <?= $p==$pg['current_page']?'active':'' ?>" href="<?= $base.$p ?>"><?= $p ?></a>
            <?php endfor; ?>
            <a class="page-link <?= $pg['current_page']>=$pg['total_pages']?'disabled':'' ?>" href="<?= $base.($pg['current_page']+1) ?>"><i class="bi bi-chevron-right"></i></a>
        </div>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>
<?php include __DIR__ . '/../includes/footer.php'; ?>
