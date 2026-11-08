
<?php
$currentPage = basename($_SERVER['PHP_SELF']);
$currentPath = str_replace('\\', '/', $_SERVER['PHP_SELF']);
?>

<aside class="sidebar p-3">

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
        <i class="bi bi-cart"></i>
        Transaksi
    </a>

    <a href="<?= $baseUrl ?>/riwayat/index.php"
       class="sidebar-link <?= str_contains($currentPath, '/riwayat/') ? 'active' : '' ?>">
        <i class="bi bi-clock-history"></i>
        Riwayat Transaksi
    </a>

    <a href="<?= $baseUrl ?>/laporan/index.php"
       class="sidebar-link <?= str_contains($currentPath, '/laporan/') ? 'active' : '' ?>">
        <i class="bi bi-file-earmark-bar-graph"></i>
        Laporan
    </a>

    <hr class="sidebar-divider">

    <a href="<?= $baseUrl ?>/users.php"
       class="sidebar-link <?= $currentPage === 'users.php' ? 'active' : '' ?>">
        <i class="bi bi-people"></i>
        Kelola User
    </a>

</aside>
