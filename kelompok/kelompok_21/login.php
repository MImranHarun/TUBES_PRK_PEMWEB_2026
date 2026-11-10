<?php
require __DIR__ . '/includes/auth.php';
if (!empty($_SESSION['guru'])) { header('Location: index.php'); exit; }
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) {
        $error = 'Sesi formulir tidak valid. Coba lagi.';
    } else {
        try {
            require __DIR__ . '/config/database.php';
            $stmt = $pdo->prepare('SELECT * FROM GURU WHERE email = ? LIMIT 1');
            $stmt->execute([trim($_POST['email'] ?? '')]); $guru = $stmt->fetch();
            if ($guru && password_verify($_POST['password'] ?? '', $guru['password'])) {
                session_regenerate_id(true);
                $_SESSION['guru'] = ['id_guru' => $guru['id_guru'], 'nama_guru' => $guru['nama_guru'], 'peran' => $guru['peran']];
                $_SESSION['csrf'] = bin2hex(random_bytes(32));
                header('Location: index.php'); exit;
            }
            $error = 'Email atau password salah.';
        } catch (PDOException $exception) {
            error_log($exception->getMessage());
            $error = 'Database belum terhubung. Periksa konfigurasi dan impor SQL.';
        }
    }
}
?>
<!doctype html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Masuk • Arunika</title>
        <link rel="stylesheet" href="css/style.css">
    </head>
    <body class="login-page">
        <main class="login-wrap">
            <section class="login-intro">
                <a class="brand" href="login.php">
                    <span class="logo">A</span> arunika<span class="dot">.</span>
                </a>
                <p class="eyebrow">RUANG TUMBUH AKADEMIK</p>
                <h1>Awali langkah.<br>Tumbuhkan potensi.</h1>
                <p>Kelola data kelas dengan rapi, untuk mendampingi setiap langkah belajar siswa.</p>
                <div class="school-art" aria-hidden="true">
                    <span>✦</span>
                    <div>ILMU · KARAKTER · MASA DEPAN</div>
                </div>
                <small>Aplikasi Rekapitulasi Nilai dan Kedisiplinan Kelas</small>
            </section>
            <section class="login-card">
                <p class="eyebrow">SELAMAT DATANG KEMBALI</p>
                <h2>Masuk ke Arunika</h2>
                <p>Gunakan akun guru yang sudah terdaftar.</p>
                <?php if ($error): ?><div class="message error" role="alert"><?= e($error) ?></div><?php endif; ?>
                <form method="post">
                    <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
                    <label>Email<input type="email" name="email" required maxlength="100" autocomplete="username" value="<?= e($_POST['email'] ?? '') ?>" placeholder="nama@sekolah.sch.id">
                    </label>
                    <label>Password<input type="password" name="password" required autocomplete="current-password">
                    </label>
                    <button class="primary" type="submit">Masuk ke ruang kelas →</button>
                </form>
                <small>Belum memiliki akun? Hubungi pengelola aplikasi sekolah.</small>
            </section>
        </main>
    </body>
</html>

