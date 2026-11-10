<?php
$host = 'localhost';
$dbname = 'sehatin';
$username = 'root';
$password = '';

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password
    );

    $pdo->setAttribute(
        PDO::ATTR_ERRMODE,
        PDO::ERRMODE_EXCEPTION
    );

    // Tambahkan baris ini untuk pengujian sementara:
    //echo "Koneksi database berhasil!";

} catch (PDOException $e) {
    die("Koneksi database gagal: " . $e->getMessage());
}