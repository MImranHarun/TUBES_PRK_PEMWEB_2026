<?php
session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax',
    'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off']);
session_start();
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}
function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
function requireLogin() {
    if (empty($_SESSION['guru'])) {
        header('Location: login.php');
        exit;
    }
}
function isAdmin() {
    return ($_SESSION['guru']['peran'] ?? '') === 'admin';
}
// Dipakai juga oleh modul anggota lain. Admin dapat mengakses semua kelas.
function canAccessClass(PDO $pdo, int $idKelas): bool {
    if (isAdmin()) return true;
    $idGuru = $_SESSION['guru']['id_guru'];
    $stmt = $pdo->prepare('SELECT id_kelas FROM KELAS WHERE id_kelas = ?
        AND (id_wali_kelas = ? OR EXISTS (SELECT 1 FROM PENUGASAN_MENGAJAR p
        WHERE p.id_kelas = KELAS.id_kelas AND p.id_guru = ?))');
    $stmt->execute([$idKelas, $idGuru, $idGuru]);
    return (bool)$stmt->fetch();
}
