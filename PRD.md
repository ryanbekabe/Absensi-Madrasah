# PRD — Sistem Absensi Sekolah
**Product Requirements Document**
**Versi:** 1.0.0
**Tanggal:** 2026-04-09
**Status:** Draft

---

## 1. Ringkasan Eksekutif

Sistem Absensi Sekolah adalah aplikasi berbasis web (PHP + MySQL) yang dirancang untuk membantu sekolah mengelola kehadiran siswa dan guru secara digital. Aplikasi ini menggantikan sistem manual berbasis kertas dengan solusi terintegrasi yang real-time, akurat, dan mudah digunakan.

---

## 2. Tujuan Produk

- Digitalisasi proses absensi siswa dan guru.
- Memberikan visibilitas real-time kepada wali kelas, guru, dan administrator terkait kehadiran.
- Menghasilkan laporan absensi periodik (harian, mingguan, bulanan).
- Mempermudah rekapitulasi data kehadiran untuk kebutuhan administrasi sekolah.

---

## 3. Pengguna (Stakeholders)

| Peran | Deskripsi |
|---|---|
| **Admin** | Mengelola seluruh data master: siswa, guru, kelas, mata pelajaran, dan jadwal. |
| **Guru / Wali Kelas** | Mencatat absensi kelas yang diampu, melihat rekap kehadiran siswa. |
| **Siswa** | Melihat rekap kehadiran pribadi. |

---

## 4. Fitur Utama (Feature Requirements)

### 4.1 Manajemen Pengguna (User Management)
- FR-01: Admin dapat menambah, mengubah, menonaktifkan akun guru dan siswa.
- FR-02: Setiap pengguna memiliki role: `admin`, `guru`, `siswa`.
- FR-03: Autentikasi menggunakan username + password (password di-hash dengan bcrypt).
- FR-04: Sesi login dengan manajemen session PHP yang aman.

### 4.2 Manajemen Data Master
- FR-05: Admin dapat mengelola data kelas (nama kelas, tingkat, wali kelas).
- FR-06: Admin dapat mengelola data guru (NIP, nama, mata pelajaran, foto).
- FR-07: Admin dapat mengelola data siswa (NIS, nama, kelas, wali, foto).
- FR-08: Admin dapat mengelola mata pelajaran dan jadwal pelajaran.
- FR-09: Admin dapat mengelola tahun ajaran aktif.

### 4.3 Absensi Siswa
- FR-10: Guru/wali kelas dapat membuka sesi absensi per kelas per hari.
- FR-11: Status absensi: **Hadir (H)**, **Izin (I)**, **Sakit (S)**, **Alpha/Alpa (A)**.
- FR-12: Guru dapat mengisi keterangan/catatan untuk siswa yang tidak hadir.
- FR-13: Absensi yang sudah disimpan dapat diubah oleh guru atau admin (dilengkapi audit log).
- FR-14: Sistem memperingatkan jika absensi kelas belum diisi hari ini.

### 4.4 Absensi Guru
- FR-15: Admin dapat mencatat kehadiran guru setiap hari.
- FR-16: Status: **Hadir**, **Izin**, **Sakit**, **Dinas Luar**, **Cuti**, **Alpha**.
- FR-17: Rekap kehadiran guru tersedia per bulan.

### 4.5 Laporan & Rekap
- FR-18: Rekap absensi siswa per kelas per periode (harian, bulanan, semester).
- FR-19: Rekap absensi guru per periode.
- FR-20: Persentase kehadiran siswa per mata pelajaran.
- FR-21: Laporan siswa yang melebihi batas alpha (configurable threshold, default: 3 alpha).
- FR-22: Ekspor laporan ke PDF.
- FR-23: Ekspor data absensi ke Excel/CSV.

### 4.6 Dashboard
- FR-24: Dashboard admin: total siswa hadir/tidak hari ini, grafik kehadiran mingguan.
- FR-25: Dashboard guru: daftar kelas yang perlu diisi absensi, rekap cepat kelas.
- FR-26: Dashboard siswa: rekap kehadiran semester dengan persentase.

### 4.7 Notifikasi
- FR-27: Tampilkan alert di dashboard jika ada kelas yang belum absen hari ini.
- FR-28: Highlight siswa yang mendekati/melebihi batas alpha.

---

## 5. Persyaratan Non-Fungsional (NFR)

| ID | Kategori | Deskripsi |
|---|---|---|
| NFR-01 | Keamanan | Password di-hash dengan `password_hash()` (bcrypt). |
| NFR-02 | Keamanan | Perlindungan CSRF token pada setiap form POST. |
| NFR-03 | Keamanan | Query menggunakan Prepared Statement (PDO/MySQLi) untuk mencegah SQL Injection. |
| NFR-04 | Keamanan | XSS prevention dengan `htmlspecialchars()` di setiap output. |
| NFR-05 | Performa | Halaman utama harus load < 3 detik pada koneksi LAN. |
| NFR-06 | Compatibilitas | Mendukung browser modern: Chrome, Firefox, Edge. |
| NFR-07 | Usability | Tampilan responsif (mobile-friendly). |
| NFR-08 | Ketersediaan | Berjalan di server lokal (LAN) dengan uptime 99% selama jam kerja. |

---

## 6. Batasan & Asumsi

- Aplikasi dijalankan di jaringan lokal (LAN) sekolah.
- Server menggunakan LAMP Stack (Linux, Apache, MySQL, PHP ≥ 7.4).
- Tidak memiliki fitur notifikasi email/SMS (fase 1).
- Absensi dilakukan secara manual oleh guru (bukan scan QR/fingerprint di fase 1).

---

## 7. Timeline Rilis

| Fase | Fitur | Target |
|---|---|---|
| **Fase 1 (MVP)** | Login, Master Data, Absensi Siswa, Dashboard | Sprint 1-2 |
| **Fase 2** | Absensi Guru, Laporan PDF/Excel, Notifikasi Alert | Sprint 3-4 |
| **Fase 3** | Rekap Semester, Export Massal, QR Code Absensi | Sprint 5+ |

---

## 8. Kriteria Penerimaan (Acceptance Criteria)

- [ ] Semua role (admin, guru, siswa) dapat login dan melihat dashboard masing-masing.
- [ ] Guru dapat mencatat absensi kelas dalam < 2 menit.
- [ ] Admin dapat melihat rekap absensi harian seluruh kelas.
- [ ] Laporan dapat dicetak/diekspor.
- [ ] Data absensi tersimpan dengan benar di database MySQL.
- [ ] Tidak ada SQL Injection atau XSS vulnerability pada input utama.
