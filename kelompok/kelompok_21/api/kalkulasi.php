<?php
/**
 * =================================================================
 * ENDPOINT API: Kalkulasi Nilai Akhir & Pengaturan Bobot
 * Modul       : Perhitungan Nilai & Laporan Akademik
 * Dikerjakan oleh: Jehan Reza Pahlevi (NPM. 2415061067)
 * =================================================================
 *
 * Endpoint ini menangani:
 * - GET  ?action=penugasan                     → Daftar penugasan guru yang login
 * - GET  ?action=bobot&id_penugasan=X          → Ambil pengaturan bobot
 * - GET  ?action=rekap&id_penugasan=X          → Ambil rekap nilai dengan rincian per komponen
 * - GET  ?action=ringkasan&id_penugasan=X      → Statistik ringkasan kelas
 * - POST ?action=bobot&id_penugasan=X          → Simpan/perbarui bobot (total wajib 100%)
 * - POST ?action=hitung&id_penugasan=X         → Hitung nilai akhir & simpan ke REKAP
 *
 * Nama Kuadran Belajar Arunika:
 *   Bintang Kelas     → Nilai >= 75 & Disiplin >= 75
 *   Bakat Terpendam   → Nilai >= 75 & Disiplin <  75
 *   Butuh Cara Baru   → Nilai <  75 & Disiplin >= 75
 *   Perlu Pendampingan→ Nilai <  75 & Disiplin <  75
 * =================================================================
 */
require __DIR__ . '/bootstrap.php';

// Dikerjakan oleh: Jehan Reza Pahlevi (NPM. 2415061067)
$action = $_GET['action'] ?? '';

// =================================================================
// FUNGSI BANTU: Predikat Huruf berdasarkan Nilai Akhir
// Dikerjakan oleh: Jehan Reza Pahlevi (NPM. 2415061067)
// Skala: A (>=85), B (>=75), C (>=65), D (>=55), E (<55)
// =================================================================
function hitungPredikat($nilaiAkhir) {
    if ($nilaiAkhir === null) return '-';
    if ($nilaiAkhir >= 85) return 'A';
    if ($nilaiAkhir >= 75) return 'B';
    if ($nilaiAkhir >= 65) return 'C';
    if ($nilaiAkhir >= 55) return 'D';
    return 'E';
}

// =================================================================
// FUNGSI BANTU: Distribusi Predikat untuk Ringkasan Kelas
// Dikerjakan oleh: Jehan Reza Pahlevi (NPM. 2415061067)
// =================================================================
function hitungDistribusiPredikat(array $nilaiArr): array {
    $distribusi = ['A' => 0, 'B' => 0, 'C' => 0, 'D' => 0, 'E' => 0];
    foreach ($nilaiArr as $nilai) {
        $predikat = hitungPredikat((float)$nilai);
        if (isset($distribusi[$predikat])) $distribusi[$predikat]++;
    }
    return $distribusi;
}

// =================================================================
// FUNGSI BANTU: Distribusi Kuadran Belajar untuk Ringkasan Kelas
// Dikerjakan oleh: Jehan Reza Pahlevi (NPM. 2415061067)
// =================================================================
function hitungDistribusiKuadran(array $rekapAll): array {
    $distribusi = [
        'Bintang Kelas'      => 0,
        'Bakat Terpendam'    => 0,
        'Butuh Cara Baru'    => 0,
        'Perlu Pendampingan' => 0
    ];
    foreach ($rekapAll as $r) {
        $k = $r['kategori_kuadran'] ?? '';
        if (isset($distribusi[$k])) $distribusi[$k]++;
    }
    return $distribusi;
}

// =================================================================
// FUNGSI BANTU: Validasi akses penugasan guru
// Dikerjakan oleh: Jehan Reza Pahlevi (NPM. 2415061067)
// Mengembalikan data penugasan lengkap (JOIN kelas, mapel, guru, periode)
// =================================================================
function validasiPenugasan(PDO $pdo, int $idPenugasan): array {
    $stmt = $pdo->prepare(
        'SELECT pm.*, k.nama_kelas, k.tingkat, m.nama_mapel, m.kode_mapel,
                g.nama_guru, p.tahun_ajaran, p.semester
         FROM PENUGASAN_MENGAJAR pm
         JOIN KELAS k ON k.id_kelas = pm.id_kelas
         JOIN MATA_PELAJARAN m ON m.id_mapel = pm.id_mapel
         JOIN GURU g ON g.id_guru = pm.id_guru
         JOIN PERIODE_PENILAIAN p ON p.id_periode = pm.id_periode
         WHERE pm.id_penugasan = ?'
    );
    $stmt->execute([$idPenugasan]);
    $penugasan = $stmt->fetch();
    if (!$penugasan) respond(null, 'Penugasan tidak ditemukan.', 404);
    if (!isAdmin() && (int)$penugasan['id_guru'] !== (int)$_SESSION['guru']['id_guru']) {
        respond(null, 'Anda tidak memiliki akses ke penugasan ini.', 403);
    }
    return $penugasan;
}

