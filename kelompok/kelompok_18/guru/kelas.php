<?php

declare(strict_types=1);
require_once __DIR__ . '/_init.php';

try {
    $profile = guruProfile();
    $activeSemester = guruActiveSemester();
    $classes = $profile !== null && $activeSemester !== null
        ? guruActiveAssignments((int) $profile['id'], (int) $activeSemester['id'])
        : [];
} catch (Throwable $exception) {
    guruReportBackendError($exception);
}

guru_page_start('Kelas', 'kelas.php');
?>
<section class="space-y-4">
    <div><h2 class="text-lg font-semibold text-[#20392a]">Kelas yang Ditugaskan</h2></div>
    <div class="grid gap-4 xl:grid-cols-2">
        <?php foreach ($classes as $class): ?>
            <article class="rounded-2xl border border-[#e2e9e2] bg-white p-5 shadow-[0_2px_8px_rgba(30,55,38,0.04)] sm:p-6">
                <div class="flex items-start justify-between gap-4">
                    <div><p class="text-xs font-semibold uppercase tracking-wide text-[#64806a]">Kode <?= attendara_ui_escape($class['code']) ?></p><h3 class="mt-1 text-lg font-semibold text-[#20392a]"><?= attendara_ui_escape($class['name']) ?></h3><p class="mt-1 text-sm text-slate-500">Tingkat <?= attendara_ui_escape((string) $class['level']) ?> · <?= attendara_ui_escape($class['semester']) ?></p></div>
                    <span class="rounded-xl bg-[#e9f0e8] p-2 text-[#28583b]"><?php attendara_ui_icon('school', 'h-5 w-5'); ?></span>
                </div>
                <div class="mt-5 flex flex-wrap gap-2">
                    <a href="kelas-detail.php?id=<?= (int) $class['id'] ?>" class="inline-flex min-h-10 items-center justify-center rounded-lg bg-[#1e5638] px-4 py-2 text-sm font-semibold text-white hover:bg-[#17452d]">Lihat Kelas</a>
                    <a href="pengaturan-kelas.php?id=<?= (int) $class['id'] ?>" class="inline-flex min-h-10 items-center justify-center rounded-lg border border-[#d8e2d8] bg-white px-4 py-2 text-sm font-semibold text-[#284b35] hover:bg-[#f2f6f2]">Pengaturan Kelas</a>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</section>
<?php guru_page_end(); ?>
