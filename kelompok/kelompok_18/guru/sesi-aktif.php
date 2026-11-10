<?php

declare(strict_types=1);
require_once __DIR__ . '/_init.php';

$sessionId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!is_int($sessionId) || $sessionId < 1) {
    http_response_code(404);
    exit('Sesi tidak ditemukan.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['_csrf_token'] ?? null)) {
        http_response_code(403);
        exit('Permintaan tidak valid.');
    }

    $postedSessionId = filter_input(INPUT_POST, 'session_id', FILTER_VALIDATE_INT);
    $action = $_POST['action'] ?? null;
    if ($postedSessionId !== $sessionId || !in_array($action, ['finish_session', 'cancel_session'], true)) {
        http_response_code(422);
        exit('Data sesi tidak valid.');
    }

    try {
        guruEndSession($sessionId, $action === 'finish_session' ? 'selesai' : 'dibatalkan');
        header('Location: sesi-aktif.php?id=' . $sessionId);
        exit;
    } catch (DomainException $exception) {
        http_response_code(409);
        exit('Sesi tidak ditemukan atau tidak dapat diubah.');
    } catch (Throwable $exception) {
        guruReportBackendError($exception);
    }
}

try {
    $session = guruOwnedSession($sessionId);
    if ($session === null) {
        http_response_code(404);
        exit('Sesi tidak ditemukan.');
    }
} catch (Throwable $exception) {
    guruReportBackendError($exception);
}

$isOngoing = $session['status'] === 'berlangsung';
$canChange = $isOngoing && $session['semester_status'] === 'aktif';
$statusLabels = [
    'direncanakan' => 'Direncanakan',
    'berlangsung' => 'Berlangsung',
    'selesai' => 'Selesai',
    'dibatalkan' => 'Dibatalkan',
];
$statusLabel = $statusLabels[$session['status']] ?? $session['status'];
$scheduleText = $session['schedule_id'] === null
    ? 'Tanpa jadwal'
    : (($session['day_label'] ?? '') . ' · ' . substr((string) $session['scheduled_start'], 0, 5)
        . '–' . substr((string) $session['scheduled_end'], 0, 5));

guru_page_start('Sesi Pembelajaran', 'kelas.php');
?>
<section class="flex flex-col gap-4 rounded-2xl border border-[#dfe7dd] bg-white p-5 sm:flex-row sm:items-center sm:justify-between sm:p-6">
    <div><span class="inline-flex rounded-full bg-[#e8f1e7] px-3 py-1 text-xs font-semibold text-[#31583c]"><?= attendara_ui_escape($statusLabel) ?></span><h2 class="mt-3 text-xl font-semibold text-[#20392a]"><?= attendara_ui_escape($session['class_name']) ?></h2><p class="mt-1 text-sm text-slate-600"><?= attendara_ui_escape($scheduleText . ' · ' . $session['semester'] . ' · ' . date('d M Y', strtotime($session['tanggal']))) ?></p></div>
    <?php if ($canChange): ?>
        <div class="flex flex-wrap gap-2"><button id="open-finish" type="button" class="min-h-10 rounded-lg bg-[#1e5638] px-4 text-sm font-semibold text-white hover:bg-[#17452d]">Selesaikan Sesi</button><button id="open-cancel" type="button" class="min-h-10 rounded-lg border border-red-200 bg-white px-4 text-sm font-semibold text-red-700 hover:bg-red-50">Batalkan Sesi</button></div>
    <?php elseif ($isOngoing): ?>
        <p class="text-sm text-amber-800">Semester sesi ini tidak aktif; sesi tidak dapat diubah.</p>
    <?php endif; ?>
</section>

