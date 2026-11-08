<?php
session_start();
require_once '../config/app.php'; 
require_once '../config/koneksi.php';

// Cek session login sesuai struktur Padel
if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) {
    echo "<script>alert('Anda harus login untuk melihat riwayat!'); window.location.href='../auth/login.php';</script>";
    exit;
}

$user_id = $_SESSION['user']['id'];
$pdo = db();

// Query JOIN 3 Tabel: peminjaman, detail_peminjaman, dan alat
// Hanya ambil milik user yang sedang login
$query = "
    SELECT p.kode, p.tanggal_pinjam, p.tanggal_kembali_rencana, p.status, 
           a.nama AS nama_alat, dp.jumlah 
    FROM peminjaman p
    JOIN detail_peminjaman dp ON p.id = dp.peminjaman_id
    JOIN alat a ON dp.alat_id = a.id
    WHERE p.user_id = ?
    ORDER BY p.created_at DESC
";
$stmt = $pdo->prepare($query);
$stmt->execute([$user_id]);
$riwayats = $stmt->fetchAll();

$judul = "Riwayat Peminjaman";
include '../includes/header.php';
// Navbar di-comment jika memang header.php sudah membawanya
// include '../includes/navbar.php'; 
?>
<!-- Jaga-jaga jika CSS belum keload -->
<script src="https://cdn.tailwindcss.com"></script>

<div class="container mx-auto mt-8 px-4 max-w-5xl">
    <h2 class="text-2xl font-bold mb-6 text-gray-800">Riwayat Peminjaman Saya</h2>

    <div class="bg-white rounded-lg shadow-md overflow-hidden border border-gray-200">
        <div class="overflow-x-auto">
            <table class="min-w-full leading-normal">
                <thead>
                    <tr>
                        <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Kode / Alat</th>
                        <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Tgl Pinjam</th>
                        <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Tgl Kembali</th>
                        <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Jumlah</th>
                        <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($riwayats) > 0) : ?>
                        <?php foreach ($riwayats as $row) : ?>
                            <tr>
                                <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                    <p class="text-gray-900 font-bold"><?= e($row['kode']) ?></p>
                                    <p class="text-gray-500 text-xs"><?= e($row['nama_alat']) ?></p>
                                </td>
                                <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                    <p class="text-gray-900"><?= e(date('d M Y', strtotime($row['tanggal_pinjam']))) ?></p>
                                </td>
                                <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                    <p class="text-gray-900"><?= e(date('d M Y', strtotime($row['tanggal_kembali_rencana']))) ?></p>
                                </td>
                                <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                    <p class="text-gray-900"><?= e($row['jumlah']) ?> unit</p>
                                </td>
                                <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                    <?php 
                                        // Badge warna otomatis berdasarkan status Padel
                                        $status = $row['status'];
                                        $badgeClass = "bg-gray-200 text-gray-800"; // default
                                        if ($status === 'menunggu') $badgeClass = "bg-yellow-100 text-yellow-800";
                                        elseif ($status === 'disetujui') $badgeClass = "bg-blue-100 text-blue-800";
                                        elseif ($status === 'ditolak') $badgeClass = "bg-red-100 text-red-800";
                                        elseif ($status === 'dikembalikan') $badgeClass = "bg-green-100 text-green-800";
                                    ?>
                                    <span class="relative inline-block px-3 py-1 font-semibold leading-tight rounded-full <?= $badgeClass ?>">
                                        <span class="relative"><?= e(ucfirst($status)) ?></span>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="5" class="px-5 py-8 border-b border-gray-200 bg-white text-sm text-center text-gray-500">
                                Belum ada riwayat peminjaman.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>