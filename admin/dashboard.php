<?php
/**
 * Dashboard Admin - Sistem Absensi Sekolah
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole('admin');

$pageTitle  = 'Dashboard Admin';
$activeMenu = 'dashboard';
$breadcrumbs = [['label' => 'Dashboard']];

$stat    = getStatHariIni();
$grafik  = getGrafikMingguan();
$alpha   = getSiswaAlphaWarning();
$ta      = getTahunAjaranAktif();
$today   = date('Y-m-d');

// Daftar kelas belum absen hari ini
$db = getDB();
$stmtKelas = $db->prepare("
    SELECT k.id, k.nama_kelas, k.tingkat,
           COALESCE(g.nama, '-') AS wali_kelas,
           (SELECT COUNT(*) FROM siswa WHERE kelas_id = k.id AND aktif = 1) AS jml_siswa,
           (SELECT COUNT(*) FROM absensi_siswa WHERE kelas_id = k.id AND tanggal = ?) AS sudah_absen
    FROM kelas k
    LEFT JOIN guru g ON k.wali_kelas_id = g.id
    JOIN tahun_ajaran ta ON k.tahun_ajaran_id = ta.id AND ta.aktif = 1
    ORDER BY k.tingkat, k.nama_kelas
");
$stmtKelas->execute([$today]);
$kelasList = $stmtKelas->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h1 class="page-title">Dashboard Admin</h1>
        <p class="page-subtitle">
            <?= namaHari($today) ?>, <?= formatTanggal($today) ?>
            <?php if ($ta): ?>
                &mdash; TA <?= e($ta['nama']) ?> Semester <?= e($ta['semester']) ?>
            <?php endif; ?>
        </p>
    </div>
    <a href="<?= APP_URL ?>/admin/absensi_siswa.php" class="btn btn-primary">
        <i class="bi bi-calendar-check"></i> Input Absensi Hari Ini
    </a>
</div>

<!-- Alert: kelas belum absen -->
<?php $belumAbsen = array_filter($kelasList, fn($k) => $k['sudah_absen'] == 0 && $k['jml_siswa'] > 0); ?>
<?php if (!empty($belumAbsen)): ?>
<div class="alert alert-warning mb-16">
    <i class="bi bi-exclamation-triangle-fill"></i>
    <span>
        <strong><?= count($belumAbsen) ?> kelas</strong> belum mengisi absensi hari ini:
        <?= implode(', ', array_map(fn($k) => '<strong>'.$k['nama_kelas'].'</strong>', $belumAbsen)) ?>
    </span>
</div>
<?php endif; ?>

<!-- Alert: siswa alpha -->
<?php if (!empty($alpha)): ?>
<div class="alert alert-danger mb-16">
    <i class="bi bi-exclamation-circle-fill"></i>
    <span>
        <strong><?= count($alpha) ?> siswa</strong> melebihi batas alpha (≥<?= ALPHA_WARNING_THRESHOLD ?>).
        <a href="<?= APP_URL ?>/laporan/alpha_warning.php" style="color:inherit;font-weight:600;">Lihat Detail &rarr;</a>
    </span>
</div>
<?php endif; ?>

<!-- Stat Cards -->
<div class="grid grid-4 mb-24">
    <div class="stat-card success">
        <div class="stat-icon success"><i class="bi bi-people-fill"></i></div>
        <div>
            <div class="stat-value"><?= number_format($stat['total_siswa']) ?></div>
            <div class="stat-label">Total Siswa Aktif</div>
            <div class="stat-change text-success">
                <i class="bi bi-check2-circle"></i> <?= $stat['hadir'] ?> hadir hari ini
            </div>
        </div>
    </div>
    <div class="stat-card warning">
        <div class="stat-icon warning"><i class="bi bi-person-x-fill"></i></div>
        <div>
            <div class="stat-value"><?= $stat['alpha'] + $stat['izin'] + $stat['sakit'] ?></div>
            <div class="stat-label">Tidak Hadir Hari Ini</div>
            <div class="stat-change text-muted">
                S:<?= $stat['sakit'] ?> I:<?= $stat['izin'] ?> A:<?= $stat['alpha'] ?>
            </div>
        </div>
    </div>
    <div class="stat-card info">
        <div class="stat-icon info"><i class="bi bi-person-badge-fill"></i></div>
        <div>
            <div class="stat-value"><?= number_format($stat['total_guru']) ?></div>
            <div class="stat-label">Total Guru Aktif</div>
            <div class="stat-change text-info">
                <i class="bi bi-check2-circle"></i> <?= $stat['guru_hadir'] ?> hadir hari ini
            </div>
        </div>
    </div>
    <div class="stat-card <?= $stat['kelas_belum_absen'] > 0 ? 'danger' : 'primary' ?>">
        <div class="stat-icon <?= $stat['kelas_belum_absen'] > 0 ? 'danger' : 'primary' ?>">
            <i class="bi bi-door-open-fill"></i>
        </div>
        <div>
            <div class="stat-value"><?= $stat['kelas_belum_absen'] ?></div>
            <div class="stat-label">Kelas Belum Absen</div>
            <div class="stat-change text-muted">dari <?= $stat['total_kelas'] ?> total kelas</div>
        </div>
    </div>
</div>

<!-- Charts + Tabel Row -->
<div class="grid grid-2-1 mb-24">

    <!-- Grafik Mingguan -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <i class="bi bi-bar-chart-fill text-primary"></i>
                Kehadiran 7 Hari Terakhir
            </div>
        </div>
        <div class="card-body">
            <div class="chart-container" style="height:260px;">
                <canvas id="grafikMingguan"></canvas>
            </div>
        </div>
    </div>

    <!-- Rekap Hari Ini Pie -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <i class="bi bi-pie-chart-fill text-primary"></i>
                Absensi Hari Ini
            </div>
        </div>
        <div class="card-body">
            <?php
            $totalToday = $stat['hadir'] + $stat['izin'] + $stat['sakit'] + $stat['alpha'];
            ?>
            <?php if ($totalToday > 0): ?>
            <canvas id="pieHariIni" style="max-height:200px;"></canvas>
            <div style="display:flex;justify-content:center;gap:12px;margin-top:16px;flex-wrap:wrap;">
                <div style="font-size:12px;display:flex;align-items:center;gap:5px;"><span style="width:10px;height:10px;border-radius:50%;background:#10b981;display:inline-block;"></span>Hadir: <?= $stat['hadir'] ?></div>
                <div style="font-size:12px;display:flex;align-items:center;gap:5px;"><span style="width:10px;height:10px;border-radius:50%;background:#f59e0b;display:inline-block;"></span>Izin: <?= $stat['izin'] ?></div>
                <div style="font-size:12px;display:flex;align-items:center;gap:5px;"><span style="width:10px;height:10px;border-radius:50%;background:#06b6d4;display:inline-block;"></span>Sakit: <?= $stat['sakit'] ?></div>
                <div style="font-size:12px;display:flex;align-items:center;gap:5px;"><span style="width:10px;height:10px;border-radius:50%;background:#ef4444;display:inline-block;"></span>Alpha: <?= $stat['alpha'] ?></div>
            </div>
            <?php else: ?>
            <div class="empty-state" style="padding:40px 20px;">
                <div class="empty-icon"><i class="bi bi-calendar-x"></i></div>
                <p class="empty-title">Belum Ada Data</p>
                <p class="empty-desc">Absensi hari ini belum diisi</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Tabel Status Kelas + Siswa Alpha -->
<div class="grid grid-2">

    <!-- Status Kelas Hari Ini -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <i class="bi bi-door-closed text-primary"></i>
                Status Absensi Kelas
            </div>
            <a href="<?= APP_URL ?>/laporan/rekap_harian.php" class="btn btn-sm btn-secondary">
                <i class="bi bi-eye"></i> Detail
            </a>
        </div>
        <div class="table-wrapper">
            <table class="table">
                <thead>
                    <tr>
                        <th>Kelas</th>
                        <th>Wali Kelas</th>
                        <th class="text-center">Siswa</th>
                        <th class="text-center">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($kelasList as $kls): ?>
                    <tr>
                        <td><strong style="color:var(--text-primary)"><?= e($kls['nama_kelas']) ?></strong></td>
                        <td><?= e($kls['wali_kelas']) ?></td>
                        <td class="text-center"><?= $kls['jml_siswa'] ?></td>
                        <td class="text-center">
                            <?php if ($kls['jml_siswa'] == 0): ?>
                                <span class="badge bg-secondary">Kosong</span>
                            <?php elseif ($kls['sudah_absen'] > 0): ?>
                                <span class="badge bg-success"><i class="bi bi-check2"></i> Sudah</span>
                            <?php else: ?>
                                <span class="badge bg-danger"><i class="bi bi-x"></i> Belum</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($kelasList)): ?>
                    <tr><td colspan="4" class="text-center text-muted">Tidak ada data kelas aktif.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Siswa Alpha Warning -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <i class="bi bi-exclamation-triangle-fill text-warning"></i>
                Siswa Peringatan Alpha (Bulan Ini)
            </div>
            <a href="<?= APP_URL ?>/laporan/alpha_warning.php" class="btn btn-sm btn-secondary">
                <i class="bi bi-eye"></i> Detail
            </a>
        </div>
        <div class="table-wrapper">
            <table class="table">
                <thead>
                    <tr>
                        <th>NIS</th>
                        <th>Nama Siswa</th>
                        <th>Kelas</th>
                        <th class="text-center">Alpha</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (array_slice($alpha, 0, 8) as $sw): ?>
                    <tr>
                        <td><?= e($sw['nis']) ?></td>
                        <td style="color:var(--text-primary);font-weight:500;"><?= e($sw['nama']) ?></td>
                        <td><?= e($sw['nama_kelas']) ?></td>
                        <td class="text-center">
                            <span class="badge bg-danger"><?= $sw['jumlah_alpha'] ?>x</span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($alpha)): ?>
                    <tr>
                        <td colspan="4" style="text-align:center;padding:30px;">
                            <i class="bi bi-check-circle-fill text-success" style="font-size:24px;"></i>
                            <p style="color:var(--text-muted);font-size:13px;margin-top:8px;">Semua siswa di bawah batas alpha.</p>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div><!-- end grid-2 -->

<script>
// Grafik Mingguan
const grafikData = <?= json_encode($grafik) ?>;
new Chart(document.getElementById('grafikMingguan'), {
    type: 'bar',
    data: {
        labels: grafikData.labels,
        datasets: [
            {
                label: 'Hadir',
                data: grafikData.hadir,
                backgroundColor: 'rgba(16,185,129,0.7)',
                borderRadius: 6,
                borderSkipped: false,
            },
            {
                label: 'Alpha',
                data: grafikData.alpha,
                backgroundColor: 'rgba(239,68,68,0.6)',
                borderRadius: 6,
                borderSkipped: false,
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { position: 'top' } },
        scales: {
            x: { grid: { display: false } },
            y: { beginAtZero: true, ticks: { stepSize: 1 } }
        }
    }
});

// Pie Chart Hari Ini
const pieEl = document.getElementById('pieHariIni');
if (pieEl) {
    new Chart(pieEl, {
        type: 'doughnut',
        data: {
            labels: ['Hadir','Izin','Sakit','Alpha'],
            datasets: [{
                data: [<?= $stat['hadir'] ?>, <?= $stat['izin'] ?>, <?= $stat['sakit'] ?>, <?= $stat['alpha'] ?>],
                backgroundColor: ['rgba(16,185,129,0.8)','rgba(245,158,11,0.8)','rgba(6,182,212,0.8)','rgba(239,68,68,0.8)'],
                borderColor: ['#10b981','#f59e0b','#06b6d4','#ef4444'],
                borderWidth: 2,
                hoverOffset: 6,
            }]
        },
        options: {
            responsive: true,
            cutout: '65%',
            plugins: { legend: { display: false } },
        }
    });
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
