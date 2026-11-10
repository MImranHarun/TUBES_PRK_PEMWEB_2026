<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

wajibLogin();

// Inisialisasi variabel untuk form
$jenis       = $_POST['jenis'] ?? 'pengaduan';
$judul       = trim($_POST['judul'] ?? '');
$kategori_id = $_POST['kategori_id'] ?? '';
$lokasi      = trim($_POST['lokasi'] ?? '');
$isi         = trim($_POST['isi'] ?? '');
$is_public   = isset($_POST['is_public']) ? 1 : (empty($_POST) ? 1 : 0);
$errors      = [];

// Ambil daftar kategori dari database
try {
    $stmtKat = $pdo->query("SELECT id, nama_kategori FROM kategori ORDER BY nama_kategori ASC");
    $daftarKategori = $stmtKat->fetchAll();
} catch (PDOException $e) {
    $daftarKategori = [];
}

// Proses jika formulir dikirim lewat metode POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifikasiCsrf();

    // Validasi Jenis Laporan
    $jenisPilihan = ['pengaduan', 'aspirasi'];
    if (!in_array($jenis, $jenisPilihan, true)) {
        $errors[] = 'Jenis laporan harus dipilih: Pengaduan atau Aspirasi.';
    }

    // Validasi Judul
    if ($judul === '') {
        $errors[] = 'Judul laporan wajib diisi.';
    } elseif (mb_strlen($judul) < 5 || mb_strlen($judul) > 255) {
        $errors[] = 'Judul laporan minimal 5 karakter dan maksimal 255 karakter.';
    }

    // Validasi Kategori
    if (empty($kategori_id)) {
        $errors[] = 'Silakan pilih kategori yang sesuai.';
    } else {
        $stmtCekKat = $pdo->prepare("SELECT id FROM kategori WHERE id = :id");
        $stmtCekKat->execute([':id' => $kategori_id]);
        if (!$stmtCekKat->fetch()) {
            $errors[] = 'Kategori yang dipilih tidak valid.';
        }
    }

    // Validasi Lokasi
    if ($lokasi === '') {
        $errors[] = 'Lokasi kejadian atau objek aspirasi wajib diisi.';
    } elseif (mb_strlen($lokasi) > 255) {
        $errors[] = 'Lokasi maksimal 255 karakter.';
    }

    // Validasi Isi Laporan
    if ($isi === '') {
        $errors[] = 'Isi laporan wajib diisi.';
    } elseif (mb_strlen($isi) < 15) {
        $errors[] = 'Isi laporan terlalu singkat, jelaskan minimal 15 karakter.';
    }

    // Validasi Unggah Foto
    $namaFotoDisimpan = null;
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] !== UPLOAD_ERR_NO_FILE) {
        $fileError = $_FILES['foto']['error'];
        $fileSize  = $_FILES['foto']['size'];
        $tmpName   = $_FILES['foto']['tmp_name'];
        $fileName  = $_FILES['foto']['name'];

        if ($fileError !== UPLOAD_ERR_OK) {
            $errors[] = 'Terjadi kesalahan saat mengunggah foto. Silakan coba lagi.';
        } else {
            // Batas maksimal 2 MB
            $maxFileSize = 2 * 1024 * 1024;
            if ($fileSize > $maxFileSize) {
                $errors[] = 'Ukuran foto maksimal adalah 2 MB.';
            }

            // Validasi ekstensi file (JPG, JPEG, PNG)
            $ekstensi = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $ekstensiDiizinkan = ['jpg', 'jpeg', 'png'];

            if (!in_array($ekstensi, $ekstensiDiizinkan, true)) {
                $errors[] = 'Format foto harus berupa JPG, JPEG, atau PNG.';
            }

            // Validasi type asli menggunakan finfo
            if (empty($errors) && function_exists('finfo_open')) {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mimeType = finfo_file($finfo, $tmpName);
                finfo_close($finfo);

                $mimeDiizinkan = ['image/jpeg', 'image/png'];
                if (!in_array($mimeType, $mimeDiizinkan, true)) {
                    $errors[] = 'Berkas yang diunggah bukan format gambar yang valid.';
                }
            }

            // Jika validasi file lolos, buat nama unik acak dan simpan ke folder uploads
            if (empty($errors)) {
                $namaFotoDisimpan = 'laporan_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ekstensi;
                $folderTujuan = __DIR__ . '/../assets/uploads/';

                if (!is_dir($folderTujuan)) {
                    mkdir($folderTujuan, 0755, true);
                }

                $pathTujuan = $folderTujuan . $namaFotoDisimpan;
                if (!move_uploaded_file($tmpName, $pathTujuan)) {
                    $errors[] = 'Gagal menyimpan berkas foto ke server.';
                    $namaFotoDisimpan = null;
                }
            }
        }
    }

    // Jika tidak ada error, simpan ke database
    if (empty($errors)) {
        try {
            $sql = "INSERT INTO laporan (user_id, kategori_id, jenis, judul, isi, lokasi, foto, is_public, status)
                    VALUES (:user_id, :kategori_id, :jenis, :judul, :isi, :lokasi, :foto, :is_public, 'menunggu')";
            
            $stmtInsert = $pdo->prepare($sql);
            $stmtInsert->execute([
                ':user_id'     => $_SESSION['user_id'],
                ':kategori_id' => $kategori_id,
                ':jenis'       => $jenis,
                ':judul'       => $judul,
                ':isi'         => $isi,
                ':lokasi'      => $lokasi,
                ':foto'        => $namaFotoDisimpan,
                ':is_public'   => $is_public
            ]);

            setFlash('success', 'Laporan Anda berhasil dikirim dan saat ini berstatus Menunggu untuk ditinjau admin.');
            header('Location: /user/laporan_saya.php');
            exit;

        } catch (PDOException $e) {
            // Jika penyimpanan database gagal dan foto terlanjur dipindah, hapus foto
            if ($namaFotoDisimpan && file_exists(__DIR__ . '/../assets/uploads/' . $namaFotoDisimpan)) {
                unlink(__DIR__ . '/../assets/uploads/' . $namaFotoDisimpan);
            }
            $errors[] = 'Terjadi kesalahan sistem saat menyimpan laporan. Silakan coba kembali.';
        }
    }
}

