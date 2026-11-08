<?php
require_once __DIR__ . '/../../includes/auth_admin.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/upload_gambar.php';
require_once __DIR__ . '/../../config/database.php'; // menyediakan $pdo

$id = (int) ($_GET['id'] ?? 0);

// Ambil berita yang akan diedit. Kalau tidak ada, hentikan halaman.
$stmt = $pdo->prepare('SELECT id_berita, judul_berita, isi_berita, id_kategori, gambar_url FROM berita WHERE id_berita = ?');
$stmt->execute([$id]);
$berita = $stmt->fetch();

if (!$berita) {
    http_response_code(404);
    exit('Berita tidak ditemukan. <a href="index.php">Kembali ke daftar</a>');
}

$kategori = $pdo->query('SELECT id_kategori, nama_kategori FROM kategori ORDER BY nama_kategori')->fetchAll();
$idKategoriValid = array_map('strval', array_column($kategori, 'id_kategori'));

// Nilai awal form diambil dari database.
$nilai = [
    'judul'      => $berita['judul_berita'],
    'kategori'   => (string) $berita['id_kategori'],
    'isi'        => $berita['isi_berita'],
    'gambar_url' => $berita['gambar_url'] ?? '',
];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $nilai['judul']    = trim($_POST['judul'] ?? '');
    $nilai['kategori'] = trim((string) ($_POST['kategori'] ?? ''));
    $nilai['isi']      = trim($_POST['isi'] ?? '');

    // Validasi sama dengan tambah.php.
    if ($nilai['judul'] === '' || mb_strlen($nilai['judul']) > 255) {
        $errors[] = 'Judul wajib diisi, maksimal 255 karakter.';
    }
    if ($nilai['isi'] === '') {
        $errors[] = 'Isi berita wajib diisi.';
    }
    if (!in_array($nilai['kategori'], $idKategoriValid, true)) {
        $errors[] = 'Kategori tidak valid.';
    }

    // Gambar baru bersifat opsional. Jika tidak diunggah, gambar lama tetap dipakai.
    [$namaGambarBaru, $errorGambar] = upload_gambar($_FILES['gambar'] ?? ['error' => UPLOAD_ERR_NO_FILE]);
    if ($errorGambar !== null) {
        $errors[] = $errorGambar;
    }

    if (!$errors) {
        $gambarFinal = $namaGambarBaru ?? $berita['gambar_url'];

        $stmt = $pdo->prepare(
            'UPDATE berita
                SET id_kategori = ?, judul_berita = ?, isi_berita = ?, gambar_url = ?
              WHERE id_berita = ?'
        );
        $stmt->execute([
            (int) $nilai['kategori'],
            $nilai['judul'],
            $nilai['isi'],
            $gambarFinal,
            $id,
        ]);

        // Kalau gambar diganti, file gambar lama dihapus agar tidak menumpuk.
        if ($namaGambarBaru !== null) {
            hapus_gambar($berita['gambar_url']);
        }

        header('Location: index.php?pesan=edit');
        exit;
    }

    // Validasi gagal: buang gambar baru yang sudah terlanjur diunggah.
    hapus_gambar($namaGambarBaru);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Berita - KabarSih</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 text-slate-800">
<?php include __DIR__ . '/../../includes/admin_nav.php'; ?>
<main class="max-w-6xl mx-auto px-4 py-6">
    <?php
    $action = 'edit.php?id=' . $id;
    $judulHalaman = 'Edit Berita';
    include __DIR__ . '/../../includes/form_berita.php';
    ?>
</main>
</body>
</html>