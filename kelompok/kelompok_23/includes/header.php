<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pageTitle = $pageTitle ?? 'Dashboard';

// Menentukan alamat dasar folder project
$scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);
$folderName = '/kelompok_23/';
$folderPosition = strpos($scriptName, $folderName);

$baseUrl = $folderPosition !== false
    ? substr($scriptName, 0, $folderPosition + strlen('/kelompok_23'))
    : '';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= htmlspecialchars($pageTitle) ?> | FEBMart</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/custom.css">
</head>
<body>