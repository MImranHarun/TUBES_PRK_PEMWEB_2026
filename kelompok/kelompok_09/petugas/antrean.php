<?php
/*
 * menampilkan booking yang dibuat pendonor, memudahkan petugas
 * menelusuri antrean, membuka formulir skrining, dan mencatat donor selesai.
 */

require_once __DIR__ . '/../config/auth_check.php';
cekRole(['petugas']);

$pageTitle = 'Antrean Skrining | OneBlood';
$userLogin = currentUser();

$allowedStatuses = [
    'menunggu' => 'Menunggu Skrining',
    'lolos' => 'Lolos Skrining',
    'ditolak' => 'Tidak Lolos',
    'selesai' => 'Donor Selesai',
];

function antreanPostString(string $key): string
{
    $value = $_POST[$key] ?? '';
    return is_string($value) ? trim($value) : '';
}

function antreanValidDate(string $value): bool
{
    if ($value === '') {
        return false;
    }
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    return $date !== false && $date->format('Y-m-d') === $value;
}

function antreanRedirect(string $type, string $message, array $query = []): never
{
    $_SESSION['antrean_flash'] = ['type' => $type, 'message' => $message];
    $url = base_url('petugas/antrean.php');
    if ($query !== []) {
        $url .= '?' . http_build_query($query);
    }
    header('Location: ' . $url, true, 303);
    exit;
}

