# Sistem Absensi Sekolah (Absensi-Madrasah)

[![Version](https://img.shields.io/badge/version-1.0.0-blue.svg)](https://github.com/ryanbekabe/Absensi-Madrasah)
[![PHP](https://img.shields.io/badge/PHP-%E2%89%A57.4-777BB4.svg)](https://www.php.net/)
[![MySQL](https://img.shields.io/badge/MySQL-%E2%89%A55.7-4479A1.svg)](https://www.mysql.com/)

Aplikasi manajemen kehadiran siswa dan guru berbasis web yang dirancang untuk efisiensi administrasi sekolah.

## ✨ Fitur Utama

-   **Multi-Role Dashboard**: Akses khusus untuk Admin, Guru, dan Siswa.
-   **Manajemen Data Master**: Kelola data Siswa, Guru, Kelas, Mata Pelajaran, Jadwal, dan Tahun Ajaran.
-   **Absensi Real-time**: Pencatatan kehadiran harian siswa per kelas dan guru.
-   **Laporan & Rekap**: Laporan bulanan dan harian yang siap diekspor ke CSV/Excel.
-   **Sistem Peringatan**: Deteksi otomatis siswa yang melebihi batas ketidakhadiran (Alpha).
-   **Audit Log**: Pelacakan aktivitas perubahan data untuk keamanan.
-   **Premium UI**: Desain responsif, modern, dan mendukung Dark Mode.

## 🚀 Instalasi Cepat

1.  **Clone Repository**:
    ```bash
    git clone https://github.com/ryanbekabe/Absensi-Madrasah.git
    cd Absensi-Madrasah
    ```

2.  **Jalankan Setup Script** (Khusus Linux):
    ```bash
    chmod +x setup.sh
    sudo ./setup.sh
    ```
    *Script ini akan menginstal dependensi, membuat database, dan mengatur permission.*

3.  **Akses Aplikasi**:
    Buka browser dan akses `http://localhost/absensi_sekolah/` (atau sesuai konfigurasi web server Anda).

## 🔑 Kredensial Default

| Role | Username | Password |
|---|---|---|
| **Admin** | `admin` | `Admin123!` |
| **Guru** | `guru01` | `Guru123!` |
| **Siswa** | `siswa001` | `Siswa123!` |

> [!IMPORTANT]
> Segera ganti password default Anda setelah login pertama kali.

## 🛠 Tech Stack

-   **Core**: PHP 7.4+, MySQL/MariaDB.
-   **Frontend**: Bootstrap 5, Vanilla JS, Chart.js.
-   **Security**: PDO Prepared Statements, Bcrypt Hashing, XSS Prevention.

## 📄 Dokumentasi Internal

-   [Product Requirements Document (PRD)](PRD.md)
-   [Development Plan](DevPlan.md)
-   [DevOps & Deployment Guide](DevOps.md)
-   [Development Log](DevLog.md)

## 🤝 Kontribusi

Kontribusi selalu diterima! Silakan fork repository ini dan kirimkan Pull Request.

---
Dikembangkan oleh **Antigravity AI** untuk **ryanbekabe**.
