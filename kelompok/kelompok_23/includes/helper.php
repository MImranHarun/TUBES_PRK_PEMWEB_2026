<?php

if (!function_exists('formatRupiah')) {
    function formatRupiah($angka)
    {
        return 'Rp ' . number_format((float) $angka, 0, ',', '.');
    }
}

if (!function_exists('setFlash')) {
    function setFlash($type, $message)
    {
        $_SESSION['flash'] = [
            'type' => $type,
            'message' => $message
        ];
    }
}

if (!function_exists('showFlash')) {
    function showFlash()
    {
        if (!isset($_SESSION['flash'])) {
            return;
        }

        $flash = $_SESSION['flash'];

        $allowedTypes = ['success', 'danger', 'warning', 'info'];
        $type = in_array($flash['type'], $allowedTypes, true)
            ? $flash['type']
            : 'info';

        echo '<div class="alert alert-' . $type . ' alert-dismissible fade show" role="alert">';
        echo htmlspecialchars($flash['message']);
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
        echo '</div>';

        unset($_SESSION['flash']);
    }
}