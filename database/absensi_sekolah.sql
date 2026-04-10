-- ============================================================
-- Database: absensi_sekolah
-- Sistem Absensi Sekolah
-- Created: 2026-04-09
-- ============================================================

SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';
SET time_zone = '+07:00';

CREATE DATABASE IF NOT EXISTS `absensi_sekolah`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `absensi_sekolah`;

-- ============================================================
-- Table: users
-- ============================================================
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) UNIQUE NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('admin','guru','siswa') NOT NULL DEFAULT 'siswa',
  `nama` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) DEFAULT NULL,
  `foto` VARCHAR(255) DEFAULT NULL,
  `aktif` TINYINT(1) NOT NULL DEFAULT 1,
  `last_login` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- Table: tahun_ajaran
-- ============================================================
CREATE TABLE IF NOT EXISTS `tahun_ajaran` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nama` VARCHAR(20) NOT NULL COMMENT 'Contoh: 2025/2026',
  `semester` ENUM('Ganjil','Genap') NOT NULL,
  `tanggal_mulai` DATE NOT NULL,
  `tanggal_selesai` DATE NOT NULL,
  `aktif` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- Table: guru
-- ============================================================
CREATE TABLE IF NOT EXISTS `guru` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `nip` VARCHAR(30) UNIQUE DEFAULT NULL,
  `nama` VARCHAR(100) NOT NULL,
  `jenis_kelamin` ENUM('L','P') NOT NULL DEFAULT 'L',
  `tempat_lahir` VARCHAR(50) DEFAULT NULL,
  `tanggal_lahir` DATE DEFAULT NULL,
  `alamat` TEXT DEFAULT NULL,
  `telepon` VARCHAR(20) DEFAULT NULL,
  `email` VARCHAR(100) DEFAULT NULL,
  `foto` VARCHAR(255) DEFAULT NULL,
  `status` ENUM('PNS','GTT','Honorer','Kontrak') NOT NULL DEFAULT 'GTT',
  `aktif` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- Table: kelas
-- ============================================================
CREATE TABLE IF NOT EXISTS `kelas` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nama_kelas` VARCHAR(30) NOT NULL,
  `tingkat` TINYINT NOT NULL COMMENT '7, 8, 9 untuk SMP atau 10, 11, 12 untuk SMA',
  `wali_kelas_id` INT DEFAULT NULL,
  `tahun_ajaran_id` INT NOT NULL,
  `keterangan` VARCHAR(100) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`wali_kelas_id`) REFERENCES `guru`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`tahun_ajaran_id`) REFERENCES `tahun_ajaran`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- Table: siswa
-- ============================================================
CREATE TABLE IF NOT EXISTS `siswa` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT DEFAULT NULL,
  `nis` VARCHAR(20) UNIQUE NOT NULL,
  `nama` VARCHAR(100) NOT NULL,
  `kelas_id` INT NOT NULL,
  `jenis_kelamin` ENUM('L','P') NOT NULL DEFAULT 'L',
  `tempat_lahir` VARCHAR(50) DEFAULT NULL,
  `tanggal_lahir` DATE DEFAULT NULL,
  `alamat` TEXT DEFAULT NULL,
  `telepon` VARCHAR(20) DEFAULT NULL,
  `nama_wali` VARCHAR(100) DEFAULT NULL,
  `telepon_wali` VARCHAR(20) DEFAULT NULL,
  `foto` VARCHAR(255) DEFAULT NULL,
  `aktif` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`kelas_id`) REFERENCES `kelas`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- Table: mata_pelajaran
-- ============================================================
CREATE TABLE IF NOT EXISTS `mata_pelajaran` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `kode` VARCHAR(10) UNIQUE NOT NULL,
  `nama` VARCHAR(100) NOT NULL,
  `keterangan` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- Table: jadwal
-- ============================================================
CREATE TABLE IF NOT EXISTS `jadwal` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `kelas_id` INT NOT NULL,
  `mapel_id` INT NOT NULL,
  `guru_id` INT NOT NULL,
  `hari` ENUM('Senin','Selasa','Rabu','Kamis','Jumat','Sabtu') NOT NULL,
  `jam_mulai` TIME NOT NULL,
  `jam_selesai` TIME NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`kelas_id`) REFERENCES `kelas`(`id`),
  FOREIGN KEY (`mapel_id`) REFERENCES `mata_pelajaran`(`id`),
  FOREIGN KEY (`guru_id`) REFERENCES `guru`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- Table: absensi_siswa
