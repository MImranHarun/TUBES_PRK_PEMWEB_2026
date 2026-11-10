<?php
// Navigasi admin bersama. Pemakaian di halaman admin mana pun:
//   include __DIR__ . '/../includes/admin_nav.php';   (sesuaikan jumlah ../ dengan kedalaman file)
// Link memakai admin_url(), jadi tetap benar walau dipanggil dari subfolder.
// Halaman yang sedang dibuka otomatis ditandai (underline tebal).

$navSkrip = $_SERVER['SCRIPT_NAME'] ?? '';
if (str_contains($navSkrip, '/admin/berita/')) {
    $navAktif = 'berita';
} elseif (str_contains($navSkrip, '/admin/aspirasi.php')) {
    $navAktif = 'aspirasi';
} else {
    $navAktif = 'dashboard';
}

$navMenu = [
    'dashboard' => ['Dashboard', '/admin/index.php'],
    'berita'    => ['Berita', '/admin/berita/index.php'],
    'aspirasi'  => ['Aspirasi', '/admin/aspirasi.php'],
];
?>
<nav class="bg-teal-700 text-white">
    <div class="max-w-6xl mx-auto px-4 py-3 flex flex-wrap items-center justify-between gap-2">
        <span class="font-bold">KabarSih Admin</span>
        <div class="flex gap-4 text-sm">
            <?php foreach ($navMenu as $kunci => [$label, $path]): ?>
                <a href="<?= e(admin_url($path)) ?>"
                   class="<?= $navAktif === $kunci ? 'font-semibold underline' : 'hover:underline' ?>"><?= e($label) ?></a>
            <?php endforeach; ?>
            <a href="<?= e(admin_url('/auth/logout.php')) ?>" class="hover:underline">Logout</a>
        </div>
    </div>
</nav>