<?php

date_default_timezone_set('Asia/Jakarta');

class Database {
    private string $host = "localhost";
    private string $db_name = "db_oneblood";
    private string $username = "root";
    private string $password = "";
    private int $port = 3306;
    private ?PDO $conn = null;

    public function getConnection(): PDO {
        if ($this->conn === null) {
            $dsn = "mysql:host={$this->host};port={$this->port};dbname={$this->db_name};charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            try {
                $this->conn = new PDO($dsn, $this->username, $this->password, $options);
            } catch (PDOException $e) {
                throw new PDOException($e->getMessage(), (int)$e->getCode());
            }
        }

        return $this->conn;
    }
}

$database = new Database();
$db = $database->getConnection();
$pdo = $db;

function base_url(string $path = ''): string {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https://' : 'http://');
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

    $projectRoot = str_replace('\\', '/', (string)realpath(__DIR__ . '/..'));
    $docRoot = str_replace('\\', '/', (string)realpath($_SERVER['DOCUMENT_ROOT'] ?? ''));

    $baseFolder = '';
    if (!empty($docRoot) && strpos($projectRoot, $docRoot) === 0) {
        $baseFolder = substr($projectRoot, strlen($docRoot));
    }

    $baseUrl = rtrim($protocol . $host . $baseFolder, '/');
    $cleanPath = ltrim($path, '/');

    return $cleanPath !== '' ? $baseUrl . '/' . $cleanPath : $baseUrl;
}

function e(?string $string): string {
    return htmlspecialchars((string)($string ?? ''), ENT_QUOTES, 'UTF-8');
}

function tgl_indo(?string $tanggal): string {
    if (empty($tanggal) || $tanggal === '0000-00-00') {
        return '-';
    }

    $bulan = [
        1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun',
        'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'
    ];

    $pecah = explode('-', substr($tanggal, 0, 10));
    if (count($pecah) !== 3) {
        return $tanggal;
    }

    return (int)$pecah[2] . ' ' . ($bulan[(int)$pecah[1]] ?? '') . ' ' . $pecah[0];
}