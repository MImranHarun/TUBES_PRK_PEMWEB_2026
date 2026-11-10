<?php
// 1. Jalankan sesi jika belum aktif
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Muat fungsi pembantu
require_once __DIR__ . '/includes/helpers.php';

// 3. Aturan AUT-06: Jika pengguna sudah login, arahkan langsung ke dashboard masing-masing
if (isset($_SESSION['pengguna']['peran'])) {
    $peran = $_SESSION['pengguna']['peran'];
    if ($peran === 'admin') {
        redirect('admin/dashboard.php');
    } elseif ($peran === 'pengajar') {
        redirect('pengajar/dashboard.php');
    } elseif ($peran === 'pelajar') {
        redirect('pelajar/dashboard.php');
    } elseif ($peran === 'guest') {
        redirect('guest/dashboard.php');
    }
}

// 4. Konfigurasi halaman dan template publik
$root = './';
$judulHalaman = 'DynaQuiz - Platform Latihan Soal Interaktif';
$halamanAktif = 'beranda';

// 5. Muat header layout publik
require_once __DIR__ . '/includes/partials/header-publik.php';
?>

<!-- Bagian Hero Banner Landing Page -->
<div class="landing-hero card" style="text-align: center; margin: 32px 0; padding: 48px 24px;">
  <span class="badge badge-sedang" style="margin-bottom: 16px;">Tugas Akhir Pemrograman Web 2026 &bull; Kelompok 14</span>
  <h1 style="font-size: 2.25rem; margin-bottom: 16px;">
    Latihan Soal Jadi Lebih Asyik di <span>DynaQuiz</span>!
  </h1>
  <p style="font-size: 1.125rem; max-width: 680px; margin: 0 auto 28px; color: var(--teks);">
    Platform kuis dan latihan soal interaktif dengan umpan balik seketika, timer pengerjaan, pembahasan mendalam, serta papan peringkat untuk menemani proses belajarmu.
  </p>

  <!-- Tombol Aksi Utama (Call to Action) -->
  <div style="display: flex; gap: 16px; justify-content: center; flex-wrap: wrap;">
    <a href="guest.php" class="btn btn-primary" style="font-size: 1rem; text-decoration: none;">
      &#128640; Coba Langsung (Mode Tamu)
    </a>
    <a href="login.php" class="btn btn-secondary" style="font-size: 1rem; text-decoration: none;">
      &#128273; Masuk Akun
    </a>
    <a href="register.php" class="btn btn-outline" style="font-size: 1rem; text-decoration: none;">
      &#128221; Daftar Pelajar
    </a>
  </div>
</div>

<!-- Grid Bento Fitur Unggulan DynaQuiz -->
<div style="margin-bottom: 40px;">
  <div style="text-align: center; margin-bottom: 24px;">
    <h2 style="font-size: 1.75rem; margin-bottom: 8px;">Kenapa Belajar di DynaQuiz?</h2>
    <p style="color: var(--teks);">Dirancang khusus dengan nuansa Claymorphism yang ramah, santai, dan interaktif.</p>
  </div>

  <div class="bento-grid">
    <!-- Fitur 1: Interaktif & Timer -->
    <div class="card">
      <div style="font-size: 2rem; margin-bottom: 12px;">&#9201;</div>
      <h3 class="card-title">Timer &amp; Pembahasan Instan</h3>
      <p style="color: var(--teks);">
        Kerjakan soal pilihan ganda dengan hitung mundur waktu. Langsung dapatkan skor dan pembahasan lengkap setelah menjawab tiap soal.
      </p>
    </div>

    <!-- Fitur 2: 4 Pilihan Tema Pastel -->
    <div class="card">
      <div style="font-size: 2rem; margin-bottom: 12px;">&#127912;</div>
      <h3 class="card-title">4 Pilihan Tema Pastel</h3>
      <p style="color: var(--teks);">
        Sesuaikan gaya tampilan favoritmu: <em>Sky Bubblegum, Lavender Dream, Mint Cloud,</em> atau <em>Peach Pop</em> tanpa kedip layar.
      </p>
    </div>

    <!-- Fitur 3: Papan Peringkat & Riwayat -->
    <div class="card">
      <div style="font-size: 2rem; margin-bottom: 12px;">&#127942;</div>
      <h3 class="card-title">Peringkat &amp; Riwayat Skor</h3>
      <p style="color: var(--teks);">
        Pantau performamu, pertahankan streak belajar harian, dan bersaing secara sehat dengan pelajar lainnya di leaderboard.
      </p>
    </div>

    <!-- Fitur 4: Kuis Terverifikasi -->
    <div class="card">
      <div style="font-size: 2rem; margin-bottom: 12px;">&#128218;</div>
      <h3 class="card-title">Kualitas Soal Terjaga</h3>
      <p style="color: var(--teks);">
        Kuis disusun langsung oleh pengajar terdaftar dan melalui kurasi moderasi admin sebelum dapat diakses di katalog publik.
      </p>
    </div>

    <!-- Fitur 5: Mode Tamu Praktis -->
    <div class="card">
      <div style="font-size: 2rem; margin-bottom: 12px;">&#10024;</div>
      <h3 class="card-title">Mode Tamu Tanpa Ribet</h3>
      <p style="color: var(--teks);">
        Ingin langsung berlatih tanpa mendaftar akun? Cukup masukkan nama panggilan dan kamu bisa langsung mencoba kuis publik.
      </p>
    </div>

    <!-- Fitur 6: Aksesibilitas Mobile-First -->
    <div class="card">
      <div style="font-size: 2rem; margin-bottom: 12px;">&#128241;</div>
      <h3 class="card-title">Nyaman di Semua Perangkat</h3>
      <p style="color: var(--teks);">
        Tampilan mobile-first yang responsif dari layar smartphone 360 px hingga desktop dengan kontras warna yang nyaman di mata.
      </p>
    </div>
  </div>
</div>

<!-- Banner Ajakan Bergabung -->
<div class="card" style="text-align: center; padding: 36px 20px; margin-bottom: 40px;">
  <h2 style="font-size: 1.5rem; margin-bottom: 12px;">Mulai Latihan Pertamamu Sekarang</h2>
  <p style="max-width: 560px; margin: 0 auto 20px; color: var(--teks);">
    Pilih kuis yang kamu sukai, uji pemahaman materi, dan lihat seberapa jauh skor belajarmu berkembang!
  </p>
  <a href="guest.php" class="btn btn-primary" style="text-decoration: none;">
    Masuk Mode Tamu Sekarang &rarr;
  </a>
</div>

<?php
// 6. Muat penutup footer layout publik
require_once __DIR__ . '/includes/partials/footer-publik.php';
?>
