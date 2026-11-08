<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

try {
    $stmt = $pdo->query("SELECT * FROM aspirasi ORDER BY created_at DESC");
    $aspirasi = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Gagal mengambil data: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Aspirasi - CiviLens Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex font-sans">
    <!-- Sidebar -->
    <aside class="w-64 bg-gray-900 text-white min-h-screen p-5 relative">
        <h1 class="text-2xl font-bold mb-8 text-blue-400 border-b border-gray-700 pb-4">CiviLens Admin</h1>
        <nav class="space-y-4">
            <a href="dashboard.php" class="block py-2 px-4 rounded hover:bg-gray-700 transition">Dashboard Overview</a>
            <a href="kelola_hoaks.php" class="block py-2 px-4 rounded hover:bg-gray-700 transition">Kelola Lapor Hoaks</a>
            <a href="kelola_aspirasi.php" class="block py-2 px-4 bg-gray-800 rounded hover:bg-gray-700 transition">Kelola Aspirasi</a>
        </nav>
        <div class="absolute bottom-5 w-52">
            <a href="logout.php" class="block w-full text-center py-2 px-4 bg-red-600 rounded hover:bg-red-700 transition font-medium">Logout</a>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 p-8">
        <header class="mb-8">
            <h2 class="text-3xl font-semibold text-gray-800">Kotak Aspirasi Publik</h2>
            <p class="text-gray-500 mt-1">Daftar masukan dan keluhan dari masyarakat.</p>
        </header>

        <div class="grid grid-cols-1 gap-6">
            <?php if (count($aspirasi) > 0): ?>
                <?php foreach ($aspirasi as $row): ?>
                <div class="bg-white p-6 rounded-lg shadow-sm border-l-4 border-green-500">
                    <div class="flex justify-between items-start">
                        <div>
                            <h3 class="text-lg font-bold text-gray-800"><?= htmlspecialchars($row['nama_pengirim']) ?></h3>
                            <p class="text-sm text-gray-500"><?= htmlspecialchars($row['email']) ?> • <?= date('d M Y, H:i', strtotime($row['created_at'])) ?></p>
                        </div>
                        <a href="aksi_hapus_aspirasi.php?id=<?= $row['id'] ?>" class="text-red-500 hover:text-red-700 text-sm font-semibold" onclick="return confirm('Yakin hapus aspirasi ini?')">Hapus</a>
                    </div>
                    <div class="mt-4 text-gray-700 bg-gray-50 p-4 rounded-md">
                        <?= nl2br(htmlspecialchars($row['pesan_aspirasi'])) ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="bg-white p-8 rounded-lg shadow-sm text-center text-gray-500">
                    Belum ada aspirasi yang masuk.
                </div>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>