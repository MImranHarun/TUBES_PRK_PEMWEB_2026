<?php
require_once __DIR__ . '/../config/auth_check.php';
if (!isLoggedIn()) {
    header('Location: ' . base_url('auth/login.php'));
    exit;
}
cekRole(['pendonor']);

$userId = currentUser()['id'];
$_SESSION['booking_token'] ??= bin2hex(random_bytes(32));
$pertanyaan = [
    'q_sehat' => 'Apakah kamu merasa sehat hari ini?',
    'q_tidur_cukup' => 'Apakah kamu tidur setidaknya 5 jam?',
    'q_bebas_obat' => 'Apakah kamu bebas dari konsumsi antibiotik atau obat dalam 3 hari terakhir?',
];
$jawaban = array_map(fn($kolom) => $_POST[$kolom] ?? '', array_keys($pertanyaan));
$eventId = filter_input(INPUT_POST, 'event_id', FILTER_VALIDATE_INT);
$error = '';

// Gunakan waktu Jakarta dari konfigurasi proyek untuk menutup booking.
$terbuka = "status_event IN ('akan_datang', 'berlangsung') AND TIMESTAMP(tanggal, jam_selesai) > ?";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'] ?? '';
    if (!is_string($token) || !hash_equals($_SESSION['booking_token'], $token)) {
        http_response_code(403);
        $error = 'Form sudah tidak berlaku. Silakan coba lagi.';
    } elseif ($eventId <= 0 || array_filter($jawaban, fn($nilai) => !in_array($nilai, ['0', '1'], true))) {
        $error = 'Pilih jadwal dan jawab semua pertanyaan.';
    } else {
        try {
            $db->beginTransaction();
            // Kunci event agar dua booking terakhir tidak melampaui kuota.
            $query = $db->prepare('SELECT kuota FROM events WHERE id = ? FOR UPDATE');
            $query->execute([$eventId]);
            $kuota = $query->fetchColumn();
            $query = $db->prepare("SELECT id FROM events WHERE id = ? AND $terbuka");
            $query->execute([$eventId, date('Y-m-d H:i:s')]);
            if (!$query->fetchColumn()) {
                $error = 'Jadwal ini sudah ditutup atau tidak tersedia.';
            } else {
                $query = $db->prepare('SELECT COUNT(*) AS total, COUNT(CASE WHEN user_id = ? THEN 1 END) AS milik_saya FROM antrean_skrining WHERE event_id = ?');
                $query->execute([$userId, $eventId]);
                $antrean = $query->fetch();
                if ($antrean['milik_saya'] > 0) {
                    $error = 'Kamu sudah mendaftar pada jadwal ini.';
                } elseif ($antrean['total'] >= $kuota) {
                    $error = 'Kuota jadwal ini sudah penuh.';
                } else {
                    $tiket = 'OB-' . strtoupper(bin2hex(random_bytes(8)));
                    $db->prepare('INSERT INTO antrean_skrining (user_id, event_id, kode_tiket, q_sehat, q_tidur_cukup, q_bebas_obat)
                        VALUES (?, ?, ?, ?, ?, ?)')->execute([$userId, $eventId, $tiket, ...$jawaban]);
                    $db->commit();
                    setFlash('success', 'Booking berhasil. Kode tiket: ' . $tiket);
                    header('Location: riwayat.php', true, 303);
                    exit;
                }
            }
            $db->rollBack();
        } catch (PDOException $exception) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            error_log($exception->getMessage());
            $error = 'Booking gagal disimpan. Silakan coba lagi.';
        }
    }
}

