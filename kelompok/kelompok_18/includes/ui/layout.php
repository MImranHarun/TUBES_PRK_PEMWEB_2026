<?php

declare(strict_types=1);

require_once __DIR__ . '/components.php';

/** Open the shared responsive application shell. */
function attendara_ui_page_start(string $title, array $navigation, string $activeHref = '', bool $compactHeader = false): void
{
    ?>
    <!doctype html>
    <html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?= attendara_ui_escape($title) ?> | ATTENDARA</title>
        <script src="https://cdn.tailwindcss.com"></script>
    </head>
    <body class="min-h-screen bg-[#f6f8f5] text-slate-900 antialiased">
        <div class="min-h-screen md:flex">
            <aside class="border-b border-[#dfe7dd] bg-[#edf2eb] md:min-h-screen md:w-64 md:shrink-0 md:border-b-0 md:border-r">
                <div class="flex items-center gap-3 px-5 py-4 md:px-6 md:py-6">
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#1e5638] text-white">
                        <?php attendara_ui_icon('school', 'h-5 w-5'); ?>
                    </span>
                    <div>
                        <a href="#" class="text-base font-bold tracking-tight text-[#173e2b]">ATTENDARA</a>
                        <p class="mt-0.5 text-xs text-slate-500">Administrasi sekolah</p>
                    </div>
                </div>
                <nav aria-label="Navigasi utama" class="flex gap-1.5 overflow-x-auto px-3 pb-3 md:block md:space-y-1.5 md:px-3">
                    <?php foreach ($navigation as $item):
                        $label = (string) ($item['label'] ?? '');
                        $href = (string) ($item['href'] ?? '#');
                        $icon = (string) ($item['icon'] ?? 'file');
                        $isLogout = ($item['type'] ?? '') === 'logout';
                        $isActive = $activeHref !== '' && $href === $activeHref;
                        $classes = $isActive
                            ? 'border-l-[3px] border-[#bfd5c1] bg-[#1e5638] pl-[9px] text-white shadow-sm [&>span]:text-white'
                            : ($isLogout
                                ? 'border-l-[3px] border-transparent bg-red-50 text-red-700 hover:bg-red-100'
                                : 'border-l-[3px] border-transparent text-[#526257] hover:bg-white/70 hover:text-[#173e2b]');
                    ?>
                        <?php if ($isLogout): ?>
                            <form method="post" action="<?= attendara_ui_escape($href) ?>" class="shrink-0 md:w-full">
                                <input type="hidden" name="_csrf_token" value="<?= attendara_ui_escape(csrfToken()) ?>">
                                <button type="submit" class="flex min-h-10 w-full items-center gap-3 rounded-lg border-l-[3px] px-3 py-2 text-left text-sm font-semibold transition-colors <?= $classes ?>">
                                    <span><?php attendara_ui_icon($icon, 'h-[18px] w-[18px]'); ?></span>
                                    <span><?= attendara_ui_escape($label) ?></span>
                                </button>
                            </form>
                        <?php else: ?>
                            <a href="<?= attendara_ui_escape($href) ?>"<?= $isActive ? ' aria-current="page"' : '' ?> class="flex min-h-10 shrink-0 items-center gap-3 rounded-lg border-l-[3px] px-3 py-2 text-sm font-medium transition-colors md:w-full <?= $classes ?>">
                                <span class="text-[#718276]"><?php attendara_ui_icon($icon, 'h-[18px] w-[18px]'); ?></span>
                                <span><?= attendara_ui_escape($label) ?></span>
                            </a>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </nav>
            </aside>
            <div class="min-w-0 flex-1">
                <header class="border-b border-[#e2e9e2] bg-white px-5 py-4 sm:px-6 lg:px-8">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <?php if (!$compactHeader): ?><p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-[#64806a]">Ruang kerja</p><?php endif; ?>
                            <h1 class="<?= $compactHeader ? '' : 'mt-1 ' ?>text-xl font-semibold tracking-tight text-[#20392a] sm:text-2xl"><?= attendara_ui_escape($title) ?></h1>
                        </div>
                        <?php if (!$compactHeader): ?>
                            <div class="hidden items-center gap-2 rounded-full bg-[#f1f5ef] px-3 py-1.5 text-xs font-medium text-[#526257] sm:flex">
                                <span class="h-2 w-2 rounded-full bg-[#6d9273]"></span>
                                Area aplikasi
                            </div>
                        <?php endif; ?>
                    </div>
                </header>
                <main class="space-y-7 px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
    <?php
}

/** Close the shared application shell. */
function attendara_ui_page_end(): void
{
    ?>
                </main>
            </div>
        </div>
    </body>
    </html>
    <?php
}
