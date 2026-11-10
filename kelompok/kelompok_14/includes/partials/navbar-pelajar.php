<?php
// includes/partials/navbar-pelajar.php
// Navigasi atas untuk Pelajar dan Pengguna Tamu (Guest)

$root = $root ?? '';
$halamanAktif = $halamanAktif ?? '';

// Cek apakah pengguna saat ini adalah Guest atau Pelajar terdaftar
$isGuest = isset($_SESSION['guest']) || (isset($_SESSION['pengguna']['peran']) && $_SESSION['pengguna']['peran'] === 'guest');

// Ambil nama tampilan pengguna
$namaPengguna = 'Pelajar';
if ($isGuest) {
    $namaPengguna = $_SESSION['guest']['username'] ?? $_SESSION['guest']['nama'] ?? 'Tamu';
} elseif (isset($_SESSION['pengguna']['nama'])) {
    $namaPengguna = $_SESSION['pengguna']['nama'];
}

// Tautan halaman Beranda sesuai status (Guest atau Pelajar)
$linkBeranda = $isGuest ? ($root . 'guest/dashboard.php') : ($root . 'pelajar/dashboard.php');
?>
<header class="navbar-top">
  <div class="navbar-container">
    <a href="<?= $linkBeranda ?>" class="navbar-brand">
      Dyna<span>Quiz</span>
    </a>

    <!-- Tombol Menu untuk tampilan HP -->
    <button type="button" class="menu-toggle" id="tombolMenu" aria-label="Buka menu navigasi" aria-expanded="false">
      &#9776;
    </button>

    <nav class="navbar-menu" id="menuNav" aria-label="Menu Pelajar">
      <a href="<?= $linkBeranda ?>" class="navbar-link <?= $halamanAktif === 'beranda' ? 'active' : '' ?>">Beranda</a>
      <a href="<?= $root ?>kuis/katalog.php" class="navbar-link <?= $halamanAktif === 'katalog' ? 'active' : '' ?>">Katalog Kuis</a>

      <?php if ($isGuest): ?>
        <!-- Menu Khusus Tamu (Guest) -->
        <a href="<?= $root ?>register.php" class="navbar-link <?= $halamanAktif === 'register' ? 'active' : '' ?>">Daftar Akun</a>
        <a href="<?= $root ?>logout.php" class="navbar-link">Keluar Sesi</a>
      <?php else: ?>
        <!-- Menu Khusus Pelajar Terdaftar -->
        <a href="<?= $root ?>pelajar/peringkat.php" class="navbar-link <?= $halamanAktif === 'peringkat' ? 'active' : '' ?>">Peringkat</a>
        <a href="<?= $root ?>pelajar/riwayat.php" class="navbar-link <?= $halamanAktif === 'riwayat' ? 'active' : '' ?>">Riwayat</a>
        <a href="<?= $root ?>profil.php" class="navbar-link <?= $halamanAktif === 'profil' ? 'active' : '' ?>">Profil</a>
        <a href="<?= $root ?>logout.php" class="navbar-link">Keluar</a>
      <?php endif; ?>

      <!-- Info Nama Pengguna -->
      <span class="badge badge-sedang" title="Pengguna aktif">
        <?= e($namaPengguna) ?><?= $isGuest ? ' (Tamu)' : '' ?>
      </span>

      <!-- Widget Pemilih Tema Claymorphism -->
      <?php include __DIR__ . '/pemilih-tema.php'; ?>
    </nav>
  </div>
</header>
