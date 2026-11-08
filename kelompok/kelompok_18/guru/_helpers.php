<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

final class GuruTapCooldownException extends RuntimeException
{
}

/** Return the PDO connection created by the shared application config. */
function guruDb(): PDO
{
    global $pdo;

    return $pdo;
}

/** Return the teacher profile linked to the authenticated user. */
function guruProfile(): ?array
{
    $statement = guruDb()->prepare(
        'SELECT id, user_id, nomor_induk, nama, email, no_telepon
         FROM guru
         WHERE user_id = :user_id AND is_active = 1
         LIMIT 1'
    );
    $statement->execute(['user_id' => currentUser()['id']]);
    $profile = $statement->fetch();

    return is_array($profile) ? $profile : null;
}

/** Return the single active semester, or null when none (or multiple) are configured. */
function guruActiveSemester(): ?array
{
    $statement = guruDb()->prepare(
        "SELECT id, kode, nama, tanggal_mulai, tanggal_selesai
         FROM semester
         WHERE status = :status
         ORDER BY tanggal_mulai DESC, id DESC
         LIMIT 2"
    );
    $statement->execute(['status' => 'aktif']);
    $semesters = $statement->fetchAll();

    if (count($semesters) > 1) {
        error_log('ATTENDARA Guru: more than one active semester is configured.');
        return null;
    }

    return $semesters[0] ?? null;
}

/** Fetch active semester assignments for the authenticated teacher. */
function guruActiveAssignments(int $guruId, int $semesterId): array
{
    $statement = guruDb()->prepare(
        'SELECT pg.id AS assignment_id, k.id AS id, k.kode AS code, k.nama AS name,
                k.tingkat AS level, s.id AS semester_id, s.nama AS semester,
                COUNT(DISTINCT ak.siswa_id) AS student_count
         FROM penugasan_guru pg
         INNER JOIN kelas k ON k.id = pg.kelas_id AND k.is_active = 1
         INNER JOIN semester s ON s.id = pg.semester_id
         LEFT JOIN anggota_kelas ak ON ak.kelas_id = k.id AND ak.semester_id = s.id
         LEFT JOIN siswa sis ON sis.id = ak.siswa_id AND sis.is_active = 1
         WHERE pg.guru_id = :guru_id AND pg.semester_id = :semester_id
         GROUP BY pg.id, k.id, k.kode, k.nama, k.tingkat, s.id, s.nama
         ORDER BY k.nama, k.id'
    );
    $statement->execute(['guru_id' => $guruId, 'semester_id' => $semesterId]);

    return $statement->fetchAll();
}

/** Fetch schedules belonging to the teacher's active-semester assignments. */
function guruActiveSchedules(int $guruId, int $semesterId): array
{
    $statement = guruDb()->prepare(
        'SELECT jm.id, pg.id AS assignment_id, k.id AS class_id, k.nama AS class_name,
                s.nama AS semester, jm.hari AS day_number, jm.jam_mulai AS start,
                jm.jam_selesai AS end, jm.berlaku_mulai AS valid_from,
                jm.berlaku_sampai AS valid_until
         FROM jadwal_mengajar jm
         INNER JOIN penugasan_guru pg ON pg.id = jm.penugasan_guru_id
         INNER JOIN kelas k ON k.id = pg.kelas_id AND k.is_active = 1
         INNER JOIN semester s ON s.id = pg.semester_id
         WHERE pg.guru_id = :guru_id AND pg.semester_id = :semester_id
           AND (jm.berlaku_mulai IS NULL OR jm.berlaku_mulai <= CURRENT_DATE())
           AND (jm.berlaku_sampai IS NULL OR jm.berlaku_sampai >= CURRENT_DATE())
         ORDER BY jm.hari, jm.jam_mulai, k.nama'
    );
    $statement->execute(['guru_id' => $guruId, 'semester_id' => $semesterId]);
    $schedules = $statement->fetchAll();

    foreach ($schedules as &$schedule) {
        $schedule['day_label'] = 'Hari ' . $schedule['day_number'];
        $schedule['valid_from'] = $schedule['valid_from'] === null ? 'Tidak dibatasi' : date('d M Y', strtotime($schedule['valid_from']));
        $schedule['valid_until'] = $schedule['valid_until'] === null ? 'Tidak dibatasi' : date('d M Y', strtotime($schedule['valid_until']));
    }
    unset($schedule);

    return $schedules;
}

/** Find one class only when the teacher owns its active-semester assignment. */
function guruOwnedActiveClass(int $guruId, int $semesterId, int $classId): ?array
{
    $statement = guruDb()->prepare(
        'SELECT pg.id AS assignment_id, k.id, k.kode AS code, k.nama AS name,
                k.tingkat AS level, s.id AS semester_id, s.nama AS semester
         FROM penugasan_guru pg
         INNER JOIN kelas k ON k.id = pg.kelas_id AND k.is_active = 1
         INNER JOIN semester s ON s.id = pg.semester_id
         WHERE pg.guru_id = :guru_id AND pg.semester_id = :semester_id AND k.id = :class_id
         LIMIT 1'
    );
    $statement->execute([
        'guru_id' => $guruId,
        'semester_id' => $semesterId,
        'class_id' => $classId,
    ]);
    $class = $statement->fetch();

    return is_array($class) ? $class : null;
}

