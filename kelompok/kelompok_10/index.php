<?php
require_once __DIR__ . '/config/auth.php';

redirect(current_user() ? 'dashboard.php' : 'login.php');