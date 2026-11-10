<?php

require_once __DIR__ . '/../config/auth_check.php';
cekRole(['petugas']);

$pageTitle = 'Manajemen Event Donor | OneBlood';
$userLogin = currentUser();

$allowedStatuses = [
    'akan_datang' => 'Akan Datang',
    'berlangsung' => 'Berlangsung',
    'selesai' => 'Selesai',
];

/** Ambil input POST hanya jika nilainya berupa string. */
function eventPostString(string $key): string
{
    $value = $_POST[$key] ?? '';
    return is_string($value) ? trim($value) : '';
}

/** Validasi tanggal kalender dengan format YYYY-MM-DD. */
function eventIsValidDate(string $date): bool
{
    $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
    return $parsed !== false && $parsed->format('Y-m-d') === $date;
}

/** Panjang karakter UTF-8, dengan fallback jika mbstring tidak tersedia. */
function eventTextLength(string $value): int
{
    if (function_exists('mb_strlen')) {
        return mb_strlen($value, 'UTF-8');
    }

    if (preg_match_all('/./us', $value, $matches) !== false) {
        return count($matches[0]);
    }

    return strlen($value);
}

/** Redirect setelah operasi yang berhasil atau gagal pada aksi hapus. */
function eventRedirectWithMessage(string $type, string $message): never
{
    $_SESSION['event_flash'] = [
        'type' => $type,
        'message' => $message,
    ];

    header('Location: ' . base_url('petugas/event.php'), true, 303);
    exit;
}