// =================================================================
// FUNGSI BANTU: Ambil bobot aktif (default 25% merata jika belum diatur)
// Dikerjakan oleh: Jehan Reza Pahlevi (NPM. 2415061067)
// =================================================================
function ambilBobot(PDO $pdo, int $idPenugasan): array {
    $stmt = $pdo->prepare('SELECT * FROM PENGATURAN_BOBOT WHERE id_penugasan = ?');
    $stmt->execute([$idPenugasan]);
    $bobot = $stmt->fetch();
    if (!$bobot) {
        return [
            'id_bobot'     => null,
            'id_penugasan' => $idPenugasan,
            'bobot_tugas'  => 25,
            'bobot_kuis'   => 25,
            'bobot_uts'    => 25,
            'bobot_uas'    => 25
        ];
    }
    return $bobot;
}

// =================================================================
// FUNGSI BANTU: Ambil peta nilai seluruh siswa secara bulk
// Dikerjakan oleh: Jehan Reza Pahlevi (NPM. 2415061067)
// Menghindari N+1 query untuk performa optimal
// =================================================================
function ambilNilaiBulk(PDO $pdo, array $idPenilaianAll): array {
    $nilaiMap = [];
    if (!empty($idPenilaianAll)) {
        $marks = implode(',', array_fill(0, count($idPenilaianAll), '?'));
        $stmt = $pdo->prepare(
            "SELECT * FROM NILAI_DAN_PENGUMPULAN WHERE id_penilaian IN ($marks)"
        );
        $stmt->execute($idPenilaianAll);
        foreach ($stmt->fetchAll() as $n) {
            $nilaiMap[$n['id_siswa']][$n['id_penilaian']] = $n;
        }
    }
    return $nilaiMap;
}

// =================================================================
// FUNGSI BANTU: Kelompokkan daftar penilaian per jenis komponen
// Dikerjakan oleh: Jehan Reza Pahlevi (NPM. 2415061067)
// =================================================================
function kelompokkanPenilaian(array $daftarPenilaian): array {
    $penilaianPerJenis = ['Tugas' => [], 'Kuis' => [], 'UTS' => [], 'UAS' => []];
    foreach ($daftarPenilaian as $p) {
        if (isset($penilaianPerJenis[$p['jenis_komponen']])) {
            $penilaianPerJenis[$p['jenis_komponen']][] = $p['id_penilaian'];
        }
    }
    return $penilaianPerJenis;
}

// =================================================================
// FUNGSI BANTU: Hitung rata-rata per jenis komponen untuk satu siswa
// Dikerjakan oleh: Jehan Reza Pahlevi (NPM. 2415061067)
// =================================================================
function hitungRataPerJenis(array $penilaianPerJenis, array $nilaiSiswa): array {
    $rataPerJenis = [];
    foreach ($penilaianPerJenis as $jenis => $ids) {
        $nilaiJenis = [];
        foreach ($ids as $idP) {
            if (isset($nilaiSiswa[$idP]) && $nilaiSiswa[$idP]['nilai'] !== null) {
                $nilaiJenis[] = (float)$nilaiSiswa[$idP]['nilai'];
            }
        }
        $rataPerJenis[$jenis] = !empty($nilaiJenis)
            ? round(array_sum($nilaiJenis) / count($nilaiJenis), 2)
            : null;
    }
    return $rataPerJenis;
}

