<?php

declare(strict_types=1);
require_once __DIR__ . '/_init.php';

$sessionId = filter_input(INPUT_GET, 'session_id', FILTER_VALIDATE_INT);
if (!is_int($sessionId) || $sessionId < 1) {
    http_response_code(404);
    exit('Sesi tidak ditemukan.');
}

try {
    $session = guruOwnedSession($sessionId);
    if ($session === null) {
        http_response_code(404);
        exit('Sesi tidak ditemukan.');
    }
    $students = guruSessionAttendance($sessionId);
} catch (DomainException $exception) {
    http_response_code(404);
    exit('Sesi tidak ditemukan.');
} catch (Throwable $exception) {
    guruReportBackendError($exception);
}

guru_page_start('Data Siswa & Poin', 'kelas.php');
?>
<nav aria-label="Menu sesi" class="flex flex-wrap gap-2">
    <a href="tap-sesi.php?session_id=<?= (int) $sessionId ?>" class="rounded-lg border border-[#d8e2d8] bg-white px-4 py-2 text-sm font-semibold text-[#526257]">Tap Kehadiran</a>
    <a href="absensi.php?session_id=<?= (int) $sessionId ?>" aria-current="page" class="rounded-lg bg-[#1e5638] px-4 py-2 text-sm font-semibold text-white">Data Siswa &amp; Poin</a>
    <a href="kelompok.php?session_id=<?= (int) $sessionId ?>" class="rounded-lg border border-[#d8e2d8] bg-white px-4 py-2 text-sm font-semibold text-[#526257]">Kelompok</a>
</nav>
<section class="space-y-4">
    <div class="flex flex-wrap items-end justify-between gap-3"><div><h2 class="text-lg font-semibold text-[#20392a]">Kehadiran Siswa</h2><p class="mt-1 text-sm text-slate-600"><?= attendara_ui_escape($session['class_name'] . ' · ' . $session['semester']) ?>. Status awal siswa adalah Hadir.</p></div><div class="w-full sm:max-w-xs"><label for="student-filter" class="mb-1.5 block text-sm font-medium text-slate-700">Cari siswa</label><input id="student-filter" type="search" placeholder="Nama atau nomor induk" class="min-h-10 w-full rounded-lg border border-[#d8e2d8] px-3 text-sm focus:border-[#376c4d] focus:outline-none focus:ring-2 focus:ring-[#e6efe6]"></div></div>
    <div class="overflow-x-auto rounded-2xl border border-[#e2e9e2] bg-white">
        <table class="min-w-full divide-y divide-[#edf1ec] text-left text-sm">
            <thead class="bg-[#f3f6f1] text-xs uppercase tracking-wide text-slate-500"><tr><th class="px-4 py-3.5 font-semibold">Nomor Induk</th><th class="px-4 py-3.5 font-semibold">Nama Siswa</th><th class="px-4 py-3.5 font-semibold">Status Kehadiran</th><th class="px-4 py-3.5 font-semibold">Poin</th><th class="px-4 py-3.5 font-semibold">Aksi</th></tr></thead>
            <tbody id="student-attendance-list" class="divide-y divide-[#edf1ec] text-slate-700">
                <?php foreach ($students as $student): ?>
                    <?php $attendanceLabel = ucfirst((string) $student['attendance_status']); ?>
                    <tr data-student-row>
                        <td class="whitespace-nowrap px-4 py-3.5"><?= attendara_ui_escape($student['number']) ?></td>
                        <td class="whitespace-nowrap px-4 py-3.5"><?= attendara_ui_escape($student['name']) ?></td>
                        <td class="whitespace-nowrap px-4 py-3.5"><span class="rounded-full bg-[#e8f1e7] px-2.5 py-1 text-xs font-medium text-[#31583c]"><?= attendara_ui_escape($attendanceLabel) ?></span></td>
                        <td class="whitespace-nowrap px-4 py-3.5 text-slate-500"><?= number_format((float) $student['points'], 2, '.', ',') ?></td>
                        <td class="whitespace-nowrap px-4 py-3.5"><div class="flex gap-2"><a href="absensi-edit.php?session_id=<?= (int) $sessionId ?>&amp;id=<?= (int) $student['id'] ?>" class="rounded-md px-2 py-1 text-xs font-semibold text-[#28583b] hover:bg-[#edf4ec]">Ubah Status</a><a href="poin-tambah.php?session_id=<?= (int) $sessionId ?>&amp;id=<?= (int) $student['id'] ?>" class="rounded-md px-2 py-1 text-xs font-semibold text-[#28583b] hover:bg-[#edf4ec]">Tambah Poin</a></div></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <p id="no-students-found" class="hidden rounded-xl bg-white px-4 py-6 text-center text-sm text-slate-500">Tidak ada siswa yang cocok.</p>
</section>
<div class="flex flex-wrap gap-2"><a href="tap-sesi.php?session_id=<?= (int) $sessionId ?>" class="inline-flex min-h-10 items-center justify-center rounded-lg border border-[#d8e2d8] bg-white px-4 py-2 text-sm font-semibold text-[#284b35]">Kembali ke Tap</a><a href="kelompok.php?session_id=<?= (int) $sessionId ?>" class="inline-flex min-h-10 items-center justify-center rounded-lg border border-[#d8e2d8] bg-white px-4 py-2 text-sm font-semibold text-[#284b35]">Lanjut ke Kelompok</a></div>
<script>
const attendanceRows = [...document.querySelectorAll('[data-student-row]')];
document.getElementById('student-filter').addEventListener('input', event => {
    const query = event.target.value.trim().toLocaleLowerCase('id');
    let shown = 0;
    attendanceRows.forEach(row => { row.hidden = !row.textContent.toLocaleLowerCase('id').includes(query); if (!row.hidden) shown++; });
    document.getElementById('no-students-found').classList.toggle('hidden', shown > 0);
});
</script>
<?php guru_page_end(); ?>
