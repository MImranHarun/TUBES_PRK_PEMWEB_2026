<?php
require __DIR__ . '/bootstrap.php';
if ($method !== 'GET') respond(null, 'Metode tidak didukung.', 405);
// Direktori hanya berisi identitas untuk pilihan wali/penugasan, tanpa password.
respond($pdo->query('SELECT id_guru, nama_guru FROM GURU ORDER BY nama_guru')->fetchAll());
