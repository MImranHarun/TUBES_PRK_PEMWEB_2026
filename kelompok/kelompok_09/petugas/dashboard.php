<?php
require_once __DIR__ . '/../config/auth_check.php';
cekRole(['petugas']);

$pageTitle = 'Dashboard Petugas | OneBlood';
$user = currentUser();
$fiturPetugas = [
    [
        'judul' => 'Jadwal Event',
        'deskripsi' => 'Buat dan kelola jadwal kegiatan donor darah.',
        'tujuan' => 'event.php',
        'kelas' => 'staff-feature-event',
    ],
    [
        'judul' => 'Antrean Skrining',
        'deskripsi' => 'Tinjau booking pendonor dan proses skrining.',
        'tujuan' => 'antrean.php',
        'kelas' => 'staff-feature-screening',
    ],
    [
        'judul' => 'Skrining Medis',
        'deskripsi' => 'Catat hasil pemeriksaan dan keputusan skrining pendonor.',
        'tujuan' => 'skrining.php',
        'kelas' => 'staff-feature-medical',
    ],
    [
        'judul' => 'Inventaris Kantong Darah',
        'deskripsi' => 'Kelola data dan status kantong darah.',
        'tujuan' => 'kantong_darah.php',
        'kelas' => 'staff-feature-inventory',
    ],
    [
        'judul' => 'Monitoring Kedaluwarsa',
        'deskripsi' => 'Pantau kantong yang segera atau sudah kedaluwarsa.',
        'tujuan' => 'expired_monitor.php',
        'kelas' => 'staff-feature-expiry',
    ],
    [
        'judul' => 'Monitoring Suhu',
        'deskripsi' => 'Catat suhu kulkas penyimpanan darah.',
        'tujuan' => 'suhu_log.php',
        'kelas' => 'staff-feature-temperature',
    ],
    [
        'judul' => 'Distribusi Darah',
        'deskripsi' => 'Catat distribusi dan penggunaan kantong darah.',
        'tujuan' => 'distribusi.php',
        'kelas' => 'staff-feature-distribution',
    ],
    [
        'judul' => 'Kebutuhan Darah Mendesak',
        'deskripsi' => 'Kelola pengumuman kebutuhan darah darurat.',
        'tujuan' => 'sos_darurat.php',
        'kelas' => 'staff-feature-emergency',
    ],
];

require_once __DIR__ . '/../includes/header.php';
?>
<main class="staff-dashboard">
    <section class="staff-welcome">
        <div class="staff-welcome-copy">
            <p class="staff-eyebrow">RUANG KERJA PETUGAS</p>
            <h1>Halo, <?= e($user['nama'] ?? 'Petugas') ?>!</h1>
            <p>Pilih fitur operasional yang ingin Anda kelola. Seluruh menu petugas tersedia dari satu halaman ini.</p>
        </div>
        <span class="staff-welcome-decoration" aria-hidden="true">+</span>
    </section>

    <section class="staff-feature-section" aria-labelledby="staff-features-title">
        <div class="staff-section-heading">
            <div>
                <p class="staff-eyebrow">OPERASIONAL ONEBLOOD</p>
                <h2 id="staff-features-title">Menu petugas</h2>
            </div>
        </div>

        <div class="staff-feature-grid">
            <?php foreach ($fiturPetugas as $fitur): ?>
                <a class="staff-feature-card <?= e($fitur['kelas']) ?>" href="<?= e(base_url('petugas/' . $fitur['tujuan'])) ?>">
                    <span class="staff-feature-copy">
                        <strong><?= e($fitur['judul']) ?></strong>
                        <small><?= e($fitur['deskripsi']) ?></small>
                    </span>
                    <span class="staff-feature-arrow" aria-hidden="true">↗</span>
                </a>
            <?php endforeach; ?>
        </div>
    </section>
</main>
<?php
$hideSidebar = true;
require_once __DIR__ . '/../includes/footer.php';
