<?php

declare(strict_types=1);

require_once __DIR__ . '/_init.php';
require_once __DIR__ . '/../config/database.php';

$errors = [];
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

/** Read a trimmed string value from the POST body. */
function siswa_post_string(string $key): string
{
    $value = $_POST[$key] ?? '';
    return is_string($value) ? trim($value) : '';
}

/** Send a flash message and return to the student list (Post/Redirect/Get). */
function siswa_redirect_with_flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
    header('Location: siswa.php');
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
        $siswaId = (int) ($_POST['siswa_id'] ?? 0);

        try {
            $statement = $pdo->prepare('SELECT is_active FROM siswa WHERE id = :id');
            $statement->execute(['id' => $siswaId]);
            $target = $statement->fetch();

            if (!is_array($target)) {
                siswa_redirect_with_flash('error', 'Data siswa tidak ditemukan.');
            }

            $newStatus = !(bool) $target['is_active'];
            $statement = $pdo->prepare('UPDATE siswa SET is_active = :active WHERE id = :id');
            $statement->execute(['active' => $newStatus ? 1 : 0, 'id' => $siswaId]);

            siswa_redirect_with_flash(
                'success',
                $newStatus ? 'Siswa berhasil diaktifkan.' : 'Siswa berhasil dinonaktifkan.'
            );
        } catch (PDOException $exception) {
            error_log('ATTENDARA toggle siswa failed: ' . $exception->getMessage());
            siswa_redirect_with_flash('error', 'Status siswa gagal diubah.');
        }
    }

    if ($action === 'save') {
        $id = (int) ($_POST['id'] ?? 0);
        $nomorInduk = siswa_post_string('nomor_induk');
        $nama = siswa_post_string('nama');
        $jenisKelamin = siswa_post_string('jenis_kelamin');
        $tanggalLahir = siswa_post_string('tanggal_lahir');

        // Server-side validation.
        if ($nomorInduk === '') {
            $errors[] = 'Nomor induk wajib diisi.';
        } elseif (mb_strlen($nomorInduk) > 50) {
            $errors[] = 'Nomor induk maksimal 50 karakter.';
        }

        if ($nama === '') {
            $errors[] = 'Nama wajib diisi.';
        } elseif (mb_strlen($nama) > 150) {
            $errors[] = 'Nama maksimal 150 karakter.';
        }

        if ($jenisKelamin !== '' && mb_strlen($jenisKelamin) > 20) {
            $errors[] = 'Jenis kelamin maksimal 20 karakter.';
        }

        $tanggalLahirValue = null;
        if ($tanggalLahir !== '') {
            $date = DateTime::createFromFormat('Y-m-d', $tanggalLahir);
            $isValidDate = $date instanceof DateTime && $date->format('Y-m-d') === $tanggalLahir;
            if (!$isValidDate) {
                $errors[] = 'Format tanggal lahir tidak valid.';
            } else {
                $tanggalLahirValue = $tanggalLahir;
            }
        }

        // Uniqueness check with a friendly message.
        if ($errors === []) {
            $sql = 'SELECT COUNT(*) FROM siswa WHERE nomor_induk = :nomor_induk';
            $params = ['nomor_induk' => $nomorInduk];
            if ($id > 0) {
                $sql .= ' AND id <> :id';
                $params['id'] = $id;
            }
            $statement = $pdo->prepare($sql);
            $statement->execute($params);
            if ((int) $statement->fetchColumn() > 0) {
                $errors[] = 'Nomor induk sudah digunakan.';
            }
        }

        if ($errors === []) {
            $jenisKelaminValue = $jenisKelamin === '' ? null : $jenisKelamin;

            try {
                if ($id === 0) {
                    $statement = $pdo->prepare(
                        'INSERT INTO siswa (nomor_induk, nama, jenis_kelamin, tanggal_lahir, is_active)
                         VALUES (:nomor_induk, :nama, :jenis_kelamin, :tanggal_lahir, TRUE)'
                    );
                    $statement->execute([
                        'nomor_induk' => $nomorInduk,
                        'nama' => $nama,
                        'jenis_kelamin' => $jenisKelaminValue,
                        'tanggal_lahir' => $tanggalLahirValue,
                    ]);
                    siswa_redirect_with_flash('success', 'Siswa berhasil ditambahkan.');
                } else {
                    $statement = $pdo->prepare(
                        'UPDATE siswa
                         SET nomor_induk = :nomor_induk, nama = :nama,
                             jenis_kelamin = :jenis_kelamin, tanggal_lahir = :tanggal_lahir
                         WHERE id = :id'
                    );
                    $statement->execute([
                        'nomor_induk' => $nomorInduk,
                        'nama' => $nama,
                        'jenis_kelamin' => $jenisKelaminValue,
                        'tanggal_lahir' => $tanggalLahirValue,
                        'id' => $id,
                    ]);
                    siswa_redirect_with_flash('success', 'Data siswa berhasil diperbarui.');
                }
            } catch (PDOException $exception) {
                error_log('ATTENDARA save siswa failed: ' . $exception->getMessage());
                $errors[] = $exception->getCode() === '23000'
                    ? 'Nomor induk sudah digunakan.'
                    : 'Data siswa gagal disimpan.';
            }
        }
    }
}

