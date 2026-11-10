<?php

declare(strict_types=1);
require_once __DIR__ . '/_init.php';

$sessionId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!is_int($sessionId) || $sessionId < 1) {
    http_response_code(404);
    exit('Sesi arsip tidak ditemukan.');
}

try {
    $session = guruArchiveDetail($sessionId);
    if ($session === null) {
        http_response_code(404);
        exit('Sesi arsip tidak ditemukan.');
    }
} catch (Throwable $exception) {
    guruReportBackendError($exception);
}

$dateLabel = date('d M Y', strtotime($session['tanggal']));
if ($session['waktu_mulai'] !== null && $session['waktu_selesai'] !== null) {
    $timeLabel = date('H:i', strtotime($session['waktu_mulai'])) . '–' . date('H:i', strtotime($session['waktu_selesai']));
} elseif ($session['scheduled_start'] !== null && $session['scheduled_end'] !== null) {
    $timeLabel = substr((string) $session['scheduled_start'], 0, 5) . '–' . substr((string) $session['scheduled_end'], 0, 5);
} else {
    $timeLabel = '—';
}
$rows = array_map(static fn (array $student): array => [
    $student['number'],
    $student['name'],
    ucfirst((string) $student['attendance_status']),
    number_format((float) $student['points'], 2, '.', ','),
], $session['students']);

guru_page_start('Detail Arsip Pembelajaran', 'arsip-pembelajaran.php');
?>
<section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <?php
    attendara_ui_stat_card('Kelas', $session['class_name'], $session['semester'], 'school');
    attendara_ui_stat_card('Tanggal', $dateLabel, 'Tanggal sesi', 'calendar');
    attendara_ui_stat_card('Waktu', $timeLabel, 'Waktu sesi atau jadwal', 'calendar');
    attendara_ui_stat_card('Status', ucfirst((string) $session['status']), 'Sesi pembelajaran', 'chart');
    ?>
</section>
<section class="space-y-4">
    <div><h2 class="text-lg font-semibold text-[#20392a]">Kehadiran dan Poin</h2><p class="mt-1 text-sm text-slate-600">Catatan per siswa dalam sesi.</p></div>
    <?php guru_render_table(['Nomor Induk', 'Nama Siswa', 'Status Kehadiran', 'Poin Keaktifan'], $rows, 'Belum ada catatan siswa.'); ?>
</section>
<section class="space-y-4">
    <h2 class="text-lg font-semibold text-[#20392a]">Kelompok</h2>
    <div class="rounded-2xl border border-[#e2e9e2] bg-white px-5 py-8 text-center text-sm text-slate-500">Skema database belum menghubungkan kelompok dengan sesi pembelajaran.</div>
</section>
<a href="arsip-pembelajaran.php" class="inline-flex min-h-10 items-center justify-center rounded-lg border border-[#d8e2d8] bg-white px-4 py-2 text-sm font-semibold text-[#284b35] hover:bg-[#f2f6f2]">Kembali ke Arsip</a>
<?php guru_page_end(); ?>
