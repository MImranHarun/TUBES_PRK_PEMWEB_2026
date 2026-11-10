<?php
$root = './';
$judulHalaman = 'Galeri Komponen & Template UI - DynaQuiz';

// Pilihan tampilan template dari query parameter ?layout=...
$layoutDipilih = $_GET['layout'] ?? 'publik';

// 1. Tampilkan sesuai template yang ingin diuji
if ($layoutDipilih === 'panel') {
    // Simulasi data sesi pengguna untuk panel
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['pengguna'] = [
        'nama' => 'Farrel (Pengembang UI)',
        'peran' => $_GET['peran'] ?? 'pengajar'
    ];
    $halamanAktif = 'dashboard';
    require_once __DIR__ . '/includes/partials/header-panel.php';
} elseif ($layoutDipilih === 'fokus') {
    $judulKuis = 'Uji Coba Mode Fokus - Matematika Dasar';
    $sisaWaktu = '14:59';
    $linkKeluar = 'galeri.php';
    require_once __DIR__ . '/includes/partials/header-fokus.php';
} elseif ($layoutDipilih === 'pelajar') {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['pengguna'] = [
        'nama' => 'Farrel Pelajar',
        'peran' => 'pelajar'
    ];
    $halamanAktif = 'dashboard';
    require_once __DIR__ . '/includes/partials/header-pelajar.php';
} else {
    // Default: Layout Publik
    $halamanAktif = 'galeri';
    require_once __DIR__ . '/includes/partials/header-publik.php';
}
?>

