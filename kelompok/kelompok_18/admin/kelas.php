<?php

declare(strict_types=1);

require_once __DIR__ . '/_init.php';
require_once __DIR__ . '/../config/database.php';

$errors = [];
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

/** Read a trimmed string value from the POST body. */
function kelas_post_string(string $key): string
{
    $value = $_POST[$key] ?? '';
    return is_string($value) ? trim($value) : '';
}

/** Send a flash message and return to the class list (Post/Redirect/Get). */
function kelas_redirect_with_flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
    header('Location: kelas.php');
    exit;
}

$editId = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;
if ($editId < 0) {
    $editId = 0;
}

$detailId = isset($_GET['detail']) ? (int) $_GET['detail'] : 0;
if ($detailId < 0) {
    $detailId = 0;
}

// ---------------------------------------------------------------
// Handle POST actions (save and toggle). CSRF is mandatory.
// ---------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['_csrf_token'] ?? null)) {
        http_response_code(403);
        exit('Permintaan tidak valid.');
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'toggle') {
        $kelasId = (int) ($_POST['kelas_id'] ?? 0);

        try {
            $statement = $pdo->prepare('SELECT is_active FROM kelas WHERE id = :id');
            $statement->execute(['id' => $kelasId]);
            $target = $statement->fetch();

            if (!is_array($target)) {
                kelas_redirect_with_flash('error', 'Data kelas tidak ditemukan.');
            }

            $newStatus = !(bool) $target['is_active'];
            $statement = $pdo->prepare('UPDATE kelas SET is_active = :active WHERE id = :id');
            $statement->execute(['active' => $newStatus ? 1 : 0, 'id' => $kelasId]);

            kelas_redirect_with_flash(
                'success',
                $newStatus ? 'Kelas berhasil diaktifkan.' : 'Kelas berhasil dinonaktifkan.'
            );
        } catch (PDOException $exception) {
            error_log('ATTENDARA toggle kelas failed: ' . $exception->getMessage());
            kelas_redirect_with_flash('error', 'Status kelas gagal diubah.');
        }
    }

    if ($action === 'save') {
        $id = (int) ($_POST['id'] ?? 0);
        $kode = kelas_post_string('kode');
        $nama = kelas_post_string('nama');
        $tingkat = kelas_post_string('tingkat');

        // Server-side validation.
        if ($kode === '') {
            $errors[] = 'Kode kelas wajib diisi.';
        } elseif (mb_strlen($kode) > 30) {
            $errors[] = 'Kode kelas maksimal 30 karakter.';
        }

        if ($nama === '') {
            $errors[] = 'Nama kelas wajib diisi.';
        } elseif (mb_strlen($nama) > 100) {
            $errors[] = 'Nama kelas maksimal 100 karakter.';
        }

        $tingkatValue = null;
        if ($tingkat !== '') {
            if (!ctype_digit($tingkat) || (int) $tingkat > 255) {
                $errors[] = 'Tingkat harus berupa angka 0-255.';
            } else {
                $tingkatValue = (int) $tingkat;
            }
        }

        // Uniqueness check with a friendly message.
        if ($errors === []) {
            $sql = 'SELECT COUNT(*) FROM kelas WHERE kode = :kode';
            $params = ['kode' => $kode];
            if ($id > 0) {
                $sql .= ' AND id <> :id';
                $params['id'] = $id;
            }
            $statement = $pdo->prepare($sql);
            $statement->execute($params);
            if ((int) $statement->fetchColumn() > 0) {
                $errors[] = 'Kode kelas sudah digunakan.';
            }
        }

        if ($errors === []) {
            try {
                if ($id === 0) {
                    $statement = $pdo->prepare(
                        'INSERT INTO kelas (kode, nama, tingkat, is_active)
                         VALUES (:kode, :nama, :tingkat, TRUE)'
                    );
                    $statement->execute([
                        'kode' => $kode,
                        'nama' => $nama,
                        'tingkat' => $tingkatValue,
                    ]);
                    kelas_redirect_with_flash('success', 'Kelas berhasil ditambahkan.');
                } else {
                    $statement = $pdo->prepare(
                        'UPDATE kelas SET kode = :kode, nama = :nama, tingkat = :tingkat WHERE id = :id'
                    );
                    $statement->execute([
                        'kode' => $kode,
                        'nama' => $nama,
                        'tingkat' => $tingkatValue,
                        'id' => $id,
                    ]);
                    kelas_redirect_with_flash('success', 'Data kelas berhasil diperbarui.');
                }
            } catch (PDOException $exception) {
                error_log('ATTENDARA save kelas failed: ' . $exception->getMessage());
                $errors[] = $exception->getCode() === '23000'
                    ? 'Kode kelas sudah digunakan.'
                    : 'Data kelas gagal disimpan.';
            }
        }
    }
}

