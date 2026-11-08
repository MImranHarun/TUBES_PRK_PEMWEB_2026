<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit;
}

if ($_SESSION['role'] !== 'admin') {
    header("Location: dashboard.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Pengguna | FEBMart</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        body {
            background: #f3f4f6;
            font-family: Arial, sans-serif;
            padding: 35px 20px;
        }

        .container-form {
            max-width: 650px;
            margin: auto;
            background: white;
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 5px 25px rgba(0,0,0,0.08);
        }

        .header {
            background: linear-gradient(145deg, #29245c, #5142a5);
            color: white;
            padding: 25px 30px;
        }

        .header h3 {
            font-weight: bold;
        }

        .header i {
            color: #ffad42;
        }

        .form-content {
            padding: 30px;
        }

        .form-label {
            color: #29245c;
            font-weight: bold;
            font-size: 14px;
        }

        .form-control, .form-select {
            padding: 11px;
            font-size: 14px;
        }

        .btn-save {
            background: #ffad42;
            color: #29245c;
            border: none;
            padding: 11px 20px;
            font-weight: bold;
        }

        .btn-save:hover {
            background: #f49a25;
            color: #29245c;
        }

        .btn-back {
            background: #f5f4fb;
            color: #5142a5;
            padding: 11px 20px;
            text-decoration: none;
            border-radius: 6px;
            font-size: 14px;
        }
    </style>
</head>

<body>

<div class="container-form">

    <div class="header">
        <h3><i class="bi bi-cart3 me-2"></i>FEBMart</h3>
        <h4 class="mt-4">Tambah Pengguna</h4>
        <p class="mb-0">Tambahkan akun Admin atau Kasir baru.</p>
    </div>

    <div class="form-content">

        <form action="proses_tambah_user.php" method="POST">

            <div class="mb-3">
                <label class="form-label">Nama Lengkap</label>
                <input type="text" name="name" class="form-control"
                       placeholder="Masukkan nama lengkap" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-control"
                       placeholder="Masukkan username" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control"
                       placeholder="Masukkan email">
            </div>

            <div class="mb-3">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control"
                       placeholder="Masukkan password" required>
            </div>

            <div class="mb-4">
                <label class="form-label">Role Pengguna</label>
                <select name="role" class="form-select" required>
                    <option value="">-- Pilih Role --</option>
                    <option value="admin">Admin</option>
                    <option value="kasir">Kasir</option>
                </select>
            </div>

            <div class="d-flex justify-content-between flex-wrap gap-2">
                <a href="users.php" class="btn-back">
                    <i class="bi bi-arrow-left me-1"></i>Kembali
                </a>

                <button type="submit" class="btn btn-save">
                    <i class="bi bi-save me-1"></i>Simpan Pengguna
                </button>
            </div>

        </form>

    </div>
</div>

</body>
</html>