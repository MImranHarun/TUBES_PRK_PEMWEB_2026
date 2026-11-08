<?php
// kelompok/kelompok_12/includes/auth.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Periksa apakah session admin_id ada
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}
?>