// ---------------------------------------------------------------
// Active semester is used to count students and show membership.
// ---------------------------------------------------------------
$statement = $pdo->prepare('SELECT id, nama FROM semester WHERE status = :status LIMIT 1');
$statement->execute(['status' => 'aktif']);
$activeSemester = $statement->fetch() ?: null;

// ---------------------------------------------------------------
// Detail view: class info and its students in the active semester.
// ---------------------------------------------------------------
if ($detailId > 0) {
    $statement = $pdo->prepare('SELECT id, kode, nama, tingkat, is_active, created_at, updated_at FROM kelas WHERE id = :id LIMIT 1');
    $statement->execute(['id' => $detailId]);
    $detailKelas = $statement->fetch();

    if (!is_array($detailKelas)) {
        kelas_redirect_with_flash('error', 'Data kelas tidak ditemukan.');
    }

    $anggota = [];
    if ($activeSemester !== null) {
        $statement = $pdo->prepare(
            'SELECT s.nomor_induk, s.nama, s.is_active
             FROM anggota_kelas AS ak
             JOIN siswa AS s ON s.id = ak.siswa_id
             WHERE ak.kelas_id = :kelas_id AND ak.semester_id = :semester_id
             ORDER BY s.nama'
        );
        $statement->execute([
            'kelas_id' => $detailId,
            'semester_id' => (int) $activeSemester['id'],
        ]);
        $anggota = $statement->fetchAll();
    }

    admin_page_start('Detail Kelas', 'kelas.php');
    ?>
    <section class="space-y-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-lg font-semibold text-[#20392a]"><?= h((string) $detailKelas['nama']) ?></h2>
                <p class="mt-1 text-sm text-slate-600">Kode <?= h((string) $detailKelas['kode']) ?></p>
            </div>
            <div class="flex items-center gap-2">
                <a href="kelas.php?edit=<?= h((string) $detailKelas['id']) ?>" class="inline-flex min-h-10 items-center justify-center rounded-lg bg-[#1e5638] px-4 py-2 text-sm font-semibold text-white hover:bg-[#17452d]">Edit</a>
                <a href="kelas.php" class="inline-flex min-h-10 items-center justify-center rounded-lg border border-[#d8e2d8] bg-white px-4 py-2 text-sm font-semibold text-[#284b35] hover:bg-[#f2f6f2]">Kembali</a>
            </div>
        </div>

        <dl class="grid gap-4 rounded-2xl border border-[#e2e9e2] bg-white p-5 sm:grid-cols-2 sm:p-6">
            <div><dt class="text-sm font-medium text-slate-500">Tingkat</dt><dd class="mt-1 text-sm text-slate-800"><?= h((string) ($detailKelas['tingkat'] ?? '-')) ?></dd></div>
            <div><dt class="text-sm font-medium text-slate-500">Status</dt><dd class="mt-1 text-sm text-slate-800"><?= (bool) $detailKelas['is_active'] ? 'Aktif' : 'Nonaktif' ?></dd></div>
            <div><dt class="text-sm font-medium text-slate-500">Semester</dt><dd class="mt-1 text-sm text-slate-800"><?= h($activeSemester !== null ? (string) $activeSemester['nama'] : '-') ?></dd></div>
            <div><dt class="text-sm font-medium text-slate-500">Jumlah siswa</dt><dd class="mt-1 text-sm text-slate-800"><?= h((string) count($anggota)) ?></dd></div>
        </dl>

        <div class="space-y-3">
            <h3 class="text-base font-semibold text-[#20392a]">Anggota Kelas (Semester Aktif)</h3>
            <div class="overflow-x-auto rounded-2xl border border-[#e2e9e2] bg-white">
                <table class="min-w-full divide-y divide-[#edf1ec] text-left text-sm">
                    <thead class="bg-[#f3f6f1] text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th scope="col" class="whitespace-nowrap px-4 py-3.5 font-semibold sm:px-5">No</th>
                            <th scope="col" class="whitespace-nowrap px-4 py-3.5 font-semibold sm:px-5">Nomor Induk</th>
                            <th scope="col" class="whitespace-nowrap px-4 py-3.5 font-semibold sm:px-5">Nama</th>
                            <th scope="col" class="whitespace-nowrap px-4 py-3.5 font-semibold sm:px-5">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#edf1ec] text-slate-700">
                        <?php if ($anggota === []): ?>
                            <tr><td colspan="4" class="px-4 py-9 text-center text-slate-500">Belum ada siswa pada semester aktif.</td></tr>
                        <?php else: ?>
                            <?php foreach ($anggota as $index => $siswa): ?>
                                <tr class="hover:bg-[#f8faf7]">
                                    <td class="whitespace-nowrap px-4 py-3.5 sm:px-5"><?= h((string) ($index + 1)) ?></td>
                                    <td class="whitespace-nowrap px-4 py-3.5 sm:px-5"><?= h((string) $siswa['nomor_induk']) ?></td>
                                    <td class="whitespace-nowrap px-4 py-3.5 sm:px-5"><?= h((string) $siswa['nama']) ?></td>
                                    <td class="whitespace-nowrap px-4 py-3.5 sm:px-5"><?= (bool) $siswa['is_active'] ? 'Aktif' : 'Nonaktif' ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
    <?php
    admin_page_end();
    exit;
}

