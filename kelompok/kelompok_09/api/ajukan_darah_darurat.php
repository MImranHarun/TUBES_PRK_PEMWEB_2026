<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';

function kembaliKeBeranda(string $status): void
{
    header('Location: ' . base_url('index.php?pengajuan=' . $status . '#butuhdarah'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Metode permintaan tidak diizinkan.');
}

$tokenKirim = (string) ($_POST['csrf_token'] ?? '');
$tokenSesi = (string) ($_SESSION['csrf_token'] ?? '');
if ($tokenSesi === '' || !hash_equals($tokenSesi, $tokenKirim)) {
    kembaliKeBeranda('gagal');
}

$judulPesan = trim((string) ($_POST['judul_pesan'] ?? ''));
$golDarah = (string) ($_POST['gol_darah'] ?? '');
$rhesus = (string) ($_POST['rhesus'] ?? '');
$rumahSakit = trim((string) ($_POST['rs_peminta'] ?? ''));
$kontakDarurat = trim((string) ($_POST['kontak_darurat'] ?? ''));
$jumlahKantong = filter_var(
    $_POST['kebutuhan_kantong'] ?? null,
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1, 'max_range' => 100000]]
);
$jumlahJudul = preg_match_all('/./us', $judulPesan);
$jumlahRumahSakit = preg_match_all('/./us', $rumahSakit);
$jumlahKontak = preg_match_all('/./us', $kontakDarurat);

$dataValid =
    $judulPesan !== '' && $jumlahJudul !== false && $jumlahJudul <= 150 &&
    in_array($golDarah, ['A', 'B', 'AB', 'O'], true) &&
    in_array($rhesus, ['+', '-'], true) &&
    $rumahSakit !== '' && $jumlahRumahSakit !== false && $jumlahRumahSakit <= 150 &&
    $jumlahKantong !== false &&
    $kontakDarurat !== '' && $jumlahKontak !== false && $jumlahKontak <= 30;

if (!$dataValid) {
    kembaliKeBeranda('gagal');
}

try {
    $stmt = $db->prepare(
        'INSERT INTO sos_broadcast
            (judul_pesan, gol_darah, rhesus, rs_peminta, kebutuhan_kantong, kontak_darurat, status_aktif, created_at)
         VALUES
            (:judul_pesan, :gol_darah, :rhesus, :rs_peminta, :kebutuhan_kantong, :kontak_darurat, 2, NOW())'
    );
    $stmt->execute([
        ':judul_pesan' => $judulPesan,
        ':gol_darah' => $golDarah,
        ':rhesus' => $rhesus,
        ':rs_peminta' => $rumahSakit,
        ':kebutuhan_kantong' => $jumlahKantong,
        ':kontak_darurat' => $kontakDarurat,
    ]);
} catch (PDOException $e) {
    error_log('Gagal menyimpan pengajuan darah darurat: ' . $e->getMessage());
    kembaliKeBeranda('gagal');
}

kembaliKeBeranda('terkirim');
