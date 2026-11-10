<?php
session_start();

require_once '../config/app.php'; 
require_once '../config/koneksi.php';

if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) {

    echo "<script>alert('Anda harus login terlebih dahulu!'); window.location.href='../index.php';</script>";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_alat = (int)$_POST['id_alat'];
    $tgl_pinjam = $_POST['tgl_pinjam'];
    $tgl_kembali = $_POST['tgl_kembali'];
    $jumlah = (int)$_POST['jumlah'];
    $keperluan = trim($_POST['keperluan']);
    
    // Ambil user id dari array session 'user' sesuai aturan README
    $user_id = $_SESSION['user']['id'];
    
    // Validasi Tanggal (PHP Native)
    if (strtotime($tgl_kembali) < strtotime($tgl_pinjam)) {
        echo "<script>alert('Tanggal kembali tidak boleh lebih awal dari tanggal pinjam!'); window.history.back();</script>";
        exit;
    }

    // Ambil koneksi PDO
    $pdo = db();

    try {
        $pdo->beginTransaction();

        // 1. Cek stok alat saat ini (untuk mencegah race condition)
        $stmtCek = $pdo->prepare("SELECT stok, nama FROM alat WHERE id = ? FOR UPDATE");
        $stmtCek->execute([$id_alat]);
        $alat = $stmtCek->fetch();

        if (!$alat || $alat['stok'] < $jumlah) {
            throw new Exception("Maaf, stok " . ($alat ? $alat['nama'] : "alat") . " tidak mencukupi.");
        }

        // 2. Buat kode peminjaman unik (format: PJ-YYYYMMDD-RANDOM)
        $kode = 'PJ-' . date('Ymd') . '-' . rand(1000, 9999);

        // 3. Insert ke tabel `peminjaman` (Status awal: 'menunggu' sesuai instruksi README)
        $queryPeminjaman = "INSERT INTO peminjaman (kode, user_id, tanggal_pinjam, tanggal_kembali_rencana, keperluan, status) 
                            VALUES (?, ?, ?, ?, ?, 'menunggu')";
        $stmtPeminjaman = $pdo->prepare($queryPeminjaman);
        $stmtPeminjaman->execute([$kode, $user_id, $tgl_pinjam, $tgl_kembali, $keperluan]);
        
        $peminjaman_id = $pdo->lastInsertId();

        // 4. Insert ke tabel `detail_peminjaman`
        $queryDetail = "INSERT INTO detail_peminjaman (peminjaman_id, alat_id, jumlah) VALUES (?, ?, ?)";
        $stmtDetail = $pdo->prepare($queryDetail);
        $stmtDetail->execute([$peminjaman_id, $id_alat, $jumlah]);

        $pdo->commit();

        echo "<script>alert('Pengajuan berhasil! Menunggu persetujuan admin.'); window.location.href='riwayat.php';</script>";
        exit;

    } catch (Exception $ex) {
        $pdo->rollBack();
        echo "<script>alert('Gagal mengajukan peminjaman: " . addslashes($ex->getMessage()) . "'); window.history.back();</script>";
        exit;
    }
} else {
    header("Location: index.php");
    exit;
}