// ---------------------------------------------------------------
// Load data for the edit form and the table.
// ---------------------------------------------------------------
$editKelas = null;
if ($editId > 0) {
    $statement = $pdo->prepare('SELECT id, kode, nama, tingkat FROM kelas WHERE id = :id LIMIT 1');
    $statement->execute(['id' => $editId]);
    $editKelas = $statement->fetch() ?: null;
}

if ($activeSemester !== null) {
    $statement = $pdo->prepare(
        'SELECT k.id, k.kode, k.nama, k.tingkat, k.is_active, COUNT(ak.siswa_id) AS jumlah_siswa
         FROM kelas AS k
         LEFT JOIN anggota_kelas AS ak ON ak.kelas_id = k.id AND ak.semester_id = :semester_id
         GROUP BY k.id, k.kode, k.nama, k.tingkat, k.is_active
         ORDER BY k.kode'
    );
    $statement->execute(['semester_id' => (int) $activeSemester['id']]);
} else {
    $statement = $pdo->query(
        'SELECT id, kode, nama, tingkat, is_active, 0 AS jumlah_siswa
         FROM kelas ORDER BY kode'
    );
}
$kelasList = $statement->fetchAll();

// Repopulate the form from the last POST when validation failed.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save' && $errors !== []) {
    $formValues = [
        'id' => (int) ($_POST['id'] ?? 0),
        'kode' => kelas_post_string('kode'),
        'nama' => kelas_post_string('nama'),
        'tingkat' => kelas_post_string('tingkat'),
    ];
} elseif ($editKelas !== null) {
    $formValues = [
        'id' => (int) $editKelas['id'],
        'kode' => (string) $editKelas['kode'],
        'nama' => (string) $editKelas['nama'],
        'tingkat' => (string) ($editKelas['tingkat'] ?? ''),
    ];
} else {
    $formValues = [
        'id' => 0,
        'kode' => '',
        'nama' => '',
        'tingkat' => '',
    ];
}

$isEditing = $formValues['id'] > 0;

