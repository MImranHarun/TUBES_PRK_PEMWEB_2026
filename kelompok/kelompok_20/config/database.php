```php
<?php

$db_host = 'localhost';
$db_name = 'tubes_ppw';
$db_user = 'root';
$db_pass = '';
$db_charset = 'utf8mb4';

$dsn = "mysql:host=$db_host;dbname=$db_name;charset=$db_charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    
    $server = new PDO(
        "mysql:host=$db_host;charset=$db_charset",
        $db_user,
        $db_pass,
        $options
    );

    $server->exec(
        "CREATE DATABASE IF NOT EXISTS `$db_name`
         CHARACTER SET utf8mb4
         COLLATE utf8mb4_unicode_ci"
    );

    $pdo = new PDO($dsn, $db_user, $db_pass, $options);

    
    $tabelWajib = ['users', 'kategori', 'laporan', 'tanggapan'];

    $tabelAda = $pdo->query("SHOW TABLES")
                    ->fetchAll(PDO::FETCH_COLUMN);

    $tabelKurang = array_diff($tabelWajib, $tabelAda);

    
    if (!empty($tabelKurang)) {

        $fileDatabase = __DIR__ . '/../database/database.sql';
        $fileSeed = __DIR__ . '/../database/seed.sql';

        if (!file_exists($fileDatabase) || !file_exists($fileSeed)) {
            throw new Exception("File SQL database atau seed tidak ditemukan.");
        }

        // Import struktur tabel dan kategori
        $sqlDatabase = file_get_contents($fileDatabase);

        foreach (explode(';', $sqlDatabase) as $query) {
            if (trim($query) !== '') {
                $pdo->exec($query);
            }
        }

        // Import data awal
        $sqlSeed = file_get_contents($fileSeed);

        foreach (explode(';', $sqlSeed) as $query) {
            if (trim($query) !== '') {
                $pdo->exec($query);
            }
        }
    }

} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());

    die("Koneksi database gagal. Periksa konfigurasi MySQL Anda.");
} catch (Exception $e) {
    error_log("Database setup error: " . $e->getMessage());

    die("Setup database gagal. Periksa file database.sql dan seed.sql.");
}


