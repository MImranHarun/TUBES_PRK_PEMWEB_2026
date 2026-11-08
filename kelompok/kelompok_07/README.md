# LabLoan

Sistem peminjaman alat dan ruang laboratorium berbasis web. Tugas Besar Praktikum Pemrograman Web, Kelompok 07.

Peminjam dapat melihat daftar alat/ruang, mengajukan peminjaman, dan memantau riwayatnya. Admin mengelola kategori dan data alat, menyetujui atau menolak pengajuan, mencatat pengembalian, serta melihat dashboard dan laporan.

## Anggota Kelompok

| No | Nama | NPM | Tugas |
|----|------|-----|-------|
| 1 | Muhammad Fadhel Saputra | 2415061097 | Fondasi project, database, layout |
| 2 | ... | ... | Auth & session |
| 3 | ... | ... | CRUD kategori & alat |
| 4 | ... | ... | Peminjaman (ajuan, riwayat, validasi) |
| 5 | ... | ... | Admin (approval, pengembalian, dashboard, laporan) |

## Teknologi

- PHP 8.2 native (PDO + prepared statements)
- MySQL / MariaDB (InnoDB, utf8mb4)
- HTML + CSS murni, font dari Google Fonts (CDN)
- Laragon sebagai local server

## Cara Install di Laragon

1. Salin folder `kelompok` ke `C:\laragon\www\`, sehingga path project menjadi `C:\laragon\www\kelompok\kelompok_07\`.
2. Jalankan Laragon, klik **Start All** (Apache/Nginx dan MySQL).
3. Import database:
   - Buka HeidiSQL (tombol **Database** di Laragon) atau phpMyAdmin.
   - Jalankan file `database/labloan.sql`. File ini otomatis membuat database `labloan`, semua tabel, dan data awal.
   - Alternatif lewat terminal Laragon: `mysql -u root < database/labloan.sql`
4. Cek koneksi di `config/koneksi.php`. Default Laragon sudah sesuai (host `localhost`, user `root`, password kosong). Ubah konstanta `DB_*` jika berbeda.
5. Buka `http://localhost/kelompok/kelompok_07/` di browser.

## Akun Admin Default

| Email | Password |
|-------|----------|
| admin@labloan.test | admin123 |

Segera ganti password setelah fitur ubah password tersedia.

## Struktur Folder

```
kelompok/kelompok_07/
├── assets/
│   ├── css/style.css
│   └── img/
├── config/
│   ├── app.php
│   └── koneksi.php
├── database/
│   └── labloan.sql
├── includes/
│   ├── header.php
│   ├── navbar.php
│   └── footer.php
├── uploads/
│   └── alat/
├── index.php
└── README.md
```

Folder fitur yang akan dibuat anggota (sudah dipakai sebagai link di navbar):

```
auth/login.php, auth/register.php, auth/logout.php
alat/index.php
peminjaman/ajukan.php, peminjaman/riwayat.php
admin/dashboard.php, admin/laporan.php
admin/kategori/index.php, admin/alat/index.php, admin/peminjaman/index.php
```

## Skema Database

| Tabel | Fungsi |
|-------|--------|
| users | Akun admin dan peminjam |
| kategori | Pengelompokan alat/ruang |
| alat | Data alat dan ruang (kolom `jenis`: alat/ruang), stok, kondisi, foto |
| peminjaman | Header pengajuan: peminjam, jadwal, status, admin yang memproses |
| detail_peminjaman | Daftar alat dan jumlah dalam satu pengajuan |

Status peminjaman: `menunggu`, `disetujui`, `ditolak`, `dikembalikan`.

## Konvensi untuk Anggota

- Selalu pakai `require_once` untuk file di folder `config/`.
- Set `$judul` sebelum `require` header.
- Pakai `url()` untuk link, `asset()` untuk file di `assets/`, dan `e()` untuk setiap output data ke HTML.
- Query wajib lewat `db()->prepare()` dengan parameter.
- Session login disimpan di `$_SESSION['user']` berisi `id`, `nama`, dan `role`.
- Foto alat disimpan di `uploads/alat/`, yang disimpan ke database hanya nama filenya.
