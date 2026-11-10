<?php
/**
 * Petugas mencatat hasil pemeriksaan fisik dan menetapkan status skrining.
 * Sistem tidak mengambil keputusan medis otomatis, keputusan tetap dibuat
 * oleh petugas yang berwenang berdasarkan pemeriksaan dan SOP yang berlaku.
 */

require_once __DIR__ . '/../config/auth_check.php';
cekRole(['petugas']);

$pageTitle = 'Skrining Medis | OneBlood';
$userLogin = currentUser();

function skriningPostString(string $key): string
{
    $value = $_POST[$key] ?? '';
    return is_string($value) ? trim($value) : '';
}

function skriningTextLength(string $value): int
{
    if (function_exists('mb_strlen')) {
        return mb_strlen($value, 'UTF-8');
    }
    if (preg_match_all('/./us', $value, $matches) !== false) {
        return count($matches[0]);
    }
    return strlen($value);
}

/**
 * Validasi angka positif sesuai kapasitas kolom DECIMAL pada database.
 * Nilai dikembalikan dalam format titik desimal agar dapat disimpan MariaDB.
 */
function skriningDecimal(string $raw, int $scale, float $maximum): ?string
{
    $raw = str_replace(',', '.', trim($raw));
    if ($raw === '' || !preg_match('/^\d{1,3}(?:\.\d{1,2})?$/', $raw)) {
        return null;
    }

    $number = (float) $raw;
    if ($number <= 0 || $number > $maximum) {
        return null;
    }

    return number_format($number, $scale, '.', '');
}

function skriningRedirect(string $type, string $message, int $id): never
{
    $_SESSION['skrining_flash'] = ['type' => $type, 'message' => $message];
    header('Location: ' . base_url('petugas/skrining.php?id=' . $id), true, 303);
    exit;
}

