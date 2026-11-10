<?php

declare(strict_types=1);

// Prefer credentials from the PHP process environment. The root fallback below
// is limited to a loopback host for local Laragon development.
$dbHost = getenv('ATTENDARA_DB_HOST') ?: '127.0.0.1';
$dbName = getenv('ATTENDARA_DB_NAME') ?: 'attendara';
$dbUser = getenv('ATTENDARA_DB_USER');
$dbPassword = getenv('ATTENDARA_DB_PASSWORD');
$dbCharset = 'utf8mb4';
$localHosts = ['127.0.0.1', 'localhost', '::1'];
$isLocalDatabase = in_array(strtolower($dbHost), $localHosts, true);

if ($dbUser === false || $dbUser === '') {
    if ($isLocalDatabase) {
        // Laragon's default local MySQL account; remote hosts still require an explicit user.
        $dbUser = 'root';
    } else {
        error_log('ATTENDARA database configuration is missing ATTENDARA_DB_USER.');
        http_response_code(500);
        exit('Konfigurasi database belum lengkap.');
    }
}

if ($dbPassword === false) {
    $dbPassword = '';
}

$dsn = "mysql:host={$dbHost};dbname={$dbName};charset={$dbCharset}";

try {
    $pdo = new PDO($dsn, $dbUser, $dbPassword, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $exception) {
    // Keep technical details out of the page; inspect server logs during development.
    error_log('ATTENDARA database connection failed: ' . $exception->getMessage());
    http_response_code(500);
    exit('Koneksi database gagal.');
}
