<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';
startAppSession();
requireRole('admin');

require_once __DIR__ . '/../includes/ui/layout.php';

$adminUser = currentUser();
$adminNavigation = [
    ['label' => 'Dashboard', 'href' => 'dashboard.php', 'icon' => 'home'],
    ['label' => 'Kelas', 'href' => 'kelas.php', 'icon' => 'school'],
    ['label' => 'Siswa', 'href' => 'siswa.php', 'icon' => 'users'],
    ['label' => 'Guru', 'href' => 'guru.php', 'icon' => 'users'],
    ['label' => 'Semester', 'href' => 'semester.php', 'icon' => 'calendar'],
    ['label' => 'Penempatan Kelas', 'href' => 'penempatan-kelas.php', 'icon' => 'school'],
    ['label' => 'Penugasan Guru', 'href' => 'penugasan-guru.php', 'icon' => 'users'],
    ['label' => 'Jadwal Mengajar', 'href' => 'jadwal-mengajar.php', 'icon' => 'calendar'],
    ['label' => 'Logout', 'href' => '../logout.php', 'icon' => 'file', 'type' => 'logout'],
];

/** Open the shared Admin shell. */
function admin_page_start(string $title, string $activeHref): void
{
    global $adminNavigation;
    attendara_ui_page_start($title, $adminNavigation, $activeHref, true);
}

function admin_page_end(): void
{
    attendara_ui_page_end();
}

/** UI-only button; it does not submit data or perform CRUD. */
function admin_ui_button(string $label, string $variant = 'primary'): void
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

/** Render text rows; action controls are visual only. */
function admin_render_table(array $headers, array $rows, string $emptyMessage = 'Belum ada data.', array $actions = []): void
{
    ?>
    <div class="overflow-x-auto rounded-2xl border border-[#e2e9e2] bg-white">
        <table data-admin-table class="min-w-full divide-y divide-[#edf1ec] text-left text-sm">
            <thead class="bg-[#f3f6f1] text-xs uppercase tracking-wide text-slate-500"><tr>
                <?php foreach ($headers as $header): ?><th scope="col" class="whitespace-nowrap px-4 py-3.5 font-semibold sm:px-5"><?= attendara_ui_escape((string) $header) ?></th><?php endforeach; ?>
                <?php if ($actions !== []): ?><th scope="col" class="px-4 py-3.5 font-semibold sm:px-5">Aksi</th><?php endif; ?>
            </tr></thead>
            <tbody class="divide-y divide-[#edf1ec] text-slate-700">
                <?php if ($rows === []): ?>
                    <tr><td colspan="<?= max(count($headers) + ($actions !== [] ? 1 : 0), 1) ?>" class="px-4 py-9 text-center text-slate-500"><?= attendara_ui_escape($emptyMessage) ?></td></tr>
                <?php else: foreach ($rows as $row): ?>
                    <tr class="hover:bg-[#f8faf7]">
                        <?php foreach ($row as $cell): ?><td class="whitespace-nowrap px-4 py-3.5 sm:px-5"><?= attendara_ui_escape((string) $cell) ?></td><?php endforeach; ?>
                        <?php if ($actions !== []): ?><td class="whitespace-nowrap px-4 py-3.5 sm:px-5"><div class="flex gap-2"><?php foreach ($actions as $action): ?><button type="button" class="rounded-md px-2 py-1 text-xs font-semibold text-[#28583b] hover:bg-[#edf4ec]"><?= attendara_ui_escape($action) ?></button><?php endforeach; ?></div></td><?php endif; ?>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
    <?php
}

/** Neutral display for literal semester status values. */
function admin_status_badge(string $status): void
{
    ?>
    <span class="inline-flex rounded-full bg-[#f1f3f0] px-2.5 py-1 text-xs font-medium text-[#5d685f]"><?= attendara_ui_escape($status) ?></span>
    <?php
}
