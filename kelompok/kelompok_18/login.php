<?php

declare(strict_types=1);

require_once __DIR__ . '/config/auth.php';
startAppSession();

if (isLoggedIn()) {
    redirectToDashboard();
}

$error = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['_csrf_token'] ?? null)) {
        http_response_code(403);
        $error = 'Permintaan tidak valid. Muat ulang halaman lalu coba kembali.';
    } else {
    $usernameInput = $_POST['username'] ?? null;
    $passwordInput = $_POST['password'] ?? null;
    $username = is_string($usernameInput) ? trim($usernameInput) : '';
    $password = is_string($passwordInput) ? $passwordInput : '';

    if ($username === '' || $password === '') {
        $error = 'Username dan password wajib diisi.';
    } else {
        require __DIR__ . '/config/database.php';

        try {
            $statement = $pdo->prepare(
                'SELECT id, username, password_hash, role, is_active
                 FROM users
                 WHERE username = :username
                 LIMIT 1'
            );
            $statement->execute(['username' => $username]);
            $user = $statement->fetch();
        } catch (PDOException $exception) {
            error_log('ATTENDARA login query failed: ' . $exception->getMessage());
            http_response_code(500);
            exit('Login sementara tidak dapat diproses.');
        }

        if (
            is_array($user)
            && (bool) $user['is_active']
            && in_array($user['role'], ['admin', 'guru'], true)
            && password_verify($password, $user['password_hash'])
        ) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int) $user['id'];
            $_SESSION['username'] = (string) $user['username'];
            $_SESSION['role'] = (string) $user['role'];
            $_SESSION['auth_created_at'] = time();
            $_SESSION['auth_last_activity'] = time();

            redirectToDashboard();
        }

        $error = 'Username atau password tidak valid, atau akun tidak aktif.';
    }
    }
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk | ATTENDARA</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="flex min-h-screen items-center justify-center bg-[#f6f8f5] p-4 text-slate-900 sm:p-6">
    <main class="grid w-full max-w-5xl overflow-hidden rounded-3xl border border-[#e2e9e2] bg-white shadow-[0_16px_50px_rgba(31,62,42,0.09)] md:min-h-[600px] md:grid-cols-[1.05fr_0.95fr]">
        <section class="flex flex-col justify-between bg-[#edf2eb] p-6 sm:p-9 md:p-12" aria-label="Tentang ATTENDARA">
            <a href="login.php" class="inline-flex w-fit items-center gap-3 rounded-lg focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#376c4d] focus-visible:ring-offset-2">
                <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-[#1e5638] text-white">
                    <svg aria-hidden="true" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                        <path d="m3 10 9-5 9 5-9 5z"/><path d="M7 12v5c3 2 7 2 10 0v-5M21 10v6"/>
                    </svg>
                </span>
                <span>
                    <span class="block text-base font-bold tracking-tight text-[#173e2b]">ATTENDARA</span>
                    <span class="mt-0.5 block text-xs text-[#617366]">Administrasi sekolah</span>
                </span>
            </a>

            <div class="my-10 max-w-md md:my-0">
                <span class="inline-flex items-center gap-2 rounded-full border border-[#d9e4d8] bg-white/70 px-3 py-1.5 text-xs font-medium text-[#47634d]">
                    <span class="h-2 w-2 rounded-full bg-[#6d9273]"></span>
                    Ruang kerja sekolah
                </span>
                <h1 class="mt-5 text-3xl font-semibold leading-tight tracking-tight text-[#173e2b] sm:text-4xl">Selamat datang kembali.</h1>
                <p class="mt-3 max-w-sm text-sm leading-6 text-[#5d6d61] sm:text-base">Kelola aktivitas sekolah dengan lebih tertata melalui satu ruang kerja yang sederhana.</p>
                <div class="mt-8 hidden items-center gap-3 rounded-2xl border border-[#dfe8dc] bg-white/70 p-4 sm:flex">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#e7efe5] text-[#31583c]">
                        <svg aria-hidden="true" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M4 19V5M4 19h17"/><path d="m7 15 4-4 3 2 5-6"/>
                        </svg>
                    </span>
                    <p class="text-sm leading-5 text-[#526257]">Satu akses untuk mendukung kegiatan administrasi sekolah.</p>
                </div>
            </div>

            <p class="hidden text-xs text-[#748176] md:block">ATTENDARA · Sistem informasi sekolah</p>
        </section>

        <section class="flex items-center justify-center p-6 sm:p-10 md:p-12" aria-labelledby="login-title">
            <div class="w-full max-w-sm">
                <div class="mb-8">
                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-[#64806a]">Akses akun</p>
                    <h2 id="login-title" class="mt-2 text-2xl font-semibold tracking-tight text-[#20392a]">Masuk ke akun</h2>
                    <p class="mt-2 text-sm leading-5 text-slate-600">Gunakan username dan password akun Admin atau Guru.</p>
                </div>

                <?php if ($error !== ''): ?>
                    <p class="mb-5 flex gap-2.5 rounded-xl border border-red-200 bg-red-50 p-3.5 text-sm leading-5 text-red-800" role="alert">
                        <svg aria-hidden="true" class="mt-0.5 h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="9"/><path d="M12 8v4M12 16h.01"/>
                        </svg>
                        <span><?= h($error) ?></span>
                    </p>
                <?php endif; ?>

                <form method="post" action="login.php" class="space-y-5" autocomplete="on">
                    <input type="hidden" name="_csrf_token" value="<?= h(csrfToken()) ?>">
                    <div>
                        <label for="username" class="mb-1.5 block text-sm font-medium text-slate-700">Username</label>
                        <input id="username" name="username" type="text" required maxlength="100" autocomplete="username"
                               value="<?= h($username) ?>"
                               class="min-h-11 w-full rounded-xl border border-[#d8e2d8] bg-white px-3.5 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-[#376c4d] focus:outline-none focus:ring-2 focus:ring-[#e6efe6]">
                    </div>
                    <div>
                        <label for="password" class="mb-1.5 block text-sm font-medium text-slate-700">Password</label>
                        <input id="password" name="password" type="password" required autocomplete="current-password"
                               class="min-h-11 w-full rounded-xl border border-[#d8e2d8] bg-white px-3.5 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-[#376c4d] focus:outline-none focus:ring-2 focus:ring-[#e6efe6]">
                    </div>
                    <button type="submit" class="inline-flex min-h-11 w-full items-center justify-center rounded-xl bg-[#1e5638] px-4 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-[#17452d] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#376c4d] focus-visible:ring-offset-2">
                        Masuk
                    </button>
                </form>
                <p class="mt-6 text-center text-xs leading-5 text-slate-500">Halaman ini hanya untuk akun Admin dan Guru.</p>
            </div>
        </section>
    </main>
</body>
</html>