// =================================================================
// FUNGSI BANTU: Hitung nilai akhir berbobot
// Dikerjakan oleh: Jehan Reza Pahlevi (NPM. 2415061067)
// Formula: NA = (Rata_Tugas×B_Tugas + Rata_Kuis×B_Kuis
//             + Rata_UTS×B_UTS + Rata_UAS×B_UAS) / 100
// =================================================================
function hitungNilaiAkhir(array $rataPerJenis, array $bobot): float {
    $na = (
        ($rataPerJenis['Tugas'] ?? 0) * (float)$bobot['bobot_tugas'] +
        ($rataPerJenis['Kuis']  ?? 0) * (float)$bobot['bobot_kuis']  +
        ($rataPerJenis['UTS']   ?? 0) * (float)$bobot['bobot_uts']   +
        ($rataPerJenis['UAS']   ?? 0) * (float)$bobot['bobot_uas']
    ) / 100;
    return round($na, 2);
}

// =================================================================
// FUNGSI BANTU: Tentukan kategori kuadran belajar Arunika
// Dikerjakan oleh: Jehan Reza Pahlevi (NPM. 2415061067)
// =================================================================
function tentukanKuadran(float $nilaiAkhir, float $indeksKedisiplinan): string {
    if ($nilaiAkhir >= 75 && $indeksKedisiplinan >= 75) return 'Bintang Kelas';
    if ($nilaiAkhir >= 75 && $indeksKedisiplinan <  75) return 'Bakat Terpendam';
    if ($nilaiAkhir <  75 && $indeksKedisiplinan >= 75) return 'Butuh Cara Baru';
    return 'Perlu Pendampingan';
}

// =================================================================
// FUNGSI BANTU: Ambil rekomendasi sistem berdasarkan kuadran
// Dikerjakan oleh: Jehan Reza Pahlevi (NPM. 2415061067)
// =================================================================
function ambilRekomendasi(string $kuadran): string {
    return [
        'Bintang Kelas'      => 'Siswa berprestasi dan disiplin. Pertahankan dan berikan apresiasi.',
        'Bakat Terpendam'    => 'Nilai baik namun kedisiplinan rendah. Perlu pembinaan kedisiplinan pengumpulan tugas.',
        'Butuh Cara Baru'    => 'Disiplin baik namun nilai rendah. Perlu bimbingan akademik dan metode belajar baru.',
        'Perlu Pendampingan' => 'Perlu perhatian khusus: bimbingan akademik dan pembinaan kedisiplinan secara intensif.'
    ][$kuadran] ?? 'Kategori kuadran tidak dikenali.';
}


// ╔═════════════════════════════════════════════════════════════════╗
// ║  ACTION: Daftar Penugasan Guru yang Sedang Login               ║
// ║  Dikerjakan oleh: Jehan Reza Pahlevi (NPM. 2415061067)        ║
// ╚═════════════════════════════════════════════════════════════════╝
if ($action === 'penugasan') {
    if ($method !== 'GET') respond(null, 'Metode tidak didukung.', 405);

    $where  = '';
    $params = [];
    if (!isAdmin()) {
        $where  = 'WHERE pm.id_guru = ?';
        $params = [$_SESSION['guru']['id_guru']];
    }

    $stmt = $pdo->prepare(
        "SELECT pm.id_penugasan, pm.id_guru, pm.id_kelas, pm.id_mapel, pm.id_periode,
                g.nama_guru, k.nama_kelas, k.tingkat,
                m.nama_mapel, m.kode_mapel,
                p.tahun_ajaran, p.semester, p.status_aktif
         FROM PENUGASAN_MENGAJAR pm
         JOIN GURU g ON g.id_guru = pm.id_guru
         JOIN KELAS k ON k.id_kelas = pm.id_kelas
         JOIN MATA_PELAJARAN m ON m.id_mapel = pm.id_mapel
         JOIN PERIODE_PENILAIAN p ON p.id_periode = pm.id_periode
         $where
         ORDER BY p.status_aktif DESC, p.tahun_ajaran DESC, k.nama_kelas ASC"
    );
    $stmt->execute($params);
    respond($stmt->fetchAll());
}


// ── Validasi id_penugasan (wajib untuk aksi selain 'penugasan') ──
// Dikerjakan oleh: Jehan Reza Pahlevi (NPM. 2415061067)
$idPenugasan = positiveId($_GET['id_penugasan'] ?? 0);
$penugasan   = validasiPenugasan($pdo, $idPenugasan);


// ╔═════════════════════════════════════════════════════════════════╗
// ║  ACTION: GET Pengaturan Bobot                                  ║
// ║  Dikerjakan oleh: Jehan Reza Pahlevi (NPM. 2415061067)        ║
// ╚═════════════════════════════════════════════════════════════════╝
if ($action === 'bobot' && $method === 'GET') {
    respond(ambilBobot($pdo, $idPenugasan));
}


