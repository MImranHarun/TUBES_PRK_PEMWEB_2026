<?php
// Konfigurasi Database
$host     = 'localhost'; // Ganti jika host database-mu berbeda
$dbname   = 'kabarsi';   // Nama database sesuai dengan file kabarsi.sql
$username = 'root';      // Username default XAMPP/MAMP
$password = '';          // Password default XAMPP (kosong). Isi 'root' jika pakai MAMP/Mac

try {
    // Membuat koneksi menggunakan PDO
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    
    // Mengatur mode error PDO menjadi Exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Mengatur default fetch mode menjadi Associative Array
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Hilangkan komentar pada baris di bawah ini hanya untuk mengecek apakah koneksi berhasil (saat testing awal)
// echo "Koneksi database berhasil!";

} catch (PDOException $e) {
    // Jika koneksi gagal, tampilkan pesan error dan hentikan script
    die("Koneksi database gagal: " . $e->getMessage());
}
?>