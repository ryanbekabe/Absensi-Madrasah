<?php
/**
 * CRUD Siswa - Admin
 * List semua siswa dengan search & pagination
 */
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireRole('admin');

$pageTitle   = 'Data Siswa';
$activeMenu  = 'siswa';
$breadcrumbs = [['label' => 'Data Siswa']];

$db      = getDB();
$perPage = 15;
$page    = max(1, cleanInt($_GET['page'] ?? 1));
$search  = clean($_GET['q'] ?? '');
$kelasId = cleanInt($_GET['kelas_id'] ?? 0);
$aktif   = isset($_GET['aktif']) ? (int)$_GET['aktif'] : 1;

// Filter kelas
$kelasList = $db->query("
    SELECT k.id, k.nama_kelas FROM kelas k 
    JOIN tahun_ajaran ta ON k.tahun_ajaran_id = ta.id AND ta.aktif = 1
    ORDER BY k.tingkat, k.nama_kelas
")->fetchAll();

// Query
$where = "WHERE s.aktif = ?";
$params = [$aktif];
if ($search) { $where .= " AND (s.nama LIKE ? OR s.nis LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }
if ($kelasId) { $where .= " AND s.kelas_id = ?"; $params[] = $kelasId; }

$total = $db->prepare("SELECT COUNT(*) FROM siswa s $where");
$total->execute($params);
$pg    = paginate($total->fetchColumn(), $perPage, $page);

$stmt = $db->prepare("
    SELECT s.*, k.nama_kelas 
    FROM siswa s LEFT JOIN kelas k ON s.kelas_id = k.id
    $where ORDER BY k.tingkat, k.nama_kelas, s.nama
    LIMIT ? OFFSET ?
");
$params[] = $perPage; $params[] = $pg['offset'];
$stmt->execute($params);
$siswaList = $stmt->fetchAll();

include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Data Siswa</h1>
        <p class="page-subtitle">Total: <?= number_format($pg['total']) ?> siswa</p>
    </div>
    <a href="<?= APP_URL ?>/admin/siswa/tambah.php" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> Tambah Siswa
    </a>
</div>

<!-- Filter -->
<div class="card mb-16">
    <div class="card-body" style="padding:14px 20px;">
        <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;">
            <div style="flex:1;min-width:200px;">
                <label class="form-label">Cari</label>
                <input type="text" name="q" class="form-control" placeholder="Nama / NIS..." value="<?= e($search) ?>">
            </div>
            <div style="min-width:160px;">
                <label class="form-label">Kelas</label>
                <select name="kelas_id" class="form-select" data-auto-submit>
                    <option value="">Semua Kelas</option>
                    <?php foreach ($kelasList as $k): ?>
                    <option value="<?= $k['id'] ?>" <?= $kelasId == $k['id'] ? 'selected' : '' ?>><?= e($k['nama_kelas']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="min-width:120px;">
                <label class="form-label">Status</label>
                <select name="aktif" class="form-select" data-auto-submit>
                    <option value="1" <?= $aktif == 1 ? 'selected' : '' ?>>Aktif</option>
                    <option value="0" <?= $aktif == 0 ? 'selected' : '' ?>>Non-aktif</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> Cari</button>
            <a href="<?= APP_URL ?>/admin/siswa/" class="btn btn-secondary"><i class="bi bi-x-circle"></i> Reset</a>
        </form>
    </div>
</div>

<!-- Tabel -->
<div class="card">
    <div class="table-wrapper">
        <table class="table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>NIS</th>
                    <th>Nama Siswa</th>
                    <th>Kelas</th>
                    <th>L/P</th>
                    <th>Wali Murid</th>
                    <th class="text-center">Status</th>
                    <th class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($siswaList as $i => $s): ?>
                <tr>
                    <td class="text-muted"><?= $pg['offset'] + $i + 1 ?></td>
                    <td><code style="font-size:12px;color:var(--primary-light);"><?= e($s['nis']) ?></code></td>
                    <td>
                        <div style="display:flex;align-items:center;gap:8px;">
                            <div class="avatar avatar-sm">
                                <?php if ($s['foto']): ?>
                                    <img src="<?= APP_URL ?>/<?= e($s['foto']) ?>" alt="foto">
                                <?php else: ?>
                                    <?= strtoupper(substr($s['nama'], 0, 2)) ?>
                                <?php endif; ?>
                            </div>
                            <div>
                                <div style="font-weight:600;color:var(--text-primary);"><?= e($s['nama']) ?></div>
                                <div style="font-size:11px;color:var(--text-muted);"><?= $s['tanggal_lahir'] ? formatTanggal($s['tanggal_lahir']) : '-' ?></div>
                            </div>
                        </div>
                    </td>
                    <td><span class="badge bg-primary"><?= e($s['nama_kelas'] ?? '-') ?></span></td>
                    <td>
                        <span class="badge <?= $s['jenis_kelamin'] === 'L' ? 'bg-info' : 'bg-secondary' ?>">
                            <?= $s['jenis_kelamin'] === 'L' ? 'L' : 'P' ?>
                        </span>
                    </td>
                    <td>
                        <div style="font-size:12px;"><?= e($s['nama_wali'] ?? '-') ?></div>
                        <div style="font-size:11px;color:var(--text-muted);"><?= e($s['telepon_wali'] ?? '') ?></div>
                    </td>
                    <td class="text-center">
                        <?= $s['aktif'] ? '<span class="badge bg-success">Aktif</span>' : '<span class="badge bg-secondary">Non-aktif</span>' ?>
                    </td>
                    <td>
                        <div class="actions">
                            <a href="<?= APP_URL ?>/admin/siswa/detail.php?id=<?= $s['id'] ?>" class="btn btn-sm btn-secondary" data-tooltip="Detail">
                                <i class="bi bi-eye"></i>
                            </a>
                            <a href="<?= APP_URL ?>/admin/siswa/edit.php?id=<?= $s['id'] ?>" class="btn btn-sm btn-warning" data-tooltip="Edit">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <a href="<?= APP_URL ?>/admin/siswa/hapus.php?id=<?= $s['id'] ?>&csrf_token=<?= generateCsrf() ?>"
                               class="btn btn-sm btn-danger"
                               data-confirm="Yakin hapus siswa <?= e($s['nama']) ?>?"
                               data-tooltip="Hapus">
                                <i class="bi bi-trash"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($siswaList)): ?>
                <tr>
                    <td colspan="8">
                        <div class="empty-state">
                            <div class="empty-icon"><i class="bi bi-people"></i></div>
                            <p class="empty-title">Tidak Ada Siswa</p>
                            <p class="empty-desc">Belum ada data siswa<?= $search ? ' untuk pencarian "'.e($search).'"' : '' ?>.</p>
                        </div>
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($pg['total_pages'] > 1): ?>
    <div style="padding:14px 20px;border-top:1px solid var(--border-color);display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;">
        <div style="font-size:12px;color:var(--text-muted);">
            Menampilkan <?= $pg['offset'] + 1 ?>–<?= min($pg['offset'] + $perPage, $pg['total']) ?> dari <?= $pg['total'] ?> siswa
        </div>
        <div class="pagination">
            <?php
            $baseUrl = APP_URL . '/admin/siswa/?q=' . urlencode($search) . '&kelas_id=' . $kelasId . '&aktif=' . $aktif . '&page=';
            ?>
            <a class="page-link <?= $pg['current_page'] <= 1 ? 'disabled' : '' ?>" href="<?= $baseUrl . ($pg['current_page'] - 1) ?>">
                <i class="bi bi-chevron-left"></i>
            </a>
            <?php for ($p = max(1, $pg['current_page'] - 2); $p <= min($pg['total_pages'], $pg['current_page'] + 2); $p++): ?>
            <a class="page-link <?= $p == $pg['current_page'] ? 'active' : '' ?>" href="<?= $baseUrl . $p ?>"><?= $p ?></a>
            <?php endfor; ?>
            <a class="page-link <?= $pg['current_page'] >= $pg['total_pages'] ? 'disabled' : '' ?>" href="<?= $baseUrl . ($pg['current_page'] + 1) ?>">
                <i class="bi bi-chevron-right"></i>
            </a>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
