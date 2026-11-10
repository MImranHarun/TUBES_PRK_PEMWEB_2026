<?php

require_once __DIR__ . '/../config/auth_check.php';

$_SESSION = [];

// Hapus cookie session jika ada
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
}

session_destroy();
session_id('');
unset($_COOKIE[session_name()]);
session_start();
session_regenerate_id(true);

header('Location: ' . base_url('auth/login.php'));
exit;