if (empty($_SESSION['antrean_csrf_token'])) {
    $_SESSION['antrean_csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = (string) $_SESSION['antrean_csrf_token'];

$flash = $_SESSION['antrean_flash'] ?? null;
unset($_SESSION['antrean_flash']);
$error = '';

/* ================================================================
 * Aksi operasional: tandai donor selesai setelah proses donor dilakukan.
 * Hanya status lolos yang dapat dipindahkan menjadi selesai.
 * ================================================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedToken = antreanPostString('csrf_token');
    $action = antreanPostString('action');

    $redirectFilters = [];
    foreach (['q', 'event_id', 'status', 'dari', 'sampai', 'page'] as $filterKey) {
        $filterValue = antreanPostString($filterKey);
        if ($filterValue !== '') {
            $redirectFilters[$filterKey] = $filterValue;
        }
    }

    if ($postedToken === '' || !hash_equals($csrfToken, $postedToken)) {
        http_response_code(403);
        $error = 'Sesi formulir tidak valid. Muat ulang halaman, lalu coba lagi.';
    } elseif ($action === 'selesai') {
        $antreanId = filter_var($_POST['antrean_id'] ?? null, FILTER_VALIDATE_INT);

        if ($antreanId === false || $antreanId === null || $antreanId < 1) {
            antreanRedirect('error', 'ID antrean tidak valid.', $redirectFilters);
        }

        try {
            $db->beginTransaction();

            $lock = $db->prepare('SELECT id, kode_tiket, status_skrining FROM antrean_skrining WHERE id = ? FOR UPDATE');
            $lock->execute([$antreanId]);
            $record = $lock->fetch();

            if (!$record) {
                $db->rollBack();
                antreanRedirect('error', 'Data antrean tidak ditemukan.', $redirectFilters);
            }

            if ($record['status_skrining'] !== 'lolos') {
                $db->rollBack();
                antreanRedirect('warning', 'Hanya pendonor yang lolos skrining yang dapat ditandai selesai.', $redirectFilters);
            }

            $update = $db->prepare("UPDATE antrean_skrining SET status_skrining = 'selesai' WHERE id = ? AND status_skrining = 'lolos'");
            $update->execute([$antreanId]);

            if ($update->rowCount() !== 1) {
                $db->rollBack();
                antreanRedirect('warning', 'Status antrean berubah. Muat ulang halaman dan periksa kembali.', $redirectFilters);
            }

            $db->commit();
            antreanRedirect('success', 'Status donor dengan tiket ' . $record['kode_tiket'] . ' berhasil ditandai selesai.', $redirectFilters);
        } catch (PDOException $exception) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            error_log('[OneBlood antrean selesai] ' . $exception->getMessage());
            antreanRedirect('error', 'Status donor gagal diperbarui karena terjadi kesalahan database.', $redirectFilters);
        }
    } else {
        http_response_code(400);
        $error = 'Aksi yang diminta tidak dikenali.';
    }
}

/* ================================================================
 * Filter dan pagination
 * ================================================================ */
$searchRaw = $_GET['q'] ?? '';
$search = is_string($searchRaw) ? trim($searchRaw) : '';
if (strlen($search) > 100) {
    $search = substr($search, 0, 100);
}

$eventId = filter_var($_GET['event_id'] ?? '', FILTER_VALIDATE_INT);
if ($eventId === false || $eventId === null || $eventId < 1) {
    $eventId = 0;
}

$statusRaw = $_GET['status'] ?? '';
$statusFilter = is_string($statusRaw) ? $statusRaw : '';
if ($statusFilter !== '' && !array_key_exists($statusFilter, $allowedStatuses)) {
    $statusFilter = '';
}

$dateFromRaw = $_GET['dari'] ?? '';
$dateToRaw = $_GET['sampai'] ?? '';
$dateFrom = is_string($dateFromRaw) ? $dateFromRaw : '';
$dateTo = is_string($dateToRaw) ? $dateToRaw : '';
if ($dateFrom !== '' && !antreanValidDate($dateFrom)) {
    $dateFrom = '';
}
if ($dateTo !== '' && !antreanValidDate($dateTo)) {
    $dateTo = '';
}
if ($dateFrom !== '' && $dateTo !== '' && $dateFrom > $dateTo) {
    [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
}

$page = filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT);
$page = ($page === false || $page === null || $page < 1) ? 1 : $page;
$perPage = 20;

/* Daftar event yang benar-benar memiliki antrean untuk pilihan filter. */
$eventOptions = $db->query(
    'SELECT e.id, e.nama_event, e.tanggal
     FROM events e
     WHERE EXISTS (SELECT 1 FROM antrean_skrining a WHERE a.event_id = e.id)
     ORDER BY e.tanggal DESC, e.id DESC'
)->fetchAll();

/* Ringkasan mengikuti filter event dan tanggal, tetapi tidak mengikuti pencarian/status. */
$summaryWhere = [];
$summaryParams = [];
if ($eventId > 0) {
    $summaryWhere[] = 'a.event_id = ?';
    $summaryParams[] = $eventId;
}
if ($dateFrom !== '') {
    $summaryWhere[] = 'DATE(a.waktu_daftar) >= ?';
    $summaryParams[] = $dateFrom;
}
if ($dateTo !== '') {
    $summaryWhere[] = 'DATE(a.waktu_daftar) <= ?';
    $summaryParams[] = $dateTo;
}
$summarySql = 'SELECT COUNT(*) AS total,
        SUM(a.status_skrining = \'menunggu\') AS menunggu,
        SUM(a.status_skrining = \'lolos\') AS lolos,
        SUM(a.status_skrining = \'ditolak\') AS ditolak,
        SUM(a.status_skrining = \'selesai\') AS selesai
    FROM antrean_skrining a';
if ($summaryWhere !== []) {
    $summarySql .= ' WHERE ' . implode(' AND ', $summaryWhere);
}
$summaryStatement = $db->prepare($summarySql);
$summaryStatement->execute($summaryParams);
$summary = $summaryStatement->fetch() ?: [];
$summary = array_map(static fn($value): int => (int) ($value ?? 0), $summary);

/* Query daftar utama. */
$where = [];
$params = [];
if ($eventId > 0) {
    $where[] = 'a.event_id = ?';
    $params[] = $eventId;
}
if ($statusFilter !== '') {
    $where[] = 'a.status_skrining = ?';
    $params[] = $statusFilter;
}
if ($dateFrom !== '') {
    $where[] = 'DATE(a.waktu_daftar) >= ?';
    $params[] = $dateFrom;
}
if ($dateTo !== '') {
    $where[] = 'DATE(a.waktu_daftar) <= ?';
    $params[] = $dateTo;
}
if ($search !== '') {
    $where[] = '(a.kode_tiket LIKE ? OR u.nama LIKE ? OR u.email LIKE ?)';
    $like = '%' . $search . '%';
    array_push($params, $like, $like, $like);
}

$whereSql = $where !== [] ? ' WHERE ' . implode(' AND ', $where) : '';
$countStatement = $db->prepare(
    'SELECT COUNT(*) FROM antrean_skrining a JOIN users u ON u.id = a.user_id' . $whereSql
);
$countStatement->execute($params);
$totalRows = (int) $countStatement->fetchColumn();
$totalPages = max(1, (int) ceil($totalRows / $perPage));
if ($page > $totalPages) {
    $page = $totalPages;
}
$offset = ($page - 1) * $perPage;

$listSql = 'SELECT
        a.id, a.kode_tiket, a.q_sehat, a.q_tidur_cukup, a.q_bebas_obat,
        a.status_skrining, a.waktu_daftar, a.waktu_periksa,
        a.berat_badan_aktual, a.tensi, a.hb, a.suhu_tubuh,
        u.nama AS nama_pendonor, u.email AS email_pendonor, u.no_hp,
        e.id AS event_id, e.nama_event, e.tanggal AS tanggal_event, e.lokasi
    FROM antrean_skrining a
    JOIN users u ON u.id = a.user_id
    JOIN events e ON e.id = a.event_id' . $whereSql . '
    ORDER BY
        CASE a.status_skrining
            WHEN \'menunggu\' THEN 1
            WHEN \'lolos\' THEN 2
            WHEN \'ditolak\' THEN 3
            WHEN \'selesai\' THEN 4
            ELSE 5
        END,
        a.waktu_daftar ASC, a.id ASC
    LIMIT :limit OFFSET :offset';
$listStatement = $db->prepare($listSql);
foreach ($params as $index => $value) {
    $listStatement->bindValue($index + 1, $value, PDO::PARAM_STR);
}
$listStatement->bindValue(':limit', $perPage, PDO::PARAM_INT);
$listStatement->bindValue(':offset', $offset, PDO::PARAM_INT);
$listStatement->execute();
$antreanList = $listStatement->fetchAll();

$queryParams = [];
if ($search !== '') $queryParams['q'] = $search;
if ($eventId > 0) $queryParams['event_id'] = $eventId;
if ($statusFilter !== '') $queryParams['status'] = $statusFilter;
if ($dateFrom !== '') $queryParams['dari'] = $dateFrom;
if ($dateTo !== '') $queryParams['sampai'] = $dateTo;

function antreanPageUrl(int $targetPage, array $params): string
{
    $params['page'] = $targetPage;
    return base_url('petugas/antrean.php') . '?' . http_build_query($params);
}

function antreanStatusLabel(string $status): string
{
    $labels = [
        'menunggu' => 'Menunggu',
        'lolos' => 'Lolos',
        'ditolak' => 'Tidak Lolos',
        'selesai' => 'Selesai',
    ];
    return $labels[$status] ?? ucfirst($status);
}

require_once __DIR__ . '/../includes/header.php';
?>
<style>
.queue-page { padding: 42px 28px 64px; }
.queue-page .inv-head { align-items: center; }
.queue-page .inv-head p { max-width: 760px; font-size: .98rem; line-height: 1.65; }
.queue-stats { max-width: 1600px; margin: 0 auto 22px; display: grid; grid-template-columns: repeat(5, minmax(0,1fr)); gap: 14px; }
.queue-stat { background: var(--cream); border: 1px solid var(--garis); border-radius: 16px; padding: 17px 19px; box-shadow: var(--bayangan); }
.queue-stat strong { display: block; font-size: 1.8rem; line-height: 1.15; color: var(--darah); }
.queue-stat span { display: block; margin-top: 5px; color: var(--tinta-redup); font-size: .88rem; }
.queue-toolbar { display: grid; grid-template-columns: minmax(220px,2fr) repeat(4,minmax(130px,1fr)) auto; gap: 10px; align-items: end; margin: 0 0 20px; }
.queue-field label { display: block; margin: 0 0 6px; font-weight: 650; font-size: .86rem; color: var(--tinta); }
.queue-field input, .queue-field select { width: 100%; min-height: 44px; box-sizing: border-box; padding: 9px 12px; border: 1px solid var(--garis); border-radius: 10px; background: #fff; color: var(--tinta); font: inherit; }
.queue-filter-actions { display: flex; gap: 8px; }
.queue-filter-actions .inv-btn { min-height: 44px; padding: 9px 15px; }
.queue-reset { display: inline-flex; align-items: center; justify-content: center; min-height: 44px; padding: 8px 12px; border: 1px solid var(--garis); border-radius: 10px; color: var(--darah); text-decoration: none; white-space: nowrap; }
.queue-alert { max-width: 1600px; margin: 0 auto 18px; padding: 13px 16px; border-radius: 12px; background: var(--sukses-pucat); color: var(--sukses); }
.queue-alert.warning { background: var(--waspada-pucat); color: var(--waspada); }
.queue-alert.error { background: var(--darah-pucat); color: var(--darah); }
.queue-table-wrap { overflow-x: auto; }
.queue-table { min-width: 1120px; }
.queue-table th, .queue-table td { vertical-align: top; }
.queue-ticket { font-weight: 750; color: var(--darah); white-space: nowrap; }
.queue-muted { display: block; color: var(--tinta-redup); font-size: .84rem; margin-top: 3px; }
.queue-answers { display: grid; gap: 5px; min-width: 155px; font-size: .82rem; }
.queue-answer { display: inline-flex; align-items: center; gap: 6px; }
.queue-dot { display: inline-block; width: 7px; height: 7px; border-radius: 50%; background: var(--sukses); }
.queue-answer.no .queue-dot { background: var(--darah); }
.queue-badge { display: inline-block; padding: 5px 10px; border-radius: 999px; font-size: .8rem; font-weight: 750; white-space: nowrap; }
.queue-badge.menunggu { background: #fff1cf; color: #80520a; }
.queue-badge.lolos { background: #e4e9c8; color: #46662f; }
.queue-badge.ditolak { background: #f5dce0; color: #8b2331; }
.queue-badge.selesai { background: #e6e8ec; color: #46505e; }
.queue-actions { display: flex; flex-wrap: wrap; gap: 7px; min-width: 145px; }
.queue-link, .queue-action-button { display: inline-flex; align-items: center; justify-content: center; border: 0; border-radius: 9px; padding: 8px 11px; font: inherit; font-size: .83rem; font-weight: 700; text-decoration: none; cursor: pointer; }
.queue-link { background: var(--darah); color: #fff; }
.queue-link.secondary { background: #f2e8e8; color: var(--darah); }
.queue-action-button { background: #e4e9c8; color: #46662f; }
.queue-action-button:hover, .queue-link:hover { filter: brightness(.96); }
.queue-note { margin: 18px 0 0; color: var(--tinta-redup); font-size: .9rem; line-height: 1.6; }
.queue-pagination { display: flex; align-items: center; justify-content: space-between; gap: 15px; margin-top: 18px; flex-wrap: wrap; }
.queue-pages { display: flex; gap: 6px; flex-wrap: wrap; }
.queue-pages a, .queue-pages span { min-width: 36px; padding: 7px 10px; text-align: center; border-radius: 8px; text-decoration: none; color: var(--darah); border: 1px solid var(--garis); }
.queue-pages .current { background: var(--darah); color: #fff; border-color: var(--darah); }
.queue-empty { text-align: center; padding: 45px 16px !important; color: var(--tinta-redup); }
@media (max-width: 1100px) { .queue-stats { grid-template-columns: repeat(3,1fr); } .queue-toolbar { grid-template-columns: repeat(3,minmax(0,1fr)); } .queue-search { grid-column: span 3; } }
@media (max-width: 700px) { .queue-page { padding: 28px 14px 42px; } .queue-page .inv-head { display: block; } .queue-stats { grid-template-columns: repeat(2,minmax(0,1fr)); gap: 9px; } .queue-stat { padding: 14px; } .queue-stat strong { font-size: 1.5rem; } .queue-toolbar { grid-template-columns: 1fr 1fr; } .queue-search { grid-column: span 2; } .queue-filter-actions { grid-column: span 2; } }
</style>

<main class="inv-wrapper queue-page">
    <div class="inv-head">
        <div>
            <h1>Antrean Skrining</h1>
            <p>Kelola booking pendonor yang masuk, periksa jawaban awal, dan lanjutkan pendonor ke proses verifikasi medis.</p>
        </div>
    </div>

    <?php if ($flash && is_array($flash)): ?>
        <?php $flashType = in_array($flash['type'] ?? '', ['success', 'warning', 'error'], true) ? $flash['type'] : 'success'; ?>
        <div class="queue-alert <?= e($flashType) ?>" role="status"><?= e((string) ($flash['message'] ?? '')) ?></div>
    <?php endif; ?>
    <?php if ($error !== ''): ?>
        <div class="queue-alert error" role="alert"><?= e($error) ?></div>
    <?php endif; ?>

    <section class="queue-stats" aria-label="Ringkasan antrean">
        <div class="queue-stat"><strong><?= $summary['total'] ?? 0 ?></strong><span>Total antrean</span></div>
        <div class="queue-stat"><strong><?= $summary['menunggu'] ?? 0 ?></strong><span>Menunggu skrining</span></div>
        <div class="queue-stat"><strong><?= $summary['lolos'] ?? 0 ?></strong><span>Lolos skrining</span></div>
        <div class="queue-stat"><strong><?= $summary['ditolak'] ?? 0 ?></strong><span>Tidak lolos</span></div>
        <div class="queue-stat"><strong><?= $summary['selesai'] ?? 0 ?></strong><span>Donor selesai</span></div>
    </section>

    <section class="inv-card">
        <form method="get" action="<?= e(base_url('petugas/antrean.php')) ?>" class="queue-toolbar">
            <div class="queue-field queue-search">
                <label for="queueSearch">Cari pendonor / kode tiket</label>
                <input type="search" id="queueSearch" name="q" value="<?= e($search) ?>" placeholder="Nama, email, atau OB-...">
            </div>
            <div class="queue-field">
                <label for="queueEvent">Event</label>
                <select id="queueEvent" name="event_id">
                    <option value="">Semua event</option>
                    <?php foreach ($eventOptions as $eventOption): ?>
                        <option value="<?= (int) $eventOption['id'] ?>" <?= $eventId === (int) $eventOption['id'] ? 'selected' : '' ?>><?= e($eventOption['nama_event']) ?> · <?= e(tgl_indo($eventOption['tanggal'])) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="queue-field">
                <label for="queueStatus">Status</label>
                <select id="queueStatus" name="status">
                    <option value="">Semua status</option>
                    <?php foreach ($allowedStatuses as $statusValue => $statusLabel): ?>
                        <option value="<?= e($statusValue) ?>" <?= $statusFilter === $statusValue ? 'selected' : '' ?>><?= e($statusLabel) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="queue-field">
                <label for="queueFrom">Daftar dari</label>
                <input type="date" id="queueFrom" name="dari" value="<?= e($dateFrom) ?>">
            </div>
            <div class="queue-field">
                <label for="queueTo">Sampai</label>
                <input type="date" id="queueTo" name="sampai" value="<?= e($dateTo) ?>">
            </div>
            <div class="queue-filter-actions">
                <button type="submit" class="inv-btn">Filter</button>
                <a class="queue-reset" href="<?= e(base_url('petugas/antrean.php')) ?>">Reset</a>
            </div>
        </form>

        <div class="queue-table-wrap">
            <table class="queue-table">
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Kode Tiket</th>
                        <th>Pendonor</th>
                        <th>Event</th>
                        <th>Jawaban Awal</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($antreanList): ?>
                    <?php foreach ($antreanList as $index => $item): ?>
                        <?php
                            $status = (string) $item['status_skrining'];
                            $registeredAt = $item['waktu_daftar'] ? date('d/m/Y H:i', strtotime($item['waktu_daftar'])) : '-';
                            $screeningUrl = base_url('petugas/skrining.php?id=' . (int) $item['id']);
                        ?>
                        <tr>
                            <td><?= $offset + $index + 1 ?></td>
                            <td>
                                <span class="queue-ticket"><?= e($item['kode_tiket']) ?></span>
                                <span class="queue-muted">Daftar: <?= e($registeredAt) ?></span>
                            </td>
                            <td>
                                <strong><?= e($item['nama_pendonor']) ?></strong>
                                <span class="queue-muted"><?= e($item['email_pendonor']) ?></span>
                                <span class="queue-muted"><?= e($item['no_hp'] ?: 'Nomor HP belum diisi') ?></span>
                            </td>
                            <td>
                                <strong><?= e($item['nama_event']) ?></strong>
                                <span class="queue-muted"><?= e(tgl_indo($item['tanggal_event'])) ?></span>
                                <span class="queue-muted"><?= e($item['lokasi']) ?></span>
                            </td>
                            <td>
                                <div class="queue-answers">
                                    <?php foreach ([
                                        'q_sehat' => 'Merasa sehat',
                                        'q_tidur_cukup' => 'Tidur cukup',
                                        'q_bebas_obat' => 'Bebas obat',
                                    ] as $questionKey => $questionLabel): ?>
                                        <?php $answerYes = (int) $item[$questionKey] === 1; ?>
                                        <span class="queue-answer <?= $answerYes ? 'yes' : 'no' ?>"><i class="queue-dot"></i><?= e($questionLabel) ?>: <strong><?= $answerYes ? 'Ya' : 'Tidak' ?></strong></span>
                                    <?php endforeach; ?>
                                </div>
                            </td>
                            <td><span class="queue-badge <?= e($status) ?>"><?= e(antreanStatusLabel($status)) ?></span></td>
                            <td>
                                <div class="queue-actions">
                                    <a class="queue-link <?= $status !== 'menunggu' ? 'secondary' : '' ?>" href="<?= e($screeningUrl) ?>">
                                        <?= $status === 'menunggu' ? 'Mulai Skrining' : ($status === 'selesai' ? 'Lihat Detail' : 'Lihat / Koreksi') ?>
                                    </a>
                                    <?php if ($status === 'lolos'): ?>
                                        <form method="post" action="<?= e(base_url('petugas/antrean.php')) ?>" onsubmit="return confirm('Pastikan proses donor untuk tiket <?= e($item['kode_tiket']) ?> sudah selesai. Tandai sebagai donor selesai?');">
                                            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                            <input type="hidden" name="action" value="selesai">
                                            <input type="hidden" name="antrean_id" value="<?= (int) $item['id'] ?>">
                                            <?php foreach ($queryParams as $filterKey => $filterValue): ?>
                                                <input type="hidden" name="<?= e($filterKey) ?>" value="<?= e((string) $filterValue) ?>">
                                            <?php endforeach; ?>
                                            <button class="queue-action-button" type="submit">Tandai Selesai</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="7" class="queue-empty">Tidak ada data antrean yang cocok dengan filter.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="queue-pagination">
            <span class="queue-muted">Menampilkan <?= $totalRows > 0 ? $offset + 1 : 0 ?>–<?= min($offset + $perPage, $totalRows) ?> dari <?= $totalRows ?> antrean</span>
            <?php if ($totalPages > 1): ?>
                <nav class="queue-pages" aria-label="Navigasi halaman antrean">
                    <?php if ($page > 1): ?><a href="<?= e(antreanPageUrl($page - 1, $queryParams)) ?>" aria-label="Halaman sebelumnya">&lsaquo;</a><?php endif; ?>
                    <?php
                        $startPage = max(1, $page - 2);
                        $endPage = min($totalPages, $page + 2);
                        for ($pageNumber = $startPage; $pageNumber <= $endPage; $pageNumber++):
                    ?>
                        <?php if ($pageNumber === $page): ?><span class="current" aria-current="page"><?= $pageNumber ?></span><?php else: ?><a href="<?= e(antreanPageUrl($pageNumber, $queryParams)) ?>"><?= $pageNumber ?></a><?php endif; ?>
                    <?php endfor; ?>
                    <?php if ($page < $totalPages): ?><a href="<?= e(antreanPageUrl($page + 1, $queryParams)) ?>" aria-label="Halaman berikutnya">&rsaquo;</a><?php endif; ?>
                </nav>
            <?php endif; ?>
        </div>

        <p class="queue-note"><strong>Alur kerja:</strong> booking pendonor akan masuk dengan status <em>Menunggu</em>. Lakukan pemeriksaan melalui tombol <em>Mulai Skrining</em>. Status <em>Lolos</em> dapat ditandai <em>Selesai</em> setelah proses donor benar-benar selesai. Status <em>Tidak Lolos</em> tetap tersimpan sebagai riwayat pemeriksaan.</p>
    </section>
</main>
<?php
$hideSidebar = true;
require_once __DIR__ . '/../includes/footer.php';
?>
