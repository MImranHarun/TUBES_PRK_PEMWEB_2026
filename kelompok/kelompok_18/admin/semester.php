<?php

declare(strict_types=1);

require_once __DIR__ . '/_init.php';
require_once __DIR__ . '/../config/database.php';

$errors = [];
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

/** Read a trimmed string value from the POST body. */
function semester_post_string(string $key): string
{
    $value = $_POST[$key] ?? '';
    return is_string($value) ? trim($value) : '';
}

/** Send a flash message and return to the semester list (Post/Redirect/Get). */
function semester_redirect_with_flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
    header('Location: semester.php');
    exit;
}

$editId = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;
if ($editId < 0) {
    $editId = 0;
}

// ---------------------------------------------------------------
// Handle POST actions (save and set-active). CSRF is mandatory.
// ---------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['_csrf_token'] ?? null)) {
        http_response_code(403);
        exit('Permintaan tidak valid.');
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'activate') {
        $semesterId = (int) ($_POST['semester_id'] ?? 0);

        try {
            $pdo->beginTransaction();

            $statement = $pdo->prepare('SELECT status FROM semester WHERE id = :id FOR UPDATE');
            $statement->execute(['id' => $semesterId]);
            $target = $statement->fetch();

            if (!is_array($target)) {
                $pdo->rollBack();
                semester_redirect_with_flash('error', 'Data semester tidak ditemukan.');
            }

            // Only one active semester is allowed. The previous active becomes 'selesai'.
            $statement = $pdo->prepare("UPDATE semester SET status = 'selesai' WHERE status = 'aktif' AND id <> :id");
            $statement->execute(['id' => $semesterId]);

            $statement = $pdo->prepare("UPDATE semester SET status = 'aktif' WHERE id = :id");
            $statement->execute(['id' => $semesterId]);

            $pdo->commit();
            semester_redirect_with_flash('success', 'Semester aktif berhasil ditetapkan.');
        } catch (PDOException $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('ATTENDARA activate semester failed: ' . $exception->getMessage());
            semester_redirect_with_flash('error', 'Semester aktif gagal ditetapkan.');
        }
    }

    if ($action === 'save') {
        $id = (int) ($_POST['id'] ?? 0);
        $kode = semester_post_string('kode');
        $nama = semester_post_string('nama');
        $tanggalMulai = semester_post_string('tanggal_mulai');
        $tanggalSelesai = semester_post_string('tanggal_selesai');

        // Server-side validation.
        if ($kode === '') {
            $errors[] = 'Kode semester wajib diisi.';
        } elseif (mb_strlen($kode) > 40) {
            $errors[] = 'Kode semester maksimal 40 karakter.';
        }

        if ($nama === '') {
            $errors[] = 'Nama semester wajib diisi.';
        } elseif (mb_strlen($nama) > 100) {
            $errors[] = 'Nama semester maksimal 100 karakter.';
        }

        $start = DateTime::createFromFormat('Y-m-d', $tanggalMulai);
        $validStart = $start instanceof DateTime && $start->format('Y-m-d') === $tanggalMulai;
        if ($tanggalMulai === '' || !$validStart) {
            $errors[] = 'Tanggal mulai wajib diisi dengan format yang valid.';
        }

        $end = DateTime::createFromFormat('Y-m-d', $tanggalSelesai);
        $validEnd = $end instanceof DateTime && $end->format('Y-m-d') === $tanggalSelesai;
        if ($tanggalSelesai === '' || !$validEnd) {
            $errors[] = 'Tanggal selesai wajib diisi dengan format yang valid.';
        }

        if ($validStart && $validEnd && $tanggalSelesai < $tanggalMulai) {
            $errors[] = 'Tanggal selesai tidak boleh lebih awal dari tanggal mulai.';
        }

        // Uniqueness check with a friendly message.
        if ($errors === []) {
            $sql = 'SELECT COUNT(*) FROM semester WHERE kode = :kode';
            $params = ['kode' => $kode];
            if ($id > 0) {
                $sql .= ' AND id <> :id';
                $params['id'] = $id;
            }
            $statement = $pdo->prepare($sql);
            $statement->execute($params);
            if ((int) $statement->fetchColumn() > 0) {
                $errors[] = 'Kode semester sudah digunakan.';
            }
        }

        if ($errors === []) {
            try {
                if ($id === 0) {
                    $statement = $pdo->prepare(
                        "INSERT INTO semester (kode, nama, tanggal_mulai, tanggal_selesai, status)
                         VALUES (:kode, :nama, :tanggal_mulai, :tanggal_selesai, 'belum_aktif')"
                    );
                    $statement->execute([
                        'kode' => $kode,
                        'nama' => $nama,
                        'tanggal_mulai' => $tanggalMulai,
                        'tanggal_selesai' => $tanggalSelesai,
                    ]);
                    semester_redirect_with_flash('success', 'Semester berhasil ditambahkan.');
                } else {
                    $statement = $pdo->prepare(
                        'UPDATE semester
                         SET kode = :kode, nama = :nama, tanggal_mulai = :tanggal_mulai, tanggal_selesai = :tanggal_selesai
                         WHERE id = :id'
                    );
                    $statement->execute([
                        'kode' => $kode,
                        'nama' => $nama,
                        'tanggal_mulai' => $tanggalMulai,
                        'tanggal_selesai' => $tanggalSelesai,
                        'id' => $id,
                    ]);
                    semester_redirect_with_flash('success', 'Data semester berhasil diperbarui.');
                }
            } catch (PDOException $exception) {
                error_log('ATTENDARA save semester failed: ' . $exception->getMessage());
                $errors[] = $exception->getCode() === '23000'
                    ? 'Kode semester sudah digunakan.'
                    : 'Data semester gagal disimpan.';
            }
        }
    }
}

