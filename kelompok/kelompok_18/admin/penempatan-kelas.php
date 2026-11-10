<?php

declare(strict_types=1);

require_once __DIR__ . '/_init.php';
require_once __DIR__ . '/../config/database.php';

$errors = [];
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

/** Read an integer value from the POST body. */
function penempatan_post_int(string $key): int
{
    $value = $_POST[$key] ?? 0;
    return is_scalar($value) ? (int) $value : 0;
}

/** Send a flash message and return to the placement page for one semester/class. */
function penempatan_redirect_with_flash(int $semesterId, int $kelasId, string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
    $query = http_build_query(array_filter([
        'semester_id' => $semesterId > 0 ? $semesterId : null,
        'kelas_id' => $kelasId > 0 ? $kelasId : null,
    ], static fn ($value): bool => $value !== null));
    header('Location: penempatan-kelas.php' . ($query === '' ? '' : '?' . $query));
    exit;
}

// The semester/class context comes from POST first (validation errors) then GET.
$isPost = $_SERVER['REQUEST_METHOD'] === 'POST';
$semesterId = $isPost ? penempatan_post_int('semester_id') : (isset($_GET['semester_id']) ? (int) $_GET['semester_id'] : 0);
$kelasId = $isPost ? penempatan_post_int('kelas_id') : (isset($_GET['kelas_id']) ? (int) $_GET['kelas_id'] : 0);

if ($semesterId < 0) {
    $semesterId = 0;
}
if ($kelasId < 0) {
    $kelasId = 0;
}

// ---------------------------------------------------------------
// Handle POST actions (place and remove). CSRF is mandatory.
// ---------------------------------------------------------------
if ($isPost) {
    if (!verifyCsrfToken($_POST['_csrf_token'] ?? null)) {
        http_response_code(403);
        exit('Permintaan tidak valid.');
    }

    $action = $_POST['action'] ?? '';

    // Validate the semester/class context for both actions.
    $semesterExists = false;
    if ($semesterId > 0) {
        $statement = $pdo->prepare('SELECT COUNT(*) FROM semester WHERE id = :id');
        $statement->execute(['id' => $semesterId]);
        $semesterExists = (int) $statement->fetchColumn() > 0;
    }
    if (!$semesterExists) {
        $errors[] = 'Semester yang dipilih tidak ditemukan.';
    }

    $kelasExists = false;
    if ($kelasId > 0) {
        $statement = $pdo->prepare('SELECT COUNT(*) FROM kelas WHERE id = :id AND is_active = TRUE');
        $statement->execute(['id' => $kelasId]);
        $kelasExists = (int) $statement->fetchColumn() > 0;
    }
    if (!$kelasExists) {
        $errors[] = 'Kelas yang dipilih tidak ditemukan atau sudah nonaktif.';
    }

    if ($action === 'place' && $errors === []) {
        // Collect a unique, positive list of student ids from the checkbox group.
        $rawStudentIds = $_POST['siswa_ids'] ?? [];
        $studentIds = [];
        if (is_array($rawStudentIds)) {
            foreach ($rawStudentIds as $value) {
                if (is_scalar($value) && (int) $value > 0) {
                    $studentIds[(int) $value] = (int) $value;
                }
            }
        }
        $studentIds = array_values($studentIds);

        if ($studentIds === []) {
            $errors[] = 'Pilih minimal satu siswa untuk ditempatkan.';
        } else {
            $placeholders = implode(', ', array_fill(0, count($studentIds), '?'));

            // Only active students can be placed.
            $statement = $pdo->prepare(
                'SELECT id FROM siswa WHERE is_active = TRUE AND id IN (' . $placeholders . ')'
            );
            $statement->execute($studentIds);
            $validIds = array_map('intval', $statement->fetchAll(PDO::FETCH_COLUMN));
            if (count($validIds) !== count($studentIds)) {
                $errors[] = 'Sebagian siswa tidak ditemukan atau sudah nonaktif.';
            }

            // A student may only have one placement per semester (keeps history intact).
            if ($errors === []) {
                $statement = $pdo->prepare(
                    'SELECT COUNT(*) FROM anggota_kelas
                     WHERE semester_id = ? AND siswa_id IN (' . $placeholders . ')'
                );
                $statement->execute(array_merge([$semesterId], $studentIds));
                if ((int) $statement->fetchColumn() > 0) {
                    $errors[] = 'Sebagian siswa sudah memiliki penempatan pada semester ini. Muat ulang halaman.';
                }
            }

            if ($errors === []) {
                try {
                    $pdo->beginTransaction();

                    $statement = $pdo->prepare(
                        'INSERT INTO anggota_kelas (siswa_id, kelas_id, semester_id)
                         VALUES (:siswa_id, :kelas_id, :semester_id)'
                    );
                    foreach ($studentIds as $studentId) {
                        $statement->execute([
                            'siswa_id' => $studentId,
                            'kelas_id' => $kelasId,
                            'semester_id' => $semesterId,
                        ]);
                    }

                    $pdo->commit();
                    penempatan_redirect_with_flash(
                        $semesterId,
                        $kelasId,
                        'success',
                        count($studentIds) . ' siswa berhasil ditempatkan.'
                    );
                } catch (PDOException $exception) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                    error_log('ATTENDARA place siswa failed: ' . $exception->getMessage());
                    $errors[] = $exception->getCode() === '23000'
                        ? 'Sebagian siswa sudah memiliki penempatan pada semester ini.'
                        : 'Penempatan siswa gagal disimpan.';
                }
            }
        }
    }

    if ($action === 'remove' && $errors === []) {
        $placementId = penempatan_post_int('placement_id');

        try {
            $pdo->beginTransaction();

            $statement = $pdo->prepare(
                'SELECT id, siswa_id, semester_id FROM anggota_kelas WHERE id = :id FOR UPDATE'
            );
            $statement->execute(['id' => $placementId]);
            $placement = $statement->fetch();

            if (!is_array($placement)) {
                $pdo->rollBack();
                penempatan_redirect_with_flash($semesterId, $kelasId, 'error', 'Data penempatan tidak ditemukan.');
            }

            // Only placements of the selected semester can be removed from this page.
            if ((int) $placement['semester_id'] !== $semesterId) {
                $pdo->rollBack();
                penempatan_redirect_with_flash($semesterId, $kelasId, 'error', 'Penempatan bukan milik semester yang dipilih.');
            }

            $statement = $pdo->prepare('DELETE FROM anggota_kelas WHERE id = :id');
            $statement->execute(['id' => $placementId]);

            $pdo->commit();
            penempatan_redirect_with_flash($semesterId, $kelasId, 'success', 'Siswa berhasil dikeluarkan dari kelas.');
        } catch (PDOException $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('ATTENDARA remove placement failed: ' . $exception->getMessage());
            penempatan_redirect_with_flash($semesterId, $kelasId, 'error', 'Siswa gagal dikeluarkan dari kelas.');
        }
    }
}

