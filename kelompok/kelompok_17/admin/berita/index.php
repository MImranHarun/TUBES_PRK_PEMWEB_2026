<?php
require_once __DIR__ . '/../includes/auth_admin.php';
require_once __DIR__ . '/../config/database.php'; 

// Pengambilan Data Sesuai Kontrak
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
    ['label' => 'Total Berita',      'nilai' => $totalBerita,         'ikon' => 'berita',   'catatan' => 'Artikel yang sudah dipublikasikan', 'warna' => 'bg-blue-50 text-blue-600'],
    ['label' => 'Total Aspirasi',    'nilai' => $totalAspirasi,       'ikon' => 'aspirasi', 'catatan' => 'Seluruh aspirasi yang masuk',       'warna' => 'bg-blue-50 text-blue-600'],
    ['label' => 'Aspirasi Menunggu', 'nilai' => $status['menunggu'],  'ikon' => 'jam',      'catatan' => 'Belum ditinjau admin',              'warna' => 'bg-amber-50 text-amber-600'],
    ['label' => 'Total Mahasiswa',   'nilai' => $totalUser,           'ikon' => 'pengguna', 'catatan' => 'Akun mahasiswa terdaftar',          'warna' => 'bg-blue-50 text-blue-600']
];

// Inisiasi Variabel Layout Kontrak
$judulTab = 'Dashboard';
include __DIR__ . '/../includes/admin_header.php';
?>

<!-- Welcome Section -->
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Dashboard</h1>
        <p class="text-slate-500 text-sm mt-1">Selamat datang, <?= e($_SESSION['nama_lengkap'] ?? 'Admin') ?>. Berikut ringkasan portal berita dan aspirasi KabarSih.</p>
    </div>
    <div class="flex gap-2">
        <a href="<?= e(admin_url('/admin/berita/tambah.php')) ?>" class="inline-flex items-center gap-2 bg-blue-600 text-white rounded-lg px-4 py-2 hover:bg-blue-700 focus-visible:ring-2 focus-visible:ring-blue-600 text-sm font-medium transition-colors">
            <?= ikon('tambah') ?> Tambah Berita
        </a>
        <a href="<?= e(admin_url('/admin/aspirasi.php')) ?>" class="inline-flex items-center gap-2 bg-white border border-slate-300 text-slate-700 rounded-lg px-4 py-2 hover:bg-slate-50 focus-visible:ring-2 focus-visible:ring-blue-600 text-sm font-medium transition-colors">
            <?= ikon('aspirasi') ?> Kelola Aspirasi
        </a>
    </div>
</div>

<!-- Kartu Statistik -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    <?php foreach ($kartu as $k): ?>
        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
            <div class="flex justify-between items-start mb-2">
                <p class="text-sm font-medium text-slate-500"><?= e($k['label']) ?></p>
                <div class="p-2 rounded-lg <?= $k['warna'] ?>">
                    <?= ikon($k['ikon'], 'w-5 h-5') ?>
                </div>
            </div>
            <h3 class="text-3xl font-semibold text-slate-900 mb-1"><?= $k['nilai'] ?></h3>
            <p class="text-xs text-slate-400"><?= e($k['catatan']) ?></p>
        </div>
    <?php endforeach; ?>
</div>

<!-- Tabel Data -->
<div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
    <div class="px-6 py-4 border-b border-slate-200 flex justify-between items-center">
        <h2 class="text-base font-semibold text-slate-900">Aspirasi Terbaru</h2>
        <a href="<?= e(admin_url('/admin/aspirasi.php')) ?>" class="text-sm font-medium text-blue-600 hover:text-blue-700">Lihat semua</a>
    </div>
    
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm min-w-[40rem]">
            <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                <tr>
                    <th class="px-6 py-3 font-medium">Judul</th>
                    <th class="px-6 py-3 font-medium">Pengirim</th>
                    <th class="px-6 py-3 font-medium">Kategori</th>
                    <th class="px-6 py-3 font-medium">Status</th>
                    <th class="px-6 py-3 font-medium">Tanggal</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
                <?php if (!$terbaru): ?>
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center justify-center">
                                <div class="h-12 w-12 rounded-full bg-slate-100 flex items-center justify-center text-slate-400 mb-3">
                                    <?= ikon('aspirasi', 'w-6 h-6') ?>
                                </div>
                                <h3 class="text-sm font-medium text-slate-900">Belum ada aspirasi</h3>
                                <p class="text-sm text-slate-500 mt-1">Aspirasi yang dikirim mahasiswa akan muncul di sini.</p>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($terbaru as $a): ?>
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-6 py-4 font-medium text-slate-900"><?= e($a['judul_aspirasi']) ?></td>
                            <td class="px-6 py-4 text-slate-600">
                                <?= $a['is_anonim'] ? '<span class="text-slate-400 italic">Anonim</span>' : e($a['nama_lengkap']) ?>
                            </td>
                            <td class="px-6 py-4 text-slate-600"><?= e($a['nama_kategori'] ?? '-') ?></td>
                            <td class="px-6 py-4">
                                <?php 
                                    $statusLabel = strtolower($a['status']);
                                    $bClass = 'bg-slate-100 text-slate-800';
                                    if ($statusLabel === 'menunggu') $bClass = 'bg-amber-100 text-amber-800';
                                    if ($statusLabel === 'ditinjau') $bClass = 'bg-blue-100 text-blue-800';
                                    if ($statusLabel === 'selesai') $bClass = 'bg-emerald-100 text-emerald-800';
                                ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium capitalize <?= $bClass ?>">
                                    <?= e($a['status']) ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-slate-500">
                                <?= e(date('d M Y H:i', strtotime($a['tanggal_kirim']))) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../includes/admin_footer.php'; ?>