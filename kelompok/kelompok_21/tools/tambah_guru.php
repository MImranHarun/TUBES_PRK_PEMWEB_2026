<?php
// Hanya dijalankan lewat terminal, bukan dari browser.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__ . '/../config/database.php';
echo "Tambah akun guru (password tersimpan sebagai hash).\n";
$nama = trim(readline('Nama: '));
$nip = trim(readline('NIP: '));
$email = trim(readline('Email: '));
$role = trim(readline('Peran [guru/admin]: '));
$password = readline('Password awal (terlihat di terminal, 8-72 karakter): ');
if (!$nama || strlen($nama) > 100 || !$nip || strlen($nip) > 50 || strlen($email) > 100
    || !filter_var($email, FILTER_VALIDATE_EMAIL) || !in_array($role, ['guru', 'admin'])
    || strlen($password) < 8 || strlen($password) > 72) {
    exit("Isian tidak valid.\n");
}
try {
    $stmt = $pdo->prepare('INSERT INTO GURU (nip, nama_guru, email, password, peran) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([$nip, $nama, $email, password_hash($password, PASSWORD_DEFAULT), $role]);
    echo "Akun berhasil dibuat.\n";
} catch (PDOException $error) {
    echo "Gagal menyimpan akun. Periksa duplikasi NIP/email atau koneksi database.\n";
}
