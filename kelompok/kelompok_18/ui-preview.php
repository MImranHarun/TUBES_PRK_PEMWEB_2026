<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/ui/layout.php';

$navigation = [
    ['label' => 'Ringkasan', 'href' => 'ui-preview.php', 'icon' => 'home'],
    ['label' => 'Data contoh', 'href' => '#contoh-tabel', 'icon' => 'users'],
    ['label' => 'Jadwal contoh', 'href' => '#contoh-kontrol', 'icon' => 'calendar'],
    ['label' => 'Laporan contoh', 'href' => '#contoh-kontrol', 'icon' => 'chart'],
];

attendara_ui_page_start('Pratinjau komponen', $navigation, 'ui-preview.php');
?>
<section aria-labelledby="intro-title" class="flex flex-wrap items-end justify-between gap-3">
    <div>
        <p class="text-xs font-semibold uppercase tracking-[0.12em] text-[#64806a]">UI kit ATTENDARA</p>
        <h2 id="intro-title" class="mt-1 text-lg font-semibold tracking-tight text-[#20392a]">Fondasi tampilan</h2>
        <p class="mt-1 max-w-2xl text-sm leading-6 text-slate-600">Pratinjau komponen dasar dengan gaya administrasi sekolah yang modern dan ramah.</p>
    </div>
    <span class="rounded-full border border-[#dce7dc] bg-[#edf4ec] px-3 py-1.5 text-xs font-medium text-[#31583c]">Data contoh · tanpa database</span>
</section>

<section aria-label="Contoh kartu statistik" class="grid gap-4 sm:grid-cols-2 2xl:grid-cols-4">
    <?php
    attendara_ui_stat_card('Total anggota', '248', 'Ringkasan angka utama', 'users');
    attendara_ui_stat_card('Kelas aktif', '12', 'Informasi pendukung', 'school');
    attendara_ui_stat_card('Jadwal hari ini', '08', 'Contoh status harian', 'calendar');
    attendara_ui_stat_card('Kehadiran', '96%', 'Contoh indikator ringkas', 'chart');
    ?>
</section>

<section class="grid items-start gap-6 2xl:grid-cols-[minmax(0,1.65fr)_minmax(19rem,0.8fr)]">
    <div id="contoh-tabel" class="min-w-0 space-y-3">
        <div class="flex items-end justify-between gap-3">
            <div>
                <h2 class="text-base font-semibold text-[#20392a]">Tabel sederhana</h2>
                <p class="mt-1 text-sm text-slate-600">Dapat digulir horizontal pada layar kecil.</p>
            </div>
            <span class="hidden text-xs text-slate-500 sm:inline">Contoh data</span>
        </div>
        <?php
        attendara_ui_table(
            ['Nama contoh', 'Kategori', 'Status'],
            [
                ['Grup Melati', 'Kelas', 'Aktif'],
                ['Jadwal pagi', 'Jadwal', 'Terjadwal'],
                ['Semester berjalan', 'Periode', 'Berlangsung'],
            ]
        );
        ?>
    </div>

    <div id="contoh-kontrol" class="space-y-4 rounded-2xl border border-[#e2e9e2] bg-white p-5 sm:p-6">
        <div>
            <h2 class="text-base font-semibold text-[#20392a]">Kontrol dasar</h2>
            <p class="mt-1 text-sm leading-5 text-slate-600">Elemen visual saja, tanpa pengiriman atau penyimpanan data.</p>
        </div>
        <?php attendara_ui_input('Nama contoh', 'nama-contoh', '', 'text', 'Masukkan teks'); ?>
        <div class="flex flex-wrap gap-2.5">
            <?php attendara_ui_button('Tombol utama'); ?>
            <?php attendara_ui_button('Sekunder', '#', 'secondary'); ?>
        </div>
    </div>
</section>
<?php attendara_ui_page_end(); ?>
