<?php
// includes/partials/header-panel.php
// Wrapper template header untuk Panel Sidebar (Pengajar, Admin, Profil)

$root = $root ?? '';
require_once __DIR__ . '/head.php';
?>

<!-- Struktur Layout Panel (Sidebar di Kiri, Konten di Kanan) -->
<div class="layout-panel">
  <?php require_once __DIR__ . '/sidebar-panel.php'; ?>

  <!-- Area Konten Utama Panel -->
  <div class="panel-content">
    <?php
    // Tampilkan notifikasi flash jika ada
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