$pageTitle = 'Sampaikan Laporan';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="card" style="max-width: 760px; margin: 0 auto;">
    <div style="margin-bottom: 1.5rem; border-bottom: 1px solid var(--color-border); padding-bottom: 0.75rem;">
        <h1 class="card-title" style="margin-bottom: 0.35rem;">Sampaikan Pengaduan atau Aspirasi</h1>
        <p style="color: var(--color-text-light); font-size: 0.95rem;">
            Silakan lengkapi formulir di bawah ini dengan informasi yang jelas.
        </p>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <strong style="display: block; margin-bottom: 0.4rem;">Perhatian! Formulir belum dapat dikirim:</strong>
            <ul style="margin-left: 1.25rem; margin-bottom: 0;">
                <?php foreach ($errors as $error): ?>
                    <li><?= sanitize($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form action="/user/kirim.php" method="POST" enctype="multipart/form-data">
        <?= csrfField() ?>

        <!-- Pilihan Jenis Laporan -->
        <div class="form-group">
            <label class="form-label">Jenis Laporan <span style="color: var(--color-danger);">*</span></label>
            <div style="display: flex; gap: 1.5rem; margin-top: 0.35rem;">
                <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                    <input type="radio" name="jenis" value="pengaduan" <?= ($jenis === 'pengaduan') ? 'checked' : '' ?>>
                    <span><strong>Pengaduan</strong> (Masalah atau keluhan)</span>
                </label>
                <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                    <input type="radio" name="jenis" value="aspirasi" <?= ($jenis === 'aspirasi') ? 'checked' : '' ?>>
                    <span><strong>Aspirasi</strong> (Saran atau usulan)</span>
                </label>
            </div>
        </div>

        <!-- Judul Laporan -->
        <div class="form-group">
            <label for="judul" class="form-label">Judul Laporan <span style="color: var(--color-danger);">*</span></label>
            <input type="text" id="judul" name="judul" class="form-control" 
                   placeholder="Contoh: Kerusakan AC di Ruang Kuliah Gedung A" 
                   value="<?= sanitize($judul) ?>" required maxlength="255">
        </div>

        <!-- Kategori & Lokasi -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
            <div class="form-group">
                <label for="kategori_id" class="form-label">Kategori <span style="color: var(--color-danger);">*</span></label>
                <select id="kategori_id" name="kategori_id" class="form-control" required>
                    <option value="">-- Pilih Kategori --</option>
                    <?php foreach ($daftarKategori as $kat): ?>
                        <option value="<?= (int)$kat['id'] ?>" <?= ((string)$kategori_id === (string)$kat['id']) ? 'selected' : '' ?>>
                            <?= sanitize($kat['nama_kategori']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="lokasi" class="form-label">Lokasi Kejadian <span style="color: var(--color-danger);">*</span></label>
                <input type="text" id="lokasi" name="lokasi" class="form-control" 
                       placeholder="Contoh: Gedung B Lantai 2 FISIP" 
                       value="<?= sanitize($lokasi) ?>" required maxlength="255">
            </div>
        </div>

        <!-- Isi Laporan -->
        <div class="form-group">
            <label for="isi" class="form-label">Isi Laporan / Keterangan Lengkap <span style="color: var(--color-danger);">*</span></label>
            <textarea id="isi" name="isi" class="form-control" rows="6" 
                      placeholder="Jelaskan secara rinci kronologi, kondisi lapangan, atau saran Anda..." 
                      required><?= sanitize($isi) ?></textarea>
        </div>

        <!-- Unggah Foto Bukti -->
        <div class="form-group">
            <label for="foto" class="form-label">Foto Bukti / Pendukung <span style="color: var(--color-text-light); font-weight: normal;">(Opsional, Maks. 2 MB)</span></label>
            <input type="file" id="foto" name="foto" class="form-control" accept=".jpg, .jpeg, .png">
            <small style="display: block; margin-top: 0.35rem; color: var(--color-text-light); font-size: 0.85rem;">
                Hanya file gambar dengan format JPG, JPEG, atau PNG.
            </small>

            <!-- Kotak Pratinjau Foto -->
            <div id="preview-box" style="display: none; margin-top: 0.85rem;">
                <p style="font-size: 0.85rem; font-weight: 600; margin-bottom: 0.35rem;">Pratinjau Foto:</p>
                <img id="preview-img" src="" alt="Pratinjau" style="max-height: 220px; border-radius: var(--radius); border: 1px solid var(--color-border); display: block;">
            </div>
        </div>

        <!-- Opsi Publik/Privat -->
        <div class="form-group" style="margin-top: 1.5rem; padding: 0.85rem 1rem; background-color: var(--color-bg); border-radius: var(--radius); border: 1px solid var(--color-border);">
            <label style="display: flex; align-items: flex-start; gap: 0.65rem; cursor: pointer; margin-bottom: 0;">
                <input type="checkbox" name="is_public" value="1" <?= $is_public ? 'checked' : '' ?> style="margin-top: 0.25rem;">
                <div>
                    <strong>Publikasikan Laporan Ini</strong>
                    <div style="font-size: 0.85rem; color: var(--color-text-light); margin-top: 0.15rem;">
                        Jika dicentang, laporan Anda dapat dilihat oleh publik di halaman Laporan Publik (tanpa menampilkan data pribadi Anda).
                    </div>
                </div>
            </label>
        </div>

        <!-- Tombol Kirim -->
        <div style="display: flex; gap: 0.75rem; margin-top: 1.5rem; justify-content: flex-end;">
            <a href="/index.php" class="btn btn-secondary">Batal</a>
            <button type="submit" class="btn btn-primary">Kirim Laporan</button>
        </div>
    </form>
</div>

<!-- Pratinjau Gambar di Browser -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const fotoInput = document.getElementById('foto');
    const previewBox = document.getElementById('preview-box');
    const previewImg = document.getElementById('preview-img');

    if (fotoInput && previewBox && previewImg) {
        fotoInput.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                if (file.type.match(/^image\/(jpeg|png)$/)) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        previewImg.src = e.target.result;
                        previewBox.style.display = 'block';
                    };
                    reader.readAsDataURL(file);
                } else {
                    previewBox.style.display = 'none';
                    previewImg.src = '';
                }
            } else {
                previewBox.style.display = 'none';
                previewImg.src = '';
            }
        });
    }
});
</script>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>

