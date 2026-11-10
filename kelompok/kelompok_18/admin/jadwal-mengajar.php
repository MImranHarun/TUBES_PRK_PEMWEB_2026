<?php

declare(strict_types=1);

require_once __DIR__ . '/_init.php';
require_once __DIR__ . '/../config/database.php';

$errors = [];
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

/**
 * Day numbers follow the application convention: 1 = Monday ... 7 = Sunday.
 * The database stores the raw number; the label is only for display.
 */
const JADWAL_DAYS = [
    1 => 'Senin',
    2 => 'Selasa',
    3 => 'Rabu',
    4 => 'Kamis',
    5 => 'Jumat',
    6 => 'Sabtu',
    7 => 'Minggu',
];

/** Read a trimmed string value from the POST body. */
function jadwal_post_string(string $key): string
{
    $value = $_POST[$key] ?? '';
    return is_string($value) ? trim($value) : '';
}

/** Read an integer value from the POST body. */
function jadwal_post_int(string $key): int
{
    $value = $_POST[$key] ?? 0;
    return is_scalar($value) ? (int) $value : 0;
}

/** Normalize a user-supplied time to H:i:s, or return null when invalid. */
function jadwal_time_value(string $value): ?string
{
    foreach (['H:i:s', 'H:i'] as $format) {
        $time = DateTime::createFromFormat($format, $value);
        if ($time instanceof DateTime && $time->format($format) === $value) {
            return $time->format('H:i:s');
        }
    }
    return null;
}

/** Validate an optional date in Y-m-d format. */
function jadwal_date_value(string $value): ?string
{
    if ($value === '') {
        return '';
    }
    $date = DateTime::createFromFormat('Y-m-d', $value);
    return $date instanceof DateTime && $date->format('Y-m-d') === $value ? $value : null;
}

/** Send a flash message and return to the schedule list. */
function jadwal_redirect_with_flash(int $filterSemesterId, string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
    $query = $filterSemesterId > 0 ? '?semester_id=' . $filterSemesterId : '';
    header('Location: jadwal-mengajar.php' . $query);
    exit;
}

$isPost = $_SERVER['REQUEST_METHOD'] === 'POST';
$filterSemesterId = $isPost
    ? jadwal_post_int('filter_semester_id')
    : (isset($_GET['semester_id']) ? (int) $_GET['semester_id'] : 0);
if ($filterSemesterId < 0) {
    $filterSemesterId = 0;
}

$editId = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;
if ($editId < 0) {
    $editId = 0;
}