/** Fetch active students assigned to a class in the specified semester. */
function guruClassStudents(int $classId, int $semesterId): array
{
    $statement = guruDb()->prepare(
        'SELECT sis.id, sis.nomor_induk AS number, sis.nama AS name
         FROM anggota_kelas ak
         INNER JOIN siswa sis ON sis.id = ak.siswa_id AND sis.is_active = 1
         WHERE ak.kelas_id = :class_id AND ak.semester_id = :semester_id
         ORDER BY sis.nama, sis.id'
    );
    $statement->execute(['class_id' => $classId, 'semester_id' => $semesterId]);

    return $statement->fetchAll();
}

/** Fetch schedules for one teacher-owned active-semester class. */
function guruClassSchedules(int $assignmentId): array
{
    $statement = guruDb()->prepare(
        'SELECT id, hari AS day_number, jam_mulai AS start, jam_selesai AS end,
                berlaku_mulai AS valid_from, berlaku_sampai AS valid_until
         FROM jadwal_mengajar
         WHERE penugasan_guru_id = :assignment_id
           AND (berlaku_mulai IS NULL OR berlaku_mulai <= CURRENT_DATE())
           AND (berlaku_sampai IS NULL OR berlaku_sampai >= CURRENT_DATE())
         ORDER BY hari, jam_mulai'
    );
    $statement->execute(['assignment_id' => $assignmentId]);
    $schedules = $statement->fetchAll();
    foreach ($schedules as &$schedule) {
        $schedule['day_label'] = 'Hari ' . $schedule['day_number'];
        $schedule['valid_from'] = $schedule['valid_from'] === null ? 'Tidak dibatasi' : date('d M Y', strtotime($schedule['valid_from']));
        $schedule['valid_until'] = $schedule['valid_until'] === null ? 'Tidak dibatasi' : date('d M Y', strtotime($schedule['valid_until']));
    }
    unset($schedule);

    return $schedules;
}

/** Create or resume today's ongoing session for a teacher-owned schedule. */
function guruStartSession(int $classId, int $scheduleId): int
{
    $profile = guruProfile();
    $semester = guruActiveSemester();
    if ($profile === null || $semester === null) {
        throw new DomainException('Profil Guru atau semester aktif tidak tersedia.');
    }

    $pdo = guruDb();
    $pdo->beginTransaction();
    try {
        $assignmentQuery = $pdo->prepare(
            'SELECT pg.id
             FROM penugasan_guru pg
             INNER JOIN kelas k ON k.id = pg.kelas_id AND k.is_active = 1
             INNER JOIN semester sem ON sem.id = pg.semester_id AND sem.status = :active_status
             WHERE pg.guru_id = :guru_id AND pg.kelas_id = :class_id
               AND pg.semester_id = :semester_id
             LIMIT 1 FOR UPDATE'
        );
        $assignmentQuery->execute([
            'active_status' => 'aktif',
            'guru_id' => $profile['id'],
            'class_id' => $classId,
            'semester_id' => $semester['id'],
        ]);
        $assignmentId = $assignmentQuery->fetchColumn();
        if ($assignmentId === false) {
            throw new DomainException('Kelas tidak ditemukan.');
        }

        $scheduleQuery = $pdo->prepare(
            'SELECT id
             FROM jadwal_mengajar
             WHERE id = :schedule_id AND penugasan_guru_id = :assignment_id
               AND (berlaku_mulai IS NULL OR berlaku_mulai <= CURRENT_DATE())
               AND (berlaku_sampai IS NULL OR berlaku_sampai >= CURRENT_DATE())
             LIMIT 1'
        );
        $scheduleQuery->execute(['schedule_id' => $scheduleId, 'assignment_id' => $assignmentId]);
        if ($scheduleQuery->fetchColumn() === false) {
            throw new DomainException('Jadwal tidak tersedia untuk kelas ini.');
        }

        $ongoingQuery = $pdo->prepare(
            "SELECT id FROM sesi_pembelajaran
             WHERE penugasan_guru_id = :assignment_id AND status = :status
             ORDER BY id DESC LIMIT 1 FOR UPDATE"
        );
        $ongoingQuery->execute(['assignment_id' => $assignmentId, 'status' => 'berlangsung']);
        $ongoingSessionId = $ongoingQuery->fetchColumn();
        if ($ongoingSessionId !== false) {
            $pdo->commit();
            return (int) $ongoingSessionId;
        }

        $insert = $pdo->prepare(
            "INSERT INTO sesi_pembelajaran
                (penugasan_guru_id, jadwal_mengajar_id, tanggal, waktu_mulai, status)
             VALUES (:assignment_id, :schedule_id, CURRENT_DATE(), CURRENT_TIMESTAMP(), :status)"
        );
        $insert->execute([
            'assignment_id' => $assignmentId,
            'schedule_id' => $scheduleId,
            'status' => 'berlangsung',
        ]);
        $sessionId = (int) $pdo->lastInsertId();

        $attendance = $pdo->prepare(
            "INSERT INTO absensi
                (sesi_pembelajaran_id, siswa_id, status, dicatat_oleh_user_id, waktu_dicatat)
             SELECT :session_id, ak.siswa_id, :attendance_status, :user_id, CURRENT_TIMESTAMP()
             FROM anggota_kelas ak
             INNER JOIN siswa sis ON sis.id = ak.siswa_id AND sis.is_active = 1
             WHERE ak.kelas_id = :class_id AND ak.semester_id = :semester_id"
        );
        $attendance->execute([
            'session_id' => $sessionId,
            'attendance_status' => 'hadir',
            'user_id' => currentUser()['id'],
            'class_id' => $classId,
            'semester_id' => $semester['id'],
        ]);
        $pdo->commit();

        return $sessionId;
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $exception;
    }
}

