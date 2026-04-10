<?php
/**
 * Helper Functions - Sistem Absensi Sekolah
 */

// ============================================================
// Sanitasi Output
// ============================================================
function e($str): string {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

// ============================================================
// Format Tanggal
// ============================================================
function formatTanggal(string $date, string $format = 'd F Y'): string {
    if (empty($date) || $date === '0000-00-00') return '-';
    $bulan = [
        1  => 'Januari', 2  => 'Februari', 3  => 'Maret',
        4  => 'April',   5  => 'Mei',       6  => 'Juni',
        7  => 'Juli',    8  => 'Agustus',   9  => 'September',
        10 => 'Oktober', 11 => 'November',  12 => 'Desember',
    ];
    $ts = strtotime($date);
    if ($format === 'd F Y') {
        return date('d', $ts) . ' ' . $bulan[(int)date('n', $ts)] . ' ' . date('Y', $ts);
    }
    return date($format, $ts);
}

function namaHari(string $date): string {
    $hari = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
    return $hari[(int)date('w', strtotime($date))];
}

function namaBulan(int $bulan): string {
    $arr = [
        1  => 'Januari', 2  => 'Februari', 3  => 'Maret',
        4  => 'April',   5  => 'Mei',       6  => 'Juni',
        7  => 'Juli',    8  => 'Agustus',   9  => 'September',
        10 => 'Oktober', 11 => 'November',  12 => 'Desember',
    ];
    return $arr[$bulan] ?? '-';
}

// ============================================================
// Status Absensi
// ============================================================
function statusLabel(string $status, string $type = 'siswa'): string {
    if ($type === 'siswa') {
        $map = [
            'H' => ['label' => 'Hadir',  'class' => 'success'],
            'I' => ['label' => 'Izin',   'class' => 'warning'],
            'S' => ['label' => 'Sakit',  'class' => 'info'],
            'A' => ['label' => 'Alpha',  'class' => 'danger'],
        ];
        $s = $map[$status] ?? ['label' => $status, 'class' => 'secondary'];
        return '<span class="badge bg-' . $s['class'] . '">' . $s['label'] . '</span>';
    }
    // Guru
    $map = [
        'Hadir'      => 'success',
        'Izin'       => 'warning',
        'Sakit'      => 'info',
        'Dinas Luar' => 'primary',
        'Cuti'       => 'secondary',
        'Alpha'      => 'danger',
    ];
    $class = $map[$status] ?? 'secondary';
    return '<span class="badge bg-' . $class . '">' . e($status) . '</span>';
}

function statusText(string $status): string {
    $map = ['H' => 'Hadir', 'I' => 'Izin', 'S' => 'Sakit', 'A' => 'Alpha'];
    return $map[$status] ?? $status;
}

// ============================================================
// Hitung Persentase Kehadiran
// ============================================================
function hitungPersentase(int $hadir, int $total): float {
    if ($total === 0) return 0.0;
    return round(($hadir / $total) * 100, 1);
}

function warnaPersentase(float $pct): string {
    if ($pct >= 80) return 'success';
    if ($pct >= 60) return 'warning';
    return 'danger';
}

// ============================================================
// Upload Foto
// ============================================================
function uploadFoto(array $file, string $subfolder = 'siswa'): ?string {
    $uploadDir = APP_ROOT . '/assets/img/uploads/' . $subfolder . '/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0775, true);
    }

    $allowed   = ['jpg', 'jpeg', 'png', 'webp'];
    $ext       = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $maxSize   = 2 * 1024 * 1024; // 2MB

    if ($file['error'] !== UPLOAD_ERR_OK) return null;
    if (!in_array($ext, $allowed)) return null;
    if ($file['size'] > $maxSize) return null;

    $filename = uniqid($subfolder . '_', true) . '.' . $ext;
    if (move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
        return 'assets/img/uploads/' . $subfolder . '/' . $filename;
    }
    return null;
}

function hapusFoto(?string $path): void {
    if ($path && file_exists(APP_ROOT . '/' . $path)) {
        unlink(APP_ROOT . '/' . $path);
    }
}

// ============================================================
// Dapatkan Tahun Ajaran Aktif
// ============================================================
function getTahunAjaranAktif(): ?array {
    $db = getDB();
    $stmt = $db->query("SELECT * FROM tahun_ajaran WHERE aktif = 1 LIMIT 1");
    return $stmt->fetch() ?: null;
}

// ============================================================
// Ambil kelas berdasarkan user guru (wali kelas)
// ============================================================
function getKelasByGuru(int $guruId): array {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT k.*, ta.nama AS tahun_ajaran
        FROM kelas k 
        LEFT JOIN tahun_ajaran ta ON k.tahun_ajaran_id = ta.id
        WHERE k.wali_kelas_id = ? AND ta.aktif = 1
        ORDER BY k.tingkat, k.nama_kelas
    ");
    $stmt->execute([$guruId]);
    return $stmt->fetchAll();
}

