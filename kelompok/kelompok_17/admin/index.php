<?php
require_once __DIR__ . '/../includes/auth_admin.php';
require_once __DIR__ . '/../config/database.php'; // harus menyediakan $pdo (PDO)

$totalBerita = (int) $pdo->query('SELECT COUNT(*) FROM berita')->fetchColumn();
$totalUser   = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'mahasiswa'")->fetchColumn();

$status = ['menunggu' => 0, 'ditinjau' => 0, 'selesai' => 0];
foreach ($pdo->query('SELECT status, COUNT(*) AS n FROM aspirasi GROUP BY status') as $r) {
    $status[$r['status']] = (int) $r['n'];
}
$totalAspirasi = array_sum($status);

$terbaru = $pdo->query(
    'SELECT a.id_aspirasi, a.judul_aspirasi, a.status, a.tanggal_kirim, a.is_anonim,
            u.nama_lengkap, k.nama_kategori
       FROM aspirasi a
       JOIN users u ON u.id_user = a.id_user
       LEFT JOIN kategori k ON k.id_kategori = a.id_kategori
      ORDER BY a.tanggal_kirim DESC
      LIMIT 5'
)->fetchAll(PDO::FETCH_ASSOC);

$kartu = [
    ['Total Berita', $totalBerita, 'bg-teal-600'],
    ['Total Aspirasi', $totalAspirasi, 'bg-indigo-600'],
    ['Menunggu', $status['menunggu'], 'bg-amber-500'],
    ['Ditinjau', $status['ditinjau'], 'bg-sky-600'],
    ['Selesai', $status['selesai'], 'bg-emerald-600'],
    ['Mahasiswa', $totalUser, 'bg-slate-600'],
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - KabarSih</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 text-slate-800">
<?php include __DIR__ . '/../includes/admin_nav.php'; ?>

<main class="max-w-6xl mx-auto px-4 py-6">
    <h1 class="text-2xl font-bold">Dashboard</h1>
    <p class="text-slate-500 mb-6">Halo, <?= e($_SESSION['nama_lengkap'] ?? 'Admin') ?>.</p>

    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-8">
        <?php foreach ($kartu as [$label, $nilai, $warna]): ?>
            <div class="<?= $warna ?> text-white rounded-lg p-4 shadow">
                <div class="text-3xl font-bold"><?= $nilai ?></div>
                <div class="text-sm opacity-90"><?= e($label) ?></div>
            </div>
        <?php endforeach; ?>
    </div>

    <h2 class="text-lg font-semibold mb-3">Aspirasi Terbaru</h2>
    <div class="bg-white rounded-lg shadow overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-100 text-left">
                <tr>
                    <th class="p-3">Judul</th>
                    <th class="p-3">Pengirim</th>
                    <th class="p-3">Kategori</th>
                    <th class="p-3">Status</th>
                    <th class="p-3">Tanggal</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$terbaru): ?>
                <tr><td colspan="5" class="p-4 text-center text-slate-500">Belum ada aspirasi.</td></tr>
            <?php endif; ?>
            <?php foreach ($terbaru as $a): ?>
                <tr class="border-t">
                    <td class="p-3"><?= e($a['judul_aspirasi']) ?></td>
                    <td class="p-3"><?= $a['is_anonim'] ? '<em>Anonim</em>' : e($a['nama_lengkap']) ?></td>
                    <td class="p-3"><?= e($a['nama_kategori'] ?? '-') ?></td>
                    <td class="p-3 capitalize"><?= e($a['status']) ?></td>
                    <td class="p-3"><?= e(date('d M Y H:i', strtotime($a['tanggal_kirim']))) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</main>
</body>
</html>