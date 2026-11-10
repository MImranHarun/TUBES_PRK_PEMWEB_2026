<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin | FEBMart</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f3f4f6;
            font-family: Arial, sans-serif;
            padding: 25px;
        }

        .login-container {
            width: 100%;
            max-width: 950px;
            min-height: 550px;
            display: flex;
            background: white;
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 5px 25px rgba(0, 0, 0, 0.08);
        }

        .welcome-section {
            width: 45%;
            background: linear-gradient(145deg, #29245c, #5142a5);
            color: white;
            padding: 35px;
            display: flex;
            flex-direction: column;
        }

        .brand {
            font-size: 25px;
            font-weight: bold;
        }

        .brand i {
            color: #ffad42;
            margin-right: 7px;
        }

        .welcome-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 20px;
        }

        .welcome-icon {
            width: 105px;
            height: 105px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            margin-bottom: 25px;
        }

        .welcome-icon i {
            font-size: 52px;
            color: #ffad42;
        }

        .welcome-content h2 {
            font-size: 27px;
            font-weight: bold;
            margin-bottom: 13px;
        }

        .welcome-content p {
            color: #d5d9e2;
            font-size: 14px;
            line-height: 1.7;
            max-width: 290px;
        }

        .welcome-footer {
            color: #b9c1d0;
            font-size: 12px;
            text-align: center;
        }

        .form-section {
            width: 55%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 55px 65px;
        }

        .form-section h2 {
            color: #29245c;
            font-size: 27px;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .form-description {
            color: #888;
            font-size: 14px;
            margin-bottom: 35px;
        }

        .form-label {
            font-size: 14px;
            font-weight: 600;
            color: #333;
        }

        .input-group-text {
            background: #f5f4fb;
            border: 1px solid #ddd;
            color: #5142a5;
            padding: 12px 14px;
        }

        .input-group .form-control {
            padding: 12px;
            border: 1px solid #ddd;
            border-left: none;
            font-size: 14px;
        }

        .input-group .form-control:focus {
            border-color: #5142a5;
            box-shadow: none;
        }

        .input-group:focus-within .input-group-text {
            border-color: #5142a5;
        }

        /* Tombol login */
        .btn-login {
            width: 100%;
            padding: 12px;
            margin-top: 12px;
            background: #ffad42;
            color: #29245c;
            border: none;
            border-radius: 7px;
            font-weight: bold;
            transition: 0.2s;
        }

        .btn-login:hover {
            background: #f49a25;
            color: #29245c;
        }

        .copyright {
            color: #999;
            font-size: 12px;
            text-align: center;
            margin-top: 30px;
        }

        /* Tampilan mobile */
        @media (max-width: 768px) {
            .login-container {
                max-width: 430px;
            }

            .welcome-section {
                display: none;
            }

            .form-section {
                width: 100%;
                padding: 40px 30px;
            }
        }
    </style>
</head>

<body>

<div class="login-container">

    <div class="welcome-section">

        <div class="brand">
            <i class="bi bi-cart3"></i>FEBMart
        </div>

        <div class="welcome-content">

            <div class="welcome-icon">
                <i class="bi bi-cart-check"></i>
            </div>

            <h2>Selamat Datang!</h2>

            <p>
                Kelola produk dan transaksi toko
                dengan lebih mudah melalui
                sistem manajemen FEBMart.
            </p>

        </div>

        <div class="welcome-footer">
            © 2026 FEBMart
        </div>

    </div>

    <div class="form-section">

        <h2>Login Admin</h2>

        <p class="form-description">
            Silakan masuk menggunakan akun admin Anda.
        </p>

        <?php if (isset($_GET['error'])): ?>
    <div class="alert alert-danger" role="alert">
        <?php
        if ($_GET['error'] === 'gagal') {
            echo "Username atau password salah!";
        } elseif ($_GET['error'] === 'nonaktif') {
            echo "Akun Anda sedang dinonaktifkan!";
        } elseif ($_GET['error'] === 'kosong') {
            echo "Username dan password wajib diisi!";
        }
        ?>
    </div>
<?php endif; ?>

        <form action="proses_login.php" method="POST">

            <div class="mb-3">
                <label for="username" class="form-label">Username</label>

                <div class="input-group">
                    <span class="input-group-text">
                        <i class="bi bi-person"></i>
                    </span>

                    <input
                        type="text"
                        id="username"
                        name="username"
                        class="form-control"
                        placeholder="Masukkan username"
                        autocomplete="username"
                        required
                    >
                </div>
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">Password</label>

                <div class="input-group">
                    <span class="input-group-text">
                        <i class="bi bi-lock"></i>
                    </span>

                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="form-control"
                        placeholder="Masukkan password"
                        autocomplete="current-password"
                        required
                    >
                </div>
            </div>

            <button type="submit" name="login" class="btn btn-login">
                Login
                <i class="bi bi-arrow-right ms-1"></i>
            </button>

        </form>

        <div class="copyright">
            © 2026 FEBMart. All Rights Reserved.
        </div>

    </div>

</div>

</body>
</html>
