<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ]);
    session_start();
}

require_once __DIR__ . '/../config/database.php';

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function url(string $path = ''): string
{
    return rtrim(APP_BASE_URL, '/') . '/' . ltrim($path, '/');
}

function redirect(string $path)
{
    header('Location: ' . url($path));
    exit;
}

function csrf_token(): string
{
    if (empty($_SESSION['_csrf_token'])) {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $token = $_POST['_csrf'] ?? '';
    if (!is_string($token) || !hash_equals(csrf_token(), $token)) {
        http_response_code(419);
        exit('Sesi formulir tidak valid atau sudah kedaluwarsa. Silakan kembali dan muat ulang halaman.');
    }
}

function set_flash(string $type, string $message): void
{
    $_SESSION['_flash'] = ['type' => $type, 'message' => $message];
}

function get_flash(): ?array
{
    if (empty($_SESSION['_flash'])) {
        return null;
    }
    $flash = $_SESSION['_flash'];
    unset($_SESSION['_flash']);
    return $flash;
}

function admin_logged_in(): bool
{
    return !empty($_SESSION['admin']['id']);
}

function require_admin(): void
{
    if (!admin_logged_in()) {
        set_flash('warning', 'Silakan login sebagai admin terlebih dahulu.');
        redirect('admin/login.php');
    }
}

function image_url(?string $image): string
{
    return $image ? url($image) : '';
}

function upload_equipment_images(array $files): array
{
    $uploaded = [];
    $names = $files['name'] ?? [];

    if (!is_array($names)) {
        return [];
    }

    $fileCount = 0;
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    try {
        foreach ($names as $index => $originalName) {
            $errorCode = $files['error'][$index] ?? UPLOAD_ERR_NO_FILE;

            if ($errorCode === UPLOAD_ERR_NO_FILE || $originalName === '') {
                continue;
            }

            $fileCount++;
            if ($fileCount > MAX_EQUIPMENT_IMAGES) {
                throw new RuntimeException('Maksimal ' . MAX_EQUIPMENT_IMAGES . ' foto dapat diunggah sekaligus.');
            }

            if ($errorCode !== UPLOAD_ERR_OK) {
                throw new RuntimeException('Salah satu foto gagal diunggah. Periksa ukuran file dan pengaturan server.');
            }

            $tmpName = $files['tmp_name'][$index] ?? '';
            $fileSize = (int) ($files['size'][$index] ?? 0);

            if ($fileSize <= 0 || $fileSize > MAX_UPLOAD_SIZE) {
                throw new RuntimeException('Setiap foto harus berukuran lebih dari 0 byte dan maksimal 4 MB.');
            }

            if (!is_uploaded_file($tmpName)) {
                throw new RuntimeException('File foto tidak dikenali sebagai unggahan yang valid.');
            }

            $mime = $finfo->file($tmpName);
            if (!isset($allowed[$mime]) || @getimagesize($tmpName) === false) {
                throw new RuntimeException('Format setiap foto harus berupa JPG, PNG, atau WEBP yang valid.');
            }

            if (!is_dir(UPLOAD_DIR) && !mkdir(UPLOAD_DIR, 0755, true) && !is_dir(UPLOAD_DIR)) {
                throw new RuntimeException('Folder upload tidak dapat dibuat.');
            }

            $filename = bin2hex(random_bytes(16)) . '.' . $allowed[$mime];
            $destination = UPLOAD_DIR . DIRECTORY_SEPARATOR . $filename;

            if (!move_uploaded_file($tmpName, $destination)) {
                throw new RuntimeException('Salah satu foto tidak dapat disimpan ke server.');
            }

            // Keep the original input index so the selected cover image can be identified.
            $uploaded[$index] = 'uploads/' . $filename;
        }
    } catch (Throwable $exception) {
        foreach ($uploaded as $path) {
            remove_equipment_image($path);
        }
        throw $exception;
    }

    return $uploaded;
}

function remove_equipment_image(?string $path): void
{
    if (!$path || !str_starts_with($path, 'uploads/')) {
        return;
    }

    $fullPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path);
    if (is_file($fullPath)) {
        @unlink($fullPath);
    }
}
