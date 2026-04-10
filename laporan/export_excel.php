<?php
/**
 * Export CSV/Excel - Laporan
 * Simple CSV export (tanpa library eksternal)
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole('admin','guru');
$db   = getDB();
$type = clean($_GET['type'] ?? 'harian');

ob_clean();

if ($type === 'harian') {
    $tanggal = clean($_GET['tanggal'] ?? date('Y-m-d'));
    $kelasId = cleanInt($_GET['kelas_id'] ?? 0);

    $where  = "WHERE ab.tanggal=?";
    $params = [$tanggal];
    if ($kelasId) { $where .= " AND ab.kelas_id=?"; $params[] = $kelasId; }

    $stmt = $db->prepare("
        SELECT s.nis, s.nama, k.nama_kelas, ab.status,
               CASE ab.status WHEN 'H' THEN 'Hadir' WHEN 'I' THEN 'Izin' WHEN 'S' THEN 'Sakit' WHEN 'A' THEN 'Alpha' END AS status_text,
               ab.keterangan, u.nama AS dicatat_oleh, ab.updated_at
        FROM absensi_siswa ab
        JOIN siswa s ON ab.siswa_id=s.id
        JOIN kelas k ON ab.kelas_id=k.id
        LEFT JOIN users u ON ab.dicatat_oleh=u.id
        $where ORDER BY k.tingkat, k.nama_kelas, s.nama
    ");
    $stmt->execute($params); $rows = $stmt->fetchAll();

    $filename = 'absensi_harian_' . $tanggal . '.csv';
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');

    $out = fopen('php://output', 'w');
    fputs($out, "\xEF\xBB\xBF"); // UTF-8 BOM
    fputcsv($out, ['No','NIS','Nama Siswa','Kelas','Status','Keterangan','Dicatat Oleh','Waktu Update'], ';');

    foreach ($rows as $i => $r) {
        fputcsv($out, [
            $i+1, $r['nis'], $r['nama'], $r['nama_kelas'],
            $r['status_text'], $r['keterangan']??'', $r['dicatat_oleh']??'', $r['updated_at']??''
        ], ';');
    }
    fclose($out);

} elseif ($type === 'bulanan') {
    $bulan   = cleanInt($_GET['bulan'] ?? date('m'));
    $tahun   = cleanInt($_GET['tahun'] ?? date('Y'));
    $kelasId = cleanInt($_GET['kelas_id'] ?? 0);

    $kelasWhere = $kelasId ? " AND s.kelas_id=$kelasId" : '';
    $stmt = $db->prepare("
        SELECT s.nis, s.nama, k.nama_kelas,
               SUM(ab.status='H') hadir, SUM(ab.status='I') izin,
               SUM(ab.status='S') sakit, SUM(ab.status='A') alpha, COUNT(ab.id) total
        FROM siswa s JOIN kelas k ON s.kelas_id=k.id
        LEFT JOIN absensi_siswa ab ON ab.siswa_id=s.id AND MONTH(ab.tanggal)=? AND YEAR(ab.tanggal)=?
        WHERE s.aktif=1 $kelasWhere
        GROUP BY s.id ORDER BY k.tingkat, k.nama_kelas, s.nama
    ");
    $p = [$bulan,$tahun]; if ($kelasId) $p[]=$kelasId;
    $stmt->execute($p); $rows = $stmt->fetchAll();

    $filename = 'rekap_bulanan_' . str_pad($bulan,2,'0',STR_PAD_LEFT) . '_' . $tahun . '.csv';
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');

    $out = fopen('php://output', 'w');
    fputs($out, "\xEF\xBB\xBF");
    fputcsv($out, ['No','NIS','Nama Siswa','Kelas','Hadir','Izin','Sakit','Alpha','Total Hari','% Hadir'], ';');

    foreach ($rows as $i => $r) {
        $pct = $r['total'] > 0 ? round($r['hadir']/$r['total']*100,1) : 0;
        fputcsv($out, [
            $i+1, $r['nis'], $r['nama'], $r['nama_kelas'],
            $r['hadir'], $r['izin'], $r['sakit'], $r['alpha'], $r['total'], $pct.'%'
        ], ';');
    }
    fclose($out);

} else {
    header('Location: ' . APP_URL . '/laporan/rekap_harian.php');
}
exit;
