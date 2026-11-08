<?php
session_start();
require_once '../config/koneksi.php';
require_once '../config/app.php';

$id_alat = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id_alat === 0) {
    header("Location: index.php");
    exit;
}

$pdo = db();

// Ambil detail alat berdasarkan ID
$query = "SELECT * FROM alat WHERE id = :id AND stok > 0";
$stmt = $pdo->prepare($query);
$stmt->execute(['id' => $id_alat]);
$alat = $stmt->fetch();

// Jika alat tidak ditemukan atau stok habis
if (!$alat) {
    echo "<script>alert('Alat tidak ditemukan atau stok habis!'); window.location.href='index.php';</script>";
    exit;
}

include '../includes/header.php';
?>
<!-- Script Tailwind untuk berjaga-jaga jika file CSS utama belum terload sempurna -->
<script src="https://cdn.tailwindcss.com"></script>

<div class="container mx-auto mt-8 px-4 max-w-3xl">
    <div class="bg-white rounded-lg shadow-md overflow-hidden border border-gray-200 p-6">
        <div class="flex items-center mb-6 pb-4 border-b border-gray-200">
            <?php $foto_url = !empty($alat['foto']) ? "../uploads/alat/" . $alat['foto'] : "../assets/img/default-alat.png"; ?>
            <img src="<?= e($foto_url) ?>" alt="<?= e($alat['nama']) ?>" class="w-24 h-24 object-cover rounded-md mr-6">
            <div>
                <h2 class="text-2xl font-bold text-gray-800"><?= e($alat['nama']) ?></h2>
                <p class="text-sm text-gray-500">Kode: <?= e($alat['kode']) ?> | Lokasi: <?= e($alat['lokasi']) ?></p>
                <p class="text-sm font-semibold text-blue-600 mt-1">Sisa Stok: <?= e($alat['stok']) ?></p>
            </div>
        </div>

        <!-- Form Pengajuan diproses ke proses_ajukan.php -->
        <form action="proses_ajukan.php" method="POST">
            <input type="hidden" name="id_alat" value="<?= $alat['id'] ?>">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label for="tgl_pinjam" class="block text-gray-700 text-sm font-bold mb-2">Tanggal Pinjam</label>
                    <input type="date" id="tgl_pinjam" name="tgl_pinjam" min="<?= date('Y-m-d') ?>" required
                        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                </div>
                <div>
                    <label for="tgl_kembali" class="block text-gray-700 text-sm font-bold mb-2">Tanggal Kembali</label>
                    <input type="date" id="tgl_kembali" name="tgl_kembali" min="<?= date('Y-m-d') ?>" required
                        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                </div>
            </div>

            <div class="mb-4">
                <label for="jumlah" class="block text-gray-700 text-sm font-bold mb-2">Jumlah Pinjam</label>
                <input type="number" id="jumlah" name="jumlah" min="1" max="<?= e($alat['stok']) ?>" value="1" required
                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                <p class="text-xs text-gray-500 mt-1">Maksimal peminjaman tidak boleh melebihi stok tersedia.</p>
            </div>

            <div class="mb-6">
                <label for="keperluan" class="block text-gray-700 text-sm font-bold mb-2">Keperluan / Keterangan</label>
                <textarea id="keperluan" name="keperluan" rows="3" required placeholder="Contoh: Praktikum Jaringan Komputer, Tugas Akhir, dll."
                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"></textarea>
            </div>

            <div class="flex items-center justify-end">
                <a href="index.php" class="text-gray-500 hover:text-gray-700 mr-4 font-semibold">Batal</a>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded focus:outline-none focus:shadow-outline transition duration-200">
                    Ajukan Peminjaman
                </button>
            </div>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>