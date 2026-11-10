# ATTENDARA

## Konfigurasi koneksi database

`config/database.php` membuat koneksi PDO ke database MySQL `attendara`. Atur
variabel lingkungan berikut pada proses PHP/web server:

- `ATTENDARA_DB_HOST` (opsional, default `127.0.0.1`)
- `ATTENDARA_DB_NAME` (opsional, default `attendara`)
- `ATTENDARA_DB_USER` (opsional untuk host lokal; default `root`, wajib untuk host lain)
- `ATTENDARA_DB_PASSWORD` (opsional; gunakan nilai kosong bila tanpa password)

Contoh nilai kredensial hanya boleh disimpan pada konfigurasi lokal server yang
tidak masuk repository. Impor skema yang sudah ada dari `database/attendara.sql`;
file koneksi tidak membuat atau mengubah tabel.

## Login

Buka `login.php`. Form mengirim field `username` dan `password` melalui POST.
Login memeriksa `users.username`, `users.password_hash`, `users.is_active`, dan
`users.role`; akun aktif dengan password valid diarahkan ke `admin/dashboard.php`
atau `guru/dashboard.php`. Halaman tujuan tersebut disediakan oleh bagian UI/
backend terkait.

## Kontrak helper autentikasi

Sertakan helper dengan `require_once __DIR__ . '/../config/auth.php'` dari
halaman dalam folder `admin/` atau `guru/`. Sebelum memanggil helper yang
membaca session, jalankan `startAppSession()`.

- `currentUser()` mengembalikan array `id`, `username`, dan `role`, atau `null`.
- `isLoggedIn()` mengembalikan status autentikasi.
- `requireLogin()` mengalihkan pengguna yang belum login ke `login.php`.
- `requireRole('admin')` atau `requireRole('guru')` mewajibkan role tersebut;
  role lain menerima HTTP 403.
- `redirectToDashboard()` mengarahkan pengguna yang login sesuai role.
- `appUrl('path')` membentuk URL relatif terhadap root proyek, termasuk saat
  aplikasi dipasang dalam subfolder.
- `csrfToken()` menghasilkan token form; `verifyCsrfToken($value)` memeriksa
  token yang diterima. Gunakan pada setiap POST yang mengubah data.
- `h($value)` meng-escape string untuk HTML dan atribut HTML.

Contoh di awal halaman khusus admin:

```php
require_once __DIR__ . '/../config/auth.php';
startAppSession();
requireRole('admin');
$user = currentUser();
```

## Logout

Kirim form dengan metode POST ke `logout.php`, sertakan hidden field
`_csrf_token` berisi `csrfToken()`. Endpoint memverifikasi token, menghapus data
session dan cookie session, kemudian mengalihkan ke `login.php`. Session login
berakhir setelah 30 menit tidak aktif atau 8 jam sejak autentikasi.
