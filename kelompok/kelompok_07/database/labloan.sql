CREATE DATABASE IF NOT EXISTS labloan CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE labloan;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS detail_peminjaman;
DROP TABLE IF EXISTS peminjaman;
DROP TABLE IF EXISTS alat;
DROP TABLE IF EXISTS kategori;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE users (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nama VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'peminjam') NOT NULL DEFAULT 'peminjam',
    nim_nip VARCHAR(30) NULL,
    no_hp VARCHAR(20) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_users_email (email),
    KEY idx_users_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE kategori (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nama VARCHAR(100) NOT NULL,
    deskripsi TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_kategori_nama (nama)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE alat (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    kategori_id INT UNSIGNED NOT NULL,
    kode VARCHAR(30) NOT NULL,
    nama VARCHAR(150) NOT NULL,
    jenis ENUM('alat', 'ruang') NOT NULL DEFAULT 'alat',
    deskripsi TEXT NULL,
    stok INT UNSIGNED NOT NULL DEFAULT 0,
    lokasi VARCHAR(100) NULL,
    kondisi ENUM('baik', 'rusak_ringan', 'rusak_berat') NOT NULL DEFAULT 'baik',
    foto VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_alat_kode (kode),
    KEY idx_alat_kategori (kategori_id),
    KEY idx_alat_jenis (jenis),
    KEY idx_alat_nama (nama),
    CONSTRAINT fk_alat_kategori FOREIGN KEY (kategori_id) REFERENCES kategori (id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE peminjaman (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    kode VARCHAR(30) NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    tanggal_pinjam DATETIME NOT NULL,
    tanggal_kembali_rencana DATETIME NOT NULL,
    tanggal_kembali_aktual DATETIME NULL,
    keperluan TEXT NOT NULL,
    status ENUM('menunggu', 'disetujui', 'ditolak', 'dikembalikan') NOT NULL DEFAULT 'menunggu',
    catatan_admin TEXT NULL,
    diproses_oleh INT UNSIGNED NULL,
    diproses_pada DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_peminjaman_kode (kode),
    KEY idx_peminjaman_user (user_id),
    KEY idx_peminjaman_status (status),
    KEY idx_peminjaman_jadwal (tanggal_pinjam, tanggal_kembali_rencana),
    KEY idx_peminjaman_admin (diproses_oleh),
    CONSTRAINT fk_peminjaman_user FOREIGN KEY (user_id) REFERENCES users (id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_peminjaman_admin FOREIGN KEY (diproses_oleh) REFERENCES users (id) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT chk_peminjaman_tanggal CHECK (tanggal_kembali_rencana > tanggal_pinjam)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE detail_peminjaman (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    peminjaman_id INT UNSIGNED NOT NULL,
    alat_id INT UNSIGNED NOT NULL,
    jumlah INT UNSIGNED NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    UNIQUE KEY uk_detail_peminjaman_alat (peminjaman_id, alat_id),
    KEY idx_detail_alat (alat_id),
    CONSTRAINT fk_detail_peminjaman FOREIGN KEY (peminjaman_id) REFERENCES peminjaman (id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_detail_alat FOREIGN KEY (alat_id) REFERENCES alat (id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_detail_jumlah CHECK (jumlah > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO users (nama, email, password, role, nim_nip) VALUES
('Administrator Lab', 'admin@labloan.test', '$2y$10$SXh1lgKoqyFS6mgoY2np6O.8mKy40P6W.Utw3AyP88EJjf3Blwfr2', 'admin', NULL);

INSERT INTO kategori (nama, deskripsi) VALUES
('Komputer & Jaringan', 'Laptop, router, switch, dan perangkat jaringan'),
('Elektronika', 'Multimeter, osiloskop, power supply, dan komponen praktikum'),
('Multimedia', 'Proyektor, kamera, dan perangkat presentasi'),
('Ruang Laboratorium', 'Ruang praktikum dan ruang diskusi yang dapat dipinjam');

INSERT INTO alat (kategori_id, kode, nama, jenis, deskripsi, stok, lokasi, kondisi) VALUES
(1, 'KJ-001', 'Laptop Lenovo ThinkPad E14', 'alat', 'Intel Core i5, RAM 8 GB, SSD 512 GB', 10, 'Lemari A1', 'baik'),
(1, 'KJ-002', 'Router MikroTik hAP ac2', 'alat', 'Router untuk praktikum jaringan', 8, 'Lemari A2', 'baik'),
(1, 'KJ-003', 'Kabel UTP Cat6 5 m', 'alat', 'Kabel jaringan siap pakai', 30, 'Laci A3', 'baik'),
(2, 'EL-001', 'Multimeter Digital Sanwa', 'alat', 'Pengukuran tegangan, arus, dan resistansi', 15, 'Lemari B1', 'baik'),
(2, 'EL-002', 'Osiloskop Rigol DS1054Z', 'alat', 'Osiloskop digital 4 kanal 50 MHz', 4, 'Meja B2', 'baik'),
(2, 'EL-003', 'Kit Arduino Uno', 'alat', 'Arduino Uno R3 lengkap dengan sensor dasar', 12, 'Lemari B3', 'rusak_ringan'),
(3, 'MM-001', 'Proyektor Epson EB-X51', 'alat', 'Proyektor XGA 3800 lumen', 3, 'Lemari C1', 'baik'),
(4, 'RL-001', 'Lab Komputer 1', 'ruang', 'Kapasitas 40 orang, dilengkapi AC dan proyektor', 1, 'Gedung Teknik Lt. 2', 'baik'),
(4, 'RL-002', 'Ruang Diskusi', 'ruang', 'Kapasitas 12 orang dengan papan tulis', 1, 'Gedung Teknik Lt. 1', 'baik');
