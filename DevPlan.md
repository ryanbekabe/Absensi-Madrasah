# DevPlan — Sistem Absensi Sekolah
**Development Plan**
**Versi:** 1.0.0
**Tanggal:** 2026-04-09

---

## 1. Arsitektur Aplikasi

```
absensi_sekolah/
├── index.php                  # Entry point / redirect ke login atau dashboard
├── login.php                  # Halaman login
├── logout.php                 # Proses logout
├── config/
│   └── db.php                 # Koneksi database (PDO)
├── includes/
│   ├── header.php             # Header HTML + navbar
│   ├── footer.php             # Footer HTML
│   ├── sidebar.php            # Sidebar navigasi
│   ├── auth.php               # Helper: cek session, role guard
│   └── functions.php          # Helper functions umum
├── assets/
│   ├── css/
│   │   └── style.css          # Custom CSS (dark mode, premium UI)
│   ├── js/
│   │   └── app.js             # JavaScript utama
│   └── img/
│       └── logo.png           # Logo sekolah
├── admin/
│   ├── dashboard.php          # Dashboard admin
│   ├── guru/                  # CRUD Guru
│   ├── siswa/                 # CRUD Siswa
│   ├── kelas/                 # CRUD Kelas
│   ├── mapel/                 # CRUD Mata Pelajaran
│   ├── jadwal/                # CRUD Jadwal
│   ├── tahun_ajaran/          # Manajemen Tahun Ajaran
│   └── absensi_guru/          # Absensi Guru
├── guru/
│   ├── dashboard.php          # Dashboard Guru
│   ├── absensi/               # Input & riwayat absensi siswa
│   └── laporan/               # Laporan per kelas
├── siswa/
│   ├── dashboard.php          # Dashboard Siswa
│   └── rekap.php              # Rekap kehadiran pribadi
├── laporan/
│   ├── rekap_harian.php       # Rekap absensi harian
│   ├── rekap_bulanan.php      # Rekap absensi bulanan
│   ├── export_pdf.php         # Export PDF (pakai mPDF/TCPDF)
│   └── export_excel.php       # Export Excel (pakai PhpSpreadsheet)
└── database/
    └── absensi_sekolah.sql    # Skema & data awal database
```

---

## 2. Skema Database

