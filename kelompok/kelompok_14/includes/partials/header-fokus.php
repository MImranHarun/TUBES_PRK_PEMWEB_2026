<?php

$root = $root ?? '';
$judulHalaman = $judulHalaman ?? 'Kerjakan Kuis - DynaQuiz';
$judulKuis = $judulKuis ?? 'Kuis DynaQuiz';
$sisaWaktu = $sisaWaktu ?? '15:00';
$linkKeluar = $linkKeluar ?? ($root . 'index.php');

// Muat tag <head> pembuka (tanpa navbar)
require_once __DIR__ . '/head.php';
?>

<!-- Bar Atas Mode Fokus -->
<header class="focus-bar">
  <div class="focus-info">
    <strong class="focus-judul"><?= e($judulKuis) ?></strong>
  </div>

  <!-- Indikator Waktu Pengerjaan -->
  <div class="timer-pill" id="wadahTimer" role="timer" aria-live="polite">
    <span aria-hidden="true">&#9201;</span>
    <span id="timerDisplay"><?= e($sisaWaktu) ?></span>
  </div>

  <!-- Tombol Keluar dari Kuis -->
  <div class="focus-aksi">
    <a href="<?= e($linkKeluar) ?>" class="btn btn-sm btn-batal" id="tombolKeluarFokus">
      Keluar
    </a>
  </div>
</header>

<!-- Progress Bar Pengerjaan Kuis -->
<div class="progress-track" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0" aria-label="Kemajuan pengerjaan kuis">
  <div class="progress-fill" id="progressFill"></div>
</div>

<!-- Konten Utama Soal Kuis -->
<main class="container">