/** Fetch a session only when it belongs to the authenticated teacher. */
function guruOwnedSession(int $sessionId): ?array
{
    $statement = guruDb()->prepare(
        'SELECT sp.id, sp.penugasan_guru_id AS assignment_id, sp.jadwal_mengajar_id AS schedule_id,
                sp.tanggal, sp.waktu_mulai, sp.waktu_selesai, sp.status,
                k.id AS class_id, k.kode AS class_code, k.nama AS class_name,
                sem.id AS semester_id, sem.nama AS semester, sem.status AS semester_status,
                jm.hari AS day_number, jm.jam_mulai AS scheduled_start, jm.jam_selesai AS scheduled_end
         FROM sesi_pembelajaran sp
         INNER JOIN penugasan_guru pg ON pg.id = sp.penugasan_guru_id
         INNER JOIN guru g ON g.id = pg.guru_id
         INNER JOIN kelas k ON k.id = pg.kelas_id
         INNER JOIN semester sem ON sem.id = pg.semester_id
         LEFT JOIN jadwal_mengajar jm ON jm.id = sp.jadwal_mengajar_id
         WHERE sp.id = :session_id AND g.user_id = :user_id
         LIMIT 1'
    );
    $statement->execute(['session_id' => $sessionId, 'user_id' => currentUser()['id']]);
    $session = $statement->fetch();

    if (!is_array($session)) {
        return null;
    }

    $session['day_label'] = $session['day_number'] === null ? null : 'Hari ' . $session['day_number'];
    return $session;
}

/** Fetch the enrolled students and their attendance for one owned session. */
function guruSessionAttendance(int $sessionId): array
{
    $session = guruOwnedSession($sessionId);
    if ($session === null) {
        throw new DomainException('Sesi tidak ditemukan.');
    }

    $statement = guruDb()->prepare(
        "SELECT sis.id, sis.nomor_induk AS number, sis.nama AS name,
                COALESCE(a.status, 'hadir') AS attendance_status, a.catatan AS note,
                COALESCE(p.total_points, 0) AS points
         FROM anggota_kelas ak
         INNER JOIN siswa sis ON sis.id = ak.siswa_id AND sis.is_active = 1
         LEFT JOIN absensi a ON a.sesi_pembelajaran_id = :session_id AND a.siswa_id = sis.id
         LEFT JOIN (
             SELECT sesi_pembelajaran_id, siswa_id, SUM(poin) AS total_points
             FROM nilai_keaktifan WHERE sesi_pembelajaran_id = :points_session_id
             GROUP BY sesi_pembelajaran_id, siswa_id
         ) p ON p.sesi_pembelajaran_id = :joined_session_id AND p.siswa_id = sis.id
         WHERE ak.kelas_id = :class_id AND ak.semester_id = :semester_id
         ORDER BY sis.nama, sis.id"
    );
    $statement->execute([
        'session_id' => $sessionId,
        'points_session_id' => $sessionId,
        'joined_session_id' => $sessionId,
        'class_id' => $session['class_id'],
        'semester_id' => $session['semester_id'],
    ]);

    return $statement->fetchAll();
}

/** Apply the per-student tap cooldown for this authenticated teacher session. */
function guruTapAttendance(int $sessionId, int $studentId): array
{
    if (!isset($_SESSION['_guru_tap_last']) || !is_array($_SESSION['_guru_tap_last'])) {
        $_SESSION['_guru_tap_last'] = [];
    }
    $userId = (int) currentUser()['id'];
    $tapKey = $userId . ':' . $sessionId . ':' . $studentId;
    $now = microtime(true);
    $lastTap = $_SESSION['_guru_tap_last'][$tapKey] ?? null;
    if ((is_float($lastTap) || is_int($lastTap)) && ($now - $lastTap) < 2.0) {
        throw new GuruTapCooldownException('Tunggu 2 detik sebelum mengirim tap lagi untuk siswa ini.');
    }

    $pdo = guruDb();
    $pdo->beginTransaction();
    try {
        $sessionQuery = $pdo->prepare(
            'SELECT pg.kelas_id, pg.semester_id
             FROM sesi_pembelajaran sp
             INNER JOIN penugasan_guru pg ON pg.id = sp.penugasan_guru_id
             INNER JOIN guru g ON g.id = pg.guru_id
             INNER JOIN semester sem ON sem.id = pg.semester_id
             WHERE sp.id = :session_id AND g.user_id = :user_id
               AND sem.status = :active_semester AND sp.status = :ongoing_status
             LIMIT 1 FOR UPDATE'
        );
        $sessionQuery->execute([
            'session_id' => $sessionId,
            'user_id' => $userId,
            'active_semester' => 'aktif',
            'ongoing_status' => 'berlangsung',
        ]);
        $session = $sessionQuery->fetch();
        if (!is_array($session)) {
            throw new DomainException('Sesi tidak ditemukan atau tidak sedang berlangsung.');
        }

        $studentQuery = $pdo->prepare(
            'SELECT sis.id
             FROM anggota_kelas ak
             INNER JOIN siswa sis ON sis.id = ak.siswa_id AND sis.is_active = 1
             WHERE ak.siswa_id = :student_id AND ak.kelas_id = :class_id
               AND ak.semester_id = :semester_id
             LIMIT 1'
        );
        $studentQuery->execute([
            'student_id' => $studentId,
            'class_id' => $session['kelas_id'],
            'semester_id' => $session['semester_id'],
        ]);
        if ($studentQuery->fetchColumn() === false) {
            throw new DomainException('Siswa tidak terdaftar pada kelas sesi ini.');
        }

        $existing = $pdo->prepare(
            'SELECT id FROM absensi WHERE sesi_pembelajaran_id = :session_id AND siswa_id = :student_id LIMIT 1'
        );
        $existing->execute(['session_id' => $sessionId, 'student_id' => $studentId]);
        $attendanceId = $existing->fetchColumn();
        if ($attendanceId === false) {
            $save = $pdo->prepare(
                'INSERT INTO absensi
                    (sesi_pembelajaran_id, siswa_id, status, dicatat_oleh_user_id, waktu_dicatat)
                 VALUES (:session_id, :student_id, :status, :user_id, CURRENT_TIMESTAMP())'
            );
            $save->execute([
                'session_id' => $sessionId, 'student_id' => $studentId,
                'status' => 'hadir', 'user_id' => $userId,
            ]);
        } else {
            $save = $pdo->prepare(
                'UPDATE absensi
                 SET status = :status, dicatat_oleh_user_id = :user_id,
                     waktu_dicatat = CURRENT_TIMESTAMP(), catatan = NULL
                 WHERE id = :attendance_id'
            );
            $save->execute(['status' => 'hadir', 'user_id' => $userId, 'attendance_id' => $attendanceId]);
        }
        $pdo->commit();
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $exception;
    }

    $_SESSION['_guru_tap_last'][$tapKey] = $now;
    return [
        'time' => date('H:i:s'),
        'tap_count' => guruTapCount($sessionId),
    ];
}

