<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


function sanitize($data) {
    if ($data === null) {
        return '';
    }
    return htmlspecialchars(trim((string)$data), ENT_QUOTES, 'UTF-8');
}

function isLogin() {
    return isset($_SESSION['user_id']);
}


function isAdmin() {
    return isLogin() && isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}


function wajibLogin() {
    if (!isLogin()) {
        setFlash('danger', 'Silakan masuk terlebih dahulu untuk mengakses halaman ini.');
        header('Location: /login.php');
        exit;
    }
}


function wajibAdmin() {
    if (!isAdmin()) {
        setFlash('danger', 'Anda tidak memiliki hak akses ke halaman administrator.');
        header('Location: /index.php');
        exit;
    }
}


function setFlash($tipe, $pesan) {
    $_SESSION['flash'] = [
        'tipe' => $tipe,
        'pesan' => $pesan
    ];
}

function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}


function badgeStatus($status) {
    $statusLower = strtolower(trim((string)$status));
    $label = ucfirst($statusLower);
    $badgeClass = 'badge-menunggu';

    if ($statusLower === 'diproses') {
        $badgeClass = 'badge-diproses';
    } elseif ($statusLower === 'selesai') {
        $badgeClass = 'badge-selesai';
    }

    return '<span class="badge ' . $badgeClass . '">' . sanitize($label) . '</span>';
}

function buatCsrf() {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}


function csrfField() {
    $token = buatCsrf();
    return '<input type="hidden" name="csrf" value="' . $token . '">';
}


function verifikasiCsrf() {
    if (!isset($_POST['csrf']) || !isset($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $_POST['csrf'])) {
        setFlash('danger', 'Validasi keamanan formulir (CSRF) gagal. Silakan coba kembali.');
        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/index.php'));
        exit;
    }
}