// ---------------------------------------------------------------
// Load reference data for the filter and the forms.
// ---------------------------------------------------------------
$semesterList = $pdo->query(
    "SELECT id, kode, nama, status FROM semester
     ORDER BY (status = 'aktif') DESC, tanggal_mulai DESC, id DESC"
)->fetchAll();

$kelasList = $pdo->query(
    'SELECT id, kode, nama, tingkat FROM kelas WHERE is_active = TRUE ORDER BY tingkat IS NULL, tingkat, nama'
)->fetchAll();

$semesterIds = array_map(static fn (array $row): int => (int) $row['id'], $semesterList);
$kelasIds = array_map(static fn (array $row): int => (int) $row['id'], $kelasList);

// Fall back to the active semester / first class when the request has no valid context.
if (!in_array($semesterId, $semesterIds, true)) {
    $semesterId = 0;
    foreach ($semesterList as $semester) {
        if ($semesterId === 0 || $semester['status'] === 'aktif') {
            $semesterId = (int) $semester['id'];
        }
        if ($semester['status'] === 'aktif') {
            break;
        }
    }
}
if (!in_array($kelasId, $kelasIds, true)) {
    $kelasId = $kelasIds === [] ? 0 : (int) $kelasIds[0];
}

$selectedSemester = null;
foreach ($semesterList as $semester) {
    if ((int) $semester['id'] === $semesterId) {
        $selectedSemester = $semester;
        break;
    }
}

$selectedKelas = null;
foreach ($kelasList as $kelas) {
    if ((int) $kelas['id'] === $kelasId) {
        $selectedKelas = $kelas;
        break;
    }
}

// Students already placed in the selected class + semester.
$placedStudents = [];
if ($semesterId > 0 && $kelasId > 0) {
    $statement = $pdo->prepare(
        'SELECT ak.id AS placement_id, s.id AS siswa_id, s.nomor_induk, s.nama
         FROM anggota_kelas ak
         INNER JOIN siswa s ON s.id = ak.siswa_id
         WHERE ak.semester_id = :semester_id AND ak.kelas_id = :kelas_id
         ORDER BY s.nama, s.nomor_induk'
    );
    $statement->execute(['semester_id' => $semesterId, 'kelas_id' => $kelasId]);
    $placedStudents = $statement->fetchAll();
}

