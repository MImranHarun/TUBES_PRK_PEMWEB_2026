<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

if (isLogin()) {
    header('Location: /index.php');
    exit;
}

$errors = [];
$nama = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifikasiCsrf();

    $nama = trim($_POST['nama'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $konfirmasi_password = $_POST['konfirmasi_password'] ?? '';

    if (empty($nama)) {
        $errors[] = 'Nama lengkap wajib diisi.';
    } elseif (strlen($nama) > 100) {
        $errors[] = 'Nama lengkap maksimal 100 karakter.';
    }

    if (empty($email)) {
        $errors[] = 'Alamat email wajib diisi.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Format alamat email tidak valid.';
    } else {
        $stmtCek = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
        $stmtCek->execute([':email' => $email]);
        if ($stmtCek->fetch()) {
            $errors[] = 'Alamat email ini sudah terdaftar. Silakan gunakan email lain atau masuk.';
        }
    }

    if (empty($password)) {
        $errors[] = 'Kata sandi wajib diisi.';
    } elseif (strlen($password) < 6) {
        $errors[] = 'Kata sandi minimal 6 karakter.';
    }

    if ($password !== $konfirmasi_password) {
        $errors[] = 'Konfirmasi kata sandi tidak cocok dengan kata sandi.';
    }

    if (empty($errors)) {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        $stmtInsert = $pdo->prepare('
            INSERT INTO users (nama, email, password, role) 
            VALUES (:nama, :email, :password, :role)
        ');

        $sukses = $stmtInsert->execute([
            ':nama' => $nama,
            ':email' => $email,
            ':password' => $passwordHash,
            ':role' => 'user'
        ]);

        if ($sukses) {
            setFlash('success', 'Pendaftaran akun berhasil! Silakan masuk dengan akun baru Anda.');
            header('Location: /login.php');
            exit;
        } else {
            $errors[] = 'Terjadi kesalahan sistem saat mendaftar. Silakan coba beberapa saat lagi.';
        }
    }
}

$pageTitle = 'Daftar Akun Pelapor';
require_once __DIR__ . '/includes/header.php';
?>

<div class="card form-auth">
    <h2 class="card-title" style="text-align: center; margin-bottom: 0.5rem;">Daftar Akun Baru</h2>
    <p style="text-align: center; color: var(--color-text-muted); margin-bottom: 1.5rem; font-size: 0.95rem;">
        Sampaikan aspirasi dan pengaduan untuk kemajuan lingkungan FISIP.
    </p>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul style="margin: 0; padding-left: 1.25rem;">
                <?php foreach ($errors as $err): ?>
                    <li><?= sanitize($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form action="/register.php" method="POST">
        <?= csrfField() ?>

        <div class="form-group">
            <label for="nama" class="form-label">Nama Lengkap</label>
            <input 
                type="text" 
                id="nama" 
                name="nama" 
                class="form-control" 
                value="<?= sanitize($nama) ?>" 
                placeholder="Contoh: Nabila Putri" 
                required
            >
        </div>

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
            >
        </div>

        <div class="form-group">
            <label for="password" class="form-label">Kata Sandi</label>
            <input 
                type="password" 
                id="password" 
                name="password" 
                class="form-control" 
                placeholder="Minimal 6 karakter" 
                minlength="6" 
                required
            >
        </div>

        <div class="form-group">
            <label for="konfirmasi_password" class="form-label">Konfirmasi Kata Sandi</label>
            <input 
                type="password" 
                id="konfirmasi_password" 
                name="konfirmasi_password" 
                class="form-control" 
                placeholder="Ulangi kata sandi" 
                minlength="6" 
                required
            >
        </div>

        <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 0.5rem;">
            Daftar Sekarang
        </button>
    </form>

    <div style="margin-top: 1.5rem; text-align: center; font-size: 0.9rem; color: var(--color-text-muted);">
        Sudah memiliki akun? <a href="/login.php" style="color: var(--color-primary); font-weight: 600;">Masuk di sini</a>
    </div>
</div>

<script src="/assets/js/auth.js" defer></script>

<?php
require_once __DIR__ . '/includes/footer.php';