$query = $db->prepare("SELECT e.id, nama_event, lokasi, tanggal, jam_mulai, jam_selesai,
    kuota - COUNT(a.id) AS sisa, COUNT(CASE WHEN a.user_id = ? THEN 1 END) AS sudah_daftar
    FROM events e LEFT JOIN antrean_skrining a ON a.event_id = e.id WHERE $terbuka
    GROUP BY e.id, nama_event, lokasi, tanggal, jam_mulai, jam_selesai, kuota ORDER BY tanggal, jam_mulai, e.id");
$query->execute([$userId, date('Y-m-d H:i:s')]);
$jadwal = $query->fetchAll();
$bisaBooking = array_filter($jadwal, fn($event) => $event['sisa'] > 0 && !$event['sudah_daftar']);
$pageTitle = 'Booking Donor | OneBlood';
require __DIR__ . '/../includes/header.php';
?>
<main class="donor-dashboard donor-page">
    <section class="donor-welcome">
        <div class="donor-welcome-copy">
            <p class="donor-eyebrow">JADWAL DONOR</p>
            <h1>Booking donor</h1>
            <p>Pilih jadwal yang sesuai dan lengkapi pertanyaan awal sebelum mendaftar.</p>
        </div>
        <a class="donor-welcome-link" href="dashboard.php">Kembali ke dashboard</a>
        <span class="donor-welcome-decoration" aria-hidden="true">+</span>
    </section>

    <?php if ($error !== ''): ?>
        <p class="donor-alert" role="alert"><?= e($error) ?></p>
    <?php endif; ?>

    <?php if (!$jadwal): ?>
        <section class="donor-content-panel donor-empty">
            <span aria-hidden="true">♥</span>
            <h2>Belum ada jadwal donor</h2>
            <p>Jadwal donor yang dapat dipesan akan ditampilkan di sini.</p>
        </section>
    <?php else: ?>
        <form class="donor-booking-form" method="post">
            <input type="hidden" name="token" value="<?= e($_SESSION['booking_token']) ?>">
            <section class="donor-content-panel">
                <div class="donor-section-heading">
                    <div>
                        <p class="donor-eyebrow">LANGKAH 1</p>
                        <h2>Pilih jadwal donor</h2>
                    </div>
                    <a class="donor-history-link" href="riwayat.php">Riwayat donor <span aria-hidden="true">→</span></a>
                </div>
                <div class="donor-event-list">
                    <?php foreach ($jadwal as $event): ?>
                        <?php $jadwalNonaktif = $event['sisa'] <= 0 || $event['sudah_daftar']; ?>
                        <label class="donor-event-option<?= $jadwalNonaktif ? ' is-disabled' : '' ?>">
                            <input type="radio" name="event_id" value="<?= (int)$event['id'] ?>" required
                                <?= $eventId === (int)$event['id'] ? 'checked' : '' ?> <?= $jadwalNonaktif ? 'disabled' : '' ?>>
                            <span class="donor-event-details">
                                <strong><?= e($event['nama_event']) ?></strong>
                                <span><?= e($event['lokasi']) ?></span>
                                <span><?= e(tgl_indo($event['tanggal'])) ?> · <?= e(substr($event['jam_mulai'], 0, 5)) ?>–<?= e(substr($event['jam_selesai'], 0, 5)) ?> WIB</span>
                            </span>
                            <span class="donor-event-capacity<?= $jadwalNonaktif ? ' is-disabled' : '' ?>">
                                <?= $event['sudah_daftar'] ? 'Sudah terdaftar' : ($event['sisa'] <= 0 ? 'Kuota penuh' : 'Sisa kuota ' . max(0, (int)$event['sisa'])) ?>
                            </span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </section>

            <?php if ($bisaBooking): ?>
                <section class="donor-content-panel">
                    <div class="donor-section-heading">
                        <div>
                            <p class="donor-eyebrow">LANGKAH 2</p>
                            <h2>Pertanyaan awal</h2>
                        </div>
                    </div>
                    <p class="donor-form-note">Jawaban ini menjadi informasi awal. Keputusan skrining tetap ditentukan oleh petugas.</p>
                    <div class="donor-screening-list">
                        <?php foreach ($pertanyaan as $kolom => $label): ?>
                            <fieldset class="donor-screening-question">
                                <legend><?= e($label) ?></legend>
                                <div class="donor-answer-options">
                                    <?php foreach (['1' => 'Ya', '0' => 'Tidak'] as $nilai => $teks): ?>
                                        <label>
                                            <input type="radio" name="<?= e($kolom) ?>" value="<?= $nilai ?>" required
                                                <?= ($_POST[$kolom] ?? '') === (string)$nilai ? 'checked' : '' ?>>
                                            <span><?= e($teks) ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </fieldset>
                        <?php endforeach; ?>
                    </div>
                    <button class="donor-submit-button" type="submit">Daftar donor <span aria-hidden="true">→</span></button>
                </section>
            <?php endif; ?>
        </form>
    <?php endif; ?>
</main>
<?php
$hideSidebar = true;
require __DIR__ . '/../includes/footer.php';
