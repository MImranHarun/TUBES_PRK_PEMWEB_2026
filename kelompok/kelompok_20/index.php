<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Beranda';

// Default nilai awal jika database kosong atau jika terjadi error
$totalLaporan   = 0;
$totalMenunggu  = 0;
$totalDiproses  = 0;
$totalSelesai   = 0;
$laporanTerbaru = [];
$pesanErrorDb   = null;

try {
    // Hitung statistik hanya untuk laporan publik (is_public = 1)
    $totalLaporan  = (int)$pdo->query("SELECT COUNT(*) FROM laporan WHERE is_public = 1")->fetchColumn();
    $totalMenunggu = (int)$pdo->query("SELECT COUNT(*) FROM laporan WHERE is_public = 1 AND status = 'menunggu'")->fetchColumn();
    $totalDiproses = (int)$pdo->query("SELECT COUNT(*) FROM laporan WHERE is_public = 1 AND status = 'diproses'")->fetchColumn();
    $totalSelesai  = (int)$pdo->query("SELECT COUNT(*) FROM laporan WHERE is_public = 1 AND status = 'selesai'")->fetchColumn();

    // Ambil 5 laporan publik terbaru (diurutkan created_at DESC dan id DESC)
    $sql = "SELECT l.id, l.judul, l.lokasi, l.jenis, l.status, l.created_at,
                   k.nama_kategori,
                   u.nama AS nama_pelapor
            FROM laporan l
            JOIN users u ON l.user_id = u.id
            LEFT JOIN kategori k ON l.kategori_id = k.id
            WHERE l.is_public = 1
            ORDER BY l.created_at DESC, l.id DESC
            LIMIT 5";
    $laporanTerbaru = $pdo->query($sql)->fetchAll();
} catch (PDOException $e) {
    // Jika koneksi/query gagal, tampilkan pesan umum tanpa membocorkan detail error teknis
    $pesanErrorDb   = 'Data belum dapat dimuat.';
    $totalLaporan   = 0;
    $totalMenunggu  = 0;
    $totalDiproses  = 0;
    $totalSelesai   = 0;
    $laporanTerbaru = [];
}

// Data kartu statistik untuk looping foreach
$kartuStatistik = [
    [
        'label' => 'Total Laporan',
        'nilai' => $totalLaporan,
        'warna' => 'var(--color-primary)'
    ],
    [
        'label' => 'Menunggu',
        'nilai' => $totalMenunggu,
        'warna' => '#6B5420'
    ],
    [
        'label' => 'Diproses',
        'nilai' => $totalDiproses,
        'warna' => '#34402A'
    ],
    [
        'label' => 'Selesai',
        'nilai' => $totalSelesai,
        'warna' => 'var(--color-primary)'
    ]
];

require_once __DIR__ . '/includes/header.php';
?>

<!-- Style khusus halaman beranda dengan variabel CSS yang tersedia -->
<style>
.beranda-hero {
    margin-bottom: 2.5rem;
    padding-bottom: 2rem;
    border-bottom: 1px solid var(--color-border);
}
.stat-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
}
.stat-card {
    background-color: var(--color-surface);
    border: 1px solid var(--color-border);
    border-radius: var(--radius);
    padding: 1.25rem 1rem;
    text-align: center;
}
.step-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 1.25rem;
}
.step-card {
    background-color: var(--color-surface);
    border: 1px solid var(--color-border);
    border-radius: var(--radius);
    padding: 1.25rem;
}
.table-laporan {
    min-width: 700px;
}
.empty-box {
    background-color: var(--color-surface);
    border: 1px dashed var(--color-border);
    border-radius: var(--radius);
    padding: 2.5rem 1.5rem;
    text-align: center;
}
</style>

<!-- Pesan error jika database bermasalah -->
<?php if ($pesanErrorDb): ?>
    <div class="alert alert-danger">
        <?= sanitize($pesanErrorDb) ?>
    </div>
<?php endif; ?>

