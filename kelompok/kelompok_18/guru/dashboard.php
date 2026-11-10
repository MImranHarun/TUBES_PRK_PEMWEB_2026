<?php

declare(strict_types=1);
require_once __DIR__ . '/_init.php';

try {
    $profile = guruProfile();
    $activeSemester = guruActiveSemester();
    $classes = $profile !== null && $activeSemester !== null
        ? guruActiveAssignments((int) $profile['id'], (int) $activeSemester['id'])
        : [];
    $schedules = $profile !== null && $activeSemester !== null
        ? guruActiveSchedules((int) $profile['id'], (int) $activeSemester['id'])
        : [];
} catch (Throwable $exception) {
    guruReportBackendError($exception);
}

guru_page_start('Dashboard Guru', 'dashboard.php');
?>
<section aria-label="Ringkasan Guru" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
    <?php
    attendara_ui_stat_card('Kelas ditugaskan', (string) count($classes), null, 'school');
    attendara_ui_stat_card('Jadwal mengajar', (string) count($schedules), $activeSemester['nama'] ?? 'Tidak ada semester aktif', 'calendar');
    attendara_ui_stat_card('Profil Guru', $profile['nomor_induk'] ?? 'Belum terhubung', $profile['nama'] ?? 'Profil Guru belum tersedia', 'users');
    ?>
</section>

<section class="space-y-4">
    <div>
        <h2 class="text-lg font-semibold text-[#20392a]">Hari Mengajar</h2>
        <p class="mt-1 text-sm text-slate-600">Hari, kelas, dan waktu mengajar.</p>
    </div>
    <div class="space-y-3">
        <?php foreach ($schedules as $schedule): ?>
            <article class="flex flex-col gap-3 rounded-2xl border border-[#e2e9e2] bg-white p-4 sm:flex-row sm:items-center sm:justify-between sm:p-5">
                <div class="flex items-start gap-4">
                    <span class="min-w-24 rounded-xl bg-[#e9f0e8] px-3 py-2 text-center text-sm font-semibold text-[#28583b]"><?= attendara_ui_escape($schedule['day_label']) ?></span>
                    <div>
                        <h3 class="font-semibold text-[#20392a]"><?= attendara_ui_escape($schedule['class_name']) ?></h3>
                        <p class="mt-1 text-sm text-slate-500"><?= attendara_ui_escape($schedule['semester']) ?></p>
                    </div>
                </div>
                <p class="text-sm font-medium text-slate-700 sm:text-right"><?= attendara_ui_escape($schedule['start'] . '–' . $schedule['end']) ?></p>
            </article>
        <?php endforeach; ?>
    </div>
</section>
<?php guru_page_end(); ?>
