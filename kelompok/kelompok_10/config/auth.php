<?php
declare(strict_types=1);

require_once __DIR__ . '/database.php';

function start_app_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (getenv('SISASENSE_SECURE_COOKIES') === 'true');
    session_set_cookie_params([
        'httponly' => true,
        'secure' => $https,
        'samesite' => 'Lax',
        'path' => '/',
    ]);
    session_start();

    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header("Content-Security-Policy: default-src 'self'; style-src 'self'; script-src 'self'; img-src 'self' data:; base-uri 'self'; form-action 'self'; frame-ancestors 'none'");
}

function app_url(string $path = ''): string
{
    $base = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
    $base = $base === '/' || $base === '.' ? '' : rtrim($base, '/');
    return $base . '/' . ltrim($path, '/');
}

function redirect(string $path): never
{
    header('Location: ' . app_url($path));
    exit;
}

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function require_valid_csrf(): void
{
    $submitted = $_POST['csrf_token'] ?? '';
    if (!is_string($submitted) || !hash_equals(csrf_token(), $submitted)) {
        http_response_code(419);
        exit('Sesi formulir tidak valid atau telah kedaluwarsa. Muat ulang halaman lalu coba kembali.');
    }
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function take_flash(): ?array
{
    $message = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return is_array($message) ? $message : null;
}

function current_user(): ?array
{
    $user = $_SESSION['user'] ?? null;
    return is_array($user) && user_has_role($user, ['admin', 'cashier'])
        ? $user
        : null;
}

function user_has_role(?array $user, string|array $roles): bool
{
    if ($user === null || empty($user['is_active'])) {
        return false;
    }

    $allowedRoles = is_array($roles) ? $roles : [$roles];
    return in_array($user['role'] ?? null, $allowedRoles, true);
}

function require_login(): void
{
    if (current_user() === null) {
        flash('error', 'Silakan masuk terlebih dahulu.');
        redirect('login.php');
    }
}

function require_role(string|array $roles): void
{
    if (current_user() === null) {
        require_login();
    }

    if (!user_has_role(current_user(), $roles)) {
        http_response_code(403);
        $pageTitle = 'Akses ditolak';
        require __DIR__ . '/../includes/header.php';
        echo '<section class="panel"><p class="eyebrow">AKSES TERBATAS</p><h1>Akses ditolak</h1><p class="muted">Akun Anda tidak memiliki izin untuk membuka halaman ini.</p><a class="button button-secondary" href="' . e(app_url('dashboard.php')) . '">Kembali</a></section>';
        require __DIR__ . '/../includes/footer.php';
        exit;
    }
}

start_app_session();