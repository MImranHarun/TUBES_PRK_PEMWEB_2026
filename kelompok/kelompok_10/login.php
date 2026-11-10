<?php
require_once __DIR__ . '/config/auth.php';

if (current_user()) {
    redirect('dashboard.php');
}

$errorMessage = '';
$hasUsers = false;
try {
    $hasUsers = (bool) db()->query('SELECT id FROM users LIMIT 1')->fetch();
} catch (PDOException $exception) {
    error_log('SisaSense login database check failed: ' . $exception->getMessage());
    $errorMessage = 'Database belum siap. Impor database/sisa_sense.sql dan periksa konfigurasi koneksi.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $errorMessage === '') {
    require_valid_csrf();
    $identifier = is_string($_POST['identifier'] ?? null) ? trim($_POST['identifier']) : '';
    $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
    $statement = db()->prepare(
        'SELECT id, full_name, username, email, password_hash, role, is_active
         FROM users WHERE username = :username OR email = :email LIMIT 1'
    );
    $statement->execute(['username' => $identifier, 'email' => $identifier]);
    $user = $statement->fetch();

    if ($user && (int) $user['is_active'] === 1 && password_verify($password, $user['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id' => (int) $user['id'],
            'name' => $user['full_name'],
            'username' => $user['username'],
            'email' => $user['email'],
            'role' => $user['role'],
            'is_active' => true,
        ];
        flash('success', 'Selamat datang, ' . $user['full_name'] . '.');
        redirect('dashboard.php');
    }

    $errorMessage = 'Email atau kata sandi tidak sesuai.';
}

$flashMessage = take_flash();
$pageTitle = 'Masuk';
require __DIR__ . '/includes/header.php';
?>
<section class="auth-layout">
    <div class="auth-intro">
        <p class="eyebrow">MANAJEMEN INVENTARIS</p>
        <h1>Stok terkendali,<br>usaha lebih pasti.</h1>
        <p>Catat produk, pantau persediaan, dan kelola pemasok dalam satu tempat.</p>
        <div class="auth-note"><span class="note-icon" aria-hidden="true">✓</span> Riwayat stok tersimpan untuk setiap perubahan.</div>
    </div>
    <div class="panel auth-panel">
        <p class="eyebrow">SELAMAT DATANG</p>
        <h2>Masuk ke SisaSense</h2>
        <p class="muted">Gunakan akun pengelola inventaris Anda.</p>
        <?php if ($flashMessage): ?>
            <div class="notice notice-<?= e($flashMessage['type']) ?>" role="status"><?= e($flashMessage['message']) ?></div>
        <?php endif; ?>
        <?php if ($errorMessage !== ''): ?>
            <div class="notice notice-error" role="alert"><?= e($errorMessage) ?></div>
        <?php endif; ?>
        <form method="post" class="form-stack">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <label for="identifier">Username atau email</label>
            <input id="identifier" name="identifier" maxlength="150" autocomplete="username" required>
            <label for="password">Kata sandi</label>
            <input id="password" name="password" type="password" autocomplete="current-password" required>
            <button class="button button-primary button-wide" type="submit" <?= $errorMessage !== '' ? 'disabled' : '' ?>>Masuk</button>
        </form>
        <?php if (!$hasUsers && $errorMessage === ''): ?>
            <p class="auth-footnote">Belum ada akun pengelola? <a href="<?= e(app_url('setup.php')) ?>">Buat akun admin pertama</a>.</p>
        <?php endif; ?>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>