// ---------------------------------------------------------------
// Load data for the edit form and the table.
// ---------------------------------------------------------------
$editSemester = null;
if ($editId > 0) {
    $statement = $pdo->prepare(
        'SELECT id, kode, nama, tanggal_mulai, tanggal_selesai FROM semester WHERE id = :id LIMIT 1'
    );
    $statement->execute(['id' => $editId]);
    $editSemester = $statement->fetch() ?: null;
}

$statement = $pdo->query(
    'SELECT id, kode, nama, tanggal_mulai, tanggal_selesai, status
     FROM semester
     ORDER BY tanggal_mulai DESC, id DESC'
);
$semesterList = $statement->fetchAll();

// Repopulate the form from the last POST when validation failed.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save' && $errors !== []) {
    $formValues = [
        'id' => (int) ($_POST['id'] ?? 0),
        'kode' => semester_post_string('kode'),
        'nama' => semester_post_string('nama'),
        'tanggal_mulai' => semester_post_string('tanggal_mulai'),
        'tanggal_selesai' => semester_post_string('tanggal_selesai'),
    ];
} elseif ($editSemester !== null) {
    $formValues = [
        'id' => (int) $editSemester['id'],
        'kode' => (string) $editSemester['kode'],
        'nama' => (string) $editSemester['nama'],
        'tanggal_mulai' => (string) $editSemester['tanggal_mulai'],
        'tanggal_selesai' => (string) $editSemester['tanggal_selesai'],
    ];
} else {
    $formValues = [
        'id' => 0,
        'kode' => '',
        'nama' => '',
        'tanggal_mulai' => '',
        'tanggal_selesai' => '',
    ];
}

$isEditing = $formValues['id'] > 0;

/** Map a semester status to a Tailwind badge class pair. */
function semester_status_badge_class(string $status): string
{
    return match ($status) {
        'aktif' => 'bg-green-50 text-green-700',
        'selesai' => 'bg-slate-100 text-slate-600',
        default => 'bg-amber-50 text-amber-700',
    };
}

