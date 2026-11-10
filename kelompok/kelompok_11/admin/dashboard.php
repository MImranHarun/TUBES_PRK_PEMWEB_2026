<?php
session_start();
require_once '../config/database.php'; // Panggil koneksi database

// Cegah akses kalau belum login
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

try {
    // 1. Hitung total petisi warga
    $stmtPetisi = $pdo->query("SELECT COUNT(*) FROM petisi");
    $totalPetisi = $stmtPetisi->fetchColumn();

    // 2. Hitung total aspirasi masuk
    $stmtAspirasi = $pdo->query("SELECT COUNT(*) FROM aspirasi");
    $totalAspirasi = $stmtAspirasi->fetchColumn();

    // 3. Hitung laporan hoaks yang butuh di-review (status pending)
    $stmtHoaks = $pdo->prepare("SELECT COUNT(*) FROM lapor_hoaks WHERE status = :status");
    $stmtHoaks->execute([':status' => 'pending']);
    $totalHoaksPending = $stmtHoaks->fetchColumn();

} catch (PDOException $e) {
    die("Gagal mengambil data statistik: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - CiviLens</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex font-sans">
    <!-- Sidebar -->
    <aside class="w-64 bg-gray-900 text-white min-h-screen p-5 relative">
        <h1 class="text-2xl font-bold mb-8 text-blue-400 border-b border-gray-700 pb-4">CiviLens Admin</h1>
        <nav class="space-y-4">
            <a href="dashboard.php" class="block py-2 px-4 bg-gray-800 rounded hover:bg-gray-700 transition">Dashboard Overview</a>
            <a href="kelola_hoaks.php" class="block py-2 px-4 rounded hover:bg-gray-700 transition">Kelola Lapor Hoaks</a>
            <a href="kelola_aspirasi.php" class="block py-2 px-4 rounded hover:bg-gray-700 transition">Kelola Aspirasi</a>
        </nav>
        
        <!-- Tombol Logout -->
        <div class="absolute bottom-5 w-52">
            <a href="logout.php" class="block w-full text-center py-2 px-4 bg-red-600 rounded hover:bg-red-700 transition font-medium">Logout</a>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 p-8">
        <header class="flex justify-between items-center mb-8">
            <h2 class="text-3xl font-semibold text-gray-800">Dashboard</h2>
            <div class="bg-white px-4 py-2 rounded-full shadow-sm text-gray-600 font-medium">
                👋 Selamat datang, Admin!
            </div>
        </header>

        <!-- Stat Cards Dinamis -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-white p-6 rounded-lg shadow-sm border-l-4 border-blue-500">
                <h3 class="text-gray-500 text-sm font-semibold uppercase tracking-wider">Petisi Warga</h3>
                <p class="text-3xl font-bold text-gray-800 mt-2"><?= $totalPetisi ?></p>
            </div>
            <div class="bg-white p-6 rounded-lg shadow-sm border-l-4 border-green-500">
                <h3 class="text-gray-500 text-sm font-semibold uppercase tracking-wider">Aspirasi Masuk</h3>
                <p class="text-3xl font-bold text-gray-800 mt-2"><?= $totalAspirasi ?></p>
            </div>
            <div class="bg-white p-6 rounded-lg shadow-sm border-l-4 border-red-500">
                <h3 class="text-gray-500 text-sm font-semibold uppercase tracking-wider">Laporan Hoaks (Pending)</h3>
                <p class="text-3xl font-bold text-gray-800 mt-2"><?= $totalHoaksPending ?></p>
            </div>
        </div>
    </main>
</body>
</html>