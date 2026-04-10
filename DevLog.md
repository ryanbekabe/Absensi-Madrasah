# DevLog — Sistem Absensi Sekolah
**Development Log / Catatan Pengembangan**
**Versi:** 1.0.0
**Tanggal Mulai:** 2026-04-09

---

## Format Entri Log

```
### [YYYY-MM-DD] vX.X.X — Judul singkat
**Developer:** [Nama]
**Status:** ✅ Done | 🔄 In Progress | ❌ Cancelled
- Apa yang dikerjakan
- Keputusan penting yang dibuat
- Bug yang ditemukan/diperbaiki
```

---

## Log Pengembangan

---

### [2026-04-09] v0.1.0 — Initial Setup & Fondasi Proyek
**Developer:** Antigravity AI
**Status:** ✅ Done

**Yang dikerjakan:**
- Membuat struktur direktori proyek lengkap.
- Menyusun dokumen perencanaan: `PRD.md`, `DevPlan.md`, `DevOps.md`, `DevLog.md`.
- Membuat skema database `absensi_sekolah.sql` dengan tabel.
- Implementasi koneksi database via PDO.
- Implementasi sistem autentikasi & dashboard multi-role.
- Implementasi CRUD Master Data & Sistem Absensi Inti.

### [2026-04-10] v1.0.3 — Fitur Absen Keluar (Check-out)
**Developer:** Antigravity AI
**Status:** ✅ Done

**Yang dikerjakan:**
- Menambahkan kolom `jam_pulang` pada tabel `absensi_siswa` dan `absensi_guru`.
- Memperbarui antarmuka Dashboard (Guru & Siswa) agar menampilkan tombol "Check-out Pulang" setelah pengguna melakukan check-in.
- Memisahkan kolom waktu menjadi `Masuk` dan `Pulang` pada laporan Rekap Harian.
- Memperbarui sistem agar otomatis mencatat `jam_masuk` saat check-in dan `jam_pulang` saat check-out.

**Status Proyek:** Stable 1.0.3.

---

### [2026-04-10] v1.0.2 — Pencatatan Waktu Rinci (Jam & Menit)
**Developer:** Antigravity AI
**Status:** ✅ Done

**Yang dikerjakan:**
- Menambahkan kolom `waktu` (TIME) pada tabel `absensi_siswa` dan `absensi_guru`.
- Memperbarui logika simpan absensi untuk mencatat waktu sekarang (`CURTIME()`).
- Menampilkan waktu check-in pada Dashboard Guru, Dashboard Siswa, dan Laporan Rekap Harian.
- Memberikan visibilitas kapan tepatnya seorang siswa atau guru melakukan absensi.

**Status Proyek:** Stable 1.0.2.

---

### [2026-04-10] v1.0.1 — Fitur Check-in Mandiri
**Developer:** Antigravity AI
**Status:** ✅ Done

**Yang dikerjakan:**
- Menambahkan fitur **Check-in Mandiri** untuk Guru di Dashboard Guru.
- Menambahkan fitur **Check-in Mandiri** untuk Siswa di Dashboard Siswa.
- Memperbaiki alert informatif di dashboard jika kehadiran belum tercatat.
- Memungkinkan user untuk mencatat kehadiran "Hadir" secara instan dengan satu klik.

**Status Proyek:** Stable 1.0.1.

---

### [2026-04-10] v1.0.0 — Finalisasi & Fitur Jadwal
**Developer:** Antigravity AI
**Status:** ✅ Done

**Yang dikerjakan:**
- Implementasi fitur Jadwal Pelajaran (Admin, Guru, Siswa).
- Implementasi Export CSV/Excel (menggunakan format CSV UTF-8 BOM).
- Integrasi Ringkasan Jadwal ke Dashboard Guru dan Siswa.
- Perbaikan kompatibilitas PHP 7.4 (menghapus `mixed` type hints).
- Penambahan Audit Log viewer bagi Admin.
- Finalisasi UI: Perbaikan responsivitas dan visualisasi grafik.
- Penambahan setup script (`setup.sh`) untuk otomatisasi.

**Bug diperbaiki:**
- Memperbaiki Error 500 pada login karena `mixed` type hint di PHP 7.4.
- Memperbaiki missing `</div>` pada dashboard siswa.
- Perbaikan variable name `$kelas_id` vs `$kelasId` di link export.

**Status Proyek:** Stable 1.0.0.

---

## Backlog

| ID | Fitur | Prioritas | Status |
|---|---|---|---|
| BL-001 | Export PDF dengan TCPDF | Tinggi | Pending |
| BL-002 | Integrasi PhpSpreadsheet (xlsx) | Sedang | Pending |
| BL-003 | QR Code absensi siswa | Sedang | Belum dimulai |
| BL-004 | Notifikasi WhatsApp API | Tinggi | Direncanakan |

---

## Bug Tracker

| ID | Deskripsi | Status | Tanggal Temukan | Tanggal Fix |
|---|---|---|---|---|
| BUG-001 | PHP 7.4 Incompatibility | ✅ Fixed | 2026-04-10 | 2026-04-10 |
| BUG-002 | UI Layout Glitch Dashboard | ✅ Fixed | 2026-04-10 | 2026-04-10 |

---

## Catatan Deployment

| Tanggal | Versi | Server | Catatan |
|---|---|---|---|
| 2026-04-09 | v0.1.0 | 192.168.88.100 | Initial deploy |
| 2026-04-10 | v1.0.0 | 192.168.88.100 | Stable Release |
