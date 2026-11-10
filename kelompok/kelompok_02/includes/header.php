<?php
$pageTitle = $pageTitle ?? 'Katalog Peralatan Laboratorium';
$activeNav = $activeNav ?? '';
$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="LABCAT adalah katalog informasi peralatan laboratorium Fakultas MIPA.">
    <title><?= e($pageTitle) ?> | LABCAT</title>
    <link rel="stylesheet" href="<?= url('assets/css/user/style.css') ?>">
    <script defer src="<?= url('assets/js/app.js') ?>"></script>
</head>
<body>
<header class="site-header">
    <div class="container nav-wrap">
        <a class="brand" href="<?= url('index.php') ?>" aria-label="LABCAT Beranda">
            <img class="brand-icon" src="<?= url('assets/images/flask-mark-white.svg') ?>" alt="">
            <span class="brand-copy">
                <strong>LABCAT</strong>
                <small>LABORATORY CATALOG SYSTEM</small>
            </span>
        </a>

        <button class="nav-toggle" type="button" aria-label="Buka menu" data-nav-toggle>☰</button>

        <nav class="main-nav" data-nav>
            <a class="<?= $activeNav === 'home' ? 'active' : '' ?>" href="<?= url('index.php') ?>">Beranda</a>
            <a class="<?= $activeNav === 'catalog' ? 'active' : '' ?>" href="<?= url('index.php#katalog') ?>">Katalog</a>
            <a class="<?= $activeNav === 'about' ? 'active' : '' ?>" href="<?= url('index.php#tentang') ?>">Tentang</a>
            <a class="btn btn-light btn-login" href="<?= url('admin/login.php') ?>">
                <span class="login-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="3.5"/><path d="M5 20c.8-3.2 3.1-5 7-5s6.2 1.8 7 5"/></svg></span>
                <span>Login Admin</span>
            </a>
        </nav>
    </div>
</header>

<main>
<?php if ($flash): ?>
    <div class="container flash-wrap">
        <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
    </div>
<?php endif; ?>
