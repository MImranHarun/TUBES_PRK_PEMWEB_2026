<?php

declare(strict_types=1);

require_once __DIR__ . '/config/auth.php';
startAppSession();

if (isLoggedIn()) {
    redirectToDashboard();
}

header('Location: ' . appUrl('login.php'));
exit;