// ---------------------------------------------------------------
// Handle POST actions (save and delete). CSRF is mandatory.
// ---------------------------------------------------------------
if ($isPost) {
    if (!verifyCsrfToken($_POST['_csrf_token'] ?? null)) {
        http_response_code(403);
        exit('Permintaan tidak valid.');
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $id = jadwal_post_int('id');
        $penugasanId = jadwal_post_int('penugasan_guru_id');
        $hari = jadwal_post_int('hari');
        $jamMulai = jadwal_time_value(jadwal_post_string('jam_mulai'));
        $jamSelesai = jadwal_time_value(jadwal_post_string('jam_selesai'));
        $berlakuMulai = jadwal_date_value(jadwal_post_string('berlaku_mulai'));
        $berlakuSampai = jadwal_date_value(jadwal_post_string('berlaku_sampai'));

        // The schedule always hangs off an assignment; teacher/class/semester come from it.
        $assignment = null;
        if ($penugasanId > 0) {
            $statement = $pdo->prepare(
                'SELECT id, guru_id, kelas_id, semester_id FROM penugasan_guru WHERE id = :id LIMIT 1'
            );
            $statement->execute(['id' => $penugasanId]);
            $assignment = $statement->fetch() ?: null;
        }
        if ($assignment === null) {
            $errors[] = 'Penugasan guru yang dipilih tidak ditemukan.';
        }

        if (!isset(JADWAL_DAYS[$hari])) {
            $errors[] = 'Hari wajib dipilih antara 1 (Senin) sampai 7 (Minggu).';
        }

        if ($jamMulai === null) {
            $errors[] = 'Jam mulai wajib diisi dengan format HH:MM atau HH:MM:SS.';
        }
        if ($jamSelesai === null) {
            $errors[] = 'Jam selesai wajib diisi dengan format HH:MM atau HH:MM:SS.';
        }
        if ($jamMulai !== null && $jamSelesai !== null && $jamSelesai <= $jamMulai) {
            $errors[] = 'Jam selesai harus lebih besar dari jam mulai.';
        }

        if ($berlakuMulai === null) {
            $errors[] = 'Format berlaku mulai tidak valid.';
        }
        if ($berlakuSampai === null) {
            $errors[] = 'Format berlaku sampai tidak valid.';
        }
        if (is_string($berlakuMulai) && is_string($berlakuSampai)
            && $berlakuMulai !== '' && $berlakuSampai !== '' && $berlakuSampai < $berlakuMulai) {
            $errors[] = 'Berlaku sampai tidak boleh lebih awal dari berlaku mulai.';
        }

        // Prevent overlapping schedules for the same class or the same teacher.
        if ($errors === [] && $assignment !== null && $jamMulai !== null && $jamSelesai !== null) {
            $statement = $pdo->prepare(
                'SELECT COUNT(*)
                 FROM jadwal_mengajar j
                 INNER JOIN penugasan_guru p ON p.id = j.penugasan_guru_id
                 WHERE j.hari = :hari
                   AND j.jam_mulai < :jam_selesai
                   AND j.jam_selesai > :jam_mulai
                   AND p.semester_id = :semester_id
                   AND (p.kelas_id = :kelas_id OR p.guru_id = :guru_id)
                   AND j.id <> :id'
            );
            $statement->execute([
                'hari' => $hari,
                'jam_mulai' => $jamMulai,
                'jam_selesai' => $jamSelesai,
                'semester_id' => (int) $assignment['semester_id'],
                'kelas_id' => (int) $assignment['kelas_id'],
                'guru_id' => (int) $assignment['guru_id'],
                'id' => $id,
            ]);
            if ((int) $statement->fetchColumn() > 0) {
                $errors[] = 'Jadwal bentrok dengan jadwal lain pada kelas atau guru yang sama.';
            }
        }

        if ($errors === []) {
            $berlakuMulaiValue = $berlakuMulai === '' ? null : $berlakuMulai;
            $berlakuSampaiValue = $berlakuSampai === '' ? null : $berlakuSampai;

            try {
                if ($id === 0) {
                    $statement = $pdo->prepare(
                        'INSERT INTO jadwal_mengajar
                             (penugasan_guru_id, hari, jam_mulai, jam_selesai, berlaku_mulai, berlaku_sampai)
                         VALUES (:penugasan_guru_id, :hari, :jam_mulai, :jam_selesai, :berlaku_mulai, :berlaku_sampai)'
                    );
                    $statement->execute([
                        'penugasan_guru_id' => $penugasanId,
                        'hari' => $hari,
                        'jam_mulai' => $jamMulai,
                        'jam_selesai' => $jamSelesai,
                        'berlaku_mulai' => $berlakuMulaiValue,
                        'berlaku_sampai' => $berlakuSampaiValue,
                    ]);
                    jadwal_redirect_with_flash($filterSemesterId, 'success', 'Jadwal mengajar berhasil ditambahkan.');
                } else {
                    $statement = $pdo->prepare(
                        'UPDATE jadwal_mengajar
                         SET penugasan_guru_id = :penugasan_guru_id, hari = :hari,
                             jam_mulai = :jam_mulai, jam_selesai = :jam_selesai,
                             berlaku_mulai = :berlaku_mulai, berlaku_sampai = :berlaku_sampai
                         WHERE id = :id'
                    );
                    $statement->execute([
                        'penugasan_guru_id' => $penugasanId,
                        'hari' => $hari,
                        'jam_mulai' => $jamMulai,
                        'jam_selesai' => $jamSelesai,
                        'berlaku_mulai' => $berlakuMulaiValue,
                        'berlaku_sampai' => $berlakuSampaiValue,
                        'id' => $id,
                    ]);
                    jadwal_redirect_with_flash($filterSemesterId, 'success', 'Jadwal mengajar berhasil diperbarui.');
                }
            } catch (PDOException $exception) {
                error_log('ATTENDARA save jadwal failed: ' . $exception->getMessage());
                $errors[] = 'Jadwal mengajar gagal disimpan.';
            }
        }
    }

    if ($action === 'delete') {
        $id = jadwal_post_int('id');

        try {
            $pdo->beginTransaction();

            $statement = $pdo->prepare('SELECT id FROM jadwal_mengajar WHERE id = :id FOR UPDATE');
            $statement->execute(['id' => $id]);
            if ($statement->fetchColumn() === false) {
                $pdo->rollBack();
                jadwal_redirect_with_flash($filterSemesterId, 'error', 'Data jadwal tidak ditemukan.');
            }

            $statement = $pdo->prepare('DELETE FROM jadwal_mengajar WHERE id = :id');
            $statement->execute(['id' => $id]);

            $pdo->commit();
            jadwal_redirect_with_flash($filterSemesterId, 'success', 'Jadwal mengajar berhasil dihapus.');
        } catch (PDOException $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('ATTENDARA delete jadwal failed: ' . $exception->getMessage());
            jadwal_redirect_with_flash(
                $filterSemesterId,
                'error',
                $exception->getCode() === '23000'
                    ? 'Jadwal masih dipakai oleh sesi pembelajaran sehingga tidak dapat dihapus.'
                    : 'Jadwal mengajar gagal dihapus.'
            );
        }
    }
}

