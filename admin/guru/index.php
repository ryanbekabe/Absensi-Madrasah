<?php
/**
 * CRUD Guru - Admin (List)
 */
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireRole('admin');

$pageTitle   = 'Data Guru';
$activeMenu  = 'guru';
$breadcrumbs = [['label' => 'Data Guru']];

$db      = getDB();
$perPage = 12;
$page    = max(1, cleanInt($_GET['page'] ?? 1));
$search  = clean($_GET['q'] ?? '');
$aktif   = isset($_GET['aktif']) ? (int)$_GET['aktif'] : 1;

$where  = "WHERE g.aktif = ?";
$params = [$aktif];
if ($search) { $where .= " AND (g.nama LIKE ? OR g.nip LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }

$total = $db->prepare("SELECT COUNT(*) FROM guru g $where");
$total->execute($params);
$pg = paginate($total->fetchColumn(), $perPage, $page);

$stmt = $db->prepare("
    SELECT g.*, u.username,
           (SELECT nama_kelas FROM kelas WHERE wali_kelas_id = g.id LIMIT 1) AS wali_kelas
    FROM guru g LEFT JOIN users u ON g.user_id = u.id
    $where ORDER BY g.nama LIMIT ? OFFSET ?
");
$params[] = $perPage; $params[] = $pg['offset'];
$stmt->execute($params);
$guruList = $stmt->fetchAll();

include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Data Guru</h1>
        <p class="page-subtitle">Total: <?= number_format($pg['total']) ?> guru</p>
    </div>
    <a href="<?= APP_URL ?>/admin/guru/tambah.php" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> Tambah Guru
    </a>
</div>

<!-- Filter -->
<div class="card mb-16">
    <div class="card-body" style="padding:14px 20px;">
        <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;">
            <div style="flex:1;min-width:200px;">
                <label class="form-label">Cari</label>
                <input type="text" name="q" class="form-control" placeholder="Nama / NIP..." value="<?= e($search) ?>">
            </div>
            <div style="min-width:120px;">
                <label class="form-label">Status</label>
                <select name="aktif" class="form-select" data-auto-submit>
                    <option value="1" <?= $aktif==1?'selected':'' ?>>Aktif</option>
                    <option value="0" <?= $aktif==0?'selected':'' ?>>Non-aktif</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> Cari</button>
            <a href="<?= APP_URL ?>/admin/guru/" class="btn btn-secondary"><i class="bi bi-x-circle"></i> Reset</a>
        </form>
    </div>
</div>

<!-- Grid Kartu Guru -->
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px;margin-bottom:20px;">
    <?php foreach ($guruList as $g): ?>
    <div class="card" style="padding:20px;display:flex;flex-direction:column;gap:14px;">
        <div style="display:flex;align-items:center;gap:12px;">
            <div class="avatar avatar-lg">
                <?php if ($g['foto']): ?>
                    <img src="<?= APP_URL ?>/<?= e($g['foto']) ?>" alt="foto" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">
                <?php else: ?>
                    <?= strtoupper(substr($g['nama'],0,2)) ?>
                <?php endif; ?>
            </div>
            <div style="flex:1;min-width:0;">
                <div style="font-weight:700;color:var(--text-primary);font-size:14px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= e($g['nama']) ?></div>
                <div style="font-size:11px;color:var(--text-muted);"><?= e($g['nip'] ?? '-') ?></div>
            </div>
        </div>
        <div style="display:flex;gap:6px;flex-wrap:wrap;">
            <span class="badge bg-<?= $g['jenis_kelamin']==='L'?'info':'secondary' ?>"><?= $g['jenis_kelamin']==='L'?'L/Laki-laki':'P/Perempuan' ?></span>
            <span class="badge bg-primary"><?= e($g['status']) ?></span>
            <?php if ($g['wali_kelas']): ?><span class="badge bg-success">Wali: <?= e($g['wali_kelas']) ?></span><?php endif; ?>
        </div>
        <div style="font-size:12px;color:var(--text-muted);">
            <i class="bi bi-telephone"></i> <?= e($g['telepon'] ?? '-') ?>
            <?php if ($g['username']): ?>
            &nbsp;&nbsp;<i class="bi bi-person-circle"></i> <?= e($g['username']) ?>
            <?php else: ?>
            &nbsp;&nbsp;<a href="<?= APP_URL ?>/admin/guru/buat_akun.php?id=<?= $g['id'] ?>" class="text-danger" style="font-weight:700;"><i class="bi bi-person-plus"></i> Buat Akun</a>
            <?php endif; ?>
        </div>
        <div style="display:flex;gap:6px;margin-top:auto;">
            <a href="<?= APP_URL ?>/admin/guru/edit.php?id=<?= $g['id'] ?>" class="btn btn-sm btn-warning" style="flex:1;justify-content:center;">
                <i class="bi bi-pencil"></i> Edit
            </a>
            <a href="<?= APP_URL ?>/admin/guru/hapus.php?id=<?= $g['id'] ?>&csrf_token=<?= generateCsrf() ?>"
               class="btn btn-sm btn-danger"
               data-confirm="Yakin nonaktifkan guru <?= e($g['nama']) ?>?">
                <i class="bi bi-trash"></i>
            </a>
        </div>
    </div>
    <?php endforeach; ?>
    <?php if (empty($guruList)): ?>
    <div style="grid-column:1/-1;">
        <div class="empty-state">
            <div class="empty-icon"><i class="bi bi-person-badge"></i></div>
            <p class="empty-title">Tidak Ada Guru</p>
            <p class="empty-desc">Belum ada data guru<?= $search ? ' untuk pencarian "'.e($search).'"' : '' ?>.</p>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Pagination -->
<?php if ($pg['total_pages'] > 1): ?>
<div style="display:flex;justify-content:center;">
    <div class="pagination">
        <?php $base = APP_URL.'/admin/guru/?q='.urlencode($search).'&aktif='.$aktif.'&page='; ?>
        <a class="page-link <?= $pg['current_page']<=1?'disabled':'' ?>" href="<?= $base.($pg['current_page']-1) ?>"><i class="bi bi-chevron-left"></i></a>
        <?php for ($p=max(1,$pg['current_page']-2);$p<=min($pg['total_pages'],$pg['current_page']+2);$p++): ?>
        <a class="page-link <?= $p==$pg['current_page']?'active':'' ?>" href="<?= $base.$p ?>"><?= $p ?></a>
        <?php endfor; ?>
        <a class="page-link <?= $pg['current_page']>=$pg['total_pages']?'disabled':'' ?>" href="<?= $base.($pg['current_page']+1) ?>"><i class="bi bi-chevron-right"></i></a>
    </div>
</div>
<?php endif; ?>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
