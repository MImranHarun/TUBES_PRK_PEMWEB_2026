<?php
// kelompok/kelompok_12/admin/dashboard.php
require_once '../includes/auth.php';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Admin</title>
</head>
<body>
    <h1>Selamat Datang, <?= htmlspecialchars($_SESSION['admin_username']) ?>!</h1>
    <p>Ini adalah halaman khusus admin yang dilindungi auth.php.</p>
    <a href="logout.php">Logout</a>
</body>
</html>