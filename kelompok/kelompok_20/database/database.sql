CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('user', 'admin') NOT NULL DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

CREATE TABLE IF NOT EXISTS kategori (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_kategori VARCHAR(100) NOT NULL UNIQUE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

CREATE TABLE IF NOT EXISTS laporan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    kategori_id INT NULL,
    jenis ENUM('pengaduan', 'aspirasi') NOT NULL,
    judul VARCHAR(255) NOT NULL,
    isi TEXT NOT NULL,
    lokasi VARCHAR(255) NOT NULL,
    foto VARCHAR(255) NULL,
    is_public TINYINT(1) NOT NULL DEFAULT 1,
    status ENUM(
        'menunggu',
        'diproses',
        'selesai'
    ) NOT NULL DEFAULT 'menunggu',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_laporan_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_laporan_kategori FOREIGN KEY (kategori_id) REFERENCES kategori (id) ON DELETE SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

CREATE TABLE IF NOT EXISTS tanggapan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    laporan_id INT NOT NULL,
    admin_id INT NOT NULL,
    isi_tanggapan TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_tanggapan_laporan FOREIGN KEY (laporan_id) REFERENCES laporan (id) ON DELETE CASCADE,
    CONSTRAINT fk_tanggapan_admin FOREIGN KEY (admin_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

-- Data awal kategori (5 kategori bawaan)
INSERT IGNORE INTO
    kategori (nama_kategori)
VALUES ('Fasilitas'),
    ('Akademik'),
    ('Keamanan'),
    ('Kebersihan'),
    ('Layanan Administrasi');