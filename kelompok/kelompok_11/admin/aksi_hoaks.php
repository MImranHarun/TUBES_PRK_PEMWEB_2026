<?php
session_start();
require_once '../config/database.php';

// Proteksi sesi admin
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

// Cek parameter ID dan status dari URL
if (isset($_GET['id']) && isset($_GET['status'])) {
    $id = $_GET['id'];
    $status = $_GET['status'];

    // Validasi ketat: status cuma boleh 'fakta' atau 'hoaks'
    if (in_array($status, ['fakta', 'hoaks'])) {
        try {
            $stmt = $pdo->prepare("UPDATE lapor_hoaks SET status = :status WHERE id = :id");
            $stmt->execute([
                ':status' => $status,
                ':id' => $id
            ]);
        } catch (PDOException $e) {
            die("Gagal update status: " . $e->getMessage());
        }
    }
}

// Balikin ke halaman tabel setelah sukses
header("Location: kelola_hoaks.php");
exit;
?>