
<?php

require_once __DIR__ . '/../config/app.php';

if (!is_logged_in()) {
    header('Location: ' . url('auth/login.php'));
    exit;
}