admin_page_start($isEditing ? 'Edit Semester' : 'Data Semester', 'semester.php');
?>
<section class="space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-lg font-semibold text-[#20392a]">Daftar Semester</h2>
            <p class="mt-1 text-sm text-slate-600">Hanya satu semester yang berstatus aktif.</p>
        </div>
        <?php attendara_ui_button('Tambah Semester', 'semester.php'); ?>
    </div>

    <?php if ($flash !== null): ?>
        <?php $flashType = ($flash['type'] ?? 'success') === 'error' ? 'error' : 'success'; ?>
        <p role="status" class="rounded-xl px-4 py-3 text-sm <?= $flashType === 'error' ? 'bg-red-50 text-red-700' : 'bg-green-50 text-green-700' ?>">
            <?= h((string) ($flash['message'] ?? '')) ?>
        </p>
    <?php endif; ?>

    <div class="overflow-x-auto rounded-2xl border border-[#e2e9e2] bg-white">
        <table class="min-w-full divide-y divide-[#edf1ec] text-left text-sm">
            <thead class="bg-[#f3f6f1] text-xs uppercase tracking-wide text-slate-500">
                <tr>
                    <th scope="col" class="whitespace-nowrap px-4 py-3.5 font-semibold sm:px-5">No</th>
                    <th scope="col" class="whitespace-nowrap px-4 py-3.5 font-semibold sm:px-5">Kode</th>
                    <th scope="col" class="whitespace-nowrap px-4 py-3.5 font-semibold sm:px-5">Nama</th>
                    <th scope="col" class="whitespace-nowrap px-4 py-3.5 font-semibold sm:px-5">Tanggal Mulai</th>
                    <th scope="col" class="whitespace-nowrap px-4 py-3.5 font-semibold sm:px-5">Tanggal Selesai</th>
                    <th scope="col" class="whitespace-nowrap px-4 py-3.5 font-semibold sm:px-5">Status</th>
                    <th scope="col" class="whitespace-nowrap px-4 py-3.5 font-semibold sm:px-5">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-[#edf1ec] text-slate-700">
                <?php if ($semesterList === []): ?>
                    <tr><td colspan="7" class="px-4 py-9 text-center text-slate-500">Belum ada data semester.</td></tr>
                <?php else: ?>
                    <?php foreach ($semesterList as $index => $semester): ?>
                        <?php $status = (string) $semester['status']; ?>
                        <tr class="hover:bg-[#f8faf7]">
                            <td class="whitespace-nowrap px-4 py-3.5 sm:px-5"><?= h((string) ($index + 1)) ?></td>
                            <td class="whitespace-nowrap px-4 py-3.5 sm:px-5"><?= h((string) $semester['kode']) ?></td>
                            <td class="whitespace-nowrap px-4 py-3.5 sm:px-5"><?= h((string) $semester['nama']) ?></td>
                            <td class="whitespace-nowrap px-4 py-3.5 sm:px-5"><?= h((string) $semester['tanggal_mulai']) ?></td>
                            <td class="whitespace-nowrap px-4 py-3.5 sm:px-5"><?= h((string) $semester['tanggal_selesai']) ?></td>
                            <td class="whitespace-nowrap px-4 py-3.5 sm:px-5">
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium <?= h(semester_status_badge_class($status)) ?>"><?= h($status) ?></span>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3.5 sm:px-5">
                                <div class="flex items-center gap-2">
                                    <a href="semester.php?edit=<?= h((string) $semester['id']) ?>" class="rounded-md px-2 py-1 text-xs font-semibold text-[#28583b] hover:bg-[#edf4ec]">Edit</a>
                                    <?php if ($status !== 'aktif'): ?>
                                        <form method="post" action="semester.php" class="inline">
                                            <input type="hidden" name="_csrf_token" value="<?= h(csrfToken()) ?>">
                                            <input type="hidden" name="action" value="activate">
                                            <input type="hidden" name="semester_id" value="<?= h((string) $semester['id']) ?>">
                                            <button type="submit" class="rounded-md px-2 py-1 text-xs font-semibold text-[#28583b] hover:bg-[#edf4ec]">Jadikan Aktif</button>
                                        </form>
                                    <?php else: ?>
                                        <span class="px-2 py-1 text-xs font-semibold text-green-700">Aktif</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<section id="form-semester" class="rounded-2xl border border-[#e2e9e2] bg-white p-5 sm:p-6">
    <h2 class="text-base font-semibold text-[#20392a]"><?= $isEditing ? 'Edit Semester' : 'Tambah Semester' ?></h2>
    <p class="mt-1 text-sm text-slate-600">
        Semester baru dibuat dengan status belum aktif. Riwayat semester lama tetap tersimpan.
    </p>

    <?php if ($errors !== []): ?>
        <ul class="mt-4 space-y-1 rounded-xl bg-red-50 px-4 py-3 text-sm text-red-700" role="alert">
            <?php foreach ($errors as $error): ?>
                <li>• <?= h($error) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <form method="post" action="semester.php" class="mt-4 grid gap-4 sm:grid-cols-2">
        <input type="hidden" name="_csrf_token" value="<?= h(csrfToken()) ?>">
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" value="<?= h((string) $formValues['id']) ?>">

        <?php
        attendara_ui_input('Kode', 'kode', $formValues['kode'], 'text', 'Contoh: 2026-GANJIL');
        attendara_ui_input('Nama', 'nama', $formValues['nama'], 'text', 'Contoh: 2026/2027 Ganjil');
        attendara_ui_input('Tanggal mulai', 'tanggal_mulai', $formValues['tanggal_mulai'], 'date');
        attendara_ui_input('Tanggal selesai', 'tanggal_selesai', $formValues['tanggal_selesai'], 'date');
        ?>

        <div class="flex items-center gap-3 sm:col-span-2">
            <button type="submit" class="inline-flex min-h-10 items-center justify-center rounded-lg bg-[#1e5638] px-4 py-2 text-sm font-semibold text-white hover:bg-[#17452d] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#376c4d] focus-visible:ring-offset-2">
                <?= $isEditing ? 'Simpan Perubahan' : 'Simpan Semester' ?>
            </button>
            <?php if ($isEditing): ?>
                <a href="semester.php" class="inline-flex min-h-10 items-center justify-center rounded-lg border border-[#d8e2d8] bg-white px-4 py-2 text-sm font-semibold text-[#284b35] hover:bg-[#f2f6f2]">Batal</a>
            <?php endif; ?>
        </div>
    </form>
</section>
<?php admin_page_end(); ?>
