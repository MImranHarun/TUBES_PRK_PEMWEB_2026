<?php

declare(strict_types=1);
require_once __DIR__ . '/_init.php';

$classId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!is_int($classId) || $classId < 1) {
    http_response_code(404);
    exit('Kelas tidak ditemukan.');
}

$pageError = '';
$students = [];
$schedules = [];
try {
    $profile = guruProfile();
    $activeSemester = guruActiveSemester();
    $class = $profile !== null && $activeSemester !== null
        ? guruOwnedActiveClass((int) $profile['id'], (int) $activeSemester['id'], $classId)
        : null;
    if ($class === null) {
        http_response_code(404);
        exit('Kelas tidak ditemukan.');
    }
    $students = guruClassStudents((int) $class['id'], (int) $class['semester_id']);
    $schedules = guruClassSchedules((int) $class['assignment_id']);
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!verifyCsrfToken($_POST['_csrf_token'] ?? null)) {
            http_response_code(403);
            exit('Permintaan tidak valid.');
        }
        $postedClassId = filter_input(INPUT_POST, 'class_id', FILTER_VALIDATE_INT);
        $scheduleId = filter_input(INPUT_POST, 'schedule_id', FILTER_VALIDATE_INT);
        if (
            ($_POST['action'] ?? null) !== 'start_session'
            || $postedClassId !== $classId
            || !is_int($scheduleId)
            || $scheduleId < 1
        ) {
            http_response_code(422);
            $pageError = 'Data sesi tidak valid. Muat ulang halaman lalu coba kembali.';
        } else {
            $newSessionId = guruStartSession($classId, $scheduleId);
            header('Location: sesi-aktif.php?id=' . $newSessionId);
            exit;
        }
    }
    $rows = array_map(static fn (array $student): array => [$student['number'], $student['name']], $students);
} catch (DomainException $exception) {
    $pageError = $exception->getMessage();
} catch (Throwable $exception) {
    guruReportBackendError($exception);
}

guru_page_start('Detail Kelas', 'kelas.php');
?>
<?php if ($pageError !== ''): ?><p class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert"><?= attendara_ui_escape($pageError) ?></p><?php endif; ?>
<section class="flex flex-col gap-4 rounded-2xl border border-[#e2e9e2] bg-white p-5 sm:flex-row sm:items-center sm:justify-between sm:p-6">
    <div><p class="text-sm text-slate-500"><?= attendara_ui_escape($class['semester']) ?></p><h2 class="mt-1 text-xl font-semibold text-[#20392a]"><?= attendara_ui_escape($class['name']) ?></h2><p class="mt-1 text-sm text-slate-600"><?= count($rows) ?> siswa</p></div>
    <div class="flex flex-wrap gap-2">
        <a href="pengaturan-kelas.php?id=<?= (int) $class['id'] ?>" class="inline-flex min-h-10 items-center justify-center rounded-lg border border-[#d8e2d8] bg-white px-4 py-2 text-sm font-semibold text-[#284b35] hover:bg-[#f2f6f2]">Pengaturan Kelas</a>
        <button id="open-session-confirm" type="button" class="inline-flex min-h-10 items-center justify-center rounded-lg bg-[#1e5638] px-4 py-2 text-sm font-semibold text-white hover:bg-[#17452d]">Mulai Sesi</button>
    </div>
</section>

<section class="space-y-4">
    <div><h2 class="text-lg font-semibold text-[#20392a]">Daftar Siswa</h2><p class="mt-1 text-sm text-slate-600">Nama siswa pada kelas ini.</p></div>
    <?php guru_render_table(['Nomor Induk', 'Nama Siswa'], $rows, 'Belum ada siswa pada kelas ini.'); ?>
</section>

<dialog id="session-confirm" class="w-[min(28rem,calc(100%-2rem))] rounded-2xl border border-[#dfe7dd] p-0 shadow-xl backdrop:bg-slate-900/30">
    <div class="p-6">
        <h2 class="text-lg font-semibold text-[#20392a]">Mulai sesi pembelajaran?</h2>
        <p class="mt-2 text-sm leading-6 text-slate-600">Sesi untuk <?= attendara_ui_escape($class['name']) ?> akan dibuka dengan jadwal yang terhubung ke penugasan Guru.</p>
        <?php if ($schedules === []): ?>
            <p class="mt-3 text-sm text-amber-800">Tidak ada jadwal yang berlaku untuk kelas ini saat ini.</p>
        <?php else: ?>
            <form id="start-session-form" method="post" action="kelas-detail.php?id=<?= (int) $class['id'] ?>" class="mt-4 space-y-3">
                <input type="hidden" name="_csrf_token" value="<?= attendara_ui_escape(csrfToken()) ?>">
                <input type="hidden" name="action" value="start_session">
                <input type="hidden" name="class_id" value="<?= (int) $class['id'] ?>">
                <?php if (count($schedules) === 1): ?>
                    <input type="hidden" name="schedule_id" value="<?= (int) $schedules[0]['id'] ?>">
                    <p class="text-sm text-slate-600"><?= attendara_ui_escape($schedules[0]['day_label'] . ' · ' . $schedules[0]['start'] . '–' . $schedules[0]['end']) ?></p>
                <?php else: ?>
                    <label for="session-schedule" class="block text-sm font-medium text-slate-700">Pilih jadwal</label>
                    <select id="session-schedule" name="schedule_id" required class="min-h-10 w-full rounded-lg border border-[#d8e2d8] bg-white px-3 text-sm">
                        <?php foreach ($schedules as $availableSchedule): ?>
                            <option value="<?= (int) $availableSchedule['id'] ?>"><?= attendara_ui_escape($availableSchedule['day_label'] . ' · ' . $availableSchedule['start'] . '–' . $availableSchedule['end']) ?></option>
                        <?php endforeach; ?>
                    </select>
                <?php endif; ?>
                <div class="mt-6 flex justify-end gap-2"><button id="cancel-session-confirm" type="button" class="min-h-10 rounded-lg border border-[#d8e2d8] bg-white px-4 text-sm font-semibold text-[#284b35]">Kembali</button><button id="confirm-start-session" type="submit" class="min-h-10 rounded-lg bg-[#1e5638] px-4 text-sm font-semibold text-white">Ya, Mulai Sesi</button></div>
            </form>
        <?php endif; ?>
    </div>
</dialog>
<script>
const sessionConfirm = document.getElementById('session-confirm');
document.getElementById('open-session-confirm').addEventListener('click', () => sessionConfirm.showModal());
document.getElementById('cancel-session-confirm')?.addEventListener('click', () => sessionConfirm.close());
</script>
<?php guru_page_end(); ?>
