<?php
/**
 * File: auth/login.php
 * Modul 1 (Anggota 1): Autentikasi Pengguna Multi-Role (Admin, Petugas, Pendonor).
 * Struktur HTML disesuaikan dengan CSS eksternal milik kelompok.
 */

require_once __DIR__ . '/../config/auth_check.php';

// Jika sudah login, langsung arahkan ke dashboard masing-masing
if (isLoggedIn()) {
    redirectByRole();
}

$pageTitle = 'OneBlood Login';
$errors    = [];
$userVal   = '';

// Proses form login saat metode request POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userVal  = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    // 1. Validasi Input
    if ($userVal === '') {
        $errors[] = 'Username atau Email wajib diisi.';
    }
    if ($password === '') {
        $errors[] = 'Password wajib diisi.';
    }

    // 2. Pencocokan Kredensial via PDO (Bisa menggunakan Email maupun Nama)
    if (empty($errors)) {
        try {
            $stmt = $db->prepare("
                SELECT id, nama, email, password, role, gol_darah, rhesus, status_aktif 
                FROM users 
                WHERE email = :val OR nama = :val 
                LIMIT 1
            ");
            $stmt->execute([':val' => $userVal]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, (string)$user['password'])) {
                if ((int)$user['status_aktif'] !== 1) {
                    $errors[] = 'Akun Anda sedang dinonaktifkan oleh Administrator.';
                } else {
                    session_regenerate_id(true);

                    $_SESSION['user_id']   = (int)$user['id'];
                    $_SESSION['nama']      = (string)$user['nama'];
                    $_SESSION['email']     = (string)$user['email'];
                    $_SESSION['role']      = (string)$user['role'];
                    $_SESSION['gol_darah'] = (string)($user['gol_darah'] ?? '-');
                    $_SESSION['rhesus']    = (string)($user['rhesus'] ?? '');

                    setFlash('success', 'Selamat datang kembali, ' . $user['nama'] . '!');
                    redirectByRole((string)$user['role']);
                }
            } else {
                $errors[] = 'Username/Email atau Password yang Anda masukkan salah.';
            }
        } catch (PDOException $e) {
            $errors[] = 'Terjadi kesalahan basis data: ' . $e->getMessage();
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<link rel="stylesheet" href="<?= base_url('css/style.css') ?>">

<div class="login-wrapper">
    <!-- SISI KIRI: Tampilan Gambar -->
    <div class="login-illustration">
        <img src="<?= base_url('assets/donor3.jpg') ?>" alt="Ilustrasi OneBlood">
    </div>

    <!-- SISI KANAN: Tampilan Kartu Login -->
    <div class="login-right">
        <div class="login-card">
            <h2 class="login-title">Masuk Ke OneBlood</h2>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <?php foreach ($errors as $err): ?>
                        <div><?= e($err) ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form action="<?= base_url('auth/login.php') ?>" method="POST" autocomplete="off">
                <div class="input-group">
                    <i class="fa-solid fa-user"></i>
                    <input type="text" name="username" placeholder="Username/Email" value="<?= e($userVal) ?>" required>
                </div>

                <div class="input-group">
                    <i class="fa-solid fa-eye" id="toggleEye" onclick="togglePassword()" style="cursor: pointer;"></i>
                    <input type="password" id="passwordInput" name="password" placeholder="Password" required>
                </div>

                 <div class="form-options">
                    <label class="remember-me">
                        <input type="checkbox" name="remember">
                        <span>Ingat saya</span>
                    </label>
                    <a href="#" class="forget-link">Lupa password?</a>
                </div>
                <button type="submit" class="btn-login">Masuk</button>
                <div class="register-prompt">
                    Belum punya akun? <a href="<?= base_url('auth/register.php') ?>">Daftar sekarang</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function togglePassword() {
    const pwd = document.getElementById('passwordInput');
    const eye = document.getElementById('toggleEye');
    if (pwd.type === 'password') {
        pwd.type = 'text';
        eye.classList.remove('fa-eye');
        eye.classList.add('fa-eye-slash');
    } else {
        pwd.type = 'password';
        eye.classList.remove('fa-eye-slash');
        eye.classList.add('fa-eye');
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>