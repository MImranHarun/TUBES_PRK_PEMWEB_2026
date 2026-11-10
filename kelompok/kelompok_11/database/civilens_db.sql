CREATE TABLE `admin` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE `petisi` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `judul` VARCHAR(255) NOT NULL,
  `deskripsi` TEXT NOT NULL,
  `target_tanda_tangan` INT NOT NULL,
  `current_tanda_tangan` INT DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE `aspirasi` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nama_pengirim` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL,
  `pesan_aspirasi` TEXT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE `lapor_hoaks` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `judul_isu` VARCHAR(255) NOT NULL,
  `link_sumber` VARCHAR(255) NOT NULL,
  `keterangan` TEXT NOT NULL,
  `status` ENUM('pending', 'fakta', 'hoaks') DEFAULT 'pending',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);