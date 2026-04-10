<?php
/**
 * Riwayat Absensi Kelas - Guru
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole('guru');
$pageTitle   = 'Riwayat Absensi';
$activeMenu  = 'riwayat';
$breadcrumbs = [['label' => 'Riwayat Absensi']];
$db   = getDB();
$user = currentUser();

$gs = $db->prepare("SELECT id FROM guru WHERE user_id=? AND aktif=1");
$gs->execute([$user['id']]); $guru = $gs->fetch();

$kelasList = $guru ? getKelasByGuru($guru['id']) : [];
$kelasId   = cleanInt($_GET['kelas_id'] ?? ($kelasList[0]['id'] ?? 0));
$bulan     = cleanInt($_GET['bulan'] ?? date('m'));
$tahun     = cleanInt($_GET['tahun'] ?? date('Y'));
$perPage   = 20;
$page      = max(1, cleanInt($_GET['page'] ?? 1));

$absensiList = [];
$pg = ['total'=>0,'total_pages'=>0,'current_page'=>1,'offset'=>0,'per_page'=>$perPage];

if ($kelasId) {
    $where  = "WHERE ab.kelas_id=? AND MONTH(ab.tanggal)=? AND YEAR(ab.tanggal)=?";
    $params = [$kelasId, $bulan, $tahun];
    $total  = $db->prepare("SELECT COUNT(*) FROM absensi_siswa ab $where");
    $total->execute($params); $pg = paginate($total->fetchColumn(), $perPage, $page);

    $stmt = $db->prepare("
        SELECT s.nama, s.nis, ab.tanggal, ab.status, ab.keterangan, ab.updated_at
        FROM absensi_siswa ab JOIN siswa s ON ab.siswa_id=s.id
        $where ORDER BY ab.tanggal DESC, s.nama
        LIMIT ? OFFSET ?
    ");
    $p = $params; $p[]=$perPage; $p[]=$pg['offset'];
    $stmt->execute($p); $absensiList = $stmt->fetchAll();
}

include __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
    <div><h1 class="page-title">Riwayat Absensi</h1><p class="page-subtitle">Riwayat absensi kelas yang Anda ampu</p></div>
</div>

<div class="card mb-16">
    <div class="card-body" style="padding:14px 20px;">
        <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;">
            <div style="min-width:150px;"><label class="form-label">Kelas</label>
                <select name="kelas_id" class="form-select" data-auto-submit>
                    <option value="">-- Pilih Kelas --</option>
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
        <div class="card-title"><i class="bi bi-clock-history text-primary"></i> Riwayat Absensi — <?= namaBulan($bulan) ?> <?= $tahun ?></div>
        <span style="font-size:12px;color:var(--text-muted);"><?= $pg['total'] ?> catatan</span>
    </div>
    <div class="table-wrapper">
        <table class="table">
            <thead><tr><th>Tanggal</th><th>Hari</th><th>NIS</th><th>Nama Siswa</th><th class="text-center">Status</th><th>Keterangan</th></tr></thead>
            <tbody>
                <?php foreach ($absensiList as $a): ?>
                <tr>
                    <td><?= formatTanggal($a['tanggal']) ?></td>
                    <td><?= namaHari($a['tanggal']) ?></td>
                    <td><code style="font-size:11px;color:var(--primary-light);"><?= e($a['nis']) ?></code></td>
                    <td style="font-weight:500;color:var(--text-primary);"><?= e($a['nama']) ?></td>
                    <td class="text-center"><?= statusLabel($a['status']) ?></td>
                    <td style="color:var(--text-muted);font-size:12px;"><?= e($a['keterangan']??'-') ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($absensiList)): ?>
                <tr><td colspan="6"><div class="empty-state"><div class="empty-icon"><i class="bi bi-calendar-x"></i></div><p class="empty-title">Belum Ada Data</p><?= $kelasId?'<p class="empty-desc">Tidak ada absensi periode ini.</p>':'<p class="empty-desc">Pilih kelas terlebih dahulu.</p>' ?></div></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if ($pg['total_pages'] > 1): ?>
    <div style="padding:14px 20px;border-top:1px solid var(--border-color);display:flex;justify-content:center;">
        <div class="pagination">
            <?php $base = APP_URL.'/guru/riwayat.php?kelas_id='.$kelasId.'&bulan='.$bulan.'&tahun='.$tahun.'&page='; ?>
            <a class="page-link <?= $pg['current_page']<=1?'disabled':'' ?>" href="<?= $base.($pg['current_page']-1) ?>"><i class="bi bi-chevron-left"></i></a>
            <?php for($p=max(1,$pg['current_page']-2);$p<=min($pg['total_pages'],$pg['current_page']+2);$p++): ?>
            <a class="page-link <?= $p==$pg['current_page']?'active':'' ?>" href="<?= $base.$p ?>"><?= $p ?></a>
            <?php endfor; ?>
            <a class="page-link <?= $pg['current_page']>=$pg['total_pages']?'disabled':'' ?>" href="<?= $base.($pg['current_page']+1) ?>"><i class="bi bi-chevron-right"></i></a>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
