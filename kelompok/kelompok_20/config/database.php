<?php

$db_host = 'localhost';
$db_name = 'db_aspiraku';
$db_user = 'root';
$db_pass = '';
$db_charset = 'utf8mb4';

$dsn = "mysql:host=$db_host;dbname=$db_name;charset=$db_charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $db_user, $db_pass, $options);
} catch (PDOException $e) {
    // Sesuai aturan SEC-10: tidak menampilkan raw error kredensial di publik
    die("Koneksi ke database gagal. Pastikan database 'db_aspiraku' sudah dibuat dan layanan MySQL aktif.");
}

