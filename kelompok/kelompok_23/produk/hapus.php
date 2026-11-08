<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../auth/login.php"); exit; }
require_once __DIR__ . '/../config/database.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    $query = $conn->query("SELECT image FROM products WHERE id = '$id'");
    $data = $query->fetch_assoc();

    if ($data) {
        if (!empty($data['image']) && file_exists("../assets/img/" . $data['image'])) {
            unlink("../assets/img/" . $data['image']);
        }

        $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            header("Location: index.php?status=hapus_berhasil");
            exit;
        } else {
            echo "Gagal menghapus produk.";
        }
    } else {
        header("Location: index.php");
    }
} else {
    header("Location: index.php");
}
?>