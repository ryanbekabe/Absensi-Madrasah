#!/bin/bash
# ============================================================
# Setup Script - Sistem Absensi Sekolah
# Jalankan: bash setup.sh
# ============================================================

set -e

APPDIR="/var/www/html/web/absensi_sekolah"
DB_HOST="localhost"
DB_USER="root"
DB_PASS="Root12342022!"
DB_NAME="absensi_sekolah"

echo ""
echo "========================================"
echo "  SETUP - Sistem Absensi Sekolah"
echo "========================================"
echo ""

# 1. Import database
echo "[1/4] Mengimport database..."
mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" < "$APPDIR/database/absensi_sekolah.sql"
echo "      ✓ Database berhasil diimport."

# 2. Update password hashes
echo "[2/4] Mengatur password default..."
php -r "
\$pdo = new PDO('mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4','$DB_USER','$DB_PASS');
\$passwords = ['admin'=>'Admin123!','guru01'=>'Guru123!','guru02'=>'Guru123!','siswa001'=>'Siswa123!','siswa002'=>'Siswa123!'];
foreach (\$passwords as \$u => \$p) {
    \$h = password_hash(\$p, PASSWORD_DEFAULT);
    \$pdo->prepare('UPDATE users SET password=? WHERE username=?')->execute([\$h,\$u]);
}
echo 'Password berhasil diset.';
"
echo "      ✓ Password default berhasil diset."

# 3. Buat direktori uploads
echo "[3/4] Membuat direktori uploads..."
mkdir -p "$APPDIR/assets/img/uploads/siswa"
mkdir -p "$APPDIR/assets/img/uploads/guru"
mkdir -p "$APPDIR/assets/img/uploads/users"
echo "      ✓ Direktori uploads dibuat."

# 4. Set permissions
echo "[4/4] Mengatur permission file..."
chown -R www-data:www-data "$APPDIR/"
find "$APPDIR" -type d -exec chmod 755 {} \;
find "$APPDIR" -type f -exec chmod 644 {} \;
chmod 775 "$APPDIR/assets/img/uploads/" -R
echo "      ✓ Permission berhasil diatur."

echo ""
echo "========================================"
echo "  SETUP SELESAI!"
echo "========================================"
echo ""
echo "  Akses Aplikasi:"
echo "  http://192.168.88.100/web/absensi_sekolah/"
echo ""
echo "  Akun Login:"
echo "  Admin  : admin    / Admin123!"
echo "  Guru   : guru01   / Guru123!"
echo "  Siswa  : siswa001 / Siswa123!"
echo ""
echo "  PERHATIAN: Ganti password setelah login pertama!"
echo "========================================"
echo ""
