

## 3.1 Gambaran Umum Sistem

SaveSmart merupakan sistem informasi berbasis web yang dirancang untuk membantu keluarga dalam mengelola dan memantau kondisi keuangan bulanan. Sistem ini menyediakan fitur pencatatan pemasukan dan pengeluaran, pengelolaan anggaran berdasarkan kategori kebutuhan, serta pembuatan dan pemantauan target tabungan.

Melalui sistem ini, pengguna dapat mengetahui ringkasan kondisi keuangan keluarga melalui dashboard dan laporan keuangan. Dengan demikian, pengelolaan keuangan keluarga diharapkan menjadi lebih terencana, terstruktur, dan efisien.

## 3.2 Tujuan Pengembangan Sistem

Tujuan pengembangan SaveSmart adalah:

1. Memudahkan pengguna dalam mencatat pemasukan dan pengeluaran keluarga.
2. Membantu pengguna mengatur anggaran bulanan berdasarkan kategori kebutuhan.
3. Memudahkan pengguna membuat dan memantau target tabungan.
4. Menampilkan ringkasan kondisi keuangan keluarga melalui dashboard.
5. Menyediakan laporan keuangan sebagai bahan evaluasi pengeluaran dan pemasukan.
6. Membantu keluarga mengelola keuangan secara lebih teratur.

## 3.3 Analisis Kebutuhan Fungsional

Kebutuhan fungsional merupakan kebutuhan yang menjelaskan fitur atau fungsi yang harus tersedia di dalam sistem SaveSmart.

| No. | Kebutuhan Fungsional | Deskripsi |
|---|---|---|
| 1 | Registrasi | Pengguna dapat membuat akun untuk mengakses sistem. |
| 2 | Login | Pengguna dapat masuk ke sistem menggunakan akun yang telah terdaftar. |
| 3 | Dashboard | Sistem menampilkan ringkasan pemasukan, pengeluaran, saldo, dan tabungan. |
| 4 | Kelola Pemasukan | Pengguna dapat menambah, melihat, mengubah, dan menghapus data pemasukan. |
| 5 | Kelola Pengeluaran | Pengguna dapat menambah, melihat, mengubah, dan menghapus data pengeluaran. |
| 6 | Kelola Anggaran | Pengguna dapat menentukan anggaran bulanan berdasarkan kategori kebutuhan. |
| 7 | Kelola Target Tabungan | Pengguna dapat membuat, melihat, mengubah, dan menghapus target tabungan. |
| 8 | Setoran Tabungan | Pengguna dapat mencatat jumlah uang yang disetorkan ke target tabungan. |
| 9 | Progres Tabungan | Sistem menampilkan perkembangan tabungan berdasarkan target yang telah ditentukan. |
| 10 | Laporan Keuangan | Sistem menampilkan laporan pemasukan dan pengeluaran berdasarkan periode tertentu. |
| 11 | Kelola Profil | Pengguna dapat melihat dan mengubah informasi profil akun atau keluarga. |
| 12 | Logout | Pengguna dapat keluar dari sistem dengan aman. |

## 3.4 Analisis Kebutuhan Nonfungsional

Kebutuhan nonfungsional merupakan kebutuhan yang berkaitan dengan kualitas, keamanan, dan kenyamanan sistem.

1. **Kemudahan Penggunaan:** Sistem memiliki tampilan yang sederhana dan mudah dipahami oleh pengguna.
2. **Keamanan:** Sistem menyediakan autentikasi login dan pengelolaan sesi untuk membatasi akses pengguna.
3. **Kinerja:** Sistem dapat memproses dan menampilkan data keuangan dengan waktu respons yang wajar.
4. **Kompatibilitas:** Sistem dapat diakses melalui browser pada komputer maupun perangkat mobile.
5. **Keakuratan Data:** Perhitungan total pemasukan, pengeluaran, saldo, dan progres tabungan harus sesuai dengan data yang tersimpan.
6. **Penyimpanan Data:** Sistem menggunakan database MySQL atau MariaDB untuk menyimpan data secara terstruktur.

## 3.5 Analisis Kebutuhan Pengguna

Dalam rancangan SaveSmart yang menggunakan tiga jenis aktor, kebutuhan pengguna dibagi sebagai berikut.

| Aktor | Hak Akses dan Kebutuhan |
|---|---|
| Admin | Mengelola data pengguna dan kategori transaksi serta mengawasi pengelolaan sistem. |
| Pengguna/Kepala Keluarga | Mengelola data keuangan keluarga, anggaran bulanan, target tabungan, dan laporan keuangan. |
| Anggota Keluarga | Melihat dashboard, mencatat pemasukan dan pengeluaran, serta melihat progres tabungan dan laporan sesuai hak aksesnya. |

Catatan: Pembagian tiga aktor ini mengasumsikan setiap anggota keluarga memiliki akun masing-masing dan sistem menerapkan pembatasan hak akses. Fitur yang tercantum perlu disesuaikan dengan implementasi akhir.

## 3.6 Kebutuhan Perangkat dan Teknologi

Perangkat dan teknologi yang dibutuhkan dalam pengembangan SaveSmart adalah:

| No. | Kebutuhan | Teknologi/Perangkat |
|---|---|---|
| 1 | Sistem Operasi | Windows |
| 2 | Code Editor | Visual Studio Code |
| 3 | Web Server Lokal | Apache melalui Laragon |
| 4 | Bahasa Markup | HTML |
| 5 | Bahasa Tampilan | CSS dan Bootstrap |
| 6 | Bahasa Pemrograman Frontend | JavaScript |
| 7 | Bahasa Pemrograman Backend | PHP Native |
| 8 | Database | MySQL/MariaDB |
| 9 | Visualisasi Grafik | Chart.js |
| 10 | Browser | Google Chrome atau browser lainnya |
| 11 | Kolaborasi Kode | Git dan GitHub |