// ---------------------------------------------------------------
// Load reference data for the filter and the form.
// ---------------------------------------------------------------
$semesterList = $pdo->query(
    "SELECT id, kode, nama, status FROM semester
     ORDER BY (status = 'aktif') DESC, tanggal_mulai DESC, id DESC"
)->fetchAll();

$activeSemesterId = 0;
foreach ($semesterList as $semester) {
    if ($semester['status'] === 'aktif') {
        $activeSemesterId = (int) $semester['id'];
        break;
    }
}

if (!$isPost && !isset($_GET['semester_id'])) {
    $filterSemesterId = $activeSemesterId;
}

$semesterExists = false;
foreach ($semesterList as $semester) {
    if ((int) $semester['id'] === $filterSemesterId) {
        $semesterExists = true;
        break;
    }
}
if ($filterSemesterId > 0 && !$semesterExists) {
    $filterSemesterId = 0;
}

// Assignments available for the form (all semesters; the schedule inherits them).
$assignmentOptions = $pdo->query(
    'SELECT p.id, g.nama AS guru_nama, k.nama AS kelas_nama, s.nama AS semester_nama
     FROM penugasan_guru p
     INNER JOIN guru g ON g.id = p.guru_id
     INNER JOIN kelas k ON k.id = p.kelas_id
     INNER JOIN semester s ON s.id = p.semester_id
     ORDER BY s.tanggal_mulai DESC, k.nama, g.nama'
)->fetchAll();

$editSchedule = null;
if ($editId > 0) {
    $statement = $pdo->prepare(
        'SELECT id, penugasan_guru_id, hari, jam_mulai, jam_selesai, berlaku_mulai, berlaku_sampai
         FROM jadwal_mengajar WHERE id = :id LIMIT 1'
    );
    $statement->execute(['id' => $editId]);
    $editSchedule = $statement->fetch() ?: null;
}