// ---------------------------------------------------------------
// Active semester is used to show the student's current class.
// ---------------------------------------------------------------
$statement = $pdo->prepare('SELECT id, nama FROM semester WHERE status = :status LIMIT 1');
$statement->execute(['status' => 'aktif']);
$activeSemester = $statement->fetch() ?: null;

// ---------------------------------------------------------------
// Detail view: profile plus placement history, never overwritten.
// ---------------------------------------------------------------
if ($detailId > 0) {
    $statement = $pdo->prepare(
        'SELECT id, nomor_induk, nama, jenis_kelamin, tanggal_lahir, is_active, created_at, updated_at
         FROM siswa WHERE id = :id LIMIT 1'
    );
    $statement->execute(['id' => $detailId]);
    $detailSiswa = $statement->fetch();

    if (!is_array($detailSiswa)) {
        siswa_redirect_with_flash('error', 'Data siswa tidak ditemukan.');
    }

    $statement = $pdo->prepare(
        'SELECT sm.nama AS semester_nama, sm.status AS semester_status,
                k.nama AS kelas_nama, ak.created_at
         FROM anggota_kelas AS ak
         JOIN semester AS sm ON sm.id = ak.semester_id
         JOIN kelas AS k ON k.id = ak.kelas_id
         WHERE ak.siswa_id = :id
         ORDER BY sm.tanggal_mulai DESC, sm.id DESC'
    );
    $statement->execute(['id' => $detailId]);
    $riwayat = $statement->fetchAll();

    admin_page_start('Detail Siswa', 'siswa.php');
    ?>
    <section class="space-y-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-lg font-semibold text-[#20392a]"><?= h((string) $detailSiswa['nama']) ?></h2>
                <p class="mt-1 text-sm text-slate-600">Nomor induk <?= h((string) $detailSiswa['nomor_induk']) ?></p>
            </div>
            <div class="flex items-center gap-2">
                <a href="siswa.php?edit=<?= h((string) $detailSiswa['id']) ?>" class="inline-flex min-h-10 items-center justify-center rounded-lg bg-[#1e5638] px-4 py-2 text-sm font-semibold text-white hover:bg-[#17452d]">Edit</a>
                <a href="siswa.php" class="inline-flex min-h-10 items-center justify-center rounded-lg border border-[#d8e2d8] bg-white px-4 py-2 text-sm font-semibold text-[#284b35] hover:bg-[#f2f6f2]">Kembali</a>
            </div>
        </div>

        <dl class="grid gap-4 rounded-2xl border border-[#e2e9e2] bg-white p-5 sm:grid-cols-2 sm:p-6">
            <div><dt class="text-sm font-medium text-slate-500">Jenis kelamin</dt><dd class="mt-1 text-sm text-slate-800"><?= h((string) ($detailSiswa['jenis_kelamin'] ?? '-')) ?></dd></div>
            <div><dt class="text-sm font-medium text-slate-500">Tanggal lahir</dt><dd class="mt-1 text-sm text-slate-800"><?= h((string) ($detailSiswa['tanggal_lahir'] ?? '-')) ?></dd></div>
            <div><dt class="text-sm font-medium text-slate-500">Status</dt><dd class="mt-1 text-sm text-slate-800"><?= (bool) $detailSiswa['is_active'] ? 'Aktif' : 'Nonaktif' ?></dd></div>
            <div><dt class="text-sm font-medium text-slate-500">Terdaftar</dt><dd class="mt-1 text-sm text-slate-800"><?= h((string) $detailSiswa['created_at']) ?></dd></div>
        </dl>

        <div class="space-y-3">
            <h3 class="text-base font-semibold text-[#20392a]">Riwayat Penempatan Kelas</h3>
            <div class="overflow-x-auto rounded-2xl border border-[#e2e9e2] bg-white">
                <table class="min-w-full divide-y divide-[#edf1ec] text-left text-sm">
                    <thead class="bg-[#f3f6f1] text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th scope="col" class="whitespace-nowrap px-4 py-3.5 font-semibold sm:px-5">Semester</th>
                            <th scope="col" class="whitespace-nowrap px-4 py-3.5 font-semibold sm:px-5">Kelas</th>
                            <th scope="col" class="whitespace-nowrap px-4 py-3.5 font-semibold sm:px-5">Status Semester</th>
                            <th scope="col" class="whitespace-nowrap px-4 py-3.5 font-semibold sm:px-5">Dicatat</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#edf1ec] text-slate-700">
                        <?php if ($riwayat === []): ?>
                            <tr><td colspan="4" class="px-4 py-9 text-center text-slate-500">Belum ada riwayat penempatan kelas.</td></tr>
                        <?php else: ?>
                            <?php foreach ($riwayat as $item): ?>
                                <tr class="hover:bg-[#f8faf7]">
                                    <td class="whitespace-nowrap px-4 py-3.5 sm:px-5"><?= h((string) $item['semester_nama']) ?></td>
                                    <td class="whitespace-nowrap px-4 py-3.5 sm:px-5"><?= h((string) $item['kelas_nama']) ?></td>
                                    <td class="whitespace-nowrap px-4 py-3.5 sm:px-5"><?= h((string) $item['semester_status']) ?></td>
                                    <td class="whitespace-nowrap px-4 py-3.5 sm:px-5"><?= h((string) $item['created_at']) ?></td>
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
$editSiswa = null;
if ($editId > 0) {
    $statement = $pdo->prepare(
        'SELECT id, nomor_induk, nama, jenis_kelamin, tanggal_lahir
         FROM siswa WHERE id = :id LIMIT 1'
    );
    $statement->execute(['id' => $editId]);
    $editSiswa = $statement->fetch() ?: null;
}

if ($activeSemester !== null) {
    $statement = $pdo->prepare(
        'SELECT s.id, s.nomor_induk, s.nama, s.jenis_kelamin, s.tanggal_lahir, s.is_active,
                k.nama AS kelas_nama
         FROM siswa AS s
         LEFT JOIN anggota_kelas AS ak ON ak.siswa_id = s.id AND ak.semester_id = :semester_id
         LEFT JOIN kelas AS k ON k.id = ak.kelas_id
         ORDER BY s.nama'
    );
    $statement->execute(['semester_id' => (int) $activeSemester['id']]);
} else {
    $statement = $pdo->query(
        'SELECT id, nomor_induk, nama, jenis_kelamin, tanggal_lahir, is_active, NULL AS kelas_nama
         FROM siswa ORDER BY nama'
    );
}
$siswaList = $statement->fetchAll();

// Repopulate the form from the last POST when validation failed.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save' && $errors !== []) {
    $formValues = [
        'id' => (int) ($_POST['id'] ?? 0),
        'nomor_induk' => siswa_post_string('nomor_induk'),
        'nama' => siswa_post_string('nama'),
        'jenis_kelamin' => siswa_post_string('jenis_kelamin'),
        'tanggal_lahir' => siswa_post_string('tanggal_lahir'),
    ];
} elseif ($editSiswa !== null) {
    $formValues = [
        'id' => (int) $editSiswa['id'],
        'nomor_induk' => (string) $editSiswa['nomor_induk'],
        'nama' => (string) $editSiswa['nama'],
        'jenis_kelamin' => (string) ($editSiswa['jenis_kelamin'] ?? ''),
        'tanggal_lahir' => (string) ($editSiswa['tanggal_lahir'] ?? ''),
    ];
} else {
    $formValues = [
        'id' => 0,
        'nomor_induk' => '',
        'nama' => '',
        'jenis_kelamin' => '',
        'tanggal_lahir' => '',
    ];
}

