<?php
// Helper upload gambar berita. Dipakai oleh tambah.php dan edit.php.
// File disimpan ke folder uploads/ dengan nama acak, lalu nama filenya disimpan di kolom gambar_url.

const GAMBAR_MAKS_BYTE = 2 * 1024 * 1024; // 2 MB
const GAMBAR_TIPE_IZIN = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp',
];

// Mengembalikan [nama_file, pesan_error]. Jika tidak ada file yang diunggah, keduanya null.
function upload_gambar(array $file): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return [null, null];
    }
    if (($file['error'] ?? -1) !== UPLOAD_ERR_OK) {
        return [null, 'Gambar gagal diunggah. Coba lagi.'];
    }
    if ($file['size'] > GAMBAR_MAKS_BYTE) {
        return [null, 'Ukuran gambar maksimal 2 MB.'];
    }

    // Cek isi file sebenarnya, bukan hanya ekstensi namanya.
    $info = @getimagesize($file['tmp_name']);
    $tipe = $info['mime'] ?? '';
    if (!isset(GAMBAR_TIPE_IZIN[$tipe])) {
        return [null, 'Format gambar harus JPG, PNG, atau WEBP.'];
    }

    $namaBaru = bin2hex(random_bytes(16)) . '.' . GAMBAR_TIPE_IZIN[$tipe];
    $tujuan = __DIR__ . '/../uploads/' . $namaBaru;

    if (!move_uploaded_file($file['tmp_name'], $tujuan)) {
        return [null, 'Gambar tidak bisa disimpan di server.'];
    }
    return [$namaBaru, null];
}

// Menghapus file gambar dari folder uploads/. Nama dibersihkan dulu agar tidak bisa keluar dari folder.
function hapus_gambar(?string $namaFile): void
{
    if ($namaFile === null || $namaFile === '') {
        return;
    }
    $aman = basename($namaFile);
    $path = __DIR__ . '/../uploads/' . $aman;
    if (is_file($path)) {
        unlink($path);
    }
}