CREATE DATABASE IF NOT EXISTS db_oneblood;
use db_oneblood;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    no_hp VARCHAR(20) DEFAULT NULL,
    alamat TEXT DEFAULT NULL,
    role ENUM('admin', 'petugas', 'pendonor') NOT NULL DEFAULT 'pendonor',
    gol_darah ENUM('A', 'B', 'AB', 'O') DEFAULT NULL,
    rhesus ENUM('+', '-') DEFAULT NULL,
    tgl_lahir DATE DEFAULT NULL,
    berat_badan DECIMAL(5,2) DEFAULT NULL,
    tgl_donor_terakhir DATE DEFAULT NULL,
    status_aktif TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)

CREATE TABLE events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_event VARCHAR(150) NOT NULL,
    lokasi VARCHAR(200) NOT NULL,
    tanggal DATE NOT NULL,
    jam_mulai TIME NOT NULL,
    jam_selesai TIME NOT NULL,
    kuota INT NOT NULL DEFAULT 50,
    status_event ENUM('akan_datang', 'berlangsung', 'selesai') NOT NULL DEFAULT 'akan_datang',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)

CREATE TABLE antrean_skrining (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    event_id INT NOT NULL,
    kode_tiket VARCHAR(30) NOT NULL UNIQUE,
    q_sehat TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1=Merasa sehat hari ini',
    q_tidur_cukup TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1=Tidur >= 5 jam',
    q_bebas_obat TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1=Tidak minum antibiotik/obat 3 hari terakhir',
    berat_badan_aktual DECIMAL(5,2) DEFAULT NULL,
    tensi VARCHAR(20) DEFAULT NULL COMMENT 'Contoh: 120/80',
    hb DECIMAL(4,1) DEFAULT NULL COMMENT 'Standar: 12.5 - 17.0 g/dL',
    suhu_tubuh DECIMAL(4,1) DEFAULT NULL COMMENT 'Standar: 36.0 - 37.5 C',
    status_skrining ENUM('menunggu', 'lolos', 'ditolak', 'selesai') NOT NULL DEFAULT 'menunggu',
    catatan_medis TEXT DEFAULT NULL,
    petugas_id INT DEFAULT NULL,
    waktu_daftar TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    waktu_periksa DATETIME DEFAULT NULL,
    CONSTRAINT fk_skrining_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_skrining_event FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    CONSTRAINT fk_skrining_petugas FOREIGN KEY (petugas_id) REFERENCES users(id) ON DELETE SET NULL
)

CREATE TABLE kantong_darah (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kode_kantong VARCHAR(30) NOT NULL UNIQUE,
    skrining_id INT DEFAULT NULL COMMENT 'Null jika input manual dari stok luar',
    gol_darah ENUM('A', 'B', 'AB', 'O') NOT NULL,
    rhesus ENUM('+', '-') NOT NULL,
    komponen ENUM('WB', 'PRC', 'TC') NOT NULL DEFAULT 'WB' COMMENT 'WB=Whole Blood, PRC=Packed Red Cell, TC=Thrombocyte',
    volume_ml INT NOT NULL DEFAULT 350,
    lokasi_rak VARCHAR(50) NOT NULL DEFAULT 'Cooler-A1',
    tgl_ambil DATE NOT NULL,
    tgl_expired DATE NOT NULL COMMENT 'Standar +35 hari dari tgl_ambil',
    status ENUM('tersedia', 'keluar', 'discard') NOT NULL DEFAULT 'tersedia',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_kantong_skrining FOREIGN KEY (skrining_id) REFERENCES antrean_skrining(id) ON DELETE SET NULL
)

CREATE TABLE log_suhu (
    id INT AUTO_INCREMENT PRIMARY KEY,
    petugas_id INT NOT NULL,
    kode_kulkas VARCHAR(50) NOT NULL DEFAULT 'Cooler-A1',
    suhu_celcius DECIMAL(4,1) NOT NULL COMMENT 'Standar aman: 2.0 s/d 6.0 C',
    status_aman ENUM('Normal', 'Peringatan') NOT NULL DEFAULT 'Normal',
    catatan VARCHAR(255) DEFAULT NULL,
    waktu_catat TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_suhu_petugas FOREIGN KEY (petugas_id) REFERENCES users(id) ON DELETE CASCADE
)

CREATE TABLE distribusi_darah (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kantong_id INT NOT NULL,
    petugas_id INT NOT NULL,
    nama_pasien VARCHAR(100) NOT NULL,
    rs_tujuan VARCHAR(150) NOT NULL,
    dokter_pengirim VARCHAR(100) NOT NULL,
    keperluan_medis VARCHAR(150) DEFAULT 'Transfusi Medis',
    tgl_keluar DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_distribusi_kantong FOREIGN KEY (kantong_id) REFERENCES kantong_darah(id) ON DELETE CASCADE,
    CONSTRAINT fk_distribusi_petugas FOREIGN KEY (petugas_id) REFERENCES users(id) ON DELETE CASCADE
)

CREATE TABLE sos_broadcast (
    id INT AUTO_INCREMENT PRIMARY KEY,
    judul_pesan VARCHAR(150) NOT NULL,
    gol_darah ENUM('A', 'B', 'AB', 'O') NOT NULL,
    rhesus ENUM('+', '-') NOT NULL,
    rs_peminta VARCHAR(150) NOT NULL,
    kebutuhan_kantong INT NOT NULL DEFAULT 1,
    kontak_darurat VARCHAR(30) NOT NULL,
    status_aktif TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1=Tampil di Banner, 0=Selesai',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)