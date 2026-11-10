# Database FEBMart

FEBMart menggunakan database MySQL bernama febmart.
Database ini menyimpan data akun, produk, dan penjualan.

## Tabel yang Digunakan

1. users: menyimpan akun admin dan kasir.
2. categories: menyimpan kategori produk.
3. products: menyimpan data produk, harga, stok, dan batas minimum stok.
4. transactions: menyimpan transaksi beserta pembayarannya.
5. transaction_details: menyimpan barang yang dibeli dalam setiap transaksi.

## Hubungan Antartabel

Setiap produk memiliki kategori melalui kolom category_id.
Kategori yang sama dapat digunakan oleh beberapa produk.

Setiap transaksi mencatat pengguna yang melayani melalui kolom user_id.
Daftar barang yang dibeli disimpan di transaction_details.

Kolom transaction_id menghubungkan detail barang dengan transaksinya.
Kolom product_id menghubungkan detail barang dengan data produk.

## Data untuk Dashboard

Dashboard akan menampilkan jumlah jenis produk, jumlah transaksi
hari ini, omzet hari ini, dan daftar produk yang stoknya menipis.

Jumlah produk dihitung dari banyaknya data pada tabel products,
bukan dari penjumlahan stok.

Jumlah transaksi dan omzet harian mengikuti kolom transaction_date.
Omzet dihitung dari kolom total. Jika belum ada transaksi,
dashboard menampilkan nilai nol.

Produk masuk daftar stok menipis jika stock sudah mencapai
atau berada di bawah min_stock. Jika stock bernilai nol,
produk ditandai sebagai habis.

## Aturan Relasi Data Master

### Kategori dan Produk

Kolom products.category_id mengacu pada categories.id.
Satu kategori dapat digunakan oleh banyak produk.
Setiap produk wajib memiliki kategori yang terdaftar.

Relasi ini menggunakan ON DELETE RESTRICT.
Kategori yang masih digunakan oleh produk tidak dapat dihapus.
Produk perlu dipindahkan ke kategori lain atau dihapus terlebih dahulu.

ON UPDATE CASCADE membuat category_id pada produk mengikuti perubahan
id kategori jika id tersebut diperbarui.

### Pengguna dan Transaksi

Kolom transactions.user_id mengacu pada users.id.
Satu pengguna dapat tercatat sebagai kasir pada banyak transaksi.
Setiap transaksi wajib terhubung dengan pengguna yang terdaftar.

Relasi ini menggunakan ON DELETE RESTRICT.
Pengguna yang memiliki riwayat transaksi tidak dapat dihapus.
Untuk menonaktifkan akun tersebut, gunakan kolom is_active
agar riwayat transaksi tetap tersimpan.

ON UPDATE CASCADE membuat user_id pada transaksi mengikuti perubahan
id pengguna jika id tersebut diperbarui.

Tabel users tidak memiliki relasi langsung dengan categories atau products.
Hubungan pengguna dengan penjualan dicatat melalui tabel transactions.

## Aturan Relasi Detail Transaksi

### Transaksi dan Detail Barang

Kolom transaction_details.transaction_id mengacu pada transactions.id.
Satu transaksi dapat memiliki beberapa detail barang.

Relasi ini menggunakan ON DELETE CASCADE.
Jika transaksi dihapus, seluruh detail barang pada transaksi tersebut
ikut terhapus. Penghapusan transaksi perlu diperhatikan karena
menghilangkan riwayat penjualan.

ON UPDATE CASCADE membuat transaction_id pada detail mengikuti
perubahan id transaksi.

### Produk dan Detail Barang

Kolom transaction_details.product_id mengacu pada products.id.
Satu produk dapat muncul dalam banyak detail transaksi.

Relasi ini menggunakan ON DELETE SET NULL.
Jika produk dihapus, product_id pada detail transaksi menjadi NULL.
Baris detail transaksi tetap tersimpan.

Kolom product_name, quantity, unit_price, dan subtotal menyimpan
rincian pembelian saat transaksi berlangsung. Data tersebut tetap
tersedia meskipun produk asal sudah dihapus.

ON UPDATE CASCADE membuat product_id pada detail mengikuti
perubahan id produk.

Penghapusan transaksi tidak otomatis mengembalikan stok produk.
Pengembalian stok membutuhkan proses tersendiri pada aplikasi.