<!-- Bagian Pembuka / Hero -->
<section class="beranda-hero">
    <div style="max-width: 850px;">
        <h1 style="font-size: 2.1rem; line-height: 1.25; color: var(--color-primary); margin-bottom: 0.85rem; letter-spacing: -0.5px;">
            Portal Pengaduan &amp; Aspirasi Publik FISIP
        </h1>
        <p style="font-size: 1.05rem; line-height: 1.65; color: var(--color-text-muted); margin-bottom: 1.5rem;">
            Layanan resmi Fakultas Ilmu Sosial dan Ilmu Politik untuk menyampaikan aspirasi dan pengaduan sarana prasarana perkuliahan. Sampaikan masukan secara terbuka dan pantau penanganannya langsung hingga selesai.
        </p>
        <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
            <?php if (!isAdmin()): ?>
                <!-- Tombol ini hanya tampil untuk guest dan pengguna biasa, disembunyikan untuk admin -->
                <a href="/user/kirim.php" class="btn btn-primary">Sampaikan Laporan</a>
            <?php endif; ?>
            <a href="/laporan_publik.php" class="btn btn-secondary">Lihat Laporan Publik</a>
        </div>
    </div>
</section>

<!-- Statistik Penanganan Laporan -->
<section style="margin-bottom: 2.75rem;">
    <h2 style="font-size: 1.25rem; font-weight: 700; color: var(--color-primary); margin-bottom: 1rem;">
        Statistik Penanganan
    </h2>

    <div class="stat-grid">
        <?php foreach ($kartuStatistik as $kartu): ?>
            <div class="stat-card">
                <div style="font-size: 2.2rem; font-weight: 700; color: <?= $kartu['warna'] ?>; line-height: 1;">
                    <?= $kartu['nilai'] ?>
                </div>
                <div style="font-size: 0.85rem; font-weight: 600; color: var(--color-text-muted); margin-top: 0.5rem; text-transform: uppercase; letter-spacing: 0.5px;">
                    <?= sanitize($kartu['label']) ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- Tata Cara Melaporkan -->
<section style="margin-bottom: 2.75rem;">
    <div style="margin-bottom: 1.25rem;">
        <h2 style="font-size: 1.25rem; font-weight: 700; color: var(--color-primary); margin-bottom: 0.35rem;">
            Tata Cara Penyampaian Laporan
        </h2>
        <p style="color: var(--color-text-muted); font-size: 0.95rem;">
            Empat tahapan utama dalam proses penyampaian keluhan dan aspirasi warga kampus FISIP:
        </p>
    </div>

    <div class="step-grid">
        <div class="step-card">
            <div style="font-size: 1.35rem; font-weight: 700; color: var(--color-secondary); margin-bottom: 0.4rem;">
                01
            </div>
            <h3 style="font-size: 1rem; font-weight: 700; color: var(--color-primary); margin-bottom: 0.5rem;">
                Masuk ke Akun
            </h3>
            <p style="font-size: 0.9rem; color: var(--color-text-muted); line-height: 1.55; margin: 0;">
                <?php if (!isLogin()): ?>
                    Silakan <a href="/login.php" style="color: var(--color-primary); font-weight: 600;">Masuk</a> atau <a href="/register.php" style="color: var(--color-primary); font-weight: 600;">Daftar Akun Baru</a> untuk dapat mengirim dan memantau perkembangan laporan Anda.
                <?php else: ?>
                    Anda sedang masuk sebagai <strong><?= sanitize($_SESSION['nama'] ?? 'Pengguna') ?></strong>.
                <?php endif; ?>
            </p>
        </div>

        <div class="step-card">
            <div style="font-size: 1.35rem; font-weight: 700; color: var(--color-secondary); margin-bottom: 0.4rem;">
                02
            </div>
            <h3 style="font-size: 1rem; font-weight: 700; color: var(--color-primary); margin-bottom: 0.5rem;">
                Tulis Laporan
            </h3>
            <p style="font-size: 0.9rem; color: var(--color-text-muted); line-height: 1.55; margin: 0;">
                Pilih jenis laporan, tentukan kategori, sebutkan lokasi secara jelas, dan lampirkan foto bukti pendukung.
            </p>
        </div>

        <div class="step-card">
            <div style="font-size: 1.35rem; font-weight: 700; color: var(--color-secondary); margin-bottom: 0.4rem;">
                03
            </div>
            <h3 style="font-size: 1rem; font-weight: 700; color: var(--color-primary); margin-bottom: 0.5rem;">
                Tindak Lanjut
            </h3>
            <p style="font-size: 0.9rem; color: var(--color-text-muted); line-height: 1.55; margin: 0;">
                Admin fakultas memeriksa laporan dan memperbarui status penanganan secara berkala.
            </p>
        </div>

        <div class="step-card">
            <div style="font-size: 1.35rem; font-weight: 700; color: var(--color-secondary); margin-bottom: 0.4rem;">
                04
            </div>
            <h3 style="font-size: 1rem; font-weight: 700; color: var(--color-primary); margin-bottom: 0.5rem;">
                Tanggapan Resmi
            </h3>
            <p style="font-size: 0.9rem; color: var(--color-text-muted); line-height: 1.55; margin: 0;">
                Pelapor dan publik dapat membaca tanggapan serta hasil penyelesaian pada rincian laporan.
            </p>
        </div>
    </div>
