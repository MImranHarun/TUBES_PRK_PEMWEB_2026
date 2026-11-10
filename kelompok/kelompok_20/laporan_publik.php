<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Laporan Publik';
require_once __DIR__ . '/includes/header.php';
?>

<!-- Header Halaman & Form Pencarian Live Search -->
<section style="margin-bottom: 2rem;">
    <div style="margin-bottom: 1.25rem;">
        <h1 style="font-size: 1.85rem; color: var(--color-primary); margin-bottom: 0.35rem; letter-spacing: -0.5px;">
            Daftar Laporan Publik
        </h1>
        <p style="color: var(--color-text-muted); font-size: 0.95rem; margin: 0;">
            Semua aspirasi dan pengaduan terbuka yang disampaikan warga kampus FISIP dapat dipantau dan ditelusuri di sini secara transparan.
        </p>
    </div>

    <!-- Kolom Pencarian Instan (Live Search dengan Debounce 300ms) -->
    <div class="card" style="margin-bottom: 1rem; padding: 1.25rem;">
        <label for="search-input" class="form-label">Cari Laporan</label>
        <div style="position: relative;">
            <input 
                type="text" 
                id="search-input" 
                class="form-control" 
                placeholder="Ketik kata kunci judul, rincian isi, atau lokasi kejadian..." 
                autocomplete="off"
            >
        </div>
        <div id="search-info" style="font-size: 0.85rem; color: var(--color-text-muted); margin-top: 0.5rem;">
            Memuat data...
        </div>
    </div>
</section>

<!-- Kontainer Tempat Hasil Live Search Dirender oleh app.js -->
<section id="laporan-container">
    <div style="text-align: center; padding: 2.5rem 1rem; color: var(--color-text-muted);">
        <em>Memuat data dari server...</em>
    </div>
</section>

<!-- Muat Script Live Search dengan atribut defer sesuai panduan proyek -->
<script src="/assets/js/app.js" defer></script>

<?php
require_once __DIR__ . '/includes/footer.php';
?>

