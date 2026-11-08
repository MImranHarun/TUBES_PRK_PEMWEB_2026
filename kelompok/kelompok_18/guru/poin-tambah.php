<?php

declare(strict_types=1);
require_once __DIR__ . '/_init.php';

$sessionId = filter_input(INPUT_GET, 'session_id', FILTER_VALIDATE_INT);
$studentId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!is_int($sessionId) || $sessionId < 1 || !is_int($studentId) || $studentId < 1) {
    http_response_code(404);
    exit('Data poin tidak ditemukan.');
}

$pageError = '';
$pointsInput = '';
$noteInput = '';
try {
    $session = guruOwnedSession($sessionId);
    if ($session === null) {
        http_response_code(404);
        exit('Data poin tidak ditemukan.');
    }
    $students = guruSessionAttendance($sessionId);
    $student = null;
    foreach ($students as $candidate) {
        if ((int) $candidate['id'] === $studentId) {
            $student = $candidate;
            break;
        }
    }
    if ($student === null) {
        http_response_code(404);
        exit('Data poin tidak ditemukan.');
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!verifyCsrfToken($_POST['_csrf_token'] ?? null)) {
            http_response_code(403);
            exit('Permintaan tidak valid.');
        }
        $postedSessionId = filter_input(INPUT_POST, 'session_id', FILTER_VALIDATE_INT);
        $postedStudentId = filter_input(INPUT_POST, 'student_id', FILTER_VALIDATE_INT);
        $pointsValue = $_POST['points'] ?? null;
        $noteValue = $_POST['note'] ?? null;
        if (
            $postedSessionId !== $sessionId
            || $postedStudentId !== $studentId
            || !is_string($pointsValue)
            || (!is_string($noteValue) && $noteValue !== null)
        ) {
            http_response_code(422);
            $pageError = 'Data poin tidak valid. Periksa kembali lalu coba lagi.';
        } else {
            $pointsInput = trim($pointsValue);
            $noteInput = $noteValue ?? '';
            guruAddPoints($sessionId, $studentId, $pointsInput, $noteInput);
            header('Location: absensi.php?session_id=' . $sessionId);
            exit;
        }
    }
    $history = guruStudentPointHistory($sessionId, $studentId);
} catch (DomainException $exception) {
    $pageError = $exception->getMessage();
    $history = [];
} catch (Throwable $exception) {
    guruReportBackendError($exception);
}

guru_page_start('Tambah Poin Keaktifan', 'kelas.php');
?>
<?php if ($pageError !== ''): ?><p class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert"><?= attendara_ui_escape($pageError) ?></p><?php endif; ?>
<section class="max-w-2xl rounded-2xl border border-[#e2e9e2] bg-white p-5 sm:p-6">
    <div><h2 class="text-lg font-semibold text-[#20392a]"><?= attendara_ui_escape($student['name']) ?></h2><p class="mt-1 text-sm text-slate-500"><?= attendara_ui_escape($student['number']) ?> · <?= attendara_ui_escape($session['class_name'] . ' · ' . $session['semester']) ?></p></div>
    <?php if ($session['status'] !== 'berlangsung' || $session['semester_status'] !== 'aktif'): ?>
        <p class="mt-4 rounded-xl bg-amber-50 px-4 py-3 text-sm text-amber-800">Poin hanya dapat ditambahkan pada sesi yang sedang berlangsung di semester aktif.</p>
    <?php else: ?>
        <form method="post" action="poin-tambah.php?session_id=<?= (int) $sessionId ?>&amp;id=<?= (int) $studentId ?>" class="mt-5 space-y-4">
            <input type="hidden" name="_csrf_token" value="<?= attendara_ui_escape(csrfToken()) ?>">
            <input type="hidden" name="session_id" value="<?= (int) $sessionId ?>">
            <input type="hidden" name="student_id" value="<?= (int) $studentId ?>">
            <div><label for="participation-points" class="mb-1.5 block text-sm font-medium text-slate-700">Jumlah Poin</label><input id="participation-points" name="points" type="number" min="0" max="99999.99" step="0.01" required value="<?= attendara_ui_escape($pointsInput) ?>" placeholder="Contoh: 5 atau 10" class="min-h-10 w-full rounded-lg border border-[#d8e2d8] px-3 text-sm focus:border-[#376c4d] focus:outline-none focus:ring-2 focus:ring-[#e6efe6]"></div>
            <div><label for="participation-note" class="mb-1.5 block text-sm font-medium text-slate-700">Catatan (opsional)</label><textarea id="participation-note" name="note" maxlength="500" rows="3" class="w-full rounded-lg border border-[#d8e2d8] px-3 py-2 text-sm focus:border-[#376c4d] focus:outline-none focus:ring-2 focus:ring-[#e6efe6]"><?= attendara_ui_escape($noteInput) ?></textarea></div>
            <div class="flex flex-wrap gap-2"><button id="save-points" type="submit" class="min-h-10 rounded-lg bg-[#1e5638] px-4 text-sm font-semibold text-white">Tambah Poin</button><a href="absensi.php?session_id=<?= (int) $sessionId ?>" class="inline-flex min-h-10 items-center rounded-lg border border-[#d8e2d8] px-4 text-sm font-semibold text-[#284b35]">Kembali</a></div>
        </form>
    <?php endif; ?>
</section>
<section class="max-w-2xl space-y-3">
    <div><h2 class="text-lg font-semibold text-[#20392a]">Riwayat Poin pada Sesi Ini</h2><p class="mt-1 text-sm text-slate-600">Total saat ini: <?= number_format((float) $student['points'], 2, '.', ',') ?></p></div>
    <?php if ($history === []): ?>
        <p class="rounded-xl border border-[#e2e9e2] bg-white px-4 py-6 text-center text-sm text-slate-500">Belum ada poin pada sesi ini.</p>
    <?php else: ?>
        <div class="overflow-x-auto rounded-2xl border border-[#e2e9e2] bg-white"><table class="min-w-full divide-y divide-[#edf1ec] text-left text-sm"><thead class="bg-[#f3f6f1] text-xs uppercase tracking-wide text-slate-500"><tr><th class="px-4 py-3 font-semibold">Waktu</th><th class="px-4 py-3 font-semibold">Poin</th><th class="px-4 py-3 font-semibold">Catatan</th></tr></thead><tbody class="divide-y divide-[#edf1ec] text-slate-700">
            <?php foreach ($history as $entry): ?><tr><td class="whitespace-nowrap px-4 py-3"><?= attendara_ui_escape(date('d M Y H:i', strtotime($entry['waktu_dicatat']))) ?></td><td class="whitespace-nowrap px-4 py-3"><?= number_format((float) $entry['poin'], 2, '.', ',') ?></td><td class="px-4 py-3"><?= attendara_ui_escape((string) ($entry['catatan'] ?? '—')) ?></td></tr><?php endforeach; ?>
        </tbody></table></div>
    <?php endif; ?>
</section>
<?php guru_page_end(); ?>