</section>

<!-- 5 Laporan Publik Terbaru -->
<section style="margin-bottom: 1rem;">
    <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 1rem; flex-wrap: wrap; gap: 0.5rem;">
        <h2 style="font-size: 1.25rem; font-weight: 700; color: var(--color-primary); margin: 0;">
            Laporan Publik Terbaru
        </h2>
        <a href="/laporan_publik.php" style="font-size: 0.9rem; font-weight: 600; color: var(--color-primary); text-decoration: none;">
            Lihat Semua Laporan &rarr;
        </a>
    </div>

    <?php if (empty($laporanTerbaru)): ?>
        <div class="empty-box">
            <p style="color: var(--color-primary); font-weight: 600; margin-bottom: 0.35rem;">
                Belum ada laporan publik yang terdaftar
            </p>
            <p style="font-size: 0.9rem; color: var(--color-text-muted); margin: 0;">
                Seluruh aspirasi dan pengaduan publik yang disampaikan warga kampus akan ditampilkan di sini.
            </p>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table-laporan">
                <thead>
                    <tr>
                        <th style="width: 13%;">Tanggal</th>
                        <th style="width: 14%;">Jenis</th>
                        <th>Judul & Lokasi</th>
                        <th style="width: 18%;">Kategori</th>
                        <th style="width: 15%;">Pelapor</th>
                        <th style="width: 12%; text-align: center;">Status</th>
                        <th style="width: 10%; text-align: center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($laporanTerbaru as $lap): ?>
                        <tr>
                            <td style="font-size: 0.85rem; color: var(--color-text-muted); white-space: nowrap;">
                                <?= date('d/m/Y', strtotime($lap['created_at'])) ?>
                            </td>
                            <td style="text-transform: capitalize; font-size: 0.85rem; font-weight: 600; color: var(--color-primary);">
                                <?= sanitize($lap['jenis']) ?>
                            </td>
                            <td>
                                <strong style="color: var(--color-text);"><?= sanitize($lap['judul']) ?></strong>
                                <div style="font-size: 0.85rem; color: var(--color-text-muted); margin-top: 0.15rem;">
                                    Lokasi: <?= sanitize($lap['lokasi'] ?? '-') ?>
                                </div>
                            </td>
                            <td style="font-size: 0.9rem;">
                                <?= sanitize($lap['nama_kategori'] ?? '-') ?>
                            </td>
                            <td style="font-size: 0.9rem;">
                                <?= sanitize($lap['nama_pelapor']) ?>
                            </td>
                            <td style="text-align: center;">
                                <?= badgeStatus($lap['status']) ?>
                            </td>
                            <td style="text-align: center;">
                                <a href="/detail.php?id=<?= (int)$lap['id'] ?>" class="btn btn-secondary btn-sm">
                                    Lihat
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
