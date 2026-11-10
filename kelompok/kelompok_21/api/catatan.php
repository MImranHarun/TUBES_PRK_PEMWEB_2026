<?php
// Modul Catatan Bimbingan Siswa (Nova).
// GET  ?id_kelas=ID  -> ringkasan catatan per siswa dalam satu kelas
// GET  ?id_siswa=ID  -> riwayat catatan + rekomendasi sistem untuk satu siswa
// POST               -> tambah catatan   (body JSON, wajib id_siswa)
// PUT  ?id=ID        -> ubah catatan     (hanya pemilik catatan atau admin)
// DELETE ?id=ID      -> hapus catatan    (hanya pemilik catatan atau admin)
require __DIR__ . '/bootstrap.php';

const STATUS_TINDAK_LANJUT = ['Direncanakan', 'Berjalan', 'Selesai'];
const LABEL_CATATAN = [
    'catatan_kualitatif' => 'Catatan',
    'rencana_tindakan' => 'Rencana tindakan',
    'evaluasi_hasil' => 'Evaluasi hasil',
];

// Ambil teks dari masukan; kosong menjadi NULL bila tidak wajib.
function catatanText(array $input, string $field, int $max, bool $required): ?string {
    $value = trim((string)($input[$field] ?? ''));
    $label = LABEL_CATATAN[$field];
    if ($value === '') {
        if ($required) respond(null, "$label wajib diisi.", 422);
        return null;
    }
    if (preg_match_all('/./us', $value) > $max) respond(null, "$label maksimal $max karakter.", 422);
    return $value;
}

// Terima "2026-11-05T09:30" (datetime-local) atau "2026-11-05 09:30".
function catatanDate($raw): string {
    $raw = str_replace('T', ' ', trim((string)$raw));
    $date = DateTime::createFromFormat('Y-m-d H:i', $raw);
    $errors = DateTime::getLastErrors();
    if (!$date || ($errors && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))) {
        respond(null, 'Tanggal interaksi tidak valid.', 422);
    }
    return $date->format('Y-m-d H:i:s');
}

// Pastikan siswa ada dan kelasnya boleh diakses oleh guru yang login.
function siswaOrFail(PDO $pdo, $id): array {
    $stmt = $pdo->prepare('SELECT id_siswa, id_kelas, nisn, nama_siswa FROM SISWA WHERE id_siswa = ?');
    $stmt->execute([positiveId($id)]);
    $siswa = $stmt->fetch();
    if (!$siswa) respond(null, 'Siswa tidak ditemukan.', 404);
    requireClass($pdo, $siswa['id_kelas']);
    return $siswa;
}

$idGuru = (int)$_SESSION['guru']['id_guru'];

