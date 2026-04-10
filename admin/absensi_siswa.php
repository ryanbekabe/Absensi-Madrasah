<?php
/**
 * Input Absensi Siswa - (Admin & Guru)
 * Guru hanya bisa absen kelas yang dia ampu / wali kelas
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole('admin','guru');

$pageTitle   = 'Absensi Siswa';
$activeMenu  = isAdmin() ? 'absensi_siswa' : 'absensi';
$breadcrumbs = [['label' => 'Absensi Siswa']];

$db    = getDB();
$user  = currentUser();
$today = date('Y-m-d');

// Ambil data guru jika role guru
$guruData = null;
if (isGuru()) {
    $gs = $db->prepare("SELECT id FROM guru WHERE user_id=? AND aktif=1");
    $gs->execute([$user['id']]);
    $guruData = $gs->fetch();
}

// Ambil daftar kelas yang boleh diakses
if (isAdmin()) {
    $kelasList = $db->query("
        SELECT k.id, k.nama_kelas, k.tingkat, COALESCE(g.nama,'-') AS wali
        FROM kelas k LEFT JOIN guru g ON k.wali_kelas_id=g.id
        JOIN tahun_ajaran ta ON k.tahun_ajaran_id=ta.id AND ta.aktif=1
        ORDER BY k.tingkat, k.nama_kelas
    ")->fetchAll();
} else {
    $kelasList = $guruData ? getKelasByGuru($guruData['id']) : [];
}

// Kelas yang dipilih
$kelasId = cleanInt($_GET['kelas_id'] ?? ($kelasList[0]['id'] ?? 0));
$tanggal = clean($_GET['tanggal'] ?? $today);

// Validasi tanggal
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal)) $tanggal = $today;

// Proses simpan absensi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $postKelasId = cleanInt($_POST['kelas_id'] ?? 0);
    $postTanggal = clean($_POST['tanggal'] ?? $today);
    $absensiData = $_POST['absensi'] ?? [];
    $keteranganData = $_POST['keterangan'] ?? [];

    if ($postKelasId && $postTanggal && !empty($absensiData)) {
        $saved = 0;
        foreach ($absensiData as $siswaId => $status) {
            $siswaId = (int)$siswaId;
            $status  = in_array($status, ['H','I','S','A']) ? $status : 'H';
            $ket     = clean($keteranganData[$siswaId] ?? '');

            // UPSERT
            $cek = $db->prepare("SELECT id FROM absensi_siswa WHERE siswa_id=? AND tanggal=?");
            $cek->execute([$siswaId, $postTanggal]);
            $existing = $cek->fetch();

            if ($existing) {
                $db->prepare("UPDATE absensi_siswa SET status=?,keterangan=?,kelas_id=?,dicatat_oleh=?,waktu=CURTIME() WHERE id=?")
                   ->execute([$status, $ket?:null, $postKelasId, $user['id'], $existing['id']]);
            } else {
                $db->prepare("INSERT INTO absensi_siswa (siswa_id,kelas_id,tanggal,waktu,status,keterangan,dicatat_oleh) VALUES (?,?,?,CURTIME(),?,?,?)")
                   ->execute([$siswaId, $postKelasId, $postTanggal, $status, $ket?:null, $user['id']]);
            }
            $saved++;
        }
        redirectWith(APP_URL . '/' . (isAdmin() ? 'admin' : 'guru') . '/absensi_siswa.php?kelas_id=' . $postKelasId . '&tanggal=' . $postTanggal,
            'success', "Absensi $saved siswa berhasil disimpan untuk " . formatTanggal($postTanggal) . ".");
    } else {
        setFlash('warning','Tidak ada data absensi yang disimpan.');
    }
}

// Ambil siswa di kelas yang dipilih
$siswaList = [];
$existingAbsensi = [];
$kelasInfo = null;

if ($kelasId) {
    $ks = $db->prepare("SELECT k.*, COALESCE(g.nama,'-') AS wali FROM kelas k LEFT JOIN guru g ON k.wali_kelas_id=g.id WHERE k.id=?");
    $ks->execute([$kelasId]); $kelasInfo = $ks->fetch();

    $ss = $db->prepare("SELECT * FROM siswa WHERE kelas_id=? AND aktif=1 ORDER BY nama");
    $ss->execute([$kelasId]); $siswaList = $ss->fetchAll();

    // Absensi yang sudah ada
    if ($siswaList) {
        $ids = implode(',', array_column($siswaList, 'id'));
        $as  = $db->query("SELECT * FROM absensi_siswa WHERE kelas_id=$kelasId AND tanggal='$tanggal'");
        foreach ($as->fetchAll() as $ab) {
            $existingAbsensi[$ab['siswa_id']] = $ab;
        }
    }
}

// Hitung rekap hari ini
$rekapHariIni = ['H'=>0,'I'=>0,'S'=>0,'A'=>0];
foreach ($existingAbsensi as $ab) { $rekapHariIni[$ab['status']]++; }

include isAdmin() ? __DIR__ . '/../includes/header.php' : __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Input Absensi Siswa</h1>
        <p class="page-subtitle"><?= $kelasInfo ? 'Kelas ' . e($kelasInfo['nama_kelas']) : 'Pilih kelas terlebih dahulu' ?></p>
    </div>
    <?php if (!empty($existingAbsensi) && count($existingAbsensi) === count($siswaList) && count($siswaList) > 0): ?>
    <span class="badge bg-success" style="font-size:13px;padding:8px 14px;"><i class="bi bi-check2-circle"></i> Absensi Lengkap</span>
    <?php endif; ?>
</div>

<!-- Filter -->
<div class="card mb-16">
    <div class="card-body" style="padding:14px 20px;">
        <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;">
            <div style="flex:1;min-width:160px;">
                <label class="form-label">Kelas</label>
                <select name="kelas_id" class="form-select" data-auto-submit>
                    <option value="">-- Pilih Kelas --</option>
                    <?php foreach ($kelasList as $k): ?>
                    <option value="<?= $k['id'] ?>" <?= $kelasId==$k['id']?'selected':'' ?>><?= e($k['nama_kelas']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="min-width:180px;">
                <label class="form-label">Tanggal</label>
                <input type="date" name="tanggal" class="form-control" value="<?= e($tanggal) ?>">
            </div>
            <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> Tampilkan</button>
        </form>
    </div>
</div>

<?php if ($kelasInfo && !empty($siswaList)): ?>

<!-- Rekap Mini -->
<div class="grid grid-4 mb-16">
    <?php
    $statItems = [['Hadir','H','success','check-circle'],['Izin','I','warning','clipboard'],['Sakit','S','info','thermometer-half'],['Alpha','A','danger','x-circle']];
    foreach ($statItems as [$lbl,$k,$color,$icon]):
    ?>
    <div class="stat-card <?= $color ?>">
        <div class="stat-icon <?= $color ?>"><i class="bi bi-<?= $icon ?>"></i></div>
        <div><div class="stat-value"><?= $rekapHariIni[$k] ?></div><div class="stat-label"><?= $lbl ?></div></div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Form Absensi -->
<form method="POST" id="formAbsensi">
    <input type="hidden" name="csrf_token" value="<?= generateCsrf() ?>">
    <input type="hidden" name="kelas_id" value="<?= $kelasId ?>">
    <input type="hidden" name="tanggal" value="<?= e($tanggal) ?>">

    <div class="card mb-16">
        <div class="card-header">
            <div class="card-title">
                <i class="bi bi-calendar-check text-primary"></i>
                Absensi Kelas <?= e($kelasInfo['nama_kelas']) ?> — <?= namaHari($tanggal) ?>, <?= formatTanggal($tanggal) ?>
            </div>
            <div style="display:flex;gap:8px;">
                <!-- Semua Hadir button -->
                <button type="button" class="btn btn-sm btn-success" onclick="setAllStatus('H')">
                    <i class="bi bi-check-all"></i> Semua Hadir
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="absensi-grid">
                <?php foreach ($siswaList as $idx => $siswa):
                    $currentStatus = $existingAbsensi[$siswa['id']]['status'] ?? 'H';
                    $currentKet    = $existingAbsensi[$siswa['id']]['keterangan'] ?? '';
                ?>
                <div class="absensi-student-card" id="card-<?= $siswa['id'] ?>">
                    <div class="avatar">
                        <?php if ($siswa['foto']): ?>
                            <img src="<?= APP_URL ?>/<?= e($siswa['foto']) ?>" alt="foto" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">
                        <?php else: ?>
                            <?= strtoupper(substr($siswa['nama'],0,2)) ?>
                        <?php endif; ?>
                    </div>
                    <div class="absensi-info">
                        <div class="absensi-name"><?= e($siswa['nama']) ?></div>
                        <div class="absensi-nis"><?= e($siswa['nis']) ?></div>
                        <!-- Input keterangan (muncul jika bukan Hadir) -->
                        <input type="text" name="keterangan[<?= $siswa['id'] ?>]"
                               class="form-control ket-input" id="ket-<?= $siswa['id'] ?>"
                               placeholder="Keterangan..."
                               value="<?= e($currentKet) ?>"
                               style="margin-top:6px;font-size:12px;padding:5px 8px;<?= $currentStatus==='H'?'display:none;':'' ?>">
                    </div>
                    <!-- Radio Status -->
                    <div class="absensi-radios">
                        <?php foreach (['H'=>'Hadir','I'=>'Izin','S'=>'Sakit','A'=>'Alpha'] as $val => $lbl): ?>
                        <div style="display:flex;flex-direction:column;align-items:center;gap:2px;">
                            <input type="radio" class="radio-status" id="r-<?= $siswa['id'] ?>-<?= $val ?>"
                                   name="absensi[<?= $siswa['id'] ?>]" value="<?= $val ?>"
                                   <?= $currentStatus===$val?'checked':'' ?>>
                            <label class="radio-label <?= $currentStatus===$val?'selected-'.$val:'' ?>" data-val="<?= $val ?>"
                                   for="r-<?= $siswa['id'] ?>-<?= $val ?>"
                                   data-tooltip="<?= $lbl ?>"
                                   onclick="handleStatus(<?= $siswa['id'] ?>,'<?= $val ?>')"><?= $val ?></label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <div style="padding:16px 20px;border-top:1px solid var(--border-color);display:flex;gap:10px;justify-content:flex-end;">
            <a href="<?= isAdmin() ? APP_URL.'/admin/dashboard.php' : APP_URL.'/guru/dashboard.php' ?>" class="btn btn-secondary">
                <i class="bi bi-x-circle"></i> Batal
            </a>
            <button type="submit" class="btn btn-primary btn-lg">
                <i class="bi bi-save"></i> Simpan Absensi (<?= count($siswaList) ?> Siswa)
            </button>
        </div>
    </div>
</form>

<?php elseif ($kelasId && empty($siswaList)): ?>
<div class="empty-state">
    <div class="empty-icon"><i class="bi bi-people"></i></div>
    <p class="empty-title">Tidak Ada Siswa</p>
    <p class="empty-desc">Kelas ini belum memiliki siswa aktif.</p>
</div>
<?php elseif (empty($kelasList)): ?>
<div class="alert alert-warning">
    <i class="bi bi-exclamation-triangle-fill"></i>
    <span><?= isGuru() ? 'Anda tidak menjadi wali kelas manapun. Hubungi admin.' : 'Belum ada kelas aktif. Tambahkan kelas dan tahun ajaran terlebih dahulu.' ?></span>
</div>
<?php else: ?>
<div class="alert alert-info"><i class="bi bi-info-circle-fill"></i><span>Pilih kelas untuk menampilkan daftar siswa.</span></div>
<?php endif; ?>

<script>
function handleStatus(siswaId, status) {
    const ket = document.getElementById('ket-' + siswaId);
    if (ket) ket.style.display = status !== 'H' ? 'block' : 'none';
}

function setAllStatus(status) {
    document.querySelectorAll('.absensi-student-card').forEach(card => {
        const id    = card.id.replace('card-','');
        const radio = card.querySelector(`input[value="${status}"]`);
        if (radio) {
            radio.checked = true;
            // Update label classes
            card.querySelectorAll('.radio-label').forEach(l => l.className = l.className.replace(/selected-[HISA]/g,'').trim());
            const lbl = card.querySelector(`.radio-label[data-val="${status}"]`);
            if (lbl) lbl.classList.add('selected-'+status);
            // Show/hide keterangan
            handleStatus(id, status);
        }
    });
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
