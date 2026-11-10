CREATE DATABASE savesmart CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE savesmart;

CREATE TABLE keluarga (
    id_keluarga INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nama_keluarga VARCHAR(100) NOT NULL,
    alamat VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id_keluarga)
) ENGINE=InnoDB;

CREATE TABLE pengguna (
    id_pengguna INT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_keluarga INT UNSIGNED NULL,
    nama_lengkap VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin','kepala_keluarga','anggota_keluarga') NOT NULL DEFAULT 'kepala_keluarga',
    status ENUM('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id_pengguna),
    UNIQUE KEY uq_pengguna_email (email),
    KEY idx_pengguna_keluarga (id_keluarga),
    CONSTRAINT fk_pengguna_keluarga FOREIGN KEY (id_keluarga)
        REFERENCES keluarga (id_keluarga) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE kategori (
    id_kategori INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nama_kategori VARCHAR(60) NOT NULL,
    jenis ENUM('pemasukan','pengeluaran') NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_kategori),
    UNIQUE KEY uq_kategori (nama_kategori, jenis)
) ENGINE=InnoDB;

CREATE TABLE pemasukan (
    id_pemasukan INT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_keluarga INT UNSIGNED NOT NULL,
    id_pengguna INT UNSIGNED NOT NULL,
    id_kategori INT UNSIGNED NOT NULL,
    jumlah DECIMAL(15,2) NOT NULL,
    tanggal DATE NOT NULL,
    keterangan VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id_pemasukan),
    KEY idx_pemasukan_keluarga_tgl (id_keluarga, tanggal),
    KEY idx_pemasukan_kategori (id_kategori),
    KEY idx_pemasukan_pengguna (id_pengguna),
    CONSTRAINT chk_pemasukan_jumlah CHECK (jumlah > 0),
    CONSTRAINT fk_pemasukan_keluarga FOREIGN KEY (id_keluarga)
        REFERENCES keluarga (id_keluarga) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_pemasukan_pengguna FOREIGN KEY (id_pengguna)
        REFERENCES pengguna (id_pengguna) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_pemasukan_kategori FOREIGN KEY (id_kategori)
        REFERENCES kategori (id_kategori) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE pengeluaran (
    id_pengeluaran INT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_keluarga INT UNSIGNED NOT NULL,
    id_pengguna INT UNSIGNED NOT NULL,
    id_kategori INT UNSIGNED NOT NULL,
    jumlah DECIMAL(15,2) NOT NULL,
    tanggal DATE NOT NULL,
    keterangan VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id_pengeluaran),
    KEY idx_pengeluaran_keluarga_tgl (id_keluarga, tanggal),
    KEY idx_pengeluaran_kategori (id_kategori),
    KEY idx_pengeluaran_pengguna (id_pengguna),
    CONSTRAINT chk_pengeluaran_jumlah CHECK (jumlah > 0),
    CONSTRAINT fk_pengeluaran_keluarga FOREIGN KEY (id_keluarga)
        REFERENCES keluarga (id_keluarga) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_pengeluaran_pengguna FOREIGN KEY (id_pengguna)
        REFERENCES pengguna (id_pengguna) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_pengeluaran_kategori FOREIGN KEY (id_kategori)
        REFERENCES kategori (id_kategori) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE anggaran (
    id_anggaran INT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_keluarga INT UNSIGNED NOT NULL,
    id_kategori INT UNSIGNED NOT NULL,
    bulan TINYINT UNSIGNED NOT NULL,
    tahun SMALLINT UNSIGNED NOT NULL,
    jumlah_anggaran DECIMAL(15,2) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id_anggaran),
    UNIQUE KEY uq_anggaran (id_keluarga, id_kategori, bulan, tahun),
    CONSTRAINT chk_anggaran_bulan CHECK (bulan BETWEEN 1 AND 12),
    CONSTRAINT chk_anggaran_jumlah CHECK (jumlah_anggaran >= 0),
    CONSTRAINT fk_anggaran_keluarga FOREIGN KEY (id_keluarga)
        REFERENCES keluarga (id_keluarga) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_anggaran_kategori FOREIGN KEY (id_kategori)
        REFERENCES kategori (id_kategori) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE target_tabungan (
    id_target INT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_keluarga INT UNSIGNED NOT NULL,
    nama_target VARCHAR(100) NOT NULL,
    target_nominal DECIMAL(15,2) NOT NULL,
    tanggal_mulai DATE NOT NULL,
    tanggal_target DATE NULL,
    status ENUM('berjalan','tercapai','dibatalkan') NOT NULL DEFAULT 'berjalan',
    catatan VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id_target),
    KEY idx_target_keluarga (id_keluarga),
    CONSTRAINT chk_target_nominal CHECK (target_nominal > 0),
    CONSTRAINT fk_target_keluarga FOREIGN KEY (id_keluarga)
        REFERENCES keluarga (id_keluarga) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE setoran_tabungan (
    id_setoran INT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_target INT UNSIGNED NOT NULL,
    id_pengguna INT UNSIGNED NOT NULL,
    jumlah DECIMAL(15,2) NOT NULL,
    tanggal DATE NOT NULL,
    keterangan VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_setoran),
    KEY idx_setoran_target (id_target),
    KEY idx_setoran_pengguna (id_pengguna),
    CONSTRAINT chk_setoran_jumlah CHECK (jumlah > 0),
    CONSTRAINT fk_setoran_target FOREIGN KEY (id_target)
        REFERENCES target_tabungan (id_target) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_setoran_pengguna FOREIGN KEY (id_pengguna)
        REFERENCES pengguna (id_pengguna) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

DELIMITER $$

CREATE TRIGGER trg_setoran_after_insert
AFTER INSERT ON setoran_tabungan
FOR EACH ROW
BEGIN
    DECLARE v_terkumpul DECIMAL(15,2);
    DECLARE v_target DECIMAL(15,2);
    SELECT COALESCE(SUM(jumlah),0) INTO v_terkumpul FROM setoran_tabungan WHERE id_target = NEW.id_target;
    SELECT target_nominal INTO v_target FROM target_tabungan WHERE id_target = NEW.id_target;
    IF v_terkumpul >= v_target THEN
        UPDATE target_tabungan SET status = 'tercapai'
        WHERE id_target = NEW.id_target AND status = 'berjalan';
    END IF;
END$$

CREATE TRIGGER trg_setoran_after_delete
AFTER DELETE ON setoran_tabungan
FOR EACH ROW
BEGIN
    DECLARE v_terkumpul DECIMAL(15,2);
    DECLARE v_target DECIMAL(15,2);
    SELECT COALESCE(SUM(jumlah),0) INTO v_terkumpul FROM setoran_tabungan WHERE id_target = OLD.id_target;
    SELECT target_nominal INTO v_target FROM target_tabungan WHERE id_target = OLD.id_target;
    IF v_terkumpul < v_target THEN
        UPDATE target_tabungan SET status = 'berjalan'
        WHERE id_target = OLD.id_target AND status = 'tercapai';
    END IF;
END$$

DELIMITER ;

CREATE OR REPLACE VIEW v_progres_tabungan AS
SELECT t.id_target, t.id_keluarga, t.nama_target, t.target_nominal,
       t.tanggal_mulai, t.tanggal_target, t.status,
       COALESCE(SUM(s.jumlah),0) AS terkumpul,
       t.target_nominal - COALESCE(SUM(s.jumlah),0) AS sisa,
       LEAST(100, ROUND(COALESCE(SUM(s.jumlah),0) / t.target_nominal * 100, 2)) AS persen
FROM target_tabungan t
LEFT JOIN setoran_tabungan s ON s.id_target = t.id_target
GROUP BY t.id_target;

CREATE OR REPLACE VIEW v_realisasi_anggaran AS
SELECT a.id_anggaran, a.id_keluarga, a.id_kategori, k.nama_kategori,
       a.bulan, a.tahun, a.jumlah_anggaran,
       COALESCE(r.realisasi,0) AS realisasi,
       a.jumlah_anggaran - COALESCE(r.realisasi,0) AS sisa_anggaran
FROM anggaran a
JOIN kategori k ON k.id_kategori = a.id_kategori
LEFT JOIN (
    SELECT id_keluarga, id_kategori, YEAR(tanggal) AS tahun, MONTH(tanggal) AS bulan, SUM(jumlah) AS realisasi
    FROM pengeluaran
    GROUP BY id_keluarga, id_kategori, YEAR(tanggal), MONTH(tanggal)
) r ON r.id_keluarga = a.id_keluarga AND r.id_kategori = a.id_kategori
   AND r.bulan = a.bulan AND r.tahun = a.tahun;

CREATE OR REPLACE VIEW v_transaksi AS
SELECT 'pemasukan' AS jenis, p.id_pemasukan AS id_transaksi, p.id_keluarga,
       p.id_pengguna, u.nama_lengkap AS pencatat, p.id_kategori, k.nama_kategori,
       p.jumlah, p.tanggal, p.keterangan
FROM pemasukan p
JOIN kategori k ON k.id_kategori = p.id_kategori
JOIN pengguna u ON u.id_pengguna = p.id_pengguna
UNION ALL
SELECT 'pengeluaran', e.id_pengeluaran, e.id_keluarga,
       e.id_pengguna, u.nama_lengkap, e.id_kategori, k.nama_kategori,
       e.jumlah, e.tanggal, e.keterangan
FROM pengeluaran e
JOIN kategori k ON k.id_kategori = e.id_kategori
JOIN pengguna u ON u.id_pengguna = e.id_pengguna;

CREATE OR REPLACE VIEW v_ringkasan_bulanan AS
SELECT x.id_keluarga, x.tahun, x.bulan,
       SUM(x.pemasukan) AS total_pemasukan,
       SUM(x.pengeluaran) AS total_pengeluaran,
       SUM(x.setoran) AS total_setoran
FROM (
    SELECT id_keluarga, YEAR(tanggal) AS tahun, MONTH(tanggal) AS bulan,
           SUM(jumlah) AS pemasukan, 0 AS pengeluaran, 0 AS setoran
    FROM pemasukan GROUP BY id_keluarga, YEAR(tanggal), MONTH(tanggal)
    UNION ALL
    SELECT id_keluarga, YEAR(tanggal), MONTH(tanggal), 0, SUM(jumlah), 0
    FROM pengeluaran GROUP BY id_keluarga, YEAR(tanggal), MONTH(tanggal)
    UNION ALL
    SELECT t.id_keluarga, YEAR(s.tanggal), MONTH(s.tanggal), 0, 0, SUM(s.jumlah)
    FROM setoran_tabungan s
    JOIN target_tabungan t ON t.id_target = s.id_target
    GROUP BY t.id_keluarga, YEAR(s.tanggal), MONTH(s.tanggal)
) x
GROUP BY x.id_keluarga, x.tahun, x.bulan;

INSERT INTO kategori (nama_kategori, jenis) VALUES
('Gaji','pemasukan'),
('Usaha','pemasukan'),
('Bonus','pemasukan'),
('Hadiah','pemasukan'),
('Lain-lain','pemasukan'),
('Makanan & Minuman','pengeluaran'),
('Transportasi','pengeluaran'),
('Pendidikan','pengeluaran'),
('Listrik & Air','pengeluaran'),
('Internet & Pulsa','pengeluaran'),
('Kesehatan','pengeluaran'),
('Hiburan','pengeluaran'),
('Belanja Rumah Tangga','pengeluaran'),
('Lain-lain','pengeluaran');

INSERT INTO pengguna (id_keluarga, nama_lengkap, email, password, role) VALUES
(NULL, 'Administrator', 'admin@savesmart.com', '$2y$10$iuvgzJPlo/MYLDYMM.kWiO2dnB/yn6RzGM2Y/XAU0ZZ6GV7ZzBYQG', 'admin');

DELIMITER $$

CREATE PROCEDURE sp_registrasi(IN p_nama_keluarga VARCHAR(100), IN p_nama VARCHAR(100), IN p_email VARCHAR(100), IN p_password VARCHAR(255))
BEGIN
    DECLARE v_id INT UNSIGNED;
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;
    START TRANSACTION;
    INSERT INTO keluarga (nama_keluarga) VALUES (p_nama_keluarga);
    SET v_id = LAST_INSERT_ID();
    INSERT INTO pengguna (id_keluarga, nama_lengkap, email, password, role)
    VALUES (v_id, p_nama, p_email, p_password, 'kepala_keluarga');
    COMMIT;
    SELECT v_id AS id_keluarga, LAST_INSERT_ID() AS id_pengguna;
END$$

CREATE PROCEDURE sp_login(IN p_email VARCHAR(100))
BEGIN
    SELECT id_pengguna, id_keluarga, nama_lengkap, password, role
    FROM pengguna
    WHERE email = p_email AND status = 'aktif'
    LIMIT 1;
END$$

CREATE PROCEDURE sp_tambah_anggota(IN p_id_keluarga INT UNSIGNED, IN p_nama VARCHAR(100), IN p_email VARCHAR(100), IN p_password VARCHAR(255))
BEGIN
    INSERT INTO pengguna (id_keluarga, nama_lengkap, email, password, role)
    VALUES (p_id_keluarga, p_nama, p_email, p_password, 'anggota_keluarga');
END$$

CREATE PROCEDURE sp_dashboard(IN p_id_keluarga INT UNSIGNED)
BEGIN
    SELECT
        COALESCE(SUM(total_pemasukan),0) AS total_pemasukan,
        COALESCE(SUM(total_pengeluaran),0) AS total_pengeluaran,
        COALESCE(SUM(total_setoran),0) AS total_setoran,
        COALESCE(SUM(total_pemasukan - total_pengeluaran - total_setoran),0) AS saldo,
        (SELECT COALESCE(SUM(s.jumlah),0)
           FROM setoran_tabungan s
           JOIN target_tabungan t ON t.id_target = s.id_target
          WHERE t.id_keluarga = p_id_keluarga) AS total_tabungan
    FROM v_ringkasan_bulanan
    WHERE id_keluarga = p_id_keluarga
      AND tahun = YEAR(CURDATE())
      AND bulan = MONTH(CURDATE());
END$$

CREATE PROCEDURE sp_grafik_bulanan(IN p_id_keluarga INT UNSIGNED)
BEGIN
    SELECT tahun, bulan, total_pemasukan, total_pengeluaran
    FROM v_ringkasan_bulanan
    WHERE id_keluarga = p_id_keluarga
      AND STR_TO_DATE(CONCAT(tahun,'-',bulan,'-01'),'%Y-%m-%d') >= DATE_SUB(DATE_FORMAT(CURDATE(),'%Y-%m-01'), INTERVAL 5 MONTH)
    ORDER BY tahun, bulan;
END$$

CREATE PROCEDURE sp_pengeluaran_per_kategori(IN p_id_keluarga INT UNSIGNED)
BEGIN
    SELECT k.nama_kategori, SUM(p.jumlah) AS total
    FROM pengeluaran p
    JOIN kategori k ON k.id_kategori = p.id_kategori
    WHERE p.id_keluarga = p_id_keluarga
      AND YEAR(p.tanggal) = YEAR(CURDATE())
      AND MONTH(p.tanggal) = MONTH(CURDATE())
    GROUP BY k.id_kategori, k.nama_kategori
    ORDER BY total DESC;
END$$

CREATE PROCEDURE sp_transaksi_terbaru(IN p_id_keluarga INT UNSIGNED)
BEGIN
    SELECT * FROM v_transaksi
    WHERE id_keluarga = p_id_keluarga
    ORDER BY tanggal DESC, id_transaksi DESC
    LIMIT 10;
END$$

CREATE PROCEDURE sp_pemasukan_tambah(IN p_id_keluarga INT UNSIGNED, IN p_id_pengguna INT UNSIGNED, IN p_id_kategori INT UNSIGNED, IN p_jumlah DECIMAL(15,2), IN p_tanggal DATE, IN p_keterangan VARCHAR(255))
BEGIN
    INSERT INTO pemasukan (id_keluarga, id_pengguna, id_kategori, jumlah, tanggal, keterangan)
    VALUES (p_id_keluarga, p_id_pengguna, p_id_kategori, p_jumlah, p_tanggal, p_keterangan);
END$$

CREATE PROCEDURE sp_pemasukan_daftar(IN p_id_keluarga INT UNSIGNED)
BEGIN
    SELECT p.*, k.nama_kategori, u.nama_lengkap
    FROM pemasukan p
    JOIN kategori k ON k.id_kategori = p.id_kategori
    JOIN pengguna u ON u.id_pengguna = p.id_pengguna
    WHERE p.id_keluarga = p_id_keluarga
    ORDER BY p.tanggal DESC, p.id_pemasukan DESC;
END$$

CREATE PROCEDURE sp_pemasukan_ubah(IN p_id INT UNSIGNED, IN p_id_keluarga INT UNSIGNED, IN p_id_kategori INT UNSIGNED, IN p_jumlah DECIMAL(15,2), IN p_tanggal DATE, IN p_keterangan VARCHAR(255))
BEGIN
    UPDATE pemasukan
    SET id_kategori = p_id_kategori, jumlah = p_jumlah, tanggal = p_tanggal, keterangan = p_keterangan
    WHERE id_pemasukan = p_id AND id_keluarga = p_id_keluarga;
END$$

CREATE PROCEDURE sp_pemasukan_hapus(IN p_id INT UNSIGNED, IN p_id_keluarga INT UNSIGNED)
BEGIN
    DELETE FROM pemasukan WHERE id_pemasukan = p_id AND id_keluarga = p_id_keluarga;
END$$

CREATE PROCEDURE sp_pengeluaran_tambah(IN p_id_keluarga INT UNSIGNED, IN p_id_pengguna INT UNSIGNED, IN p_id_kategori INT UNSIGNED, IN p_jumlah DECIMAL(15,2), IN p_tanggal DATE, IN p_keterangan VARCHAR(255))
BEGIN
    INSERT INTO pengeluaran (id_keluarga, id_pengguna, id_kategori, jumlah, tanggal, keterangan)
    VALUES (p_id_keluarga, p_id_pengguna, p_id_kategori, p_jumlah, p_tanggal, p_keterangan);
END$$

CREATE PROCEDURE sp_pengeluaran_daftar(IN p_id_keluarga INT UNSIGNED)
BEGIN
    SELECT e.*, k.nama_kategori, u.nama_lengkap
    FROM pengeluaran e
    JOIN kategori k ON k.id_kategori = e.id_kategori
    JOIN pengguna u ON u.id_pengguna = e.id_pengguna
    WHERE e.id_keluarga = p_id_keluarga
    ORDER BY e.tanggal DESC, e.id_pengeluaran DESC;
END$$

CREATE PROCEDURE sp_pengeluaran_ubah(IN p_id INT UNSIGNED, IN p_id_keluarga INT UNSIGNED, IN p_id_kategori INT UNSIGNED, IN p_jumlah DECIMAL(15,2), IN p_tanggal DATE, IN p_keterangan VARCHAR(255))
BEGIN
    UPDATE pengeluaran
    SET id_kategori = p_id_kategori, jumlah = p_jumlah, tanggal = p_tanggal, keterangan = p_keterangan
    WHERE id_pengeluaran = p_id AND id_keluarga = p_id_keluarga;
END$$

CREATE PROCEDURE sp_pengeluaran_hapus(IN p_id INT UNSIGNED, IN p_id_keluarga INT UNSIGNED)
BEGIN
    DELETE FROM pengeluaran WHERE id_pengeluaran = p_id AND id_keluarga = p_id_keluarga;
END$$

CREATE PROCEDURE sp_anggaran_simpan(IN p_id_keluarga INT UNSIGNED, IN p_id_kategori INT UNSIGNED, IN p_bulan TINYINT UNSIGNED, IN p_tahun SMALLINT UNSIGNED, IN p_jumlah DECIMAL(15,2))
BEGIN
    INSERT INTO anggaran (id_keluarga, id_kategori, bulan, tahun, jumlah_anggaran)
    VALUES (p_id_keluarga, p_id_kategori, p_bulan, p_tahun, p_jumlah)
    ON DUPLICATE KEY UPDATE jumlah_anggaran = VALUES(jumlah_anggaran);
END$$

CREATE PROCEDURE sp_anggaran_daftar(IN p_id_keluarga INT UNSIGNED, IN p_bulan TINYINT UNSIGNED, IN p_tahun SMALLINT UNSIGNED)
BEGIN
    SELECT *, ROUND(realisasi / NULLIF(jumlah_anggaran,0) * 100, 2) AS persen_terpakai
    FROM v_realisasi_anggaran
    WHERE id_keluarga = p_id_keluarga AND bulan = p_bulan AND tahun = p_tahun;
END$$

CREATE PROCEDURE sp_anggaran_hapus(IN p_id INT UNSIGNED, IN p_id_keluarga INT UNSIGNED)
BEGIN
    DELETE FROM anggaran WHERE id_anggaran = p_id AND id_keluarga = p_id_keluarga;
END$$

CREATE PROCEDURE sp_target_tambah(IN p_id_keluarga INT UNSIGNED, IN p_nama VARCHAR(100), IN p_nominal DECIMAL(15,2), IN p_mulai DATE, IN p_target DATE, IN p_catatan VARCHAR(255))
BEGIN
    INSERT INTO target_tabungan (id_keluarga, nama_target, target_nominal, tanggal_mulai, tanggal_target, catatan)
    VALUES (p_id_keluarga, p_nama, p_nominal, p_mulai, p_target, p_catatan);
END$$

CREATE PROCEDURE sp_target_daftar(IN p_id_keluarga INT UNSIGNED)
BEGIN
    SELECT * FROM v_progres_tabungan
    WHERE id_keluarga = p_id_keluarga
    ORDER BY status, tanggal_target;
END$$

CREATE PROCEDURE sp_target_ubah(IN p_id INT UNSIGNED, IN p_id_keluarga INT UNSIGNED, IN p_nama VARCHAR(100), IN p_nominal DECIMAL(15,2), IN p_target DATE, IN p_catatan VARCHAR(255))
BEGIN
    UPDATE target_tabungan
    SET nama_target = p_nama, target_nominal = p_nominal, tanggal_target = p_target, catatan = p_catatan
    WHERE id_target = p_id AND id_keluarga = p_id_keluarga;
END$$

CREATE PROCEDURE sp_target_hapus(IN p_id INT UNSIGNED, IN p_id_keluarga INT UNSIGNED)
BEGIN
    DELETE FROM target_tabungan WHERE id_target = p_id AND id_keluarga = p_id_keluarga;
END$$

CREATE PROCEDURE sp_setoran_tambah(IN p_id_target INT UNSIGNED, IN p_id_pengguna INT UNSIGNED, IN p_jumlah DECIMAL(15,2), IN p_tanggal DATE, IN p_keterangan VARCHAR(255))
BEGIN
    INSERT INTO setoran_tabungan (id_target, id_pengguna, jumlah, tanggal, keterangan)
    VALUES (p_id_target, p_id_pengguna, p_jumlah, p_tanggal, p_keterangan);
END$$

CREATE PROCEDURE sp_setoran_daftar(IN p_id_target INT UNSIGNED)
BEGIN
    SELECT s.*, u.nama_lengkap
    FROM setoran_tabungan s
    JOIN pengguna u ON u.id_pengguna = s.id_pengguna
    WHERE s.id_target = p_id_target
    ORDER BY s.tanggal DESC, s.id_setoran DESC;
END$$

CREATE PROCEDURE sp_laporan_transaksi(IN p_id_keluarga INT UNSIGNED, IN p_dari DATE, IN p_sampai DATE)
BEGIN
    SELECT * FROM v_transaksi
    WHERE id_keluarga = p_id_keluarga AND tanggal BETWEEN p_dari AND p_sampai
    ORDER BY tanggal;
END$$

CREATE PROCEDURE sp_laporan_total(IN p_id_keluarga INT UNSIGNED, IN p_dari DATE, IN p_sampai DATE)
BEGIN
    SELECT jenis, SUM(jumlah) AS total
    FROM v_transaksi
    WHERE id_keluarga = p_id_keluarga AND tanggal BETWEEN p_dari AND p_sampai
    GROUP BY jenis;
END$$

CREATE PROCEDURE sp_laporan_kategori(IN p_id_keluarga INT UNSIGNED, IN p_dari DATE, IN p_sampai DATE)
BEGIN
    SELECT jenis, nama_kategori, SUM(jumlah) AS total
    FROM v_transaksi
    WHERE id_keluarga = p_id_keluarga AND tanggal BETWEEN p_dari AND p_sampai
    GROUP BY jenis, nama_kategori
    ORDER BY jenis, total DESC;
END$$

CREATE PROCEDURE sp_admin_pengguna()
BEGIN
    SELECT u.*, k.nama_keluarga
    FROM pengguna u
    LEFT JOIN keluarga k ON k.id_keluarga = u.id_keluarga
    ORDER BY u.created_at DESC;
END$$

CREATE PROCEDURE sp_admin_status_pengguna(IN p_id INT UNSIGNED, IN p_status VARCHAR(10))
BEGIN
    UPDATE pengguna SET status = p_status WHERE id_pengguna = p_id;
END$$

CREATE PROCEDURE sp_kategori_daftar()
BEGIN
    SELECT * FROM kategori ORDER BY jenis, nama_kategori;
END$$

CREATE PROCEDURE sp_kategori_tambah(IN p_nama VARCHAR(60), IN p_jenis VARCHAR(12))
BEGIN
    INSERT INTO kategori (nama_kategori, jenis) VALUES (p_nama, p_jenis);
END$$

CREATE PROCEDURE sp_kategori_ubah(IN p_id INT UNSIGNED, IN p_nama VARCHAR(60), IN p_jenis VARCHAR(12))
BEGIN
    UPDATE kategori SET nama_kategori = p_nama, jenis = p_jenis WHERE id_kategori = p_id;
END$$

CREATE PROCEDURE sp_kategori_hapus(IN p_id INT UNSIGNED)
BEGIN
    DELETE FROM kategori WHERE id_kategori = p_id;
END$$

CREATE PROCEDURE sp_profil(IN p_id INT UNSIGNED)
BEGIN
    SELECT u.nama_lengkap, u.email, u.role, k.nama_keluarga, k.alamat
    FROM pengguna u
    LEFT JOIN keluarga k ON k.id_keluarga = u.id_keluarga
    WHERE u.id_pengguna = p_id;
END$$

CREATE PROCEDURE sp_profil_ubah(IN p_id INT UNSIGNED, IN p_nama VARCHAR(100), IN p_email VARCHAR(100))
BEGIN
    UPDATE pengguna SET nama_lengkap = p_nama, email = p_email WHERE id_pengguna = p_id;
END$$

CREATE PROCEDURE sp_password_ubah(IN p_id INT UNSIGNED, IN p_password VARCHAR(255))
BEGIN
    UPDATE pengguna SET password = p_password WHERE id_pengguna = p_id;
END$$

CREATE PROCEDURE sp_keluarga_ubah(IN p_id INT UNSIGNED, IN p_nama VARCHAR(100), IN p_alamat VARCHAR(255))
BEGIN
    UPDATE keluarga SET nama_keluarga = p_nama, alamat = p_alamat WHERE id_keluarga = p_id;
END$$

DELIMITER ;