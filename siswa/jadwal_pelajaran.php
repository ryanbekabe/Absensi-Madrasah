<?php
/**
 * Jadwal Pelajaran - Siswa
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole('siswa');
$pageTitle   = 'Jadwal Pelajaran';
$activeMenu  = 'jadwal';
$breadcrumbs = [['label' => 'Jadwal Pelajaran']];
$db   = getDB();
$user = currentUser();

// Ambil data siswa dan kelasnya
$ss = $db->prepare("SELECT s.*, k.nama_kelas, k.id AS kelas_id 
                    FROM siswa s 
                    JOIN kelas k ON s.kelas_id = k.id 
                    WHERE s.user_id = ? AND s.aktif = 1");
$ss->execute([$user['id']]);
$siswa = $ss->fetch();

$jadwalList = [];
if ($siswa) {
    $stmt = $db->prepare("
        SELECT j.*, mp.nama AS mapel_nama, mp.kode AS mapel_kode, g.nama AS guru_nama
        FROM jadwal j
        JOIN mata_pelajaran mp ON j.mapel_id = mp.id
        JOIN guru g ON j.guru_id = g.id
        WHERE j.kelas_id = ?
        ORDER BY FIELD(j.hari, 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'), j.jam_mulai
    ");
    $stmt->execute([$siswa['kelas_id']]);
    $jadwalList = $stmt->fetchAll();
}

$hariList = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Jadwal Pelajaran</h1>
        <p class="page-subtitle">Daftar jadwal pelajaran kelas <?= $siswa ? e($siswa['nama_kelas']) : '-' ?></p>
    </div>
    <button onclick="window.print()" class="btn btn-secondary no-print">
        <i class="bi bi-printer"></i> Cetak Jadwal
    </button>
</div>

<?php if (!$siswa): ?>
<div class="alert alert-warning">
    <i class="bi bi-exclamation-triangle-fill"></i>
    <span>Data siswa Anda tidak ditemukan atau tidak aktif. Silakan hubungi admin.</span>
</div>
<?php else: ?>

<div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 20px;">
    <?php foreach ($hariList as $hari): 
        $jadwalHari = array_filter($jadwalList, function($j) use ($hari) { return $j['hari'] === $hari; });
    ?>
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <i class="bi bi-calendar-day text-primary"></i> <?= $hari ?>
            </div>
            <span class="badge bg-secondary"><?= count($jadwalHari) ?> Mata Pelajaran</span>
        </div>
        <div class="card-body" style="padding: 0;">
            <?php if (empty($jadwalHari)): ?>
            <div style="padding: 20px; text-align: center; color: var(--text-muted); font-size: 13px;">
                Tidak ada pelajaran
            </div>
            <?php else: ?>
            <div class="table-wrapper">
                <table class="table" style="margin: 0; width: 100%;">
                    <tbody>
                        <?php foreach ($jadwalHari as $j): ?>
                        <tr>
                            <td style="width: 100px; font-weight: 600; color: var(--primary-light); vertical-align: middle;">
                                <?= substr($j['jam_mulai'], 0, 5) ?> - <?= substr($j['jam_selesai'], 0, 5) ?>
                            </td>
                            <td>
                                <div style="font-weight: 600; color: var(--text-primary);"><?= e($j['mapel_nama']) ?></div>
                                <div style="font-size: 11px; color: var(--text-muted);">
                                    <i class="bi bi-person-badge"></i> <?= e($j['guru_nama']) ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
