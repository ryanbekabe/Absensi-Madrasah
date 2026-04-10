<?php
/**
 * Dashboard Guru - Sistem Absensi Sekolah
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole('guru');

$pageTitle   = 'Dashboard Guru';
$activeMenu  = 'dashboard';
$breadcrumbs = [['label' => 'Dashboard']];

$user  = currentUser();
$today = date('Y-m-d');
$db    = getDB();

// Ambil data guru berdasarkan user_id
$stmtG = $db->prepare("SELECT * FROM guru WHERE user_id = ? LIMIT 1");
$stmtG->execute([$user['id']]);
$guru  = $stmtG->fetch();

// Kelas yang diajar / wali kelas
$kelasList = [];
if ($guru) {
    $kelasList = getKelasByGuru($guru['id']);
}

// Status absensi kelas hari ini
foreach ($kelasList as &$kls) {
    $s = $db->prepare("SELECT COUNT(*) FROM absensi_siswa WHERE kelas_id = ? AND tanggal = ?");
    $s->execute([$kls['id'], $today]);
    $kls['sudah_absen'] = (int)$s->fetchColumn();

    $s2 = $db->prepare("SELECT COUNT(*) FROM siswa WHERE kelas_id = ? AND aktif = 1");
    $s2->execute([$kls['id']]);
    $kls['jml_siswa'] = (int)$s2->fetchColumn();
}
unset($kls);

// Absensi sendiri hari ini
$stmtAbsen = null;
$absenGuru = null;
if ($guru) {
    $stmtAbsen = $db->prepare("SELECT * FROM absensi_guru WHERE guru_id = ? AND tanggal = ?");
    $stmtAbsen->execute([$guru['id'], $today]);
    $absenGuru = $stmtAbsen->fetch();
}

// Rekap bulan ini untuk semua kelas yang diampu
$bulanIni = date('m');
$tahunIni = date('Y');
$totalHariAjar = 0; $totalHadir = 0; $totalAlpha = 0;
foreach ($kelasList as $kls) {
    $sR = $db->prepare("
        SELECT COUNT(*) total, SUM(status='H') hadir, SUM(status='A') alpha
        FROM absensi_siswa 
        WHERE kelas_id = ? AND MONTH(tanggal)=? AND YEAR(tanggal)=?
    ");
    $sR->execute([$kls['id'], $bulanIni, $tahunIni]);
    $r = $sR->fetch();
    $totalHariAjar += $r['total'] ?? 0;
    $totalHadir    += $r['hadir'] ?? 0;
    $totalAlpha    += $r['alpha'] ?? 0;
}

// Jadwal hari ini
$hariIni = namaHari($today);
$jadwalHariIni = [];
if ($guru) {
    $stmtJ = $db->prepare("
        SELECT j.*, mp.nama AS mapel_nama, k.nama_kelas
        FROM jadwal j
        JOIN mata_pelajaran mp ON j.mapel_id = mp.id
        JOIN kelas k ON j.kelas_id = k.id
        JOIN tahun_ajaran ta ON k.tahun_ajaran_id = ta.id AND ta.aktif = 1
        WHERE j.guru_id = ? AND j.hari = ?
        ORDER BY j.jam_mulai
    ");
    $stmtJ->execute([$guru['id'], $hariIni]);
    $jadwalHariIni = $stmtJ->fetchAll();
}

include __DIR__ . '/../includes/header.php';
?>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h1 class="page-title">Selamat Datang, <?= e(explode(' ', $user['nama'])[0]) ?>!</h1>
        <p class="page-subtitle"><?= namaHari($today) ?>, <?= formatTanggal($today) ?></p>
    </div>
    <?php if (!empty($kelasList)): ?>
    <a href="<?= APP_URL ?>/guru/absensi.php" class="btn btn-primary">
        <i class="bi bi-calendar-check"></i> Input Absensi
    </a>
    <?php endif; ?>
</div>

<!-- Alert kelas belum absen -->
<?php
$belum = array_filter($kelasList, fn($k) => $k['sudah_absen'] == 0 && $k['jml_siswa'] > 0);
if (!empty($belum)):
?>
<div class="alert alert-warning mb-16">
    <i class="bi bi-exclamation-triangle-fill"></i>
    <div>
        <strong>Perhatian!</strong> Kelas berikut belum diisi absensi hari ini:
        <?= implode(', ', array_map(fn($k) => '<strong>'.$k['nama_kelas'].'</strong>', $belum)) ?>.
        <a href="<?= APP_URL ?>/guru/absensi.php" style="color:inherit;font-weight:700;"> Isi Sekarang &rarr;</a>
    </div>
</div>
<?php endif; ?>

<!-- Absensi guru hari ini -->
<?php if ($guru && !$absenGuru): ?>
<div class="alert alert-info mb-16">
    <i class="bi bi-info-circle-fill"></i>
    <span>Kehadiran Anda hari ini belum dicatat. Hubungi admin untuk mencatat kehadiran.</span>
</div>
<?php endif; ?>

<!-- Stat Cards -->
<div class="grid grid-3 mb-24">
    <div class="stat-card primary">
        <div class="stat-icon primary"><i class="bi bi-door-open"></i></div>
        <div>
            <div class="stat-value"><?= count($kelasList) ?></div>
            <div class="stat-label">Kelas Diampu</div>
            <div class="stat-change text-muted"><?= count($belum) ?> belum absen hari ini</div>
        </div>
    </div>
    <div class="stat-card success">
        <div class="stat-icon success"><i class="bi bi-check2-circle"></i></div>
        <div>
            <div class="stat-value"><?= $totalHadir ?></div>
            <div class="stat-label">Total Kehadiran Bulan Ini</div>
            <div class="stat-change text-muted">dari seluruh kelas</div>
        </div>
    </div>
    <div class="stat-card <?= $totalAlpha > 0 ? 'danger' : 'success' ?>">
        <div class="stat-icon <?= $totalAlpha > 0 ? 'danger' : 'success' ?>">
            <i class="bi bi-exclamation-circle"></i>
        </div>
        <div>
            <div class="stat-value"><?= $totalAlpha ?></div>
            <div class="stat-label">Total Alpha Bulan Ini</div>
            <div class="stat-change text-muted"><?= namaBulan((int)$bulanIni) ?> <?= $tahunIni ?></div>
        </div>
    </div>
</div>

<!-- Jadwal Hari Ini -->
<div class="card mb-24">
    <div class="card-header">
        <div class="card-title"><i class="bi bi-calendar-event text-primary"></i> Jadwal Mengajar Hari Ini (<?= $hariIni ?>)</div>
        <a href="<?= APP_URL ?>/guru/jadwal_mengajar.php" class="btn btn-sm btn-secondary">Lihat Semua</a>
    </div>
    <div class="card-body">
        <?php if (empty($jadwalHariIni)): ?>
            <p class="text-muted" style="margin: 0; font-size: 14px;">Tidak ada jadwal mengajar hari ini.</p>
        <?php else: ?>
            <div style="display: flex; gap: 15px; overflow-x: auto; padding-bottom: 5px;">
                <?php foreach ($jadwalHariIni as $j): ?>
                    <div style="min-width: 200px; background: rgba(255,255,255,0.04); border-left: 4px solid var(--primary); padding: 12px; border-radius: 8px;">
                        <div style="font-weight: 700; color: var(--text-primary); font-size: 14px;"><?= e($j['mapel_nama']) ?></div>
                        <div style="font-size: 12px; color: var(--text-muted); margin: 4px 0;">
                            <i class="bi bi-clock"></i> <?= substr($j['jam_mulai'],0,5) ?> - <?= substr($j['jam_selesai'],0,5) ?>
                        </div>
                        <div style="font-size: 11px; color: var(--primary-light); font-weight: 600;">Kelas: <?= e($j['nama_kelas']) ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="grid grid-2">
    <!-- Daftar Kelas -->
    <div class="card">
        <div class="card-header">
            <div class="card-title"><i class="bi bi-door-open text-primary"></i> Kelas Saya</div>
            <a href="<?= APP_URL ?>/guru/absensi.php" class="btn btn-sm btn-primary">
                <i class="bi bi-calendar-check"></i> Absensi
            </a>
        </div>
        <?php if (empty($kelasList)): ?>
        <div class="card-body">
            <div class="empty-state">
                <div class="empty-icon"><i class="bi bi-door-closed"></i></div>
                <p class="empty-title">Belum Ada Kelas</p>
                <p class="empty-desc">Anda belum menjadi wali kelas manapun.</p>
            </div>
        </div>
        <?php else: ?>
        <div class="table-wrapper">
            <table class="table">
                <thead>
                    <tr><th>Kelas</th><th class="text-center">Siswa</th><th class="text-center">Absen Hari Ini</th><th></th></tr>
                </thead>
                <tbody>
                    <?php foreach ($kelasList as $kls): ?>
                    <tr>
                        <td><strong style="color:var(--text-primary)"><?= e($kls['nama_kelas']) ?></strong></td>
                        <td class="text-center"><?= $kls['jml_siswa'] ?></td>
                        <td class="text-center">
                            <?php if ($kls['sudah_absen'] > 0): ?>
                                <span class="badge bg-success"><i class="bi bi-check2"></i> Sudah</span>
                            <?php else: ?>
                                <span class="badge bg-danger"><i class="bi bi-x"></i> Belum</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="<?= APP_URL ?>/guru/absensi.php?kelas_id=<?= $kls['id'] ?>" class="btn btn-sm btn-primary">
                                <i class="bi bi-pencil"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <!-- Kehadiran Guru Hari Ini -->
    <div class="card">
        <div class="card-header">
            <div class="card-title"><i class="bi bi-person-check text-primary"></i> Status Kehadiran Saya</div>
        </div>
        <div class="card-body">
            <?php if ($absenGuru): ?>
            <div style="text-align:center;padding:20px 0;">
                <div style="font-size:48px;margin-bottom:8px;">
                    <?php
                    $icons = ['Hadir'=>'✅','Izin'=>'📋','Sakit'=>'🤒','Dinas Luar'=>'🏢','Cuti'=>'🌴','Alpha'=>'❌'];
                    echo $icons[$absenGuru['status']] ?? '❓';
                    ?>
                </div>
                <div style="font-size:22px;font-weight:700;color:var(--text-primary);margin-bottom:4px;">
                    <?= e($absenGuru['status']) ?>
                </div>
                <div style="font-size:13px;color:var(--text-muted);">Tercatat: <?= formatTanggal($today) ?></div>
                <?php if ($absenGuru['keterangan']): ?>
                <div style="margin-top:12px;background:rgba(255,255,255,0.04);border-radius:8px;padding:10px;font-size:13px;color:var(--text-secondary);">
                    <?= e($absenGuru['keterangan']) ?>
                </div>
                <?php endif; ?>
            </div>
            <?php else: ?>
            <div class="empty-state">
                <div class="empty-icon"><i class="bi bi-person-dash"></i></div>
                <p class="empty-title">Belum Dicatat</p>
                <p class="empty-desc">Kehadiran hari ini belum dicatat oleh admin.</p>
            </div>
            <?php endif; ?>
        </div>

        <!-- Rekap bulan ini -->
        <div class="card-header" style="margin-top:0;">
            <div class="card-title"><i class="bi bi-calendar3 text-primary"></i> Rekap Bulan Ini</div>
        </div>
        <?php
        if ($guru) {
            $sRG = $db->prepare("
                SELECT 
                    SUM(status='Hadir') hadir, SUM(status='Izin') izin,
                    SUM(status='Sakit') sakit, SUM(status='Dinas Luar') dinas,
                    SUM(status='Cuti') cuti, SUM(status='Alpha') alpha,
                    COUNT(*) total
                FROM absensi_guru 
                WHERE guru_id=? AND MONTH(tanggal)=? AND YEAR(tanggal)=?
            ");
            $sRG->execute([$guru['id'], $bulanIni, $tahunIni]);
            $rg = $sRG->fetch();
        } else { $rg = []; }
        ?>
        <div class="card-body">
            <div class="grid grid-3" style="gap:8px;">
                <?php
                $items = [
                    ['Hadir',     $rg['hadir'] ?? 0,  'success'],
                    ['Izin',      $rg['izin'] ?? 0,   'warning'],
                    ['Sakit',     $rg['sakit'] ?? 0,  'info'],
                    ['Dinas',     $rg['dinas'] ?? 0,  'primary'],
                    ['Cuti',      $rg['cuti'] ?? 0,   'secondary'],
                    ['Alpha',     $rg['alpha'] ?? 0,  'danger'],
                ];
                foreach ($items as [$label, $val, $color]):
                ?>
                <div style="text-align:center;background:rgba(255,255,255,0.04);border-radius:8px;padding:12px 6px;">
                    <div style="font-size:20px;font-weight:700;color:var(--<?= $color === 'secondary' ? 'text-secondary' : $color ?>);"><?= $val ?></div>
                    <div style="font-size:11px;color:var(--text-muted);"><?= $label ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
