<?php

if (!isset($pageTitle)) {
    $pageTitle = 'Portal Pengaduan & Aspirasi';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= sanitize($pageTitle) ?> - Aspiraku</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <header class="site-header">
        <nav class="navbar container">
            <a href="/index.php" class="navbar-brand">
                <span class="brand-name">Aspiraku</span>
                <span class="brand-sub">FISIP</span>
            </a>

            <div class="navbar-menu">
                <a href="/index.php" class="nav-link">Beranda</a>
                <a href="/laporan_publik.php" class="nav-link">Laporan Publik</a>

                <?php if (isLogin()): ?>
                    <?php if (isAdmin()): ?>
                        <a href="/admin/dashboard.php" class="nav-link">Dashboard</a>
                        <a href="/admin/kelola.php" class="nav-link">Kelola Laporan</a>
                        <a href="/admin/kategori.php" class="nav-link">Kategori</a>
                    <?php else: ?>
                        <a href="/user/kirim.php" class="nav-link">Sampaikan Laporan</a>
                        <a href="/user/laporan_saya.php" class="nav-link">Laporan Saya</a>
                        <a href="/profil.php" class="nav-link">Profil</a>
                    <?php endif; ?>

                    <span class="user-greeting">Halo, <strong><?= sanitize($_SESSION['nama'] ?? 'Pengguna') ?></strong></span>
                    <a href="/logout.php" class="nav-link nav-btn-logout">Keluar</a>
                <?php else: ?>
                    <a href="/login.php" class="nav-link">Masuk</a>
                    <a href="/register.php" class="nav-link nav-btn-daftar">Daftar</a>
                <?php endif; ?>
            </div>
        </nav>
    </header>

    <main class="container main-content">
        <?php $flash = getFlash(); ?>
        <?php if ($flash): ?>
            <div class="alert alert-<?= sanitize($flash['tipe']) ?>">
                <?= sanitize($flash['pesan']) ?>
            </div>
        <?php endif; ?>

