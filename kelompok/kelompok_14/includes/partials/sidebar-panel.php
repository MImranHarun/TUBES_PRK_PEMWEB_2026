<?php
// includes/partials/sidebar-panel.php
// Panel samping (sidebar) untuk peran Pengajar dan Admin

$root = $root ?? '';
$halamanAktif = $halamanAktif ?? '';

// Ambil data peran dan nama dari sesi
$peran = $_SESSION['pengguna']['peran'] ?? 'pengajar';
$namaPengguna = $_SESSION['pengguna']['nama'] ?? 'Pengguna';

// Tautan dashboard sesuai peran
$linkDashboard = ($peran === 'admin') ? ($root . 'admin/dashboard.php') : ($root . 'pengajar/dashboard.php');
?>
<aside class="sidebar-panel" aria-label="Navigasi Panel Samping">
  <div class="sidebar-atas">
    <!-- Logo dan Judul Panel -->
    <a href="<?= $linkDashboard ?>" class="navbar-brand">
      Dyna<span>Quiz</span>
    </a>
    <span class="badge badge-sedang">
      Panel <?= ($peran === 'admin') ? 'Admin' : 'Pengajar' ?>
    </span>

    <!-- Menu Navigasi Samping -->
    <ul class="sidebar-menu" id="menuSidebar">
      <?php if ($peran === 'admin'): ?>
        <!-- Menu Khusus Admin -->
        <li>
          <a href="<?= $root ?>admin/dashboard.php" class="sidebar-link <?= $halamanAktif === 'dashboard' ? 'active' : '' ?>">
            <span>&#128202;</span> Dashboard
          </a>
        </li>
        <li>
          <a href="<?= $root ?>admin/persetujuan.php" class="sidebar-link <?= $halamanAktif === 'persetujuan' ? 'active' : '' ?>">
            <span>&#128221;</span> Persetujuan Kuis
            <?php if (!empty($jumlahAntrianKuis)): ?>
              <span class="sidebar-badge"><?= (int)$jumlahAntrianKuis ?></span>
            <?php endif; ?>
          </a>
        </li>
        <li>
          <a href="<?= $root ?>admin/pengguna.php" class="sidebar-link <?= $halamanAktif === 'pengguna' ? 'active' : '' ?>">
            <span>&#128101;</span> Kelola Pengguna
          </a>
        </li>
        <li>
          <a href="<?= $root ?>admin/mapel.php" class="sidebar-link <?= $halamanAktif === 'mapel' ? 'active' : '' ?>">
            <span>&#128218;</span> Kelola Mapel
          </a>
        </li>
        <li>
          <a href="<?= $root ?>admin/moderasi.php" class="sidebar-link <?= $halamanAktif === 'moderasi' ? 'active' : '' ?>">
            <span>&#128737;</span> Moderasi Kuis
          </a>
        </li>
      <?php else: ?>
        <!-- Menu Khusus Pengajar -->
        <li>
          <a href="<?= $root ?>pengajar/dashboard.php" class="sidebar-link <?= $halamanAktif === 'dashboard' ? 'active' : '' ?>">
            <span>&#128202;</span> Dashboard
          </a>
        </li>
        <li>
          <a href="<?= $root ?>pengajar/kuis.php" class="sidebar-link <?= $halamanAktif === 'kuis' ? 'active' : '' ?>">
            <span>&#128221;</span> Kuis Saya
          </a>
        </li>
      <?php endif; ?>

      <!-- Menu Bersama (Profil & Keluar) -->
      <li>
        <a href="<?= $root ?>profil.php" class="sidebar-link <?= $halamanAktif === 'profil' ? 'active' : '' ?>">
          <span>&#128100;</span> Profil Saya
        </a>
      </li>
      <li>
        <a href="<?= $root ?>logout.php" class="sidebar-link">
          <span>&#128682;</span> Keluar
        </a>
      </li>
    </ul>
  </div>

  <!-- Bagian Bawah Sidebar (Info User & Pemilih Tema) -->
  <div class="sidebar-bawah">
    <p class="card-sm">
      Halo, <strong><?= e($namaPengguna) ?></strong>
    </p>

    <!-- Widget Pemilih Tema Claymorphism -->
    <?php include __DIR__ . '/pemilih-tema.php'; ?>
  </div>
</aside>