/** Return the number of unique students tapped during the current login session. */
function guruTapCount(int $sessionId): int
{
    $prefix = (int) currentUser()['id'] . ':' . $sessionId . ':';
    $tappedStudents = [];
    foreach ($_SESSION['_guru_tap_last'] ?? [] as $key => $_timestamp) {
        if (is_string($key) && str_starts_with($key, $prefix)) {
            $tappedStudents[substr($key, strlen($prefix))] = true;
        }
    }

    return count($tappedStudents);
}

/** Return student IDs tapped by this teacher in the current login session. */
function guruTappedStudentIds(int $sessionId): array
{
    $prefix = (int) currentUser()['id'] . ':' . $sessionId . ':';
    $studentIds = [];
    foreach ($_SESSION['_guru_tap_last'] ?? [] as $key => $_timestamp) {
        if (is_string($key) && str_starts_with($key, $prefix)) {
            $studentId = filter_var(substr($key, strlen($prefix)), FILTER_VALIDATE_INT);
            if ($studentId !== false) {
                $studentIds[] = (int) $studentId;
            }
        }
    }

    return $studentIds;
}

/** Return successful tap times for the current teacher session. */
function guruTappedStudentTimes(int $sessionId): array
{
    $prefix = (int) currentUser()['id'] . ':' . $sessionId . ':';
    $times = [];
    foreach ($_SESSION['_guru_tap_last'] ?? [] as $key => $timestamp) {
        if (is_string($key) && str_starts_with($key, $prefix) && (is_float($timestamp) || is_int($timestamp))) {
            $studentId = filter_var(substr($key, strlen($prefix)), FILTER_VALIDATE_INT);
            if ($studentId !== false) {
                $times[(int) $studentId] = date('H:i:s', (int) $timestamp);
            }
        }
    }

    return $times;
}

/** Add a manual participation score to an owned, ongoing session. */
function guruAddPoints(int $sessionId, int $studentId, string $points, ?string $note): void
{
    if (!preg_match('/^(?:0|[1-9][0-9]{0,4})(?:\.[0-9]{1,2})?$/', $points)) {
        throw new DomainException('Poin harus bernilai 0 sampai 99999.99 dengan maksimal 2 angka desimal.');
    }
    if ($note !== null && (function_exists('mb_strlen') ? mb_strlen($note, 'UTF-8') : strlen($note)) > 500) {
        throw new DomainException('Catatan maksimal 500 karakter.');
    }

    $pdo = guruDb();
    $pdo->beginTransaction();
    try {
        $sessionQuery = $pdo->prepare(
            'SELECT pg.kelas_id, pg.semester_id
             FROM sesi_pembelajaran sp
             INNER JOIN penugasan_guru pg ON pg.id = sp.penugasan_guru_id
             INNER JOIN guru g ON g.id = pg.guru_id
             INNER JOIN semester sem ON sem.id = pg.semester_id
             WHERE sp.id = :session_id AND g.user_id = :user_id
               AND sem.status = :active_semester AND sp.status = :ongoing_status
             LIMIT 1 FOR UPDATE'
        );
        $sessionQuery->execute([
            'session_id' => $sessionId,
            'user_id' => currentUser()['id'],
            'active_semester' => 'aktif',
            'ongoing_status' => 'berlangsung',
        ]);
        $session = $sessionQuery->fetch();
        if (!is_array($session)) {
            throw new DomainException('Poin hanya dapat dicatat pada sesi Guru yang sedang berlangsung.');
        }

        $studentQuery = $pdo->prepare(
            'SELECT sis.id
             FROM anggota_kelas ak
             INNER JOIN siswa sis ON sis.id = ak.siswa_id AND sis.is_active = 1
             WHERE ak.siswa_id = :student_id AND ak.kelas_id = :class_id
               AND ak.semester_id = :semester_id
             LIMIT 1'
        );
        $studentQuery->execute([
            'student_id' => $studentId,
            'class_id' => $session['kelas_id'],
            'semester_id' => $session['semester_id'],
        ]);
        if ($studentQuery->fetchColumn() === false) {
            throw new DomainException('Siswa tidak terdaftar pada kelas sesi ini.');
        }

        $insert = $pdo->prepare(
            'INSERT INTO nilai_keaktifan
                (sesi_pembelajaran_id, siswa_id, poin, dicatat_oleh_user_id, catatan, waktu_dicatat)
             VALUES (:session_id, :student_id, :points, :user_id, :note, CURRENT_TIMESTAMP())'
        );
        $insert->execute([
            'session_id' => $sessionId,
            'student_id' => $studentId,
            'points' => $points,
            'user_id' => currentUser()['id'],
            'note' => $note === '' ? null : $note,
        ]);
        $pdo->commit();
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $exception;
    }
}

