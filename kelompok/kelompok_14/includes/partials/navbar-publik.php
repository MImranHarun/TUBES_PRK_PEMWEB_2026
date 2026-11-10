<?php
// includes/partials/navbar-publik.php
// Navigasi atas sederhana khusus untuk halaman publik (Landing, Login, Register, Guest, Error)

$root = $root ?? '';
$halamanAktif = $halamanAktif ?? '';
?>
<header class="navbar-top">
  <div class="navbar-container">
    <a href="<?= $root ?>index.php" class="navbar-brand">
      Dyna<span>Quiz</span>
    </a>

    <!-- Tombol Menu untuk tampilan HP -->
    <button type="button" class="menu-toggle" id="tombolMenu" aria-label="Buka menu navigasi" aria-expanded="false">
      &#9776;
    </button>

    <nav class="navbar-menu" id="menuNav" aria-label="Menu Utama">
      <a href="<?= $root ?>index.php" class="navbar-link <?= $halamanAktif === 'beranda' ? 'active' : '' ?>">Beranda</a>
      <a href="<?= $root ?>guest.php" class="navbar-link <?= $halamanAktif === 'guest' ? 'active' : '' ?>">Mode Tamu</a>
      <a href="<?= $root ?>login.php" class="navbar-link <?= $halamanAktif === 'login' ? 'active' : '' ?>">Masuk</a>
      <a href="<?= $root ?>register.php" class="navbar-link <?= $halamanAktif === 'register' ? 'active' : '' ?>">Daftar</a>

      <!-- Widget Pemilih Tema Claymorphism -->
      <?php include __DIR__ . '/pemilih-tema.php'; ?>
    </nav>
  </div>
</header>
