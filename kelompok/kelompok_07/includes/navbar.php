<?php

$user = current_user();

$menusByRole = [
    'tamu' => [
        ['label' => 'Beranda', 'path' => 'index.php'],
        ['label' => 'Daftar Alat', 'path' => 'alat/index.php'],
    ],
    'peminjam' => [
        ['label' => 'Beranda', 'path' => 'index.php'],
        ['label' => 'Daftar Alat', 'path' => 'alat/index.php'],
        ['label' => 'Ajukan Peminjaman', 'path' => 'peminjaman/ajukan.php'],
        ['label' => 'Riwayat', 'path' => 'peminjaman/riwayat.php'],
    ],
    'admin' => [
        ['label' => 'Dashboard', 'path' => 'admin/dashboard.php'],
        ['label' => 'Kategori', 'path' => 'admin/kategori/index.php'],
        ['label' => 'Data Alat', 'path' => 'admin/alat/index.php'],
        ['label' => 'Peminjaman', 'path' => 'admin/peminjaman/index.php'],
        ['label' => 'Laporan', 'path' => 'admin/laporan.php'],
    ],
];

$activeRole = $user['role'] ?? 'tamu';
$menus = $menusByRole[$activeRole] ?? $menusByRole['tamu'];
?>
<header class="navbar">
    <div class="container navbar-inner">
        <a class="navbar-brand" href="<?= e(url('index.php')) ?>">
            <span class="navbar-logo">LL</span>
            <span><?= e(APP_NAME) ?></span>
        </a>
        <input type="checkbox" id="navbar-toggle" class="navbar-toggle">
        <label for="navbar-toggle" class="navbar-burger" aria-label="Buka menu">
            <span></span><span></span><span></span>
        </label>
        <nav class="navbar-menu">
            <ul class="navbar-links">
<?php foreach ($menus as $menu): ?>
                <li>
                    <a href="<?= e(url($menu['path'])) ?>" class="<?= is_current_page($menu['path']) ? 'active' : '' ?>"><?= e($menu['label']) ?></a>
                </li>
<?php endforeach; ?>
            </ul>
            <div class="navbar-actions">
<?php if ($user === null): ?>
                <a href="<?= e(url('auth/login.php')) ?>" class="btn btn-outline btn-sm">Masuk</a>
                <a href="<?= e(url('auth/register.php')) ?>" class="btn btn-primary btn-sm">Daftar</a>
<?php else: ?>
                <span class="navbar-user">
                    <?= e($user['nama']) ?>
                    <span class="badge badge-<?= $activeRole === 'admin' ? 'info' : 'neutral' ?>"><?= e($activeRole) ?></span>
                </span>
                <a href="<?= e(url('auth/logout.php')) ?>" class="btn btn-danger btn-sm">Keluar</a>
<?php endif; ?>
            </div>
        </nav>
    </div>
</header>