// ╔═════════════════════════════════════════════════════════════════╗
// ║  ACTION: POST Simpan/Perbarui Bobot                            ║
// ║  Dikerjakan oleh: Jehan Reza Pahlevi (NPM. 2415061067)        ║
// ║  Validasi: semua bobot >= 0 dan total harus tepat 100%         ║
// ╚═════════════════════════════════════════════════════════════════╝
if ($action === 'bobot' && in_array($method, ['POST', 'PUT'])) {
    $input = inputData();

    $bobotTugas = filter_var($input['bobot_tugas'] ?? '', FILTER_VALIDATE_FLOAT);
    $bobotKuis  = filter_var($input['bobot_kuis']  ?? '', FILTER_VALIDATE_FLOAT);
    $bobotUts   = filter_var($input['bobot_uts']   ?? '', FILTER_VALIDATE_FLOAT);
    $bobotUas   = filter_var($input['bobot_uas']   ?? '', FILTER_VALIDATE_FLOAT);

    // Validasi tipe data numerik
    if ($bobotTugas === false || $bobotKuis === false ||
        $bobotUts === false  || $bobotUas === false) {
        respond(null, 'Semua bobot harus berupa angka.', 422);
    }
    // Validasi nilai tidak negatif
    if ($bobotTugas < 0 || $bobotKuis < 0 || $bobotUts < 0 || $bobotUas < 0) {
        respond(null, 'Bobot tidak boleh bernilai negatif.', 422);
    }
    // Validasi total bobot wajib tepat 100%
    $total = $bobotTugas + $bobotKuis + $bobotUts + $bobotUas;
    if (abs($total - 100) > 0.01) {
        respond(null, 'Total bobot harus tepat 100%. Saat ini: ' . round($total, 2) . '%', 422);
    }

    // Upsert: cek apakah bobot sudah ada untuk penugasan ini
    $stmt = $pdo->prepare('SELECT id_bobot FROM PENGATURAN_BOBOT WHERE id_penugasan = ?');
    $stmt->execute([$idPenugasan]);
    $existing = $stmt->fetch();

    if ($existing) {
        $stmt = $pdo->prepare(
            'UPDATE PENGATURAN_BOBOT
             SET bobot_tugas = ?, bobot_kuis = ?, bobot_uts = ?, bobot_uas = ?
             WHERE id_penugasan = ?'
        );
        $stmt->execute([$bobotTugas, $bobotKuis, $bobotUts, $bobotUas, $idPenugasan]);
        respond(['id_bobot' => (int)$existing['id_bobot']], 'Bobot berhasil diperbarui.');
    } else {
        $stmt = $pdo->prepare(
            'INSERT INTO PENGATURAN_BOBOT
             (id_penugasan, bobot_tugas, bobot_kuis, bobot_uts, bobot_uas)
             VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$idPenugasan, $bobotTugas, $bobotKuis, $bobotUts, $bobotUas]);
        respond(['id_bobot' => (int)$pdo->lastInsertId()], 'Bobot berhasil disimpan.', 201);
    }
}


