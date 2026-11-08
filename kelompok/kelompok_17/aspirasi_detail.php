<?php
require_once 'config/database.php';

$id_aspirasi = $_GET['id'] ?? null;

if (!$id_aspirasi) {
    header("Location: index.php");
    exit();
}

try {
    $stmt = $pdo->prepare("SELECT aspirasi.*, kategori.nama_kategori, users.nama_lengkap 
                           FROM aspirasi 
                           JOIN kategori ON aspirasi.id_kategori = kategori.id_kategori 
                           JOIN users ON aspirasi.id_user = users.id_user 
                           WHERE aspirasi.id_aspirasi = ?");
    $stmt->execute([$id_aspirasi]);
    $aspirasi = $stmt->fetch();

    if (!$aspirasi) {
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
        <div class="flex justify-between items-center">
            <span class="bg-amber-100 text-amber-800 text-xs font-semibold px-2.5 py-1 rounded">
                <?= htmlspecialchars($aspirasi['nama_kategori']); ?>
            </span>
            
            <?php
            $statusClass = match(strtolower($aspirasi['status'])) {
                'ditinjau' => 'bg-blue-100 text-blue-800',
                'selesai' => 'bg-green-100 text-green-800',
                default => 'bg-amber-50 text-amber-700',
            };
            ?>
            <span class="text-xs font-semibold px-3 py-1 rounded-full capitalize <?= $statusClass; ?>">
                Status: <?= htmlspecialchars($aspirasi['status']); ?>
            </span>
        </div>

        <h1 class="text-2xl sm:text-3xl font-extrabold text-gray-900 mt-4">
            <?= htmlspecialchars($aspirasi['judul_aspirasi']); ?>
        </h1>

        <div class="flex items-center text-xs text-gray-500 space-x-4 mt-3 pb-6 border-b border-gray-100">
            <span>Oleh: <strong class="text-gray-700"><?= ($aspirasi['is_anonim'] == 1) ? 'Anonim' : htmlspecialchars($aspirasi['nama_lengkap']); ?></strong></span>
            <span>&bull;</span>
            <span><?= date('d M Y, H:i', strtotime($aspirasi['tanggal_kirim'])); ?> WIB</span>
        </div>

        <div class="text-gray-800 leading-relaxed mt-6">
            <?= nl2br(htmlspecialchars($aspirasi['isi_aspirasi'])); ?>
        </div>
    </article>

    <!-- Section Modul Komentar (Integrasi Anggota 5) -->
    <div class="mt-8 bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h3 class="text-lg font-bold text-gray-900 mb-4">Diskusi / Tanggapan</h3>
        <!-- Anggota 5 akan menyusun form dan daftar komentar di sini -->
    </div>
</div>

<?php include 'includes/footer.php'; ?>