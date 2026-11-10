<?php
// includes/partials/header-pelajar.php
// Wrapper template header untuk halaman Pelajar dan Guest (Dashboard, Katalog, Detail, Hasil, Riwayat, Peringkat)

$root = $root ?? '';
require_once __DIR__ . '/head.php';
require_once __DIR__ . '/navbar-pelajar.php';
?>

<!-- Struktur Konten Utama Pelajar dan Guest -->
<div class="container">
  <?php
  // Tampilkan notifikasi flash message jika ada
  if (function_exists('flash')) {
      $pesanFlash = flash();
      if ($pesanFlash) {
          $tipe = ($pesanFlash['tipe'] === 'sukses' || $pesanFlash['tipe'] === 'galat' || $pesanFlash['tipe'] === 'info')
              ? $pesanFlash['tipe']
              : 'info';
          echo '<div class="alert alert-' . e($tipe) . '">' . e($pesanFlash['pesan']) . '</div>';
      }
  }
  ?>
  <main>
