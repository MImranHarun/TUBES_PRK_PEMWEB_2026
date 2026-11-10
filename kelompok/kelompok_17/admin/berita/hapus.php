<?php
require_once __DIR__ . '/../../includes/auth_admin.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/upload_gambar.php';
require_once __DIR__ . '/../../config/database.php'; // menyediakan $pdo

// Hanya menerima POST. Membuka link ini langsung lewat GET akan ditolak.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Metode tidak diizinkan.');
}
csrf_verify();

$id = (int) ($_POST['id_berita'] ?? 0);

$stmt = $pdo->prepare('SELECT gambar_url FROM berita WHERE id_berita = ?');
$stmt->execute([$id]);
$berita = $stmt->fetch();

if ($berita) {
    // Hapus baris berita dulu. Komentar terkait ikut terhapus otomatis oleh ON DELETE CASCADE.
    $stmt = $pdo->prepare('DELETE FROM berita WHERE id_berita = ?');
    $stmt->execute([$id]);

    // Baru hapus file gambarnya setelah data di database berhasil dihapus.
    hapus_gambar($berita['gambar_url']);

    header('Location: index.php?pesan=hapus');
    exit;
}

header('Location: index.php');
exit;