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

// Ambil foto lama
$currentPhoto = null;
$getPhoto = $conn->prepare("SELECT photo FROM users WHERE id = ?");
$getPhoto->bind_param("i", $id);
$getPhoto->execute();
$photoRow = $getPhoto->get_result()->fetch_assoc();
$currentPhoto = $photoRow['photo'] ?? null;
$getPhoto->close();

// Proses upload foto jika ada
$newPhoto = $currentPhoto;
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

    $uploadedPhoto = bin2hex(random_bytes(16)) . '.' . $allowedTypes[$mime];
    $uploadPath = __DIR__ . '/assets/img/' . $uploadedPhoto;

    if (!move_uploaded_file($_FILES['photo']['tmp_name'], $uploadPath)) {
        die("Gagal menyimpan foto profil.");
    }

    $newPhoto = $uploadedPhoto;
}

// Memperbarui data pengguna
$query = "UPDATE users
          SET name = ?, username = ?, email = ?, role = ?, photo = ?
          WHERE id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param(
    "sssssi",
    $name,
    $username,
    $email,
    $role,
    $newPhoto,
    $id
);

if ($stmt->execute()) {
    // Hapus foto lama jika diganti (kecuali aset default)
    if ($newPhoto !== $currentPhoto && !empty($currentPhoto) && !in_array($currentPhoto, ['admin-avatar.svg', 'avatar-default.svg', 'logo.svg'], true)) {
        $oldPath = __DIR__ . '/assets/img/' . basename($currentPhoto);
        if (is_file($oldPath)) {
            unlink($oldPath);
        }
    }
    header("Location: users.php?status=edit_berhasil");
    exit;
} else {
    if ($newPhoto !== $currentPhoto && !empty($newPhoto)) {
        $uploadedPath = __DIR__ . '/assets/img/' . basename($newPhoto);
        if (is_file($uploadedPath)) {
            unlink($uploadedPath);
        }
    }
    die("Gagal memperbarui pengguna: " . $stmt->error);
}

$stmt->close();
$conn->close();
?>