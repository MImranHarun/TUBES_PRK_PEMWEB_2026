<?php
// 1. Set kode status respon HTTP menjadi 404 Not Found
http_response_code(404);

// 2. Jalankan sesi jika belum aktif
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 3. Muat fungsi pembantu
require_once __DIR__ . '/includes/helpers.php';

// 4. Konfigurasi halaman dan template publik
$root = './';
$judulHalaman = '404 Halaman Tidak Ditemukan - DynaQuiz';
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

<!-- Kartu Konten Error 404 -->
<div class="card" style="max-width: 640px; margin: 48px auto; text-align: center; padding: 48px 28px;">
  <div style="font-size: 3.5rem; line-height: 1; margin-bottom: 16px;" aria-hidden="true">
    &#128506;
  </div>

  <span class="badge badge-sedang" style="margin-bottom: 16px;">
    Error 404 &bull; Tidak Ditemukan
  </span>

  <h1 style="font-size: 2rem; margin-bottom: 14px;">
    Halaman Tidak Ditemukan
  </h1>

  <p style="font-size: 1.05rem; color: var(--teks); line-height: 1.6; margin-bottom: 32px;">
    Maaf, halaman atau kuis yang kamu cari tidak dapat ditemukan. Tautan yang kamu tuju mungkin salah ketik, sudah dipindahkan, atau kuis telah dihapus dari sistem.
  </p>

  <!-- Tombol Aksi Navigasi -->
  <div style="display: flex; gap: 14px; justify-content: center; flex-wrap: wrap;">
    <a href="<?= e($linkKembali) ?>" class="btn btn-primary" style="text-decoration: none;">
      &#8592; <?= e($labelKembali) ?>
    </a>

    <a href="index.php" class="btn btn-secondary" style="text-decoration: none;">
      &#127968; Halaman Utama
    </a>
  </div>
</div>

<?php
// 6. Muat penutup footer layout publik
require_once __DIR__ . '/includes/partials/footer-publik.php';
?>
