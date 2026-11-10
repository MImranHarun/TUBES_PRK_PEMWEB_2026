<?php
declare(strict_types=1);

define('APP_NAME', 'LABCAT');
define('APP_TAGLINE', 'Laboratory Catalog System');

/*
 * Jika folder project bukan /labcat, sesuaikan nilai ini.
 * Contoh: folder htdocs/katalog-lab berarti gunakan '/katalog-lab'.
 */
define(
    'APP_BASE_URL',
    '/TUBES_PRK_PEMWEB_2026/kelompok/kelompok_02'
);

define('UPLOAD_DIR', dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads');
define('MAX_UPLOAD_SIZE', 4 * 1024 * 1024); // Maksimal 4 MB per foto

define('MAX_EQUIPMENT_IMAGES', 10); // Maksimal 10 foto untuk setiap alat
