<?php
$pageTitle = 'OneBlood - Sistem Informasi Donor Darah';
require_once __DIR__ . '/includes/header.php';
?>

<main>
    <section class="hero-wrapper">
        <div class="hero-container">
            <div class="hero-content">
                <h1 class="hero-title">Selamat Datang di <span class="highlight-text">One</span>Blood<br>
                    Sistem Donor Darah Terpadu</h1>
                <p class="hero-desc">Sistem Donor Darah Terpadu OneBlood merupakan Sistem Donor Darah
                    yang memudahkan masyarakat untuk mendapatkan informasi terkait donor darah,
                    stok darah, dan kegiatan donor darah di sekitar mereka.</p>
                <div class="hero-buttons">
                    <a href="<?= e(base_url('auth/register.php')) ?>" class="btn-pink">Daftar Pendonor</a>
                    <a href="#" class="btn-pink">Selengkapnya</a>
                </div>
            </div>
            <div class="hero-image">
                <img src="<?= e(base_url('assets/donor1.jpg')) ?>" alt="Ilustrasi Donor Darah">
            </div>
        </div>
        <div class="sliders-dots">
            <span class="dot active"></span>
            <span class="dot"></span>
        </div>
    </section>
    <section class="stokdarah">
        <div class="stokdarah-container">
            <div class="stokdarah-title">
                <h1>Stok Darah</h1>
                <p>Live Stok Darah</p>
            </div>
            <div class="stokdarah-content">
                <div class="blood-grid">
                    <div class="blood-card">
                        <div class="blood-icon">A</div>
                        <div class="blood-info">
                            <h4>Stok Darah A</h4>
                            <p class="status-empty">Belum Tersedia</p>
                        </div>
                    </div>
                    <div class="blood-card">
                        <div class="blood-icon">B</div>
                        <div class="blood-info">
                            <h4>Stok Darah B</h4>
                            <p class="status-available">Belum Tersedia</p>
                        </div>
                    </div>
                    <div class="blood-card">
                        <div class="blood-icon">O</div>
                        <div class="blood-info">
                            <h4>Stok Darah O</h4>
                            <p class="status-available">Belum Tersedia</p>
                        </div>
                    </div>
                    <div class="blood-card">
                        <div class="blood-icon">AB</div>
                        <div class="blood-info">
                            <h4>Stok Darah AB</h4>
                            <p class="status-empty">Belum Tersedia</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<?php
$hideSidebar = true;
require_once __DIR__ . '/includes/footer.php';
?>
