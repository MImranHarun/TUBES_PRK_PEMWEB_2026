<?php
session_start();
require_once '../config/database.php';
require_once '../config/auth_check.php';

/** @var PDO $pdo */

// Proteksi Halaman: Hanya Admin dan Petugas Medis yang bisa mengakses
cekRole(['admin', 'petugas']);

// Filter Bulan & Tahun (Default: Bulan & Tahun Saat Ini)
$bulan_pilihan = isset($_GET['bulan']) ? (int)$_GET['bulan'] : (int)date('m');
$tahun_pilihan = isset($_GET['tahun']) ? (int)$_GET['tahun'] : (int)date('Y');

// ==========================================
// 1. QUERY ANALITIK & STATISTIK BULANAN
// ==========================================
try {
    // Total Kantong Darah Masuk (Bulan Ini)
    $stmt_masuk = $pdo->prepare("SELECT COUNT(*) FROM kantong_darah WHERE MONTH(tgl_ambil) = :bulan AND YEAR(tgl_ambil) = :tahun");
    $stmt_masuk->execute([':bulan' => $bulan_pilihan, ':tahun' => $tahun_pilihan]);
    $total_masuk = $stmt_masuk->fetchColumn();

    // Total Kantong Darah Keluar (Bulan Ini)
    $stmt_keluar = $pdo->prepare("SELECT COUNT(*) FROM distribusi_darah WHERE MONTH(tgl_keluar) = :bulan AND YEAR(tgl_keluar) = :tahun");
    $stmt_keluar->execute([':bulan' => $bulan_pilihan, ':tahun' => $tahun_pilihan]);
    $total_keluar = $stmt_keluar->fetchColumn();

    // Total Stok Aktif Tersedia Saat Ini
    $stmt_stok = $pdo->query("SELECT COUNT(*) FROM kantong_darah WHERE status = 'tersedia' AND tgl_expired > CURDATE()");
    $total_tersedia = $stmt_stok->fetchColumn();

    // Rekapitulasi Stok Aktif Per Golongan Darah
    $stmt_gol = $pdo->query("SELECT gol_darah, rhesus, COUNT(*) AS jumlah 
                             FROM kantong_darah 
                             WHERE status = 'tersedia' AND tgl_expired > CURDATE() 
                             GROUP BY gol_darah, rhesus");
    $rekap_gol = $stmt_gol->fetchAll(PDO::FETCH_ASSOC);

    // Format array agar mudah dipanggil di UI
    $stok_per_gol = [];
    foreach ($rekap_gol as $row) {
        $stok_per_gol[$row['gol_darah'] . $row['rhesus']] = $row['jumlah'];
    }

} catch (PDOException $e) {
    $total_masuk = $total_keluar = $total_tersedia = 0;
    $stok_per_gol = [];
}

// ==========================================
// 2. QUERY DAFTAR RIWAYAT DISTRIBUSI (DETAIL LAPORAN)
// ==========================================
try {
    $stmt_laporan = $pdo->prepare("SELECT d.*, k.kode_kantong, k.gol_darah, k.rhesus, k.komponen, u.nama AS nama_petugas
                                   FROM distribusi_darah d
                                   JOIN kantong_darah k ON d.kantong_id = k.id
                                   LEFT JOIN users u ON d.petugas_id = u.id
                                   WHERE MONTH(d.tgl_keluar) = :bulan AND YEAR(d.tgl_keluar) = :tahun
                                   ORDER BY d.tgl_keluar DESC");
    $stmt_laporan->execute([':bulan' => $bulan_pilihan, ':tahun' => $tahun_pilihan]);
    $laporan_distribusi = $stmt_laporan->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $laporan_distribusi = [];
}

$nama_bulan = [
    1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan & Dashboard Analitik - OneBlood</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- STYLING KHUSUS CETAK LAPORAN (PRINT PDF) -->
    <style>
        @media print {
            /* Sembunyikan elemen navigasi saat dicetak */
            header, sidebar, footer, .no-print {
                display: none !important;
            }
            body {
                background-color: white !important;
                color: black !important;
                font-size: 12pt;
            }
            main {
                padding: 0 !important;
                margin: 0 !important;
                overflow: visible !important;
            }
            .print-header {
                display: block !important;
            }
            .border, .shadow-sm {
                box-shadow: none !important;
                border-color: #ccc !important;
            }
        }
        .print-header {
            display: none;
        }
    </style>
</head>
<body class="bg-gray-50 font-sans text-gray-800 antialiased min-h-screen flex flex-col">

    <!-- Header / Navbar -->
    <?php include '../includes/header.php'; ?>

    <div class="flex flex-1">
        <!-- Sidebar -->
        <?php include '../includes/sidebar.php'; ?>

        <!-- Content Area -->
        <main class="flex-1 p-6 md:p-8 overflow-y-auto">
            <div class="max-w-7xl mx-auto space-y-6">
                
                <!-- Kop Surat Cetak (Hanya Muncul Saat Print/PDF) -->
                <div class="print-header text-center border-b-2 border-gray-800 pb-4 mb-6">
                    <h1 class="text-2xl font-bold uppercase tracking-wide">Laporan Distribusi & Stok Darah OneBlood</h1>
                    <p class="text-sm">Unit Donor Darah — Fakultas Kedokteran | Periode: <?= $nama_bulan[$bulan_pilihan] . ' ' . $tahun_pilihan; ?></p>
                    <p class="text-xs text-gray-500">Dicetak pada: <?= date('d/m/Y H:i:s'); ?></p>
                </div>

                <!-- Title & Filter (Hilang Saat Cetak) -->
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 no-print">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
                            <i class="fa-solid fa-chart-pie text-emerald-600"></i> Dashboard Analitik & Laporan
                        </h1>
                        <p class="text-sm text-gray-500 mt-1">Rekapitulasi sirkulasi kantong darah dan laporan distribusi resmi.</p>
                    </div>

                    <!-- Actions & Filter -->
                    <div class="flex items-center gap-3">
                        <form action="laporan.php" method="GET" class="flex items-center gap-2">
                            <select name="bulan" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none">
                                <?php foreach ($nama_bulan as $k => $v): ?>
                                    <option value="<?= $k; ?>" <?= $k === $bulan_pilihan ? 'selected' : ''; ?>><?= $v; ?></option>
                                <?php endforeach; ?>
                            </select>
                            <select name="tahun" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none">
                                <?php for ($y = date('Y'); $y >= date('Y') - 3; $y--): ?>
                                    <option value="<?= $y; ?>" <?= $y === $tahun_pilihan ? 'selected' : ''; ?>><?= $y; ?></option>
                                <?php endfor; ?>
                            </select>
                            <button type="submit" class="bg-gray-800 hover:bg-gray-900 text-white px-3 py-2 rounded-lg text-sm font-medium transition-colors">
                                Filter
                            </button>
                        </form>

                        <!-- Tombol Print PDF -->
                        <button onclick="window.print()" class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-lg text-sm font-medium shadow flex items-center gap-2 transition-colors">
                            <i class="fa-solid fa-print"></i> Cetak PDF / Laporan
                        </button>
                    </div>
                </div>

                <!-- 3 Card Statistik Ringkasan -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm flex items-center justify-between">
                        <div>
                            <p class="text-xs font-semibold text-gray-500 uppercase">Darah Masuk (Bulan Ini)</p>
                            <h3 class="text-3xl font-extrabold text-emerald-600 mt-1"><?= $total_masuk; ?> <span class="text-xs font-normal text-gray-500">Kantong</span></h3>
                        </div>
                        <div class="p-3 bg-emerald-50 text-emerald-600 rounded-lg text-2xl">
                            <i class="fa-solid fa-box-archive"></i>
                        </div>
                    </div>

                    <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm flex items-center justify-between">
                        <div>
                            <p class="text-xs font-semibold text-gray-500 uppercase">Darah Keluar (Bulan Ini)</p>
                            <h3 class="text-3xl font-extrabold text-blue-600 mt-1"><?= $total_keluar; ?> <span class="text-xs font-normal text-gray-500">Kantong</span></h3>
                        </div>
                        <div class="p-3 bg-blue-50 text-blue-600 rounded-lg text-2xl">
                            <i class="fa-solid fa-truck-ramping"></i>
                        </div>
                    </div>

                    <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm flex items-center justify-between">
                        <div>
                            <p class="text-xs font-semibold text-gray-500 uppercase">Total Stok Aktif Gudang</p>
                            <h3 class="text-3xl font-extrabold text-red-600 mt-1"><?= $total_tersedia; ?> <span class="text-xs font-normal text-gray-500">Kantong</span></h3>
                        </div>
                        <div class="p-3 bg-red-50 text-red-600 rounded-lg text-2xl">
                            <i class="fa-solid fa-droplet"></i>
                        </div>
                    </div>
                </div>

                <!-- Grid Rekapitulasi Per Golongan Darah -->
                <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
                    <h2 class="text-base font-semibold text-gray-900 mb-4 flex items-center gap-2 border-b pb-3">
                        <i class="fa-solid fa-layer-group text-red-600"></i> Status Ketersediaan 8 Golongan Darah
                    </h2>

                    <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-8 gap-3 text-center">
                        <?php 
                        $gol_list = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
                        foreach ($gol_list as $g):
                            $jumlah = $stok_per_gol[$g] ?? 0;
                            $status_bg = $jumlah > 10 ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : ($jumlah > 3 ? 'bg-amber-50 text-amber-700 border-amber-200' : 'bg-rose-50 text-rose-700 border-rose-200');
                        ?>
                            <div class="p-3 rounded-lg border <?= $status_bg; ?>">
                                <span class="block text-sm font-black"><?= $g; ?></span>
                                <span class="block text-lg font-bold mt-1"><?= $jumlah; ?></span>
                                <span class="block text-[10px] uppercase tracking-wider mt-0.5"><?= $jumlah > 10 ? 'Aman' : ($jumlah > 3 ? 'Menipis' : 'Kritis'); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Tabel Detail Laporan Distribusi Bulan Ini -->
                <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
                    <h2 class="text-base font-semibold text-gray-900 mb-4 flex items-center justify-between border-b pb-3">
                        <span><i class="fa-solid fa-file-invoice text-gray-600 mr-2"></i>Rincian Distribusi Darah Outgoing</span>
                        <span class="text-xs text-gray-500">Periode: <?= $nama_bulan[$bulan_pilihan] . ' ' . $tahun_pilihan; ?></span>
                    </h2>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm border-collapse">
                            <thead>
                                <tr class="bg-gray-100 text-gray-700 uppercase text-xs">
                                    <th class="p-3 border">No</th>
                                    <th class="p-3 border">Kode Kantong</th>
                                    <th class="p-3 border">Gol. Darah</th>
                                    <th class="p-3 border">Nama Pasien</th>
                                    <th class="p-3 border">RS Tujuan</th>
                                    <th class="p-3 border">Dokter Pengirim</th>
                                    <th class="p-3 border">Petugas Memproses</th>
                                    <th class="p-3 border">Tanggal Keluar</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <?php if (count($laporan_distribusi) > 0): ?>
                                    <?php $no = 1; foreach ($laporan_distribusi as $row): ?>
                                        <tr class="hover:bg-gray-50">
                                            <td class="p-3 border text-center font-medium"><?= $no++; ?></td>
                                            <td class="p-3 border font-mono font-bold text-blue-600"><?= e($row['kode_kantong']); ?></td>
                                            <td class="p-3 border font-black"><?= e($row['gol_darah'] . $row['rhesus']); ?> (<?= e($row['komponen']); ?>)</td>
                                            <td class="p-3 border"><?= e($row['nama_pasien']); ?></td>
                                            <td class="p-3 border"><?= e($row['rs_tujuan']); ?></td>
                                            <td class="p-3 border"><?= e($row['dokter_pengirim']); ?></td>
                                            <td class="p-3 border text-xs"><?= e($row['nama_petugas'] ?? 'Admin'); ?></td>
                                            <td class="p-3 border text-xs"><?= date('d/m/Y H:i', strtotime($row['tgl_keluar'])); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="p-6 text-center text-gray-400 text-sm border">
                                            Tidak ada transaksi pengeluaran darah pada periode bulan dan tahun ini.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Tanda Tangan Laporan (Hanya Muncul Saat Print/PDF) -->
                    <div class="print-header mt-12 grid grid-cols-2 gap-8 text-center text-sm">
                        <div>
                            <p>Mengetahui,</p>
                            <p class="font-bold mt-1">Kepala Unit Donor Darah</p>
                            <div class="h-16"></div>
                            <p class="font-bold underline">( ______________________ )</p>
                        </div>
                        <div>
                            <p>Bandar Lampung, <?= date('d F Y'); ?></p>
                            <p class="font-bold mt-1">Petugas Penanggung Jawab</p>
                            <div class="h-16"></div>
                            <p class="font-bold underline">( <?= e($_SESSION['nama'] ?? 'Petugas OneBlood'); ?> )</p>
                        </div>
                    </div>

                </div>

            </div>
        </main>
    </div>

    <!-- Footer -->
    <?php include '../includes/footer.php'; ?>
</body>
</html>