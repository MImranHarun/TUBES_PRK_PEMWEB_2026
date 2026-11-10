-- 1. Buat dan gunakan database
CREATE DATABASE IF NOT EXISTS kabarsi;
USE kabarsi;

-- 2. Buat tabel users
CREATE TABLE `users` (
  `id_user` INT(11) NOT NULL AUTO_INCREMENT,
  `nama_lengkap` VARCHAR(100) NOT NULL,
  `npm` VARCHAR(20) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('admin', 'mahasiswa') NOT NULL,
  PRIMARY KEY (`id_user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Buat tabel kategori
CREATE TABLE `kategori` (
  `id_kategori` INT(11) NOT NULL AUTO_INCREMENT,
  `nama_kategori` VARCHAR(50) NOT NULL,
  PRIMARY KEY (`id_kategori`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Buat tabel berita
CREATE TABLE `berita` (
  `id_berita` INT(11) NOT NULL AUTO_INCREMENT,
  `id_user` INT(11) NOT NULL,
  `id_kategori` INT(11) NOT NULL,
  `judul_berita` VARCHAR(255) NOT NULL,
  `isi_berita` TEXT NOT NULL,
  `gambar_url` VARCHAR(255) DEFAULT NULL,
  `tanggal_publikasi` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_berita`),
  FOREIGN KEY (`id_user`) REFERENCES `users`(`id_user`) ON DELETE CASCADE,
  FOREIGN KEY (`id_kategori`) REFERENCES `kategori`(`id_kategori`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Buat tabel aspirasi
CREATE TABLE `aspirasi` (
  `id_aspirasi` INT(11) NOT NULL AUTO_INCREMENT,
  `id_user` INT(11) NOT NULL,
  `id_kategori` INT(11) NOT NULL,
  `judul_aspirasi` VARCHAR(255) NOT NULL,
  `isi_aspirasi` TEXT NOT NULL,
  `tanggal_kirim` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status` ENUM('menunggu', 'ditinjau', 'selesai') NOT NULL DEFAULT 'menunggu',
  `is_anonim` TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id_aspirasi`),
  FOREIGN KEY (`id_user`) REFERENCES `users`(`id_user`) ON DELETE CASCADE,
  FOREIGN KEY (`id_kategori`) REFERENCES `kategori`(`id_kategori`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. Buat tabel komentar
CREATE TABLE `komentar` (
  `id_komentar` INT(11) NOT NULL AUTO_INCREMENT,
  `id_user` INT(11) NOT NULL,
  `id_berita` INT(11) DEFAULT NULL,
  `id_aspirasi` INT(11) DEFAULT NULL,
  `isi_komentar` TEXT NOT NULL,
  `tanggal_komentar` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `is_anonim` TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id_komentar`),
  FOREIGN KEY (`id_user`) REFERENCES `users`(`id_user`) ON DELETE CASCADE,
  FOREIGN KEY (`id_berita`) REFERENCES `berita`(`id_berita`) ON DELETE CASCADE,
  FOREIGN KEY (`id_aspirasi`) REFERENCES `aspirasi`(`id_aspirasi`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. Insert Dummy Data Awal
INSERT INTO `users` (`nama_lengkap`, `npm`, `password`, `role`) VALUES
('Admin Utama Kampus', 'admin123', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
('Andi Mahasiswa', '1234567890', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'mahasiswa');

INSERT INTO `kategori` (`nama_kategori`) VALUES
('Akademik'), ('Fasilitas Kampus'), ('Isu Sosial');