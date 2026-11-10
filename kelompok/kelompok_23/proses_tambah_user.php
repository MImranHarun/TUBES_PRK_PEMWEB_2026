<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit;
}

if ($_SESSION['role'] !== 'admin') {
    header("Location: users.php");
    exit;
}

require_once __DIR__ . '/config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: tambah_user.php");
    exit;
}

$name = trim($_POST['name'] ?? '');
$username = trim($_POST['username'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$role = $_POST['role'] ?? '';

if ($name === '' || $username === '' || $password === '' ||
    !in_array($role, ['admin', 'kasir'], true)) {
    die("Semua data wajib diisi dengan benar!");
}

if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    die("Format email tidak valid!");
}

$check = $conn->prepare("SELECT id FROM users WHERE username = ?");
$check->bind_param("s", $username);
$check->execute();

if ($check->get_result()->num_rows > 0) {
    die("Username sudah digunakan! Silakan gunakan username lain.");
}
$check->close();

if ($email !== '') {
    $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $check->bind_param("s", $email);
    $check->execute();

    if ($check->get_result()->num_rows > 0) {
        die("Email sudah digunakan! Silakan gunakan email lain.");
    }
    $check->close();
}

$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Proses upload foto jika ada
$photoName = null;
if (isset($_FILES['photo']) && $_FILES['photo']['error'] !== UPLOAD_ERR_NO_FILE) {
    if ($_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
        die("Upload foto gagal. Silakan coba lagi.");
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($_FILES['photo']['tmp_name']);
    $allowedTypes = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp'
    ];

    if (!isset($allowedTypes[$mime])) {
        die("Format foto harus JPG, PNG, atau WebP!");
    }

    if ($_FILES['photo']['size'] > 2 * 1024 * 1024) {
        die("Ukuran foto maksimal 2 MB!");
    }

    $photoName = bin2hex(random_bytes(16)) . '.' . $allowedTypes[$mime];
    $uploadPath = __DIR__ . '/assets/img/' . $photoName;

    if (!move_uploaded_file($_FILES['photo']['tmp_name'], $uploadPath)) {
        die("Gagal menyimpan foto profil.");
    }
}

$query = "INSERT INTO users (name, username, email, password, role, photo)
          VALUES (?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($query);
$stmt->bind_param(
    "ssssss",
    $name,
    $username,
    $email,
    $hashed_password,
    $role,
    $photoName
);

if ($stmt->execute()) {
    header("Location: users.php?status=berhasil");
    exit;
} else {
    if (!empty($photoName)) {
        $failedPath = __DIR__ . '/assets/img/' . basename($photoName);
        if (is_file($failedPath)) {
            unlink($failedPath);
        }
    }
    die("Gagal menambahkan pengguna: " . $stmt->error);
}

$stmt->close();
$conn->close();
?>