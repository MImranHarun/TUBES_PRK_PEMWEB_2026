
<?php

require_once __DIR__ . '/auth_check.php';

if (!has_role('admin')) {
    http_response_code(403);
    exit('403 - Anda tidak memiliki akses ke halaman ini.');
}