/** Fetch score history for one student in an owned session. */
function guruStudentPointHistory(int $sessionId, int $studentId): array
{
    $session = guruOwnedSession($sessionId);
    if ($session === null) {
        throw new DomainException('Sesi tidak ditemukan.');
    }

    $statement = guruDb()->prepare(
        'SELECT nk.poin, nk.waktu_dicatat, nk.catatan, u.username AS recorded_by
         FROM nilai_keaktifan nk
         INNER JOIN users u ON u.id = nk.dicatat_oleh_user_id
         WHERE nk.sesi_pembelajaran_id = :session_id AND nk.siswa_id = :student_id
         ORDER BY nk.waktu_dicatat DESC, nk.id DESC'
    );
    $statement->execute(['session_id' => $sessionId, 'student_id' => $studentId]);

    return $statement->fetchAll();
}

/** Return class and semester options from all of the teacher's assignments. */
function guruArchiveFilters(): array
{
    $classes = guruDb()->prepare(
        'SELECT DISTINCT k.id, k.nama AS name
         FROM penugasan_guru pg
         INNER JOIN guru g ON g.id = pg.guru_id
         INNER JOIN kelas k ON k.id = pg.kelas_id
         WHERE g.user_id = :user_id
         ORDER BY k.nama, k.id'
    );
    $classes->execute(['user_id' => currentUser()['id']]);

    $semesters = guruDb()->prepare(
        'SELECT DISTINCT sem.id, sem.nama AS name, sem.tanggal_mulai
         FROM penugasan_guru pg
         INNER JOIN guru g ON g.id = pg.guru_id
         INNER JOIN semester sem ON sem.id = pg.semester_id
         WHERE g.user_id = :user_id
         ORDER BY sem.tanggal_mulai DESC, sem.id DESC'
    );
    $semesters->execute(['user_id' => currentUser()['id']]);

    return ['classes' => $classes->fetchAll(), 'semesters' => $semesters->fetchAll()];
}

/** Fetch completed sessions for the authenticated teacher with optional filters. */
function guruArchiveSessions(?int $classId = null, ?int $semesterId = null): array
{
    $sql =
        'SELECT sp.id, sp.tanggal, sp.waktu_mulai, sp.waktu_selesai,
                jm.jam_mulai AS scheduled_start, jm.jam_selesai AS scheduled_end,
                k.id AS class_id, k.nama AS class_name,
                sem.id AS semester_id, sem.nama AS semester, sp.status,
                COUNT(DISTINCT ak.siswa_id) AS student_count
         FROM sesi_pembelajaran sp
         INNER JOIN penugasan_guru pg ON pg.id = sp.penugasan_guru_id
         INNER JOIN guru g ON g.id = pg.guru_id
         INNER JOIN kelas k ON k.id = pg.kelas_id
         INNER JOIN semester sem ON sem.id = pg.semester_id
         LEFT JOIN jadwal_mengajar jm ON jm.id = sp.jadwal_mengajar_id
         LEFT JOIN anggota_kelas ak ON ak.kelas_id = pg.kelas_id AND ak.semester_id = pg.semester_id
         WHERE g.user_id = :user_id AND sp.status = :completed_status';
    $parameters = ['user_id' => currentUser()['id'], 'completed_status' => 'selesai'];

    if ($classId !== null) {
        $sql .= ' AND k.id = :class_id';
        $parameters['class_id'] = $classId;
    }
    if ($semesterId !== null) {
        $sql .= ' AND sem.id = :semester_id';
        $parameters['semester_id'] = $semesterId;
    }

    $sql .=
        ' GROUP BY sp.id, sp.tanggal, sp.waktu_mulai, sp.waktu_selesai,
                  jm.jam_mulai, jm.jam_selesai, k.id, k.nama,
                  sem.id, sem.nama, sp.status
          ORDER BY sp.tanggal DESC, sp.id DESC';
    $statement = guruDb()->prepare($sql);
    $statement->execute($parameters);

    return $statement->fetchAll();
}