if ($method === 'GET') {
    if (isset($_GET['id_kelas'])) {
        $idKelas = positiveId($_GET['id_kelas']);
        requireClass($pdo, $idKelas);
        $stmt = $pdo->prepare('SELECT s.id_siswa, s.nisn, s.nama_siswa,
                COUNT(c.id_catatan) AS total,
                COALESCE(SUM(c.status_tindak_lanjut <> \'Selesai\'), 0) AS belum_selesai,
                MAX(c.tanggal_interaksi) AS terakhir
            FROM SISWA s LEFT JOIN CATATAN_BIMBINGAN c ON c.id_siswa = s.id_siswa
            WHERE s.id_kelas = ? GROUP BY s.id_siswa, s.nisn, s.nama_siswa ORDER BY s.nama_siswa');
        $stmt->execute([$idKelas]);
        $rows = $stmt->fetchAll();
        foreach ($rows as &$row) {
            $row['total'] = (int)$row['total'];
            $row['belum_selesai'] = (int)$row['belum_selesai'];
        }
        unset($row);
        respond($rows);
    }

    $siswa = siswaOrFail($pdo, $_GET['id_siswa'] ?? 0);
    $stmt = $pdo->prepare('SELECT c.id_catatan, c.id_guru, g.nama_guru, c.id_periode,
            CONCAT(p.tahun_ajaran, \' · \', p.semester) AS periode,
            c.catatan_kualitatif, c.rencana_tindakan, c.status_tindak_lanjut,
            c.evaluasi_hasil, c.tanggal_interaksi
        FROM CATATAN_BIMBINGAN c
        JOIN GURU g ON g.id_guru = c.id_guru
        JOIN PERIODE_PENILAIAN p ON p.id_periode = c.id_periode
        WHERE c.id_siswa = ? ORDER BY c.tanggal_interaksi DESC, c.id_catatan DESC');
    $stmt->execute([$siswa['id_siswa']]);
    $catatan = $stmt->fetchAll();
    foreach ($catatan as &$row) {
        $row['dapat_diubah'] = isAdmin() || (int)$row['id_guru'] === $idGuru;
    }
    unset($row);

    // Rekomendasi dari modul perhitungan (milik anggota lain); boleh kosong.
    $stmt = $pdo->prepare('SELECT m.nama_mapel, r.nilai_akhir, r.indeks_kedisiplinan,
            r.kategori_kuadran, r.rekomendasi_sistem
        FROM REKAP_AKADEMIK_KUADRAN r
        JOIN PENUGASAN_MENGAJAR pm ON pm.id_penugasan = r.id_penugasan
        JOIN MATA_PELAJARAN m ON m.id_mapel = pm.id_mapel
        WHERE r.id_siswa = ? ORDER BY r.id_rekap DESC LIMIT 10');
    $stmt->execute([$siswa['id_siswa']]);
    respond(['siswa' => $siswa, 'catatan' => $catatan, 'rekomendasi' => $stmt->fetchAll()]);
}

if (!in_array($method, ['POST', 'PUT', 'DELETE'])) respond(null, 'Metode tidak didukung.', 405);

$id = null;
$old = null;
if ($method !== 'POST') {
    $id = positiveId($_GET['id'] ?? 0);
    $stmt = $pdo->prepare('SELECT * FROM CATATAN_BIMBINGAN WHERE id_catatan = ?');
    $stmt->execute([$id]);
    $old = $stmt->fetch();
    if (!$old) respond(null, 'Catatan tidak ditemukan.', 404);
    siswaOrFail($pdo, $old['id_siswa']);
    if (!isAdmin() && (int)$old['id_guru'] !== $idGuru) {
        respond(null, 'Catatan hanya dapat diubah oleh guru yang menulisnya.', 403);
    }
}

if ($method === 'DELETE') {
    $stmt = $pdo->prepare('DELETE FROM CATATAN_BIMBINGAN WHERE id_catatan = ?');
    $stmt->execute([$id]);
    respond(null, 'Catatan berhasil dihapus.');
}

$input = inputData();
$catatan = catatanText($input, 'catatan_kualitatif', 2000, true);
$rencana = catatanText($input, 'rencana_tindakan', 1000, false);
$evaluasi = catatanText($input, 'evaluasi_hasil', 1000, false);
$status = trim((string)($input['status_tindak_lanjut'] ?? ''));
if (!in_array($status, STATUS_TINDAK_LANJUT, true)) respond(null, 'Status tindak lanjut tidak valid.', 422);
if ($status === 'Selesai' && $evaluasi === null) {
    respond(null, 'Evaluasi hasil wajib diisi jika status tindak lanjut Selesai.', 422);
}
$tanggal = catatanDate($input['tanggal_interaksi'] ?? '');

if ($method === 'POST') {
    $siswa = siswaOrFail($pdo, $input['id_siswa'] ?? 0);
    $periode = $pdo->query('SELECT id_periode FROM PERIODE_PENILAIAN WHERE status_aktif = 1 LIMIT 1')->fetchColumn();
    if (!$periode) respond(null, 'Belum ada periode penilaian yang aktif. Hubungi admin.', 422);
    $stmt = $pdo->prepare('INSERT INTO CATATAN_BIMBINGAN (id_periode, id_guru, id_siswa,
            catatan_kualitatif, rencana_tindakan, status_tindak_lanjut, evaluasi_hasil, tanggal_interaksi)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([$periode, $idGuru, $siswa['id_siswa'], $catatan, $rencana, $status, $evaluasi, $tanggal]);
    respond(['id_catatan' => (int)$pdo->lastInsertId()], 'Catatan berhasil disimpan.', 201);
}

// PUT: siswa, penulis, dan periode tetap; hanya isi catatan yang berubah.
$stmt = $pdo->prepare('UPDATE CATATAN_BIMBINGAN SET catatan_kualitatif = ?, rencana_tindakan = ?,
        status_tindak_lanjut = ?, evaluasi_hasil = ?, tanggal_interaksi = ? WHERE id_catatan = ?');
$stmt->execute([$catatan, $rencana, $status, $evaluasi, $tanggal, $id]);
respond(['id_catatan' => $id], 'Catatan berhasil diperbarui.');
