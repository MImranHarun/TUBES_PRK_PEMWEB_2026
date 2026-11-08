-- 1. Buat Database dan Gunakan Database
CREATE DATABASE IF NOT EXISTS `sehatin`;
USE `sehatin`;

-- 2. Hapus Tabel Jika Sudah Ada (Order dari child ke parent untuk menghindari error foreign key)
DROP TABLE IF EXISTS `articles`;
DROP TABLE IF EXISTS `tips`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `admins`;

-- 3. Tabel admins
CREATE TABLE `admins` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(100) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Tabel categories
CREATE TABLE `categories` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `description` TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Tabel articles
CREATE TABLE `articles` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `category_id` INT NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `content` TEXT NOT NULL,
    `image` VARCHAR(255) DEFAULT NULL,
    `author` VARCHAR(100) DEFAULT 'Admin',
    `views` INT DEFAULT 0,
    `status` ENUM('draft', 'published', 'archived') DEFAULT 'draft',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_articles_categories` 
        FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) 
        ON DELETE CASCADE 
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. Tabel tips
CREATE TABLE `tips` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT NOT NULL,
    `image` VARCHAR(255) DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ===================================================
-- DATA AWAL (SEED DATA / CONTOH ISIAN DATABASE)
-- ===================================================

-- Insert Admin awal (password bawaan contoh: admin123)
INSERT INTO `admins` (`username`, `password`) VALUES
('admin', 'admin123');

-- Insert Kategori Artikel
INSERT INTO `categories` (`name`, `description`) VALUES
('Gizi & Nutrisi', 'Artikel mengenai asupan makanan sehat dan gizi seimbang.'),
('Kesehatan Mental', 'Seputar manajemen stres, kebiasaan positif, dan kesejahteraan mental.'),
('Olahraga & Kebugaran', 'Tips dan panduan aktivitas fisik harian.');

-- Insert Contoh Artikel
INSERT INTO `articles` (`category_id`, `title`, `content`, `image`, `author`, `views`, `status`) VALUES
(1, 'Manfaat Minum Air Putih 8 Gelas Sehari', 'Air putih sangat penting untuk menjaga hidrasi tubuh, fungsi ginjal, dan konsentrasi harian...', 'air_putih.jpg', 'Admin', 15, 'published'),
(2, 'Cara Mengatasi Stres Saat Kuliah & Kerja', 'Stres merupakan hal wajar, namun bisa dikendalikan dengan teknik pernapasan dan tirat dari layar...', 'stres.jpg', 'Admin', 42, 'published');

-- Insert Contoh Tips Sehat
INSERT INTO `tips` (`title`, `description`, `image`) VALUES
('Jalan Kaki 30 Menit', 'Jalan kaki ringan setiap pagi membantu meningkatkan sirkulasi darah dan mood.', 'jalan_pagi.jpg'),
('Tidur Cukup 7-8 Jam', 'Hindari begadang agar sistem imun dan pemulihan sel tubuh tetap optimal.', 'tidur_sehat.jpg');