// ╔═════════════════════════════════════════════════════════════════╗
// ║  ACTION: GET Rekap Nilai dengan Rincian per Komponen           ║
// ║  Dikerjakan oleh: Jehan Reza Pahlevi (NPM. 2415061067)        ║
// ║  Menghitung on-the-fly: rata-rata per komponen, nilai akhir,   ║
// ║  predikat huruf, dan status kelengkapan nilai tiap siswa.      ║
// ╚═════════════════════════════════════════════════════════════════╝
if ($action === 'rekap' && $method === 'GET') {
    $bobot = ambilBobot($pdo, $idPenugasan);

    // Ambil seluruh siswa di kelas penugasan
    $stmt = $pdo->prepare(
        'SELECT id_siswa, nisn, nama_siswa, jenis_kelamin
         FROM SISWA WHERE id_kelas = ? ORDER BY nama_siswa'
    );
    $stmt->execute([$penugasan['id_kelas']]);
    $daftarSiswa = $stmt->fetchAll();

    // Ambil seluruh item penilaian dalam penugasan
    $stmt = $pdo->prepare('SELECT * FROM PENILAIAN WHERE id_penugasan = ?');
    $stmt->execute([$idPenugasan]);
    $daftarPenilaian  = $stmt->fetchAll();
    $penilaianPerJenis = kelompokkanPenilaian($daftarPenilaian);
    $totalPenilaian    = count($daftarPenilaian);

    // Bulk-fetch semua nilai (menghindari N+1 query)
    $idPenilaianAll = array_column($daftarPenilaian, 'id_penilaian');
    $nilaiMap       = ambilNilaiBulk($pdo, $idPenilaianAll);

    // Ambil rekap tersimpan (jika sudah pernah dihitung sebelumnya)
    $stmt = $pdo->prepare('SELECT * FROM REKAP_AKADEMIK_KUADRAN WHERE id_penugasan = ?');
    $stmt->execute([$idPenugasan]);
    $rekapMap = [];
    foreach ($stmt->fetchAll() as $r) {
        $rekapMap[$r['id_siswa']] = $r;
    }

    // Hitung rincian per siswa
    // Dikerjakan oleh: Jehan Reza Pahlevi (NPM. 2415061067)
    $hasilRekap = [];
    foreach ($daftarSiswa as $siswa) {
        $idSiswa    = $siswa['id_siswa'];
        $nilaiSiswa = $nilaiMap[$idSiswa] ?? [];

        // Rata-rata per jenis komponen
        $rataPerJenis = hitungRataPerJenis($penilaianPerJenis, $nilaiSiswa);

        // Hitung nilai akhir berbobot (hanya jika sudah ada data nilai)
        $sudahDinilai = count($nilaiSiswa);
        $nilaiAkhir   = null;
        $predikat     = '-';
        if ($sudahDinilai > 0) {
            $nilaiAkhir = hitungNilaiAkhir($rataPerJenis, $bobot);
            $predikat   = hitungPredikat($nilaiAkhir);
        }

        // Status kelengkapan
        $statusKelengkapan = ($sudahDinilai >= $totalPenilaian && $totalPenilaian > 0)
            ? 'Lengkap' : 'Belum lengkap';

        // Data rekap tersimpan (indeks kedisiplinan & kuadran)
        $rekap = $rekapMap[$idSiswa] ?? null;

        $hasilRekap[] = [
            'id_siswa'                => $idSiswa,
            'nisn'                    => $siswa['nisn'],
            'nama_siswa'              => $siswa['nama_siswa'],
            'jenis_kelamin'           => $siswa['jenis_kelamin'],
            'rata_tugas'              => $rataPerJenis['Tugas'],
            'rata_kuis'               => $rataPerJenis['Kuis'],
            'rata_uts'                => $rataPerJenis['UTS'],
            'rata_uas'                => $rataPerJenis['UAS'],
            'nilai_akhir'             => $nilaiAkhir,
            'predikat'                => $predikat,
            'status_kelengkapan_nilai'=> $statusKelengkapan,
            'jumlah_dinilai'          => $sudahDinilai,
            'jumlah_penilaian'        => $totalPenilaian,
            // Dari tabel REKAP (tersedia setelah action=hitung)
            'indeks_kedisiplinan'     => $rekap['indeks_kedisiplinan'] ?? null,
            'kategori_kuadran'        => $rekap['kategori_kuadran'] ?? null,
            'rekomendasi_sistem'      => $rekap['rekomendasi_sistem'] ?? null
        ];
    }

    respond([
        'penugasan' => [
            'id_penugasan' => $idPenugasan,
            'nama_guru'    => $penugasan['nama_guru'],
            'nama_kelas'   => $penugasan['nama_kelas'],
            'tingkat'      => $penugasan['tingkat'],
            'nama_mapel'   => $penugasan['nama_mapel'],
            'kode_mapel'   => $penugasan['kode_mapel'],
            'tahun_ajaran' => $penugasan['tahun_ajaran'],
            'semester'     => $penugasan['semester']
        ],
        'bobot' => [
            'bobot_tugas' => (float)$bobot['bobot_tugas'],
            'bobot_kuis'  => (float)$bobot['bobot_kuis'],
            'bobot_uts'   => (float)$bobot['bobot_uts'],
            'bobot_uas'   => (float)$bobot['bobot_uas']
        ],
        'jumlah_penilaian' => $totalPenilaian,
        'jumlah_siswa'     => count($daftarSiswa),
        'siswa'            => $hasilRekap
    ]);
}


