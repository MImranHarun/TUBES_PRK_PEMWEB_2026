<?php
require_once __DIR__ . '/config/auth.php';
require_login();

$pageTitle = 'Beranda';
$activePage = 'home';
$navItems = [
    ['key' => 'home', 'label' => 'Beranda', 'href' => 'dashboard.php'],
];
require __DIR__ . '/includes/header.php';
?>
<section class="welcome-panel">
    <p class="eyebrow">SISTEM MANAJEMEN PERSEDIAAN</p>
    <h1>Selamat datang, <?= e(current_user()['name']) ?>.</h1>
    <p class="welcome-copy">Anda masuk sebagai <strong><?= current_user()['role'] === 'admin' ? 'Administrator' : 'Kasir' ?></strong>. Gunakan navigasi bersama untuk membuka fitur yang tersedia bagi peran Anda.</p>
    <div class="welcome-status"><span class="status-dot" aria-hidden="true"></span> Sesi Anda aktif dan terlindungi.</div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>