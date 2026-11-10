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
// 1. PROSES FORM: TAMBAH BROADCAST SOS BARU
// ==========================================
if (isset($_POST['tambah_sos'])) {
    $gol_darah        = trim($_POST['gol_darah']);
    $rhesus           = trim($_POST['rhesus']);
    $rs_peminta       = trim($_POST['rs_peminta']);
    $kebutuhan_kantong= (int)$_POST['kebutuhan_kantong'];
    $kontak_darurat   = trim($_POST['kontak_darurat']);

    if (!empty($gol_darah) && !empty($rs_peminta) && $kebutuhan_kantong > 0 && !empty($kontak_darurat)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO sos_broadcast (gol_darah, rhesus, rs_peminta, kebutuhan_kantong, kontak_darurat, status_aktif, created_at) 
                                   VALUES (:gol_darah, :rhesus, :rs_peminta, :kebutuhan_kantong, :kontak_darurat, 'aktif', NOW())");
            $stmt->execute([
                ':gol_darah'        => $gol_darah,
                ':rhesus'           => $rhesus,
                ':rs_peminta'       => $rs_peminta,
                ':kebutuhan_kantong'=> $kebutuhan_kantong,
                ':kontak_darurat'   => $kontak_darurat
            ]);
            $pesan_sukses = "Pengumuman SOS Darurat berhasil dipublikasikan!";
        } catch (PDOException $e) {
            $pesan_gagal = "Gagal menyimpan data: " . $e->getMessage();
        }
    } else {
        $pesan_gagal = "Mohon isi semua kolom dengan benar!";
    }
}

// ==========================================
// 2. PROSES UPDATE: UBAH STATUS (AKTIF / SELESAI)
// ==========================================
if (isset($_GET['action']) && $_GET['action'] === 'toggle_status' && isset($_GET['id'])) {
    $id_sos = (int)$_GET['id'];
    $status_baru = $_GET['status'] === 'aktif' ? 'selesai' : 'aktif';

    try {
        $stmt = $pdo->prepare("UPDATE sos_broadcast SET status_aktif = :status WHERE id = :id");
        $stmt->execute([':status' => $status_baru, ':id' => $id_sos]);
        header("Location: sos_darurat.php?msg=updated");
        exit();
    } catch (PDOException $e) {
        $pesan_gagal = "Gagal memperbarui status: " . $e->getMessage();
    }
}

// ==========================================
// 3. PROSES HAPUS: HAPUS BROADCAST SOS
// ==========================================
if (isset($_GET['action']) && $_GET['action'] === 'hapus' && isset($_GET['id'])) {
    $id_sos = (int)$_GET['id'];

    try {
        $stmt = $pdo->prepare("DELETE FROM sos_broadcast WHERE id = :id");
        $stmt->execute([':id' => $id_sos]);
        header("Location: sos_darurat.php?msg=deleted");
        exit();
    } catch (PDOException $e) {
        $pesan_gagal = "Gagal menghapus data: " . $e->getMessage();
    }
}

// Tangkap pesan dari redirect GET
if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'updated') $pesan_sukses = "Status pengumuman SOS berhasil diubah!";
    if ($_GET['msg'] === 'deleted') $pesan_sukses = "Pengumuman SOS berhasil dihapus!";
}

