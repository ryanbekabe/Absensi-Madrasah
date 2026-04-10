# DevOps — Sistem Absensi Sekolah
**Panduan Operasional & Deployment**
**Versi:** 1.0.0
**Tanggal:** 2026-04-09

---

## 1. Lingkungan Server

| Komponen | Spesifikasi |
|---|---|
| OS | Linux (Debian/Ubuntu/Linux Mint) |
| Web Server | Apache2 |
| PHP | 7.4+ |
| Database | MySQL 5.7+ / MariaDB 10.3+ |
| URL Akses | URL LOKAL: http://192.168.88.100/web/absensi_sekolah/
URL PUBLIK: https://rsipalangkaraya.online/web/absensi_sekolah/ |
| Path Dokumen | `/var/www/html/web/absensi_sekolah/` |

---

## 2. Persyaratan Server

### PHP Extensions yang Dibutuhkan
```bash
sudo apt install php php-mysql php-mbstring php-gd php-zip php-xml php-curl
```

### Verifikasi versi PHP
```bash
php -v
```

### Verifikasi ekstensi aktif
```bash
php -m | grep -E "pdo|mysql|mbstring|gd|zip|xml"
```

---

## 3. Konfigurasi Database

### Kredensial Database
```
Host     : localhost
User     : root
Password : Root12342022!
Database : absensi_sekolah
Port     : 3306
```

### Membuat Database
```sql
CREATE DATABASE IF NOT EXISTS absensi_sekolah 
    CHARACTER SET utf8mb4 
    COLLATE utf8mb4_unicode_ci;
```

### Import Skema Database
```bash
mysql -u root -p'Root12342022!' absensi_sekolah < /var/www/html/web/absensi_sekolah/database/absensi_sekolah.sql
```

---

## 4. Konfigurasi Apache

### Pastikan mod_rewrite aktif
```bash
sudo a2enmod rewrite
sudo systemctl restart apache2
```

### Pastikan direktori ada di AllowOverride
Edit `/etc/apache2/apache2.conf` atau VirtualHost:
```apache
<Directory /var/www/html>
    AllowOverride All
    Options Indexes FollowSymLinks
    Require all granted
</Directory>
```

### Restart Apache setelah perubahan konfigurasi
```bash
sudo systemctl restart apache2
sudo systemctl status apache2
```

---

## 5. Permission File

```bash
# Set ownership ke www-data (user Apache)
sudo chown -R www-data:www-data /var/www/html/web/absensi_sekolah/

# Set permission direktori
sudo find /var/www/html/web/absensi_sekolah/ -type d -exec chmod 755 {} \;

# Set permission file
sudo find /var/www/html/web/absensi_sekolah/ -type f -exec chmod 644 {} \;

# Pastikan folder upload dapat ditulis
sudo chmod 775 /var/www/html/web/absensi_sekolah/assets/img/uploads/
```

---

## 6. File Konfigurasi Aplikasi

Edit file `/var/www/html/web/absensi_sekolah/config/db.php` sesuai kebutuhan:
```php
<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'absensi_sekolah');
define('DB_USER', 'root');
define('DB_PASS', 'Root12342022!');
define('DB_CHARSET', 'utf8mb4');
```

---

## 7. Backup & Restore

### Backup Database (jalankan setiap hari via cron)
```bash
# Tambahkan ke crontab
crontab -e

# Backup setiap hari jam 23:00
0 23 * * * mysqldump -u root -p'Root12342022!' absensi_sekolah > /backup/absensi_sekolah_$(date +\%Y\%m\%d).sql

# Hapus backup lebih dari 30 hari
0 1 * * * find /backup/ -name "absensi_sekolah_*.sql" -mtime +30 -delete
```

### Restore Database
```bash
mysql -u root -p'Root12342022!' absensi_sekolah < /backup/absensi_sekolah_YYYYMMDD.sql
```

---

## 8. Monitoring & Log

### Log Apache
```bash
# Access log
tail -f /var/log/apache2/access.log

# Error log
tail -f /var/log/apache2/error.log
```

### Log PHP
Tambahkan ke `php.ini` untuk logging error:
```
log_errors = On
error_log = /var/log/php_errors.log
display_errors = Off  # WAJIB Off di production
```

### Cek error PHP terbaru
```bash
tail -n 50 /var/log/php_errors.log
```

---

## 9. Update Aplikasi

```bash
# 1. Backup database sebelum update
mysqldump -u root -p'Root12342022!' absensi_sekolah > /backup/pre_update_$(date +%Y%m%d).sql

# 2. Salin file baru ke direktori
# (gunakan metode yang sesuai: git pull, scp, dll)

# 3. Jalankan migrasi SQL jika ada (diletakkan di database/migrations/)
mysql -u root -p'Root12342022!' absensi_sekolah < database/migrations/vX.X.X.sql

# 4. Reset permission jika diperlukan
sudo chown -R www-data:www-data /var/www/html/web/absensi_sekolah/

# 5. Restart/reload Apache
sudo systemctl reload apache2
```

---

## 10. Otomatisasi Setup

Tersedia script `setup.sh` untuk mempercepat konfigurasi awal (database, permission, dependensi).

```bash
chmod +x setup.sh
sudo ./setup.sh
```

---

## 11. Troubleshooting Umum

| Masalah | Kemungkinan Penyebab | Solusi |
|---|---|---|
| Halaman kosong / error 500 | Error PHP tersembunyi | Cek `/var/log/apache2/error.log` |
| Tidak bisa login ke DB | Kredensial salah | Verifikasi `config/db.php` |
| Upload foto gagal | Permission folder uploads | `chmod 775 assets/img/uploads/` |
| Halaman tidak ditemukan (404) | mod_rewrite tidak aktif | `sudo a2enmod rewrite && sudo systemctl restart apache2` |
| Karakter rusak (?) | Charset tidak cocok | Pastikan `utf8mb4` di DB dan koneksi PDO |
| Session expired terlalu cepat | `session.gc_maxlifetime` kecil | Edit `php.ini` atau set di `config/db.php` |

---

## 12. Keamanan Produksi

- [ ] Ubah password default semua akun setelah instalasi.
- [ ] Pastikan `display_errors = Off` di `php.ini`.
- [ ] Batasi akses ke folder `config/` via `.htaccess`.
- [ ] Gunakan HTTPS jika memungkinkan (self-signed cert untuk LAN).
- [ ] Aktifkan firewall (ufw) dan batasi port database (3306) hanya untuk localhost.
- [ ] Backup database secara rutin.
- [ ] Review audit_log secara berkala.
