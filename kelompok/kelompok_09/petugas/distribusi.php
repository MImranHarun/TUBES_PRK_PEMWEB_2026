<?php
session_start();
require_once '../config/database.php';
require_once '../config/auth_check.php';

/** @var PDO $pdo */

// Proteksi Halaman: Hanya Petugas Medis dan Admin yang bisa mengakses
cekRole(['petugas', 'admin']);

$pesan_sukses = "";
$pesan_gagal  = "";

// ==========================================
// 1. PROSES FORM: PROSES DISTRIBUSI & POTONG STOK (TRANSAKSI PDO)
// ==========================================
if (isset($_POST['proses_distribusi'])) {
    $kantong_id      = (int)$_POST['kantong_id'];
    $nama_pasien     = trim($_POST['nama_pasien']);
    $rs_tujuan       = trim($_POST['rs_tujuan']);
    $dokter_pengirim = trim($_POST['dokter_pengirim']);
    $petugas_id      = $_SESSION['user_id'] ?? 1; // Mengambil ID Petugas yang sedang login

    if ($kantong_id > 0 && !empty($nama_pasien) && !empty($rs_tujuan) && !empty($dokter_pengirim)) {
        try {
            // Mulai Database Transaction agar data tersimpan konsisten
            $pdo->beginTransaction();

            // Cek ketersediaan kantong darah terlebih dahulu
            $stmt_check = $pdo->prepare("SELECT id, status FROM kantong_darah WHERE id = :id AND status = 'tersedia' FOR UPDATE");
            $stmt_check->execute([':id' => $kantong_id]);
            $kantong = $stmt_check->fetch();

            if (!$kantong) {
                throw new Exception("Kantong darah yang dipilih tidak tersedia atau sudah distibusikan!");
            }

            // A. Insert data ke tabel distribusi_darah
            $stmt_insert = $pdo->prepare("INSERT INTO distribusi_darah (kantong_id, petugas_id, nama_pasien, rs_tujuan, dokter_pengirim, tgl_keluar) 
                                         VALUES (:kantong_id, :petugas_id, :nama_pasien, :rs_tujuan, :dokter_pengirim, NOW())");
            $stmt_insert->execute([
                ':kantong_id'      => $kantong_id,
                ':petugas_id'      => $petugas_id,
                ':nama_pasien'     => $nama_pasien,
                ':rs_tujuan'       => $rs_tujuan,
                ':dokter_pengirim' => $dokter_pengirim
            ]);

            // B. Update status kantong_darah milik Anggota 4 menjadi 'keluar' (Potong Stok)
            $stmt_update = $pdo->prepare("UPDATE kantong_darah SET status = 'keluar' WHERE id = :id");
            $stmt_update->execute([':id' => $kantong_id]);

            // Commit Transaksi jika semua query berhasil
            $pdo->commit();
            $pesan_sukses = "Pengeluaran darah berhasil diproses dan stok kantong otomatis diperbarui!";

        } catch (Exception $e) {
            // Rollback jika ada kegagalan
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $pesan_gagal = "Gagal memproses distribusi: " . $e->getMessage();
        }
    } else {
        $pesan_gagal = "Mohon lengkapi semua data formulir distribusi!";
    }
}

// ==========================================
// 2. QUERY READ: AMBIL KANTONG DARAH YANG TERSEDIA
// ==========================================
try {
    // Hanya mengambil kantong yang statusnya 'tersedia' dan belum kedaluwarsa
    $stmt_stok = $pdo->query("SELECT id, kode_kantong, gol_darah, rhesus, komponen, tgl_expired 
                             FROM kantong_darah 
                             WHERE status = 'tersedia' AND tgl_expired > CURDATE() 
                             ORDER BY tgl_expired ASC");
    $kantong_tersedia = $stmt_stok->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $kantong_tersedia = [];
}

// ==========================================
// 3. QUERY READ: AMBIL RIWAYAT DISTRIBUSI DARAH
// ==========================================
try {
    $query_riwayat = "SELECT d.*, k.kode_kantong, k.gol_darah, k.rhesus, k.komponen, u.nama AS nama_petugas
                      FROM distribusi_darah d
                      JOIN kantong_darah k ON d.kantong_id = k.id
                      LEFT JOIN users u ON d.petugas_id = u.id
                      ORDER BY d.tgl_keluar DESC";
    $stmt_riwayat = $pdo->query($query_riwayat);
    $riwayat_distribusi = $stmt_riwayat->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $riwayat_distribusi = [];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Distribusi & Pengeluaran Darah - OneBlood</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
                
                <!-- Page Title -->
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
                            <i class="fa-solid fa-truck-medical text-blue-600"></i> Distribusi & Pengeluaran Darah
                        </h1>
                        <p class="text-sm text-gray-500 mt-1">Catat permintaan darah keluar untuk Rumah Sakit & Pasien. Stok kantong darah akan terpotong secara otomatis.</p>
                    </div>
                </div>

                <!-- Alert Messages -->
                <?php if (!empty($pesan_sukses)): ?>
                    <div class="p-4 bg-emerald-50 border-l-4 border-emerald-500 text-emerald-800 rounded shadow-sm flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <i class="fa-solid fa-circle-check"></i>
                            <span><?= e($pesan_sukses); ?></span>
                        </div>
                        <button onclick="this.parentElement.remove()" class="text-emerald-600 hover:text-emerald-900">&times;</button>
                    </div>
                <?php endif; ?>

                <?php if (!empty($pesan_gagal)): ?>
                    <div class="p-4 bg-rose-50 border-l-4 border-rose-500 text-rose-800 rounded shadow-sm flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <i class="fa-solid fa-circle-exclamation"></i>
                            <span><?= e($pesan_gagal); ?></span>
                        </div>
                        <button onclick="this.parentElement.remove()" class="text-rose-600 hover:text-rose-900">&times;</button>
                    </div>
                <?php endif; ?>

                <!-- Form Input & Grid Dashboard -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    
                    <!-- Form Input Distribusi -->
                    <div class="lg:col-span-1 bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2 border-b pb-3">
                            <i class="fa-solid fa-right-from-bracket text-blue-600"></i> Form Darah Keluar
                        </h2>
                        
                        <form action="distribusi.php" method="POST" class="space-y-4">
                            <div>
                                <label class="block text-xs font-semibold uppercase text-gray-600 mb-1">Pilih Kantong Darah (Tersedia)</label>
                                <select name="kantong_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none text-sm">
                                    <option value="">-- Pilih Kantong Darah --</option>
                                    <?php foreach ($kantong_tersedia as $k): ?>
                                        <option value="<?= $k['id']; ?>">
                                            [<?= e($k['kode_kantong']); ?>] Gol: <?= e($k['gol_darah'] . $k['rhesus']); ?> (<?= e($k['komponen']); ?>) - Exp: <?= tgl_indo($k['tgl_expired']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (count($kantong_tersedia) === 0): ?>
                                    <p class="text-xs text-rose-500 mt-1">* Tidak ada kantong darah yang tersedia di gudang saat ini.</p>
                                <?php endif; ?>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold uppercase text-gray-600 mb-1">Nama Pasien Penerima</label>
                                <input type="text" name="nama_pasien" placeholder="Contoh: Bpk. Supriadi" required 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none text-sm">
                            </div>

                            <div>
                                <label class="block text-xs font-semibold uppercase text-gray-600 mb-1">Rumah Sakit Tujuan</label>
                                <input type="text" name="rs_tujuan" placeholder="Contoh: RS Advent Bandar Lampung" required 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none text-sm">
                            </div>

                            <div>
                                <label class="block text-xs font-semibold uppercase text-gray-600 mb-1">Dokter Penanggung Jawab / Pengirim</label>
                                <input type="text" name="dokter_pengirim" placeholder="Contoh: dr. Rizky, Sp.PD" required 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none text-sm">
                            </div>

                            <button type="submit" name="proses_distribusi" <?= count($kantong_tersedia) === 0 ? 'disabled' : ''; ?>
                                    class="w-full mt-2 bg-blue-600 hover:bg-blue-700 disabled:bg-gray-300 text-white font-medium py-2.5 px-4 rounded-lg transition-colors duration-200 flex items-center justify-center gap-2 text-sm shadow">
                                <i class="fa-solid fa-box-open"></i> Diproses & Keluarkan Darah
                            </button>
                        </form>
                    </div>

                    <!-- Tabel Riwayat Distribusi -->
                    <div class="lg:col-span-2 bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center justify-between border-b pb-3">
                            <span><i class="fa-solid fa-clock-rotate-left text-gray-600 mr-2"></i>Riwayat Darah Keluar</span>
                            <span class="text-xs bg-blue-50 text-blue-700 px-2.5 py-1 rounded-full font-medium">Total: <?= count($riwayat_distribusi); ?> Transaksi</span>
                        </h2>

                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-sm border-collapse">
                                <thead>
                                    <tr class="bg-gray-100 text-gray-700 uppercase text-xs">
                                        <th class="p-3 rounded-l-lg">Kode Kantong</th>
                                        <th class="p-3">Gol. Darah</th>
                                        <th class="p-3">Nama Pasien</th>
                                        <th class="p-3">RS Tujuan</th>
                                        <th class="p-3">Dokter</th>
                                        <th class="p-3 rounded-r-lg">Tanggal Keluar</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    <?php if (count($riwayat_distribusi) > 0): ?>
                                        <?php foreach ($riwayat_distribusi as $row): ?>
                                            <tr class="hover:bg-gray-50 transition-colors">
                                                <td class="p-3 font-mono font-semibold text-blue-600"><?= e($row['kode_kantong']); ?></td>
                                                <td class="p-3">
                                                    <span class="inline-block px-2 py-0.5 bg-red-100 text-red-700 rounded text-xs font-black">
                                                        <?= e($row['gol_darah'] . $row['rhesus']); ?>
                                                    </span>
                                                    <span class="text-xs text-gray-500 block"><?= e($row['komponen']); ?></span>
                                                </td>
                                                <td class="p-3 font-medium text-gray-800"><?= e($row['nama_pasien']); ?></td>
                                                <td class="p-3 text-gray-600 text-xs"><?= e($row['rs_tujuan']); ?></td>
                                                <td class="p-3 text-gray-600 text-xs"><?= e($row['dokter_pengirim']); ?></td>
                                                <td class="p-3 text-gray-500 text-xs"><?= date('d/m/Y H:i', strtotime($row['tgl_keluar'])); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="p-6 text-center text-gray-400 text-sm">
                                                Belum ada transaksi distribusi darah keluar.
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
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