$formValues = [
    'id' => 0,
    'penugasan_guru_id' => 0,
    'hari' => 0,
    'jam_mulai' => '',
    'jam_selesai' => '',
    'berlaku_mulai' => '',
    'berlaku_sampai' => '',
];
if ($isPost && ($_POST['action'] ?? '') === 'save' && $errors !== []) {
    $formValues = [
        'id' => jadwal_post_int('id'),
        'penugasan_guru_id' => jadwal_post_int('penugasan_guru_id'),
        'hari' => jadwal_post_int('hari'),
        'jam_mulai' => jadwal_post_string('jam_mulai'),
        'jam_selesai' => jadwal_post_string('jam_selesai'),
        'berlaku_mulai' => jadwal_post_string('berlaku_mulai'),
        'berlaku_sampai' => jadwal_post_string('berlaku_sampai'),
    ];
} elseif ($editSchedule !== null) {
    $formValues = [
        'id' => (int) $editSchedule['id'],
        'penugasan_guru_id' => (int) $editSchedule['penugasan_guru_id'],
        'hari' => (int) $editSchedule['hari'],
        'jam_mulai' => (string) $editSchedule['jam_mulai'],
        'jam_selesai' => (string) $editSchedule['jam_selesai'],
        'berlaku_mulai' => (string) ($editSchedule['berlaku_mulai'] ?? ''),
        'berlaku_sampai' => (string) ($editSchedule['berlaku_sampai'] ?? ''),
    ];
}
$isEditing = $formValues['id'] > 0;

// Schedule list for the selected semester (0 = all semesters).
$listSql = 'SELECT j.id, j.hari, j.jam_mulai, j.jam_selesai, j.berlaku_mulai, j.berlaku_sampai,
                   p.id AS penugasan_id,
                   g.nama AS guru_nama, k.nama AS kelas_nama, s.nama AS semester_nama
            FROM jadwal_mengajar j
            INNER JOIN penugasan_guru p ON p.id = j.penugasan_guru_id
            INNER JOIN guru g ON g.id = p.guru_id
            INNER JOIN kelas k ON k.id = p.kelas_id
            INNER JOIN semester s ON s.id = p.semester_id';
$listParams = [];
if ($filterSemesterId > 0) {
    $listSql .= ' WHERE p.semester_id = :semester_id';
    $listParams['semester_id'] = $filterSemesterId;
}
$listSql .= ' ORDER BY s.tanggal_mulai DESC, j.hari, j.jam_mulai';

$statement = $pdo->prepare($listSql);
$statement->execute($listParams);
$scheduleList = $statement->fetchAll();