// ╔═════════════════════════════════════════════════════════════════╗
// ║  ACTION: GET Ringkasan Statistik Kelas                         ║
// ║  Dikerjakan oleh: Jehan Reza Pahlevi (NPM. 2415061067)        ║
// ║  Menampilkan rata-rata kelas, nilai tertinggi/terendah,        ║
// ║  persentase kelengkapan, distribusi predikat & kuadran.        ║
// ╚═════════════════════════════════════════════════════════════════╝
if ($action === 'ringkasan' && $method === 'GET') {
    $stmt = $pdo->prepare(
        'SELECT r.*, s.nama_siswa
         FROM REKAP_AKADEMIK_KUADRAN r
         JOIN SISWA s ON s.id_siswa = r.id_siswa
         WHERE r.id_penugasan = ?'
    );
    $stmt->execute([$idPenugasan]);
    $rekapAll = $stmt->fetchAll();

    if (empty($rekapAll)) {
        respond([
            'sudah_dihitung'     => false,
            'jumlah_siswa'       => 0,
            'rata_rata_kelas'    => null,
            'nilai_tertinggi'    => null,
            'nilai_terendah'     => null,
            'persen_lengkap'     => 0,
            'distribusi_predikat'=> ['A' => 0, 'B' => 0, 'C' => 0, 'D' => 0, 'E' => 0],
            'distribusi_kuadran' => [
                'Bintang Kelas' => 0, 'Bakat Terpendam' => 0,
                'Butuh Cara Baru' => 0, 'Perlu Pendampingan' => 0
            ]
        ]);
    }

    $nilaiAkhirArr    = array_column($rekapAll, 'nilai_akhir');
    $jumlahLengkap    = count(array_filter(
        $rekapAll, fn($r) => $r['status_kelengkapan_nilai'] === 'Lengkap'
    ));
    $jumlahSiswa      = count($rekapAll);

    respond([
        'sudah_dihitung'      => true,
        'jumlah_siswa'        => $jumlahSiswa,
        'rata_rata_kelas'     => round(array_sum($nilaiAkhirArr) / $jumlahSiswa, 2),
        'nilai_tertinggi'     => round(max($nilaiAkhirArr), 2),
        'nilai_terendah'      => round(min($nilaiAkhirArr), 2),
        'persen_lengkap'      => round(($jumlahLengkap / $jumlahSiswa) * 100, 1),
        'distribusi_predikat' => hitungDistribusiPredikat($nilaiAkhirArr),
        'distribusi_kuadran'  => hitungDistribusiKuadran($rekapAll)
    ]);
}


