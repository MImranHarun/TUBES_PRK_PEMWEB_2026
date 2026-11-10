
<?php

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/koneksi.php';

$error = '';
$email = '';
$success = get_flash('success');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'Email dan password wajib diisi.';
    } else {
        try {
            $pdo = db();

            $query = $pdo->prepare(
                'SELECT id, nama, email, password, role, nim_nip, no_hp
                 FROM users
                 WHERE email = :email'
            );

            $query->execute([
                'email' => $email
            ]);

            $user = $query->fetch();

            if ($user && password_verify($password, $user['password'])) {

                session_regenerate_id(true);

                $_SESSION['user'] = [
                    'id' => $user['id'],
                    'nama' => $user['nama'],
                    'email' => $user['email'],
                    'role' => $user['role'],
                    'nim_nip' => $user['nim_nip'],
                    'no_hp' => $user['no_hp']
                ];

                set_flash('success', 'Login berhasil.');

                header('Location: ' . url('index.php'));
                exit;

            } else {
                $error = 'Email atau password salah.';
            }

        } catch (PDOException $e) {
            $error = 'Terjadi kesalahan. Silakan coba kembali.';
        }
    }
}

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - LabLoan</title>
</head>
<body>

    <h2>Login Akun LabLoan</h2>

    <?php if ($success): ?>
        <p style="color: green;">
            <?= e($success) ?>
        </p>
    <?php endif; ?>

    <?php if ($error !== ''): ?>
        <p style="color: red;">
            <?= e($error) ?>
        </p>
    <?php endif; ?>

    <form method="POST" action="">

        <label>Email</label><br>
        <input type="email" name="email"
               value="<?= e($email) ?>" required>
        <br><br>

        <label>Password</label><br>
        <input type="password" name="password" required>
        <br><br>

        <button type="submit">Login</button>

    </form>

    <p>
        Belum punya akun?
        <a href="<?= e(url('auth/register.php')) ?>">Daftar</a>
    </p>

</body>
</html>