// ==========================================
// 4. QUERY READ: AMBIL DAFTAR BROADCAST SOS
// ==========================================
try {
    $stmt = $pdo->query("SELECT * FROM sos_broadcast ORDER BY status_aktif ASC, created_at DESC");
    $daftar_sos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $daftar_sos = [];
    $pesan_gagal = "Gagal mengambil data: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen SOS Darurat - OneBlood</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50 font-sans text-gray-800 antialiased min-h-screen flex flex-col">

    <!-- Header / Navbar Inclusion -->
    <?php include '../includes/header.php'; ?>

    <div class="flex flex-1">
        <!-- Sidebar Inclusion -->
        <?php include '../includes/sidebar.php'; ?>

        <!-- Content Area -->
        <main class="flex-1 p-6 md:p-8 overflow-y-auto">
            <div class="max-w-7xl mx-auto space-y-6">
                
                <!-- Page Title -->
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
                            <i class="fa-solid font-bold fa-triangle-exclamation text-red-600"></i> Broadcast Kebutuhan Darah Darurat (SOS)
                        </h1>
                        <p class="text-sm text-gray-500 mt-1">Kelola pengumuman stok darah kritis yang akan ditampilkan di Landing Page & Dashboard Pendonor.</p>
                    </div>
                </div>

                <!-- Alert Messages -->
                <?php if (!empty($pesan_sukses)): ?>
                    <div class="p-4 bg-emerald-50 border-l-4 border-emerald-500 text-emerald-800 rounded shadow-sm flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <i class="fa-solid fa-circle-check"></i>
                            <span><?= htmlspecialchars($pesan_sukses); ?></span>
                        </div>
                        <button onclick="this.parentElement.remove()" class="text-emerald-600 hover:text-emerald-900">&times;</button>
                    </div>
                <?php endif; ?>

                <?php if (!empty($pesan_gagal)): ?>
                    <div class="p-4 bg-rose-50 border-l-4 border-rose-500 text-rose-800 rounded shadow-sm flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <i class="fa-solid fa-circle-exclamation"></i>
                            <span><?= htmlspecialchars($pesan_gagal); ?></span>
                        </div>
                        <button onclick="this.parentElement.remove()" class="text-rose-600 hover:text-rose-900">&times;</button>
                    </div>
                <?php endif; ?>

                <!-- Grid Form Input & Info -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    
                    <!-- Form Input Broadcast SOS -->
                    <div class="lg:col-span-1 bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2 border-b pb-3">
                            <i class="fa-solid fa-bullhorn text-red-600"></i> Buat Pengumuman SOS
                        </h2>
                        
                        <form action="sos_darurat.php" method="POST" class="space-y-4">
                            <div>
                                <label class="block text-xs font-semibold uppercase text-gray-600 mb-1">Golongan Darah & Rhesus</label>
                                <div class="grid grid-cols-2 gap-2">
                                    <select name="gol_darah" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:outline-none text-sm">
                                        <option value="">-- Gol. Darah --</option>
                                        <option value="A">A</option>
                                        <option value="B">B</option>
                                        <option value="AB">AB</option>
                                        <option value="O">O</option>
                                    </select>
                                    <select name="rhesus" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:outline-none text-sm">
                                        <option value="+">Positif (+)</option>
                                        <option value="-">Negatif (-)</option>
                                    </select>
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold uppercase text-gray-600 mb-1">Rumah Sakit Peminta</label>
                                <input type="text" name="rs_peminta" placeholder="Contoh: RSUD Dr. H. Abdul Moeloek" required 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:outline-none text-sm">
                            </div>

                            <div>
                                <label class="block text-xs font-semibold uppercase text-gray-600 mb-1">Kebutuhan (Jumlah Kantong)</label>
                                <input type="number" name="kebutuhan_kantong" min="1" placeholder="Contoh: 5" required 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:outline-none text-sm">
                            </div>

                            <div>
                                <label class="block text-xs font-semibold uppercase text-gray-600 mb-1">Kontak Darurat / Penanggung Jawab</label>
                                <input type="text" name="kontak_darurat" placeholder="Contoh: 0812-3456-7890 (Dr. Ahmad)" required 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:outline-none text-sm">
                            </div>

                            <button type="submit" name="tambah_sos" 
                                    class="w-full mt-2 bg-red-600 hover:bg-red-700 text-white font-medium py-2.5 px-4 rounded-lg transition-colors duration-200 flex items-center justify-center gap-2 text-sm shadow">
                                <i class="fa-solid fa-paper-plane"></i> Publikasikan SOS
                            </button>
                        </form>
                    </div>

                    <!-- Tabel Daftar SOS Active & History -->
                    <div class="lg:col-span-2 bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center justify-between border-b pb-3">
                            <span><i class="fa-solid fa-list-check text-gray-600 mr-2"></i>Daftar Broadcast SOS</span>
                            <span class="text-xs bg-gray-100 text-gray-600 px-2.5 py-1 rounded-full font-medium">Total: <?= count($daftar_sos); ?> Pengumuman</span>
                        </h2>

                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-sm border-collapse">
                                <thead>
                                    <tr class="bg-gray-100 text-gray-700 uppercase text-xs">
                                        <th class="p-3 rounded-l-lg">Gol. Darah</th>
                                        <th class="p-3">RS Peminta</th>
                                        <th class="p-3">Kebutuhan</th>
                                        <th class="p-3">Kontak</th>
                                        <th class="p-3">Status</th>
                                        <th class="p-3 text-center rounded-r-lg">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    <?php if (count($daftar_sos) > 0): ?>
                                        <?php foreach ($daftar_sos as $sos): ?>
                                            <tr class="hover:bg-gray-50 transition-colors">
                                                <td class="p-3 font-bold text-gray-900">
                                                    <span class="inline-block px-2 py-1 bg-red-100 text-red-700 rounded text-xs font-black">
                                                        <?= htmlspecialchars($sos['gol_darah'] . $sos['rhesus']); ?>
                                                    </span>
                                                </td>
                                                <td class="p-3 font-medium text-gray-800"><?= htmlspecialchars($sos['rs_peminta']); ?></td>
                                                <td class="p-3 text-gray-600"><?= htmlspecialchars($sos['kebutuhan_kantong']); ?> Kantong</td>
                                                <td class="p-3 text-gray-600 text-xs"><?= htmlspecialchars($sos['kontak_darurat']); ?></td>
                                                <td class="p-3">
                                                    <?php if ($sos['status_aktif'] === 'aktif'): ?>
                                                        <span class="px-2.5 py-1 bg-rose-100 text-rose-700 border border-rose-200 rounded-full text-xs font-semibold animate-pulse flex items-center w-max gap-1">
                                                            <span class="w-1.5 h-1.5 bg-rose-600 rounded-full"></span> Aktif
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="px-2.5 py-1 bg-gray-100 text-gray-600 border border-gray-200 rounded-full text-xs font-semibold flex items-center w-max gap-1">
                                                            <span class="w-1.5 h-1.5 bg-gray-400 rounded-full"></span> Selesai
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="p-3 text-center">
                                                    <div class="flex items-center justify-center gap-2">
                                                        <!-- Toggle Status Button -->
                                                        <a href="sos_darurat.php?action=toggle_status&id=<?= $sos['id']; ?>&status=<?= $sos['status_aktif']; ?>" 
                                                           title="Ubah Status"
                                                           class="p-1.5 rounded-lg border text-xs font-medium transition-colors <?= $sos['status_aktif'] === 'aktif' ? 'border-amber-300 text-amber-700 hover:bg-amber-50' : 'border-emerald-300 text-emerald-700 hover:bg-emerald-50'; ?>">
                                                            <i class="fa-solid <?= $sos['status_aktif'] === 'aktif' ? 'fa-circle-check' : 'fa-rotate-left'; ?>"></i>
                                                        </a>

                                                        <!-- Hapus Button -->
                                                        <a href="sos_darurat.php?action=hapus&id=<?= $sos['id']; ?>" 
                                                           onclick="return confirm('Apakah Anda yakin ingin menghapus pengumuman SOS ini?');"
                                                           title="Hapus"
                                                           class="p-1.5 rounded-lg border border-red-200 text-red-600 hover:bg-red-50 text-xs transition-colors">
                                                            <i class="fa-solid fa-trash-can"></i>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="p-6 text-center text-gray-400 text-sm">
                                                Belum ada pengumuman SOS darurat yang dipublikasikan.
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

    <!-- Footer Inclusion -->
    <?php include '../includes/footer.php'; ?>
</body>
</html>