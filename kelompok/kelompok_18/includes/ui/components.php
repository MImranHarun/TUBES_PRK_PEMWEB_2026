<?php

declare(strict_types=1);

/** Escape text and attributes rendered by the reusable UI components. */
function attendara_ui_escape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Render a small inline SVG icon from the shared, allow-listed icon set. */
function attendara_ui_icon(string $name, string $class = 'h-5 w-5'): void
{
    $icons = [
        'home' => '<path d="m3 10 9-7 9 7v10a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1z"/><path d="M3 10h18"/>',
        'users' => '<path d="M16 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="10" cy="7" r="4"/><path d="M20 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>',
        'school' => '<path d="m3 10 9-5 9 5-9 5z"/><path d="M7 12v5c3 2 7 2 10 0v-5M21 10v6"/>',
        'calendar' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 11h18"/>',
        'chart' => '<path d="M4 19V5M4 19h17"/><path d="m7 15 4-4 3 2 5-6"/>',
        'file' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M8 13h8M8 17h8"/>',
        'settings' => '<circle cx="12" cy="12" r="3"/><path d="m19.4 15 .1.1 1.4 1.1-1.4 2.4-1.7-.7a8 8 0 0 1-1.7 1l-.3 1.8h-2.8l-.3-1.8a8 8 0 0 1-1.7-1l-1.7.7-1.4-2.4L7.3 15a8 8 0 0 1 0-2l-1.4-1.1 1.4-2.4 1.7.7a8 8 0 0 1 1.7-1l.3-1.8h2.8l.3 1.8a8 8 0 0 1 1.7 1l1.7-.7 1.4 2.4-1.4 1.1a8 8 0 0 1-.1 2z"/>',
    ];
    $paths = $icons[$name] ?? $icons['file'];
    ?>
    <svg aria-hidden="true" class="<?= attendara_ui_escape($class) ?>" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
        <?= $paths ?>
    </svg>
    <?php
}

/** Render a structured statistics card with an icon, primary value and context. */
function attendara_ui_stat_card(string $label, string $value, ?string $description = null, string $icon = 'chart'): void
{
    ?>
    <section class="flex min-h-36 items-start justify-between gap-4 rounded-2xl border border-[#e2e9e2] bg-white p-5 shadow-[0_2px_8px_rgba(30,55,38,0.04)] sm:p-6">
        <div class="min-w-0">
            <h2 class="text-sm font-medium text-slate-600"><?= attendara_ui_escape($label) ?></h2>
            <p class="mt-3 text-3xl font-semibold leading-none tracking-tight text-[#173e2b]"><?= attendara_ui_escape($value) ?></p>
            <?php if ($description !== null && $description !== ''): ?>
                <p class="mt-3 text-sm leading-5 text-slate-500"><?= attendara_ui_escape($description) ?></p>
            <?php endif; ?>
        </div>
        <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-[#e9f0e8] text-[#28583b]">
            <?php attendara_ui_icon($icon, 'h-5 w-5'); ?>
        </span>
    </section>
    <?php
}

/** Render a responsive table from column labels and rows of plain text values. */
function attendara_ui_table(array $headers, array $rows, string $emptyMessage = 'Belum ada data.'): void
{
    ?>
    <div class="overflow-x-auto rounded-2xl border border-[#e2e9e2] bg-white">
        <table class="min-w-full divide-y divide-[#edf1ec] text-left text-sm">
            <thead class="bg-[#f3f6f1] text-xs uppercase tracking-wide text-slate-500">
                <tr>
                    <?php foreach ($headers as $header): ?>
                        <th scope="col" class="whitespace-nowrap px-4 py-3.5 font-semibold sm:px-5"><?= attendara_ui_escape((string) $header) ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody class="divide-y divide-[#edf1ec] text-slate-700">
                <?php if ($rows === []): ?>
                    <tr><td colspan="<?= max(count($headers), 1) ?>" class="px-4 py-8 text-center text-slate-500"><?= attendara_ui_escape($emptyMessage) ?></td></tr>
                <?php else: ?>
                    <?php foreach ($rows as $row): ?>
                        <tr class="transition-colors hover:bg-[#f8faf7]">
                            <?php foreach ($headers as $index => $_header): ?>
                                <td class="whitespace-nowrap px-4 py-3.5 sm:px-5"><?= attendara_ui_escape((string) ($row[$index] ?? '')) ?></td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
}

/** Render a link with a small set of consistent visual variants. */
function attendara_ui_button(string $label, string $href = '#', string $variant = 'primary'): void
{
    $variants = [
        'primary' => 'bg-[#1e5638] text-white hover:bg-[#17452d] focus-visible:ring-[#376c4d]',
        'secondary' => 'border border-[#d8e2d8] bg-white text-[#284b35] hover:bg-[#f2f6f2] focus-visible:ring-[#376c4d]',
    ];
    $classes = $variants[$variant] ?? $variants['primary'];
    ?>
    <a href="<?= attendara_ui_escape($href) ?>" class="inline-flex min-h-10 items-center justify-center rounded-lg px-4 py-2 text-sm font-semibold focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 <?= $classes ?>">
        <?= attendara_ui_escape($label) ?>
    </a>
    <?php
}

/** Render a labeled text input suitable for a page-owned form. */
function attendara_ui_input(string $label, string $name, string $value = '', string $type = 'text', string $placeholder = ''): void
{
    $allowedTypes = ['text', 'email', 'password', 'number', 'date'];
    if (!in_array($type, $allowedTypes, true)) {
        $type = 'text';
    }
    ?>
    <div>
        <label for="<?= attendara_ui_escape($name) ?>" class="mb-1.5 block text-sm font-medium text-slate-700"><?= attendara_ui_escape($label) ?></label>
        <input id="<?= attendara_ui_escape($name) ?>" name="<?= attendara_ui_escape($name) ?>" type="<?= attendara_ui_escape($type) ?>" value="<?= attendara_ui_escape($value) ?>" placeholder="<?= attendara_ui_escape($placeholder) ?>" class="min-h-10 w-full rounded-lg border border-[#d8e2d8] bg-white px-3 py-2 text-sm text-slate-900 placeholder:text-slate-400 focus:border-[#376c4d] focus:outline-none focus:ring-2 focus:ring-[#e6efe6]">
    </div>
    <?php
}