// ============================================================
// Rekap Absensi Siswa
// ============================================================
function rekapAbsensiSiswa(int $siswaId, string $bulan = '', string $tahun = ''): array {
    $db = getDB();
    $bulan = $bulan ?: date('m');
    $tahun = $tahun ?: date('Y');

    $stmt = $db->prepare("
        SELECT 
            COUNT(*) as total,
            SUM(status = 'H') as hadir,
            SUM(status = 'I') as izin,
            SUM(status = 'S') as sakit,
            SUM(status = 'A') as alpha
        FROM absensi_siswa
        WHERE siswa_id = ?
          AND MONTH(tanggal) = ?
          AND YEAR(tanggal) = ?
    ");
    $stmt->execute([$siswaId, $bulan, $tahun]);
    $data = $stmt->fetch();

    $data['persen_hadir'] = hitungPersentase((int)($data['hadir'] ?? 0), (int)($data['total'] ?? 0));
    return $data;
}

// ============================================================
// Statistik dashboard admin - hari ini
// ============================================================
function getStatHariIni(): array {
    $db    = getDB();
    $today = date('Y-m-d');

    // Total siswa aktif
    $totalSiswa = $db->query("SELECT COUNT(*) FROM siswa WHERE aktif = 1")->fetchColumn();

    // Absensi hari ini
    $stmt = $db->prepare("
        SELECT 
            SUM(status='H') hadir, SUM(status='I') izin,
            SUM(status='S') sakit, SUM(status='A') alpha,
            COUNT(*) total_tercatat
        FROM absensi_siswa WHERE tanggal = ?
    ");
    $stmt->execute([$today]);
    $absensi = $stmt->fetch();

    // Total guru aktif
    $totalGuru = $db->query("SELECT COUNT(*) FROM guru WHERE aktif = 1")->fetchColumn();

    // Absensi guru hari ini
    $stmtG = $db->prepare("SELECT COUNT(*) FROM absensi_guru WHERE tanggal = ? AND status = 'Hadir'");
    $stmtG->execute([$today]);
    $guruHadir = $stmtG->fetchColumn();

    // Total kelas yang sudah absen hari ini
    $stmtK = $db->prepare("SELECT COUNT(DISTINCT kelas_id) FROM absensi_siswa WHERE tanggal = ?");
    $stmtK->execute([$today]);
    $kelasAbsen = $stmtK->fetchColumn();

    $totalKelas = $db->query("
        SELECT COUNT(*) FROM kelas k 
        JOIN tahun_ajaran ta ON k.tahun_ajaran_id = ta.id AND ta.aktif = 1
    ")->fetchColumn();

    return [
        'total_siswa'    => $totalSiswa,
        'hadir'          => $absensi['hadir'] ?? 0,
        'izin'           => $absensi['izin'] ?? 0,
        'sakit'          => $absensi['sakit'] ?? 0,
        'alpha'          => $absensi['alpha'] ?? 0,
        'total_tercatat' => $absensi['total_tercatat'] ?? 0,
        'total_guru'     => $totalGuru,
        'guru_hadir'     => $guruHadir,
        'kelas_belum_absen' => (int)$totalKelas - (int)$kelasAbsen,
        'total_kelas'    => $totalKelas,
    ];
}

// ============================================================
// Grafik mingguan (7 hari terakhir)
// ============================================================
function getGrafikMingguan(): array {
    $db = getDB();
    $labels = [];
    $dataHadir = [];
    $dataAlpha = [];

    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-{$i} days"));
        $labels[] = namaHari($date) . ' ' . date('d/m', strtotime($date));

        $stmt = $db->prepare("
            SELECT SUM(status='H') hadir, SUM(status='A') alpha 
            FROM absensi_siswa WHERE tanggal = ?
        ");
        $stmt->execute([$date]);
        $row = $stmt->fetch();
        $dataHadir[] = (int)($row['hadir'] ?? 0);
        $dataAlpha[] = (int)($row['alpha'] ?? 0);
    }

    return ['labels' => $labels, 'hadir' => $dataHadir, 'alpha' => $dataAlpha];
}

// ============================================================
// Ambil siswa dengan alpha melebihi threshold
// ============================================================
function getSiswaAlphaWarning(int $bulan = 0, int $tahun = 0): array {
    $db = getDB();
    $bulan = $bulan ?: (int)date('m');
    $tahun = $tahun ?: (int)date('Y');

    $stmt = $db->prepare("
        SELECT s.id, s.nis, s.nama, k.nama_kelas,
               COUNT(CASE WHEN ab.status = 'A' THEN 1 END) AS jumlah_alpha
        FROM siswa s
        JOIN kelas k ON s.kelas_id = k.id
        LEFT JOIN absensi_siswa ab ON ab.siswa_id = s.id
            AND MONTH(ab.tanggal) = ? AND YEAR(ab.tanggal) = ?
        WHERE s.aktif = 1
        GROUP BY s.id
        HAVING jumlah_alpha >= ?
        ORDER BY jumlah_alpha DESC
    ");
    $stmt->execute([$bulan, $tahun, ALPHA_WARNING_THRESHOLD]);
    return $stmt->fetchAll();
}

// ============================================================
// Pagination Helper
// ============================================================
function paginate(int $totalData, int $perPage, int $currentPage): array {
    $totalPages = (int)ceil($totalData / $perPage);
    $offset     = ($currentPage - 1) * $perPage;
    return [
        'total'        => $totalData,
        'per_page'     => $perPage,
        'current_page' => $currentPage,
        'total_pages'  => $totalPages,
        'offset'       => $offset,
    ];
}

// ============================================================
// Redirect dengan flash message
// ============================================================
function redirectWith(string $url, string $type, string $message): void {
    setFlash($type, $message);
    header('Location: ' . $url);
    exit;
}

// ============================================================
// Input sanitize
// ============================================================
function clean($val): string {
    return trim(strip_tags((string)$val));
}

function cleanInt($val): int {
    return (int)filter_var($val, FILTER_SANITIZE_NUMBER_INT);
}