// ╔═════════════════════════════════════════════════════════════════╗
// ║  ACTION: POST Hitung Nilai Akhir & Simpan ke Rekap             ║
// ║  Dikerjakan oleh: Jehan Reza Pahlevi (NPM. 2415061067)        ║
// ║                                                                 ║
// ║  Formula Nilai Akhir:                                           ║
// ║    NA = (Rata_Tugas × B_Tugas + Rata_Kuis × B_Kuis             ║
// ║        + Rata_UTS × B_UTS + Rata_UAS × B_UAS) / 100            ║
// ║                                                                 ║
// ║  Indeks Kedisiplinan:                                           ║
// ║    IK = (Jumlah Tepat Waktu / Total Penilaian) × 100%          ║
// ║                                                                 ║
// ║  Kuadran Belajar Arunika:                                       ║
// ║    NA>=75 & IK>=75 → Bintang Kelas                              ║
// ║    NA>=75 & IK<75  → Bakat Terpendam                            ║
// ║    NA<75  & IK>=75 → Butuh Cara Baru                            ║
// ║    NA<75  & IK<75  → Perlu Pendampingan                         ║
// ╚═════════════════════════════════════════════════════════════════╝
if ($action === 'hitung' && $method === 'POST') {
    $bobot = ambilBobot($pdo, $idPenugasan);

    // Ambil seluruh item penilaian
    $stmt = $pdo->prepare('SELECT * FROM PENILAIAN WHERE id_penugasan = ?');
    $stmt->execute([$idPenugasan]);
    $daftarPenilaian   = $stmt->fetchAll();
    $penilaianPerJenis = kelompokkanPenilaian($daftarPenilaian);
    $totalPenilaian    = count($daftarPenilaian);

    // Ambil daftar siswa di kelas
    $stmt = $pdo->prepare('SELECT id_siswa FROM SISWA WHERE id_kelas = ?');
    $stmt->execute([$penugasan['id_kelas']]);
    $daftarSiswa = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (empty($daftarSiswa)) {
        respond(null, 'Tidak ada siswa di kelas ini.', 422);
    }

    // Bulk-fetch semua nilai (optimasi performa)
    $idPenilaianAll = array_column($daftarPenilaian, 'id_penilaian');
    $nilaiMap       = ambilNilaiBulk($pdo, $idPenilaianAll);

    // Mulai transaksi database
    // Dikerjakan oleh: Jehan Reza Pahlevi (NPM. 2415061067)
    $pdo->beginTransaction();
    $hasilRekap = [];

    foreach ($daftarSiswa as $idSiswa) {
        $nilaiSiswa = $nilaiMap[$idSiswa] ?? [];

        // ── Rata-rata per jenis komponen ──
        $rataPerJenis = hitungRataPerJenis($penilaianPerJenis, $nilaiSiswa);

        // ── Nilai akhir berbobot ──
        $nilaiAkhir = hitungNilaiAkhir($rataPerJenis, $bobot);

        // ── Indeks kedisiplinan ──
        $tepatWaktu   = 0;
        $sudahDinilai = 0;
        foreach ($nilaiSiswa as $n) {
            $sudahDinilai++;
            if ($n['status_pengumpulan'] === 'Tepat waktu') $tepatWaktu++;
        }
        $indeksKedisiplinan = ($totalPenilaian > 0)
            ? round(($tepatWaktu / $totalPenilaian) * 100, 2)
            : 0;

        // ── Status kelengkapan ──
        $statusKelengkapan = ($sudahDinilai >= $totalPenilaian && $totalPenilaian > 0)
            ? 'Lengkap' : 'Belum lengkap';

        // ── Kategori kuadran & rekomendasi ──
        $kuadran     = tentukanKuadran($nilaiAkhir, $indeksKedisiplinan);
        $rekomendasi = ambilRekomendasi($kuadran);
        $predikat    = hitungPredikat($nilaiAkhir);

        // ── Upsert ke tabel REKAP_AKADEMIK_KUADRAN ──
        $stmt = $pdo->prepare(
            'SELECT id_rekap FROM REKAP_AKADEMIK_KUADRAN
             WHERE id_penugasan = ? AND id_siswa = ?'
        );
        $stmt->execute([$idPenugasan, $idSiswa]);
        $existingRekap = $stmt->fetch();

        if ($existingRekap) {
            $stmt = $pdo->prepare(
                'UPDATE REKAP_AKADEMIK_KUADRAN
                 SET nilai_akhir = ?, indeks_kedisiplinan = ?,
                     status_kelengkapan_nilai = ?, kategori_kuadran = ?,
                     rekomendasi_sistem = ?
                 WHERE id_rekap = ?'
            );
            $stmt->execute([
                $nilaiAkhir, $indeksKedisiplinan, $statusKelengkapan,
                $kuadran, $rekomendasi, $existingRekap['id_rekap']
            ]);
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO REKAP_AKADEMIK_KUADRAN
                 (id_penugasan, id_siswa, nilai_akhir, indeks_kedisiplinan,
                  status_kelengkapan_nilai, kategori_kuadran, rekomendasi_sistem)
                 VALUES (?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $idPenugasan, $idSiswa, $nilaiAkhir, $indeksKedisiplinan,
                $statusKelengkapan, $kuadran, $rekomendasi
            ]);
        }

        $hasilRekap[] = [
            'id_siswa'                => $idSiswa,
            'nilai_akhir'             => $nilaiAkhir,
            'predikat'                => $predikat,
            'indeks_kedisiplinan'     => $indeksKedisiplinan,
            'status_kelengkapan_nilai'=> $statusKelengkapan,
            'kategori_kuadran'        => $kuadran,
            'rekomendasi_sistem'      => $rekomendasi
        ];
    }

    $pdo->commit();
    respond($hasilRekap, 'Rekap berhasil dihitung untuk ' . count($hasilRekap) . ' siswa.');
}


// ── Fallback: aksi tidak dikenali ──
// Dikerjakan oleh: Jehan Reza Pahlevi (NPM. 2415061067)
respond(null, 'Aksi tidak dikenali. Gunakan: penugasan, bobot, rekap, ringkasan, atau hitung.', 400);

