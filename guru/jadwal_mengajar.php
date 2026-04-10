<?php
/**
 * Jadwal Mengajar - Guru
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole('guru');
$pageTitle   = 'Jadwal Mengajar';
$activeMenu  = 'jadwal';
$breadcrumbs = [['label' => 'Jadwal Mengajar']];
$db   = getDB();
$user = currentUser();

// Ambil ID Guru berdasarkan User ID
$gs = $db->prepare("SELECT id FROM guru WHERE user_id = ? AND aktif = 1");
$gs->execute([$user['id']]);
$guru = $gs->fetch();

$jadwalList = [];
if ($guru) {
    $stmt = $db->prepare("
        SELECT j.*, mp.nama AS mapel_nama, mp.kode AS mapel_kode, k.nama_kelas
        FROM jadwal j
        JOIN mata_pelajaran mp ON j.mapel_id = mp.id
        JOIN kelas k ON j.kelas_id = k.id
        JOIN tahun_ajaran ta ON k.tahun_ajaran_id = ta.id AND ta.aktif = 1
        WHERE j.guru_id = ?
        ORDER BY FIELD(j.hari, 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'), j.jam_mulai
    ");
    $stmt->execute([$guru['id']]);
    $jadwalList = $stmt->fetchAll();
}

$hariList = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Jadwal Mengajar</h1>
        <p class="page-subtitle">Daftar jadwal mengajar Anda pada tahun ajaran aktif</p>
    </div>
    <button onclick="window.print()" class="btn btn-secondary no-print">
        <i class="bi bi-printer"></i> Cetak Jadwal
    </button>
</div>

<?php if (!$guru): ?>
<div class="alert alert-warning">
    <i class="bi bi-exclamation-triangle-fill"></i>
    <span>Data guru Anda tidak ditemukan atau tidak aktif. Hubungi admin.</span>
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
            <span class="badge bg-secondary"><?= count($jadwalHari) ?> Sesi</span>
        </div>
        <div class="card-body" style="padding: 0;">
            <?php if (empty($jadwalHari)): ?>
            <div style="padding: 20px; text-align: center; color: var(--text-muted); font-size: 13px;">
                Libur / Tidak ada jadwal
            </div>
            <?php else: ?>
            <div class="table-wrapper">
                <table class="table" style="margin: 0;">
                    <tbody>
                        <?php foreach ($jadwalHari as $j): ?>
                        <tr>
                            <td style="width: 100px; font-weight: 600; color: var(--primary-light); vertical-align: middle;">
                                <?= substr($j['jam_mulai'], 0, 5) ?> - <?= substr($j['jam_selesai'], 0, 5) ?>
                            </td>
                            <td>
                                <div style="font-weight: 600; color: var(--text-primary);"><?= e($j['mapel_nama']) ?></div>
                                <div style="font-size: 11px; color: var(--text-muted);">
                                    <i class="bi bi-door-open"></i> Kelas: <?= e($j['nama_kelas']) ?>
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
