<?php
/**
 * Dashboard Siswa - Sistem Absensi Sekolah
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole('siswa');

$pageTitle   = 'Dashboard Siswa';
$activeMenu  = 'dashboard';
$breadcrumbs = [['label' => 'Dashboard']];

$user  = currentUser();
$today = date('Y-m-d');
$db    = getDB();

// Data siswa
$stmtS = $db->prepare("
    SELECT s.*, k.nama_kelas, k.tingkat
    FROM siswa s
    JOIN kelas k ON s.kelas_id = k.id
    WHERE s.user_id = ? LIMIT 1
");
$stmtS->execute([$user['id']]);
$siswa = $stmtS->fetch();
$today = date('Y-m-d');

// Handle Self Check-in/out Siswa
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    verifyCsrf();
    if ($siswa) {
        try {
            $today = date('Y-m-d');
            if ($_POST['action'] === 'student_checkin') {
                $c = $db->prepare("SELECT id FROM absensi_siswa WHERE siswa_id = ? AND tanggal = ?");
                $c->execute([$siswa['id'], $today]);
                if (!$c->fetch()) {
                    $db->prepare("INSERT INTO absensi_siswa (siswa_id, kelas_id, tanggal, jam_masuk, status, dicatat_oleh) VALUES (?, ?, ?, CURTIME(), 'H', ?)")
                       ->execute([$siswa['id'], $siswa['kelas_id'], $today, $user['id']]);
                    redirectWith(APP_URL . '/siswa/dashboard.php', 'success', 'Berhasil melakukan check-in masuk.');
                } else {
                    setFlash('warning', 'Anda sudah melakukan check-in masuk hari ini.');
                }
            } elseif ($_POST['action'] === 'student_checkout') {
                $c = $db->prepare("SELECT id, jam_pulang FROM absensi_siswa WHERE siswa_id = ? AND tanggal = ?");
                $c->execute([$siswa['id'], $today]);
                $absen = $c->fetch();
                if ($absen) {
                    if (!$absen['jam_pulang']) {
                        $db->prepare("UPDATE absensi_siswa SET jam_pulang = CURTIME() WHERE id = ?")
                           ->execute([$absen['id']]);
                        redirectWith(APP_URL . '/siswa/dashboard.php', 'success', 'Berhasil melakukan check-out pulang.');
                    } else {
                        setFlash('warning', 'Anda sudah melakukan check-out hari ini.');
                    }
                } else {
                    setFlash('danger', 'Gagal: Anda belum melakukan check-in masuk.');
                }
            }
        } catch (Exception $e) {
            error_log("[Checkin/out Siswa Error] " . $e->getMessage());
            setFlash('danger', 'Kegagalan sistem: ' . $e->getMessage());
        }
    }
}

// Rekap bulan ini
$bulan = date('m');
$tahun = date('Y');
$rekap = $siswa ? rekapAbsensiSiswa($siswa['id'], $bulan, $tahun) : [];

// Absensi 30 hari terakhir (heatmap/riwayat)
$riwayat = [];
if ($siswa) {
    $stmtR = $db->prepare("
        SELECT tanggal, status, keterangan 
        FROM absensi_siswa 
        WHERE siswa_id = ? 
        ORDER BY tanggal DESC
        LIMIT 30
    ");
    $stmtR->execute([$siswa['id']]);
    $riwayat = $stmtR->fetchAll();
}

// Rekap per bulan (6 bulan terakhir)
$grafikBulan = [];
for ($i = 5; $i >= 0; $i--) {
    $ts = strtotime("-{$i} months");
    $m  = date('m', $ts);
    $y  = date('Y', $ts);
    $rBulan = $siswa ? rekapAbsensiSiswa($siswa['id'], $m, $y) : ['hadir' => 0, 'total' => 0];
    $grafikBulan[] = [
        'label'  => namaBulan((int)$m) . ' ' . $y,
        'hadir'  => (int)($rBulan['hadir'] ?? 0),
        'total'  => (int)($rBulan['total'] ?? 0),
        'persen' => hitungPersentase((int)($rBulan['hadir'] ?? 0), (int)($rBulan['total'] ?? 0)),
    ];
}

// Status hari ini
$statusHariIni = null;
if ($siswa) {
    $stmtH = $db->prepare("SELECT * FROM absensi_siswa WHERE siswa_id = ? AND tanggal = ?");
    $stmtH->execute([$siswa['id'], $today]);
    $statusHariIni = $stmtH->fetch();
}

// Jadwal hari ini
$hariIni = namaHari($today);
$jadwalHariIni = [];
if ($siswa) {
    $stmtJ = $db->prepare("
        SELECT j.*, mp.nama AS mapel_nama, g.nama AS guru_nama
        FROM jadwal j
        JOIN mata_pelajaran mp ON j.mapel_id = mp.id
        JOIN guru g ON j.guru_id = g.id
        WHERE j.kelas_id = ? AND j.hari = ?
        ORDER BY j.jam_mulai
    ");
    $stmtJ->execute([$siswa['kelas_id'], $hariIni]);
    $jadwalHariIni = $stmtJ->fetchAll();
}

include __DIR__ . '/../includes/header.php';
?>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h1 class="page-title">Halo, <?= e(explode(' ', $user['nama'])[0]) ?>!</h1>
        <p class="page-subtitle"><?= namaHari($today) ?>, <?= formatTanggal($today) ?></p>
    </div>
    <a href="<?= APP_URL ?>/siswa/rekap.php" class="btn btn-secondary">
        <i class="bi bi-calendar3"></i> Rekap Lengkap
    </a>
</div>

<?php if (!$siswa): ?>
<div class="alert alert-warning">
    <i class="bi bi-exclamation-triangle-fill"></i>
    <span>Data siswa Anda belum terdaftar. Hubungi administrator sekolah.</span>
</div>
<?php else: ?>

<!-- Profil Singkat & Status Hari Ini -->
<div class="grid" style="grid-template-columns:1fr 2fr;gap:16px;margin-bottom:24px;">

    <!-- Info Siswa -->
    <div class="card">
        <div class="card-body" style="display:flex;flex-direction:column;align-items:center;text-align:center;padding:28px;">
            <div class="avatar avatar-xl" style="margin-bottom:14px;font-size:32px;">
                <?php if (!empty($siswa['foto'])): ?>
                    <img src="<?= APP_URL ?>/<?= e($siswa['foto']) ?>" alt="foto" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">
                <?php else: ?>
                    <?= strtoupper(substr($siswa['nama'], 0, 2)) ?>
                <?php endif; ?>
            </div>
            <div style="font-size:17px;font-weight:700;color:var(--text-primary);margin-bottom:4px;">
                <?= e($siswa['nama']) ?>
            </div>
            <div style="font-size:12px;color:var(--text-muted);margin-bottom:16px;"><?= e($siswa['nis']) ?></div>
            <div style="display:flex;gap:10px;flex-wrap:wrap;justify-content:center;">
                <span class="badge bg-primary" style="font-size:12px;padding:5px 10px;">Kelas <?= e($siswa['nama_kelas']) ?></span>
                <span class="badge bg-secondary" style="font-size:12px;padding:5px 10px;">Tingkat <?= e($siswa['tingkat']) ?></span>
            </div>
        </div>
    </div>

    <!-- Stat Bulan Ini -->
    <div>
        <div class="grid grid-4" style="margin-bottom:16px;">
            <?php
            $statItems = [
                ['Hadir',  $rekap['hadir'] ?? 0,  'success', 'check-circle'],
                ['Izin',   $rekap['izin'] ?? 0,   'warning', 'clipboard'],
                ['Sakit',  $rekap['sakit'] ?? 0,  'info',    'thermometer-half'],
                ['Alpha',  $rekap['alpha'] ?? 0,  'danger',  'x-circle'],
            ];
            foreach ($statItems as [$lbl, $val, $color, $icon]):
            ?>
            <div class="stat-card <?= $color ?>">
                <div class="stat-icon <?= $color ?>"><i class="bi bi-<?= $icon ?>"></i></div>
                <div>
                    <div class="stat-value"><?= $val ?></div>
                    <div class="stat-label"><?= $lbl ?></div>
                    <div class="stat-change text-muted"><?= namaBulan((int)$bulan) ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Persentase Kehadiran -->
        <div class="card">
            <div class="card-body">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
                    <span style="font-size:13px;font-weight:600;color:var(--text-primary);">
                        Persentase Kehadiran Bulan Ini
                    </span>
                    <span style="font-size:22px;font-weight:800;color:var(--<?= warnaPersentase($rekap['persen_hadir'] ?? 0) ?>);">
                        <?= $rekap['persen_hadir'] ?? 0 ?>%
                    </span>
                </div>
                <div class="progress">
                    <div class="progress-bar <?= warnaPersentase($rekap['persen_hadir'] ?? 0) ?>"
                         style="width:<?= $rekap['persen_hadir'] ?? 0 ?>%"></div>
                </div>
                <div style="font-size:11px;color:var(--text-muted);margin-top:6px;">
                    <?= $rekap['hadir'] ?? 0 ?> dari <?= $rekap['total'] ?? 0 ?> hari sekolah
                    <?php if (($rekap['alpha'] ?? 0) >= ALPHA_WARNING_THRESHOLD): ?>
                        &nbsp;<span style="color:var(--danger);font-weight:600;">⚠ Melebihi batas alpha!</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- Status Hari Ini + Grafik -->
<div class="grid grid-2 mb-24">

    <!-- Status Hari Ini -->
    <div class="card">
        <div class="card-header">
            <div class="card-title"><i class="bi bi-calendar-day text-primary"></i> Status Hari Ini</div>
        </div>
        <div class="card-body" style="text-align:center;padding:36px 20px;">
            <?php if ($statusHariIni): ?>
            <?php
            $stIcons = ['H'=>'✅','I'=>'📋','S'=>'🤒','A'=>'❌'];
            $stTexts = ['H'=>'Hadir','I'=>'Izin','S'=>'Sakit','A'=>'Alpha'];
            $stColors = ['H'=>'success','I'=>'warning','S'=>'info','A'=>'danger'];
            $st = $statusHariIni['status'];
            ?>
            <div style="font-size:56px;margin-bottom:8px;"><?= $stIcons[$st] ?></div>
            <div style="font-size:24px;font-weight:800;color:var(--<?= $stColors[$st] ?>);margin-bottom:6px;">
                <?= $stTexts[$st] ?>
            </div>
            <div style="font-size:13px;color:var(--text-muted);margin-bottom:20px;">
                <?= formatTanggal($today) ?><br>
                <?php if ($statusHariIni['jam_masuk']): ?>
                    <span class="badge bg-success" style="font-size:10px;">Masuk: <?= substr($statusHariIni['jam_masuk'], 0, 5) ?></span>
                <?php endif; ?>
                <?php if ($statusHariIni['jam_pulang']): ?>
                    <span class="badge bg-info" style="font-size:10px;">Pulang: <?= substr($statusHariIni['jam_pulang'], 0, 5) ?></span>
                <?php endif; ?>
            </div>

            <?php if ($st === 'H' && !$statusHariIni['jam_pulang']): ?>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= generateCsrf() ?>">
                    <input type="hidden" name="action" value="student_checkout">
                    <button type="submit" class="btn btn-warning w-100" style="background:#f59e0b; border-color:#f59e0b; color:#fff;">
                        <i class="bi bi-box-arrow-right"></i> Check-out Pulang
                    </button>
                </form>
            <?php endif; ?>
            <?php if ($statusHariIni['keterangan']): ?>
            <div style="margin-top:14px;background:rgba(255,255,255,0.04);border-radius:8px;padding:10px;font-size:13px;color:var(--text-secondary);">
                <?= e($statusHariIni['keterangan']) ?>
            </div>
            <?php endif; ?>
            <?php else: ?>
            <div style="font-size:48px;margin-bottom:12px;">📅</div>
            <div style="font-size:16px;font-weight:600;color:var(--text-secondary);">Belum Dicatat</div>
            <div style="font-size:12px;color:var(--text-muted);margin-bottom:16px;">Silakan melakukan check-in mandiri atau hubungi guru.</div>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= generateCsrf() ?>">
                <input type="hidden" name="action" value="student_checkin">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-geo-alt-fill"></i> Check-in Hadir Sekarang
                </button>
            </form>
            <?php endif; ?>
        </div>
    </div>

    <!-- Grafik 6 Bulan -->
    <div class="card">
        <div class="card-header">
            <div class="card-title"><i class="bi bi-graph-up text-primary"></i> Tren Kehadiran 6 Bulan</div>
        </div>
        <div class="card-body">
            <div class="chart-container" style="height:180px;">
                <canvas id="grafikSiswa"></canvas>
            </div>
        </div>
    </div>

</div>

<!-- Jadwal Hari Ini -->
<div class="card mb-24">
    <div class="card-header">
        <div class="card-title"><i class="bi bi-calendar-event text-primary"></i> Jadwal Pelajaran Hari Ini (<?= $hariIni ?>)</div>
        <a href="<?= APP_URL ?>/siswa/jadwal_pelajaran.php" class="btn btn-sm btn-secondary">Lihat Semua</a>
    </div>
    <div class="card-body">
        <?php if (empty($jadwalHariIni)): ?>
            <p class="text-muted" style="margin: 0; font-size: 14px;">Tidak ada pelajaran hari ini.</p>
        <?php else: ?>
            <div style="display: flex; gap: 15px; overflow-x: auto; padding-bottom: 5px;">
                <?php foreach ($jadwalHariIni as $j): ?>
                    <div style="min-width: 200px; background: rgba(255,255,255,0.04); border-left: 4px solid var(--primary); padding: 12px; border-radius: 8px;">
                        <div style="font-weight: 700; color: var(--text-primary); font-size: 14px;"><?= e($j['mapel_nama']) ?></div>
                        <div style="font-size: 12px; color: var(--text-muted); margin: 4px 0;">
                            <i class="bi bi-clock"></i> <?= substr($j['jam_mulai'],0,5) ?> - <?= substr($j['jam_selesai'],0,5) ?>
                        </div>
                        <div style="font-size: 11px; color: var(--primary-light); font-weight: 600;">Guru: <?= e($j['guru_nama']) ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Riwayat Absensi Terakhir -->
<div class="card">
    <div class="card-header">
        <div class="card-title"><i class="bi bi-clock-history text-primary"></i> Riwayat Absensi Terbaru</div>
        <a href="<?= APP_URL ?>/siswa/rekap.php" class="btn btn-sm btn-secondary">
            <i class="bi bi-list-ul"></i> Lihat Semua
        </a>
    </div>
    <div class="table-wrapper">
        <table class="table">
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Hari</th>
                    <th class="text-center">Status</th>
                    <th>Keterangan</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($riwayat as $r): ?>
                <tr>
                    <td><?= formatTanggal($r['tanggal']) ?></td>
                    <td><?= namaHari($r['tanggal']) ?></td>
                    <td class="text-center"><?= statusLabel($r['status']) ?></td>
                    <td><?= $r['keterangan'] ? e($r['keterangan']) : '<span class="text-muted">-</span>' ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($riwayat)): ?>
                <tr><td colspan="4" class="text-center text-muted" style="padding:30px;">Belum ada data absensi.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php endif; ?>

<script>
// Grafik tren 6 bulan
const grafikData = <?= json_encode($grafikBulan) ?>;
new Chart(document.getElementById('grafikSiswa'), {
    type: 'line',
    data: {
        labels: grafikData.map(d => d.label),
        datasets: [{
            label: '% Kehadiran',
            data: grafikData.map(d => d.persen),
            borderColor: '#6366f1',
            backgroundColor: 'rgba(99,102,241,0.15)',
            borderWidth: 2,
            fill: true,
            tension: 0.4,
            pointBackgroundColor: '#6366f1',
            pointRadius: 4,
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: { beginAtZero: true, max: 100, ticks: { callback: v => v + '%' } },
            x: { grid: { display: false } }
        },
        plugins: { legend: { display: false } }
    }
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
