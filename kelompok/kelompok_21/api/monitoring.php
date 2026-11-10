<?php

require_once __DIR__ . '/bootstrap.php';

const MON_TEPAT       = 'Tepat waktu';
const MON_TERLAMBAT   = 'Terlambat';
const MON_BELUM       = 'Belum mengumpulkan';
const MON_BELUM_TEMPO = 'Belum jatuh tempo';
const MON_KOSONG      = 'Belum dikumpulkan'; // tulisan yang sama dengan modul Rio

$idGuru   = (int)$_SESSION['guru']['id_guru'];
$aksi     = $_GET['aksi'] ?? '';
// Jam diambil dari MySQL supaya satu zona waktu dengan data tenggat.
$sekarang = strtotime($pdo->query('SELECT NOW()')->fetchColumn());

/* ---------- fungsi bantu ---------- */

function monStatus(?string $waktu, ?string $tenggat, int $sekarang): string
{
    $adaWaktu = $waktu !== null && $waktu !== '';
    if (!$tenggat) return $adaWaktu ? MON_TEPAT : MON_BELUM_TEMPO;
    if ($adaWaktu) return strtotime($waktu) <= strtotime($tenggat) ? MON_TEPAT : MON_TERLAMBAT;
    return $sekarang > strtotime($tenggat) ? MON_BELUM : MON_BELUM_TEMPO;
}

// 0-100. Tepat waktu 100, terlambat 50, belum mengumpulkan 0. Belum jatuh tempo tidak dihitung.
function monIndeks(array $daftarStatus): ?float
{
    $skor = 0; $jumlah = 0;
    foreach ($daftarStatus as $s) {
        if ($s === MON_TEPAT)          { $skor += 100; $jumlah++; }
        elseif ($s === MON_TERLAMBAT)  { $skor += 50;  $jumlah++; }
        elseif ($s === MON_BELUM)      { $jumlah++; }
    }
    return $jumlah ? round($skor / $jumlah, 2) : null;
}

