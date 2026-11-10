<?php
// Proteksi CSRF: setiap form admin membawa token rahasia yang hanya diketahui session ini.
// Pemakaian:
//   require_once __DIR__ . '/../includes/csrf.php';
//   echo csrf_field();                   di dalam form method POST
//   csrf_verify();                      di awal proses POST

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ambil token untuk session ini. Dibuat sekali, lalu dipakai terus sampai logout.
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Input tersembunyi yang dimasukkan ke dalam form.
function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

// Hentikan proses jika token dari form tidak cocok dengan token di session.
function csrf_verify(): void
{
    $kiriman = $_POST['csrf_token'] ?? '';
    if (!is_string($kiriman) || !hash_equals(csrf_token(), $kiriman)) {
        http_response_code(403);
        exit('Permintaan tidak valid. Muat ulang halaman lalu coba lagi.');
    }
}