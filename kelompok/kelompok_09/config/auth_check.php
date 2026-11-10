<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/database.php';

function isLoggedIn(): bool {
    return isset($_SESSION['user_id'], $_SESSION['role']);
}

function cekLogin(): void {
    if (!isLoggedIn()) {
        setFlash('error', 'Silahkan login terlebih dahulu.');
        header('Location: ' . base_url('auth/login.php'));
        exit();
    }
}

function cekRole(array $allowedRoles): void {
    cekLogin();

    $currentRole = (string)($_SESSION['role'] ?? '');
    if (!in_array($currentRole, $allowedRoles, true)) {
        setFlash('error', 'Anda tidak memiliki izin untuk mengakses halaman ini.');
        redirectByRole($currentRole);
    }
}

function redirectByRole(?string $role = null): void {
    $targetRole = $role ?? $_SESSION['role'] ?? '';
    $destinations = [
        'admin' => 'admin/users.php',
        'petugas' => 'petugas/event.php',
        'pendonor' => 'index.php',
    ];
    $destination = $destinations[$targetRole] ?? 'index.php';

    if (!is_file(__DIR__ . '/../' . $destination)) {
        $destination = 'index.php';
    }

    header('Location: ' . base_url($destination));
    exit;
}

function currentUser(): ?array {
    if (!isLoggedIn()) {
        return null;
    }

    return [
        'id'    => (int)$_SESSION['user_id'],
        'nama'  => (string)($_SESSION['nama'] ?? 'Pengguna'),
        'email' => (string)($_SESSION['email'] ?? ''),
        'role'  => (string)($_SESSION['role'] ?? ''),
        'gol_darah' => (string)($_SESSION['gol_darah'] ?? '-'),
        'rhesus' => (string)($_SESSION['rhesus'] ?? ''),
    ];
}

function setFlash(string $type, string $message): void {
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message,
    ];
}

function getFlash(): ?array {
    if (isset($_SESSION['flash']) && is_array($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}