if (empty($_SESSION['skrining_csrf_token'])) {
    $_SESSION['skrining_csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = (string) $_SESSION['skrining_csrf_token'];

$flash = $_SESSION['skrining_flash'] ?? null;
unset($_SESSION['skrining_flash']);
$error = '';
$formData = [
    'berat_badan_aktual' => '',
    'tensi' => '',
    'hb' => '',
    'suhu_tubuh' => '',
    'status_skrining' => '',
    'catatan_medis' => '',
];

$antreanId = 0;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $antreanId = filter_var($_POST['antrean_id'] ?? null, FILTER_VALIDATE_INT);
    $antreanId = ($antreanId === false || $antreanId === null || $antreanId < 1) ? 0 : $antreanId;
} else {
    $antreanId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
    $antreanId = ($antreanId === false || $antreanId === null || $antreanId < 1) ? 0 : $antreanId;
}

if ($antreanId < 1) {
    http_response_code(400);
    $error = 'ID antrean tidak valid. Buka halaman ini melalui tombol pada daftar antrean.';
    $record = null;
} else {
    /* Ambil data booking, pendonor, event, serta hasil skrining sebelumnya. */
    $recordStatement = $db->prepare(
        'SELECT
            a.id, a.kode_tiket, a.q_sehat, a.q_tidur_cukup, a.q_bebas_obat,
            a.berat_badan_aktual, a.tensi, a.hb, a.suhu_tubuh,
            a.status_skrining, a.catatan_medis, a.petugas_id,
            a.waktu_daftar, a.waktu_periksa,
            u.nama AS nama_pendonor, u.email AS email_pendonor,
            u.no_hp, u.alamat, u.tgl_lahir, u.berat_badan AS berat_badan_profil,
            u.gol_darah, u.rhesus,
            e.nama_event, e.lokasi, e.tanggal AS tanggal_event,
            e.jam_mulai, e.jam_selesai,
            p.nama AS nama_petugas
        FROM antrean_skrining a
        JOIN users u ON u.id = a.user_id
        JOIN events e ON e.id = a.event_id
        LEFT JOIN users p ON p.id = a.petugas_id
        WHERE a.id = ?
        LIMIT 1'
    );
    $recordStatement->execute([$antreanId]);
    $record = $recordStatement->fetch();

    if (!$record) {
        http_response_code(404);
        $error = 'Data antrean tidak ditemukan. Data mungkin sudah dihapus atau ID tidak sesuai.';
    }
}

/* ================================================================
 * Simpan hasil pemeriksaan dan keputusan petugas.
 * ================================================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $record) {
    $postedToken = skriningPostString('csrf_token');

    $formData = [
        'berat_badan_aktual' => skriningPostString('berat_badan_aktual'),
        'tensi' => skriningPostString('tensi'),
        'hb' => skriningPostString('hb'),
        'suhu_tubuh' => skriningPostString('suhu_tubuh'),
        'status_skrining' => skriningPostString('status_skrining'),
        'catatan_medis' => skriningPostString('catatan_medis'),
    ];

    if ($postedToken === '' || !hash_equals($csrfToken, $postedToken)) {
        http_response_code(403);
        $error = 'Sesi formulir tidak valid atau sudah kedaluwarsa. Muat ulang halaman, lalu coba lagi.';
    } elseif ($record['status_skrining'] === 'selesai') {
        $error = 'Skrining tidak dapat diubah karena proses donor sudah ditandai selesai.';
    } else {
        $weight = skriningDecimal($formData['berat_badan_aktual'], 2, 999.99);
        $hb = skriningDecimal($formData['hb'], 1, 999.9);
        $temperature = skriningDecimal($formData['suhu_tubuh'], 1, 99.9);
        $tensi = $formData['tensi'];
        $decision = $formData['status_skrining'];
        $notes = $formData['catatan_medis'];

        if ($weight === null) {
            $error = 'Berat badan aktual wajib berupa angka lebih dari 0 dan maksimal 999,99 kg.';
        } elseif (!preg_match('/^\d{1,3}\s*\/\s*\d{1,3}$/', $tensi)) {
            $error = 'Tekanan darah wajib menggunakan format angka sistolik/diastolik, misalnya 120/80.';
        } elseif ($hb === null) {
            $error = 'Nilai Hb wajib berupa angka lebih dari 0 dan maksimal 999,9 g/dL.';
        } elseif ($temperature === null) {
            $error = 'Suhu tubuh wajib berupa angka lebih dari 0 dan maksimal 99,9 °C.';
        } elseif (!in_array($decision, ['lolos', 'ditolak'], true)) {
            $error = 'Pilih keputusan skrining: Lolos atau Tidak Lolos.';
        } elseif (skriningTextLength($notes) > 5000) {
            $error = 'Catatan medis maksimal 5.000 karakter.';
        } else {
            // Rapikan format tensi menjadi 120/80 tanpa spasi.
            $tensi = preg_replace('/\s+/', '', $tensi);

            try {
                $db->beginTransaction();

                $lock = $db->prepare('SELECT id, status_skrining FROM antrean_skrining WHERE id = ? FOR UPDATE');
                $lock->execute([$antreanId]);
                $current = $lock->fetch();

                if (!$current) {
                    $db->rollBack();
                    $error = 'Data antrean tidak ditemukan saat akan disimpan.';
                } elseif ($current['status_skrining'] === 'selesai') {
                    $db->rollBack();
                    $error = 'Data tidak dapat diubah karena proses donor sudah selesai.';
                } else {
                    $update = $db->prepare(
                        'UPDATE antrean_skrining
                         SET berat_badan_aktual = ?,
                             tensi = ?,
                             hb = ?,
                             suhu_tubuh = ?,
                             status_skrining = ?,
                             catatan_medis = ?,
                             petugas_id = ?,
                             waktu_periksa = NOW()
                         WHERE id = ?'
                    );
                    $update->execute([
                        $weight,
                        $tensi,
                        $hb,
                        $temperature,
                        $decision,
                        $notes !== '' ? $notes : null,
                        (int) $userLogin['id'],
                        $antreanId,
                    ]);
                    $db->commit();

                    $decisionLabel = $decision === 'lolos' ? 'Lolos skrining' : 'Tidak lolos skrining';
                    skriningRedirect('success', 'Hasil pemeriksaan berhasil disimpan. Keputusan: ' . $decisionLabel . '.', $antreanId);
                }
            } catch (PDOException $exception) {
                if ($db->inTransaction()) {
                    $db->rollBack();
                }
                error_log('[OneBlood skrining save] ' . $exception->getMessage());
                $error = 'Hasil skrining gagal disimpan karena terjadi kesalahan database. Periksa kembali koneksi dan struktur tabel.';
            }
        }
    }
}

/* Isi awal form dari database apabila form belum dikirim. */
if ($record && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $formData = [
        'berat_badan_aktual' => $record['berat_badan_aktual'] !== null ? (string) $record['berat_badan_aktual'] : '',
        'tensi' => (string) ($record['tensi'] ?? ''),
        'hb' => $record['hb'] !== null ? (string) $record['hb'] : '',
        'suhu_tubuh' => $record['suhu_tubuh'] !== null ? (string) $record['suhu_tubuh'] : '',
        'status_skrining' => (string) ($record['status_skrining'] === 'menunggu' ? '' : $record['status_skrining']),
        'catatan_medis' => (string) ($record['catatan_medis'] ?? ''),
    ];
}

$isFinalized = $record && $record['status_skrining'] === 'selesai';
$statusLabels = [
    'menunggu' => 'Menunggu Skrining',
    'lolos' => 'Lolos Skrining',
    'ditolak' => 'Tidak Lolos',
    'selesai' => 'Donor Selesai',
];

require_once __DIR__ . '/../includes/header.php';
?>
<style>
.screen-page { padding: 40px 28px 64px; }
.screen-page .inv-head { align-items: center; }
.screen-back { display: inline-flex; align-items: center; gap: 7px; margin-bottom: 15px; color: var(--darah); text-decoration: none; font-weight: 700; }
.screen-alert { max-width: 1180px; margin: 0 auto 18px; padding: 13px 16px; border-radius: 12px; background: var(--sukses-pucat); color: var(--sukses); }
.screen-alert.warning { background: var(--waspada-pucat); color: var(--waspada); }
.screen-alert.error { background: var(--darah-pucat); color: var(--darah); }
.screen-layout { max-width: 1180px; margin: 0 auto; display: grid; grid-template-columns: minmax(0, .9fr) minmax(0, 1.1fr); gap: 20px; align-items: start; }
.screen-panel { background: var(--cream); border: 1px solid var(--garis); border-radius: 20px; padding: 23px; box-shadow: var(--bayangan); }
.screen-panel h2 { margin: 0 0 17px; color: var(--darah); font-size: 1.25rem; }
.screen-person { display: flex; justify-content: space-between; gap: 14px; padding-bottom: 15px; border-bottom: 1px solid var(--garis); }
.screen-person h3 { margin: 0 0 4px; font-size: 1.2rem; }
.screen-subtle { color: var(--tinta-redup); font-size: .9rem; }
.screen-meta { display: grid; grid-template-columns: 1fr 1fr; gap: 14px 18px; margin-top: 17px; }
.screen-meta div { min-width: 0; }
.screen-meta dt { color: var(--tinta-redup); font-size: .81rem; }
.screen-meta dd { margin: 3px 0 0; font-weight: 650; overflow-wrap: anywhere; }
.screen-ticket { display: inline-block; padding: 6px 10px; background: #f5dce0; border-radius: 8px; color: var(--darah); font-weight: 800; letter-spacing: .03em; }
.screen-questions { display: grid; gap: 9px; margin-top: 18px; }
.screen-question { display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 10px 12px; background: #fff; border: 1px solid var(--garis); border-radius: 10px; }
.screen-answer { font-weight: 750; color: var(--sukses); }
.screen-answer.no { color: var(--darah); }
.screen-warning { margin-top: 13px; padding: 12px 14px; border-radius: 10px; background: var(--waspada-pucat); color: #704b0b; font-size: .88rem; line-height: 1.55; }
.screen-form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
.screen-field.full { grid-column: 1 / -1; }
.screen-field label { display: block; margin: 0 0 6px; font-weight: 700; font-size: .9rem; }
.screen-field input, .screen-field select, .screen-field textarea { width: 100%; box-sizing: border-box; border: 1px solid var(--garis); border-radius: 10px; padding: 11px 12px; font: inherit; color: var(--tinta); background: #fff; }
.screen-field textarea { min-height: 115px; resize: vertical; }
.screen-field input:focus, .screen-field select:focus, .screen-field textarea:focus { outline: 2px solid rgba(139,35,49,.18); border-color: var(--darah); }
.screen-help { display: block; margin-top: 5px; font-size: .79rem; color: var(--tinta-redup); line-height: 1.45; }
.screen-decision-note { margin: 15px 0; padding: 12px 14px; background: #fff; border: 1px solid var(--garis); border-radius: 10px; font-size: .86rem; line-height: 1.55; }
.screen-actions { display: flex; flex-wrap: wrap; gap: 10px; justify-content: flex-end; margin-top: 20px; }
.screen-actions .inv-btn { min-width: 150px; }
.screen-cancel { display: inline-flex; align-items: center; justify-content: center; padding: 10px 15px; border: 1px solid var(--garis); border-radius: 999px; color: var(--darah); text-decoration: none; font-weight: 700; }
.screen-readonly { padding: 13px 15px; border-radius: 12px; background: #e9edf2; color: #46505e; margin-bottom: 15px; }
@media (max-width: 850px) { .screen-layout { grid-template-columns: 1fr; } }
@media (max-width: 600px) { .screen-page { padding: 25px 13px 40px; } .screen-panel { padding: 17px; } .screen-form-grid, .screen-meta { grid-template-columns: 1fr; } .screen-actions { flex-direction: column-reverse; } .screen-actions .inv-btn, .screen-cancel { width: 100%; box-sizing: border-box; } }
</style>

<main class="inv-wrapper screen-page">
    <div class="inv-head">
        <div>
            <a class="screen-back" href="<?= e(base_url('petugas/antrean.php')) ?>">&larr; Kembali ke Antrean</a>
            <h1>Verifikasi Skrining Medis</h1>
            <p>Catat hasil pemeriksaan pendonor dan tetapkan keputusan skrining.</p>
        </div>
    </div>

    <?php if ($flash && is_array($flash)): ?>
        <?php $flashType = in_array($flash['type'] ?? '', ['success', 'warning', 'error'], true) ? $flash['type'] : 'success'; ?>
        <div class="screen-alert <?= e($flashType) ?>" role="status"><?= e((string) ($flash['message'] ?? '')) ?></div>
    <?php endif; ?>
    <?php if ($error !== ''): ?>
        <div class="screen-alert error" role="alert"><?= e($error) ?></div>
    <?php endif; ?>

    <?php if ($record): ?>
        <?php
            $questionRows = [
                'q_sehat' => 'Merasa sehat hari ini',
                'q_tidur_cukup' => 'Tidur setidaknya 5 jam',
                'q_bebas_obat' => 'Bebas antibiotik/obat dalam 3 hari terakhir',
            ];
        ?>
        <div class="screen-layout">
            <section class="screen-panel" aria-labelledby="donorInfoTitle">
                <h2 id="donorInfoTitle">Informasi Pendonor</h2>
                <div class="screen-person">
                    <div>
                        <h3><?= e($record['nama_pendonor']) ?></h3>
                        <div class="screen-subtle"><?= e($record['email_pendonor']) ?></div>
                    </div>
                    <span class="screen-ticket"><?= e($record['kode_tiket']) ?></span>
                </div>

                <dl class="screen-meta">
                    <div><dt>Nomor HP</dt><dd><?= e($record['no_hp'] ?: '-') ?></dd></div>
                    <div><dt>Tanggal Lahir</dt><dd><?= e($record['tgl_lahir'] ? tgl_indo($record['tgl_lahir']) : '-') ?></dd></div>
                    <div><dt>Golongan Darah</dt><dd><?= e(($record['gol_darah'] ?? '-') . (($record['rhesus'] ?? '') !== '' ? $record['rhesus'] : '')) ?></dd></div>
                    <div><dt>Berat Badan Profil</dt><dd><?= $record['berat_badan_profil'] !== null ? e((string) $record['berat_badan_profil']) . ' kg' : '-' ?></dd></div>
                    <div><dt>Event</dt><dd><?= e($record['nama_event']) ?></dd></div>
                    <div><dt>Tanggal Event</dt><dd><?= e(tgl_indo($record['tanggal_event'])) ?></dd></div>
                    <div><dt>Lokasi</dt><dd><?= e($record['lokasi']) ?></dd></div>
                    <div><dt>Waktu Pendaftaran</dt><dd><?= e($record['waktu_daftar'] ? date('d/m/Y H:i', strtotime($record['waktu_daftar'])) : '-') ?></dd></div>
                </dl>

                <h2 style="margin-top:24px;">Jawaban Pemeriksaan Awal</h2>
                <div class="screen-questions">
                    <?php foreach ($questionRows as $questionKey => $questionLabel): ?>
                        <?php $answerYes = (int) $record[$questionKey] === 1; ?>
                        <div class="screen-question">
                            <span><?= e($questionLabel) ?></span>
                            <strong class="screen-answer <?= $answerYes ? '' : 'no' ?>"><?= $answerYes ? 'Ya' : 'Tidak' ?></strong>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php if ((int) $record['q_sehat'] !== 1 || (int) $record['q_tidur_cukup'] !== 1 || (int) $record['q_bebas_obat'] !== 1): ?>
                    <div class="screen-warning"><strong>Perlu perhatian:</strong> terdapat jawaban awal “Tidak”. Tinjau kembali jawaban dan kondisi pendonor sesuai SOP sebelum menetapkan keputusan. Sistem tidak otomatis menolak pendonor.</div>
                <?php endif; ?>
            </section>

            <section class="screen-panel" aria-labelledby="medicalFormTitle">
                <h2 id="medicalFormTitle">Hasil Pemeriksaan Medis</h2>
                <?php if ($isFinalized): ?>
                    <div class="screen-readonly"><strong>Data terkunci.</strong> Proses donor sudah ditandai selesai sehingga hasil skrining tidak dapat diubah melalui halaman ini.</div>
                <?php endif; ?>

                <form method="post" action="<?= e(base_url('petugas/skrining.php?id=' . (int) $record['id'])) ?>">
                    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                    <input type="hidden" name="antrean_id" value="<?= (int) $record['id'] ?>">

                    <div class="screen-form-grid">
                        <div class="screen-field">
                            <label for="actualWeight">Berat Badan Aktual (kg)</label>
                            <input type="number" id="actualWeight" name="berat_badan_aktual" min="0.01" max="999.99" step="0.01" required value="<?= e($formData['berat_badan_aktual']) ?>" <?= $isFinalized ? 'disabled' : '' ?>>
                            <small class="screen-help">Masukkan hasil penimbangan saat pemeriksaan.</small>
                        </div>
                        <div class="screen-field">
                            <label for="bloodPressure">Tekanan Darah (mmHg)</label>
                            <input type="text" id="bloodPressure" name="tensi" maxlength="20" placeholder="120/80" pattern="[0-9]{1,3}\s*/\s*[0-9]{1,3}" required value="<?= e($formData['tensi']) ?>" <?= $isFinalized ? 'disabled' : '' ?>>
                            <small class="screen-help">Format sistolik/diastolik, contoh 120/80.</small>
                        </div>
                        <div class="screen-field">
                            <label for="hbValue">Hemoglobin / Hb (g/dL)</label>
                            <input type="number" id="hbValue" name="hb" min="0.1" max="999.9" step="0.1" required value="<?= e($formData['hb']) ?>" <?= $isFinalized ? 'disabled' : '' ?>>
                            <small class="screen-help">Catat hasil alat pemeriksaan Hb.</small>
                        </div>
                        <div class="screen-field">
                            <label for="bodyTemperature">Suhu Tubuh (&deg;C)</label>
                            <input type="number" id="bodyTemperature" name="suhu_tubuh" min="0.1" max="99.9" step="0.1" required value="<?= e($formData['suhu_tubuh']) ?>" <?= $isFinalized ? 'disabled' : '' ?>>
                            <small class="screen-help">Catat hasil pengukuran suhu tubuh.</small>
                        </div>
                        <div class="screen-field full">
                            <label for="medicalNotes">Catatan Medis</label>
                            <textarea id="medicalNotes" name="catatan_medis" maxlength="5000" placeholder="Catatan tambahan pemeriksaan (opsional)" <?= $isFinalized ? 'disabled' : '' ?>><?= e($formData['catatan_medis']) ?></textarea>
                        </div>
                        <div class="screen-field full">
                            <label for="screeningDecision">Keputusan Skrining</label>
                            <select id="screeningDecision" name="status_skrining" required <?= $isFinalized ? 'disabled' : '' ?>>
                                <option value="">Pilih keputusan petugas</option>
                                <option value="lolos" <?= $formData['status_skrining'] === 'lolos' ? 'selected' : '' ?>>Lolos Skrining</option>
                                <option value="ditolak" <?= $formData['status_skrining'] === 'ditolak' ? 'selected' : '' ?>>Tidak Lolos Skrining</option>
                            </select>
                            <small class="screen-help">Status ditetapkan oleh petugas berdasarkan pemeriksaan dan SOP yang berlaku.</small>
                        </div>
                    </div>

                    <div class="screen-decision-note">
                        <strong>Catatan penting:</strong> komentar pada struktur database mencantumkan Hb 12,5–17,0 g/dL dan suhu 36,0–37,5 &deg;C sebagai standar contoh. Nilai tersebut bukan pengganti SOP medis. Sistem ini hanya mencatat hasil dan keputusan petugas, tidak mendiagnosis atau menentukan kelayakan donor secara otomatis.
                    </div>

                    <div class="screen-actions">
                        <a class="screen-cancel" href="<?= e(base_url('petugas/antrean.php')) ?>">Kembali ke Antrean</a>
                        <?php if (!$isFinalized): ?>
                            <button class="inv-btn" type="submit">Simpan Hasil Skrining</button>
                        <?php endif; ?>
                    </div>
                </form>

                <?php if ($record['waktu_periksa']): ?>
                    <p class="screen-subtle" style="margin:17px 0 0;">Pemeriksaan terakhir: <?= e(date('d/m/Y H:i', strtotime($record['waktu_periksa']))) ?><?= $record['nama_petugas'] ? ' oleh ' . e($record['nama_petugas']) : '' ?>.</p>
                <?php endif; ?>
                <p class="screen-subtle" style="margin:8px 0 0;">Status saat ini: <strong><?= e($statusLabels[$record['status_skrining']] ?? $record['status_skrining']) ?></strong></p>
            </section>
        </div>
    <?php else: ?>
        <section class="inv-card" style="max-width:1180px;margin:0 auto;">
            <p>Halaman skrining tidak dapat ditampilkan karena data antrean tidak tersedia.</p>
            <p><a class="inv-btn" href="<?= e(base_url('petugas/antrean.php')) ?>">Kembali ke Antrean</a></p>
        </section>
    <?php endif; ?>
</main>
<?php
$hideSidebar = true;
require_once __DIR__ . '/../includes/footer.php';
?>
