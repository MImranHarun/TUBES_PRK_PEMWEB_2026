<?php
require_once __DIR__ . '/config/auth.php';

if (current_user()) {
    redirect('dashboard.php');
}

$errorMessage = '';
$created = false;
try {
    $created = (bool) db()->query('SELECT id FROM users LIMIT 1')->fetch();
} catch (PDOException $exception) {
    error_log('SisaSense setup database check failed: ' . $exception->getMessage());
    $errorMessage = 'Database belum siap. Impor database/sisa_sense.sql dan periksa konfigurasi koneksi.';
}

if (!$created && $errorMessage === '' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_valid_csrf();
    $name = is_string($_POST['name'] ?? null) ? trim($_POST['name']) : '';
    $username = is_string($_POST['username'] ?? null) ? trim($_POST['username']) : '';
    $email = is_string($_POST['email'] ?? null) ? trim($_POST['email']) : '';
    $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
    $confirmPassword = is_string($_POST['confirm_password'] ?? null) ? $_POST['confirm_password'] : '';

    if ($name === '' || mb_strlen($name) > 100) {
        $errorMessage = 'Nama wajib diisi dan maksimal 100 karakter.';
    } elseif (!preg_match('/^[A-Za-z0-9._-]{3,50}$/', $username)) {
        $errorMessage = 'Username harus terdiri dari 3–50 karakter: huruf, angka, titik, garis bawah, atau tanda hubung.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 150) {
        $errorMessage = 'Masukkan alamat email yang valid.';
    } elseif (strlen($password) < 12) {
        $errorMessage = 'Kata sandi harus memiliki minimal 12 karakter.';
    } elseif ($password !== $confirmPassword) {
        $errorMessage = 'Konfirmasi kata sandi tidak sama.';
    } else {
        $connection = db();
        $lockAcquired = false;
        try {
            $lockAcquired = (int) $connection->query("SELECT GET_LOCK('sisasense_initial_setup', 5)")->fetchColumn() === 1;
            if (!$lockAcquired) {
                $errorMessage = 'Proses setup sedang berlangsung. Coba kembali beberapa saat lagi.';
            } else {
                $connection->beginTransaction();
                $existingUser = $connection->query('SELECT id FROM users LIMIT 1')->fetch();
                if ($existingUser) {
                    $connection->rollBack();
                    $created = true;
                } else {
                    $statement = $connection->prepare(
                        'INSERT INTO users (full_name, username, email, password_hash, role, is_active)
                         VALUES (:full_name, :username, :email, :password_hash, :role, 1)'
                    );
                    $statement->execute([
                        'full_name' => $name,
                        'username' => $username,
                        'email' => $email,
                        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                        'role' => 'admin',
                    ]);
                    $userId = (int) $connection->lastInsertId();
                    $connection->commit();
                    session_regenerate_id(true);
                    $_SESSION['user'] = [
                        'id' => $userId,
                        'name' => $name,
                        'username' => $username,
                        'email' => $email,
                        'role' => 'admin',
                        'is_active' => true,
                    ];
                    flash('success', 'Akun admin berhasil dibuat. Selamat menggunakan SisaSense.');
                    redirect('dashboard.php');
                }
            }
        } catch (PDOException $exception) {
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }
            error_log('SisaSense initial admin setup failed: ' . $exception->getMessage());
            $errorMessage = 'Akun admin tidak dapat dibuat. Pastikan username dan email belum digunakan, lalu coba kembali.';
        } finally {
            if ($lockAcquired) {
                $connection->query("SELECT RELEASE_LOCK('sisasense_initial_setup')");
            }
        }
    }
}

$pageTitle = 'Setup admin';
require __DIR__ . '/includes/header.php';
?>
<section class="narrow-page">
    <div class="panel">
        <p class="eyebrow">PENYIAPAN AWAL</p>
        <h1>Buat akun admin</h1>
        <?php if ($created): ?>
            <div class="notice notice-success">Akun sudah tersedia. Silakan masuk menggunakan akun yang terdaftar.</div>
            <a class="button button-primary" href="<?= e(app_url('login.php')) ?>">Ke halaman masuk</a>
        <?php else: ?>
            <p class="muted">Akun pertama akan mendapatkan akses admin. Setelah dibuat, halaman ini tidak dapat digunakan untuk mendaftarkan akun baru.</p>
            <?php if ($errorMessage !== ''): ?>
                <div class="notice notice-error" role="alert"><?= e($errorMessage) ?></div>
            <?php endif; ?>
            <form method="post" class="form-stack">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <label for="name">Nama lengkap</label>
                <input id="name" name="name" maxlength="100" autocomplete="name" required>
                <label for="username">Username</label>
                <input id="username" name="username" minlength="3" maxlength="50" pattern="[A-Za-z0-9._-]+" autocomplete="username" required>
                <label for="email">Email</label>
                <input id="email" name="email" type="email" maxlength="150" autocomplete="email" required>
                <label for="password">Kata sandi (minimal 12 karakter)</label>
                <input id="password" name="password" type="password" minlength="12" autocomplete="new-password" required>
                <label for="confirm_password">Konfirmasi kata sandi</label>
                <input id="confirm_password" name="confirm_password" type="password" minlength="12" autocomplete="new-password" required>
                <button class="button button-primary" type="submit">Buat akun admin</button>
            </form>
        <?php endif; ?>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