function monTabelRiwayat(PDO $pdo): void
{
    $pdo->exec('CREATE TABLE IF NOT EXISTS riwayat_pengumpulan (
        id_riwayat   INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
        id_penilaian INT NOT NULL,
        id_siswa     INT NOT NULL,
        id_guru      INT NOT NULL,
        status_lama  VARCHAR(50) NULL,
        status_baru  VARCHAR(50) NULL,
        waktu_lama   DATETIME NULL,
        waktu_baru   DATETIME NULL,
        waktu_ubah   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        KEY idx_riwayat (id_penilaian, id_siswa)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
}

function monPenugasan(PDO $pdo, int $id): array
{
    $stmt = $pdo->prepare('SELECT p.id_penugasan, p.id_guru, p.id_kelas, k.nama_kelas, m.nama_mapel
        FROM PENUGASAN_MENGAJAR p
        JOIN KELAS k ON k.id_kelas = p.id_kelas
        JOIN MATA_PELAJARAN m ON m.id_mapel = p.id_mapel
        WHERE p.id_penugasan = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if (!$row) respond(null, 'Penugasan tidak ditemukan.', 404);
    requireClass($pdo, (int)$row['id_kelas']);
    return $row;
}

function monTugas(PDO $pdo, int $idPenilaian): array
{
    $stmt = $pdo->prepare('SELECT n.id_penilaian, n.id_penugasan, n.judul_penilaian, n.jenis_komponen,
            n.tenggat_waktu, p.id_guru, p.id_kelas
        FROM PENILAIAN n JOIN PENUGASAN_MENGAJAR p ON p.id_penugasan = n.id_penugasan
        WHERE n.id_penilaian = ?');
    $stmt->execute([$idPenilaian]);
    $row = $stmt->fetch();
    if (!$row) respond(null, 'Tugas tidak ditemukan.', 404);
    if (strtolower($row['jenis_komponen']) !== 'tugas') respond(null, 'Penilaian ini bukan tugas.', 422);
    requireClass($pdo, (int)$row['id_kelas']);
    return $row;
}

function monBolehUbah(array $row, int $idGuru): bool
{
    return isAdmin() || (int)$row['id_guru'] === $idGuru;
}

/* ---------- GET: penugasan milik guru ---------- */
if ($method === 'GET' && $aksi === 'penugasan') {
    $sql = 'SELECT p.id_penugasan, p.id_guru, k.nama_kelas, m.nama_mapel, pr.tahun_ajaran, pr.semester
        FROM PENUGASAN_MENGAJAR p
        JOIN KELAS k ON k.id_kelas = p.id_kelas
        JOIN MATA_PELAJARAN m ON m.id_mapel = p.id_mapel
        JOIN PERIODE_PENILAIAN pr ON pr.id_periode = p.id_periode';
    if (isAdmin()) {
        $stmt = $pdo->query($sql . ' ORDER BY k.nama_kelas, m.nama_mapel');
    } else {
        $stmt = $pdo->prepare($sql . ' WHERE p.id_guru = ? OR k.id_wali_kelas = ? ORDER BY k.nama_kelas, m.nama_mapel');
        $stmt->execute([$idGuru, $idGuru]);
    }
    $hasil = $stmt->fetchAll();
    foreach ($hasil as &$h) $h['bisa_ubah'] = monBolehUbah($h, $idGuru);
    respond($hasil);
}

/* ---------- GET: daftar tugas dalam satu penugasan ---------- */
if ($method === 'GET' && $aksi === 'tugas') {
    $p = monPenugasan($pdo, positiveId($_GET['id_penugasan'] ?? 0));
    $stmt = $pdo->prepare("SELECT id_penilaian, judul_penilaian, tenggat_waktu FROM PENILAIAN
        WHERE id_penugasan = ? AND jenis_komponen = 'Tugas' ORDER BY tenggat_waktu ASC, id_penilaian ASC");
    $stmt->execute([$p['id_penugasan']]);
    respond($stmt->fetchAll());
}

/* ---------- GET: status tiap siswa untuk satu tugas ---------- */
if ($method === 'GET' && $aksi === 'daftar') {
    $t = monTugas($pdo, positiveId($_GET['id_penilaian'] ?? 0));

    $stmt = $pdo->prepare('SELECT id_siswa, nisn, nama_siswa FROM SISWA WHERE id_kelas = ? ORDER BY nama_siswa');
    $stmt->execute([$t['id_kelas']]);
    $siswa = $stmt->fetchAll();

    $stmt = $pdo->prepare("SELECT id_penilaian, tenggat_waktu FROM PENILAIAN
        WHERE id_penugasan = ? AND jenis_komponen = 'Tugas'");
    $stmt->execute([$t['id_penugasan']]);
    $semuaTugas = $stmt->fetchAll();

    $peta = []; // $peta[id_siswa][id_penilaian] = waktu
    if ($semuaTugas) {
        $ids = array_column($semuaTugas, 'id_penilaian');
        $in  = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare("SELECT id_siswa, id_penilaian, waktu_pengumpulan
            FROM NILAI_DAN_PENGUMPULAN WHERE id_penilaian IN ($in)");
        $stmt->execute($ids);
        foreach ($stmt->fetchAll() as $r) $peta[$r['id_siswa']][$r['id_penilaian']] = $r['waktu_pengumpulan'];
    }

    $hasil = [];
    $ringkas = [MON_TEPAT => 0, MON_TERLAMBAT => 0, MON_BELUM => 0, MON_BELUM_TEMPO => 0];
    foreach ($siswa as $s) {
        $waktu  = $peta[$s['id_siswa']][$t['id_penilaian']] ?? null;
        $status = monStatus($waktu, $t['tenggat_waktu'], $sekarang);
        $ringkas[$status]++;

        $semuaStatus = [];
        foreach ($semuaTugas as $tg) {
            $semuaStatus[] = monStatus($peta[$s['id_siswa']][$tg['id_penilaian']] ?? null, $tg['tenggat_waktu'], $sekarang);
        }
        $hasil[] = [
            'id_siswa' => (int)$s['id_siswa'], 'nisn' => $s['nisn'], 'nama_siswa' => $s['nama_siswa'],
            'waktu_pengumpulan' => $waktu, 'status' => $status, 'indeks' => monIndeks($semuaStatus),
        ];
    }
    respond([
        'tugas' => ['id_penilaian' => (int)$t['id_penilaian'], 'judul' => $t['judul_penilaian'], 'tenggat' => $t['tenggat_waktu']],
        'bisa_ubah' => monBolehUbah($t, $idGuru),
        'ringkasan' => $ringkas,
        'siswa' => $hasil,
    ]);
}

/* ---------- GET: riwayat satu siswa pada satu tugas ---------- */
if ($method === 'GET' && $aksi === 'riwayat') {
    $t = monTugas($pdo, positiveId($_GET['id_penilaian'] ?? 0));
    $idSiswa = positiveId($_GET['id_siswa'] ?? 0);
    monTabelRiwayat($pdo);
    $stmt = $pdo->prepare('SELECT r.status_lama, r.status_baru, r.waktu_lama, r.waktu_baru, r.waktu_ubah, g.nama_guru
        FROM riwayat_pengumpulan r LEFT JOIN GURU g ON g.id_guru = r.id_guru
        WHERE r.id_penilaian = ? AND r.id_siswa = ? ORDER BY r.id_riwayat DESC LIMIT 50');
    $stmt->execute([$t['id_penilaian'], $idSiswa]);
    respond($stmt->fetchAll());
}

/* ---------- POST: catat atau kosongkan waktu pengumpulan ---------- */
if ($method === 'POST' && $aksi === 'catat') {
    $input   = inputData();
    $t       = monTugas($pdo, positiveId($input['id_penilaian'] ?? 0));
    $idSiswa = positiveId($input['id_siswa'] ?? 0);
    if (!monBolehUbah($t, $idGuru)) respond(null, 'Hanya guru pengampu atau admin yang boleh mengubah.', 403);

    $stmt = $pdo->prepare('SELECT id_siswa FROM SISWA WHERE id_siswa = ? AND id_kelas = ?');
    $stmt->execute([$idSiswa, $t['id_kelas']]);
    if (!$stmt->fetch()) respond(null, 'Siswa bukan anggota kelas ini.', 422);

    $mentah = trim((string)($input['waktu_pengumpulan'] ?? ''));
    $waktuBaru = null;
    if ($mentah !== '') {
        $d = date_create($mentah);
        if (!$d) respond(null, 'Format waktu tidak valid.', 422);
        $waktuBaru = $d->format('Y-m-d H:i:s');
    }
    $statusBaru = $waktuBaru === null ? MON_KOSONG : monStatus($waktuBaru, $t['tenggat_waktu'], $sekarang);

    $stmt = $pdo->prepare('SELECT waktu_pengumpulan, status_pengumpulan FROM NILAI_DAN_PENGUMPULAN
        WHERE id_siswa = ? AND id_penilaian = ?');
    $stmt->execute([$idSiswa, $t['id_penilaian']]);
    $lama = $stmt->fetch();

    // Hanya dua kolom milik modul ini yang diubah. Kolom nilai (milik Rio) tidak disentuh.
    if ($lama) {
        $stmt = $pdo->prepare('UPDATE NILAI_DAN_PENGUMPULAN SET waktu_pengumpulan = ?, status_pengumpulan = ?
            WHERE id_siswa = ? AND id_penilaian = ?');
        $stmt->execute([$waktuBaru, $statusBaru, $idSiswa, $t['id_penilaian']]);
    } else {
        $stmt = $pdo->prepare('INSERT INTO NILAI_DAN_PENGUMPULAN (id_siswa, id_penilaian, waktu_pengumpulan, status_pengumpulan)
            VALUES (?, ?, ?, ?)');
        $stmt->execute([$idSiswa, $t['id_penilaian'], $waktuBaru, $statusBaru]);
    }

    monTabelRiwayat($pdo);
    $stmt = $pdo->prepare('INSERT INTO riwayat_pengumpulan
        (id_penilaian, id_siswa, id_guru, status_lama, status_baru, waktu_lama, waktu_baru) VALUES (?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([$t['id_penilaian'], $idSiswa, $idGuru,
        $lama['status_pengumpulan'] ?? null, $statusBaru, $lama['waktu_pengumpulan'] ?? null, $waktuBaru]);

    respond(['status' => monStatus($waktuBaru, $t['tenggat_waktu'], $sekarang)], 'Pengumpulan dicatat.');
}

respond(null, 'Aksi tidak dikenal.', 404);