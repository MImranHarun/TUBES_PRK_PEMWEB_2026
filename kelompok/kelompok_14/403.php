<?php
// 1. Set kode status respon HTTP menjadi 403 Forbidden
http_response_code(403);

// 2. Jalankan sesi jika belum aktif
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 3. Muat fungsi pembantu
require_once __DIR__ . '/includes/helpers.php';

// 4. Konfigurasi halaman dan template publik
$root = './';
$judulHalaman = '403 Akses Ditolak - DynaQuiz';
$halamanAktif = '';

// Tentukan tautan kembali yang cerdas berdasarkan peran pengguna saat ini
$linkKembali = 'index.php';
$labelKembali = 'Kembali ke Beranda';

if (isset($_SESSION['pengguna']['peran'])) {
    $peran = $_SESSION['pengguna']['peran'];
    if ($peran === 'admin') {
        $linkKembali = 'admin/dashboard.php';
        $labelKembali = 'Kembali ke Dashboard Admin';
    } elseif ($peran === 'pengajar') {
        $linkKembali = 'pengajar/dashboard.php';
        $labelKembali = 'Kembali ke Dashboard Pengajar';
    } elseif ($peran === 'pelajar') {
        $linkKembali = 'pelajar/dashboard.php';
        $labelKembali = 'Kembali ke Dashboard Pelajar';
    } elseif ($peran === 'guest') {
        $linkKembali = 'guest/dashboard.php';
        $labelKembali = 'Kembali ke Dashboard Tamu';
    }
}

// 5. Muat header layout publik
require_once __DIR__ . '/includes/partials/header-publik.php';
?>

<!-- Kartu Konten Error 403 -->
<div class="card" style="max-width: 640px; margin: 48px auto; text-align: center; padding: 48px 28px;">
  <div style="font-size: 3.5rem; line-height: 1; margin-bottom: 16px;" aria-hidden="true">
    &#128683;
  </div>

  <span class="badge badge-sulit" style="margin-bottom: 16px;">
    Error 403 &bull; Akses Ditolak
  </span>

  <h1 style="font-size: 2rem; margin-bottom: 14px;">
    Ups! Kamu Tidak Memiliki Akses
  </h1>

  <p style="font-size: 1.05rem; color: var(--teks); line-height: 1.6; margin-bottom: 32px;">
    Maaf, akun atau peranmu saat ini tidak memiliki izin untuk membuka halaman yang dituju. Silakan kembali ke dashboard atau masuk menggunakan akun yang sesuai.
  </p>

  <!-- Tombol Aksi Navigasi -->
  <div style="display: flex; gap: 14px; justify-content: center; flex-wrap: wrap;">
    <a href="<?= e($linkKembali) ?>" class="btn btn-primary" style="text-decoration: none;">
      &#8592; <?= e($labelKembali) ?>
    </a>

    <?php if (!isset($_SESSION['pengguna'])): ?>
      <a href="login.php" class="btn btn-secondary" style="text-decoration: none;">
        &#128273; Masuk dengan Akun Lain
      </a>
    <?php endif; ?>
  </div>
</div>

<?php
// 6. Muat penutup footer layout publik
require_once __DIR__ . '/includes/partials/footer-publik.php';
?>
