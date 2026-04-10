<?php
/**
 * Absensi Guru - Admin
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole('admin');
$pageTitle = 'Absensi Guru'; $activeMenu = 'absensi_guru';
$breadcrumbs = [['label'=>'Absensi Guru']];
$db   = getDB();
$user = currentUser();
$today = date('Y-m-d');
$tanggal = clean($_GET['tanggal'] ?? $today);
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/',$tanggal)) $tanggal = $today;

// Simpan absensi guru
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $tgl     = clean($_POST['tanggal']??$today);
    $absensi = $_POST['absensi'] ?? [];
    $keterangan = $_POST['keterangan'] ?? [];

    foreach ($absensi as $guruId => $status) {
        $guruId = (int)$guruId;
        $status = in_array($status,['Hadir','Izin','Sakit','Dinas Luar','Cuti','Alpha'])?$status:'Hadir';
        $ket    = clean($keterangan[$guruId]??'');

        $cek = $db->prepare("SELECT id FROM absensi_guru WHERE guru_id=? AND tanggal=?");
        $cek->execute([$guruId,$tgl]);
        $ex  = $cek->fetch();
        if ($ex) {
            $db->prepare("UPDATE absensi_guru SET status=?,keterangan=?,dicatat_oleh=?,waktu=CURTIME() WHERE id=?")
               ->execute([$status,$ket?:null,$user['id'],$ex['id']]);
        } else {
            $db->prepare("INSERT INTO absensi_guru (guru_id,tanggal,waktu,status,keterangan,dicatat_oleh) VALUES (?,?,CURTIME(),?,?,?)")
               ->execute([$guruId,$tgl,$status,$ket?:null,$user['id']]);
        }
    }
    redirectWith(APP_URL.'/admin/absensi_guru.php?tanggal='.$tgl,'success','Absensi guru berhasil disimpan untuk '.formatTanggal($tgl).'.');
}

// Data guru aktif
$guruList = $db->query("SELECT id,nama,nip,jenis_kelamin,foto FROM guru WHERE aktif=1 ORDER BY nama")->fetchAll();

// Absensi yang sudah ada
$existingAbsensi = [];
$as = $db->prepare("SELECT * FROM absensi_guru WHERE tanggal=?"); $as->execute([$tanggal]);
foreach ($as->fetchAll() as $ab) $existingAbsensi[$ab['guru_id']] = $ab;

// Rekap hari ini
$rekap = ['Hadir'=>0,'Izin'=>0,'Sakit'=>0,'Dinas Luar'=>0,'Cuti'=>0,'Alpha'=>0];
foreach ($existingAbsensi as $ab) $rekap[$ab['status']]++;

include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div><h1 class="page-title">Absensi Guru</h1><p class="page-subtitle"><?= namaHari($tanggal) ?>, <?= formatTanggal($tanggal) ?></p></div>
</div>

<!-- Pilih tanggal -->
<div class="card mb-16">
    <div class="card-body" style="padding:14px 20px;">
        <form method="GET" style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap;">
            <div style="min-width:200px;">
                <label class="form-label">Tanggal Absensi</label>
                <input type="date" name="tanggal" class="form-control" value="<?= e($tanggal) ?>">
            </div>
            <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> Tampilkan</button>
        </form>
    </div>
</div>

<!-- Rekap -->
<div class="grid" style="grid-template-columns:repeat(6,1fr);gap:10px;margin-bottom:20px;">
    <?php
    $rColors = ['Hadir'=>'success','Izin'=>'warning','Sakit'=>'info','Dinas Luar'=>'primary','Cuti'=>'secondary','Alpha'=>'danger'];
    foreach ($rekap as $status => $jumlah):
    ?>
    <div class="stat-card <?= $rColors[$status] ?>">
        <div><div class="stat-value"><?= $jumlah ?></div><div class="stat-label"><?= $status ?></div></div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Form Absensi -->
<form method="POST">
    <input type="hidden" name="csrf_token" value="<?= generateCsrf() ?>">
    <input type="hidden" name="tanggal" value="<?= e($tanggal) ?>">
    <div class="card">
        <div class="card-header">
            <div class="card-title"><i class="bi bi-person-check text-primary"></i> Daftar Hadir Guru</div>
            <button type="button" class="btn btn-sm btn-success" onclick="setAllGuru('Hadir')">
                <i class="bi bi-check-all"></i> Semua Hadir
            </button>
        </div>
        <div class="table-wrapper">
            <table class="table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Nama Guru</th>
                        <th>NIP</th>
                        <th>Status</th>
                        <th>Waktu</th>
                        <th>Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($guruList as $i => $g):
                        $curStatus = $existingAbsensi[$g['id']]['status'] ?? 'Hadir';
                        $curKet    = $existingAbsensi[$g['id']]['keterangan'] ?? '';
                    ?>
                    <tr>
                        <td class="text-muted"><?= $i+1 ?></td>
                        <td>
                            <div style="display:flex;align-items:center;gap:8px;">
                                <div class="avatar avatar-sm">
                                    <?php if($g['foto']): ?><img src="<?= APP_URL ?>/<?= e($g['foto']) ?>" alt="foto" style="width:100%;height:100%;object-fit:cover;border-radius:50%;"><?php else: ?><?= strtoupper(substr($g['nama'],0,2)) ?><?php endif; ?>
                                </div>
                                <span style="font-weight:600;color:var(--text-primary);"><?= e($g['nama']) ?></span>
                            </div>
                        </td>
                        <td style="font-size:12px;color:var(--text-muted);"><?= e($g['nip']??'-') ?></td>
                        <td style="min-width:180px;">
                            <select class="form-select" name="absensi[<?= $g['id'] ?>]" id="sel-<?= $g['id'] ?>"
                                    onchange="toggleKet(<?= $g['id'] ?>,this.value)"
                                    style="font-size:12px;padding:5px 30px 5px 10px;">
                                <?php foreach(['Hadir','Izin','Sakit','Dinas Luar','Cuti','Alpha'] as $st): ?>
                                <option value="<?= $st ?>" <?= $curStatus===$st?'selected':'' ?>><?= $st ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td style="font-size:12px;"><?= $curStatus==='Hadir' && !empty($existingAbsensi[$g['id']]['waktu']) ? substr($existingAbsensi[$g['id']]['waktu'],0,5) : '-' ?></td>
                        <td>
                            <input type="text" class="form-control" name="keterangan[<?= $g['id'] ?>]"
                                   id="ket-<?= $g['id'] ?>"
                                   placeholder="Keterangan (opsional)..."
                                   value="<?= e($curKet) ?>"
                                   style="font-size:12px;<?= $curStatus==='Hadir'?'display:none;':'' ?>">
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if(empty($guruList)): ?><tr><td colspan="5" class="text-center text-muted" style="padding:30px;">Belum ada guru aktif.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php if (!empty($guruList)): ?>
        <div style="padding:16px 20px;border-top:1px solid var(--border-color);display:flex;justify-content:flex-end;">
            <button type="submit" class="btn btn-primary btn-lg">
                <i class="bi bi-save"></i> Simpan Absensi Guru
            </button>
        </div>
        <?php endif; ?>
    </div>
</form>

<script>
function toggleKet(id, status) {
    const ket = document.getElementById('ket-' + id);
    if (ket) ket.style.display = status !== 'Hadir' ? 'block' : 'none';
}
function setAllGuru(status) {
    document.querySelectorAll('select[name^="absensi["]').forEach(sel => {
        sel.value = status;
        const id = sel.id.replace('sel-','');
        toggleKet(id, status);
    });
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
