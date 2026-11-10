<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KabarSih - Portal Berita & Aspirasi FISIP</title>
    <!-- Tailwind CSS via CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 text-gray-800 min-h-screen flex flex-col justify-between font-sans">

    <!-- Navigation Bar -->
    <nav class="bg-blue-900 text-white shadow-md sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <!-- Logo -->
                <div class="flex items-center space-x-3">
                    <a href="index.php" class="font-bold text-xl tracking-wide flex items-center space-x-2">
                        <span class="bg-blue-600 px-2.5 py-1 rounded-lg text-white font-extrabold text-sm">KS</span>
                        <span>KabarSih FISIP</span>
                    </a>
                </div>

                <!-- Nav Links -->
                <div class="flex items-center space-x-4">
                    <a href="index.php" class="hover:bg-blue-800 px-3 py-2 rounded-md text-sm font-medium transition">Beranda</a>
                    
                    <?php if (isset($_SESSION['id_user'])): ?>
                        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                            <a href="admin/index.php" class="bg-amber-500 hover:bg-amber-600 px-3 py-2 rounded-md text-sm font-medium transition text-white">Panel Admin</a>
                        <?php else: ?>
                            <a href="dashboard_user.php" class="hover:bg-blue-800 px-3 py-2 rounded-md text-sm font-medium transition">Dashboard Saya</a>
                        <?php endif; ?>
                        
                        <a href="auth/logout.php" class="bg-red-600 hover:bg-red-700 px-3 py-2 rounded-md text-sm font-medium transition">Logout</a>
                    <?php else: ?>
                        <a href="auth/login.php" class="hover:bg-blue-800 px-3 py-2 rounded-md text-sm font-medium transition">Login</a>
                        <a href="auth/register.php" class="bg-blue-600 hover:bg-blue-700 px-3 py-2 rounded-md text-sm font-medium transition">Daftar</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Container -->
    <main class="mb-auto">