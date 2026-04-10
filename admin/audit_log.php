<?php
/**
 * Audit Log - Admin
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole('admin');
$pageTitle   = 'Audit Log';
$activeMenu  = 'audit_log';
$breadcrumbs = [['label' => 'Audit Log']];
$db = getDB();

$perPage = 20;
$page    = max(1, cleanInt($_GET['page'] ?? 1));
$search  = clean($_GET['q'] ?? '');

$where  = "WHERE 1=1";
$params = [];
if ($search) { $where .= " AND (al.action LIKE ? OR u.nama LIKE ? OR al.table_name LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%"; }

$total = $db->prepare("SELECT COUNT(*) FROM audit_log al LEFT JOIN users u ON al.user_id=u.id $where");
$total->execute($params); $pg = paginate($total->fetchColumn(), $perPage, $page);

$stmt = $db->prepare("
    SELECT al.*, u.nama AS user_nama, u.role AS user_role
    FROM audit_log al LEFT JOIN users u ON al.user_id=u.id
    $where ORDER BY al.created_at DESC LIMIT ? OFFSET ?
");
$p = $params; $p[] = $perPage; $p[] = $pg['offset'];
$stmt->execute($p); $logs = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
    <div><h1 class="page-title">Audit Log</h1><p class="page-subtitle">Riwayat aktivitas sistem</p></div>
</div>

<div class="card mb-16">
    <div class="card-body" style="padding:14px 20px;">
        <form method="GET" style="display:flex;gap:10px;align-items:flex-end;">
            <div style="flex:1;"><label class="form-label">Cari</label><input type="text" name="q" class="form-control" placeholder="Aksi / nama user / tabel..." value="<?= e($search) ?>"></div>
            <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> Cari</button>
            <a href="<?= APP_URL ?>/admin/audit_log.php" class="btn btn-secondary"><i class="bi bi-x-circle"></i> Reset</a>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <div class="card-title"><i class="bi bi-journal-text text-primary"></i> Log Aktivitas</div>
        <span style="font-size:12px;color:var(--text-muted);"><?= $pg['total'] ?> catatan</span>
    </div>
    <div class="table-wrapper">
        <table class="table">
            <thead><tr><th>Waktu</th><th>Pengguna</th><th>Aksi</th><th>Tabel</th><th>ID Data</th><th>IP Address</th></tr></thead>
            <tbody>
                <?php foreach ($logs as $log):
                    $actionColor = ['INSERT'=>'success','UPDATE'=>'warning','DELETE'=>'danger','LOGIN'=>'info'][$log['action']] ?? 'secondary';
                ?>
                <tr>
                    <td style="font-size:11px;white-space:nowrap;color:var(--text-muted);"><?= date('d/m/Y H:i:s', strtotime($log['created_at'])) ?></td>
                    <td>
                        <div style="font-size:12px;font-weight:600;color:var(--text-primary);"><?= e($log['user_nama']??'System') ?></div>
                        <div style="font-size:10px;color:var(--text-muted);"><?= ucfirst(e($log['user_role']??'')) ?></div>
                    </td>
                    <td><span class="badge bg-<?= $actionColor ?>"><?= e($log['action']) ?></span></td>
                    <td style="font-size:12px;color:var(--text-muted);"><?= $log['table_name'] ? e($log['table_name']) : '-' ?></td>
                    <td style="font-size:12px;color:var(--text-muted);"><?= $log['record_id'] ?? '-' ?></td>
                    <td style="font-size:11px;color:var(--text-muted);"><?= e($log['ip_address']??'-') ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($logs)): ?>
                <tr><td colspan="6"><div class="empty-state" style="padding:30px;"><div class="empty-icon"><i class="bi bi-journal-x"></i></div><p class="empty-title">Tidak Ada Log</p></div></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if ($pg['total_pages'] > 1): ?>
    <div style="padding:14px 20px;border-top:1px solid var(--border-color);display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;">
        <div style="font-size:12px;color:var(--text-muted);">Menampilkan <?= $pg['offset']+1 ?>–<?= min($pg['offset']+$perPage,$pg['total']) ?> dari <?= $pg['total'] ?></div>
        <div class="pagination">
            <?php $base = APP_URL.'/admin/audit_log.php?q='.urlencode($search).'&page='; ?>
            <a class="page-link <?= $pg['current_page']<=1?'disabled':'' ?>" href="<?= $base.($pg['current_page']-1) ?>"><i class="bi bi-chevron-left"></i></a>
            <?php for($p=max(1,$pg['current_page']-2);$p<=min($pg['total_pages'],$pg['current_page']+2);$p++): ?>
            <a class="page-link <?= $p==$pg['current_page']?'active':'' ?>" href="<?= $base.$p ?>"><?= $p ?></a>
            <?php endfor; ?>
            <a class="page-link <?= $pg['current_page']>=$pg['total_pages']?'disabled':'' ?>" href="<?= $base.($pg['current_page']+1) ?>"><i class="bi bi-chevron-right"></i></a>
        </div>
    </div>
    <?php endif; ?>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
