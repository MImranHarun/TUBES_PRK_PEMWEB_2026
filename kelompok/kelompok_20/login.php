<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

if (isLogin()) {
    if (isAdmin()) {
        header('Location: /admin/dashboard.php');
    } else {
        header('Location: /index.php');
    }
    exit;
}

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifikasiCsrf();

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Silakan masukkan alamat email dan kata sandi Anda.';
    } else {
        $stmt = $pdo->prepare('SELECT id, nama, email, password, role FROM users WHERE email = :email LIMIT 1');
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);

            $_SESSION['user_id'] = (int)$user['id'];
            $_SESSION['nama']    = $user['nama'];
            $_SESSION['email']   = $user['email'];
            $_SESSION['role']    = $user['role'];

            setFlash('success', 'Selamat datang kembali, ' . $user['nama'] . '!');

            if ($user['role'] === 'admin') {
                header('Location: /admin/dashboard.php');
            } else {
                header('Location: /index.php');
            }
            exit;
        } else {
            $error = 'Email atau kata sandi yang Anda masukkan salah.';
        }
    }
}

$pageTitle = 'Masuk ke Akun';
require_once __DIR__ . '/includes/header.php';
?>

<div class="card form-auth">
    <h2 class="card-title" style="text-align: center; margin-bottom: 0.5rem;">Masuk ke Aspiraku</h2>
    <p style="text-align: center; color: var(--color-text-muted); margin-bottom: 1.5rem; font-size: 0.95rem;">
        Silakan masukkan email dan kata sandi untuk melanjutkan.
    </p>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger">
            <?= sanitize($error) ?>
        </div>
    <?php endif; ?>

    <form action="/login.php" method="POST">
        <?= csrfField() ?>

        <div class="form-group">
            <label for="email" class="form-label">Alamat Email</label>
            <input 
                type="email" 
                id="email" 
                name="email" 
                class="form-control" 
                value="<?= sanitize($email) ?>" 
                placeholder="nama@email.com" 
                required 
                autofocus
            >
        </div>

        <div class="form-group">
            <label for="password" class="form-label">Kata Sandi</label>
            <input 
                type="password" 
                id="password" 
                name="password" 
                class="form-control" 
                placeholder="Masukkan kata sandi" 
                required
            >
        </div>

        <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 0.5rem;">
            Masuk
        </button>
    </form>

    <div style="margin-top: 1.5rem; text-align: center; font-size: 0.9rem; color: var(--color-text-muted);">
        Belum memiliki akun? <a href="/register.php" style="color: var(--color-primary); font-weight: 600;">Daftar di sini</a>
    </div>
</div>

<script src="/assets/js/auth.js" defer></script>

<?php
require_once __DIR__ . '/includes/footer.php';

