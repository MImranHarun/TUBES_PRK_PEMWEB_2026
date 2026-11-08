
CREATE DATABASE IF NOT EXISTS `akademik`
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `akademik`;
SET NAMES utf8mb4;

CREATE TABLE `GURU` (
  `id_guru` INT NOT NULL AUTO_INCREMENT,
  `nip` VARCHAR(50),
  `nama_guru` VARCHAR(100),
  `email` VARCHAR(100),
  `password` VARCHAR(255),
  `peran` VARCHAR(50),
  PRIMARY KEY (`id_guru`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `MATA_PELAJARAN` (
  `id_mapel` INT NOT NULL AUTO_INCREMENT,
  `kode_mapel` VARCHAR(20),
  `nama_mapel` VARCHAR(100),
  PRIMARY KEY (`id_mapel`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `PERIODE_PENILAIAN` (
  `id_periode` INT NOT NULL AUTO_INCREMENT,
  `tahun_ajaran` VARCHAR(20),
  `semester` VARCHAR(20),
  `status_aktif` BOOLEAN,
  PRIMARY KEY (`id_periode`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `KELAS` (
  `id_kelas` INT NOT NULL AUTO_INCREMENT,
  `id_wali_kelas` INT NULL,
  `nama_kelas` VARCHAR(50),
  `tingkat` VARCHAR(20),
  PRIMARY KEY (`id_kelas`),
  CONSTRAINT `fk_kelas_wali` FOREIGN KEY (`id_wali_kelas`)
    REFERENCES `GURU` (`id_guru`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `SISWA` (
  `id_kelas` INT NOT NULL,
  `id_siswa` INT NOT NULL AUTO_INCREMENT,
  `nisn` VARCHAR(50),
  `nama_siswa` VARCHAR(100),
  `jenis_kelamin` VARCHAR(20),
  PRIMARY KEY (`id_siswa`),
  CONSTRAINT `fk_siswa_kelas` FOREIGN KEY (`id_kelas`)
    REFERENCES `KELAS` (`id_kelas`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `PENUGASAN_MENGAJAR` (
  `id_periode` INT NOT NULL,
  `id_mapel` INT NOT NULL,
  `id_kelas` INT NOT NULL,
  `id_guru` INT NOT NULL,
  `id_penugasan` INT NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`id_penugasan`),
  CONSTRAINT `fk_penugasan_periode` FOREIGN KEY (`id_periode`)
    REFERENCES `PERIODE_PENILAIAN` (`id_periode`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_penugasan_mapel` FOREIGN KEY (`id_mapel`)
    REFERENCES `MATA_PELAJARAN` (`id_mapel`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_penugasan_kelas` FOREIGN KEY (`id_kelas`)
    REFERENCES `KELAS` (`id_kelas`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_penugasan_guru` FOREIGN KEY (`id_guru`)
    REFERENCES `GURU` (`id_guru`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `PENGATURAN_BOBOT` (
  `id_penugasan` INT NOT NULL,
  `id_bobot` INT NOT NULL AUTO_INCREMENT,
  `bobot_tugas` FLOAT,
  `bobot_kuis` FLOAT,
  `bobot_uts` FLOAT,
  `bobot_uas` FLOAT,
  PRIMARY KEY (`id_bobot`),
  UNIQUE KEY `uq_bobot_penugasan` (`id_penugasan`),
  CONSTRAINT `fk_bobot_penugasan` FOREIGN KEY (`id_penugasan`)
    REFERENCES `PENUGASAN_MENGAJAR` (`id_penugasan`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `PENILAIAN` (
  `id_penugasan` INT NOT NULL,
  `id_penilaian` INT NOT NULL AUTO_INCREMENT,
  `judul_penilaian` VARCHAR(100),
  `jenis_komponen` VARCHAR(50),
  `tenggat_waktu` DATETIME,
  `tanggal_dibuat` DATETIME,
  PRIMARY KEY (`id_penilaian`),
  CONSTRAINT `fk_penilaian_penugasan` FOREIGN KEY (`id_penugasan`)
    REFERENCES `PENUGASAN_MENGAJAR` (`id_penugasan`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `NILAI_DAN_PENGUMPULAN` (
  `id_siswa` INT NOT NULL,
  `id_penilaian` INT NOT NULL,
  `id_nilai_pengumpulan` INT NOT NULL AUTO_INCREMENT,
  `waktu_pengumpulan` DATETIME,
  `status_pengumpulan` VARCHAR(50),
  `nilai` FLOAT,
  PRIMARY KEY (`id_nilai_pengumpulan`),
  UNIQUE KEY `uq_nilai_siswa_penilaian` (`id_siswa`, `id_penilaian`),
  CONSTRAINT `fk_nilai_siswa` FOREIGN KEY (`id_siswa`)
    REFERENCES `SISWA` (`id_siswa`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_nilai_penilaian` FOREIGN KEY (`id_penilaian`)
    REFERENCES `PENILAIAN` (`id_penilaian`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `REKAP_AKADEMIK_KUADRAN` (
  `id_penugasan` INT NOT NULL,
  `id_siswa` INT NOT NULL,
  `id_rekap` INT NOT NULL AUTO_INCREMENT,
  `nilai_akhir` FLOAT,
  `indeks_kedisiplinan` FLOAT,
  `status_kelengkapan_nilai` VARCHAR(50),
  `kategori_kuadran` VARCHAR(50),
  `rekomendasi_sistem` TEXT,
  PRIMARY KEY (`id_rekap`),
  UNIQUE KEY `uq_rekap_penugasan_siswa` (`id_penugasan`, `id_siswa`),
  CONSTRAINT `fk_rekap_penugasan` FOREIGN KEY (`id_penugasan`)
    REFERENCES `PENUGASAN_MENGAJAR` (`id_penugasan`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_rekap_siswa` FOREIGN KEY (`id_siswa`)
    REFERENCES `SISWA` (`id_siswa`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `CATATAN_BIMBINGAN` (
  `id_periode` INT NOT NULL,
  `id_guru` INT NOT NULL,
  `id_siswa` INT NOT NULL,
  `id_catatan` INT NOT NULL AUTO_INCREMENT,
  `catatan_kualitatif` TEXT,
  `rencana_tindakan` TEXT,
  `status_tindak_lanjut` VARCHAR(50),
  `evaluasi_hasil` TEXT,
  `tanggal_interaksi` DATETIME,
  PRIMARY KEY (`id_catatan`),
  CONSTRAINT `fk_catatan_periode` FOREIGN KEY (`id_periode`)
    REFERENCES `PERIODE_PENILAIAN` (`id_periode`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_catatan_guru` FOREIGN KEY (`id_guru`)
    REFERENCES `GURU` (`id_guru`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_catatan_siswa` FOREIGN KEY (`id_siswa`)
    REFERENCES `SISWA` (`id_siswa`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
