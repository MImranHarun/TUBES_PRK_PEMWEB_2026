CREATE DATABASE IF NOT EXISTS febmart
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE febmart;

CREATE TABLE IF NOT EXISTS users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'kasir') NOT NULL DEFAULT 'kasir',
    photo VARCHAR(255) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

SET @users_photo_exists = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'users'
      AND COLUMN_NAME = 'photo'
);
SET @users_photo_migration = IF(
    @users_photo_exists = 0,
    'ALTER TABLE users ADD COLUMN photo VARCHAR(255) NULL AFTER password',
    'SELECT 1'
);
PREPARE users_photo_statement FROM @users_photo_migration;
EXECUTE users_photo_statement;
DEALLOCATE PREPARE users_photo_statement;

INSERT IGNORE INTO users
(id, name, username, email, password, role, photo, is_active)
VALUES
(1, 'Admin FEBMart', 'adminfebmart', 'admin@febmart.com',
'$2y$10$p8YODzPHYBk6BduqZQvqQuohOUDvsXcLKjXlBcD.h2u18JxAyfMhq',
'admin', NULL, 1),
(2, 'kasir percobaan', 'kasir01', 'kasir1febmart@email.com',
'$2y$10$Z1T4unI7lKiF3qz1EylwDuv3X4NMvdJVdeKzaO6KURVFphFIoOgSW',
'kasir', NULL, 1);