### Tabel: `users`
```sql
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin','guru','siswa') NOT NULL,
    nama VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    foto VARCHAR(255),
    aktif TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### Tabel: `guru`
```sql
CREATE TABLE guru (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    nip VARCHAR(30) UNIQUE,
    nama VARCHAR(100) NOT NULL,
    jenis_kelamin ENUM('L','P') NOT NULL,
    tempat_lahir VARCHAR(50),
    tanggal_lahir DATE,
    alamat TEXT,
    telepon VARCHAR(20),
    email VARCHAR(100),
    foto VARCHAR(255),
    aktif TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

### Tabel: `siswa`
```sql
CREATE TABLE siswa (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    nis VARCHAR(20) UNIQUE NOT NULL,
    nama VARCHAR(100) NOT NULL,
    kelas_id INT NOT NULL,
    jenis_kelamin ENUM('L','P') NOT NULL,
    tempat_lahir VARCHAR(50),
    tanggal_lahir DATE,
    alamat TEXT,
    telepon VARCHAR(20),
    nama_wali VARCHAR(100),
    telepon_wali VARCHAR(20),
    foto VARCHAR(255),
    aktif TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (kelas_id) REFERENCES kelas(id)
);
```

### Tabel: `kelas`
```sql
CREATE TABLE kelas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_kelas VARCHAR(20) NOT NULL,
    tingkat TINYINT NOT NULL,
    wali_kelas_id INT,
    tahun_ajaran_id INT NOT NULL,
    keterangan VARCHAR(100),
    FOREIGN KEY (wali_kelas_id) REFERENCES guru(id) ON DELETE SET NULL,
    FOREIGN KEY (tahun_ajaran_id) REFERENCES tahun_ajaran(id)
);
```

### Tabel: `tahun_ajaran`
```sql
CREATE TABLE tahun_ajaran (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(20) NOT NULL,
    semester ENUM('Ganjil','Genap') NOT NULL,
    tanggal_mulai DATE NOT NULL,
    tanggal_selesai DATE NOT NULL,
    aktif TINYINT(1) DEFAULT 0
);
```

### Tabel: `mata_pelajaran`
```sql
CREATE TABLE mata_pelajaran (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kode VARCHAR(10) UNIQUE NOT NULL,
    nama VARCHAR(100) NOT NULL,
    keterangan TEXT
);
```

### Tabel: `jadwal`
```sql
CREATE TABLE jadwal (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kelas_id INT NOT NULL,
    mapel_id INT NOT NULL,
    guru_id INT NOT NULL,
    hari ENUM('Senin','Selasa','Rabu','Kamis','Jumat','Sabtu') NOT NULL,
    jam_mulai TIME NOT NULL,
    jam_selesai TIME NOT NULL,
    FOREIGN KEY (kelas_id) REFERENCES kelas(id),
    FOREIGN KEY (mapel_id) REFERENCES mata_pelajaran(id),
    FOREIGN KEY (guru_id) REFERENCES guru(id)
);
```

### Tabel: `absensi_siswa`
```sql
CREATE TABLE absensi_siswa (
    id INT AUTO_INCREMENT PRIMARY KEY,
    siswa_id INT NOT NULL,
    kelas_id INT NOT NULL,
    tanggal DATE NOT NULL,
    status ENUM('H','I','S','A') NOT NULL COMMENT 'H=Hadir, I=Izin, S=Sakit, A=Alpha',
    keterangan TEXT,
    dicatat_oleh INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_absensi (siswa_id, tanggal),
    FOREIGN KEY (siswa_id) REFERENCES siswa(id),
    FOREIGN KEY (kelas_id) REFERENCES kelas(id),
    FOREIGN KEY (dicatat_oleh) REFERENCES users(id)
);
```

### Tabel: `absensi_guru`
```sql
CREATE TABLE absensi_guru (
    id INT AUTO_INCREMENT PRIMARY KEY,
    guru_id INT NOT NULL,
    tanggal DATE NOT NULL,
    status ENUM('Hadir','Izin','Sakit','Dinas Luar','Cuti','Alpha') NOT NULL,
    keterangan TEXT,
    dicatat_oleh INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_absensi_guru (guru_id, tanggal),
    FOREIGN KEY (guru_id) REFERENCES guru(id),
    FOREIGN KEY (dicatat_oleh) REFERENCES users(id)
);
```

### Tabel: `audit_log`
```sql
CREATE TABLE audit_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    table_name VARCHAR(50),
    record_id INT,
    old_value TEXT,
    new_value TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);
```

---

## 3. Tech Stack

| Komponen | Teknologi |
|---|---|
| Backend | PHP 7.4+ |
| Database | MySQL 5.7+ / MariaDB |
| Frontend | HTML5, CSS3, Vanilla JS |
| CSS Framework | Bootstrap 5 |
| Icons | Bootstrap Icons |
| Charts | Chart.js |
| PDF Export | TCPDF |
| Excel Export | PhpSpreadsheet |
| Database Driver | PDO (PHP Data Objects) |

---

## 4. Sprint Plan

### Sprint 1 (Fondasi & Master Data)
- [x] Setup project directory & struktur folder
- [x] Buat skema database SQL
- [x] Implementasi koneksi DB (PDO)
- [x] Halaman Login / Logout
- [x] Middleware autentikasi & role guard
- [x] Dashboard Admin (skeleton)
- [x] CRUD Tahun Ajaran
- [x] CRUD Kelas
- [x] CRUD Guru
- [x] CRUD Siswa

### Sprint 2 (Absensi Siswa & Dashboard)
- [x] Form input absensi siswa per kelas
- [x] Tampilan rekap absensi harian
- [x] Dashboard guru (kelas yang perlu diisi)
- [x] Dashboard siswa (rekap pribadi)
- [x] CRUD Mata Pelajaran & Jadwal

### Sprint 3 (Absensi Guru & Laporan)
- [x] Form input absensi guru
- [x] Rekap absensi guru bulanan
- [x] Laporan rekap siswa (harian & bulanan)
- [x] Alert siswa melebihi batas alpha

### Sprint 4 (Export & Polish)
- [ ] Export PDF rekap absensi (Pending)
- [x] Export CSV/Excel (UTF-8 BOM)
- [x] UI polishing & responsivitas
- [x] Testing & bug fixing

---

## 5. Konvensi Kode

- **PHP**: PSR-12, snake_case untuk nama variabel dan fungsi.
- **SQL**: Uppercase untuk keyword SQL, lowercase untuk nama tabel/kolom.
- **JS**: camelCase, tidak menggunakan `var` (gunakan `let`/`const`).
- **CSS**: BEM methodology untuk class naming.
- **Keamanan**: Semua input user wajib di-sanitasi; gunakan prepared statement.

---

## 6. Kredensial Default

| Role | Username | Password default |
|---|---|---|
| Admin | `admin` | `Admin123!` |
| Guru (Contoh) | `guru01` | `Guru123!` |
| Siswa (Contoh) | `siswa001` | `Siswa123!` |

> **PENTING**: Ganti password default setelah instalasi pertama!
