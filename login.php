<?php
/**
 * Login Page - Sistem Absensi Sekolah
 */
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

// Sudah login? redirect dashboard
if (isLoggedIn()) {
    redirectToDashboard();
}

$error   = '';
$inputUN = '';

// Proses LOGIN
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $username = clean($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $inputUN  = $username;

    if (empty($username) || empty($password)) {
        $error = 'Username dan password wajib diisi.';
    } else {
        $db   = getDB();
        $stmt = $db->prepare("SELECT * FROM users WHERE username = ? AND aktif = 1 LIMIT 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Regenerate session ID (security)
            session_regenerate_id(true);

            $_SESSION['user_id']  = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['nama']     = $user['nama'];
            $_SESSION['role']     = $user['role'];
            $_SESSION['foto']     = $user['foto'];

            // Update last login
            $db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?")->execute([$user['id']]);

            redirectToDashboard();
        } else {
            $error = 'Username atau password salah, atau akun tidak aktif.';
        }
    }
}

$csrfToken = generateCsrf();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Login Sistem Absensi Sekolah">
    <title>Login — <?= APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="<?= APP_URL ?>/assets/css/style.css" rel="stylesheet">
    <style>
        .pw-toggle { position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: none; border: none; color: var(--text-muted); cursor: pointer; padding: 0; font-size: 16px; transition: var(--transition); }
        .pw-toggle:hover { color: var(--text-primary); }
        .field-wrap { position: relative; }
        .demo-accounts { margin-top: 24px; border-top: 1px solid var(--border-color); padding-top: 20px; }
        .demo-account { background: rgba(255,255,255,0.04); border: 1px solid var(--border-color); border-radius: 8px; padding: 8px 12px; cursor: pointer; transition: var(--transition); display: flex; align-items: center; gap: 10px; margin-bottom: 6px; }
        .demo-account:hover { background: rgba(99,102,241,0.1); border-color: rgba(99,102,241,0.3); }
        .demo-role { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; padding: 2px 6px; border-radius: 4px; flex-shrink: 0; }
        .role-admin { background: rgba(99,102,241,0.2); color: #a5b4fc; }
        .role-guru  { background: rgba(16,185,129,0.2); color: #6ee7b7; }
        .role-siswa { background: rgba(6,182,212,0.2);   color: #67e8f9; }
    </style>
</head>
<body>
<div class="login-page">

    <!-- Background orbs -->
    <div class="login-bg-orbs">
        <div class="orb orb-1"></div>
        <div class="orb orb-2"></div>
        <div class="orb orb-3"></div>
    </div>

    <!-- Login Card -->
    <div class="login-card">
        <!-- Logo -->
        <div class="login-logo">
            <div class="login-logo-icon">🏫</div>
            <h1 class="login-title"><?= APP_NAME ?></h1>
            <p class="login-subtitle">Sistem Absensi Digital Sekolah</p>
        </div>

        <!-- Error Message -->
        <?php if ($error): ?>
        <div class="alert alert-danger mb-16" style="margin-bottom:20px;">
            <i class="bi bi-exclamation-circle-fill"></i>
            <span><?= e($error) ?></span>
        </div>
        <?php endif; ?>

        <!-- Form -->
        <form method="POST" action="" id="loginForm" novalidate>
            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">

            <div class="form-group">
                <label class="form-label" for="username">
                    <i class="bi bi-person"></i> Username
                </label>
                <div class="field-wrap">
                    <input
                        type="text"
                        class="form-control"
                        id="username"
                        name="username"
                        placeholder="Masukkan username..."
                        value="<?= e($inputUN) ?>"
                        required
                        autocomplete="username"
                        autofocus
                    >
                </div>
            </div>

            <div class="form-group" style="margin-bottom:24px;">
                <label class="form-label" for="password">
                    <i class="bi bi-lock"></i> Password
                </label>
                <div class="field-wrap">
                    <input
                        type="password"
                        class="form-control"
                        id="password"
                        name="password"
                        placeholder="Masukkan password..."
                        required
                        autocomplete="current-password"
                        style="padding-right:40px;"
                    >
                    <button type="button" class="pw-toggle" id="pwToggle" aria-label="Toggle password visibility">
                        <i class="bi bi-eye" id="pwIcon"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn btn-primary w-100 btn-lg" id="loginBtn">
                <i class="bi bi-box-arrow-in-right"></i>
                Masuk
            </button>
        </form>

        <!-- Demo Accounts -->
        <div class="demo-accounts">
            <p style="font-size:11px;color:var(--text-muted);margin-bottom:10px;text-align:center;">
                <i class="bi bi-info-circle"></i> Akun Demo (klik untuk isi otomatis)
            </p>

            <div class="demo-account" onclick="fillLogin('admin','Admin123!')">
                <span class="demo-role role-admin">Admin</span>
                <div>
                    <div style="font-size:12px;font-weight:600;color:var(--text-primary);">admin</div>
                    <div style="font-size:11px;color:var(--text-muted);">Password: Admin123!</div>
                </div>
            </div>
            <div class="demo-account" onclick="fillLogin('guru01','Guru123!')">
                <span class="demo-role role-guru">Guru</span>
                <div>
                    <div style="font-size:12px;font-weight:600;color:var(--text-primary);">guru01</div>
                    <div style="font-size:11px;color:var(--text-muted);">Password: Guru123!</div>
                </div>
            </div>
            <div class="demo-account" onclick="fillLogin('siswa001','Siswa123!')">
                <span class="demo-role role-siswa">Siswa</span>
                <div>
                    <div style="font-size:12px;font-weight:600;color:var(--text-primary);">siswa001</div>
                    <div style="font-size:11px;color:var(--text-muted);">Password: Siswa123!</div>
                </div>
            </div>
        </div>

        <p style="text-align:center;font-size:11px;color:var(--text-muted);margin-top:16px;">
            &copy; <?= date('Y') ?> <?= APP_NAME ?>. All rights reserved.
        </p>
    </div>
</div>

<script>
function fillLogin(u, p) {
    document.getElementById('username').value = u;
    document.getElementById('password').value = p;
    document.getElementById('username').focus();
}

// Password toggle
document.getElementById('pwToggle').addEventListener('click', function() {
    const input = document.getElementById('password');
    const icon  = document.getElementById('pwIcon');
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'bi bi-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'bi bi-eye';
    }
});

// Loading state on submit
document.getElementById('loginForm').addEventListener('submit', function() {
    const btn = document.getElementById('loginBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span>Memproses...';
});
</script>
</body>
</html>
