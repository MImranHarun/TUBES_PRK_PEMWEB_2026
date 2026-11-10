<?php
require_once __DIR__ . '/../config/auth_check.php';
if (!isLoggedIn()) {
    header('Location: ' . base_url('auth/login.php'));
    exit;
}
cekRole(['pendonor']);

$query = $db->prepare('SELECT id, nama, gol_darah, rhesus, tgl_donor_terakhir FROM users WHERE id = ?');
$query->execute([currentUser()['id']]);
$profil = $query->fetch();
$pageTitle = 'Kartu Pendonor | OneBlood';
$bodyClass = 'donor-card-print-page';
require __DIR__ . '/../includes/header.php';
?>
<main class="donor-dashboard donor-page donor-card-page">
    <section class="donor-welcome no-print">
        <div class="donor-welcome-copy">
            <p class="donor-eyebrow">IDENTITAS PENDONOR</p>
            <h1>Kartu pendonor</h1>
            <p>Kartu identitas donor darah Anda. Cetak dan simpan untuk keperluan donor.</p>
        </div>
        <a class="donor-welcome-link" href="dashboard.php">Kembali ke dashboard <span aria-hidden="true">→</span></a>
        <span class="donor-welcome-decoration" aria-hidden="true">+</span>
    </section>

    <?php if (!$profil): ?>
        <section class="donor-content-panel donor-empty">
            <span aria-hidden="true">♥</span>
            <h2>Profil pendonor tidak ditemukan</h2>
            <p>Data kartu belum dapat ditampilkan.</p>
        </section>
    <?php else: ?>
        <section class="donor-membership-card" aria-label="Kartu identitas pendonor">
            <div class="donor-membership-top">
                <div class="donor-membership-brand">
                    <span class="donor-membership-drop" aria-hidden="true">+</span>
                    <span><strong>OneBlood</strong><small>DONOR CARD</small></span>
                </div>
                <span class="donor-membership-label">KARTU PENDONOR</span>
            </div>
            <div class="donor-membership-content">
                <div class="donor-membership-person">
                    <p>IDENTITAS PENDONOR</p>
                    <h2><?= e($profil['nama']) ?></h2>
                    <span>ID Pendonor #<?= (int)$profil['id'] ?></span>
                </div>
                <div class="donor-membership-blood">
                    <small>GOLONGAN DARAH</small>
                    <strong><?= e($profil['gol_darah'] ? $profil['gol_darah'] . $profil['rhesus'] : '—') ?></strong>
                </div>
            </div>
            <div class="donor-membership-bottom">
                <span>Donor terakhir</span>
                <strong><?= e(tgl_indo($profil['tgl_donor_terakhir'])) ?></strong>
            </div>
        </section>
        <div class="donor-card-actions no-print">
            <p>Simpan kartu ini atau cetak untuk ditunjukkan saat diperlukan.</p>
            <button class="donor-submit-button" type="button" onclick="window.print()">Cetak kartu <span aria-hidden="true">→</span></button>
        </div>
    <?php endif; ?>
</main>
<?php
$hideSidebar = true;
require __DIR__ . '/../includes/footer.php';