// Active students that do not yet have a placement in the selected semester.
$candidateStudents = [];
if ($semesterId > 0) {
    $statement = $pdo->prepare(
        'SELECT s.id, s.nomor_induk, s.nama
         FROM siswa s
         WHERE s.is_active = TRUE
           AND NOT EXISTS (
               SELECT 1 FROM anggota_kelas ak
               WHERE ak.siswa_id = s.id AND ak.semester_id = :semester_id
           )
         ORDER BY s.nama, s.nomor_induk'
    );
    $statement->execute(['semester_id' => $semesterId]);
    $candidateStudents = $statement->fetchAll();
}

// Placement recap per class for the selected semester (keeps history visible).
$semesterRecap = [];
if ($semesterId > 0) {
    $statement = $pdo->prepare(
        'SELECT k.id AS kelas_id, k.nama,
                COUNT(ak.id) AS jumlah,
                COALESCE(SUM(ak.kelas_id = :current_kelas), 0) AS jumlah_kelas_ini
         FROM kelas k
         LEFT JOIN anggota_kelas ak ON ak.kelas_id = k.id AND ak.semester_id = :semester_id
         WHERE k.is_active = TRUE
         GROUP BY k.id, k.nama
         ORDER BY k.tingkat IS NULL, k.tingkat, k.nama'
    );
    $statement->execute(['semester_id' => $semesterId, 'current_kelas' => $kelasId]);
    $semesterRecap = $statement->fetchAll();
}

admin_page_start('Penempatan Kelas', 'penempatan-kelas.php');
?>
<section class="space-y-4">
    <div>
        <h2 class="text-lg font-semibold text-[#20392a]">Penempatan Siswa</h2>
        <p class="mt-1 text-sm text-slate-600">
            Penempatan disimpan per semester. Riwayat semester lama tidak ditimpa.
        </p>
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

    <form method="get" action="penempatan-kelas.php" class="grid gap-4 rounded-2xl border border-[#e2e9e2] bg-white p-5 sm:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_auto] sm:p-6">
        <div>
            <label for="filter-semester" class="mb-1.5 block text-sm font-medium text-slate-700">Semester</label>
            <select id="filter-semester" name="semester_id" class="min-h-10 w-full rounded-lg border border-[#d8e2d8] bg-white px-3 text-sm">
                <?php if ($semesterList === []): ?>
                    <option value="0">Belum ada semester</option>
                <?php else: ?>
                    <?php foreach ($semesterList as $semester): ?>
                        <option value="<?= h((string) $semester['id']) ?>" <?= (int) $semester['id'] === $semesterId ? 'selected' : '' ?>>
                            <?= h((string) $semester['nama']) ?><?= $semester['status'] === 'aktif' ? ' (aktif)' : '' ?>
                        </option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
        </div>
        <div>
            <label for="filter-kelas" class="mb-1.5 block text-sm font-medium text-slate-700">Kelas</label>
            <select id="filter-kelas" name="kelas_id" class="min-h-10 w-full rounded-lg border border-[#d8e2d8] bg-white px-3 text-sm">
                <?php if ($kelasList === []): ?>
                    <option value="0">Belum ada kelas</option>
                <?php else: ?>
                    <?php foreach ($kelasList as $kelas): ?>
                        <option value="<?= h((string) $kelas['id']) ?>" <?= (int) $kelas['id'] === $kelasId ? 'selected' : '' ?>>
                            <?= h((string) $kelas['nama']) ?>
                        </option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
        </div>
        <div class="flex items-end">
            <button type="submit" class="inline-flex min-h-10 items-center justify-center rounded-lg bg-[#1e5638] px-4 py-2 text-sm font-semibold text-white hover:bg-[#17452d] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#376c4d] focus-visible:ring-offset-2">
                Tampilkan
            </button>
        </div>
    </form>
</section>

<?php if ($selectedSemester === null || $selectedKelas === null): ?>
    <section class="rounded-2xl border border-[#e2e9e2] bg-white p-5 text-sm text-slate-600 sm:p-6">
        Tambahkan semester dan kelas terlebih dahulu sebelum menempatkan siswa.
    </section>
