<?php
session_start();
require_once '../config/koneksi.php';
require_once '../config/app.php';

$pdo = db();

// Ambil data alat dari database (hanya yang stoknya > 0)
// Diubah dari nama_alat menjadi nama
$query = "SELECT * FROM alat WHERE stok > 0 ORDER BY nama ASC";
$stmt = $pdo->query($query);
$alats = $stmt->fetchAll();

include '../includes/header.php';
?>
<script src="https://cdn.tailwindcss.com"></script>

<div class="container mx-auto mt-8 px-4">
    <h2 class="text-2xl font-bold mb-6 text-gray-800">Katalog Alat Laboratorium</h2>

    <!-- Grid Katalog Alat -->
    <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-6">
        <?php if (count($alats) > 0) : ?>
            <?php foreach ($alats as $row) : ?>
                <div class="bg-white rounded-lg shadow-md overflow-hidden border border-gray-200 flex flex-col">
                    <!-- Diubah dari foto_alat menjadi foto -->
                    <?php $foto_url = !empty($row['foto']) ? "../uploads/alat/" . $row['foto'] : "../assets/img/default-alat.png"; ?>
                    <img src="<?= e($foto_url) ?>" alt="<?= e($row['nama']) ?>" class="w-full h-48 object-cover">
                    
                    <div class="p-4 flex flex-col flex-grow">
                        <!-- Diubah dari nama_alat menjadi nama -->
                        <h3 class="font-bold text-lg mb-1"><?= e($row['nama']) ?></h3>
                        <p class="text-xs text-gray-500 mb-2">Kode: <?= e($row['kode']) ?></p>
                        <p class="text-gray-600 text-sm mb-4 line-clamp-2"><?= e($row['deskripsi']) ?></p>
                        
                        <!-- Pendorong margin otomatis ke bawah agar tombol selalu sejajar -->
                        <div class="mt-auto">
                            <div class="flex justify-between items-center mb-4">
                                <span class="text-sm">Lokasi: <span class="font-medium"><?= e($row['lokasi']) ?></span></span>
                                <span class="text-sm">Stok: <span class="font-bold text-blue-600"><?= e($row['stok']) ?></span></span>
                            </div>
                            
                            <a href="ajukan.php?id=<?= $row['id'] ?>" class="block w-full text-center bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded transition duration-200">
                                Pinjam Alat
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else : ?>
            <div class="col-span-full text-center py-8">
                <p class="text-gray-500 text-lg">Maaf, saat ini tidak ada alat yang tersedia untuk dipinjam.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>