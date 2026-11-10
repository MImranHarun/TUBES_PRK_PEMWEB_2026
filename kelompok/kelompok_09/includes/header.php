<?php

require_once __DIR__ . '/../config/auth_check.php';

$pageTitle = $pageTitle ?? 'OneBlood - Sistem Informasi Donor Darah';
$userLogin = currentUser();
$bodyClass = $bodyClass ?? '';
getFlash();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?></title>
    <link rel="stylesheet" href="<?= e(base_url('css/style.css') . '?v=' . filemtime(__DIR__ . '/../css/style.css')) ?>">
</head>
<body<?= $bodyClass !== '' ? ' class="' . e($bodyClass) . '"' : '' ?>>
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
                <?php if (($userLogin['role'] ?? '') === 'admin'): ?>
                    <li class="nav-item">
                        <a href="<?= e(base_url('index.php')) ?>" class="nav-link">Beranda</a>
                    </li>
                    <li class="nav-item">
                        <a href="<?= e(base_url('admin/users.php')) ?>" class="nav-link">Manajemen</a>
                    </li>
                    <li class="nav-item">
                        <a href="<?= e(base_url('admin/laporan.php')) ?>" class="nav-link">Laporan</a>
                    </li>
                <?php elseif (($userLogin['role'] ?? '') === 'pendonor'): ?>
                    <li class="nav-item">
                        <a href="<?= e(base_url('index.php')) ?>" class="nav-link">Beranda</a>
                    </li>
                    <li class="nav-item">
                        <a href="<?= e(base_url('index.php#stokdarah')) ?>" class="nav-link">StokDarah</a>
                    </li>
                    <li class="nav-item">
                        <a href="<?= e(base_url('index.php#butuhdarah')) ?>" class="nav-link">ButuhDarah</a>
                    </li>
                    <li class="nav-item">
                        <a href="<?= e(base_url('index.php#kelayakan')) ?>" class="nav-link">Kelayakan</a>
                    </li>
                    <li class="nav-item">
                        <a href="<?= e(base_url('pendonor/dashboard.php')) ?>" class="nav-link">DonorDarah</a>
                    </li>
                <?php elseif (($userLogin['role'] ?? '') === 'petugas'): ?>
                    <li class="nav-item">
                        <a href="<?= e(base_url('index.php')) ?>" class="nav-link">Beranda</a>
                    </li>
                    <li class="nav-item">
                        <a href="<?= e(base_url('petugas/dashboard.php')) ?>" class="nav-link">Layanan</a>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a href="<?= e(base_url('index.php')) ?>" class="nav-link">Beranda</a>
                    </li>
                    <li class="nav-item">
                        <a href="<?= e(base_url('pendonor/dashboard.php')) ?>" class="nav-link">DonorDarah</a>
                    </li>
                    <li class="nav-item">
                        <a href="<?= e(base_url('index.php#stokdarah')) ?>" class="nav-link">StokDarah</a>
                    </li>
                    <li class="nav-item">
                        <a href="<?= e(base_url('index.php#butuhdarah')) ?>" class="nav-link">ButuhDarah</a>
                    </li>
                <?php endif; ?>
                <?php if ($userLogin !== null): ?>
                    <li class="nav-item">
                        <details class="account-menu">
                            <summary class="account-menu-trigger" aria-label="Menu akun pengguna" title="<?= e($userLogin['nama']) ?>">
                                <svg viewBox="0 0 24 24" aria-hidden="true">
                                    <path d="M12 12a4.5 4.5 0 1 0 0-9 4.5 4.5 0 0 0 0 9Zm0 2c-4.42 0-8 2.24-8 5v2h16v-2c0-2.76-3.58-5-8-5Z"/>
                                </svg>
                            </summary>
                            <div class="account-menu-panel">
                                <div class="account-menu-user">
                                    <strong><?= e($userLogin['nama']) ?></strong>
                                    <span><?= e(ucfirst($userLogin['role'])) ?></span>
                                </div>
                                <a class="account-menu-logout" href="<?= e(base_url('auth/logout.php')) ?>">Logout</a>
                            </div>
                        </details>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a href="<?= e(base_url('auth/login.php')) ?>" class="nav-link">Login</a>
                    </li>
                    <li class="nav-item">
                        <a href="<?= e(base_url('auth/register.php')) ?>" class="nav-link">Daftar</a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>