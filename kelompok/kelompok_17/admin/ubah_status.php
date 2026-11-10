<?php
require_once __DIR__ . '/../includes/auth_admin.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../config/database.php'; // menyediakan $pdo

// Hanya menerima POST. Membuka link ini langsung lewat GET akan ditolak.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Metode tidak diizinkan.');
}
csrf_verify();

$idAspirasi = (int) ($_POST['id_aspirasi'] ?? 0);
$statusBaru = $_POST['status'] ?? '';

// Validasi di server: status harus salah satu dari nilai ENUM di database.
$statusValid = ['menunggu', 'ditinjau', 'selesai'];
if (!in_array($statusBaru, $statusValid, true)) {
    http_response_code(400);
    exit('Status tidak valid. <a href="aspirasi.php">Kembali</a>');
}

// Hanya kolom status yang diubah. Kolom lain tidak disentuh.
$stmt = $pdo->prepare('UPDATE aspirasi SET status = ? WHERE id_aspirasi = ?');
$stmt->execute([$statusBaru, $idAspirasi]);

header('Location: aspirasi.php?pesan=status');
exit;