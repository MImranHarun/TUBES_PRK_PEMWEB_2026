<?php
require_once __DIR__ . '/../config/auth.php';
$pageTitle = $pageTitle ?? 'SisaSense';
$activePage = $activePage ?? '';
$navItems = $navItems ?? [];
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?> | SisaSense</title>
    <link rel="stylesheet" href="<?= e(app_url('assets/css/style.css')) ?>">
</head>
<body>
<div class="app-shell">
    <?php if (current_user()): ?>
        <aside class="sidebar">
            <a class="brand" href="<?= e(app_url('dashboard.php')) ?>">
                <span class="brand-mark" aria-hidden="true">S</span>
                <span>SisaSense<small>Inventaris UMKM</small></span>
            </a>
            <?php if ($navItems): ?>
                <nav class="side-nav" aria-label="Navigasi utama">
                    <?php foreach ($navItems as $item): ?>
                        <a class="<?= ($item['key'] ?? '') === $activePage ? 'is-active' : '' ?>" href="<?= e(app_url($item['href'])) ?>">
                            <span class="nav-indicator" aria-hidden="true"></span><?= e($item['label']) ?>
                        </a>
                    <?php endforeach; ?>
                </nav>
            <?php endif; ?>
            <div class="sidebar-user">
                <span class="avatar" aria-hidden="true"><?= e(mb_strtoupper(mb_substr(current_user()['name'] ?? 'S', 0, 1))) ?></span>
                <span class="user-identity"><strong><?= e(current_user()['name'] ?? 'Pengguna') ?></strong><small><?= current_user()['role'] === 'admin' ? 'Administrator' : 'Kasir' ?></small></span>
                <form method="post" action="<?= e(app_url('logout.php')) ?>">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <button class="logout-button" type="submit" aria-label="Keluar dari akun">↗</button>
                </form>
            </div>
        </aside>
    <?php endif; ?>
    <div class="main-column">
        <?php if (current_user()): ?>
            <header class="topbar">
                <span><?= e($pageTitle) ?></span>
                <span class="topbar-date"><?= e(date('l, d F Y')) ?></span>
            </header>
        <?php endif; ?>
        <main class="page-shell<?= current_user() ? '' : ' page-shell-auth' ?>">
    <?php if ($flashMessage = take_flash()): ?>
        <div class="notice notice-<?= e($flashMessage['type']) ?>" role="status"><?= e($flashMessage['message']) ?></div>
    <?php endif; ?>
