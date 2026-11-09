<?php
/**
 * File: auth/logout.php
 * Modul 1 (Anggota 1): Menghapus session login pengguna dengan aman
 * dan mengarahkan kembali ke halaman login.
 */

require_once __DIR__ . '/../config/auth_check.php';

// Kosongkan seluruh data session user
$_SESSION = [];

// Hapus cookie session jika ada
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

// Hancurkan session lama lalu mulai session baru untuk mengirim Flash Message
session_destroy();
session_start();

setFlash('success', 'Anda telah berhasil keluar (logout) dari sistem OneBlood.');
header('Location: ' . base_url('auth/login.php'));
exit;