/** Return one completed session and its historical class roster, attendance and scores. */
function guruArchiveDetail(int $sessionId): ?array
{
    $session = guruOwnedSession($sessionId);
    if ($session === null || $session['status'] !== 'selesai') {
        return null;
    }

    $statement = guruDb()->prepare(
        "SELECT sis.id, sis.nomor_induk AS number, sis.nama AS name,
                COALESCE(a.status, 'hadir') AS attendance_status,
                COALESCE(p.total_points, 0) AS points
         FROM anggota_kelas ak
         INNER JOIN siswa sis ON sis.id = ak.siswa_id
         LEFT JOIN absensi a ON a.sesi_pembelajaran_id = :session_id AND a.siswa_id = sis.id
         LEFT JOIN (
             SELECT sesi_pembelajaran_id, siswa_id, SUM(poin) AS total_points
             FROM nilai_keaktifan WHERE sesi_pembelajaran_id = :points_session_id
             GROUP BY sesi_pembelajaran_id, siswa_id
         ) p ON p.sesi_pembelajaran_id = :joined_session_id AND p.siswa_id = sis.id
         WHERE ak.kelas_id = :class_id AND ak.semester_id = :semester_id
         ORDER BY sis.nama, sis.id"
    );
    $statement->execute([
        'session_id' => $sessionId,
        'points_session_id' => $sessionId,
        'joined_session_id' => $sessionId,
        'class_id' => $session['class_id'],
        'semester_id' => $session['semester_id'],
    ]);
    $session['students'] = $statement->fetchAll();

    return $session;
}

/** Fetch semester-scoped groups for one active class assignment. */
function guruGroupsForClass(int $guruId, int $classId, int $semesterId): array
{
    $statement = guruDb()->prepare(
        'SELECT kg.id, kg.nama AS name, COUNT(ak.siswa_id) AS member_count
         FROM kelompok kg
         INNER JOIN penugasan_guru pg ON pg.kelas_id = kg.kelas_id AND pg.semester_id = kg.semester_id
         LEFT JOIN anggota_kelompok ak ON ak.kelompok_id = kg.id
         WHERE pg.guru_id = :guru_id AND pg.kelas_id = :class_id AND pg.semester_id = :semester_id
         GROUP BY kg.id, kg.nama
         ORDER BY kg.nama, kg.id'
    );
    $statement->execute(['guru_id' => $guruId, 'class_id' => $classId, 'semester_id' => $semesterId]);

    return $statement->fetchAll();
}

/** Fetch current eligible students and whether each belongs to a specific group. */
function guruGroupStudents(int $classId, int $semesterId, ?int $groupId = null): array
{
    $statement = guruDb()->prepare(
        'SELECT sis.id, sis.nomor_induk AS number, sis.nama AS name,
                CASE WHEN ag.kelompok_id IS NULL THEN 0 ELSE 1 END AS is_member
         FROM anggota_kelas ak
         INNER JOIN siswa sis ON sis.id = ak.siswa_id AND sis.is_active = 1
         LEFT JOIN anggota_kelompok ag ON ag.siswa_id = sis.id AND ag.kelompok_id = :group_id
         WHERE ak.kelas_id = :class_id AND ak.semester_id = :semester_id
         ORDER BY sis.nama, sis.id'
    );
    $statement->execute(['group_id' => $groupId, 'class_id' => $classId, 'semester_id' => $semesterId]);

    return $statement->fetchAll();
}

/** Create semester-scoped groups after validating a complete class roster partition. */
function guruCreateGroups(int $classId, string $namePrefix, array $memberGroups): void
{
    $namePrefix = trim($namePrefix);
    if ($namePrefix === '' || (function_exists('mb_strlen') ? mb_strlen($namePrefix, 'UTF-8') : strlen($namePrefix)) > 80) {
        throw new DomainException('Nama kelompok wajib diisi dan maksimal 80 karakter.');
    }
    if ($memberGroups === [] || count($memberGroups) > 20) {
        throw new DomainException('Jumlah kelompok harus antara 1 sampai 20.');
    }

    $profile = guruProfile();
    $semester = guruActiveSemester();
    if ($profile === null || $semester === null) {
        throw new DomainException('Profil Guru atau semester aktif tidak tersedia.');
    }

    $pdo = guruDb();
    $pdo->beginTransaction();
    try {
        $assignment = $pdo->prepare(
            'SELECT pg.id
             FROM penugasan_guru pg
             INNER JOIN kelas k ON k.id = pg.kelas_id AND k.is_active = 1
             INNER JOIN semester sem ON sem.id = pg.semester_id AND sem.status = :active_status
             WHERE pg.guru_id = :guru_id AND pg.kelas_id = :class_id AND pg.semester_id = :semester_id
             LIMIT 1 FOR UPDATE'
        );
        $assignment->execute([
            'active_status' => 'aktif', 'guru_id' => $profile['id'],
            'class_id' => $classId, 'semester_id' => $semester['id'],
        ]);
        if ($assignment->fetchColumn() === false) {
            throw new DomainException('Kelas tidak ditugaskan kepada Anda pada semester aktif.');
        }

        $rosterQuery = $pdo->prepare(
            'SELECT ak.siswa_id
             FROM anggota_kelas ak
             INNER JOIN siswa sis ON sis.id = ak.siswa_id AND sis.is_active = 1
             WHERE ak.kelas_id = :class_id AND ak.semester_id = :semester_id'
        );
        $rosterQuery->execute(['class_id' => $classId, 'semester_id' => $semester['id']]);
        $roster = array_map('intval', $rosterQuery->fetchAll(PDO::FETCH_COLUMN));
        $submitted = [];
        foreach ($memberGroups as $members) {
            if (!is_array($members) || $members === []) {
                throw new DomainException('Setiap kelompok harus memiliki anggota.');
            }
            foreach ($members as $studentId) {
                if (!is_int($studentId) || !in_array($studentId, $roster, true) || isset($submitted[$studentId])) {
                    throw new DomainException('Anggota kelompok tidak sesuai dengan siswa kelas aktif.');
                }
                $submitted[$studentId] = true;
            }
        }
        sort($roster);
        $submittedIds = array_map('intval', array_keys($submitted));
        sort($submittedIds);
        if ($roster === [] || $submittedIds !== $roster) {
            throw new DomainException('Semua siswa aktif harus masuk tepat ke satu kelompok.');
        }

        $insertGroup = $pdo->prepare(
            'INSERT INTO kelompok (kelas_id, semester_id, nama) VALUES (:class_id, :semester_id, :name)'
        );
        $insertMember = $pdo->prepare(
            'INSERT INTO anggota_kelompok (kelompok_id, siswa_id) VALUES (:group_id, :student_id)'
        );
        foreach (array_values($memberGroups) as $index => $members) {
            $groupName = $namePrefix . ' ' . ($index + 1);
            if ((function_exists('mb_strlen') ? mb_strlen($groupName, 'UTF-8') : strlen($groupName)) > 100) {
                throw new DomainException('Nama kelompok melebihi batas database.');
            }
            $insertGroup->execute(['class_id' => $classId, 'semester_id' => $semester['id'], 'name' => $groupName]);
            $groupId = (int) $pdo->lastInsertId();
            foreach ($members as $studentId) {
                $insertMember->execute(['group_id' => $groupId, 'student_id' => $studentId]);
            }
        }

        $pdo->commit();
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $exception;
    }
}

