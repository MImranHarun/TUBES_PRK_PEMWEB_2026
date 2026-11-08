# FEBMart
> **Sistem Informasi Point of Sales (POS) dan Manajemen Persediaan Barang Berbasis Web**  
> Menggunakan PHP Native dan MySQL

---

##  1. Identitas Proyek

| Keterangan | Informasi |
| :--- | :--- |
| **Nama Sistem** | FEBMart |
| **Kelompok** | 23 |
| **Tema** | Fakultas Ekonomi dan Bisnis (FEB) |
| **Jenis Sistem** | Point of Sales (POS) dan Manajemen Persediaan Barang |
| **Platform** | Website |
| **Bahasa Pemrograman** | PHP Native, HTML, CSS, JavaScript |
| **Database** | MySQL |
| **Framework Backend** | *Tidak menggunakan framework backend (PHP Native)* |

---

##  2. Deskripsi Sistem

**FEBMart** merupakan sistem informasi berbasis web yang dirancang untuk membantu proses pengelolaan penjualan dan persediaan barang secara terkomputerisasi.Sistem ini menyediakan fitur **Point of Sales (POS)** untuk membantu proses transaksi penjualan, mulai dari pemilihan produk, pengelolaan keranjang belanja, perhitungan total pembayaran, hingga penyimpanan transaksi.Selain itu, FEBMart menyediakan fitur **manajemen persediaan barang** yang memungkinkan administrator mengelola data kategori, produk, jumlah stok, serta memantau ketersediaan barang. Dengan adanya FEBMart, proses operasional penjualan diharapkan menjadi lebih terstruktur, efektif, dan meminimalkan kesalahan pencatatan transaksi maupun persediaan barang.

---

## 3. Latar Belakang

Pengelolaan transaksi penjualan dan persediaan barang merupakan bagian penting dalam kegiatan operasional bisnis. Proses pencatatan yang masih dilakukan secara manual dapat menyebabkan berbagai kendala, seperti kesalahan perhitungan pembayaran, kesulitan dalam memantau stok barang, serta kurang efektifnya proses pembuatan laporan penjualan.Oleh karena itu, diperlukan sistem informasi yang dapat membantu mengintegrasikan proses transaksi penjualan dan pengelolaan persediaan barang dalam satu platform.FEBMart dikembangkan sebagai solusi berbasis web yang menyediakan pengelolaan data produk, transaksi kasir, informasi stok, serta laporan penjualan secara terintegrasi.

---

## 4. Tujuan Sistem

Tujuan pengembangan FEBMart adalah:
- Mempermudah proses transaksi penjualan melalui sistem Point of Sales (POS).
- Membantu pengelolaan data kategori dan produk.
- Mempermudah pemantauan jumlah persediaan barang.
- Mengelola data pengguna dan hak akses sistem.
- Menyediakan riwayat transaksi dan laporan penjualan.
- Mengurangi kesalahan pencatatan dan perhitungan transaksi.
- Menyediakan informasi penjualan dan persediaan secara terstruktur.

---

##  5. Fitur Utama Sistem

### A. Authentication & User Management
- Login administrator.
- Validasi username dan password.
- Pengelolaan session pengguna.
- Enkripsi password menggunakan `password_hash()`.
- Logout pengguna.
- Menampilkan daftar pengguna.
- Menambahkan pengguna baru.
- Mengubah data pengguna.
- Menghapus pengguna.

### B. Master Data Kategori & Produk
- Menampilkan daftar kategori.
- Menambahkan kategori produk.
- Mengubah dan menghapus kategori.
- Menampilkan daftar produk.
- Menambahkan produk baru.
- Memilih kategori produk.
- Mengunggah gambar produk.
- Mengubah dan menghapus data produk.
- Pencarian produk dan validasi data.

### C. Transaksi Kasir / Point of Sales
- Menampilkan katalog produk.
- Menambahkan produk ke keranjang belanja.
- Mengubah jumlah barang.
- Menghapus produk dari keranjang.
- Menyinkronkan harga dan stok keranjang dengan data terbaru.
- Menghitung subtotal dan total pembayaran.
- Memasukkan nominal pembayaran.
- Menyediakan shortcut nominal pembayaran.
- Menghitung uang kembalian secara otomatis.
- Menyimpan transaksi dan detail transaksi.
- Memotong stok barang secara otomatis setelah transaksi berhasil.
- Memvalidasi input transaksi dan melindungi aksi kasir dengan CSRF token.
- Menampilkan pesan error jika tabel transaksi gagal disiapkan.

### D. Riwayat Transaksi, Laporan & Cetak Struk
- Menampilkan riwayat transaksi.
- Melihat detail barang dalam transaksi.
- Menampilkan laporan penjualan.
- Memfilter laporan berdasarkan tanggal.
- Menghitung total omzet.
- Menampilkan nota atau struk pembayaran.
- Mencetak struk transaksi.
- Mengekspor laporan ke format Excel.

### E. UI/UX & Layout Website
- Menggunakan template AdminLTE dan Bootstrap.
- Menyediakan header dan navbar.
- Menyediakan sidebar navigasi.
- Menyediakan footer.
- Menyediakan helper format mata uang Rupiah.
- Menyediakan dashboard utama.
- Menyediakan notifikasi berhasil dan gagal.
- Menyesuaikan tampilan untuk perangkat dengan ukuran layar berbeda (responsif).

