<?php
require_once __DIR__ . '/../includes/auth_admin.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../config/database.php'; // menyediakan $pdo

// Daftar status yang diizinkan, sama dengan nilai ENUM di kabarsi.sql.
$daftarStatus = ['menunggu' => 'Menunggu', 'ditinjau' => 'Ditinjau', 'selesai' => 'Selesai'];

// Pesan singkat setelah status diubah.
$pesan = isset($_GET['pesan']) && $_GET['pesan'] === 'status' ? 'Status aspirasi berhasil diubah.' : '';

// Ambil semua aspirasi. Pengirim yang anonim tidak ditampilkan namanya.
$daftarAspirasi = $pdo->query(
    'SELECT a.id_aspirasi, a.judul_aspirasi, a.status, a.tanggal_kirim, a.is_anonim,
            u.nama_lengkap, k.nama_kategori
       FROM aspirasi a
       JOIN users u ON u.id_user = a.id_user
       JOIN kategori k ON k.id_kategori = a.id_kategori
      ORDER BY a.tanggal_kirim DESC'
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Aspirasi - KabarSih</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 text-slate-800">
<?php include __DIR__ . '/../includes/admin_nav.php'; ?>

<main class="max-w-6xl mx-auto px-4 py-6">
    <h1 class="text-2xl font-bold mb-4">Kelola Aspirasi</h1>

    <?php if ($pesan !== ''): ?>
        <div class="bg-emerald-100 text-emerald-800 rounded p-3 mb-4"><?= e($pesan) ?></div>
    <?php endif; ?>

    <div class="bg-white rounded-lg shadow overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-100 text-left">
                <tr>
                    <th class="p-3">Judul</th>
                    <th class="p-3">Kategori</th>
                    <th class="p-3">Pengirim</th>
                    <th class="p-3">Tanggal</th>
                    <th class="p-3">Status</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$daftarAspirasi): ?>
                <tr><td colspan="5" class="p-4 text-center text-slate-500">Belum ada aspirasi.</td></tr>
            <?php endif; ?>
            <?php foreach ($daftarAspirasi as $a): ?>
                <tr class="border-t align-top">
                    <td class="p-3 font-medium"><?= e($a['judul_aspirasi']) ?></td>
                    <td class="p-3"><?= e($a['nama_kategori']) ?></td>
                    <td class="p-3"><?= $a['is_anonim'] ? '<em>Anonim</em>' : e($a['nama_lengkap']) ?></td>
                    <td class="p-3"><?= e(date('d M Y H:i', strtotime($a['tanggal_kirim']))) ?></td>
                    <td class="p-3">
                        <form method="POST" action="ubah_status.php" class="flex gap-2 items-center">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id_aspirasi" value="<?= (int) $a['id_aspirasi'] ?>">
                            <select name="status" class="border rounded px-2 py-1">
                                <?php foreach ($daftarStatus as $nilaiStatus => $labelStatus): ?>
                                    <option value="<?= e($nilaiStatus) ?>" <?= $a['status'] === $nilaiStatus ? 'selected' : '' ?>>
                                        <?= e($labelStatus) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="bg-teal-600 text-white px-3 py-1 rounded hover:bg-teal-700">Simpan</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</main>
</body>
</html>