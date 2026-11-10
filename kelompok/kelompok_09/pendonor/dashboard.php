<?php
require_once __DIR__ . '/../config/auth_check.php';
if (!isLoggedIn()) {
    header('Location: ' . base_url('auth/login.php'));
    exit;
}
cekRole(['pendonor']);

$query = $db->prepare("SELECT COUNT(*) AS total, COUNT(CASE WHEN status_skrining = 'menunggu' THEN 1 END) AS menunggu,
    COUNT(CASE WHEN status_skrining = 'selesai' THEN 1 END) AS selesai FROM antrean_skrining WHERE user_id = ?");
$query->execute([currentUser()['id']]);
$ringkasan = $query->fetch();
$query = $db->prepare('SELECT kode_tiket, status_skrining, catatan_medis, nama_event, lokasi, tanggal, jam_mulai, jam_selesai
    FROM antrean_skrining a JOIN events e ON e.id = a.event_id
    WHERE a.user_id = ? ORDER BY e.tanggal DESC, a.id DESC LIMIT 5');
$query->execute([currentUser()['id']]);
$riwayat = $query->fetchAll();
$pageTitle = 'Dashboard Pendonor | OneBlood';
require __DIR__ . '/../includes/header.php';
?>
<main class="donor-dashboard">
    <section class="donor-welcome">
        <div class="donor-welcome-copy">
            <h1>Halo, <?= e(currentUser()['nama']) ?></h1>
            <p>Setiap donor membawa harapan. Pantau aktivitas dan siapkan jadwal donor berikutnya di sini.</p>
        </div>
        <a class="donor-welcome-link" href="booking.php">Cari jadwal donor <span aria-hidden="true">→</span></a>
        <span class="donor-welcome-decoration" aria-hidden="true">+</span>
    </section>

    <section class="donor-overview" aria-labelledby="donor-overview-title">
        <div class="donor-section-heading">
            <div>
                <p class="donor-eyebrow">AKTIVITAS ANDA</p>
                <h2 id="donor-overview-title">Ringkasan donor</h2>
            </div>
        </div>
        <div class="donor-stats">
            <article class="donor-stat">
                <div><p>Total booking</p><strong><?= (int)$ringkasan['total'] ?></strong></div>
            </article>
            <article class="donor-stat donor-stat-pending">
                <div><p>Menunggu skrining</p><strong><?= (int)$ringkasan['menunggu'] ?></strong></div>
            </article>
            <article class="donor-stat donor-stat-complete">
                <div><p>Donor selesai</p><strong><?= (int)$ringkasan['selesai'] ?></strong></div>
            </article>
        </div>
    </section>

    <section class="donor-shortcuts" aria-labelledby="donor-shortcuts-title">
        <div class="donor-section-heading">
            <div>
                <p class="donor-eyebrow">LAYANAN</p>
                <h2 id="donor-shortcuts-title">Apa yang ingin Anda lakukan?</h2>
            </div>
        </div>
        <div class="donor-action-grid">
            <a class="donor-action-card" href="booking.php">
                <span class="donor-action-icon" aria-hidden="true">01</span>
                <span class="donor-action-copy"><strong>Booking donor</strong><small>Pilih jadwal dan daftarkan diri untuk donor darah.</small></span>
                <span class="donor-action-arrow" aria-hidden="true">↗</span>
            </a>
            <a class="donor-action-card donor-action-card-warm" href="kartu.php">
                <span class="donor-action-icon" aria-hidden="true">02</span>
                <span class="donor-action-copy"><strong>Kartu pendonor</strong><small>Lihat atau cetak kartu identitas pendonor Anda.</small></span>
                <span class="donor-action-arrow" aria-hidden="true">↗</span>
            </a>
        </div>
    </section>

    <section class="donor-history" aria-labelledby="donor-history-title">
        <div class="donor-section-heading">
            <div>
                <p class="donor-eyebrow">PERJALANAN KEBAIKAN</p>
                <h2 id="donor-history-title">Riwayat donor terbaru</h2>
            </div>
            <a class="donor-history-link" href="riwayat.php">Lihat semua <span aria-hidden="true">→</span></a>
        </div>
        <div class="donor-history-panel">
            <?php if (!$riwayat): ?>
                <div class="donor-empty">
                    <span aria-hidden="true">♥</span>
                    <h3>Belum ada riwayat donor</h3>
                    <p>Mulai perjalanan donor Anda dengan memilih jadwal yang tersedia.</p>
                    <a class="donor-empty-link" href="booking.php">Booking donor pertama</a>
                </div>
            <?php else: ?>
                <div class="donor-table-wrap">
                    <table class="donor-history-table" aria-label="Lima riwayat booking donor terbaru">
                        <thead><tr><th scope="col">Kode tiket</th><th scope="col">Jadwal donor</th><th scope="col">Status</th><th scope="col">Catatan petugas</th></tr></thead>
                        <tbody>
                            <?php foreach ($riwayat as $item): ?>
                                <tr>
                                    <td><strong class="donor-ticket"><?= e($item['kode_tiket']) ?></strong></td>
                                    <td>
                                        <strong><?= e($item['nama_event']) ?></strong>
                                        <span class="donor-table-detail"><?= e($item['lokasi']) ?> · <?= e(tgl_indo($item['tanggal'])) ?>, <?= e(substr($item['jam_mulai'], 0, 5)) ?>–<?= e(substr($item['jam_selesai'], 0, 5)) ?> WIB</span>
                                    </td>
                                    <td><span class="donor-status"><?= e(ucfirst($item['status_skrining'])) ?></span></td>
                                    <td><?= e($item['catatan_medis'] ?: '—') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </section>
</main>
<?php
$hideSidebar = true;
require __DIR__ . '/../includes/footer.php';
