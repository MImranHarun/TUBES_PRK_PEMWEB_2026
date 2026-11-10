<?php

declare(strict_types=1);
require_once __DIR__ . '/_init.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');
    if (!verifyCsrfToken($_POST['_csrf_token'] ?? null)) {
        http_response_code(403);
        echo json_encode(['error' => 'Permintaan tidak valid.']);
        exit;
    }

    $sessionId = filter_input(INPUT_POST, 'session_id', FILTER_VALIDATE_INT);
    $studentId = filter_input(INPUT_POST, 'student_id', FILTER_VALIDATE_INT);
    if (
        ($_POST['action'] ?? null) !== 'tap_student'
        || !is_int($sessionId) || $sessionId < 1
        || !is_int($studentId) || $studentId < 1
    ) {
        http_response_code(422);
        echo json_encode(['error' => 'Data tap tidak valid.']);
        exit;
    }

    try {
        echo json_encode(guruTapAttendance($sessionId, $studentId), JSON_THROW_ON_ERROR);
        exit;
    } catch (GuruTapCooldownException $exception) {
        http_response_code(429);
        echo json_encode(['error' => $exception->getMessage(), 'retry_after' => 2]);
        exit;
    } catch (DomainException $exception) {
        http_response_code(409);
        echo json_encode(['error' => $exception->getMessage()]);
        exit;
    } catch (Throwable $exception) {
        error_log('ATTENDARA Guru tap failed: ' . $exception->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Tap tidak dapat diproses. Coba kembali.']);
        exit;
    }
}

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
    if ($session['status'] !== 'berlangsung' || $session['semester_status'] !== 'aktif') {
        http_response_code(409);
        exit('Tap hanya tersedia pada sesi yang sedang berlangsung di semester aktif.');
    }
    $students = guruSessionAttendance($sessionId);
    $tappedTimes = guruTappedStudentTimes($sessionId);
    $tapCount = guruTapCount($sessionId);
} catch (Throwable $exception) {
    guruReportBackendError($exception);
}

$csrfJson = json_encode(csrfToken(), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?: '""';

guru_page_start('Tap Kehadiran', 'kelas.php');
?>
<nav aria-label="Menu sesi" class="flex flex-wrap gap-2">
    <a href="tap-sesi.php?session_id=<?= (int) $sessionId ?>" aria-current="page" class="rounded-lg bg-[#1e5638] px-4 py-2 text-sm font-semibold text-white">Tap Kehadiran</a>
    <a href="absensi.php?session_id=<?= (int) $sessionId ?>" class="rounded-lg border border-[#d8e2d8] bg-white px-4 py-2 text-sm font-semibold text-[#526257]">Data Siswa &amp; Poin</a>
    <a href="kelompok.php?session_id=<?= (int) $sessionId ?>" class="rounded-lg border border-[#d8e2d8] bg-white px-4 py-2 text-sm font-semibold text-[#526257]">Kelompok</a>
</nav>
<section class="flex flex-col gap-4 rounded-2xl border border-[#e2e9e2] bg-white p-5 sm:flex-row sm:items-center sm:justify-between sm:p-6">
    <div><h2 class="text-lg font-semibold text-[#20392a]">Tap Siswa · <?= attendara_ui_escape($session['class_name']) ?></h2><p class="mt-1 text-sm text-slate-600">Setelah tombol ditekan, tunggu 2 detik sebelum permintaan tap diproses.</p></div>
    <div class="rounded-xl bg-[#e9f0e8] px-4 py-3 text-center"><p class="text-xs font-medium text-[#526257]">Tap tercatat pada sesi ini</p><p class="mt-1 text-2xl font-semibold text-[#173e2b]"><span id="tap-count"><?= (int) $tapCount ?></span> / <?= count($students) ?></p></div>
</section>
<section class="space-y-4">
    <div><h2 class="text-lg font-semibold text-[#20392a]">Daftar Siswa</h2></div>
    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
        <?php foreach ($students as $student): ?>
            <?php $alreadyTapped = isset($tappedTimes[(int) $student['id']]); ?>
            <article class="flex items-center justify-between gap-3 rounded-2xl border border-[#e2e9e2] bg-white p-4<?= $alreadyTapped ? ' border-[#9db99f] bg-[#f7faf6]' : '' ?>" data-student-card data-student-id="<?= (int) $student['id'] ?>">
                <div class="min-w-0"><p class="truncate text-sm font-semibold text-[#20392a]"><?= attendara_ui_escape($student['name']) ?></p><p class="mt-1 text-xs text-slate-500"><?= attendara_ui_escape($student['number']) ?></p><p class="mt-1 text-xs text-slate-400" data-tap-time><?= $alreadyTapped ? 'Tap diterima ' . attendara_ui_escape($tappedTimes[(int) $student['id']]) : 'Belum tap' ?></p></div>
                <button type="button" data-tap-button<?= $alreadyTapped ? ' disabled data-tapped="true"' : '' ?> class="min-h-9 shrink-0 rounded-lg border border-[#d8e2d8] px-3 text-xs font-semibold text-[#284b35] hover:bg-[#f2f6f2] disabled:border-[#cfe0ce] disabled:bg-[#e8f1e7] disabled:text-[#31583c]"><?= $alreadyTapped ? 'Sudah Tap' : 'Tap Masuk' ?></button>
            </article>
        <?php endforeach; ?>
    </div>
</section>
<div class="flex flex-wrap gap-2"><a href="sesi-aktif.php?id=<?= (int) $sessionId ?>" class="inline-flex min-h-10 items-center justify-center rounded-lg border border-[#d8e2d8] bg-white px-4 py-2 text-sm font-semibold text-[#284b35]">Kembali ke Sesi</a><a href="absensi.php?session_id=<?= (int) $sessionId ?>" class="inline-flex min-h-10 items-center justify-center rounded-lg bg-[#1e5638] px-4 py-2 text-sm font-semibold text-white">Lanjut ke Data Siswa</a></div>
<script>
const tapToken = <?= $csrfJson ?>;
const tapEndpoint = 'tap-sesi.php?session_id=<?= (int) $sessionId ?>';
document.querySelectorAll('[data-student-card]').forEach(card => {
    const button = card.querySelector('[data-tap-button]');
    button.addEventListener('click', async () => {
        if (button.dataset.tapped === 'true' || button.disabled) return;
        button.disabled = true;
        button.textContent = 'Tunggu 2 detik…';
        await new Promise(resolve => window.setTimeout(resolve, 2000));

        const body = new FormData();
        body.set('_csrf_token', tapToken);
        body.set('action', 'tap_student');
        body.set('session_id', card.dataset.sessionId || '<?= (int) $sessionId ?>');
        body.set('student_id', card.dataset.studentId);
        try {
            const response = await fetch(tapEndpoint, {method: 'POST', body, credentials: 'same-origin', headers: {'Accept': 'application/json'}});
            const result = await response.json();
            if (!response.ok) throw new Error(result.error || 'Tap tidak dapat diproses.');
            card.querySelector('[data-tap-time]').textContent = `Tap diterima ${result.time}`;
            card.classList.add('border-[#9db99f]', 'bg-[#f7faf6]');
            button.dataset.tapped = 'true';
            button.textContent = 'Sudah Tap';
            button.classList.add('border-[#cfe0ce]', 'bg-[#e8f1e7]', 'text-[#31583c]');
            document.getElementById('tap-count').textContent = String(result.tap_count);
        } catch (error) {
            button.disabled = false;
            button.textContent = 'Tap Masuk';
            card.querySelector('[data-tap-time]').textContent = error.message;
        }
    });
});
</script>
<?php guru_page_end(); ?>
