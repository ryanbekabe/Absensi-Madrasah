<?php
/**
 * Header & Topbar - Sistem Absensi Sekolah
 * @param string $pageTitle   - Judul halaman
 * @param string $activeMenu  - Key menu aktif
 * @param array  $breadcrumbs - [['label'=>'...','url'=>'...'], ...]
 */

if (!isset($pageTitle))   $pageTitle   = APP_NAME;
if (!isset($activeMenu))  $activeMenu  = '';
if (!isset($breadcrumbs)) $breadcrumbs = [];

$user    = currentUser();
$csrfToken = generateCsrf();

// Flash messages
$flashSuccess = getFlash('success');
$flashDanger  = getFlash('danger');
$flashWarning = getFlash('warning');
$flashInfo    = getFlash('info');

// Initial huruf untuk avatar
$initials = '';
if ($user) {
    $words = explode(' ', $user['nama']);
    $initials = strtoupper(substr($words[0], 0, 1));
    if (isset($words[1])) $initials .= strtoupper(substr($words[1], 0, 1));
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Sistem Absensi Sekolah - Kelola kehadiran siswa dan guru secara digital">
    <title><?= e($pageTitle) ?> — <?= APP_NAME ?></title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="<?= APP_URL ?>/assets/css/style.css" rel="stylesheet">

    <style>
        /* Override Bootstrap to match dark theme */
        .modal-content { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius-lg); }
        .modal-header { border-bottom-color: var(--border-color); }
        .modal-footer { border-top-color: var(--border-color); }
        .modal-title  { color: var(--text-primary); font-size: 15px; font-weight: 600; }
        .dropdown-menu { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 6px; }
        .dropdown-item { color: var(--text-secondary); font-size: 13px; border-radius: var(--radius-sm); padding: 7px 12px; }
        .dropdown-item:hover { background: rgba(255,255,255,0.06); color: var(--text-primary); }
        .dropdown-divider { border-color: var(--border-color); }
    </style>
</head>
<body>
<div class="app-wrapper">

<!-- ============================================================
     SIDEBAR
     ============================================================ -->
<?php include __DIR__ . '/sidebar.php'; ?>

<!-- ============================================================
     MAIN CONTENT
     ============================================================ -->
<div class="main-content">

    <!-- TOPBAR -->
    <header class="topbar">
        <div class="topbar-left">
            <button class="btn-sidebar-toggle" id="sidebarToggle" aria-label="Toggle Sidebar">
                <i class="bi bi-list"></i>
            </button>

            <!-- Breadcrumb -->
            <nav class="breadcrumb-custom" aria-label="breadcrumb">
                <a href="<?= APP_URL ?>"><i class="bi bi-house"></i></a>
                <?php foreach ($breadcrumbs as $crumb): ?>
                    <span class="sep"><i class="bi bi-chevron-right fs-12"></i></span>
                    <?php if (!empty($crumb['url'])): ?>
                        <a href="<?= e($crumb['url']) ?>"><?= e($crumb['label']) ?></a>
                    <?php else: ?>
                        <span><?= e($crumb['label']) ?></span>
                    <?php endif; ?>
                <?php endforeach; ?>
            </nav>
        </div>

        <div class="topbar-right">
            <span class="topbar-time" id="liveClock"></span>

            <!-- User Dropdown -->
            <?php if ($user): ?>
            <div class="dropdown">
                <button class="topbar-btn" style="width:auto;padding:0 10px;gap:8px;" data-bs-toggle="dropdown" id="userDropdown" aria-expanded="false">
                    <div class="avatar avatar-sm">
                        <?php if ($user['foto']): ?>
                            <img src="<?= APP_URL ?>/<?= e($user['foto']) ?>" alt="foto">
                        <?php else: ?>
                            <?= e($initials) ?>
                        <?php endif; ?>
                    </div>
                    <span style="font-size:12px;font-weight:600;color:var(--text-primary);">
                        <?= e(explode(' ', $user['nama'])[0]) ?>
                    </span>
                    <i class="bi bi-chevron-down" style="font-size:10px;color:var(--text-muted);"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                    <li>
                        <div style="padding:8px 12px;border-bottom:1px solid var(--border-color);margin-bottom:4px;">
                            <div style="font-size:13px;font-weight:600;color:var(--text-primary);"><?= e($user['nama']) ?></div>
                            <div style="font-size:11px;color:var(--primary-light);text-transform:capitalize;"><?= e($user['role']) ?></div>
                        </div>
                    </li>
                    <li><a class="dropdown-item" href="<?= APP_URL ?>/profile.php"><i class="bi bi-person me-2"></i>Profil Saya</a></li>
                    <li><a class="dropdown-item" href="<?= APP_URL ?>/profile.php?tab=password"><i class="bi bi-shield-lock me-2"></i>Ganti Password</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <a class="dropdown-item" href="<?= APP_URL ?>/logout.php" 
                           onclick="return confirm('Yakin ingin logout?')"
                           style="color:#f87171;">
                            <i class="bi bi-box-arrow-right me-2"></i>Logout
                        </a>
                    </li>
                </ul>
            </div>
            <?php endif; ?>
        </div>
    </header>

    <!-- PAGE CONTENT wrapper - flash messages injected here -->
    <main class="page-content animate-fade-in">

        <!-- Flash Messages -->
        <?php if ($flashSuccess): ?>
        <div class="alert alert-success mb-16" id="flash-alert">
            <i class="bi bi-check-circle-fill"></i>
            <span><?= e($flashSuccess) ?></span>
            <button class="alert-close" onclick="this.parentElement.remove()">&times;</button>
        </div>
        <?php endif; ?>
        <?php if ($flashDanger): ?>
        <div class="alert alert-danger mb-16" id="flash-alert">
            <i class="bi bi-exclamation-circle-fill"></i>
            <span><?= e($flashDanger) ?></span>
            <button class="alert-close" onclick="this.parentElement.remove()">&times;</button>
        </div>
        <?php endif; ?>
        <?php if ($flashWarning): ?>
        <div class="alert alert-warning mb-16" id="flash-alert">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <span><?= e($flashWarning) ?></span>
            <button class="alert-close" onclick="this.parentElement.remove()">&times;</button>
        </div>
        <?php endif; ?>
        <?php if ($flashInfo): ?>
        <div class="alert alert-info mb-16" id="flash-alert">
            <i class="bi bi-info-circle-fill"></i>
            <span><?= e($flashInfo) ?></span>
            <button class="alert-close" onclick="this.parentElement.remove()">&times;</button>
        </div>
        <?php endif; ?>
