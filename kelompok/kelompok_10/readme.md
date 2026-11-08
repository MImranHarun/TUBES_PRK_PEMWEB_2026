# SisaSense

**Sistem Manajemen Persediaan dan Analisis Performa Penjualan UMKM**

SisaSense merupakan aplikasi web untuk membantu UMKM mengelola persediaan barang, mencatat transaksi penjualan, memantau stok, serta menganalisis performa penjualan. Sistem dirancang untuk membantu pemilik usaha mengurangi risiko kehabisan stok, penumpukan barang, dan produk yang mendekati kedaluwarsa.

## 1. Teknologi

- HTML
- CSS
- JavaScript
- PHP Native
- MySQL/MariaDB
- Git dan GitHub

Proyek ini tidak menggunakan framework backend.

## 2. Struktur Folder

```text
kelompok_10/
├── assets/
│   ├── css/
│   │   └── style.css
│   ├── images/
│   └── js/
├── config/
│   ├── auth.php
│   └── database.php
├── database/
│   └── sisa_sense.sql
├── includes/
│   ├── header.php
│   └── footer.php
├── tests/
│   └── run.php
├── dashboard.php
├── index.php
├── login.php
├── logout.php
├── setup.php
├── .gitignore
└── README.md
```

## 3. Persyaratan Sistem

- PHP 8.2 atau lebih baru.
- MySQL atau MariaDB.
- Ekstensi PHP PDO MySQL.
- Ekstensi PHP mbstring.
- Apache atau web server PHP lainnya.
- Browser modern.

## 4. Instalasi Lokal

1. Clone repositori kelompok.
2. Letakkan proyek pada direktori web server.
3. Jalankan Apache dan MySQL/MariaDB.
4. Impor file `database/sisa_sense.sql` melalui phpMyAdmin.
5. Pastikan database `sisasense_db` berhasil dibuat.
6. Periksa konfigurasi koneksi pada `config/database.php`.
7. Buka aplikasi melalui:

```text
http://localhost/TUBES_PRK_PEMWEB_2026/kelompok/kelompok_10/
```

Konfigurasi koneksi lokal bawaan:

| Parameter | Nilai default |
|---|---|
| Host | 127.0.0.1 |
| Port | 3306 |
| Database | sisasense_db |
| Username | root |
| Password | Kosong |

Jika konfigurasi MySQL berbeda, atur environment variable berikut pada lingkungan PHP:

- `DB_HOST`
- `DB_PORT`
- `DB_NAME`
- `DB_USER`
- `DB_PASSWORD`

Jangan mengunggah password database pribadi ke GitHub.

## 5. Pembuatan Akun Admin

Setelah database berhasil diimpor:

1. Buka halaman aplikasi.
2. Pilih menu pembuatan akun admin pertama.
3. Masukkan nama lengkap, username, email, dan password.
4. Gunakan password minimal 12 karakter.
5. Setelah akun dibuat, sistem akan mengarahkan pengguna ke dashboard.

Akun admin pertama hanya dapat dibuat ketika tabel users belum memiliki akun.

## 6. Database

Database menggunakan nama `sisasense_db` dengan tabel utama:

| Tabel | Fungsi |
|---|---|
| users | Menyimpan akun dan hak akses |
| categories | Menyimpan kategori produk |
| suppliers | Menyimpan data pemasok |
| products | Menyimpan informasi produk |
| stock_batches | Menyimpan batch dan jumlah stok |
| inventory_movements | Menyimpan riwayat perubahan stok |
| sales | Menyimpan transaksi penjualan |
| sale_items | Menyimpan detail barang yang dijual |

Kontrak database harus disepakati bersama sebelum anggota mengembangkan modul masing-masing.

Jumlah stok tersedia dihitung dari `stock_batches.qty_remaining`. Jangan membuat kolom stok total terpisah tanpa kesepakatan karena dapat menyebabkan data stok tidak konsisten.

## 7. Kontrak Integrasi

- Gunakan koneksi database melalui fungsi `db()` pada `config/database.php`.
- Gunakan helper autentikasi dari `config/auth.php`.
- Gunakan `require_login()` untuk halaman yang memerlukan login.
- Gunakan `require_role()` untuk halaman dengan pembatasan hak akses.
- Gunakan `includes/header.php` dan `includes/footer.php` sebagai layout bersama.
- Set `$pageTitle`, `$activePage`, dan `$navItems` sebelum memanggil header.
- Semua modul harus menggunakan nama tabel dan kolom sesuai kontrak database.
- Semua pekerjaan harus berada di dalam folder `kelompok/kelompok_10/`.

## 8. Pengujian

Jalankan pengujian autentikasi dari root repositori:

```bash
php kelompok/kelompok_10/tests/run.php
```

Periksa sintaks seluruh file PHP menggunakan PHP lint.

PowerShell:

```powershell
Get-ChildItem kelompok\kelompok_10 -Recurse -Filter *.php |
ForEach-Object { php -l $_.FullName }
```

## 9. Aturan Kolaborasi GitHub

Branch integrasi kelompok: `staging`.

Branch pekerjaan:

- Ketua: `feat/kelompok-10-foundation`
- Anggota produk: `feat/kelompok-10-produk`
- Anggota persediaan: `feat/kelompok-10-persediaan`
- Anggota penjualan: `feat/kelompok-10-penjualan`
- Anggota dashboard: `feat/kelompok-10-dashboard`

Alur kerja:

1. Ambil pembaruan dari `staging`.
2. Buat branch fitur masing-masing.
3. Kerjakan modul pada folder kelompok.
4. Lakukan commit.
5. Push branch ke repositori kelompok.
6. Buat Pull Request menuju `staging`.
7. Ketua melakukan review dan pengujian.
8. Merge setelah perubahan dinyatakan layak.
9. Ketua membuat Pull Request final dari `staging` ke `main` repositori resmi LabKom.

Jangan mengerjakan fitur langsung pada branch `main` atau `staging`.

## 10. Status Pengembangan

Fondasi awal:
- Struktur proyek.
- Koneksi database.
- Autentikasi dan session.
- Login dan logout.
- Pembuatan akun admin pertama.
- Layout dasar.
- Kontrak database awal.

Fitur yang dikembangkan selanjutnya:
- Manajemen produk.
- Manajemen persediaan.
- Transaksi penjualan.
- Dashboard analitik.
- Laporan penjualan dan persediaan.
- Rekomendasi restock dan simulasi diskon.
