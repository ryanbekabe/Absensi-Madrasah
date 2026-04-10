<?php
/**
 * Sidebar Navigation - Sistem Absensi Sekolah
 * Ditampilkan berdasarkan role user
 */
$role = $_SESSION['role'] ?? '';
$user = currentUser();

// Inisial nama untuk avatar
$words    = explode(' ', $user['nama'] ?? 'U');
$initials = strtoupper(substr($words[0], 0, 1) . (isset($words[1]) ? substr($words[1], 0, 1) : ''));

// Helper: tandai menu aktif
function navLink(string $href, string $icon, string $label, string $activeMenu, string $key, string $badge = ''): string {
    $active = ($activeMenu === $key) ? ' active' : '';
    $b = $badge ? '<span class="nav-badge">' . $badge . '</span>' : '';
    return '<li class="nav-item">
        <a class="nav-link' . $active . '" href="' . $href . '">
            <i class="bi bi-' . $icon . '"></i>
            <span>' . $label . '</span>' . $b . '
        </a>
    </li>';
}
?>
<nav class="sidebar" id="sidebar">
    <!-- Brand -->
    <a class="sidebar-brand" href="<?= APP_URL ?>">
        <div class="sidebar-brand-icon">🏫</div>
        <div class="sidebar-brand-text">
            <span class="sidebar-brand-name"><?= APP_NAME ?></span>
            <span class="sidebar-brand-sub">v<?= APP_VERSION ?></span>
        </div>
    </a>

    <!-- User Info -->
    <div class="sidebar-user">
        <div class="sidebar-avatar">
            <?php if (!empty($user['foto'])): ?>
                <img src="<?= APP_URL ?>/<?= e($user['foto']) ?>" alt="avatar">
            <?php else: ?>
                <?= e($initials) ?>
            <?php endif; ?>
        </div>
        <div class="sidebar-user-info">
            <div class="sidebar-user-name" title="<?= e($user['nama']) ?>"><?= e($user['nama']) ?></div>
            <div class="sidebar-user-role"><?= e($role) ?></div>
        </div>
    </div>

    <!-- Navigation -->
    <div class="sidebar-nav">

        <?php if ($role === 'admin'): ?>
        <!-- ======= ADMIN MENU ======= -->
        <div class="nav-section-label">Utama</div>
        <ul class="nav-item" style="list-style:none;padding:0;">
            <?= navLink(APP_URL.'/admin/dashboard.php', 'speedometer2', 'Dashboard', $activeMenu, 'dashboard') ?>
        </ul>

        <div class="nav-section-label">Absensi</div>
        <ul style="list-style:none;padding:0;">
            <?= navLink(APP_URL.'/admin/absensi_siswa.php', 'calendar-check', 'Absensi Siswa', $activeMenu, 'absensi_siswa') ?>
            <?= navLink(APP_URL.'/admin/absensi_guru.php', 'person-check', 'Absensi Guru', $activeMenu, 'absensi_guru') ?>
        </ul>

        <div class="nav-section-label">Data Master</div>
        <ul style="list-style:none;padding:0;">
            <?= navLink(APP_URL.'/admin/siswa/', 'people', 'Data Siswa', $activeMenu, 'siswa') ?>
            <?= navLink(APP_URL.'/admin/guru/', 'person-badge', 'Data Guru', $activeMenu, 'guru') ?>
            <?= navLink(APP_URL.'/admin/kelas/', 'door-open', 'Data Kelas', $activeMenu, 'kelas') ?>
            <?= navLink(APP_URL.'/admin/mapel/', 'book', 'Mata Pelajaran', $activeMenu, 'mapel') ?>
            <?= navLink(APP_URL.'/admin/jadwal/', 'calendar3', 'Jadwal Pelajaran', $activeMenu, 'jadwal') ?>
            <?= navLink(APP_URL.'/admin/tahun_ajaran/', 'calendar2-range', 'Tahun Ajaran', $activeMenu, 'tahun_ajaran') ?>
        </ul>

        <div class="nav-section-label">Laporan</div>
        <ul style="list-style:none;padding:0;">
            <?= navLink(APP_URL.'/laporan/rekap_harian.php', 'file-earmark-bar-graph', 'Rekap Harian', $activeMenu, 'rekap_harian') ?>
            <?= navLink(APP_URL.'/laporan/rekap_bulanan.php', 'file-earmark-spreadsheet', 'Rekap Bulanan', $activeMenu, 'rekap_bulanan') ?>
            <?= navLink(APP_URL.'/laporan/alpha_warning.php', 'exclamation-triangle', 'Peringatan Alpha', $activeMenu, 'alpha_warning') ?>
        </ul>

        <div class="nav-section-label">Sistem</div>
        <ul style="list-style:none;padding:0;">
            <?= navLink(APP_URL.'/admin/users/', 'person-gear', 'Pengguna', $activeMenu, 'users') ?>
            <?= navLink(APP_URL.'/admin/audit_log.php', 'journal-text', 'Audit Log', $activeMenu, 'audit_log') ?>
        </ul>

        <?php elseif ($role === 'guru'): ?>
        <!-- ======= GURU MENU ======= -->
        <div class="nav-section-label">Utama</div>
        <ul style="list-style:none;padding:0;">
            <?= navLink(APP_URL.'/guru/dashboard.php', 'speedometer2', 'Dashboard', $activeMenu, 'dashboard') ?>
        </ul>

        <div class="nav-section-label">Absensi</div>
        <ul style="list-style:none;padding:0;">
            <?= navLink(APP_URL.'/guru/absensi.php', 'calendar-check', 'Input Absensi', $activeMenu, 'absensi') ?>
            <?= navLink(APP_URL.'/guru/riwayat.php', 'clock-history', 'Riwayat Absensi', $activeMenu, 'riwayat') ?>
            <?= navLink(APP_URL.'/guru/jadwal_mengajar.php', 'calendar3', 'Jadwal Mengajar', $activeMenu, 'jadwal') ?>
        </ul>

        <div class="nav-section-label">Laporan</div>
        <ul style="list-style:none;padding:0;">
            <?= navLink(APP_URL.'/guru/rekap.php', 'file-earmark-bar-graph', 'Rekap Kelas', $activeMenu, 'rekap') ?>
            <?= navLink(APP_URL.'/guru/siswa_alpha.php', 'exclamation-triangle', 'Siswa Peringatan', $activeMenu, 'siswa_alpha') ?>
        </ul>

        <?php elseif ($role === 'siswa'): ?>
        <!-- ======= SISWA MENU ======= -->
        <div class="nav-section-label">Utama</div>
        <ul style="list-style:none;padding:0;">
            <?= navLink(APP_URL.'/siswa/dashboard.php', 'speedometer2', 'Dashboard', $activeMenu, 'dashboard') ?>
            <?= navLink(APP_URL.'/siswa/jadwal_pelajaran.php', 'calendar3', 'Jadwal Pelajaran', $activeMenu, 'jadwal') ?>
            <?= navLink(APP_URL.'/siswa/rekap.php', 'file-earmark-bar-graph', 'Rekap Kehadiran', $activeMenu, 'rekap') ?>
        </ul>

        <?php endif; ?>

        <!-- Common: Profile & Logout -->
        <div class="nav-section-label">Akun</div>
        <ul style="list-style:none;padding:0;">
            <?= navLink(APP_URL.'/profile.php', 'person-circle', 'Profil Saya', $activeMenu, 'profile') ?>
            <li class="nav-item">
                <a class="nav-link" href="<?= APP_URL ?>/logout.php" 
                   onclick="return confirm('Yakin ingin logout?')"
                   style="color:#f87171;">
                    <i class="bi bi-box-arrow-right"></i>
                    <span>Logout</span>
                </a>
            </li>
        </ul>

    </div><!-- end .sidebar-nav -->
</nav>