### F. Database, Dashboard & Monitoring Stok
- Merancang struktur database FEBMart.
- Membuat skema database.
- Mengatur relasi antartabel.
- Menampilkan total produk.
- Menampilkan jumlah transaksi hari ini.
- Menampilkan informasi omzet.
- Menampilkan produk dengan stok menipis.
- Memberikan notifikasi ketika stok barang menipis.

---

## 6. Teknologi yang Digunakan

| Teknologi | Kegunaan |
| :--- | :--- |
| **HTML5** | Membuat struktur halaman website |
| **CSS3** | Mengatur tampilan website |
| **JavaScript** | Menambahkan interaksi pada halaman |
| **PHP Native** | Mengembangkan proses backend dan logika sistem |
| **MySQL** | Menyimpan dan mengelola data |
| **Bootstrap** | Membantu pengembangan tampilan responsif |
| **AdminLTE** | Template dashboard administrator |
| **Git & GitHub** | Mengelola versi kode dan kolaborasi tim |
| **Laragon** | Menjalankan server lokal dan database |

---

## 7. Persyaratan Sistem

Untuk menjalankan FEBMart pada lingkungan lokal, diperlukan:

- Sistem operasi Windows atau sistem operasi lain yang mendukung server PHP.
- Laragon atau XAMPP.
- PHP versi 8.2 atau lebih baru.
- MySQL atau MariaDB.
- Browser modern (seperti Google Chrome atau Mozilla Firefox).
- Git untuk mengambil dan mengelola kode proyek.

---

## 8. Panduan Instalasi dan Menjalankan Sistem

### Langkah 1 – Clone Repository
Clone repository proyek ke komputer Anda:
```bash
git clone https://github.com/Jayy-develop/TUBES_PRK_PEMWEB_2026.git
```

### Langkah 2 – Masuk ke Direktori Proyek
```bash
cd TUBES_PRK_PEMWEB_2026
```

### Langkah 3 – Masuk ke Folder FEBMart
```bash
cd kelompok/kelompok_23
```

### Langkah 4 – Jalankan Web Server
1. Buka aplikasi **Laragon**.
2. Klik tombol **Start All**.
3. Pastikan service **Apache** dan **MySQL** sudah berstatus *Running*.

### Langkah 5 – Buat Database
1. Buka **phpMyAdmin** melalui browser (`http://localhost/phpmyadmin`).
2. Buat database baru bernama `febmart`.
3. Impor file `database/schema.sql` ke dalam database `febmart`.

### Langkah 6 – Konfigurasi Database
Sesuaikan konfigurasi koneksi database pada file:
```text
config/database.php
```
Pastikan host, username, password, dan nama database sesuai dengan konfigurasi MySQL lokal Anda.

### Langkah 7 – Jalankan Website
Buka browser dan akses alamat berikut:
```text
http://localhost/TUBES_PRK_PEMWEB_2026/kelompok/kelompok_23/
```

---

## 9. Kolaborasi Git dan GitHub

Pengembangan FEBMart menggunakan Git dan GitHub untuk mengelola versi kode serta mendukung kolaborasi antaranggota.

### Alur Kerja Kolaborasi:
1. Setiap anggota mengerjakan fitur pada branch masing-masing.
2. Branch fitur dibuat dari branch `staging`.
3. Perubahan kode **hanya dilakukan** di folder `kelompok/kelompok_23/`.
4. Setiap anggota melakukan *commit* dan *push* ke repository fork kelompok.
5. Setiap anggota membuat *Pull Request* menuju branch `staging`.
6. Ketua kelompok melakukan review dan merge perubahan.
7. Setelah seluruh fitur terintegrasi dan diuji, ketua kelompok membuat *Pull Request* final ke repository utama LabKom.

**Contoh alur branch Anggota 1:**
```bash
git checkout staging
git pull origin staging
git checkout -b feat/kelompok-23-auth-user
```

**Contoh commit & push:**
```bash
git add kelompok/kelompok_23/
git commit -m "feat(kelompok_23): menambahkan fitur autentikasi"
git push origin feat/kelompok-23-auth-user
```

---

## 10. Keamanan Sistem

Pengembangan FEBMart memperhatikan beberapa aspek keamanan, antara lain:
- Password pengguna disimpan menggunakan fungsi hashing PHP (`password_hash()`).
- Validasi session untuk membatasi akses halaman administrator.
- Validasi dan sanitasi input pengguna sebelum diproses.
- Penggunaan *prepared statements* untuk mencegah serangan *SQL Injection*.
- Pengaturan hak akses berdasarkan role pengguna.
- Validasi ketat pada alur login dan logout.

---

## 11. Batasan Pengembangan

Sistem FEBMart dikembangkan dengan batasan sebagai berikut:
1. Sistem berbasis website dan dijalankan pada lingkungan server lokal.
2. Backend menggunakan PHP Native tanpa framework backend.
3. Database menggunakan MySQL atau MariaDB.
4. Sistem difokuskan pada transaksi penjualan dan manajemen persediaan barang.
5. Fitur yang dikembangkan mengikuti ruang lingkup tugas besar Praktikum Pemrograman Web 2026.

---

<div align="center">

**FEBMart – Sistem Informasi Point of Sales (POS) dan Manajemen Persediaan Barang Berbasis Web**  
Kelompok 23 • Teknik Informatika • Universitas Lampung  
*Praktikum Pemrograman Web 2026*

</div>