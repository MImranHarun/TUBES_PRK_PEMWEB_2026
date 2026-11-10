<?php

declare(strict_types=1);

require_once __DIR__ . '/_init.php';
require_once __DIR__ . '/../config/database.php';

$errors = [];
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

/** Read an integer value from the POST body. */
function penugasan_post_int(string $key): int
{
    $value = $_POST[$key] ?? 0;
    return is_scalar($value) ? (int) $value : 0;
}

/** Send a flash message and return to the assignment list. */
function penugasan_redirect_with_flash(int $filterSemesterId, string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
    $query = $filterSemesterId > 0 ? '?semester_id=' . $filterSemesterId : '';
    header('Location: penugasan-guru.php' . $query);
    exit;
}

$isPost = $_SERVER['REQUEST_METHOD'] === 'POST';
$filterSemesterId = $isPost
    ? penugasan_post_int('filter_semester_id')
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
        $id = penugasan_post_int('id');
        $guruId = penugasan_post_int('guru_id');
        $kelasId = penugasan_post_int('kelas_id');
        $formSemesterId = penugasan_post_int('penugasan_semester_id');

        // Server-side validation against the current data.
        $statement = $pdo->prepare('SELECT COUNT(*) FROM guru WHERE id = :id AND is_active = TRUE');
        $statement->execute(['id' => $guruId]);
        if ((int) $statement->fetchColumn() === 0) {
            $errors[] = 'Guru yang dipilih tidak ditemukan atau sudah nonaktif.';
        }

        $statement = $pdo->prepare('SELECT COUNT(*) FROM kelas WHERE id = :id AND is_active = TRUE');
        $statement->execute(['id' => $kelasId]);
        if ((int) $statement->fetchColumn() === 0) {
            $errors[] = 'Kelas yang dipilih tidak ditemukan atau sudah nonaktif.';
        }

        $statement = $pdo->prepare('SELECT COUNT(*) FROM semester WHERE id = :id');
        $statement->execute(['id' => $formSemesterId]);
        if ((int) $statement->fetchColumn() === 0) {
            $errors[] = 'Semester yang dipilih tidak ditemukan.';
        }

        // A teacher can only be assigned once per class + semester.
        if ($errors === []) {
            $sql = 'SELECT COUNT(*) FROM penugasan_guru
                    WHERE guru_id = :guru_id AND kelas_id = :kelas_id AND semester_id = :semester_id';
            $params = ['guru_id' => $guruId, 'kelas_id' => $kelasId, 'semester_id' => $formSemesterId];
            if ($id > 0) {
                $sql .= ' AND id <> :id';
                $params['id'] = $id;
            }
            $statement = $pdo->prepare($sql);
            $statement->execute($params);
            if ((int) $statement->fetchColumn() > 0) {
                $errors[] = 'Guru tersebut sudah ditugaskan pada kelas dan semester ini.';
            }
        }

        if ($errors === []) {
            try {
                if ($id === 0) {
                    $statement = $pdo->prepare(
                        'INSERT INTO penugasan_guru (guru_id, kelas_id, semester_id)
                         VALUES (:guru_id, :kelas_id, :semester_id)'
                    );
                    $statement->execute([
                        'guru_id' => $guruId,
                        'kelas_id' => $kelasId,
                        'semester_id' => $formSemesterId,
                    ]);
                    penugasan_redirect_with_flash($filterSemesterId, 'success', 'Penugasan guru berhasil ditambahkan.');
                } else {
                    $statement = $pdo->prepare(
                        'UPDATE penugasan_guru
                         SET guru_id = :guru_id, kelas_id = :kelas_id, semester_id = :semester_id
                         WHERE id = :id'
                    );
                    $statement->execute([
                        'guru_id' => $guruId,
                        'kelas_id' => $kelasId,
                        'semester_id' => $formSemesterId,
                        'id' => $id,
                    ]);
                    penugasan_redirect_with_flash($filterSemesterId, 'success', 'Data penugasan berhasil diperbarui.');
                }
            } catch (PDOException $exception) {
                error_log('ATTENDARA save penugasan failed: ' . $exception->getMessage());
                $errors[] = $exception->getCode() === '23000'
                    ? 'Guru tersebut sudah ditugaskan pada kelas dan semester ini.'
                    : 'Data penugasan gagal disimpan.';
            }
        }
    }

    if ($action === 'delete') {
        $id = penugasan_post_int('id');

        try {
            $pdo->beginTransaction();

            $statement = $pdo->prepare('SELECT id FROM penugasan_guru WHERE id = :id FOR UPDATE');
            $statement->execute(['id' => $id]);
            if ($statement->fetchColumn() === false) {
                $pdo->rollBack();
                penugasan_redirect_with_flash($filterSemesterId, 'error', 'Data penugasan tidak ditemukan.');
            }

            // Schedules reference the assignment through a restrictive foreign key.
            $statement = $pdo->prepare('SELECT COUNT(*) FROM jadwal_mengajar WHERE penugasan_guru_id = :id');
            $statement->execute(['id' => $id]);
            if ((int) $statement->fetchColumn() > 0) {
                $pdo->rollBack();
                penugasan_redirect_with_flash(
                    $filterSemesterId,
                    'error',
                    'Penugasan masih memiliki jadwal mengajar. Hapus jadwalnya terlebih dahulu.'
                );
            }

            $statement = $pdo->prepare('DELETE FROM penugasan_guru WHERE id = :id');
            $statement->execute(['id' => $id]);

            $pdo->commit();
            penugasan_redirect_with_flash($filterSemesterId, 'success', 'Penugasan guru berhasil dihapus.');
        } catch (PDOException $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('ATTENDARA delete penugasan failed: ' . $exception->getMessage());
            penugasan_redirect_with_flash($filterSemesterId, 'error', 'Penugasan guru gagal dihapus.');
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

// Default the list filter to the active semester on a fresh page load.
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

$guruList = $pdo->query(
    'SELECT id, nomor_induk, nama FROM guru WHERE is_active = TRUE ORDER BY nama, nomor_induk'
)->fetchAll();

$kelasList = $pdo->query(
    'SELECT id, kode, nama, tingkat FROM kelas WHERE is_active = TRUE ORDER BY tingkat IS NULL, tingkat, nama'
)->fetchAll();

// Assignment being edited (only via GET so form repopulation stays predictable).
$editAssignment = null;
if ($editId > 0) {
    $statement = $pdo->prepare(
        'SELECT id, guru_id, kelas_id, semester_id FROM penugasan_guru WHERE id = :id LIMIT 1'
    );
    $statement->execute(['id' => $editId]);
    $editAssignment = $statement->fetch() ?: null;
}

// Repopulate the form after a failed POST, otherwise use the edit row or defaults.
$formValues = [
    'id' => 0,
    'guru_id' => 0,
    'kelas_id' => 0,
    'semester_id' => $filterSemesterId > 0 ? $filterSemesterId : $activeSemesterId,
];
if ($isPost && ($_POST['action'] ?? '') === 'save' && $errors !== []) {
    $formValues = [
        'id' => penugasan_post_int('id'),
        'guru_id' => penugasan_post_int('guru_id'),
        'kelas_id' => penugasan_post_int('kelas_id'),
        'semester_id' => penugasan_post_int('penugasan_semester_id'),
    ];
} elseif ($editAssignment !== null) {
    $formValues = [
        'id' => (int) $editAssignment['id'],
        'guru_id' => (int) $editAssignment['guru_id'],
        'kelas_id' => (int) $editAssignment['kelas_id'],
        'semester_id' => (int) $editAssignment['semester_id'],
    ];
}
$isEditing = $formValues['id'] > 0;

// Assignment list for the selected semester (0 = all semesters).
$listSql = 'SELECT p.id, p.created_at,
                   g.id AS guru_id, g.nama AS guru_nama, g.nomor_induk AS guru_nomor,
                   k.nama AS kelas_nama, s.nama AS semester_nama,
                   (SELECT COUNT(*) FROM jadwal_mengajar j WHERE j.penugasan_guru_id = p.id) AS jumlah_jadwal
            FROM penugasan_guru p
            INNER JOIN guru g ON g.id = p.guru_id
            INNER JOIN kelas k ON k.id = p.kelas_id
            INNER JOIN semester s ON s.id = p.semester_id';
$listParams = [];
if ($filterSemesterId > 0) {
    $listSql .= ' WHERE p.semester_id = :semester_id';
    $listParams['semester_id'] = $filterSemesterId;
}
$listSql .= ' ORDER BY s.tanggal_mulai DESC, k.nama, g.nama';

$statement = $pdo->prepare($listSql);
$statement->execute($listParams);
$assignmentList = $statement->fetchAll();

admin_page_start('Penugasan Guru', 'penugasan-guru.php');
?>
<section class="space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-lg font-semibold text-[#20392a]">Daftar Penugasan</h2>
            <p class="mt-1 text-sm text-slate-600">Satu guru dapat ditugaskan pada beberapa kelas per semester.</p>
        </div>
        <form method="get" action="penugasan-guru.php" class="flex items-end gap-2">
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
                    <th scope="col" class="whitespace-nowrap px-4 py-3.5 font-semibold sm:px-5">Jadwal</th>
                    <th scope="col" class="whitespace-nowrap px-4 py-3.5 font-semibold sm:px-5">Dibuat</th>
                    <th scope="col" class="whitespace-nowrap px-4 py-3.5 font-semibold sm:px-5">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-[#edf1ec] text-slate-700">
                <?php if ($assignmentList === []): ?>
                    <tr><td colspan="7" class="px-4 py-9 text-center text-slate-500">Belum ada penugasan guru.</td></tr>
                <?php else: ?>
                    <?php foreach ($assignmentList as $index => $assignment): ?>
                        <tr class="hover:bg-[#f8faf7]">
                            <td class="px-4 py-3.5 sm:px-5"><?= h((string) ($index + 1)) ?></td>
                            <td class="px-4 py-3.5 sm:px-5">
                                <span class="block font-medium text-slate-700"><?= h((string) $assignment['guru_nama']) ?></span>
                                <span class="block text-xs text-slate-500"><?= h((string) $assignment['guru_nomor']) ?></span>
                            </td>
                            <td class="px-4 py-3.5 sm:px-5"><?= h((string) $assignment['kelas_nama']) ?></td>
                            <td class="px-4 py-3.5 sm:px-5"><?= h((string) $assignment['semester_nama']) ?></td>
                            <td class="px-4 py-3.5 sm:px-5"><?= h((string) (int) $assignment['jumlah_jadwal']) ?></td>
                            <td class="px-4 py-3.5 sm:px-5"><?= h((string) $assignment['created_at']) ?></td>
                            <td class="px-4 py-3.5 sm:px-5">
                                <div class="flex items-center gap-2">
                                    <a href="penugasan-guru.php?<?= $filterSemesterId > 0 ? 'semester_id=' . h((string) $filterSemesterId) . '&amp;' : '' ?>edit=<?= h((string) $assignment['id']) ?>" class="rounded-md px-2 py-1 text-xs font-semibold text-[#28583b] hover:bg-[#edf4ec]">Edit</a>
                                    <form method="post" action="penugasan-guru.php" class="inline" onsubmit="return confirm('Hapus penugasan ini?');">
                                        <input type="hidden" name="_csrf_token" value="<?= h(csrfToken()) ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= h((string) $assignment['id']) ?>">
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

<section id="form-penugasan" class="rounded-2xl border border-[#e2e9e2] bg-white p-5 sm:p-6">
    <h2 class="text-base font-semibold text-[#20392a]"><?= $isEditing ? 'Edit Penugasan Guru' : 'Tambah Penugasan Guru' ?></h2>
    <p class="mt-1 text-sm text-slate-600">Guru, kelas, dan semester harus unik untuk setiap penugasan.</p>

    <form method="post" action="penugasan-guru.php<?= $filterSemesterId > 0 ? '?semester_id=' . h((string) $filterSemesterId) : '' ?>" class="mt-4 grid gap-4 sm:grid-cols-3">
        <input type="hidden" name="_csrf_token" value="<?= h(csrfToken()) ?>">
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" value="<?= h((string) $formValues['id']) ?>">
        <input type="hidden" name="filter_semester_id" value="<?= h((string) $filterSemesterId) ?>">

        <div>
            <label for="assignment-teacher" class="mb-1.5 block text-sm font-medium text-slate-700">Guru</label>
            <select id="assignment-teacher" name="guru_id" class="min-h-10 w-full rounded-lg border border-[#d8e2d8] bg-white px-3 text-sm">
                <option value="0">Pilih guru</option>
                <?php foreach ($guruList as $guru): ?>
                    <option value="<?= h((string) $guru['id']) ?>" <?= (int) $guru['id'] === $formValues['guru_id'] ? 'selected' : '' ?>>
                        <?= h((string) $guru['nama']) ?> · <?= h((string) $guru['nomor_induk']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label for="assignment-class" class="mb-1.5 block text-sm font-medium text-slate-700">Kelas</label>
            <select id="assignment-class" name="kelas_id" class="min-h-10 w-full rounded-lg border border-[#d8e2d8] bg-white px-3 text-sm">
                <option value="0">Pilih kelas</option>
                <?php foreach ($kelasList as $kelas): ?>
                    <option value="<?= h((string) $kelas['id']) ?>" <?= (int) $kelas['id'] === $formValues['kelas_id'] ? 'selected' : '' ?>>
                        <?= h((string) $kelas['nama']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label for="assignment-semester" class="mb-1.5 block text-sm font-medium text-slate-700">Semester</label>
            <select id="assignment-semester" name="penugasan_semester_id" class="min-h-10 w-full rounded-lg border border-[#d8e2d8] bg-white px-3 text-sm">
                <option value="0">Pilih semester</option>
                <?php foreach ($semesterList as $semester): ?>
                    <option value="<?= h((string) $semester['id']) ?>" <?= (int) $semester['id'] === $formValues['semester_id'] ? 'selected' : '' ?>>
                        <?= h((string) $semester['nama']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="flex items-center gap-3 sm:col-span-3">
            <button type="submit" class="inline-flex min-h-10 items-center justify-center rounded-lg bg-[#1e5638] px-4 py-2 text-sm font-semibold text-white hover:bg-[#17452d] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#376c4d] focus-visible:ring-offset-2">
                <?= $isEditing ? 'Simpan Perubahan' : 'Simpan Penugasan' ?>
            </button>
            <?php if ($isEditing): ?>
                <a href="penugasan-guru.php<?= $filterSemesterId > 0 ? '?semester_id=' . h((string) $filterSemesterId) : '' ?>" class="inline-flex min-h-10 items-center justify-center rounded-lg border border-[#d8e2d8] bg-white px-4 py-2 text-sm font-semibold text-[#284b35] hover:bg-[#f2f6f2]">Batal</a>
            <?php endif; ?>
        </div>
    </form>
</section>
<?php admin_page_end(); ?>
