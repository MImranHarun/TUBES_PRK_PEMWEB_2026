<?php

require_once __DIR__ . '/../config/auth_check.php';

$pageTitle = $pageTitle ?? 'OneBlood - Sistem Informasi Donor Darah';
$userLogin = currentUser();
$flashMessage = getFlash();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?></title>
    <link rel="stylesheet" href="<?= base_url('css/style.css') ?>">
</head>
<body>
    <header>
        <div class="header-title">
            <img class="logo" src="<?= e(base_url('assets/blood.svg')) ?>" alt="OneBlood Logo">
            <div class="header-text">
                <p class="title">OneBlood</p>
                <p class="subtitle">
                        Sistem Donor Darah Terpadu
                </p>  
            </div>
        </div>
        <nav>
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="<?= base_url('index.php') ?>" class="nav-link">Beranda</a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link">Donor Darah</a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link">Stok Darah</a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link">Tentang Kami</a>
                </li>
                <li class="nav-item">
                    <a href="<?= base_url('auth/login.php') ?>" class="nav-link">Login</a>
                </li>
                <li class="nav-item">
                    <a href="<?= base_url('auth/register.php') ?>" class="nav-link">Daftar</a>
                </li>
            </ul>
        </nav>
    </header>

<?php if ($flashMessage !== null): ?>
        <div class="container flash-wrapper no-print">
            <div class="alert alert-<?= e($flashMessage['type']) ?>">
                <span><?= e($flashMessage['message']) ?></span>
                <button type="button" class="alert-close" onclick="this.parentElement.remove();">&times;</button>
            </div>
        </div>
<?php endif; ?>