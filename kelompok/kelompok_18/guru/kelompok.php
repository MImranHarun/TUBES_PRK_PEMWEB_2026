<?php

declare(strict_types=1);
require_once __DIR__ . '/_init.php';

$rawSessionId = $_GET['session_id'] ?? '';
$rawClassId = $_GET['class_id'] ?? '';
if (
    (!is_string($rawSessionId) && !is_int($rawSessionId))
    || (!is_string($rawClassId) && !is_int($rawClassId))
) {
    http_response_code(422);
    exit('Konteks kelas tidak valid.');
}
$sessionId = $rawSessionId === '' ? null : filter_var($rawSessionId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$classId = $rawClassId === '' ? null : filter_var($rawClassId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if (($rawSessionId !== '' && $sessionId === false) || ($rawClassId !== '' && $classId === false)) {
    http_response_code(422);
    exit('Konteks kelas tidak valid.');
}

try {
    $profile = guruProfile();
    $activeSemester = guruActiveSemester();
    $classes = $profile !== null && $activeSemester !== null
        ? guruActiveAssignments((int) $profile['id'], (int) $activeSemester['id'])
        : [];
    $session = $sessionId === null ? null : guruOwnedSession((int) $sessionId);
    if ($sessionId !== null && ($session === null || (int) $session['semester_id'] !== (int) ($activeSemester['id'] ?? 0))) {
        http_response_code(404);
        exit('Sesi tidak ditemukan.');
    }

    if ($session !== null) {
        if ($classId !== null && $classId !== (int) $session['class_id']) {
            http_response_code(422);
            exit('Kelas tidak sesuai dengan sesi.');
        }
        $classId = (int) $session['class_id'];
    } elseif ($classId === null && count($classes) === 1) {
        $classId = (int) $classes[0]['id'];
    }

    $selectedClass = null;
    foreach ($classes as $classOption) {
        if ($classId !== null && (int) $classOption['id'] === $classId) {
            $selectedClass = $classOption;
            break;
        }
    }
    if ($classId !== null && $selectedClass === null) {
        http_response_code(404);
        exit('Kelas tidak ditemukan.');
    }
    $groups = $selectedClass === null
        ? []
        : guruGroupsForClass((int) $profile['id'], (int) $selectedClass['id'], (int) $activeSemester['id']);
} catch (Throwable $exception) {
    guruReportBackendError($exception);
}

guru_page_start('Kelompok Sesi', 'kelas.php');
?>
<nav aria-label="Menu sesi" class="flex flex-wrap gap-2">
    <a href="tap-sesi.php<?= $session === null ? '' : '?session_id=' . (int) $session['id'] ?>" class="rounded-lg border border-[#d8e2d8] bg-white px-4 py-2 text-sm font-semibold text-[#526257]">Tap Kehadiran</a>
    <a href="absensi.php<?= $session === null ? '' : '?session_id=' . (int) $session['id'] ?>" class="rounded-lg border border-[#d8e2d8] bg-white px-4 py-2 text-sm font-semibold text-[#526257]">Data Siswa &amp; Poin</a>
    <a href="kelompok.php<?= $session === null ? '' : '?session_id=' . (int) $session['id'] ?>" aria-current="page" class="rounded-lg bg-[#1e5638] px-4 py-2 text-sm font-semibold text-white">Kelompok</a>
</nav>
<section class="flex flex-wrap items-center justify-between gap-3">
    <div><h2 class="text-lg font-semibold text-[#20392a]">Kelompok Pembelajaran</h2><p class="mt-1 text-sm text-slate-600">Kelompok disimpan untuk kelas dan semester aktif.</p></div>
    <?php if ($selectedClass !== null): ?><a href="kelompok-baru.php?class_id=<?= (int) $selectedClass['id'] ?>" class="inline-flex min-h-10 items-center justify-center rounded-lg bg-[#1e5638] px-4 py-2 text-sm font-semibold text-white hover:bg-[#17452d]">Tambah Kelompok</a><?php endif; ?>
</section>
<?php if ($session === null && count($classes) > 1): ?>
    <form method="get" action="kelompok.php" class="max-w-lg rounded-2xl border border-[#e2e9e2] bg-white p-4">
        <label for="group-class" class="mb-1.5 block text-sm font-medium text-slate-700">Pilih Kelas</label>
        <div class="flex flex-wrap gap-2"><select id="group-class" name="class_id" required class="min-h-10 min-w-56 flex-1 rounded-lg border border-[#d8e2d8] bg-white px-3 text-sm"><option value="">Pilih kelas</option><?php foreach ($classes as $classOption): ?><option value="<?= (int) $classOption['id'] ?>"<?= $classId === (int) $classOption['id'] ? ' selected' : '' ?>><?= attendara_ui_escape($classOption['name'] . ' · ' . $classOption['semester']) ?></option><?php endforeach; ?></select><button type="submit" class="min-h-10 rounded-lg bg-[#1e5638] px-4 text-sm font-semibold text-white">Tampilkan</button></div>
    </form>
<?php endif; ?>
<?php if ($selectedClass === null): ?>
    <p class="rounded-2xl border border-[#e2e9e2] bg-white px-5 py-8 text-center text-sm text-slate-500"><?= $classes === [] ? 'Belum ada kelas yang ditugaskan pada semester aktif.' : 'Pilih kelas untuk melihat kelompok.' ?></p>
<?php else: ?>
    <p class="text-sm text-slate-500"><?= attendara_ui_escape($selectedClass['name'] . ' · ' . $selectedClass['semester']) ?></p>
    <section class="space-y-3">
        <?php foreach ($groups as $group): ?>
            <article class="rounded-2xl border border-[#e2e9e2] bg-white p-5">
                <div class="flex flex-wrap items-center justify-between gap-3"><div><h3 class="font-semibold text-[#20392a]"><?= attendara_ui_escape($group['name']) ?></h3><p class="mt-1 text-sm text-slate-500"><?= attendara_ui_escape($selectedClass['name'] . ' · ' . $selectedClass['semester']) ?> · <?= (int) $group['member_count'] ?> anggota</p></div><a href="kelompok-edit.php?id=<?= (int) $group['id'] ?>" class="inline-flex min-h-9 items-center rounded-lg border border-[#d8e2d8] px-3 text-sm font-semibold text-[#284b35] hover:bg-[#f2f6f2]">Edit Kelompok</a></div>
            </article>
        <?php endforeach; ?>
        <?php if ($groups === []): ?><p class="rounded-2xl border border-[#e2e9e2] bg-white px-5 py-8 text-center text-sm text-slate-500">Belum ada kelompok untuk kelas dan semester ini.</p><?php endif; ?>
    </section>
<?php endif; ?>
<?php if ($session !== null): ?>
    <a href="sesi-aktif.php?id=<?= (int) $session['id'] ?>" class="inline-flex min-h-10 items-center justify-center rounded-lg border border-[#d8e2d8] bg-white px-4 py-2 text-sm font-semibold text-[#284b35]">Kembali ke Sesi</a>
<?php elseif ($selectedClass !== null): ?>
    <a href="kelas-detail.php?id=<?= (int) $selectedClass['id'] ?>" class="inline-flex min-h-10 items-center justify-center rounded-lg border border-[#d8e2d8] bg-white px-4 py-2 text-sm font-semibold text-[#284b35]">Kembali ke Kelas</a>
<?php endif; ?>
<?php guru_page_end(); ?>
