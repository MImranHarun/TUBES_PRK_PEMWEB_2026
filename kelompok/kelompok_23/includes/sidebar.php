
<?php
$currentPage = basename($_SERVER['PHP_SELF']);
$currentPath = str_replace('\\', '/', $_SERVER['PHP_SELF']);
$sidebarUserName = trim((string)($_SESSION['name'] ?? ''));
$sidebarUserRole = trim((string)($_SESSION['role'] ?? ''));
$sidebarPhoto = trim((string)($_SESSION['photo'] ?? ''));
if ($sidebarPhoto === 'avatar-avatar.svg') {
    $sidebarPhoto = '';
    $_SESSION['photo'] = null;
}
$sidebarAvatarSrc = ($sidebarPhoto !== '' && is_file(__DIR__ . '/../assets/img/' . $sidebarPhoto))
    ? $baseUrl . '/assets/img/' . rawurlencode($sidebarPhoto)
    : $baseUrl . '/assets/img/avatar-default.svg';
?>

<aside class="sidebar p-3">

    <div class="sidebar-brand">
        <img src="<?= $baseUrl ?>/assets/img/logo.svg"
             alt="FEBMart" loading="lazy">
    </div>

    <div class="sidebar-profile">
        <div class="sidebar-avatar">
            <img src="<?= htmlspecialchars($sidebarAvatarSrc); ?>"
                 alt="Foto profil" loading="lazy">
        </div>
        <div class="sidebar-userinfo">
            <strong class="text-truncate"><?= htmlspecialchars($sidebarUserName ?: 'Pengguna'); ?></strong>
            <small class="text-muted text-truncate"><?= htmlspecialchars(ucfirst($sidebarUserRole ?: 'user')); ?></small>
        </div>
    </div>

    <h6 class="sidebar-heading">MENU UTAMA</h6>

    <a href="<?= $baseUrl ?>/dashboard.php"
       class="sidebar-link <?= $currentPage === 'dashboard.php' ? 'active' : '' ?>">
        <i class="bi bi-speedometer2"></i>
        Dashboard
    </a>

    <a href="<?= $baseUrl ?>/produk/index.php"
       class="sidebar-link <?= str_contains($currentPath, '/produk/') ? 'active' : '' ?>">
        <i class="bi bi-box-seam"></i>
        Produk
    </a>

    <a href="<?= $baseUrl ?>/kategori/index.php"
       class="sidebar-link <?= str_contains($currentPath, '/kategori/') ? 'active' : '' ?>">
        <i class="bi bi-tags"></i>
        Kategori
    </a>

    <a href="<?= $baseUrl ?>/transaksi/index.php"
       class="sidebar-link <?= str_contains($currentPath, '/transaksi/') ? 'active' : '' ?>">
        <i class="bi bi-cash-coin"></i>
        Kasir
    </a>

    <a href="<?= $baseUrl ?>/riwayat/index.php"
       class="sidebar-link <?= str_contains($currentPath, '/riwayat/') ? 'active' : '' ?>">
        <i class="bi bi-clock-history"></i>
        Riwayat Transaksi
    </a>

    <a href="<?= $baseUrl ?>/laporan/index.php"
       class="sidebar-link <?= str_contains($currentPath, '/laporan/') ? 'active' : '' ?>">
        <i class="bi bi-file-earmark-bar-graph"></i>
        Laporan Penjualan
    </a>

    <hr class="sidebar-divider">

    <a href="<?= $baseUrl ?>/users.php"
       class="sidebar-link <?= $currentPage === 'users.php' ? 'active' : '' ?>">
        <i class="bi bi-people"></i>
        Kelola User
    </a>

</aside>
