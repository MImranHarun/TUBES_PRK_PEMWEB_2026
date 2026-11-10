
<?php

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/koneksi.php';

$error = '';

$nama = '';
$email = '';
$nim_nip = '';
$no_hp = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $nim_nip = trim($_POST['nim_nip'] ?? '');
    $no_hp = trim($_POST['no_hp'] ?? '');
    $password = $_POST['password'] ?? '';
    $konfirmasi = $_POST['konfirmasi_password'] ?? '';

    if ($nama === '' || $email === '' || $password === '') {
        $error = 'Nama, email, dan password wajib diisi.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid.';
    } elseif (strlen($password) < 8) {
        $error = 'Password minimal 8 karakter.';
    } elseif ($password !== $konfirmasi) {
        $error = 'Konfirmasi password tidak sesuai.';
    } else {
        try {
            $pdo = db();

            $cek = $pdo->prepare(
                'SELECT id FROM users WHERE email = :email'
            );

            $cek->execute([
                'email' => $email
            ]);

            if ($cek->fetch()) {
                $error = 'Email sudah terdaftar.';
            } else {
                $passwordHash = password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );

                $query = $pdo->prepare(
                    'INSERT INTO users
                    (nama, email, password, role, nim_nip, no_hp)
                    VALUES
                    (:nama, :email, :password, :role, :nim_nip, :no_hp)'
                );

                $query->execute([
                    'nama' => $nama,
                    'email' => $email,
                    'password' => $passwordHash,
                    'role' => 'peminjam',
                    'nim_nip' => $nim_nip !== '' ? $nim_nip : null,
                    'no_hp' => $no_hp !== '' ? $no_hp : null
                ]);

                set_flash(
                    'success',
                    'Registrasi berhasil. Silakan login.'
                );

                header('Location: ' . url('auth/login.php'));
                exit;
            }

        } catch (PDOException $e) {
            $error = 'Registrasi gagal. Silakan coba kembali.';
        }
    }
}

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrasi - LabLoan</title>
</head>
<body>

    <h2>Registrasi Akun LabLoan</h2>

    <?php if ($error !== ''): ?>
        <p style="color: red;">
            <?= e($error) ?>
        </p>
    <?php endif; ?>

    <form method="POST" action="">

        <label>Nama Lengkap</label><br>
        <input type="text" name="nama" value="<?= e($nama) ?>" required>
        <br><br>

        <label>Email</label><br>
        <input type="email" name="email" value="<?= e($email) ?>" required>
        <br><br>

        <label>NIM/NIP</label><br>
        <input type="text" name="nim_nip" value="<?= e($nim_nip) ?>">
        <br><br>

        <label>Nomor HP</label><br>
        <input type="text" name="no_hp" value="<?= e($no_hp) ?>">
        <br><br>

        <label>Password</label><br>
        <input type="password" name="password" required>
        <br><br>

        <label>Konfirmasi Password</label><br>
        <input type="password" name="konfirmasi_password" required>
        <br><br>

        <button type="submit">Daftar</button>

    </form>

    <p>
        Sudah punya akun?
        <a href="<?= e(url('auth/login.php')) ?>">Login</a>
    </p>

</body>
</html>