CREATE TABLE IF NOT EXISTS categories (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS products (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id BIGINT UNSIGNED NOT NULL,
    sku VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(150) NOT NULL,
    description TEXT NULL,
    price DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    stock INT UNSIGNED NOT NULL DEFAULT 0,
    min_stock INT UNSIGNED NOT NULL DEFAULT 5,
    image VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_products_category
        FOREIGN KEY (category_id) REFERENCES categories(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    INDEX idx_products_name (name),
    INDEX idx_products_stock (stock)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS transactions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    invoice_number VARCHAR(40) NOT NULL UNIQUE,
    user_id BIGINT UNSIGNED NOT NULL,
    transaction_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    subtotal DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    total DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    amount_paid DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    change_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    payment_method ENUM('cash', 'transfer', 'qris') NOT NULL DEFAULT 'cash',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_transactions_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    INDEX idx_transactions_date (transaction_date),
    INDEX idx_transactions_user (user_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS transaction_details (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    transaction_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NULL,
    product_name VARCHAR(150) NOT NULL,
    quantity INT UNSIGNED NOT NULL,
    unit_price DECIMAL(12,2) NOT NULL,
    subtotal DECIMAL(12,2) NOT NULL,
    CONSTRAINT fk_details_transaction
        FOREIGN KEY (transaction_id) REFERENCES transactions(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_details_product
        FOREIGN KEY (product_id) REFERENCES products(id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    INDEX idx_details_transaction (transaction_id),
    INDEX idx_details_product (product_id)
) ENGINE=InnoDB;

START TRANSACTION;

INSERT INTO categories (name, description)
VALUES
    ('Makanan Ringan', 'Camilan dan makanan siap konsumsi.'),
    ('Minuman', 'Minuman kemasan dan minuman instan.'),
    ('Bahan Pokok', 'Bahan pangan pokok untuk kebutuhan sehari-hari.'),
    ('Perawatan Diri', 'Produk kebersihan dan perawatan pribadi.'),
    ('Kebersihan Rumah', 'Produk kebersihan untuk rumah tangga.'),
    ('Alat Tulis', 'Perlengkapan tulis dan kebutuhan sekolah.'),
    ('Produk Beku', 'Makanan beku siap diolah.')
ON DUPLICATE KEY UPDATE description = VALUES(description);

INSERT INTO products
    (category_id, sku, name, description, price, stock, min_stock, image)
SELECT categories.id, product_seed.sku, product_seed.name,
       product_seed.description, product_seed.price, product_seed.stock,
       product_seed.min_stock, NULL
FROM (
    SELECT 'FBM-SEED-001' AS sku, 'Makanan Ringan' AS category_name, 'Keripik Singkong' AS name, 'Keripik singkong rasa original 150 g.' AS description, 12000.00 AS price, 35 AS stock, 5 AS min_stock
    UNION ALL SELECT 'FBM-SEED-002', 'Makanan Ringan', 'Biskuit Cokelat', 'Biskuit cokelat kemasan 200 g.', 9500.00, 28, 5
    UNION ALL SELECT 'FBM-SEED-003', 'Makanan Ringan', 'Wafer Vanila', 'Wafer vanila kemasan 180 g.', 7500.00, 32, 5
    UNION ALL SELECT 'FBM-SEED-004', 'Makanan Ringan', 'Roti Tawar', 'Roti tawar putih isi 10 lembar.', 18000.00, 18, 4
    UNION ALL SELECT 'FBM-SEED-005', 'Minuman', 'Air Mineral 600 ml', 'Air mineral botol 600 ml.', 4000.00, 60, 10
    UNION ALL SELECT 'FBM-SEED-006', 'Minuman', 'Teh Botol 350 ml', 'Teh siap minum botol 350 ml.', 6500.00, 40, 8
    UNION ALL SELECT 'FBM-SEED-007', 'Minuman', 'Susu UHT 1 L', 'Susu UHT plain kemasan 1 liter.', 8500.00, 24, 5
    UNION ALL SELECT 'FBM-SEED-008', 'Minuman', 'Kopi Instan', 'Kopi instan sachet isi 10.', 12000.00, 30, 5
    UNION ALL SELECT 'FBM-SEED-009', 'Bahan Pokok', 'Beras 5 kg', 'Beras putih kemasan 5 kg.', 72000.00, 20, 5
    UNION ALL SELECT 'FBM-SEED-010', 'Bahan Pokok', 'Minyak Goreng 1 L', 'Minyak goreng kemasan 1 liter.', 18500.00, 26, 5
    UNION ALL SELECT 'FBM-SEED-011', 'Bahan Pokok', 'Gula Pasir 1 kg', 'Gula pasir kemasan 1 kg.', 17000.00, 22, 5
    UNION ALL SELECT 'FBM-SEED-012', 'Bahan Pokok', 'Telur Ayam 1 kg', 'Telur ayam negeri per kilogram.', 30000.00, 16, 5
    UNION ALL SELECT 'FBM-SEED-013', 'Perawatan Diri', 'Sabun Mandi', 'Sabun mandi batang 90 g.', 6000.00, 40, 8
    UNION ALL SELECT 'FBM-SEED-014', 'Perawatan Diri', 'Sampo 170 ml', 'Sampo perawatan rambut kemasan 170 ml.', 23000.00, 20, 5
    UNION ALL SELECT 'FBM-SEED-015', 'Perawatan Diri', 'Pasta Gigi 120 g', 'Pasta gigi keluarga kemasan 120 g.', 15000.00, 24, 5
    UNION ALL SELECT 'FBM-SEED-016', 'Perawatan Diri', 'Sikat Gigi', 'Sikat gigi dewasa bulu lembut.', 9000.00, 25, 5
    UNION ALL SELECT 'FBM-SEED-017', 'Kebersihan Rumah', 'Detergen 1 kg', 'Detergen bubuk kemasan 1 kg.', 22000.00, 18, 5
    UNION ALL SELECT 'FBM-SEED-018', 'Kebersihan Rumah', 'Sabun Cuci Piring 700 ml', 'Sabun cuci piring cair 700 ml.', 12000.00, 20, 5
    UNION ALL SELECT 'FBM-SEED-019', 'Kebersihan Rumah', 'Pembersih Lantai 800 ml', 'Pembersih lantai cair 800 ml.', 16000.00, 15, 4
    UNION ALL SELECT 'FBM-SEED-020', 'Alat Tulis', 'Buku Tulis', 'Buku tulis 40 lembar.', 6000.00, 50, 10
    UNION ALL SELECT 'FBM-SEED-021', 'Alat Tulis', 'Pulpen', 'Pulpen tinta hitam.', 3500.00, 60, 10
    UNION ALL SELECT 'FBM-SEED-022', 'Alat Tulis', 'Pensil', 'Pensil kayu HB.', 2500.00, 45, 10
    UNION ALL SELECT 'FBM-SEED-023', 'Produk Beku', 'Nugget Ayam 500 g', 'Nugget ayam beku kemasan 500 g.', 42000.00, 14, 4
    UNION ALL SELECT 'FBM-SEED-024', 'Produk Beku', 'Sosis Sapi 500 g', 'Sosis sapi beku kemasan 500 g.', 26000.00, 16, 4
    UNION ALL SELECT 'FBM-SEED-025', 'Produk Beku', 'Kentang Goreng 500 g', 'Kentang goreng beku kemasan 500 g.', 32000.00, 12, 4
) AS product_seed
INNER JOIN categories ON categories.name = product_seed.category_name
ON DUPLICATE KEY UPDATE
    category_id = VALUES(category_id),
    name = VALUES(name),
    description = VALUES(description),
    price = VALUES(price),
    stock = VALUES(stock),
    min_stock = VALUES(min_stock),
    image = VALUES(image);

INSERT INTO transactions
    (invoice_number, user_id, transaction_date, subtotal, total,
     amount_paid, change_amount, payment_method)
SELECT transaction_seed.invoice_number, users.id, transaction_seed.transaction_date,
       transaction_seed.subtotal, transaction_seed.total,
       transaction_seed.amount_paid, transaction_seed.change_amount,
       transaction_seed.payment_method
FROM (
    SELECT 'SEED-FEB-20261001-001' AS invoice_number, '2026-10-01 09:15:00' AS transaction_date, 211000.00 AS subtotal, 211000.00 AS total, 250000.00 AS amount_paid, 39000.00 AS change_amount, 'cash' AS payment_method
    UNION ALL SELECT 'SEED-FEB-20261002-002', '2026-10-02 10:30:00', 47000.00, 47000.00, 50000.00, 3000.00, 'qris'
    UNION ALL SELECT 'SEED-FEB-20261003-003', '2026-10-03 12:05:00', 50000.00, 50000.00, 60000.00, 10000.00, 'cash'
    UNION ALL SELECT 'SEED-FEB-20261004-004', '2026-10-04 13:20:00', 30000.00, 30000.00, 50000.00, 20000.00, 'transfer'
    UNION ALL SELECT 'SEED-FEB-20261005-005', '2026-10-05 15:45:00', 94000.00, 94000.00, 100000.00, 6000.00, 'cash'
    UNION ALL SELECT 'SEED-FEB-20261006-006', '2026-10-06 11:10:00', 62000.00, 62000.00, 100000.00, 38000.00, 'qris'
    UNION ALL SELECT 'SEED-FEB-20261007-007', '2026-10-07 16:00:00', 61000.00, 61000.00, 70000.00, 9000.00, 'cash'
    UNION ALL SELECT 'SEED-FEB-20261008-008', '2026-10-08 09:40:00', 106000.00, 106000.00, 150000.00, 44000.00, 'transfer'
) AS transaction_seed
INNER JOIN users ON users.username = 'kasir01'
ON DUPLICATE KEY UPDATE
    user_id = VALUES(user_id),
    transaction_date = VALUES(transaction_date),
    subtotal = VALUES(subtotal),
    total = VALUES(total),
    amount_paid = VALUES(amount_paid),
    change_amount = VALUES(change_amount),
    payment_method = VALUES(payment_method);

DELETE transaction_details
FROM transaction_details
INNER JOIN transactions ON transactions.id = transaction_details.transaction_id
WHERE transactions.invoice_number LIKE 'SEED-FEB-%';

INSERT INTO transaction_details
    (transaction_id, product_id, product_name, quantity, unit_price, subtotal)
SELECT transactions.id, products.id, products.name,
       detail_seed.quantity, products.price,
       products.price * detail_seed.quantity
FROM (
    SELECT 'SEED-FEB-20261001-001' AS invoice_number, 'FBM-SEED-009' AS sku, 2 AS quantity
    UNION ALL SELECT 'SEED-FEB-20261001-001', 'FBM-SEED-010', 2
    UNION ALL SELECT 'SEED-FEB-20261001-001', 'FBM-SEED-012', 1
    UNION ALL SELECT 'SEED-FEB-20261002-002', 'FBM-SEED-004', 1
    UNION ALL SELECT 'SEED-FEB-20261002-002', 'FBM-SEED-007', 2
    UNION ALL SELECT 'SEED-FEB-20261002-002', 'FBM-SEED-005', 3
    UNION ALL SELECT 'SEED-FEB-20261003-003', 'FBM-SEED-013', 2
    UNION ALL SELECT 'SEED-FEB-20261003-003', 'FBM-SEED-014', 1
    UNION ALL SELECT 'SEED-FEB-20261003-003', 'FBM-SEED-015', 1
    UNION ALL SELECT 'SEED-FEB-20261004-004', 'FBM-SEED-020', 3
    UNION ALL SELECT 'SEED-FEB-20261004-004', 'FBM-SEED-021', 2
    UNION ALL SELECT 'SEED-FEB-20261004-004', 'FBM-SEED-022', 2
    UNION ALL SELECT 'SEED-FEB-20261005-005', 'FBM-SEED-023', 1
    UNION ALL SELECT 'SEED-FEB-20261005-005', 'FBM-SEED-024', 2
    UNION ALL SELECT 'SEED-FEB-20261006-006', 'FBM-SEED-017', 1
    UNION ALL SELECT 'SEED-FEB-20261006-006', 'FBM-SEED-018', 2
    UNION ALL SELECT 'SEED-FEB-20261006-006', 'FBM-SEED-019', 1
    UNION ALL SELECT 'SEED-FEB-20261007-007', 'FBM-SEED-003', 4
    UNION ALL SELECT 'SEED-FEB-20261007-007', 'FBM-SEED-002', 2
    UNION ALL SELECT 'SEED-FEB-20261007-007', 'FBM-SEED-001', 1
    UNION ALL SELECT 'SEED-FEB-20261008-008', 'FBM-SEED-011', 2
    UNION ALL SELECT 'SEED-FEB-20261008-008', 'FBM-SEED-012', 2
    UNION ALL SELECT 'SEED-FEB-20261008-008', 'FBM-SEED-008', 1
) AS detail_seed
INNER JOIN transactions ON transactions.invoice_number = detail_seed.invoice_number
INNER JOIN products ON products.sku = detail_seed.sku;

COMMIT;