admin_page_start('Jadwal Mengajar', 'jadwal-mengajar.php');
?>
<section class="space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-lg font-semibold text-[#20392a]">Daftar Jadwal Mengajar</h2>
            <p class="mt-1 text-sm text-slate-600">Guru, kelas, dan semester ditampilkan dari penugasan guru.</p>
        </div>
        <form method="get" action="jadwal-mengajar.php" class="flex items-end gap-2">
            <div>
                <label for="filter-semester" class="mb-1.5 block text-sm font-medium text-slate-700">Semester</label>
                <select id="filter-semester" name="semester_id" class="min-h-10 rounded-lg border border-[#d8e2d8] bg-white px-3 text-sm">
                    <option value="0" <?= $filterSemesterId === 0 ? 'selected' : '' ?>>Semua semester</option>
                    <?php foreach ($semesterList as $semester): ?>
                        <option value="<?= h((string) $semester['id']) ?>" <?= (int) $semester['id'] === $filterSemesterId ? 'selected' : '' ?>>
                            <?= h((string) $semester['nama']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="inline-flex min-h-10 items-center justify-center rounded-lg bg-[#1e5638] px-4 py-2 text-sm font-semibold text-white hover:bg-[#17452d]">Tampilkan</button>
        </form>
    </div>

    <?php if ($flash !== null): ?>
        <?php $flashType = ($flash['type'] ?? 'success') === 'error' ? 'error' : 'success'; ?>
        <p role="status" class="rounded-xl px-4 py-3 text-sm <?= $flashType === 'error' ? 'bg-red-50 text-red-700' : 'bg-green-50 text-green-700' ?>">
            <?= h((string) ($flash['message'] ?? '')) ?>
        </p>
    <?php endif; ?>

    <?php if ($errors !== []): ?>
        <ul class="space-y-1 rounded-xl bg-red-50 px-4 py-3 text-sm text-red-700" role="alert">
            <?php foreach ($errors as $error): ?>
                <li>• <?= h($error) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <div class="overflow-x-auto rounded-2xl border border-[#e2e9e2] bg-white">
        <table class="min-w-full divide-y divide-[#edf1ec] text-left text-sm">
            <thead class="bg-[#f3f6f1] text-xs uppercase tracking-wide text-slate-500">
                <tr>
                    <th scope="col" class="whitespace-nowrap px-4 py-3.5 font-semibold sm:px-5">No</th>
                    <th scope="col" class="whitespace-nowrap px-4 py-3.5 font-semibold sm:px-5">Guru</th>
                    <th scope="col" class="whitespace-nowrap px-4 py-3.5 font-semibold sm:px-5">Kelas</th>
                    <th scope="col" class="whitespace-nowrap px-4 py-3.5 font-semibold sm:px-5">Semester</th>
                    <th scope="col" class="whitespace-nowrap px-4 py-3.5 font-semibold sm:px-5">Hari</th>
                    <th scope="col" class="whitespace-nowrap px-4 py-3.5 font-semibold sm:px-5">Jam</th>
                    <th scope="col" class="whitespace-nowrap px-4 py-3.5 font-semibold sm:px-5">Berlaku</th>
                    <th scope="col" class="whitespace-nowrap px-4 py-3.5 font-semibold sm:px-5">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-[#edf1ec] text-slate-700">
                <?php if ($scheduleList === []): ?>
                    <tr><td colspan="8" class="px-4 py-9 text-center text-slate-500">Belum ada jadwal mengajar.</td></tr>
                <?php else: ?>
                    <?php foreach ($scheduleList as $index => $schedule): ?>
                        <?php
                        $dayNumber = (int) $schedule['hari'];
                        $dayLabel = JADWAL_DAYS[$dayNumber] ?? 'Hari ' . $dayNumber;
                        $validFrom = ($schedule['berlaku_mulai'] ?? '') !== '' ? (string) $schedule['berlaku_mulai'] : '';
                        $validUntil = ($schedule['berlaku_sampai'] ?? '') !== '' ? (string) $schedule['berlaku_sampai'] : '';
                        $validity = $validFrom === '' && $validUntil === ''
                            ? 'Selalu'
                            : ($validFrom !== '' ? $validFrom : '—') . ' s.d. ' . ($validUntil !== '' ? $validUntil : '—');
                        ?>
                        <tr class="hover:bg-[#f8faf7]">
                            <td class="px-4 py-3.5 sm:px-5"><?= h((string) ($index + 1)) ?></td>
                            <td class="px-4 py-3.5 sm:px-5"><?= h((string) $schedule['guru_nama']) ?></td>
                            <td class="px-4 py-3.5 sm:px-5"><?= h((string) $schedule['kelas_nama']) ?></td>
                            <td class="px-4 py-3.5 sm:px-5"><?= h((string) $schedule['semester_nama']) ?></td>
                            <td class="px-4 py-3.5 sm:px-5"><?= h($dayLabel . ' (' . $dayNumber . ')') ?></td>
                            <td class="whitespace-nowrap px-4 py-3.5 sm:px-5"><?= h(substr((string) $schedule['jam_mulai'], 0, 5) . '–' . substr((string) $schedule['jam_selesai'], 0, 5)) ?></td>
                            <td class="whitespace-nowrap px-4 py-3.5 sm:px-5"><?= h($validity) ?></td>
                            <td class="px-4 py-3.5 sm:px-5">
                                <div class="flex items-center gap-2">
                                    <a href="jadwal-mengajar.php?<?= $filterSemesterId > 0 ? 'semester_id=' . h((string) $filterSemesterId) . '&amp;' : '' ?>edit=<?= h((string) $schedule['id']) ?>" class="rounded-md px-2 py-1 text-xs font-semibold text-[#28583b] hover:bg-[#edf4ec]">Edit</a>
                                    <form method="post" action="jadwal-mengajar.php" class="inline" onsubmit="return confirm('Hapus jadwal ini?');">
                                        <input type="hidden" name="_csrf_token" value="<?= h(csrfToken()) ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= h((string) $schedule['id']) ?>">
                                        <input type="hidden" name="filter_semester_id" value="<?= h((string) $filterSemesterId) ?>">
                                        <button type="submit" class="rounded-md px-2 py-1 text-xs font-semibold text-red-700 hover:bg-red-50">Hapus</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<section id="form-jadwal" class="rounded-2xl border border-[#e2e9e2] bg-white p-5 sm:p-6">
    <h2 class="text-base font-semibold text-[#20392a]"><?= $isEditing ? 'Edit Jadwal' : 'Tambah Jadwal' ?></h2>
    <p class="mt-1 text-sm text-slate-600">
        Hari memakai angka sesuai konvensi aplikasi: 1 = Senin sampai 7 = Minggu. Jam boleh ditulis HH:MM atau HH:MM:SS.
    </p>

    <?php if ($assignmentOptions === []): ?>
        <p class="mt-4 rounded-xl bg-amber-50 px-4 py-3 text-sm text-amber-800">
            Belum ada penugasan guru. Buat penugasan guru terlebih dahulu sebelum menambah jadwal.
        </p>
    <?php else: ?>
        <form method="post" action="jadwal-mengajar.php<?= $filterSemesterId > 0 ? '?semester_id=' . h((string) $filterSemesterId) : '' ?>" class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <input type="hidden" name="_csrf_token" value="<?= h(csrfToken()) ?>">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="id" value="<?= h((string) $formValues['id']) ?>">
            <input type="hidden" name="filter_semester_id" value="<?= h((string) $filterSemesterId) ?>">

            <div class="lg:col-span-3">
                <label for="schedule-assignment" class="mb-1.5 block text-sm font-medium text-slate-700">Penugasan Guru</label>
                <select id="schedule-assignment" name="penugasan_guru_id" class="min-h-10 w-full rounded-lg border border-[#d8e2d8] bg-white px-3 text-sm">
                    <option value="0">Pilih penugasan</option>
                    <?php foreach ($assignmentOptions as $option): ?>
                        <option value="<?= h((string) $option['id']) ?>" <?= (int) $option['id'] === $formValues['penugasan_guru_id'] ? 'selected' : '' ?>>
                            #<?= h((string) $option['id']) ?> · <?= h((string) $option['guru_nama']) ?> · <?= h((string) $option['kelas_nama']) ?> · <?= h((string) $option['semester_nama']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label for="schedule-day" class="mb-1.5 block text-sm font-medium text-slate-700">Hari</label>
                <select id="schedule-day" name="hari" class="min-h-10 w-full rounded-lg border border-[#d8e2d8] bg-white px-3 text-sm">
                    <option value="0">Pilih hari</option>
                    <?php foreach (JADWAL_DAYS as $number => $dayName): ?>
                        <option value="<?= h((string) $number) ?>" <?= $number === $formValues['hari'] ? 'selected' : '' ?>>
                            <?= h($number . ' · ' . $dayName) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <?php
            attendara_ui_input('Jam mulai', 'jam_mulai', $formValues['jam_mulai'], 'text', 'Contoh: 07:00');
            attendara_ui_input('Jam selesai', 'jam_selesai', $formValues['jam_selesai'], 'text', 'Contoh: 08:30');
            attendara_ui_input('Berlaku mulai (opsional)', 'berlaku_mulai', $formValues['berlaku_mulai'], 'date');
            attendara_ui_input('Berlaku sampai (opsional)', 'berlaku_sampai', $formValues['berlaku_sampai'], 'date');
            ?>

            <div class="flex items-center gap-3 sm:col-span-2 lg:col-span-3">
                <button type="submit" class="inline-flex min-h-10 items-center justify-center rounded-lg bg-[#1e5638] px-4 py-2 text-sm font-semibold text-white hover:bg-[#17452d] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#376c4d] focus-visible:ring-offset-2">
                    <?= $isEditing ? 'Simpan Perubahan' : 'Simpan Jadwal' ?>
                </button>
                <?php if ($isEditing): ?>
                    <a href="jadwal-mengajar.php<?= $filterSemesterId > 0 ? '?semester_id=' . h((string) $filterSemesterId) : '' ?>" class="inline-flex min-h-10 items-center justify-center rounded-lg border border-[#d8e2d8] bg-white px-4 py-2 text-sm font-semibold text-[#284b35] hover:bg-[#f2f6f2]">Batal</a>
                <?php endif; ?>
            </div>
        </form>
    <?php endif; ?>
</section>
<?php admin_page_end(); ?>
