<?php
session_start();

// Memeriksa session login
if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit;
}

// Hanya admin yang boleh mengedit pengguna
if ($_SESSION['role'] !== 'admin') {
    header("Location: dashboard.php");
    exit;
}

// Koneksi database
require_once __DIR__ . '/config/database.php';

// Pastikan data dikirim menggunakan POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: users.php");
    exit;
}

// Mengambil data dari form
$id = (int) ($_POST['id'] ?? 0);
$name = trim($_POST['name'] ?? '');
$username = trim($_POST['username'] ?? '');
$email = trim($_POST['email'] ?? '');
$role = $_POST['role'] ?? '';

// Validasi input
if ($id <= 0 || $name === '' || $username === '' ||
    !in_array($role, ['admin', 'kasir'], true)) {
    die("Data pengguna tidak valid!");
}

// Validasi email jika diisi
if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    die("Format email tidak valid!");
}

// Memeriksa apakah username digunakan pengguna lain
$check = $conn->prepare(
    "SELECT id FROM users WHERE username = ? AND id != ?"
);
$check->bind_param("si", $username, $id);
$check->execute();

if ($check->get_result()->num_rows > 0) {
    die("Username sudah digunakan oleh pengguna lain!");
}
$check->close();

// Memeriksa apakah email digunakan pengguna lain
if ($email !== '') {
    $check = $conn->prepare(
        "SELECT id FROM users WHERE email = ? AND id != ?"
    );
    $check->bind_param("si", $email, $id);
    $check->execute();

    if ($check->get_result()->num_rows > 0) {
        die("Email sudah digunakan oleh pengguna lain!");
    }
    $check->close();
}

// Memperbarui data pengguna
$query = "UPDATE users
          SET name = ?, username = ?, email = ?, role = ?
          WHERE id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param(
    "ssssi",
    $name,
    $username,
    $email,
    $role,
    $id
);

if ($stmt->execute()) {
    header("Location: users.php?status=edit_berhasil");
    exit;
} else {
    die("Gagal memperbarui pengguna: " . $stmt->error);
}

$stmt->close();
$conn->close();
?>