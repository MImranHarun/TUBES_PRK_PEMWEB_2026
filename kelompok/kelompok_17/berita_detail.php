<?php
require_once 'config/database.php';

$id_berita = $_GET['id'] ?? null;

if (!$id_berita) {
    header("Location: index.php");
    exit();
}

try {
    $stmt = $pdo->prepare("SELECT berita.*, kategori.nama_kategori, users.nama_lengkap AS penulis 
                           FROM berita 
                           JOIN kategori ON berita.id_kategori = kategori.id_kategori 
                           JOIN users ON berita.id_user = users.id_user 
                           WHERE berita.id_berita = ?");
    $stmt->execute([$id_berita]);
    $berita = $stmt->fetch();

    if (!$berita) {
        header("Location: index.php");
        exit();
    }
} catch (PDOException $e) {
    header("Location: index.php");
    exit();
}

include 'includes/header.php';
?>

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <a href="index.php" class="text-blue-600 hover:underline text-sm font-medium">&larr; Kembali ke Beranda</a>
    
    <article class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 sm:p-8 mt-4">
        <span class="bg-blue-100 text-blue-800 text-xs font-semibold px-2.5 py-1 rounded">
            <?= htmlspecialchars($berita['nama_kategori']); ?>
        </span>
        
        <h1 class="text-2xl sm:text-3xl font-extrabold text-gray-900 mt-3">
            <?= htmlspecialchars($berita['judul_berita']); ?>
        </h1>
        
        <div class="flex items-center text-xs text-gray-500 space-x-4 mt-3 pb-6 border-b border-gray-100">
            <span>Diterbitkan oleh: <strong class="text-gray-700"><?= htmlspecialchars($berita['penulis']); ?></strong></span>
            <span>&bull;</span>
            <span><?= date('d M Y, H:i', strtotime($berita['tanggal_publikasi'])); ?> WIB</span>
        </div>

        <?php if (!empty($berita['gambar_url'])): ?>
            <img src="uploads/<?= htmlspecialchars($berita['gambar_url']); ?>" alt="Gambar Berita" class="w-full max-h-96 object-cover rounded-lg my-6">
        <?php endif; ?>

        <div class="prose max-w-none text-gray-800 leading-relaxed space-y-4 mt-6">
            <?= nl2br(htmlspecialchars($berita['isi_berita'])); ?>
        </div>
    </article>

    <!-- Section Modul Komentar (Integrasi Anggota 5) -->
    <div class="mt-8 bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h3 class="text-lg font-bold text-gray-900 mb-4">Komentar</h3>
        <!-- Anggota 5 akan menyusun form dan daftar komentar di sini -->
    </div>
</div>

<?php include 'includes/footer.php'; ?>