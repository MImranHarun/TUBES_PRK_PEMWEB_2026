<?php

require_once __DIR__ . '/../config/database.php';

if (!isset($conn) || !($conn instanceof mysqli)) {
    die("Koneksi database tidak tersedia!");
}

$name = "Admin FEBMart";
$username = "adminfebmart";
$email = "admin@febmart.com";
$password = password_hash("adminfebmart", PASSWORD_DEFAULT);
$role = "admin";

$check = $conn->prepare("SELECT id FROM users WHERE username = ?");

if (!$check) {
    die("Gagal memeriksa username: " . $conn->error);
}

$check->bind_param("s", $username);
$check->execute();

$result = $check->get_result();

if ($result->num_rows > 0) {
    die("Akun Admin FEBMart sudah tersedia!");
}

// Masukkan akun admin
$query = "INSERT INTO users (name, username, email, password, role)
          VALUES (?, ?, ?, ?, ?)";

$stmt = $conn->prepare($query);

if (!$stmt) {
    die("Gagal menyiapkan query: " . $conn->error);
}

$stmt->bind_param(
    "sssss",
    $name,
    $username,
    $email,
    $password,
    $role
);

if ($stmt->execute()) {
    echo "Akun Admin FEBMart berhasil dibuat!";
} else {
    echo "Gagal membuat akun: " . $stmt->error;
}

$stmt->close();
$check->close();
$conn->close();

?>