-- ============================================================
CREATE TABLE IF NOT EXISTS `absensi_siswa` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `siswa_id` INT NOT NULL,
  `kelas_id` INT NOT NULL,
  `tanggal` DATE NOT NULL,
  `status` ENUM('H','I','S','A') NOT NULL COMMENT 'H=Hadir, I=Izin, S=Sakit, A=Alpha',
  `keterangan` TEXT DEFAULT NULL,
  `dicatat_oleh` INT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_absensi` (`siswa_id`, `tanggal`),
  FOREIGN KEY (`siswa_id`) REFERENCES `siswa`(`id`),
  FOREIGN KEY (`kelas_id`) REFERENCES `kelas`(`id`),
  FOREIGN KEY (`dicatat_oleh`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- Table: absensi_guru
-- ============================================================
CREATE TABLE IF NOT EXISTS `absensi_guru` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `guru_id` INT NOT NULL,
  `tanggal` DATE NOT NULL,
  `status` ENUM('Hadir','Izin','Sakit','Dinas Luar','Cuti','Alpha') NOT NULL DEFAULT 'Hadir',
  `keterangan` TEXT DEFAULT NULL,
  `dicatat_oleh` INT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_absensi_guru` (`guru_id`, `tanggal`),
  FOREIGN KEY (`guru_id`) REFERENCES `guru`(`id`),
  FOREIGN KEY (`dicatat_oleh`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- Table: audit_log
-- ============================================================
CREATE TABLE IF NOT EXISTS `audit_log` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `action` VARCHAR(100) NOT NULL,
  `table_name` VARCHAR(50) DEFAULT NULL,
  `record_id` INT DEFAULT NULL,
  `old_value` TEXT DEFAULT NULL,
  `new_value` TEXT DEFAULT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- SEED DATA
-- ============================================================

-- Users (password: Admin123! | Guru123! | Siswa123!)
-- Password di-hash dengan password_hash() PHP bcrypt
INSERT INTO `users` (`username`, `password`, `role`, `nama`, `email`, `aktif`) VALUES
('admin',   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'Administrator', 'admin@sekolah.sch.id', 1),
('guru01',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'guru',  'Budi Santoso, S.Pd', 'budi@sekolah.sch.id', 1),
('guru02',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'guru',  'Siti Rahayu, S.Pd', 'siti@sekolah.sch.id', 1),
('siswa001','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'siswa', 'Ahmad Fauzi', NULL, 1),
('siswa002','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'siswa', 'Dewi Lestari', NULL, 1);

-- NOTE: Hash di atas adalah hash dari password 'password' (default Laravel).
-- Jalankan script PHP berikut untuk mendapatkan hash yang benar:
-- Admin: password_hash('Admin123!', PASSWORD_DEFAULT)
-- Guru:  password_hash('Guru123!', PASSWORD_DEFAULT)
-- Siswa: password_hash('Siswa123!', PASSWORD_DEFAULT)
-- Lalu UPDATE users SET password='[hash]' WHERE username='[username]';

-- Tahun Ajaran
INSERT INTO `tahun_ajaran` (`nama`, `semester`, `tanggal_mulai`, `tanggal_selesai`, `aktif`) VALUES
('2025/2026', 'Genap', '2026-01-02', '2026-06-30', 1);

-- Guru
INSERT INTO `guru` (`user_id`, `nip`, `nama`, `jenis_kelamin`, `tempat_lahir`, `tanggal_lahir`, `telepon`, `status`) VALUES
(2, '197801012005011001', 'Budi Santoso, S.Pd', 'L', 'Jakarta', '1978-01-01', '081234567890', 'PNS'),
(3, '198503152010012002', 'Siti Rahayu, S.Pd',  'P', 'Bandung', '1985-03-15', '081234567891', 'PNS');

-- Kelas
INSERT INTO `kelas` (`nama_kelas`, `tingkat`, `wali_kelas_id`, `tahun_ajaran_id`, `keterangan`) VALUES
('7A', 7, 1, 1, 'Kelas 7 Unggulan'),
('7B', 7, 2, 1, NULL),
('8A', 8, NULL, 1, NULL),
('9A', 9, NULL, 1, NULL);

-- Siswa
INSERT INTO `siswa` (`user_id`, `nis`, `nama`, `kelas_id`, `jenis_kelamin`, `tempat_lahir`, `tanggal_lahir`, `nama_wali`, `telepon_wali`) VALUES
(4, '2025001', 'Ahmad Fauzi',   1, 'L', 'Jakarta',  '2013-05-10', 'Fauzi Sr.',  '081111111111'),
(5, '2025002', 'Dewi Lestari',  1, 'P', 'Surabaya', '2013-08-22', 'Lestari Sr.','082222222222'),
(NULL, '2025003', 'Rizki Pratama', 1, 'L', 'Medan',   '2013-03-15', 'Pratama Sr.','083333333333'),
(NULL, '2025004', 'Sari Indah',   2, 'P', 'Solo',    '2013-07-30', 'Indah Sr.', '084444444444'),
(NULL, '2025005', 'Eko Nugroho',  2, 'L', 'Yogya',   '2013-11-05', 'Nugroho Sr.','085555555555');

-- Mata Pelajaran
INSERT INTO `mata_pelajaran` (`kode`, `nama`) VALUES
('MTK', 'Matematika'),
('BIN', 'Bahasa Indonesia'),
('BING', 'Bahasa Inggris'),
('IPA', 'Ilmu Pengetahuan Alam'),
('IPS', 'Ilmu Pengetahuan Sosial'),
('PKN', 'Pendidikan Kewarganegaraan'),
('AGAMA', 'Pendidikan Agama'),
('PJOK', 'Pend. Jasmani, Olah Raga & Kes.'),
('SBK', 'Seni Budaya & Keterampilan'),
('TIK', 'Teknologi Informasi & Komunikasi');