admin_page_start($isEditing ? 'Edit Kelas' : 'Data Kelas', 'kelas.php');
?>
<section class="space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-lg font-semibold text-[#20392a]">Daftar Kelas</h2>
            <p class="mt-1 text-sm text-slate-600">Jumlah siswa mengikuti semester aktif.</p>
        </div>
        <?php attendara_ui_button('Tambah Kelas', 'kelas.php'); ?>
    </div>

    <?php if ($flash !== null): ?>
        <?php $flashType = ($flash['type'] ?? 'success') === 'error' ? 'error' : 'success'; ?>
        <p role="status" class="rounded-xl px-4 py-3 text-sm <?= $flashType === 'error' ? 'bg-red-50 text-red-700' : 'bg-green-50 text-green-700' ?>">
            <?= h((string) ($flash['message'] ?? '')) ?>
        </p>
    <?php endif; ?>

    <p class="text-sm text-slate-500">
        Data untuk <?= h($activeSemester !== null ? (string) $activeSemester['nama'] : 'semester aktif (belum ditetapkan)') ?>.
    </p>

    <div class="overflow-x-auto rounded-2xl border border-[#e2e9e2] bg-white">
        <table class="min-w-full divide-y divide-[#edf1ec] text-left text-sm">
            <thead class="bg-[#f3f6f1] text-xs uppercase tracking-wide text-slate-500">
                <tr>
                    <th scope="col" class="whitespace-nowrap px-4 py-3.5 font-semibold sm:px-5">No</th>
                    <th scope="col" class="whitespace-nowrap px-4 py-3.5 font-semibold sm:px-5">Kode</th>
                    <th scope="col" class="whitespace-nowrap px-4 py-3.5 font-semibold sm:px-5">Nama</th>
                    <th scope="col" class="whitespace-nowrap px-4 py-3.5 font-semibold sm:px-5">Tingkat</th>
                    <th scope="col" class="whitespace-nowrap px-4 py-3.5 font-semibold sm:px-5">Jumlah Siswa</th>
                    <th scope="col" class="whitespace-nowrap px-4 py-3.5 font-semibold sm:px-5">Status</th>
                    <th scope="col" class="whitespace-nowrap px-4 py-3.5 font-semibold sm:px-5">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-[#edf1ec] text-slate-700">
                <?php if ($kelasList === []): ?>
                    <tr><td colspan="7" class="px-4 py-9 text-center text-slate-500">Belum ada data kelas.</td></tr>
                <?php else: ?>
                    <?php foreach ($kelasList as $index => $kelas): ?>
                        <tr class="hover:bg-[#f8faf7]">
                            <td class="whitespace-nowrap px-4 py-3.5 sm:px-5"><?= h((string) ($index + 1)) ?></td>
                            <td class="whitespace-nowrap px-4 py-3.5 sm:px-5"><?= h((string) $kelas['kode']) ?></td>
                            <td class="whitespace-nowrap px-4 py-3.5 sm:px-5"><?= h((string) $kelas['nama']) ?></td>
                            <td class="whitespace-nowrap px-4 py-3.5 sm:px-5"><?= h((string) ($kelas['tingkat'] ?? '-')) ?></td>
                            <td class="whitespace-nowrap px-4 py-3.5 sm:px-5"><?= h((string) $kelas['jumlah_siswa']) ?></td>
                            <td class="whitespace-nowrap px-4 py-3.5 sm:px-5">
                                <?php if ((bool) $kelas['is_active']): ?>
                                    <span class="inline-flex rounded-full bg-green-50 px-2.5 py-1 text-xs font-medium text-green-700">Aktif</span>
                                <?php else: ?>
                                    <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600">Nonaktif</span>
                                <?php endif; ?>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3.5 sm:px-5">
                                <div class="flex items-center gap-2">
                                    <a href="kelas.php?detail=<?= h((string) $kelas['id']) ?>" class="rounded-md px-2 py-1 text-xs font-semibold text-[#28583b] hover:bg-[#edf4ec]">Detail</a>
                                    <a href="kelas.php?edit=<?= h((string) $kelas['id']) ?>" class="rounded-md px-2 py-1 text-xs font-semibold text-[#28583b] hover:bg-[#edf4ec]">Edit</a>
                                    <form method="post" action="kelas.php" class="inline">
                                        <input type="hidden" name="_csrf_token" value="<?= h(csrfToken()) ?>">
                                        <input type="hidden" name="action" value="toggle">
                                        <input type="hidden" name="kelas_id" value="<?= h((string) $kelas['id']) ?>">
                                        <button type="submit" class="rounded-md px-2 py-1 text-xs font-semibold <?= (bool) $kelas['is_active'] ? 'text-red-700 hover:bg-red-50' : 'text-[#28583b] hover:bg-[#edf4ec]' ?>">
                                            <?= (bool) $kelas['is_active'] ? 'Nonaktifkan' : 'Aktifkan' ?>
                                        </button>
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

<section id="form-kelas" class="rounded-2xl border border-[#e2e9e2] bg-white p-5 sm:p-6">
    <h2 class="text-base font-semibold text-[#20392a]"><?= $isEditing ? 'Edit Kelas' : 'Tambah Kelas' ?></h2>
    <p class="mt-1 text-sm text-slate-600">Kelas dipakai untuk penempatan siswa dan penugasan guru per semester.</p>

    <?php if ($errors !== []): ?>
        <ul class="mt-4 space-y-1 rounded-xl bg-red-50 px-4 py-3 text-sm text-red-700" role="alert">
            <?php foreach ($errors as $error): ?>
                <li>• <?= h($error) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <form method="post" action="kelas.php" class="mt-4 grid gap-4 sm:grid-cols-3">
        <input type="hidden" name="_csrf_token" value="<?= h(csrfToken()) ?>">
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" value="<?= h((string) $formValues['id']) ?>">

        <?php
        attendara_ui_input('Kode', 'kode', $formValues['kode'], 'text', 'Contoh: X-A');
        attendara_ui_input('Nama', 'nama', $formValues['nama'], 'text', 'Nama kelas');
        attendara_ui_input('Tingkat (opsional)', 'tingkat', $formValues['tingkat'], 'number');
        ?>

        <div class="flex items-center gap-3 sm:col-span-3">
            <button type="submit" class="inline-flex min-h-10 items-center justify-center rounded-lg bg-[#1e5638] px-4 py-2 text-sm font-semibold text-white hover:bg-[#17452d] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#376c4d] focus-visible:ring-offset-2">
                <?= $isEditing ? 'Simpan Perubahan' : 'Simpan Kelas' ?>
            </button>
            <?php if ($isEditing): ?>
                <a href="kelas.php" class="inline-flex min-h-10 items-center justify-center rounded-lg border border-[#d8e2d8] bg-white px-4 py-2 text-sm font-semibold text-[#284b35] hover:bg-[#f2f6f2]">Batal</a>
            <?php endif; ?>
        </div>
    </form>
</section>
<?php admin_page_end(); ?>
