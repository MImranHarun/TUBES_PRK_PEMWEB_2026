<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

try {
    $stmt = $pdo->query("SELECT * FROM lapor_hoaks ORDER BY created_at DESC");
    $laporan_hoaks = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Gagal mengambil data: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Lapor Hoaks - CiviLens Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex font-sans">
    <!-- Sidebar -->
    <aside class="w-64 bg-gray-900 text-white min-h-screen p-5 relative">
        <h1 class="text-2xl font-bold mb-8 text-blue-400 border-b border-gray-700 pb-4">CiviLens Admin</h1>
        <nav class="space-y-4">
            <a href="dashboard.php" class="block py-2 px-4 rounded hover:bg-gray-700 transition">Dashboard Overview</a>
            <a href="kelola_hoaks.php" class="block py-2 px-4 bg-gray-800 rounded hover:bg-gray-700 transition">Kelola Lapor Hoaks</a>
            <a href="kelola_aspirasi.php" class="block py-2 px-4 rounded hover:bg-gray-700 transition">Kelola Aspirasi</a>
        </nav>
        <div class="absolute bottom-5 w-52">
            <a href="logout.php" class="block w-full text-center py-2 px-4 bg-red-600 rounded hover:bg-red-700 transition font-medium">Logout</a>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 p-8">
        <header class="mb-8">
            <h2 class="text-3xl font-semibold text-gray-800">Manajemen Laporan Hoaks</h2>
            <p class="text-gray-500 mt-1">Verifikasi kebenaran isu yang dilaporkan masyarakat.</p>
        </header>

        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Judul Isu</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sumber</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (count($laporan_hoaks) > 0): ?>
                        <?php foreach ($laporan_hoaks as $row): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= date('d M Y', strtotime($row['created_at'])) ?></td>
                            <td class="px-6 py-4 text-sm text-gray-900 font-medium"><?= htmlspecialchars($row['judul_isu']) ?></td>
                            <td class="px-6 py-4 text-sm text-blue-500 hover:underline">
                                <a href="<?= htmlspecialchars($row['link_sumber']) ?>" target="_blank">Lihat Tautan</a>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php if ($row['status'] == 'pending'): ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">Pending</span>
                                <?php elseif ($row['status'] == 'fakta'): ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Fakta</span>
                                <?php else: ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Hoaks</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <a href="aksi_hoaks.php?id=<?= $row['id'] ?>&status=fakta" class="text-green-600 hover:text-green-900 mr-3">Set Fakta</a>
                                <a href="aksi_hoaks.php?id=<?= $row['id'] ?>&status=hoaks" class="text-red-600 hover:text-red-900">Set Hoaks</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="px-6 py-4 text-center text-gray-500">Belum ada laporan hoaks.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>