-- ============================================================================
-- DynaQuiz - Database Schema (MySQL 8.0+ / MariaDB 10.6+)
-- Kelompok 14 - Tugas Besar Praktikum Pemrograman Web 2026
-- PIC: Zalfa (Sesi 1 - schema.sql)
-- ============================================================================

SET NAMES utf8mb4;
SET time_zone = '+07:00';
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------------------------------------------------------
-- Hapus tabel jika sudah ada (urutan dari tabel anak ke induk)
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `log_login`;
DROP TABLE IF EXISTS `log_status_kuis`;
DROP TABLE IF EXISTS `jawaban_percobaan`;
DROP TABLE IF EXISTS `percobaan_kuis`;
DROP TABLE IF EXISTS `opsi_jawaban`;
DROP TABLE IF EXISTS `soal`;
DROP TABLE IF EXISTS `kuis`;
DROP TABLE IF EXISTS `tingkat`;
DROP TABLE IF EXISTS `topik`;
DROP TABLE IF EXISTS `user`;

-- ----------------------------------------------------------------------------
-- 1. Tabel `user`
-- Menyimpan akun Admin, Pengajar, dan Pelajar (Guest disimpan di sesi PHP).
-- ----------------------------------------------------------------------------
CREATE TABLE `user` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `nama` VARCHAR(100) NOT NULL,
    `email` VARCHAR(150) NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `peran` ENUM('pelajar', 'pengajar', 'admin') NOT NULL DEFAULT 'pelajar',
    `dibuat_pada` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `diperbarui_pada` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_user_email` (`email`),
    KEY `idx_user_peran` (`peran`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 2. Tabel `topik`
-- Data master topik / mata pelajaran untuk pengelompokan dan filter katalog kuis.
-- ----------------------------------------------------------------------------
CREATE TABLE `topik` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `nama` VARCHAR(100) NOT NULL,
    `deskripsi` VARCHAR(255) DEFAULT NULL,
    `dibuat_pada` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `diperbarui_pada` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_topik_nama` (`nama`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 3. Tabel `tingkat`
-- Data master jenjang/tingkat pendidikan (mis. SD, SMP, SMA, Umum).
-- Memiliki kolom `urutan` untuk pengurutan tampilan (ADM-06).
-- ----------------------------------------------------------------------------
CREATE TABLE `tingkat` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `nama` VARCHAR(50) NOT NULL,
    `urutan` SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    `dibuat_pada` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `diperbarui_pada` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_tingkat_nama` (`nama`),
    KEY `idx_tingkat_urutan` (`urutan`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 4. Tabel `kuis`
-- Menyimpan informasi kuis, status kurasi, jenis akses (publik/privat), dan kode.
-- Topik dan tingkat yang dipakai kuis tidak dapat dihapus (ON DELETE RESTRICT, BR-18).
-- ----------------------------------------------------------------------------
CREATE TABLE `kuis` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `pengajar_id` INT UNSIGNED NOT NULL,
    `topik_id` INT UNSIGNED NOT NULL,
    `tingkat_id` INT UNSIGNED NOT NULL,
    `judul` VARCHAR(150) NOT NULL,
    `deskripsi` TEXT DEFAULT NULL,
    `durasi_menit` SMALLINT UNSIGNED NOT NULL DEFAULT 15,
    `akses` ENUM('publik', 'privat') NOT NULL DEFAULT 'publik',
    `kode` VARCHAR(8) DEFAULT NULL,
    `status` ENUM('draft', 'menunggu', 'terbit', 'ditolak', 'ditarik') NOT NULL DEFAULT 'draft',
    `diterbitkan_pada` DATETIME DEFAULT NULL,
    `dibuat_pada` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `diperbarui_pada` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_kuis_kode` (`kode`),
    KEY `idx_kuis_katalog` (`status`, `akses`, `topik_id`, `tingkat_id`, `diterbitkan_pada`),
    KEY `idx_kuis_pengajar_status` (`pengajar_id`, `status`),
    KEY `idx_kuis_topik` (`topik_id`),
    KEY `idx_kuis_tingkat` (`tingkat_id`),
    CONSTRAINT `fk_kuis_pengajar` FOREIGN KEY (`pengajar_id`) REFERENCES `user` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_kuis_topik` FOREIGN KEY (`topik_id`) REFERENCES `topik` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_kuis_tingkat` FOREIGN KEY (`tingkat_id`) REFERENCES `tingkat` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 5. Tabel `soal`
-- Menyimpan daftar pertanyaan pilihan ganda per kuis beserta pembahasan dan poin.
-- Indeks UNIQUE (`kuis_id`, `urutan`) sesuai PRD bagian 10.2.
-- ----------------------------------------------------------------------------
CREATE TABLE `soal` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `kuis_id` INT UNSIGNED NOT NULL,
    `urutan` SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    `pertanyaan` TEXT NOT NULL,
    `pembahasan` TEXT DEFAULT NULL,
    `poin` SMALLINT UNSIGNED NOT NULL DEFAULT 10,
    `dibuat_pada` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `diperbarui_pada` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_soal_kuis_urutan` (`kuis_id`, `urutan`),
    CONSTRAINT `fk_soal_kuis` FOREIGN KEY (`kuis_id`) REFERENCES `kuis` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 6. Tabel `opsi_jawaban`
-- Menyimpan pilihan jawaban (2 s.d. 5 opsi, label A s.d. E) untuk setiap soal.
-- Kolom `benar` bernilai 1 untuk kunci jawaban (tepat 1 per soal) dan 0 untuk lainnya.
-- ----------------------------------------------------------------------------
CREATE TABLE `opsi_jawaban` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `soal_id` INT UNSIGNED NOT NULL,
    `label` CHAR(1) NOT NULL,
    `teks` VARCHAR(255) NOT NULL,
    `benar` TINYINT(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_opsi_soal_label` (`soal_id`, `label`),
    KEY `idx_opsi_soal_benar` (`soal_id`, `benar`),
    CONSTRAINT `fk_opsi_soal` FOREIGN KEY (`soal_id`) REFERENCES `soal` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 7. Tabel `percobaan_kuis`
-- Mencatat sesi pengerjaan kuis oleh Pelajar terdaftar.
-- Menyimpan snapshot `durasi_menit` dan `skor_maksimal` saat percobaan dimulai (KRJ-01, BR-07).
-- Kuis yang sudah memiliki percobaan tidak dapat dihapus (ON DELETE RESTRICT, BR-06).
-- ----------------------------------------------------------------------------
CREATE TABLE `percobaan_kuis` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `kuis_id` INT UNSIGNED NOT NULL,
    `pelajar_id` INT UNSIGNED NOT NULL,
    `waktu_mulai` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `waktu_selesai` DATETIME DEFAULT NULL,
    `durasi_menit` SMALLINT UNSIGNED NOT NULL,
    `skor` INT UNSIGNED NOT NULL DEFAULT 0,
    `skor_maksimal` INT UNSIGNED NOT NULL DEFAULT 0,
    `jumlah_benar` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `jumlah_salah` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `status` ENUM('berjalan', 'selesai', 'waktu_habis') NOT NULL DEFAULT 'berjalan',
    `catatan` VARCHAR(255) DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_percobaan_pelajar_status` (`pelajar_id`, `status`, `waktu_mulai`),
    KEY `idx_percobaan_kuis_pelajar` (`kuis_id`, `pelajar_id`, `status`),
    KEY `idx_percobaan_peringkat` (`status`, `pelajar_id`, `kuis_id`, `skor`),
    CONSTRAINT `fk_percobaan_kuis` FOREIGN KEY (`kuis_id`) REFERENCES `kuis` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_percobaan_pelajar` FOREIGN KEY (`pelajar_id`) REFERENCES `user` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 8. Tabel `jawaban_percobaan`
-- Mencatat jawaban per soal pada suatu percobaan (soal kosong tidak disimpan, BR-08).
-- Hanya boleh 1 jawaban per soal dalam 1 percobaan: UNIQUE (`percobaan_id`, `soal_id`) (BR-11).
-- Jika soal/opsi dihapus saat revisi kuis, `soal_id`/`opsi_id` menjadi NULL namun
-- snapshot `benar` dan `poin` tetap tersimpan agar riwayat skor tetap utuh (PRD 10.2 & KT-11).
-- ----------------------------------------------------------------------------
CREATE TABLE `jawaban_percobaan` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `percobaan_id` INT UNSIGNED NOT NULL,
    `soal_id` INT UNSIGNED DEFAULT NULL,
    `opsi_id` INT UNSIGNED DEFAULT NULL,
    `benar` TINYINT(1) NOT NULL DEFAULT 0,
    `poin` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `dijawab_pada` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_jawaban_percobaan_soal` (`percobaan_id`, `soal_id`),
    KEY `idx_jawaban_soal_benar` (`soal_id`, `benar`),
    KEY `idx_jawaban_opsi` (`opsi_id`),
    CONSTRAINT `fk_jawaban_percobaan` FOREIGN KEY (`percobaan_id`) REFERENCES `percobaan_kuis` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_jawaban_soal` FOREIGN KEY (`soal_id`) REFERENCES `soal` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_jawaban_opsi` FOREIGN KEY (`opsi_id`) REFERENCES `opsi_jawaban` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 9. Tabel `log_status_kuis`
-- Mencatat jejak audit perubahan status kuis beserta pelaku dan catatan keputusan
-- (Ajukan publish, Setujui, Tolak, Revisi, Tarik untuk edit, Moderasi).
-- ----------------------------------------------------------------------------
CREATE TABLE `log_status_kuis` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `kuis_id` INT UNSIGNED NOT NULL,
    `diubah_oleh` INT UNSIGNED NOT NULL,
    `status_dari` ENUM('draft', 'menunggu', 'terbit', 'ditolak', 'ditarik') NOT NULL,
    `status_ke` ENUM('draft', 'menunggu', 'terbit', 'ditolak', 'ditarik') NOT NULL,
    `catatan` TEXT DEFAULT NULL,
    `dibuat_pada` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_log_status_kuis_waktu` (`kuis_id`, `dibuat_pada`),
    KEY `idx_log_status_pelaku` (`diubah_oleh`),
    CONSTRAINT `fk_log_status_kuis` FOREIGN KEY (`kuis_id`) REFERENCES `kuis` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_log_status_user` FOREIGN KEY (`diubah_oleh`) REFERENCES `user` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 10. Tabel `log_login`
-- Mencatat aktivitas/percobaan login berdasarkan email dan alamat IP.
-- ----------------------------------------------------------------------------
CREATE TABLE `log_login` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `email` VARCHAR(150) NOT NULL,
    `alamat_ip` VARCHAR(45) NOT NULL,
    `waktu` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_log_login_cek` (`email`, `alamat_ip`, `waktu`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