if (empty($_SESSION['event_csrf_token'])) {
    $_SESSION['event_csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = (string) $_SESSION['event_csrf_token'];

$flash = $_SESSION['event_flash'] ?? null;
unset($_SESSION['event_flash']);

$error = '';
$formOpen = false;
$formMode = 'create';
$formEventId = 0;
$formData = [
    'nama_event' => '',
    'lokasi' => '',
    'tanggal' => date('Y-m-d'),
    'jam_mulai' => '',
    'jam_selesai' => '',
    'kuota' => '50',
    'status_event' => 'akan_datang',
];

/* ================================================================
 * PROSES FORM: CREATE, UPDATE, DAN DELETE
 * ================================================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedToken = eventPostString('csrf_token');

    if ($postedToken === '' || !hash_equals($csrfToken, $postedToken)) {
        http_response_code(403);
        $error = 'Sesi formulir tidak valid atau sudah kedaluwarsa. Muat ulang halaman, lalu coba lagi.';
    } else {
        $action = eventPostString('action');

        if ($action === 'delete') {
            $eventId = filter_var($_POST['event_id'] ?? null, FILTER_VALIDATE_INT);

            if ($eventId === false || $eventId === null || $eventId < 1) {
                eventRedirectWithMessage('error', 'ID event tidak valid.');
            }

            try {
                $db->beginTransaction();

                // Kunci baris event. Proses booking juga mengunci baris ini
                // sebelum menghitung kuota, sehingga penghapusan tidak
                // berlomba dengan booking baru.
                $lockEvent = $db->prepare('SELECT id FROM events WHERE id = ? FOR UPDATE');
                $lockEvent->execute([$eventId]);
                $eventExists = $lockEvent->fetchColumn();

                if (!$eventExists) {
                    $db->rollBack();
                    eventRedirectWithMessage('error', 'Event tidak ditemukan atau sudah dihapus.');
                }

                $countBookings = $db->prepare('SELECT COUNT(*) FROM antrean_skrining WHERE event_id = ?');
                $countBookings->execute([$eventId]);
                $totalBookings = (int) $countBookings->fetchColumn();

                if ($totalBookings > 0) {
                    $db->rollBack();
                    eventRedirectWithMessage(
                        'warning',
                        'Event tidak dapat dihapus karena sudah memiliki ' . $totalBookings . ' pendaftar. Ubah statusnya menjadi Selesai bila event tidak lagi dibuka.'
                    );
                }

                $deleteEvent = $db->prepare('DELETE FROM events WHERE id = ?');
                $deleteEvent->execute([$eventId]);
                $db->commit();

                eventRedirectWithMessage('success', 'Event berhasil dihapus.');
            } catch (PDOException $exception) {
                if ($db->inTransaction()) {
                    $db->rollBack();
                }
                error_log('[OneBlood event delete] ' . $exception->getMessage());
                eventRedirectWithMessage('error', 'Event gagal dihapus karena terjadi kesalahan database.');
            }
        }

        if ($action === 'create' || $action === 'update') {
            $formOpen = true;
            $formMode = $action;
            $formEventId = filter_var($_POST['event_id'] ?? null, FILTER_VALIDATE_INT) ?: 0;

            $formData = [
                'nama_event' => eventPostString('nama_event'),
                'lokasi' => eventPostString('lokasi'),
                'tanggal' => eventPostString('tanggal'),
                'jam_mulai' => eventPostString('jam_mulai'),
                'jam_selesai' => eventPostString('jam_selesai'),
                'kuota' => eventPostString('kuota'),
                'status_event' => eventPostString('status_event'),
            ];

            $namaEvent = $formData['nama_event'];
            $lokasi = $formData['lokasi'];
            $tanggal = $formData['tanggal'];
            $jamMulai = $formData['jam_mulai'];
            $jamSelesai = $formData['jam_selesai'];
            $kuotaRaw = $formData['kuota'];
            $statusEvent = $formData['status_event'];

            if ($action === 'update' && $formEventId < 1) {
                $error = 'ID event yang akan diperbarui tidak valid.';
            } elseif ($namaEvent === '' || $lokasi === '' || $tanggal === '' || $jamMulai === '' || $jamSelesai === '' || $kuotaRaw === '' || $statusEvent === '') {
                $error = 'Semua kolom wajib diisi.';
            } elseif (eventTextLength($namaEvent) > 150) {
                $error = 'Nama event maksimal 150 karakter.';
            } elseif (eventTextLength($lokasi) > 200) {
                $error = 'Lokasi maksimal 200 karakter.';
            } elseif (!eventIsValidDate($tanggal)) {
                $error = 'Tanggal event tidak valid.';
            } elseif (!preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $jamMulai) || !preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $jamSelesai)) {
                $error = 'Jam mulai dan jam selesai harus menggunakan format waktu yang valid.';
            } elseif ($jamSelesai <= $jamMulai) {
                $error = 'Jam selesai harus lebih akhir daripada jam mulai.';
            } elseif (!preg_match('/^\d+$/', $kuotaRaw) || (int) $kuotaRaw < 1 || (int) $kuotaRaw > 100000) {
                $error = 'Kuota harus berupa bilangan bulat antara 1 sampai 100.000.';
            } elseif (!array_key_exists($statusEvent, $allowedStatuses)) {
                $error = 'Status event tidak dikenali.';
            } else {
                $kuota = (int) $kuotaRaw;

                try {
                    if ($action === 'create') {
                        $insertEvent = $db->prepare(
                            'INSERT INTO events (nama_event, lokasi, tanggal, jam_mulai, jam_selesai, kuota, status_event)
                             VALUES (:nama_event, :lokasi, :tanggal, :jam_mulai, :jam_selesai, :kuota, :status_event)'
                        );
                        $insertEvent->execute([
                            ':nama_event' => $namaEvent,
                            ':lokasi' => $lokasi,
                            ':tanggal' => $tanggal,
                            ':jam_mulai' => $jamMulai . ':00',
                            ':jam_selesai' => $jamSelesai . ':00',
                            ':kuota' => $kuota,
                            ':status_event' => $statusEvent,
                        ]);

                        eventRedirectWithMessage('success', 'Jadwal event donor berhasil ditambahkan.');
                    }

                    // Penguncian event menjaga pemeriksaan kuota tetap konsisten
                    // dengan proses booking pendonor.
                    $db->beginTransaction();
                    $lockEvent = $db->prepare('SELECT id FROM events WHERE id = ? FOR UPDATE');
                    $lockEvent->execute([$formEventId]);

                    if (!$lockEvent->fetchColumn()) {
                        $db->rollBack();
                        $error = 'Event yang akan diperbarui tidak ditemukan.';
                    } else {
                        $countBookings = $db->prepare('SELECT COUNT(*) FROM antrean_skrining WHERE event_id = ?');
                        $countBookings->execute([$formEventId]);
                        $totalBookings = (int) $countBookings->fetchColumn();

                        if ($kuota < $totalBookings) {
                            $db->rollBack();
                            $error = 'Kuota tidak boleh lebih kecil daripada jumlah pendaftar saat ini (' . $totalBookings . ' orang).';
                        } else {
                            $updateEvent = $db->prepare(
                                'UPDATE events
                                 SET nama_event = :nama_event,
                                     lokasi = :lokasi,
                                     tanggal = :tanggal,
                                     jam_mulai = :jam_mulai,
                                     jam_selesai = :jam_selesai,
                                     kuota = :kuota,
                                     status_event = :status_event
                                 WHERE id = :id'
                            );
                            $updateEvent->execute([
                                ':nama_event' => $namaEvent,
                                ':lokasi' => $lokasi,
                                ':tanggal' => $tanggal,
                                ':jam_mulai' => $jamMulai . ':00',
                                ':jam_selesai' => $jamSelesai . ':00',
                                ':kuota' => $kuota,
                                ':status_event' => $statusEvent,
                                ':id' => $formEventId,
                            ]);
                            $db->commit();

                            eventRedirectWithMessage('success', 'Perubahan jadwal event berhasil disimpan.');
                        }
                    }
                } catch (PDOException $exception) {
                    if ($db->inTransaction()) {
                        $db->rollBack();
                    }
                    error_log('[OneBlood event save] ' . $exception->getMessage());
                    $error = 'Data event gagal disimpan karena terjadi kesalahan database. Silakan periksa kembali dan coba lagi.';
                }
            }
        } elseif ($action !== 'delete') {
            $error = 'Aksi formulir tidak dikenali.';
        }
    }
}

/* ================================================================
 * DATA UNTUK DASHBOARD DAN TABEL
 * ================================================================ */
$stats = $db->query(
    "SELECT
        COUNT(*) AS total_event,
        COALESCE(SUM(status_event = 'akan_datang'), 0) AS akan_datang,
        COALESCE(SUM(status_event = 'berlangsung'), 0) AS berlangsung,
        COALESCE(SUM(status_event = 'selesai'), 0) AS selesai
     FROM events"
)->fetch();

$search = isset($_GET['q']) && is_string($_GET['q']) ? trim($_GET['q']) : '';
$statusFilter = isset($_GET['status']) && is_string($_GET['status']) ? $_GET['status'] : '';
if (!array_key_exists($statusFilter, $allowedStatuses)) {
    $statusFilter = '';
}

$where = [];
$params = [];

if ($search !== '') {
    $where[] = '(e.nama_event LIKE :keyword_nama OR e.lokasi LIKE :keyword_lokasi)';
    $keyword = '%' . $search . '%';
    $params[':keyword_nama'] = $keyword;
    $params[':keyword_lokasi'] = $keyword;
}

if ($statusFilter !== '') {
    $where[] = 'e.status_event = :status_event';
    $params[':status_event'] = $statusFilter;
}

$sqlEvents =
    'SELECT e.id, e.nama_event, e.lokasi, e.tanggal, e.jam_mulai, e.jam_selesai,
            e.kuota, e.status_event, e.created_at,
            (SELECT COUNT(*) FROM antrean_skrining a WHERE a.event_id = e.id) AS jumlah_pendaftar
     FROM events e';

if ($where) {
    $sqlEvents .= ' WHERE ' . implode(' AND ', $where);
}

$sqlEvents .= " ORDER BY
    CASE e.status_event
        WHEN 'berlangsung' THEN 0
        WHEN 'akan_datang' THEN 1
        ELSE 2
    END ASC,
    e.tanggal ASC,
    e.jam_mulai ASC,
    e.id DESC";

$listEvents = $db->prepare($sqlEvents);
$listEvents->execute($params);
$events = $listEvents->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<style>
    .event-page { padding-top: var(--r-6); }
    .event-page .inv-head { align-items: center; gap: 18px; }
    .event-page .inv-head p { max-width: 760px; line-height: 1.55; }
    .event-primary-btn, .event-secondary-btn, .event-danger-btn {
        display: inline-flex; align-items: center; justify-content: center; gap: 8px;
        border: 0; border-radius: 999px; padding: 11px 18px; font: inherit;
        font-weight: 700; text-decoration: none; cursor: pointer; transition: .18s ease;
    }
    .event-primary-btn { background: var(--darah); color: var(--cream); }
    .event-primary-btn:hover { background: var(--darah-tua); transform: translateY(-1px); }
    .event-secondary-btn { background: var(--info-pucat); color: var(--info); }
    .event-secondary-btn:hover { filter: brightness(.97); }
    .event-danger-btn { background: var(--darah-pucat); color: var(--darah); }
    .event-danger-btn:hover { background: #edc9c0; }
    .event-stats { max-width: 1600px; margin: 0 auto var(--r-5); display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: var(--r-4); }
    .event-stat { min-width: 0; padding: 20px 22px; background: var(--cream); border: 1px solid var(--garis); border-radius: 18px; box-shadow: 0 8px 22px rgba(9,25,69,.07); }
    .event-stat span { display: block; color: var(--darah); font-size: 2rem; font-weight: 800; line-height: 1.1; }
    .event-stat p { margin: 7px 0 0; color: var(--tinta-redup); font-size: var(--t-sm); }
    .event-toolbar { display: flex; align-items: end; justify-content: space-between; gap: 14px; flex-wrap: wrap; margin-bottom: 20px; }
    .event-filter { display: flex; align-items: end; gap: 10px; flex-wrap: wrap; flex: 1; }
    .event-filter-field { display: grid; gap: 6px; min-width: 180px; }
    .event-filter-field.search-field { flex: 1; min-width: 220px; }
    .event-filter-field label, .event-dialog label { color: var(--tinta); font-size: var(--t-sm); font-weight: 700; }
    .event-filter-field input, .event-filter-field select, .event-dialog input, .event-dialog select {
        width: 100%; min-height: 44px; box-sizing: border-box; border: 1px solid var(--garis-tegas);
        border-radius: 11px; background: #fff; color: var(--tinta); padding: 10px 13px; font: inherit;
    }
    .event-filter-field input:focus, .event-filter-field select:focus, .event-dialog input:focus, .event-dialog select:focus { outline: 3px solid rgba(176,48,60,.16); border-color: var(--darah); }
    .event-filter-actions { display: flex; align-items: center; gap: 8px; }
    .event-count { color: var(--tinta-redup); font-size: var(--t-sm); white-space: nowrap; }
    .event-table-wrap { width: 100%; overflow-x: auto; }
    .event-table { min-width: 980px; }
    .event-table th { white-space: nowrap; }
    .event-table td { vertical-align: middle; }
    .event-name { display: block; color: var(--tinta); font-weight: 800; }
    .event-location { display: block; margin-top: 4px; color: var(--tinta-redup); font-size: var(--t-xs); }
    .event-capacity { display: grid; gap: 5px; min-width: 105px; }
    .event-capacity strong { color: var(--tinta); }
    .event-capacity small { color: var(--tinta-redup); }
    .event-progress { width: 100%; height: 7px; overflow: hidden; border-radius: 999px; background: #eadfd1; }
    .event-progress span { display: block; height: 100%; border-radius: inherit; background: var(--sukses); }
    .event-status { display: inline-block; padding: 6px 10px; border-radius: 999px; font-size: var(--t-xs); font-weight: 800; white-space: nowrap; }
    .event-status.akan_datang { background: var(--info-pucat); color: var(--info); }
    .event-status.berlangsung { background: var(--sukses-pucat); color: var(--sukses); }
    .event-status.selesai { background: var(--warm-cream); color: var(--tinta-redup); }
    .event-actions { display: flex; align-items: center; gap: 7px; white-space: nowrap; }
    .event-actions button { padding: 8px 12px; font-size: var(--t-xs); }
    .event-actions form { margin: 0; }
    .event-action-locked { display: block; margin-top: 5px; color: var(--tinta-redup); font-size: 11px; white-space: normal; }
    .event-notice { max-width: 1600px; margin: 0 auto var(--r-4); padding: 13px 16px; border-radius: 12px; font-weight: 600; }
    .event-notice.success { color: var(--sukses); background: var(--sukses-pucat); }
    .event-notice.warning { color: var(--waspada); background: var(--waspada-pucat); }
    .event-notice.error { color: var(--darah); background: var(--darah-pucat); }
    .event-info { margin: 18px 0 0; padding: 13px 15px; border-radius: 12px; background: #f8efdf; color: var(--tinta-redup); font-size: var(--t-sm); line-height: 1.55; }
    .event-dialog { width: min(700px, 94vw); max-width: 700px; max-height: 90vh; overflow-y: auto; }
    .event-dialog::backdrop { background: rgba(35, 10, 16, .55); backdrop-filter: blur(2px); }
    .event-dialog-header { display: flex; justify-content: space-between; align-items: start; gap: 15px; margin-bottom: 20px; }
    .event-dialog-header h2 { margin: 0 0 5px; }
    .event-dialog-header p { margin: 0; color: var(--tinta-redup); font-size: var(--t-sm); }
    .event-dialog-close { border: 0; background: transparent; color: var(--tinta-redup); font-size: 1.6rem; cursor: pointer; line-height: 1; }
    .event-form-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 0 16px; }
    .event-form-field { display: grid; gap: 7px; margin-bottom: 15px; }
    .event-form-field.full { grid-column: 1 / -1; }
    .event-form-help { margin: -3px 0 12px; color: var(--tinta-redup); font-size: var(--t-xs); }
    .event-booking-warning { margin: 0 0 15px; padding: 11px 13px; border-radius: 10px; background: var(--waspada-pucat); color: var(--waspada); font-size: var(--t-sm); line-height: 1.5; }
    .event-dialog-actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 8px; }
    .event-empty { padding: 65px 20px !important; text-align: center; color: var(--tinta-redup); }
    .event-empty strong { display: block; margin-bottom: 7px; color: var(--tinta); font-size: 1.05rem; }
    @media (max-width: 900px) { .event-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    @media (max-width: 700px) {
        .event-page { padding: 28px 16px; }
        .event-page .inv-head { align-items: stretch; flex-direction: column; }
        .event-page .inv-head .event-primary-btn { align-self: flex-start; }
        .event-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; }
        .event-stat { padding: 16px; }
        .event-stat span { font-size: 1.6rem; }
        .event-toolbar, .event-filter { align-items: stretch; flex-direction: column; }
        .event-filter-field, .event-filter-field.search-field { min-width: 0; width: 100%; }
        .event-filter-actions { width: 100%; }
        .event-filter-actions button, .event-filter-actions a { flex: 1; }
        .event-form-grid { grid-template-columns: 1fr; }
        .event-form-field.full { grid-column: auto; }
        .event-dialog-actions { flex-direction: column-reverse; }
        .event-dialog-actions button { width: 100%; }
    }
</style>

<main class="inv-wrapper event-page">
    <div class="inv-head">
        <div>
            <h1>Jadwal Event Donor</h1>
            <p>Kelola jadwal kegiatan donor darah, lokasi pelaksanaan, kuota pendonor, dan status kegiatan.</p>
        </div>
        <button class="event-primary-btn" type="button" id="openCreateEvent">+ Tambah Event</button>
    </div>

    <?php if (is_array($flash) && isset($flash['message'])): ?>
        <div class="event-notice <?= e((string) ($flash['type'] ?? 'success')) ?>" role="status">
            <?= e((string) $flash['message']) ?>
        </div>
    <?php endif; ?>

    <?php if ($error !== ''): ?>
        <div class="event-notice error" role="alert"><?= e($error) ?></div>
    <?php endif; ?>

    <section class="event-stats" aria-label="Ringkasan event">
        <article class="event-stat"><span><?= (int) ($stats['total_event'] ?? 0) ?></span><p>Total event</p></article>
        <article class="event-stat"><span><?= (int) ($stats['akan_datang'] ?? 0) ?></span><p>Akan datang</p></article>
        <article class="event-stat"><span><?= (int) ($stats['berlangsung'] ?? 0) ?></span><p>Sedang berlangsung</p></article>
        <article class="event-stat"><span><?= (int) ($stats['selesai'] ?? 0) ?></span><p>Selesai</p></article>
    </section>

    <section class="inv-card inv-card-auto">
        <div class="event-toolbar">
            <form class="event-filter" method="get" action="<?= e(base_url('petugas/event.php')) ?>">
                <div class="event-filter-field search-field">
                    <label for="event-search">Cari event atau lokasi</label>
                    <input id="event-search" type="search" name="q" value="<?= e($search) ?>" placeholder="Contoh: Donor Darah Kampus...">
                </div>
                <div class="event-filter-field">
                    <label for="event-status-filter">Status event</label>
                    <select id="event-status-filter" name="status">
                        <option value="">Semua status</option>
                        <?php foreach ($allowedStatuses as $value => $label): ?>
                            <option value="<?= e($value) ?>" <?= $statusFilter === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="event-filter-actions">
                    <button class="event-primary-btn" type="submit">Terapkan</button>
                    <a class="event-secondary-btn" href="<?= e(base_url('petugas/event.php')) ?>">Reset</a>
                </div>
            </form>
            <span class="event-count"><?= count($events) ?> event ditampilkan</span>
        </div>

        <div class="event-table-wrap">
            <table class="event-table">
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Nama Event / Lokasi</th>
                        <th>Tanggal</th>
                        <th>Waktu</th>
                        <th>Kuota</th>
                        <th>Status</th>
                        <th>Tindakan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$events): ?>
                        <tr>
                            <td colspan="7" class="event-empty">
                                <strong><?= $search !== '' || $statusFilter !== '' ? 'Event tidak ditemukan' : 'Belum ada jadwal event' ?></strong>
                                <?= $search !== '' || $statusFilter !== '' ? 'Coba ubah kata kunci atau filter status.' : 'Tambahkan jadwal donor pertama melalui tombol Tambah Event.' ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($events as $index => $event): ?>
                            <?php
                                $registered = (int) $event['jumlah_pendaftar'];
                                $capacity = (int) $event['kuota'];
                                $remaining = max(0, $capacity - $registered);
                                $percentage = $capacity > 0 ? min(100, (int) round(($registered / $capacity) * 100)) : 0;
                                $eventForJs = json_encode($event, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
                                $statusLabel = $allowedStatuses[$event['status_event']] ?? 'Tidak diketahui';
                            ?>
                            <tr>
                                <td><?= $index + 1 ?></td>
                                <td>
                                    <span class="event-name"><?= e($event['nama_event']) ?></span>
                                    <span class="event-location"><?= e($event['lokasi']) ?></span>
                                </td>
                                <td><?= e(tgl_indo($event['tanggal'])) ?></td>
                                <td><?= e(substr($event['jam_mulai'], 0, 5)) ?>–<?= e(substr($event['jam_selesai'], 0, 5)) ?> WIB</td>
                                <td>
                                    <div class="event-capacity">
                                        <strong><?= $registered ?> / <?= $capacity ?></strong>
                                        <div class="event-progress" aria-label="<?= $percentage ?> persen kuota terisi">
                                            <span style="width: <?= $percentage ?>%"></span>
                                        </div>
                                        <small><?= $remaining ?> slot tersisa</small>
                                    </div>
                                </td>
                                <td><span class="event-status <?= e($event['status_event']) ?>"><?= e($statusLabel) ?></span></td>
                                <td>
                                    <div class="event-actions">
                                        <button
                                            class="event-secondary-btn event-edit-button"
                                            type="button"
                                            data-event="<?= e((string) $eventForJs) ?>"
                                        >Edit</button>
                                        <?php if ($registered === 0): ?>
                                            <form method="post" action="<?= e(base_url('petugas/event.php')) ?>" onsubmit="return confirm('Yakin ingin menghapus event ini? Tindakan ini tidak dapat dibatalkan.');">
                                                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="event_id" value="<?= (int) $event['id'] ?>">
                                                <button class="event-danger-btn" type="submit">Hapus</button>
                                            </form>
                                        <?php else: ?>
                                            <span class="event-action-locked" title="Event memiliki pendaftar">Terkunci: ada pendaftar</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <p class="event-info">
            <strong>Catatan:</strong> kuota tidak dapat dikurangi hingga lebih kecil dari jumlah pendaftar yang sudah tercatat. Event yang sudah memiliki pendaftar tidak dapat dihapus agar data booking dan riwayat pendonor tetap aman. Untuk menutup pendaftaran, ubah status event menjadi <strong>Selesai</strong>.
        </p>
    </section>
</main>

<dialog class="inv-dialog event-dialog" id="eventDialog" aria-labelledby="eventDialogTitle">
    <div class="event-dialog-header">
        <div>
            <h2 id="eventDialogTitle">Tambah Jadwal Event</h2>
            <p>Isi informasi kegiatan donor darah di bawah ini.</p>
        </div>
        <button class="event-dialog-close" type="button" id="closeEventDialog" aria-label="Tutup dialog">&times;</button>
    </div>

    <p class="event-booking-warning" id="eventBookingWarning" hidden></p>

    <form method="post" action="<?= e(base_url('petugas/event.php')) ?>" id="eventForm">
        <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
        <input type="hidden" name="action" id="eventAction" value="<?= e($formMode) ?>">
        <input type="hidden" name="event_id" id="eventId" value="<?= $formEventId > 0 ? $formEventId : '' ?>">

        <div class="event-form-grid">
            <div class="event-form-field full">
                <label for="eventName">Nama Event</label>
                <input type="text" id="eventName" name="nama_event" maxlength="150" required value="<?= e($formData['nama_event']) ?>" placeholder="Contoh: Donor Darah OneBlood 2026">
            </div>
            <div class="event-form-field full">
                <label for="eventLocation">Lokasi</label>
                <input type="text" id="eventLocation" name="lokasi" maxlength="200" required value="<?= e($formData['lokasi']) ?>" placeholder="Contoh: Aula Fakultas Kedokteran">
            </div>
            <div class="event-form-field">
                <label for="eventDate">Tanggal</label>
                <input type="date" id="eventDate" name="tanggal" required value="<?= e($formData['tanggal']) ?>">
            </div>
            <div class="event-form-field">
                <label for="eventQuota">Kuota Pendonor</label>
                <input type="number" id="eventQuota" name="kuota" min="1" max="100000" step="1" required value="<?= e($formData['kuota']) ?>">
            </div>
            <div class="event-form-field">
                <label for="eventStart">Jam Mulai</label>
                <input type="time" id="eventStart" name="jam_mulai" required value="<?= e($formData['jam_mulai']) ?>">
            </div>
            <div class="event-form-field">
                <label for="eventEnd">Jam Selesai</label>
                <input type="time" id="eventEnd" name="jam_selesai" required value="<?= e($formData['jam_selesai']) ?>">
            </div>
            <div class="event-form-field full">
                <label for="eventStatus">Status Event</label>
                <select id="eventStatus" name="status_event" required>
                    <?php foreach ($allowedStatuses as $value => $label): ?>
                        <option value="<?= e($value) ?>" <?= $formData['status_event'] === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
                <p class="event-form-help">Hanya event berstatus Akan Datang atau Berlangsung yang dapat dipilih pendonor, selama waktu pendaftaran belum berakhir dan kuota masih tersedia.</p>
            </div>
        </div>

        <div class="event-dialog-actions">
            <button class="event-secondary-btn" type="button" id="cancelEventDialog">Batal</button>
            <button class="event-primary-btn" type="submit" id="saveEventButton">Simpan Event</button>
        </div>
    </form>
</dialog>

<script>
(() => {
    const dialog = document.getElementById('eventDialog');
    const form = document.getElementById('eventForm');
    const actionInput = document.getElementById('eventAction');
    const idInput = document.getElementById('eventId');
    const title = document.getElementById('eventDialogTitle');
    const saveButton = document.getElementById('saveEventButton');
    const bookingWarning = document.getElementById('eventBookingWarning');

    const fields = {
        nama_event: document.getElementById('eventName'),
        lokasi: document.getElementById('eventLocation'),
        tanggal: document.getElementById('eventDate'),
        jam_mulai: document.getElementById('eventStart'),
        jam_selesai: document.getElementById('eventEnd'),
        kuota: document.getElementById('eventQuota'),
        status_event: document.getElementById('eventStatus')
    };

    function openCreateDialog() {
        form.reset();
        actionInput.value = 'create';
        idInput.value = '';
        fields.nama_event.value = '';
        fields.lokasi.value = '';
        fields.tanggal.value = <?= json_encode(date('Y-m-d')) ?>;
        fields.jam_mulai.value = '';
        fields.jam_selesai.value = '';
        bookingWarning.hidden = true;
        bookingWarning.textContent = ''; 
        fields.kuota.value = '50';
        fields.status_event.value = 'akan_datang';
        title.textContent = 'Tambah Jadwal Event';
        saveButton.textContent = 'Simpan Event';
        dialog.showModal();
        fields.nama_event.focus();
    }

    function openEditDialog(rawEvent) {
        let eventData;
        try {
            eventData = JSON.parse(rawEvent);
        } catch (error) {
            alert('Data event tidak dapat dibaca. Muat ulang halaman dan coba lagi.');
            return;
        }

        const registered = Number(eventData.jumlah_pendaftar ?? 0);
        if (registered > 0) {
            const proceed = confirm(
                `Event ini sudah memiliki ${registered} pendaftar. Perubahan tanggal, waktu, lokasi, atau status dapat memengaruhi peserta yang sudah mendaftar. Tetap lanjutkan?`
            );
            if (!proceed) return;
        }

        actionInput.value = 'update';
        idInput.value = eventData.id;
        fields.nama_event.value = eventData.nama_event ?? '';
        fields.lokasi.value = eventData.lokasi ?? '';
        fields.tanggal.value = eventData.tanggal ?? '';
        fields.jam_mulai.value = (eventData.jam_mulai ?? '').toString().slice(0, 5);
        fields.jam_selesai.value = (eventData.jam_selesai ?? '').toString().slice(0, 5);
        fields.kuota.value = eventData.kuota ?? 50;
        fields.status_event.value = eventData.status_event ?? 'akan_datang';
        if (registered > 0) {
            bookingWarning.textContent = `Perhatian: sudah ada ${registered} pendaftar pada event ini. Pastikan perubahan jadwal dan lokasi sudah dikoordinasikan kepada mereka.`;
            bookingWarning.hidden = false;
        } else {
            bookingWarning.hidden = true;
            bookingWarning.textContent = '';
        }
        title.textContent = 'Edit Jadwal Event';
        saveButton.textContent = 'Simpan Perubahan';
        dialog.showModal();
        fields.nama_event.focus();
    }

    document.getElementById('openCreateEvent').addEventListener('click', openCreateDialog);
    document.getElementById('closeEventDialog').addEventListener('click', () => dialog.close());
    document.getElementById('cancelEventDialog').addEventListener('click', () => dialog.close());

    document.querySelectorAll('.event-edit-button').forEach((button) => {
        button.addEventListener('click', () => openEditDialog(button.dataset.event ?? '{}'));
    });

    // Jika validasi server gagal, tampilkan kembali formulir dengan input sebelumnya.
    const shouldReopen = <?= $formOpen ? 'true' : 'false' ?>;
    if (shouldReopen) {
        title.textContent = <?= json_encode($formMode === 'update' ? 'Edit Jadwal Event' : 'Tambah Jadwal Event') ?>;
        <?php if ($formMode === 'update'): ?>
        bookingWarning.textContent = 'Perubahan yang disimpan dapat memengaruhi peserta yang sudah mendaftar. Pastikan jadwal dan lokasi sudah dikoordinasikan.';
        bookingWarning.hidden = false;
        <?php endif; ?>
        saveButton.textContent = <?= json_encode($formMode === 'update' ? 'Simpan Perubahan' : 'Simpan Event') ?>;
        dialog.showModal();
    }

    // Pemeriksaan ringan di browser; validasi utama tetap dilakukan di server.
    form.addEventListener('submit', (event) => {
        if (fields.jam_mulai.value && fields.jam_selesai.value && fields.jam_selesai.value <= fields.jam_mulai.value) {
            event.preventDefault();
            alert('Jam selesai harus lebih akhir daripada jam mulai.');
            fields.jam_selesai.focus();
        }
    });
})();
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