<section class="space-y-4">
    <div><h2 class="text-lg font-semibold text-[#20392a]">Menu Dalam Sesi</h2></div>
    <div class="grid gap-4 md:grid-cols-3">
        <a href="tap-sesi.php?session_id=<?= (int) $session['id'] ?>" class="rounded-2xl border border-[#e2e9e2] bg-white p-5 hover:border-[#a9c3ad] hover:bg-[#fbfcfa]"><h3 class="font-semibold text-[#20392a]">Tap Kehadiran</h3><p class="mt-2 text-sm text-slate-500">Tandai siswa yang sudah melakukan tap.</p></a>
        <a href="absensi.php?session_id=<?= (int) $session['id'] ?>" class="rounded-2xl border border-[#e2e9e2] bg-white p-5 hover:border-[#a9c3ad] hover:bg-[#fbfcfa]"><h3 class="font-semibold text-[#20392a]">Data Siswa &amp; Poin</h3><p class="mt-2 text-sm text-slate-500">Atur status kehadiran dan poin siswa.</p></a>
        <a href="kelompok.php?session_id=<?= (int) $session['id'] ?>" class="rounded-2xl border border-[#e2e9e2] bg-white p-5 hover:border-[#a9c3ad] hover:bg-[#fbfcfa]"><h3 class="font-semibold text-[#20392a]">Kelompok</h3><p class="mt-2 text-sm text-slate-500">Buat dan acak kelompok siswa.</p></a>
    </div>
</section>
<a href="kelas-detail.php?id=<?= (int) $session['class_id'] ?>" class="inline-flex min-h-10 items-center justify-center rounded-lg border border-[#d8e2d8] bg-white px-4 py-2 text-sm font-semibold text-[#284b35] hover:bg-[#f2f6f2]">Kembali ke Kelas</a>

<?php if ($canChange): ?>
    <dialog id="finish-dialog" class="w-[min(28rem,calc(100%-2rem))] rounded-2xl border border-[#dfe7dd] p-0 shadow-xl backdrop:bg-slate-900/30"><div class="p-6"><h2 class="text-lg font-semibold text-[#20392a]">Selesaikan sesi?</h2><p class="mt-2 text-sm text-slate-600">Pastikan status kehadiran dan poin sudah diperiksa.</p><div class="mt-6 flex justify-end gap-2"><button type="button" data-close="finish-dialog" class="min-h-10 rounded-lg border border-[#d8e2d8] px-4 text-sm font-semibold">Kembali</button><form method="post" action="sesi-aktif.php?id=<?= (int) $session['id'] ?>"><input type="hidden" name="_csrf_token" value="<?= attendara_ui_escape(csrfToken()) ?>"><input type="hidden" name="session_id" value="<?= (int) $session['id'] ?>"><input type="hidden" name="action" value="finish_session"><button type="submit" class="min-h-10 rounded-lg bg-[#1e5638] px-4 text-sm font-semibold text-white">Selesaikan</button></form></div></div></dialog>
    <dialog id="cancel-dialog" class="w-[min(28rem,calc(100%-2rem))] rounded-2xl border border-[#dfe7dd] p-0 shadow-xl backdrop:bg-slate-900/30"><div class="p-6"><h2 class="text-lg font-semibold text-[#20392a]">Batalkan sesi?</h2><p class="mt-2 text-sm text-slate-600">Sesi ini akan ditandai batal.</p><div class="mt-6 flex justify-end gap-2"><button type="button" data-close="cancel-dialog" class="min-h-10 rounded-lg border border-[#d8e2d8] px-4 text-sm font-semibold">Kembali</button><form method="post" action="sesi-aktif.php?id=<?= (int) $session['id'] ?>"><input type="hidden" name="_csrf_token" value="<?= attendara_ui_escape(csrfToken()) ?>"><input type="hidden" name="session_id" value="<?= (int) $session['id'] ?>"><input type="hidden" name="action" value="cancel_session"><button type="submit" class="min-h-10 rounded-lg bg-red-600 px-4 text-sm font-semibold text-white">Batalkan Sesi</button></form></div></div></dialog>
    <script>
    const finishDialog = document.getElementById('finish-dialog');
    const cancelDialog = document.getElementById('cancel-dialog');
    document.getElementById('open-finish').addEventListener('click', () => finishDialog.showModal());
    document.getElementById('open-cancel').addEventListener('click', () => cancelDialog.showModal());
    document.querySelectorAll('[data-close]').forEach(button => button.addEventListener('click', () => document.getElementById(button.dataset.close).close()));
    </script>
<?php endif; ?>
<?php guru_page_end(); ?>
