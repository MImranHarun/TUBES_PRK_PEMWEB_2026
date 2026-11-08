<?php

declare(strict_types=1);

/** Start a hardened session once, before sending any response body. */
function startAppSession(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    ini_set('session.use_strict_mode', '1');
    session_set_cookie_params([
        'httponly' => true,
        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'samesite' => 'Lax',
        'path' => '/',
    ]);
    session_start();

    $now = time();
    $hasIdentity = isset($_SESSION['user_id']) || isset($_SESSION['username']) || isset($_SESSION['role']);
    if ($hasIdentity) {
        $validIdentity = isset($_SESSION['user_id'], $_SESSION['username'], $_SESSION['role'], $_SESSION['auth_created_at'], $_SESSION['auth_last_activity'])
            && is_int($_SESSION['user_id'])
            && $_SESSION['user_id'] > 0
            && is_string($_SESSION['username'])
            && $_SESSION['username'] !== ''
            && in_array($_SESSION['role'], ['admin', 'guru'], true)
            && is_int($_SESSION['auth_created_at'])
            && is_int($_SESSION['auth_last_activity'])
            && $_SESSION['auth_last_activity'] <= $now
            && $_SESSION['auth_created_at'] <= $now
            && $now - $_SESSION['auth_last_activity'] <= 1800
            && $now - $_SESSION['auth_created_at'] <= 28800;

        if (!$validIdentity) {
            $_SESSION = [];
            session_regenerate_id(true);
        } else {
            $_SESSION['auth_last_activity'] = $now;
        }
    }

    if (!isset($_SESSION['_csrf_token']) || !is_string($_SESSION['_csrf_token'])) {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }
}

/** Return the session's CSRF token, creating it if necessary. */
function csrfToken(): string
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        throw new LogicException('Panggil startAppSession() sebelum csrfToken().');
    }

    if (!isset($_SESSION['_csrf_token']) || !is_string($_SESSION['_csrf_token'])) {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['_csrf_token'];
}

/** Verify a submitted CSRF token using a timing-safe comparison. */
function verifyCsrfToken(mixed $submittedToken): bool
{
    return is_string($submittedToken)
        && isset($_SESSION['_csrf_token'])
        && is_string($_SESSION['_csrf_token'])
        && hash_equals($_SESSION['_csrf_token'], $submittedToken);
}

/** Escape a value for safe insertion into HTML text or quoted attributes. */
function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Return the authenticated identity, or null when no valid identity is stored. */
function currentUser(): ?array
{
    if (
        !isset($_SESSION['user_id'], $_SESSION['username'], $_SESSION['role'])
        || !is_int($_SESSION['user_id'])
        || !is_string($_SESSION['username'])
        || !in_array($_SESSION['role'], ['admin', 'guru'], true)
    ) {
        return null;
    }

    return [
        'id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'role' => $_SESSION['role'],
    ];
}

function isLoggedIn(): bool
{
    return currentUser() !== null;
}

/** Build a URL relative to this project, including when hosted in a subfolder. */
function appUrl(string $path): string
{
    $scriptPath = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/');
    $pathSegments = array_values(array_filter(explode('/', trim($scriptPath, '/')), static fn (string $segment): bool => $segment !== ''));
    if ($pathSegments !== []) {
        array_pop($pathSegments);
    }

    $lastDirectory = $pathSegments === [] ? '' : $pathSegments[array_key_last($pathSegments)];
    if (in_array($lastDirectory, ['admin', 'guru'], true)) {
        array_pop($pathSegments);
    }

    $base = $pathSegments === [] ? '' : '/' . implode('/', $pathSegments);
    return $base . '/' . ltrim($path, '/');
}

/** Require an authenticated user; redirect guests to the login form. */
function requireLogin(): void
{
    if (!isLoggedIn()) {
        header('Location: ' . appUrl('login.php'));
        exit;
    }
}

/** Require one role; authenticated users with another role receive HTTP 403. */
function requireRole(string $role): void
{
    if (!in_array($role, ['admin', 'guru'], true)) {
        throw new InvalidArgumentException('Role yang diminta tidak valid.');
    }

    requireLogin();
    if (currentUser()['role'] !== $role) {
        http_response_code(403);
        exit('Akses ditolak.');
    }
}

/** Send the user to the dashboard assigned to their authenticated role. */
function redirectToDashboard(): void
{
    requireLogin();
    $role = currentUser()['role'];
    header('Location: ' . appUrl($role . '/dashboard.php'));
    exit;
}