$isEditing = $formValues['id'] > 0;

admin_page_start($isEditing ? 'Edit Siswa' : 'Data Siswa', 'siswa.php');
?>
<section class="space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-lg font-semibold text-[#20392a]">Daftar Siswa</h2>
            <p class="mt-1 text-sm text-slate-600">Data profil serta kelas pada semester aktif.</p>
        </div>
        <?php attendara_ui_button('Tambah Siswa', 'siswa.php'); ?>
    </div>

    <?php if ($flash !== null): ?>
        <?php $flashType = ($flash['type'] ?? 'success') === 'error' ? 'error' : 'success'; ?>
        <p role="status" class="rounded-xl px-4 py-3 text-sm <?= $flashType === 'error' ? 'bg-red-50 text-red-700' : 'bg-green-50 text-green-700' ?>">
            <?= h((string) ($flash['message'] ?? '')) ?>
        </p>
    <?php endif; ?>

    <p class="text-sm text-slate-500">
        Kelas ditampilkan untuk <?= h($activeSemester !== null ? (string) $activeSemester['nama'] : 'semester aktif (belum ditetapkan)') ?>.
    </p>

    <div class="max-w-xl"><?php attendara_ui_input('Cari siswa', 'cari_siswa', '', 'text', 'Nama atau nomor induk'); ?></div>

    <div class="overflow-x-auto rounded-2xl border border-[#e2e9e2] bg-white">
        <table data-admin-table class="min-w-full divide-y divide-[#edf1ec] text-left text-sm">
            <thead class="bg-[#f3f6f1] text-xs uppercase tracking-wide text-slate-500">
                <tr>
                    <th scope="col" class="whitespace-nowrap px-4 py-3.5 font-semibold sm:px-5">No</th>
                    <th scope="col" class="whitespace-nowrap px-4 py-3.5 font-semibold sm:px-5">Nomor Induk</th>
                    <th scope="col" class="whitespace-nowrap px-4 py-3.5 font-semibold sm:px-5">Nama</th>
                    <th scope="col" class="whitespace-nowrap px-4 py-3.5 font-semibold sm:px-5">Jenis Kelamin</th>
                    <th scope="col" class="whitespace-nowrap px-4 py-3.5 font-semibold sm:px-5">Tanggal Lahir</th>
                    <th scope="col" class="whitespace-nowrap px-4 py-3.5 font-semibold sm:px-5">Kelas Saat Ini</th>
                    <th scope="col" class="whitespace-nowrap px-4 py-3.5 font-semibold sm:px-5">Status</th>
                    <th scope="col" class="whitespace-nowrap px-4 py-3.5 font-semibold sm:px-5">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-[#edf1ec] text-slate-700">
                <?php if ($siswaList === []): ?>
                    <tr><td colspan="8" class="px-4 py-9 text-center text-slate-500">Belum ada data siswa.</td></tr>
                <?php else: ?>
                    <?php foreach ($siswaList as $index => $siswa): ?>
                        <tr class="hover:bg-[#f8faf7]">
                            <td class="whitespace-nowrap px-4 py-3.5 sm:px-5"><?= h((string) ($index + 1)) ?></td>
                            <td class="whitespace-nowrap px-4 py-3.5 sm:px-5"><?= h((string) $siswa['nomor_induk']) ?></td>
                            <td class="whitespace-nowrap px-4 py-3.5 sm:px-5"><?= h((string) $siswa['nama']) ?></td>
                            <td class="whitespace-nowrap px-4 py-3.5 sm:px-5"><?= h((string) ($siswa['jenis_kelamin'] ?? '-')) ?></td>
                            <td class="whitespace-nowrap px-4 py-3.5 sm:px-5"><?= h((string) ($siswa['tanggal_lahir'] ?? '-')) ?></td>
                            <td class="whitespace-nowrap px-4 py-3.5 sm:px-5"><?= h((string) ($siswa['kelas_nama'] ?? '-')) ?></td>
                            <td class="whitespace-nowrap px-4 py-3.5 sm:px-5">
                                <?php if ((bool) $siswa['is_active']): ?>
                                    <span class="inline-flex rounded-full bg-green-50 px-2.5 py-1 text-xs font-medium text-green-700">Aktif</span>
                                <?php else: ?>
                                    <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600">Nonaktif</span>
                                <?php endif; ?>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3.5 sm:px-5">
                                <div class="flex items-center gap-2">
                                    <a href="siswa.php?detail=<?= h((string) $siswa['id']) ?>" class="rounded-md px-2 py-1 text-xs font-semibold text-[#28583b] hover:bg-[#edf4ec]">Detail</a>
                                    <a href="siswa.php?edit=<?= h((string) $siswa['id']) ?>" class="rounded-md px-2 py-1 text-xs font-semibold text-[#28583b] hover:bg-[#edf4ec]">Edit</a>
                                    <form method="post" action="siswa.php" class="inline">
                                        <input type="hidden" name="_csrf_token" value="<?= h(csrfToken()) ?>">
                                        <input type="hidden" name="action" value="toggle">
                                        <input type="hidden" name="siswa_id" value="<?= h((string) $siswa['id']) ?>">
                                        <button type="submit" class="rounded-md px-2 py-1 text-xs font-semibold <?= (bool) $siswa['is_active'] ? 'text-red-700 hover:bg-red-50' : 'text-[#28583b] hover:bg-[#edf4ec]' ?>">
                                            <?= (bool) $siswa['is_active'] ? 'Nonaktifkan' : 'Aktifkan' ?>
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
    <p id="siswa-search-empty" class="hidden rounded-xl bg-white px-4 py-6 text-center text-sm text-slate-500">Tidak ada siswa yang cocok dengan pencarian.</p>
</section>

<section id="form-siswa" class="rounded-2xl border border-[#e2e9e2] bg-white p-5 sm:p-6">
    <h2 class="text-base font-semibold text-[#20392a]"><?= $isEditing ? 'Edit Siswa' : 'Tambah Siswa' ?></h2>
    <p class="mt-1 text-sm text-slate-600">Siswa tidak memiliki akun login. Data historis tidak dihapus saat dinonaktifkan.</p>

    <?php if ($errors !== []): ?>
        <ul class="mt-4 space-y-1 rounded-xl bg-red-50 px-4 py-3 text-sm text-red-700" role="alert">
            <?php foreach ($errors as $error): ?>
                <li>• <?= h($error) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <form method="post" action="siswa.php" class="mt-4 grid gap-4 sm:grid-cols-2">
        <input type="hidden" name="_csrf_token" value="<?= h(csrfToken()) ?>">
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" value="<?= h((string) $formValues['id']) ?>">

        <?php
        attendara_ui_input('Nomor induk', 'nomor_induk', $formValues['nomor_induk']);
        attendara_ui_input('Nama', 'nama', $formValues['nama']);
        attendara_ui_input('Jenis kelamin (opsional)', 'jenis_kelamin', $formValues['jenis_kelamin']);
        attendara_ui_input('Tanggal lahir (opsional)', 'tanggal_lahir', $formValues['tanggal_lahir'], 'date');
        ?>

        <div class="flex items-center gap-3 sm:col-span-2">
            <button type="submit" class="inline-flex min-h-10 items-center justify-center rounded-lg bg-[#1e5638] px-4 py-2 text-sm font-semibold text-white hover:bg-[#17452d] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#376c4d] focus-visible:ring-offset-2">
                <?= $isEditing ? 'Simpan Perubahan' : 'Simpan Siswa' ?>
            </button>
            <?php if ($isEditing): ?>
                <a href="siswa.php" class="inline-flex min-h-10 items-center justify-center rounded-lg border border-[#d8e2d8] bg-white px-4 py-2 text-sm font-semibold text-[#284b35] hover:bg-[#f2f6f2]">Batal</a>
            <?php endif; ?>
        </div>
    </form>
</section>

<script>
document.getElementById('cari_siswa').addEventListener('input', event => {
    const query = event.target.value.trim().toLocaleLowerCase('id');
    const rows = [...document.querySelectorAll('[data-admin-table] tbody tr')];
    let visible = 0;
    rows.forEach(row => {
        const matches = row.textContent.toLocaleLowerCase('id').includes(query);
        row.hidden = !matches;
        if (matches) visible++;
    });
    document.getElementById('siswa-search-empty').classList.toggle('hidden', visible > 0);
});
</script>
<?php admin_page_end(); ?>
