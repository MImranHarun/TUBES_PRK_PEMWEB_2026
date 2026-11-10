<?php
// Penjaga akses halaman admin. Panggil di baris pertama setiap file admin:
//   require_once __DIR__ . '/../includes/auth_admin.php';
// Asumsi: login (Anggota 1) mengisi $_SESSION['id_user'] dan $_SESSION['role'].

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Membuat URL absolut ke path di dalam folder kelompok, misalnya admin_url('/admin/aspirasi.php').
// Dihitung dari lokasi file yang sedang dibuka, jadi tetap benar dari subfolder mana pun.
function admin_url(string $path): string
{
    $skrip = $_SERVER['SCRIPT_NAME'] ?? '';
    $posisi = strpos($skrip, '/admin/');
    $basis = $posisi === false ? '' : substr($skrip, 0, $posisi);
    // Spasi di nama folder (misalnya "ppw eg") harus di-encode agar link berfungsi.
    $basisAman = implode('/', array_map('rawurlencode', explode('/', $basis)));
    return $basisAman . $path;
}

if (!isset($_SESSION['id_user'])) {
    header('Location: ' . admin_url('/auth/login.php'));
    exit;
}

if (($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(403);
    exit('Akses ditolak: halaman ini hanya untuk admin.');
}

// Helper escape output (anti-XSS)
function e($s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}