/** Return an editable group only when it belongs to the teacher's active semester class. */
function guruOwnedActiveGroup(int $groupId): ?array
{
    $profile = guruProfile();
    $semester = guruActiveSemester();
    if ($profile === null || $semester === null) {
        return null;
    }

    $statement = guruDb()->prepare(
        'SELECT kg.id, kg.kelas_id AS class_id, kg.semester_id,
                kg.nama AS name, k.nama AS class_name, sem.nama AS semester
         FROM kelompok kg
         INNER JOIN penugasan_guru pg ON pg.kelas_id = kg.kelas_id AND pg.semester_id = kg.semester_id
         INNER JOIN kelas k ON k.id = kg.kelas_id
         INNER JOIN semester sem ON sem.id = kg.semester_id AND sem.status = :active_status
         WHERE kg.id = :group_id AND pg.guru_id = :guru_id
           AND kg.semester_id = :semester_id
         LIMIT 1'
    );
    $statement->execute([
        'active_status' => 'aktif', 'group_id' => $groupId,
        'guru_id' => $profile['id'], 'semester_id' => $semester['id'],
    ]);
    $group = $statement->fetch();

    return is_array($group) ? $group : null;
}

/** Update a group name and member set transactionally in the active semester. */
function guruUpdateGroup(int $groupId, string $name, array $studentIds): void
{
    $name = trim($name);
    if ($name === '' || (function_exists('mb_strlen') ? mb_strlen($name, 'UTF-8') : strlen($name)) > 100) {
        throw new DomainException('Nama kelompok wajib diisi dan maksimal 100 karakter.');
    }
    if ($studentIds === [] || count(array_unique($studentIds)) !== count($studentIds)) {
        throw new DomainException('Pilih satu atau lebih siswa tanpa duplikasi.');
    }

    $profile = guruProfile();
    $semester = guruActiveSemester();
    if ($profile === null || $semester === null) {
        throw new DomainException('Profil Guru atau semester aktif tidak tersedia.');
    }

    $pdo = guruDb();
    $pdo->beginTransaction();
    try {
        $groupQuery = $pdo->prepare(
            'SELECT kg.id, kg.kelas_id, kg.semester_id
             FROM kelompok kg
             INNER JOIN penugasan_guru pg ON pg.kelas_id = kg.kelas_id AND pg.semester_id = kg.semester_id
             INNER JOIN semester sem ON sem.id = kg.semester_id AND sem.status = :active_status
             WHERE kg.id = :group_id AND pg.guru_id = :guru_id AND kg.semester_id = :semester_id
             LIMIT 1 FOR UPDATE'
        );
        $groupQuery->execute([
            'active_status' => 'aktif', 'group_id' => $groupId,
            'guru_id' => $profile['id'], 'semester_id' => $semester['id'],
        ]);
        $group = $groupQuery->fetch();
        if (!is_array($group)) {
            throw new DomainException('Kelompok tidak ditemukan atau semester tidak aktif.');
        }

        $rosterQuery = $pdo->prepare(
            'SELECT ak.siswa_id
             FROM anggota_kelas ak
             INNER JOIN siswa sis ON sis.id = ak.siswa_id AND sis.is_active = 1
             WHERE ak.kelas_id = :class_id AND ak.semester_id = :semester_id'
        );
        $rosterQuery->execute(['class_id' => $group['kelas_id'], 'semester_id' => $group['semester_id']]);
        $roster = array_map('intval', $rosterQuery->fetchAll(PDO::FETCH_COLUMN));
        foreach ($studentIds as $studentId) {
            if (!is_int($studentId) || !in_array($studentId, $roster, true)) {
                throw new DomainException('Anggota kelompok harus siswa aktif di kelas dan semester ini.');
            }
        }

        $update = $pdo->prepare('UPDATE kelompok SET nama = :name WHERE id = :group_id');
        $update->execute(['name' => $name, 'group_id' => $groupId]);
        $deleteMembers = $pdo->prepare('DELETE FROM anggota_kelompok WHERE kelompok_id = :group_id');
        $deleteMembers->execute(['group_id' => $groupId]);
        $insertMember = $pdo->prepare(
            'INSERT INTO anggota_kelompok (kelompok_id, siswa_id) VALUES (:group_id, :student_id)'
        );
        foreach ($studentIds as $studentId) {
            $insertMember->execute(['group_id' => $groupId, 'student_id' => $studentId]);
        }
        $pdo->commit();
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $exception;
    }
}