<div class="galeri-wrapper" style="padding: 20px 0;">
  <!-- Bagian Pengganti Mode Template -->
  <div class="card" style="margin-bottom: 24px;">
    <h2>Uji Coba Template Layout (Sesi 2)</h2>
    <p>Pilih salah satu layout di bawah untuk menguji apakah template bekerja dengan baik:</p>
    <div style="display: flex; gap: 10px; flex-wrap: wrap; margin-top: 12px;">
      <a href="galeri.php?layout=publik" class="btn <?= $layoutDipilih === 'publik' ? 'btn-primary' : 'btn-batal' ?>">Layout Publik</a>
      <a href="galeri.php?layout=pelajar" class="btn <?= $layoutDipilih === 'pelajar' ? 'btn-primary' : 'btn-batal' ?>">Layout Pelajar</a>
      <a href="galeri.php?layout=panel&peran=pengajar" class="btn <?= ($layoutDipilih === 'panel' && ($_GET['peran'] ?? '') !== 'admin') ? 'btn-primary' : 'btn-batal' ?>">Layout Panel (Pengajar)</a>
      <a href="galeri.php?layout=panel&peran=admin" class="btn <?= ($layoutDipilih === 'panel' && ($_GET['peran'] ?? '') === 'admin') ? 'btn-primary' : 'btn-batal' ?>">Layout Panel (Admin)</a>
      <a href="galeri.php?layout=fokus" class="btn <?= $layoutDipilih === 'fokus' ? 'btn-primary' : 'btn-batal' ?>">Layout Mode Fokus</a>
    </div>
  </div>

  <!-- Bagian 1: Tombol -->
  <div class="card" style="margin-bottom: 24px;">
    <h2>1. Tombol (Buttons)</h2>
    <p>Spesifikasi: Efek timbul claymorphism, efek tekan saat diklik, radius membulat.</p>
    <div style="display: flex; gap: 12px; flex-wrap: wrap; align-items: center; margin-top: 12px;">
      <button type="button" class="btn btn-primary">Tombol Utama</button>
      <button type="button" class="btn btn-secondary">Tombol Sekunder</button>
      <button type="button" class="btn btn-batal">Tombol Batal</button>
      <button type="button" class="btn btn-sm btn-primary">Tombol Kecil</button>
      <button type="button" class="btn btn-primary" disabled>Tombol Dinonaktifkan</button>
    </div>
  </div>

  <!-- Bagian 2: Kartu & Bento Grid -->
  <div class="card" style="margin-bottom: 24px;">
    <h2>2. Grid Bento & Kartu (Cards)</h2>
    <p>Spesifikasi: Layout bento box responsif dengan bayangan lembut clay.</p>
    <div class="grid-bento" style="margin-top: 16px;">
      <div class="card card-sorotan">
        <span class="badge badge-sedang">Kartu Sorotan</span>
        <h3 style="margin: 8px 0;">Kartu Utama Bento</h3>
        <p>Kartu ini memiliki aksen background sekunder dan bayangan lebih tegas untuk menarik perhatian pelajar.</p>
      </div>
      <div class="card">
        <span class="badge badge-mudah">Mudah</span>
        <h3 style="margin: 8px 0;">Kuis Pemula</h3>
        <p>Contoh kartu kuis standar untuk katalog dan dashboard.</p>
      </div>
      <div class="card">
        <span class="badge badge-sulit">Sulit</span>
        <h3 style="margin: 8px 0;">Kuis Mahir</h3>
        <p>Contoh kartu dengan badge tingkat kesulitan sulit.</p>
      </div>
    </div>
  </div>

  <!-- Bagian 3: Badge Status -->
  <div class="card" style="margin-bottom: 24px;">
    <h2>3. Lencana & Status (Badges)</h2>
    <p>Spesifikasi: Warna pastel kontras dengan teks gelap terbaca.</p>
    <div style="display: flex; gap: 10px; flex-wrap: wrap; align-items: center; margin-top: 12px;">
      <span class="badge badge-mudah">Mudah</span>
      <span class="badge badge-sedang">Sedang</span>
      <span class="badge badge-sulit">Sulit</span>
      <span class="badge badge-status badge-draft">Draft</span>
      <span class="badge badge-status badge-menunggu">Menunggu Review</span>
      <span class="badge badge-status badge-publish">Published</span>
      <span class="badge badge-status badge-ditolak">Ditolak</span>
    </div>
  </div>

  <!-- Bagian 4: Notifikasi Flash & Alert -->
  <div class="card" style="margin-bottom: 24px;">
    <h2>4. Pesan Notifikasi (Alerts)</h2>
    <div class="alert alert-sukses" style="margin-top: 12px;">
      <strong>Berhasil!</strong> Jawaban kuis Anda telah berhasil disimpan ke sistem.
    </div>
    <div class="alert alert-galat" style="margin-top: 12px;">
      <strong>Peringatan!</strong> Username atau kata sandi yang Anda masukkan salah.
    </div>
    <div class="alert alert-info" style="margin-top: 12px;">
      <strong>Info:</strong> Mode pengerjaan kuis sedang dalam tahap uji coba tema.
    </div>
  </div>

  <!-- Bagian 5: Form & Input -->
  <div class="card" style="margin-bottom: 24px;">
    <h2>5. Input Form & Kontrol</h2>
    <div style="display: flex; flex-direction: column; gap: 16px; margin-top: 16px; max-width: 500px;">
      <div class="form-group">
        <label for="inputContoh" class="form-label">Nama Lengkap</label>
        <input type="text" id="inputContoh" class="form-input" placeholder="Masukkan nama Anda...">
      </div>

      <div class="form-group">
        <label for="selectContoh" class="form-label">Mata Pelajaran</label>
        <select id="selectContoh" class="form-select">
          <option value="">-- Pilih Mata Pelajaran --</option>
          <option value="1">Matematika Dasar</option>
          <option value="2">Bahasa Indonesia</option>
          <option value="3">Ilmu Pengetahuan Alam</option>
        </select>
      </div>

      <div class="form-group">
        <label for="pesanContoh" class="form-label">Catatan Tambahan</label>
        <textarea id="pesanContoh" class="form-textarea" placeholder="Tuliskan catatan di sini..."></textarea>
      </div>
    </div>
  </div>
</div>

<?php
// Tutup halaman sesuai template
if ($layoutDipilih === 'panel') {
    require_once __DIR__ . '/includes/partials/footer-panel.php';
} elseif ($layoutDipilih === 'fokus') {
    require_once __DIR__ . '/includes/partials/footer-fokus.php';
} elseif ($layoutDipilih === 'pelajar') {
    require_once __DIR__ . '/includes/partials/footer-pelajar.php';
} else {
    require_once __DIR__ . '/includes/partials/footer-publik.php';
}
