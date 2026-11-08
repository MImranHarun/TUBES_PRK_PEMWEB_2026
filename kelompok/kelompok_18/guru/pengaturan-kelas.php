<?php

declare(strict_types=1);
require_once __DIR__ . '/_init.php';
$classId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!is_int($classId) || $classId < 1) {
    http_response_code(404);
    exit('Kelas tidak ditemukan.');
}

try {
    $profile = guruProfile();
    $activeSemester = guruActiveSemester();
    $class = $profile !== null && $activeSemester !== null
        ? guruOwnedActiveClass((int) $profile['id'], (int) $activeSemester['id'], $classId)
        : null;
    if ($class === null) {
        http_response_code(404);
        exit('Kelas tidak ditemukan.');
    }
    $schedules = guruClassSchedules((int) $class['assignment_id']);
    $schedule = $schedules[0] ?? null;
} catch (Throwable $exception) {
    guruReportBackendError($exception);
}

guru_page_start('Pengaturan Kelas', 'kelas.php');
?>
<section class="grid gap-4 lg:grid-cols-2">
    <article class="rounded-2xl border border-[#e2e9e2] bg-white p-5 sm:p-6">
        <h2 class="text-base font-semibold text-[#20392a]">Informasi Kelas</h2>
        <dl class="mt-4 divide-y divide-[#edf1ec] text-sm">
            <div class="flex justify-between gap-4 py-3"><dt class="text-slate-500">Nama</dt><dd class="font-medium text-slate-700"><?= attendara_ui_escape($class['name']) ?></dd></div>
            <div class="flex justify-between gap-4 py-3"><dt class="text-slate-500">Kode</dt><dd class="font-medium text-slate-700"><?= attendara_ui_escape($class['code']) ?></dd></div>
            <div class="flex justify-between gap-4 py-3"><dt class="text-slate-500">Tingkat</dt><dd class="font-medium text-slate-700"><?= attendara_ui_escape((string) $class['level']) ?></dd></div>
            <div class="flex justify-between gap-4 py-3"><dt class="text-slate-500">Semester</dt><dd class="font-medium text-slate-700"><?= attendara_ui_escape($class['semester']) ?></dd></div>
        </dl>
    </article>
    <article class="rounded-2xl border border-[#e2e9e2] bg-white p-5 sm:p-6">
        <h2 class="text-base font-semibold text-[#20392a]">Jadwal Penugasan</h2>
        <?php if ($schedule === null): ?>
            <p class="mt-4 text-sm text-slate-500">Belum ada jadwal aktif untuk kelas ini.</p>
        <?php else: ?>
        <dl class="mt-4 divide-y divide-[#edf1ec] text-sm">
            <div class="flex justify-between gap-4 py-3"><dt class="text-slate-500">Hari</dt><dd class="font-medium text-slate-700"><?= attendara_ui_escape($schedule['day_label']) ?></dd></div>
            <div class="flex justify-between gap-4 py-3"><dt class="text-slate-500">Waktu</dt><dd class="font-medium text-slate-700"><?= attendara_ui_escape($schedule['start'] . '–' . $schedule['end']) ?></dd></div>
            <div class="flex justify-between gap-4 py-3"><dt class="text-slate-500">Masa berlaku</dt><dd class="text-right font-medium text-slate-700"><?= attendara_ui_escape($schedule['valid_from'] . ' – ' . $schedule['valid_until']) ?></dd></div>
            <div class="flex justify-between gap-4 py-3"><dt class="text-slate-500">Penugasan</dt><dd class="font-medium text-slate-700">#<?= attendara_ui_escape((string) $class['assignment_id']) ?></dd></div>
        </dl>
        <?php endif; ?>
    </article>
</section>
<p class="rounded-xl border border-[#dfe7dd] bg-[#f3f6f1] px-4 py-3 text-sm text-[#526257]">Data kelas, penugasan, dan jadwal dikelola melalui administrasi sekolah.</p>
<a href="kelas-detail.php?id=<?= (int) $class['id'] ?>" class="inline-flex min-h-10 items-center justify-center rounded-lg border border-[#d8e2d8] bg-white px-4 py-2 text-sm font-semibold text-[#284b35] hover:bg-[#f2f6f2]">Kembali ke Detail Kelas</a>
<?php guru_page_end(); ?>