/** Save an attendance status for an enrolled student during an active semester. */
function guruSaveAttendance(int $sessionId, int $studentId, string $status, ?string $note): void
{
    if (!in_array($status, ['hadir', 'izin', 'alpha', 'sakit'], true)) {
        throw new DomainException('Status kehadiran tidak valid.');
    }
    if ($note !== null && (function_exists('mb_strlen') ? mb_strlen($note, 'UTF-8') : strlen($note)) > 500) {
        throw new DomainException('Catatan maksimal 500 karakter.');
    }

    $pdo = guruDb();
    $pdo->beginTransaction();
    try {
        $sessionQuery = $pdo->prepare(
            'SELECT sp.id, pg.kelas_id, pg.semester_id
             FROM sesi_pembelajaran sp
             INNER JOIN penugasan_guru pg ON pg.id = sp.penugasan_guru_id
             INNER JOIN guru g ON g.id = pg.guru_id
             INNER JOIN semester sem ON sem.id = pg.semester_id
             WHERE sp.id = :session_id AND g.user_id = :user_id AND sem.status = :active_status
             LIMIT 1 FOR UPDATE'
        );
        $sessionQuery->execute([
            'session_id' => $sessionId,
            'user_id' => currentUser()['id'],
            'active_status' => 'aktif',
        ]);
        $session = $sessionQuery->fetch();
        if (!is_array($session)) {
            throw new DomainException('Sesi tidak ditemukan atau semesternya tidak aktif.');
        }

        $studentQuery = $pdo->prepare(
            'SELECT sis.id
             FROM anggota_kelas ak
             INNER JOIN siswa sis ON sis.id = ak.siswa_id AND sis.is_active = 1
             WHERE ak.siswa_id = :student_id AND ak.kelas_id = :class_id
               AND ak.semester_id = :semester_id
             LIMIT 1'
        );
        $studentQuery->execute([
            'student_id' => $studentId,
            'class_id' => $session['kelas_id'],
            'semester_id' => $session['semester_id'],
        ]);
        if ($studentQuery->fetchColumn() === false) {
            throw new DomainException('Siswa tidak terdaftar pada kelas sesi ini.');
        }

        $existing = $pdo->prepare(
            'SELECT id FROM absensi WHERE sesi_pembelajaran_id = :session_id AND siswa_id = :student_id LIMIT 1'
        );
        $existing->execute(['session_id' => $sessionId, 'student_id' => $studentId]);
        $attendanceId = $existing->fetchColumn();
        if ($attendanceId === false) {
            $save = $pdo->prepare(
                'INSERT INTO absensi
                    (sesi_pembelajaran_id, siswa_id, status, dicatat_oleh_user_id, catatan, waktu_dicatat)
                 VALUES (:session_id, :student_id, :status, :user_id, :note, CURRENT_TIMESTAMP())'
            );
        } else {
            $save = $pdo->prepare(
                'UPDATE absensi
                 SET status = :status, dicatat_oleh_user_id = :user_id,
                     catatan = :note, waktu_dicatat = CURRENT_TIMESTAMP()
                 WHERE id = :attendance_id'
            );
        }
        $values = [
            'session_id' => $sessionId,
            'student_id' => $studentId,
            'status' => $status,
            'user_id' => currentUser()['id'],
            'note' => $note === '' ? null : $note,
        ];
        if ($attendanceId !== false) {
            unset($values['session_id'], $values['student_id']);
            $values['attendance_id'] = $attendanceId;
        }
        $save->execute($values);
        $pdo->commit();
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $exception;
    }
}

/** Change an owned, ongoing session to a terminal state. */
function guruEndSession(int $sessionId, string $status): void
{
    if (!in_array($status, ['selesai', 'dibatalkan'], true)) {
        throw new InvalidArgumentException('Status sesi tidak valid.');
    }

    $pdo = guruDb();
    $pdo->beginTransaction();
    try {
        $statement = $pdo->prepare(
            "SELECT sp.id
             FROM sesi_pembelajaran sp
             INNER JOIN penugasan_guru pg ON pg.id = sp.penugasan_guru_id
             INNER JOIN guru g ON g.id = pg.guru_id
             INNER JOIN semester sem ON sem.id = pg.semester_id
             WHERE sp.id = :session_id AND g.user_id = :user_id
               AND sem.status = :active_semester AND sp.status = :ongoing_status
             LIMIT 1 FOR UPDATE"
        );
        $statement->execute([
            'session_id' => $sessionId,
            'user_id' => currentUser()['id'],
            'active_semester' => 'aktif',
            'ongoing_status' => 'berlangsung',
        ]);
        if ($statement->fetchColumn() === false) {
            throw new DomainException('Sesi tidak ditemukan atau tidak dapat diubah.');
        }

        $update = $pdo->prepare(
            'UPDATE sesi_pembelajaran
             SET status = :status, waktu_selesai = CURRENT_TIMESTAMP()
             WHERE id = :session_id AND status = :ongoing_status'
        );
        $update->execute(['status' => $status, 'session_id' => $sessionId, 'ongoing_status' => 'berlangsung']);
        $pdo->commit();
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $exception;
    }
}

/** Return a generic response for backend data loading failures. */
function guruReportBackendError(Throwable $exception): never
{
    error_log('ATTENDARA Guru page failed: ' . $exception->getMessage());
    http_response_code(500);
    exit('Data halaman Guru sementara tidak dapat dimuat.');
}
