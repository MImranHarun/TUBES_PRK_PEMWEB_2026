<?php
require_once __DIR__ . '/../config/auth_check.php';
if (!isLoggedIn()) {
    header('Location: ' . base_url('auth/login.php'));
    exit;
}
cekRole(['pendonor']);

$query = $db->prepare('SELECT kode_tiket, status_skrining, catatan_medis, nama_event, lokasi, tanggal, jam_mulai, jam_selesai
    FROM antrean_skrining a JOIN events e ON e.id = a.event_id WHERE a.user_id = ? ORDER BY e.tanggal DESC, a.id DESC');
$query->execute([currentUser()['id']]);
$riwayat = $query->fetchAll();
$pageTitle = 'Riwayat Donor | OneBlood';
require __DIR__ . '/../includes/header.php';
?>
<main class="donor-dashboard donor-page">
    <section class="donor-welcome">
        <div class="donor-welcome-copy">
            <p class="donor-eyebrow">PERJALANAN KEBAIKAN</p>
            <h1>Riwayat donor</h1>
            <p>Lihat kembali jadwal, status skrining, dan catatan dari petugas.</p>
        </div>
        <a class="donor-welcome-link" href="dashboard.php">Kembali ke dashboard <span aria-hidden="true">→</span></a>
        <span class="donor-welcome-decoration" aria-hidden="true">+</span>
    </section>

    <section class="donor-history donor-page-history">
        <div class="donor-section-heading">
            <div>
                <p class="donor-eyebrow">CATATAN ANDA</p>
                <h2>Semua aktivitas donor</h2>
            </div>
            <a class="donor-history-link" href="booking.php">Booking donor <span aria-hidden="true">→</span></a>
        </div>
        <div class="donor-history-panel">
            <?php if (!$riwayat): ?>
                <div class="donor-empty">
                    <span aria-hidden="true">♥</span>
                    <h3>Belum ada riwayat donor</h3>
                    <p>Aktivitas booking dan skrining akan muncul setelah Anda mendaftar.</p>
                    <a class="donor-empty-link" href="booking.php">Cari jadwal donor</a>
                </div>
            <?php else: ?>
                <div class="donor-table-wrap">
                    <table class="donor-history-table" aria-label="Riwayat booking dan hasil skrining">
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
