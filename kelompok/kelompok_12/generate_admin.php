<?php
// kelompok/kelompok_12/generate_admin.php
require_once 'config/database.php';

$username = 'admin';
$password_plain = 'admin123'; // Password yang ingin digunakan

// Hash password dengan algoritma BCRYPT bawaan PHP
$password_hashed = password_hash($password_plain, PASSWORD_DEFAULT);

try {
    $stmt = $pdo->prepare("INSERT INTO admins (username, password) VALUES (:username, :password)");
    $stmt->execute([
        ':username' => $username,
        ':password' => $password_hashed
    ]);
    echo "Akun admin berhasil dibuat!<br>";
    echo "Username: <b>$username</b><br>";
    echo "Password: <b>$password_plain</b>";
} catch (PDOException $e) {
    echo "Gagal membuat admin (mungkin username sudah ada): " . $e->getMessage();
}