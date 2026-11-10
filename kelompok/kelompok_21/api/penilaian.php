<?php
require __DIR__ . '/bootstrap.php';

$idGuru = $_SESSION['guru']['id_guru'];
$aksi = $_GET['aksi'] ?? '';
$jenisValid = ['Tugas', 'Kuis', 'UTS', 'UAS'];

// Ambil penugasan beserta pengecekan hak akses (admin: semua, guru: miliknya).
function ambilPenugasan(PDO $pdo, $id) {
    $id = positiveId($id);
    $stmt = $pdo->prepare('SELECT * FROM PENUGASAN_MENGAJAR WHERE id_penugasan = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if (!$row) respond(null, 'Penugasan tidak ditemukan.', 404);
    if (!isAdmin() && (int)$row['id_guru'] !== (int)$_SESSION['guru']['id_guru']) {
        respond(null, 'Anda tidak memiliki akses ke penugasan ini.', 403);
    }
    return $row;
}

// Ambil penilaian beserta pengecekan akses lewat penugasannya.
function ambilPenilaian(PDO $pdo, $id) {
    $id = positiveId($id);
    $stmt = $pdo->prepare('SELECT * FROM PENILAIAN WHERE id_penilaian = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if (!$row) respond(null, 'Penilaian tidak ditemukan.', 404);
    $row['penugasan'] = ambilPenugasan($pdo, $row['id_penugasan']);
    return $row;
}

// Validasi nilai wajib angka 0-100; kembalikan null jika kosong.
function validasiNilai($value) {
    $value = trim((string)$value);
    if ($value === '') return null;
    $value = str_replace(',', '.', $value);
    if (!is_numeric($value) || (float)$value < 0 || (float)$value > 100) {
        respond(null, 'Nilai harus berupa angka antara 0 sampai 100.', 422);
    }
    return round((float)$value, 2);
}

/* ---------------------------- GET ---------------------------- */
if ($method === 'GET') {
    // Daftar penugasan yang boleh dipilih (kelas + mapel + periode).
    if ($aksi === 'konteks') {
        $sql = 'SELECT pm.id_penugasan, k.nama_kelas, m.nama_mapel,
                    pr.tahun_ajaran, pr.semester, pr.status_aktif
                FROM PENUGASAN_MENGAJAR pm
                JOIN KELAS k ON k.id_kelas = pm.id_kelas
                JOIN MATA_PELAJARAN m ON m.id_mapel = pm.id_mapel
                JOIN PERIODE_PENILAIAN pr ON pr.id_periode = pm.id_periode';
        $params = [];
        if (!isAdmin()) { $sql .= ' WHERE pm.id_guru = ?'; $params[] = $idGuru; }
        $sql .= ' ORDER BY pr.status_aktif DESC, k.nama_kelas, m.nama_mapel';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        respond($stmt->fetchAll());
    }

    // Daftar penilaian dalam satu penugasan + progres pengisian nilai.
    if ($aksi === 'daftar') {
        $pm = ambilPenugasan($pdo, $_GET['id_penugasan'] ?? 0);
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM SISWA WHERE id_kelas = ?');
        $stmt->execute([$pm['id_kelas']]);
        $jumlahSiswa = (int)$stmt->fetchColumn();

        $stmt = $pdo->prepare('SELECT p.id_penilaian, p.judul_penilaian, p.jenis_komponen,
                p.tanggal_dibuat, COUNT(n.nilai) AS terisi, AVG(n.nilai) AS rata_rata
            FROM PENILAIAN p
            LEFT JOIN NILAI_DAN_PENGUMPULAN n ON n.id_penilaian = p.id_penilaian
            WHERE p.id_penugasan = ?
            GROUP BY p.id_penilaian
            ORDER BY p.id_penilaian DESC');
        $stmt->execute([$pm['id_penugasan']]);
        $rows = $stmt->fetchAll();
        foreach ($rows as &$r) {
            $r['jumlah_siswa'] = $jumlahSiswa;
            $r['rata_rata'] = $r['rata_rata'] === null ? null : round((float)$r['rata_rata'], 2);
        }
        respond($rows);
    }

    // Seluruh siswa satu kelas beserta nilainya pada satu penilaian.
    if ($aksi === 'nilai') {
        $p = ambilPenilaian($pdo, $_GET['id_penilaian'] ?? 0);
        $stmt = $pdo->prepare('SELECT s.id_siswa, s.nisn, s.nama_siswa, n.nilai
            FROM SISWA s
            LEFT JOIN NILAI_DAN_PENGUMPULAN n
                ON n.id_siswa = s.id_siswa AND n.id_penilaian = ?
            WHERE s.id_kelas = ?
            ORDER BY s.nama_siswa');
        $stmt->execute([$p['id_penilaian'], $p['penugasan']['id_kelas']]);
        respond(['penilaian' => [
            'id_penilaian' => $p['id_penilaian'],
            'judul_penilaian' => $p['judul_penilaian'],
            'jenis_komponen' => $p['jenis_komponen']
        ], 'siswa' => $stmt->fetchAll()]);
    }

    // Rekap rata-rata nilai per komponen untuk setiap siswa.
    if ($aksi === 'rekap') {
        $pm = ambilPenugasan($pdo, $_GET['id_penugasan'] ?? 0);
        $stmt = $pdo->prepare('SELECT id_siswa, nisn, nama_siswa FROM SISWA
            WHERE id_kelas = ? ORDER BY nama_siswa');
        $stmt->execute([$pm['id_kelas']]);
        $siswa = $stmt->fetchAll();

        $stmt = $pdo->prepare('SELECT n.id_siswa, p.jenis_komponen, AVG(n.nilai) AS rata
            FROM NILAI_DAN_PENGUMPULAN n
            JOIN PENILAIAN p ON p.id_penilaian = n.id_penilaian
            WHERE p.id_penugasan = ? AND n.nilai IS NOT NULL
            GROUP BY n.id_siswa, p.jenis_komponen');
        $stmt->execute([$pm['id_penugasan']]);
        $peta = [];
        foreach ($stmt->fetchAll() as $r) {
            $peta[$r['id_siswa']][$r['jenis_komponen']] = round((float)$r['rata'], 2);
        }
        foreach ($siswa as &$s) {
            foreach ($jenisValid as $j) $s[$j] = $peta[$s['id_siswa']][$j] ?? null;
        }
        respond($siswa);
    }

    respond(null, 'Aksi tidak dikenal.', 404);
}

/* ------------------- POST: simpan nilai satu kelas ------------------- */
// Body JSON datar: {"s_<id_siswa>": "85", "s_<id_siswa>": "", ...}
if ($method === 'POST' && $aksi === 'simpan_nilai') {
    $p = ambilPenilaian($pdo, $_GET['id_penilaian'] ?? 0);
    $input = inputData();

    $stmt = $pdo->prepare('SELECT id_siswa FROM SISWA WHERE id_kelas = ?');
    $stmt->execute([$p['penugasan']['id_kelas']]);
    $siswaKelas = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));

    // Validasi semua dulu sebelum menyimpan apa pun.
    $data = [];
    foreach ($input as $key => $value) {
        if (!preg_match('/^s_(\d+)$/', (string)$key, $m)) continue;
        $idSiswa = (int)$m[1];
        if (!in_array($idSiswa, $siswaKelas, true)) {
            respond(null, 'Siswa tidak terdaftar pada kelas ini.', 422);
        }
        $data[$idSiswa] = validasiNilai($value);
    }

    $pdo->beginTransaction();
    $find = $pdo->prepare('SELECT id_nilai_pengumpulan FROM NILAI_DAN_PENGUMPULAN
        WHERE id_siswa = ? AND id_penilaian = ?');
    $upd = $pdo->prepare('UPDATE NILAI_DAN_PENGUMPULAN SET nilai = ? WHERE id_nilai_pengumpulan = ?');
    $ins = $pdo->prepare('INSERT INTO NILAI_DAN_PENGUMPULAN (id_siswa, id_penilaian, nilai) VALUES (?, ?, ?)');
    $tersimpan = 0;
    foreach ($data as $idSiswa => $nilai) {
        $find->execute([$idSiswa, $p['id_penilaian']]);
        $id = $find->fetchColumn();
        if ($id) { $upd->execute([$nilai, $id]); $tersimpan++; }
        elseif ($nilai !== null) { $ins->execute([$idSiswa, $p['id_penilaian'], $nilai]); $tersimpan++; }
    }
    $pdo->commit();
    respond(['tersimpan' => $tersimpan], 'Nilai berhasil disimpan.');
}

/* ---------------- POST / PUT / DELETE: CRUD penilaian ---------------- */
if (!in_array($method, ['POST', 'PUT', 'DELETE'])) respond(null, 'Metode tidak didukung.', 405);

if ($method === 'DELETE') {
    $p = ambilPenilaian($pdo, $_GET['id'] ?? 0);
    $pdo->beginTransaction();
    // Nilai milik penilaian ini dihapus dulu karena relasi bersifat RESTRICT.
    $pdo->prepare('DELETE FROM NILAI_DAN_PENGUMPULAN WHERE id_penilaian = ?')->execute([$p['id_penilaian']]);
    $pdo->prepare('DELETE FROM PENILAIAN WHERE id_penilaian = ?')->execute([$p['id_penilaian']]);
    $pdo->commit();
    respond(null, 'Penilaian berhasil dihapus.');
}

$input = inputData();
$judul = trim((string)($input['judul_penilaian'] ?? ''));
$jenis = trim((string)($input['jenis_komponen'] ?? ''));
if ($judul === '' || preg_match_all('/./us', $judul) > 100) {
    respond(null, 'Judul penilaian wajib diisi, maksimal 100 karakter.', 422);
}
if (!in_array($jenis, $jenisValid, true)) {
    respond(null, 'Jenis komponen harus Tugas, Kuis, UTS, atau UAS.', 422);
}

if ($method === 'PUT') {
    $p = ambilPenilaian($pdo, $_GET['id'] ?? 0);
    $stmt = $pdo->prepare('UPDATE PENILAIAN SET judul_penilaian = ?, jenis_komponen = ? WHERE id_penilaian = ?');
    $stmt->execute([$judul, $jenis, $p['id_penilaian']]);
    respond(['id_penilaian' => (int)$p['id_penilaian']], 'Penilaian berhasil diperbarui.');
}

// POST: buat penilaian baru
$pm = ambilPenugasan($pdo, $input['id_penugasan'] ?? 0);
$stmt = $pdo->prepare('INSERT INTO PENILAIAN (id_penugasan, judul_penilaian, jenis_komponen, tanggal_dibuat)
    VALUES (?, ?, ?, NOW())');
$stmt->execute([$pm['id_penugasan'], $judul, $jenis]);
respond(['id_penilaian' => (int)$pdo->lastInsertId()], 'Penilaian berhasil dibuat.', 201);