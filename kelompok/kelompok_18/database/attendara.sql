-- ATTENDARA database schema
-- Compatible target: MySQL 8+ / MariaDB 10.5+ with InnoDB.
-- Import this file in a local development database server (Laragon/XAMPP).
-- It creates the database and tables only; it does not insert seed accounts/data.
-- No automatic attendance points, final absence-reason list, or 2-second rule is encoded.

CREATE DATABASE IF NOT EXISTS attendara
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE attendara;

CREATE TABLE IF NOT EXISTS users (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  username VARCHAR(100) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('admin', 'guru') NOT NULL,
  is_active BOOLEAN NOT NULL DEFAULT TRUE,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_users_username (username)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS guru (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id BIGINT UNSIGNED NULL,
  nomor_induk VARCHAR(50) NOT NULL,
  nama VARCHAR(150) NOT NULL,
  email VARCHAR(254) NULL,
  no_telepon VARCHAR(30) NULL,
  is_active BOOLEAN NOT NULL DEFAULT TRUE,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_guru_user_id (user_id),
  UNIQUE KEY uq_guru_nomor_induk (nomor_induk),
  CONSTRAINT fk_guru_user
    FOREIGN KEY (user_id) REFERENCES users (id)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS siswa (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  nomor_induk VARCHAR(50) NOT NULL,
  nama VARCHAR(150) NOT NULL,
  jenis_kelamin VARCHAR(20) NULL,
  tanggal_lahir DATE NULL,
  is_active BOOLEAN NOT NULL DEFAULT TRUE,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_siswa_nomor_induk (nomor_induk),
  KEY idx_siswa_nama (nama)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS kelas (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  kode VARCHAR(30) NOT NULL,
  nama VARCHAR(100) NOT NULL,
  tingkat TINYINT UNSIGNED NULL,
  is_active BOOLEAN NOT NULL DEFAULT TRUE,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_kelas_kode (kode),
  KEY idx_kelas_nama (nama)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS semester (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  kode VARCHAR(40) NOT NULL,
  nama VARCHAR(100) NOT NULL,
  tanggal_mulai DATE NOT NULL,
  tanggal_selesai DATE NOT NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'belum_aktif',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_semester_kode (kode),
  KEY idx_semester_status (status),
  KEY idx_semester_tanggal (tanggal_mulai, tanggal_selesai)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS anggota_kelas (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  siswa_id BIGINT UNSIGNED NOT NULL,
  kelas_id BIGINT UNSIGNED NOT NULL,
  semester_id BIGINT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  -- One placement per student per semester; keeps semester history as new rows.
  UNIQUE KEY uq_anggota_kelas_siswa_semester (siswa_id, semester_id),
  KEY idx_anggota_kelas_semester_kelas (semester_id, kelas_id),
  CONSTRAINT fk_anggota_kelas_siswa
    FOREIGN KEY (siswa_id) REFERENCES siswa (id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_anggota_kelas_kelas
    FOREIGN KEY (kelas_id) REFERENCES kelas (id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_anggota_kelas_semester
    FOREIGN KEY (semester_id) REFERENCES semester (id)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS penugasan_guru (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  guru_id BIGINT UNSIGNED NOT NULL,
  kelas_id BIGINT UNSIGNED NOT NULL,
  semester_id BIGINT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  -- Current model has no subject entity: one teacher/class/semester assignment.
  UNIQUE KEY uq_penugasan_guru_kelas_semester (guru_id, kelas_id, semester_id),
  KEY idx_penugasan_semester_kelas (semester_id, kelas_id),
  CONSTRAINT fk_penugasan_guru_guru
    FOREIGN KEY (guru_id) REFERENCES guru (id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_penugasan_guru_kelas
    FOREIGN KEY (kelas_id) REFERENCES kelas (id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_penugasan_guru_semester
    FOREIGN KEY (semester_id) REFERENCES semester (id)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS jadwal_mengajar (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  penugasan_guru_id BIGINT UNSIGNED NOT NULL,
  hari TINYINT UNSIGNED NOT NULL COMMENT 'Mapping day number to weekday is an application convention.',
  jam_mulai TIME NOT NULL,
  jam_selesai TIME NOT NULL,
  berlaku_mulai DATE NULL,
  berlaku_sampai DATE NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_jadwal_id_penugasan (id, penugasan_guru_id),
  KEY idx_jadwal_penugasan_hari_jam (penugasan_guru_id, hari, jam_mulai),
  CONSTRAINT fk_jadwal_penugasan
    FOREIGN KEY (penugasan_guru_id) REFERENCES penugasan_guru (id)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS sesi_pembelajaran (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  penugasan_guru_id BIGINT UNSIGNED NOT NULL,
  jadwal_mengajar_id BIGINT UNSIGNED NULL,
  tanggal DATE NOT NULL,
  waktu_mulai DATETIME NULL,
  waktu_selesai DATETIME NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'direncanakan',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_sesi_penugasan_tanggal (penugasan_guru_id, tanggal),
  KEY idx_sesi_jadwal_penugasan (jadwal_mengajar_id, penugasan_guru_id),
  CONSTRAINT fk_sesi_penugasan
    FOREIGN KEY (penugasan_guru_id) REFERENCES penugasan_guru (id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  -- When a schedule is provided, it must belong to the same teaching assignment.
  CONSTRAINT fk_sesi_jadwal_penugasan
    FOREIGN KEY (jadwal_mengajar_id, penugasan_guru_id)
    REFERENCES jadwal_mengajar (id, penugasan_guru_id)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS absensi (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  sesi_pembelajaran_id BIGINT UNSIGNED NOT NULL,
  siswa_id BIGINT UNSIGNED NOT NULL,
  status VARCHAR(30) NOT NULL,
  dicatat_oleh_user_id BIGINT UNSIGNED NOT NULL,
  waktu_dicatat DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  catatan VARCHAR(500) NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_absensi_sesi_siswa (sesi_pembelajaran_id, siswa_id),
  KEY idx_absensi_siswa_sesi (siswa_id, sesi_pembelajaran_id),
  KEY idx_absensi_pencatat (dicatat_oleh_user_id),
  CONSTRAINT fk_absensi_sesi
    FOREIGN KEY (sesi_pembelajaran_id) REFERENCES sesi_pembelajaran (id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_absensi_siswa
    FOREIGN KEY (siswa_id) REFERENCES siswa (id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_absensi_pencatat
    FOREIGN KEY (dicatat_oleh_user_id) REFERENCES users (id)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS nilai_keaktifan (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  sesi_pembelajaran_id BIGINT UNSIGNED NOT NULL,
  siswa_id BIGINT UNSIGNED NOT NULL,
  poin DECIMAL(7,2) NOT NULL,
  dicatat_oleh_user_id BIGINT UNSIGNED NOT NULL,
  waktu_dicatat DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  catatan VARCHAR(500) NULL,
  PRIMARY KEY (id),
  KEY idx_nilai_sesi_siswa (sesi_pembelajaran_id, siswa_id),
  KEY idx_nilai_siswa_sesi (siswa_id, sesi_pembelajaran_id),
  KEY idx_nilai_pencatat (dicatat_oleh_user_id),
  CONSTRAINT fk_nilai_sesi
    FOREIGN KEY (sesi_pembelajaran_id) REFERENCES sesi_pembelajaran (id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_nilai_siswa
    FOREIGN KEY (siswa_id) REFERENCES siswa (id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_nilai_pencatat
    FOREIGN KEY (dicatat_oleh_user_id) REFERENCES users (id)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS kelompok (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  kelas_id BIGINT UNSIGNED NOT NULL,
  semester_id BIGINT UNSIGNED NULL COMMENT 'NULL means no semester scope; lifecycle policy is not finalized.',
  nama VARCHAR(100) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_kelompok_kelas_semester (kelas_id, semester_id),
  CONSTRAINT fk_kelompok_kelas
    FOREIGN KEY (kelas_id) REFERENCES kelas (id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_kelompok_semester
    FOREIGN KEY (semester_id) REFERENCES semester (id)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS anggota_kelompok (
  kelompok_id BIGINT UNSIGNED NOT NULL,
  siswa_id BIGINT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (kelompok_id, siswa_id),
  KEY idx_anggota_kelompok_siswa (siswa_id),
  CONSTRAINT fk_anggota_kelompok_kelompok
    FOREIGN KEY (kelompok_id) REFERENCES kelompok (id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_anggota_kelompok_siswa
    FOREIGN KEY (siswa_id) REFERENCES siswa (id)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;
