-- ============================================================
-- CBT NUSANTARA - Database Setup
-- Single School Edition v2.0
-- ============================================================

CREATE DATABASE IF NOT EXISTS `cbt_pkbm_albarakah`
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE `cbt_pkbm_albarakah`;

-- ============================================================
-- 1. USERS & AUTHENTICATION (4 Tables)
-- ============================================================

CREATE TABLE `admin` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(50) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `nama` VARCHAR(100) NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `guru` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(50) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `nama` VARCHAR(100) NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `kelas` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `nama_kelas` VARCHAR(50) NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `siswa` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `nama` VARCHAR(100) NOT NULL,
  `kelas_id` INT(11) NOT NULL,
  `username` VARCHAR(50) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `password_plain` VARCHAR(255) DEFAULT NULL,
  `session_id` VARCHAR(100) DEFAULT NULL,
  `is_blocked` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  KEY `kelas_id` (`kelas_id`),
  CONSTRAINT `siswa_ibfk_1` FOREIGN KEY (`kelas_id`)
    REFERENCES `kelas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pengawas` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(50) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `nama` VARCHAR(100) NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 2. QUESTION BANK MANAGEMENT (3 Tables)
-- ============================================================

CREATE TABLE `bank_soal` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `kode_soal` VARCHAR(50) NOT NULL,
  `nama_bank` VARCHAR(100) NOT NULL,
  `created_by_role` VARCHAR(20) DEFAULT NULL,
  `created_by_id` INT(11) DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `kode_soal` (`kode_soal`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `soal` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `bank_soal_id` INT(11) NOT NULL,
  `nomor_soal` INT(11) NOT NULL,
  `tipe_soal` ENUM('PG','ESAI') DEFAULT 'PG',
  `pertanyaan` TEXT NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_bank_nomor` (`bank_soal_id`, `nomor_soal`),
  CONSTRAINT `soal_ibfk_1` FOREIGN KEY (`bank_soal_id`)
    REFERENCES `bank_soal` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `opsi_jawaban` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `soal_id` INT(11) NOT NULL,
  `opsi_key` VARCHAR(5) NOT NULL,
  `opsi_text` TEXT NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_soal` (`soal_id`),
  CONSTRAINT `opsi_jawaban_ibfk_1` FOREIGN KEY (`soal_id`)
    REFERENCES `soal` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 3. EXAM MANAGEMENT (3 Tables)
-- ============================================================

CREATE TABLE `ujian` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `bank_soal_id` INT(11) NOT NULL,
  `nama_ujian` VARCHAR(200) NOT NULL,
  `tanggal_ujian` DATE NOT NULL,
  `jam_mulai` TIME NOT NULL,
  `jam_selesai` TIME NOT NULL,
  `jumlah_soal` INT(11) NOT NULL,
  `alokasi_waktu` INT(11) NOT NULL COMMENT 'dalam menit',
  `acak_soal` TINYINT(1) DEFAULT 0,
  `acak_jawaban` TINYINT(1) DEFAULT 0,
  `status` ENUM('draft','aktif','selesai') DEFAULT 'draft',
  `created_by_role` VARCHAR(20) DEFAULT NULL,
  `created_by_id` INT(11) DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_status_tanggal` (`status`, `tanggal_ujian`),
  CONSTRAINT `ujian_ibfk_1` FOREIGN KEY (`bank_soal_id`)
    REFERENCES `bank_soal` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `ujian_kelas` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `ujian_id` INT(11) NOT NULL,
  `kelas_id` INT(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_ujian_kelas` (`ujian_id`, `kelas_id`),
  CONSTRAINT `ujian_kelas_ibfk_1` FOREIGN KEY (`ujian_id`)
    REFERENCES `ujian` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ujian_kelas_ibfk_2` FOREIGN KEY (`kelas_id`)
    REFERENCES `kelas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `kunci_jawaban` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `ujian_id` INT(11) NOT NULL,
  `nomor_soal` INT(11) NOT NULL,
  `jawaban_benar` VARCHAR(5) NOT NULL,
  `skor` DECIMAL(5,2) DEFAULT 1.00,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_ujian_nomor` (`ujian_id`, `nomor_soal`),
  KEY `idx_ujian` (`ujian_id`),
  CONSTRAINT `kunci_jawaban_ibfk_1` FOREIGN KEY (`ujian_id`)
    REFERENCES `ujian` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 4. EXAM EXECUTION (2 Tables)
-- ============================================================

CREATE TABLE `sesi_ujian` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `ujian_id` INT(11) NOT NULL,
  `siswa_id` INT(11) NOT NULL,
  `token_used` VARCHAR(10) DEFAULT NULL,
  `waktu_mulai` DATETIME DEFAULT NULL,
  `waktu_selesai` DATETIME DEFAULT NULL,
  `sisa_waktu` INT(11) DEFAULT NULL COMMENT 'dalam detik',
  `exit_count` INT(11) DEFAULT 0,
  `penalty_time` INT(11) DEFAULT 0 COMMENT 'dalam detik',
  `status` ENUM('belum_mulai','sedang_ujian','selesai') DEFAULT 'belum_mulai',
  `nilai` DECIMAL(5,2) DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_ujian_siswa` (`ujian_id`, `siswa_id`),
  KEY `idx_status` (`status`),
  KEY `idx_siswa` (`siswa_id`),
  CONSTRAINT `sesi_ujian_ibfk_1` FOREIGN KEY (`ujian_id`)
    REFERENCES `ujian` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sesi_ujian_ibfk_2` FOREIGN KEY (`siswa_id`)
    REFERENCES `siswa` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `jawaban_siswa` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `sesi_ujian_id` INT(11) NOT NULL,
  `nomor_soal` INT(11) NOT NULL,
  `jawaban` VARCHAR(5) DEFAULT NULL,
  `is_correct` TINYINT(1) DEFAULT 0,
  `skor_diperoleh` DECIMAL(5,2) DEFAULT 0.00,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_sesi_nomor` (`sesi_ujian_id`, `nomor_soal`),
  KEY `idx_sesi` (`sesi_ujian_id`),
  CONSTRAINT `jawaban_siswa_ibfk_1` FOREIGN KEY (`sesi_ujian_id`)
    REFERENCES `sesi_ujian` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 5. AUDIT & CONFIG (2 Tables)
-- ============================================================

CREATE TABLE `log_aktivitas` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `sesi_ujian_id` INT(11) NOT NULL,
  `aktivitas` VARCHAR(100) NOT NULL,
  `keterangan` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_sesi` (`sesi_ujian_id`),
  CONSTRAINT `log_aktivitas_ibfk_1` FOREIGN KEY (`sesi_ujian_id`)
    REFERENCES `sesi_ujian` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `settings` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `setting_key` VARCHAR(100) NOT NULL,
  `setting_value` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 6. DEFAULT DATA
-- ============================================================

-- Admin default (password: admin123)
INSERT INTO `admin` (`username`, `password`, `nama`) VALUES
('admin', '$2y$10$LJt5ZJtxUCxDBYRSH9wqRumOE9v5h.zzzwOcCF1ahN6Qtf515l8QO', 'Administrator');

-- Default kelas
INSERT INTO `kelas` (`nama_kelas`) VALUES
('VII A'), ('VII B'), ('VII C'),
('VIII A'), ('VIII B'), ('VIII C'),
('IX A'), ('IX B'), ('IX C');

-- Default settings
INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
('token_enabled', '1'),
('exam_without_token', '0'),
('token_duration', '100'),
('current_token', ''),
('token_generated_at', ''),
('exam_timer', '75'),
('exam_without_schedule', '1'),
('min_submit_time', '10'),
('answer_all_required', '0'),
('exit_penalty', '5'),
('max_exit_count', '3'),
('penalty_lockout_duration', '10'),
('violation_action_type', 'selesai'),
('enable_activity_log', '1'),
('login_without_subject', '0'),
('enable_reset_login', '1'),
('enable_student_logout', '1'),
('show_score_after_exam', '1'),
('school_name', 'NAMA SEKOLAH ANDA'),
('school_description', 'PORTAL UJIAN CBT'),
('school_logo', 'uploads/logo/logo.png'),
('maintenance_mode', '0');
