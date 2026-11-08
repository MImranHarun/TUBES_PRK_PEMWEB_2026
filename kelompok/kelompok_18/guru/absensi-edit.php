<?php

declare(strict_types=1);
require_once __DIR__ . '/_init.php';

$sessionId = filter_input(INPUT_GET, 'session_id', FILTER_VALIDATE_INT);
$studentId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!is_int($sessionId) || $sessionId < 1 || !is_int($studentId) || $studentId < 1) {
    http_response_code(404);
    exit('Data absensi tidak ditemukan.');
}

$pageError = '';
$allowedStatuses = ['hadir', 'izin', 'alpha', 'sakit'];
try {
    $session = guruOwnedSession($sessionId);
    if ($session === null) {
        http_response_code(404);
        exit('Data absensi tidak ditemukan.');
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
        exit('Data absensi tidak ditemukan.');
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!verifyCsrfToken($_POST['_csrf_token'] ?? null)) {
            http_response_code(403);
            exit('Permintaan tidak valid.');
        }
        $postedSessionId = filter_input(INPUT_POST, 'session_id', FILTER_VALIDATE_INT);
        $postedStudentId = filter_input(INPUT_POST, 'student_id', FILTER_VALIDATE_INT);
        $status = $_POST['status'] ?? null;
        $note = $_POST['note'] ?? null;
        if (
            $postedSessionId !== $sessionId
            || $postedStudentId !== $studentId
            || !is_string($status)
            || !in_array($status, $allowedStatuses, true)
            || (!is_string($note) && $note !== null)
        ) {
            http_response_code(422);
            $pageError = 'Data absensi tidak valid. Periksa kembali lalu coba lagi.';
        } else {
            guruSaveAttendance($sessionId, $studentId, $status, $note);
            header('Location: absensi.php?session_id=' . $sessionId);
            exit;
        }
    }
} catch (DomainException $exception) {
    $pageError = $exception->getMessage();
} catch (Throwable $exception) {
    guruReportBackendError($exception);
}

guru_page_start('Ubah Status Kehadiran', 'kelas.php');
?>
<?php if ($pageError !== ''): ?><p class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert"><?= attendara_ui_escape($pageError) ?></p><?php endif; ?>
<section class="max-w-2xl rounded-2xl border border-[#e2e9e2] bg-white p-5 sm:p-6">
    <div><h2 class="text-lg font-semibold text-[#20392a]"><?= attendara_ui_escape($student['name']) ?></h2><p class="mt-1 text-sm text-slate-500"><?= attendara_ui_escape($student['number']) ?></p></div>
    <form id="attendance-edit-form" method="post" action="absensi-edit.php?session_id=<?= (int) $sessionId ?>&amp;id=<?= (int) $studentId ?>" class="mt-5 space-y-4">
        <input type="hidden" name="_csrf_token" value="<?= attendara_ui_escape(csrfToken()) ?>">
        <input type="hidden" name="session_id" value="<?= (int) $sessionId ?>">
        <input type="hidden" name="student_id" value="<?= (int) $studentId ?>">
        <div><label for="attendance-status" class="mb-1.5 block text-sm font-medium text-slate-700">Status Kehadiran</label><select id="attendance-status" name="status" required class="min-h-10 w-full rounded-lg border border-[#d8e2d8] bg-white px-3 text-sm">
            <?php foreach (['hadir' => 'Hadir', 'izin' => 'Izin', 'alpha' => 'Alpha', 'sakit' => 'Sakit'] as $value => $label): ?>
                <option value="<?= attendara_ui_escape($value) ?>"<?= $student['attendance_status'] === $value ? ' selected' : '' ?>><?= attendara_ui_escape($label) ?></option>
            <?php endforeach; ?>
        </select></div>
        <div><label for="attendance-note" class="mb-1.5 block text-sm font-medium text-slate-700">Catatan (opsional)</label><textarea id="attendance-note" name="note" maxlength="500" rows="3" class="w-full rounded-lg border border-[#d8e2d8] px-3 py-2 text-sm focus:border-[#376c4d] focus:outline-none focus:ring-2 focus:ring-[#e6efe6]"><?= attendara_ui_escape((string) ($student['note'] ?? '')) ?></textarea></div>
        <div class="flex flex-wrap gap-2"><button id="save-attendance" type="submit" class="min-h-10 rounded-lg bg-[#1e5638] px-4 text-sm font-semibold text-white">Simpan Status</button><a href="absensi.php?session_id=<?= (int) $sessionId ?>" class="inline-flex min-h-10 items-center rounded-lg border border-[#d8e2d8] px-4 text-sm font-semibold text-[#284b35]">Kembali</a></div>
    </form>
</section>
<?php guru_page_end(); ?>
