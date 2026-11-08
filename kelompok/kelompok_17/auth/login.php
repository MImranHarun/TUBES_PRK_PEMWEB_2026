<?php
session_start();
require_once '../config/database.php';

$pesan = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 1. Validasi input di sisi server (Mencegah input kosong)
    $npm = isset($_POST['npm']) ? trim($_POST['npm']) : '';
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';

    if (empty($npm) || empty($password)) {
        $pesan = "<p class='text-red-500 mb-4'>NPM dan Password wajib diisi!</p>";
    } else {
        // 2. Tidak memakai SELECT * (Sebutkan nama kolom secara spesifik)
        $stmt = $pdo->prepare("SELECT id_user, nama_lengkap, password, role FROM users WHERE npm = ?");
        $stmt->execute([$npm]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            
            // 3. Panggil session_regenerate_id(true) untuk mencegah Session Fixation
            session_regenerate_id(true);

            $_SESSION['id_user'] = $user['id_user'];
            $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
            $_SESSION['role'] = $user['role'];

            if ($user['role'] == 'admin') {
                header("Location: ../admin/index.php");
            } else {
                header("Location: ../index.php");
            }
            exit();
        } else {
            $pesan = "<p class='text-red-500 mb-4'>NPM atau Password salah!</p>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Login - KabarSih</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center h-screen">
    <div class="bg-white p-8 rounded-lg shadow-md w-96">
        <h2 class="text-2xl font-bold mb-6 text-center">Login KabarSih</h2>
        <?= $pesan; ?>
        <form method="POST" action="">
            <div class="mb-4">
                <label class="block text-gray-700">NPM</label>
                <input type="text" name="npm" required class="w-full px-3 py-2 border rounded">
            </div>
            <div class="mb-6">
                <label class="block text-gray-700">Password</label>
                <input type="password" name="password" required class="w-full px-3 py-2 border rounded">
            </div>
            <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded hover:bg-blue-700">Login</button>
        </form>
        <p class="mt-4 text-sm text-center">Belum punya akun? <a href="register.php" class="text-blue-600">Daftar sekarang</a></p>
    </div>
</body>
</html>