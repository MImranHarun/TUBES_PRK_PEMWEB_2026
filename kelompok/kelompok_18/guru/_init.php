<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';
startAppSession();
requireRole('guru');
require_once __DIR__ . '/../includes/ui/layout.php';
require_once __DIR__ . '/_helpers.php';

$guruNavigation = [
    ['label' => 'Dashboard', 'href' => 'dashboard.php', 'icon' => 'home'],
    ['label' => 'Kelas', 'href' => 'kelas.php', 'icon' => 'school'],
    ['label' => 'Arsip Pembelajaran', 'href' => 'arsip-pembelajaran.php', 'icon' => 'file'],
    ['label' => 'Logout', 'href' => '../logout.php', 'icon' => 'file', 'type' => 'logout'],
];

// Contoh statis untuk tampilan Guru, selaras dengan relasi ERD.
$guruUiData = [
    'teacher' => ['name' => 'Budi Santoso', 'number' => 'G-2026-001'],
    'classes' => [
        ['id' => 1, 'assignment_id' => 6, 'schedule_id' => 6, 'code' => 'X-A', 'name' => 'Kelas X-A', 'level' => 10, 'semester_id' => 2, 'semester' => '2026/2027 Ganjil'],
    ],
    // Label hari ini contoh UI karena konversi kolom hari belum ditetapkan di skema.
    'schedules' => [
        ['id' => 6, 'assignment_id' => 6, 'class_id' => 1, 'class_name' => 'Kelas X-A', 'semester' => '2026/2027 Ganjil', 'day_label' => 'Senin', 'day_number' => 1, 'start' => '07:00', 'end' => '08:30', 'valid_from' => '13 Jul 2026', 'valid_until' => '19 Des 2026'],
    ],
    'students' => [],
    'archives' => [
        ['id' => 6, 'date' => '02 Nov 2026', 'class_name' => 'Kelas X-A', 'semester' => '2026/2027 Ganjil', 'time' => '07:00–08:30', 'status' => 'selesai', 'attendance_count' => 20],
    ],
];

for ($studentId = 1; $studentId <= 20; $studentId++) {
    $sequence = str_pad((string) $studentId, 3, '0', STR_PAD_LEFT);
    $guruUiData['students'][] = [
        'id' => $studentId,
        'number' => 'ATT-2026-' . $sequence,
        'name' => 'Siswa ' . str_pad((string) $studentId, 2, '0', STR_PAD_LEFT),
    ];
}

/** Open the compact shared shell for Guru pages. */
function guru_page_start(string $title, string $activeHref): void
{
    global $guruNavigation;
    attendara_ui_page_start($title, $guruNavigation, $activeHref, true);
}

function guru_page_end(): void
{
    attendara_ui_page_end();
}

/** Render a visual-only action button. */
function guru_ui_button(string $label, string $variant = 'primary'): void
{
    $classes = $variant === 'secondary'
        ? 'border border-[#d8e2d8] bg-white text-[#284b35] hover:bg-[#f2f6f2]'
        : 'bg-[#1e5638] text-white hover:bg-[#17452d]';
    ?>
    <button type="button" class="inline-flex min-h-10 items-center justify-center rounded-lg px-4 py-2 text-sm font-semibold focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#376c4d] focus-visible:ring-offset-2 <?= $classes ?>">
        <?= attendara_ui_escape($label) ?>
    </button>
    <?php
}

/** Render a responsive table from escaped text values. */
function guru_render_table(array $headers, array $rows, string $emptyMessage = 'Belum ada data.'): void
{
    ?>
    <div class="overflow-x-auto rounded-2xl border border-[#e2e9e2] bg-white">
        <table class="min-w-full divide-y divide-[#edf1ec] text-left text-sm">
            <thead class="bg-[#f3f6f1] text-xs uppercase tracking-wide text-slate-500"><tr>
                <?php foreach ($headers as $header): ?><th scope="col" class="whitespace-nowrap px-4 py-3.5 font-semibold sm:px-5"><?= attendara_ui_escape((string) $header) ?></th><?php endforeach; ?>
            </tr></thead>
            <tbody class="divide-y divide-[#edf1ec] text-slate-700">
                <?php if ($rows === []): ?>
                    <tr><td colspan="<?= max(count($headers), 1) ?>" class="px-4 py-8 text-center text-slate-500"><?= attendara_ui_escape($emptyMessage) ?></td></tr>
                <?php else: foreach ($rows as $row): ?>
                    <tr class="hover:bg-[#f8faf7]"><?php foreach ($row as $cell): ?><td class="whitespace-nowrap px-4 py-3.5 sm:px-5"><?= attendara_ui_escape((string) $cell) ?></td><?php endforeach; ?></tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
    <?php
}
