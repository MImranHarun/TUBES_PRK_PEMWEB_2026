<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit;
}

if ($_SESSION['role'] !== 'admin') {
    header("Location: dashboard.php");
    exit;
}

require_once __DIR__ . '/config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: users.php");
    exit;
}

$id = (int) ($_POST['id'] ?? 0);

if ($id <= 0) {
    header("Location: users.php");
    exit;
}

if ($id === (int) $_SESSION['user_id']) {
    header("Location: users.php?status=gagal_hapus");
    exit;
}

$query = "DELETE FROM users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id);

if ($stmt->execute() && $stmt->affected_rows > 0) {
    header("Location: users.php?status=hapus_berhasil");
    exit;
} else {
    header("Location: users.php?status=gagal_hapus");
    exit;
}

$stmt->close();
$conn->close();
?>