<?php else: ?>
    <section class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)]">
        <div class="space-y-4 rounded-2xl border border-[#e2e9e2] bg-white p-5 sm:p-6">
            <div>
                <h2 class="text-base font-semibold text-[#20392a]">Tempatkan Siswa</h2>
                <p class="mt-1 text-sm text-slate-600">
                    <?= h((string) $selectedKelas['nama']) ?> · <?= h((string) $selectedSemester['nama']) ?>
                </p>
            </div>

            <?php if ($candidateStudents === []): ?>
                <p class="rounded-xl bg-[#f3f6f1] px-4 py-3 text-sm text-[#526257]">
                    Semua siswa aktif sudah memiliki penempatan pada semester ini.
                </p>
            <?php else: ?>
                <form method="post" action="penempatan-kelas.php" class="space-y-3">
                    <input type="hidden" name="_csrf_token" value="<?= h(csrfToken()) ?>">
                    <input type="hidden" name="action" value="place">
                    <input type="hidden" name="semester_id" value="<?= h((string) $semesterId) ?>">
                    <input type="hidden" name="kelas_id" value="<?= h((string) $kelasId) ?>">

                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <label for="cari-siswa" class="text-sm font-medium text-slate-700">Pilih siswa</label>
                        <label class="inline-flex items-center gap-2 text-sm text-slate-600">
                            <input id="pilih-semua" type="checkbox" class="h-4 w-4 rounded border-[#bac9ba] text-[#1e5638] focus:ring-[#376c4d]"> Pilih semua
                        </label>
                    </div>

                    <input id="cari-siswa" type="search" placeholder="Cari nama atau nomor induk" class="min-h-10 w-full rounded-lg border border-[#d8e2d8] bg-white px-3 text-sm focus:border-[#376c4d] focus:outline-none focus:ring-2 focus:ring-[#e6efe6]">

                    <div id="daftar-siswa" class="max-h-80 space-y-1 overflow-y-auto rounded-xl border border-[#e2e9e2] p-2">
                        <?php foreach ($candidateStudents as $student): ?>
                            <?php $searchText = mb_strtolower($student['nomor_induk'] . ' ' . $student['nama']); ?>
                            <label class="student-row flex cursor-pointer items-center gap-3 rounded-lg px-3 py-2.5 hover:bg-[#f3f6f1]" data-search="<?= h($searchText) ?>">
                                <input type="checkbox" name="siswa_ids[]" value="<?= h((string) $student['id']) ?>" class="h-4 w-4 rounded border-[#bac9ba] text-[#1e5638] focus:ring-[#376c4d]">
                                <span class="min-w-0">
                                    <span class="block text-sm font-medium text-slate-700"><?= h((string) $student['nama']) ?></span>
                                    <span class="block text-xs text-slate-500"><?= h((string) $student['nomor_induk']) ?></span>
                                </span>
                            </label>
                        <?php endforeach; ?>
                        <p id="siswa-kosong" class="hidden px-3 py-8 text-center text-sm text-slate-500">Tidak ada siswa yang cocok.</p>
                    </div>

                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <span class="text-sm text-slate-500"><?= h((string) count($candidateStudents)) ?> siswa tersedia</span>
                        <button type="submit" class="inline-flex min-h-10 items-center justify-center rounded-lg bg-[#1e5638] px-4 py-2 text-sm font-semibold text-white hover:bg-[#17452d] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#376c4d] focus-visible:ring-offset-2">
                            Tempatkan Siswa
                        </button>
                    </div>
                </form>

                <script>
                (() => {
                    const search = document.getElementById('cari-siswa');
                    const rows = Array.from(document.querySelectorAll('.student-row'));
                    const empty = document.getElementById('siswa-kosong');
                    const selectAll = document.getElementById('pilih-semua');

                    const filter = () => {
                        const query = search.value.trim().toLocaleLowerCase('id');
                        let visible = 0;
                        rows.forEach(row => {
                            const match = row.dataset.search.includes(query);
                            row.classList.toggle('hidden', !match);
                            if (match) visible += 1;
                        });
                        empty.classList.toggle('hidden', visible !== 0);
                        selectAll.checked = false;
                    };

                    search.addEventListener('input', filter);
                    selectAll.addEventListener('change', () => {
                        rows.forEach(row => {
                            if (row.classList.contains('hidden')) return;
                            row.querySelector('input[type="checkbox"]').checked = selectAll.checked;
                        });
                    });
                })();
                </script>
            <?php endif; ?>
        </div>

        <div class="space-y-4 rounded-2xl border border-[#e2e9e2] bg-white p-5 sm:p-6">
            <div>
                <h2 class="text-base font-semibold text-[#20392a]">Rekap Semester</h2>
                <p class="mt-1 text-sm text-slate-600"><?= h((string) $selectedSemester['nama']) ?></p>
            </div>
            <div class="overflow-x-auto rounded-xl border border-[#e2e9e2]">
                <table class="min-w-full divide-y divide-[#edf1ec] text-left text-sm">
                    <thead class="bg-[#f3f6f1] text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th scope="col" class="px-4 py-3 font-semibold">Kelas</th>
                            <th scope="col" class="px-4 py-3 font-semibold">Jumlah Siswa</th>
                            <th scope="col" class="px-4 py-3 font-semibold">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#edf1ec] text-slate-700">
                        <?php foreach ($semesterRecap as $recap): ?>
                            <?php $recapId = (int) $recap['kelas_id']; ?>
                            <tr class="<?= $recapId === $kelasId ? 'bg-[#f8faf7]' : '' ?>">
                                <td class="px-4 py-3"><?= h((string) $recap['nama']) ?></td>
                                <td class="px-4 py-3"><?= h((string) (int) $recap['jumlah']) ?></td>
                                <td class="px-4 py-3">
                                    <?php if ($recapId === $kelasId): ?>
                                        <span class="text-xs font-semibold text-green-700">Dipilih</span>
                                    <?php else: ?>
                                        <a href="penempatan-kelas.php?semester_id=<?= h((string) $semesterId) ?>&amp;kelas_id=<?= h((string) $recapId) ?>" class="rounded-md px-2 py-1 text-xs font-semibold text-[#28583b] hover:bg-[#edf4ec]">Lihat</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <section class="space-y-4">
        <div>
            <h2 class="text-lg font-semibold text-[#20392a]">Siswa pada Kelas</h2>
            <p class="mt-1 text-sm text-slate-600">
                <?= h((string) $selectedKelas['nama']) ?> · <?= h((string) $selectedSemester['nama']) ?> · <?= h((string) count($placedStudents)) ?> siswa
            </p>
        </div>

        <div class="overflow-x-auto rounded-2xl border border-[#e2e9e2] bg-white">
            <table class="min-w-full divide-y divide-[#edf1ec] text-left text-sm">
                <thead class="bg-[#f3f6f1] text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th scope="col" class="whitespace-nowrap px-4 py-3.5 font-semibold sm:px-5">No</th>
                        <th scope="col" class="whitespace-nowrap px-4 py-3.5 font-semibold sm:px-5">Nomor Induk</th>
                        <th scope="col" class="whitespace-nowrap px-4 py-3.5 font-semibold sm:px-5">Nama Siswa</th>
                        <th scope="col" class="whitespace-nowrap px-4 py-3.5 font-semibold sm:px-5">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#edf1ec] text-slate-700">
                    <?php if ($placedStudents === []): ?>
                        <tr><td colspan="4" class="px-4 py-9 text-center text-slate-500">Belum ada siswa di kelas ini.</td></tr>
                    <?php else: ?>
                        <?php foreach ($placedStudents as $index => $placement): ?>
                            <tr class="hover:bg-[#f8faf7]">
                                <td class="px-4 py-3.5 sm:px-5"><?= h((string) ($index + 1)) ?></td>
                                <td class="px-4 py-3.5 sm:px-5"><?= h((string) $placement['nomor_induk']) ?></td>
                                <td class="px-4 py-3.5 sm:px-5"><?= h((string) $placement['nama']) ?></td>
                                <td class="px-4 py-3.5 sm:px-5">
                                    <form method="post" action="penempatan-kelas.php" class="inline" onsubmit="return confirm('Keluarkan siswa ini dari kelas?');">
                                        <input type="hidden" name="_csrf_token" value="<?= h(csrfToken()) ?>">
                                        <input type="hidden" name="action" value="remove">
                                        <input type="hidden" name="semester_id" value="<?= h((string) $semesterId) ?>">
                                        <input type="hidden" name="kelas_id" value="<?= h((string) $kelasId) ?>">
                                        <input type="hidden" name="placement_id" value="<?= h((string) $placement['placement_id']) ?>">
                                        <button type="submit" class="rounded-md px-2 py-1 text-xs font-semibold text-red-700 hover:bg-red-50">Keluarkan</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
<?php endif; ?>
<?php admin_page_end(); ?>
