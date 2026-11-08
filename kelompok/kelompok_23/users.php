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

require_once __DIR__ . '/config/database.php';

$query = "SELECT id, name, username, email, role, is_active, created_at
          FROM users
          ORDER BY id DESC";

$result = $conn->query($query);

if (!$result) {
    die("Gagal mengambil data pengguna: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Daftar Pengguna | FEBMart</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            margin: 0;
            background: #f3f4f6;
            font-family: Arial, sans-serif;
            padding: 35px 20px;
        }

        .main-container {
            width: 100%;
            max-width: 1150px;
            margin: 0 auto;
            background: white;
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 5px 25px rgba(0, 0, 0, 0.08);
        }

        .header-section {
            background: linear-gradient(145deg, #29245c, #5142a5);
            color: white;
            padding: 30px 35px;
        }

        .brand {
            font-size: 23px;
            font-weight: bold;
            margin-bottom: 25px;
        }

        .brand i {
            color: #ffad42;
            margin-right: 7px;
        }

        .header-section h2 {
            font-size: 25px;
            font-weight: bold;
            margin-bottom: 8px;
        }

        .header-section p {
            color: #d5d9e2;
            font-size: 14px;
            margin-bottom: 0;
        }

        .content-section {
            padding: 35px;
        }

        .content-section h4 {
            color: #29245c;
            font-size: 20px;
            font-weight: bold;
        }

        .content-description {
            color: #888;
            font-size: 13px;
        }

        .total-user {
            background: #f5f4fb;
            color: #5142a5;
            padding: 10px 15px;
            border-radius: 7px;
            font-size: 13px;
            font-weight: bold;
        }

        .table-responsive {
            border-radius: 8px;
        }

        .table {
            font-size: 13px;
            margin-bottom: 0;
        }

        .table thead th {
            background: #29245c;
            color: white;
            padding: 14px 12px;
            font-weight: 600;
            white-space: nowrap;
        }

        .table tbody td {
            padding: 13px 12px;
            vertical-align: middle;
        }

        .table tbody tr:hover {
            background: #f5f4fb;
        }

        .badge-role {
            background: #eeeaff;
            color: #5142a5;
            padding: 7px 11px;
            border-radius: 5px;
            font-weight: 600;
        }

        .btn-save {
            background: #ffad42;
            color: #29245c;
            border: none;
            border-radius: 7px;
            padding: 10px 15px;
            font-size: 13px;
            font-weight: bold;
            transition: 0.2s;
            text-decoration: none;
        }

        .btn-save:hover {
            background: #f49a25;
            color: #29245c;
        }

        .btn-back {
            background: #f5f4fb;
            color: #5142a5;
            border: 1px solid #ddd;
            border-radius: 7px;
            padding: 11px 18px;
            font-size: 14px;
            font-weight: bold;
            text-decoration: none;
            transition: 0.2s;
        }

        .btn-back:hover {
            background: #e9e6f7;
            color: #29245c;
        }

        .copyright {
            color: #999;
            font-size: 12px;
            text-align: center;
            padding: 20px;
            border-top: 1px solid #eee;
        }

        @media (max-width: 768px) {
            body {
                padding: 15px;
            }

            .header-section,
            .content-section {
                padding: 25px 20px;
            }

            .header-section h2 {
                font-size: 21px;
            }
        }
    </style>
</head>

<body>

<div class="main-container">

    <div class="header-section">

        <div class="brand">
            <i class="bi bi-cart3"></i>FEBMart
        </div>

        <h2>
            <i class="bi bi-people-fill me-2"></i>Daftar Pengguna
        </h2>

        <p>
            Kelola dan pantau seluruh pengguna yang terdaftar
            dalam sistem manajemen FEBMart.
        </p>

    </div>

    <div class="content-section">

        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">

            <div>
                <h4>Data Pengguna</h4>

                <p class="content-description mb-0">
                    Informasi akun pengguna FEBMart.
                </p>
            </div>

            <div class="d-flex align-items-center flex-wrap gap-2">

                <div class="total-user">
                    <i class="bi bi-person-check me-1"></i>
                    Total: <?= $result->num_rows; ?> Pengguna
                </div>

                <a href="tambah_user.php" class="btn-save">
                    <i class="bi bi-person-plus me-1"></i>
                    Tambah Pengguna
                </a>

            </div>

        </div>

        <?php if (isset($_GET['status']) && $_GET['status'] === 'berhasil'): ?>
            <div class="alert alert-success" role="alert">
                <i class="bi bi-check-circle me-1"></i>
                Pengguna baru berhasil ditambahkan!
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['status']) && $_GET['status'] === 'edit_berhasil'): ?>
            <div class="alert alert-success" role="alert">
                <i class="bi bi-check-circle me-1"></i>
                Data pengguna berhasil diperbarui!
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['status']) && $_GET['status'] === 'hapus_berhasil'): ?>
            <div class="alert alert-success" role="alert">
                <i class="bi bi-check-circle me-1"></i>
                Pengguna berhasil dihapus!
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['status']) && $_GET['status'] === 'gagal_hapus'): ?>
            <div class="alert alert-danger" role="alert">
                <i class="bi bi-exclamation-circle me-1"></i>
                Pengguna gagal dihapus atau Anda tidak dapat menghapus akun sendiri!
            </div>
        <?php endif; ?>

        <div class="table-responsive">

            <table class="table table-bordered table-hover align-middle">

                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Tanggal Dibuat</th>
                        <th>Aksi</th>
                    </tr>
                </thead>

                <tbody>

                    <?php if ($result->num_rows > 0): ?>

                        <?php $no = 1; ?>

                        <?php while ($user = $result->fetch_assoc()): ?>

                            <tr>

                                <td><?= $no++; ?></td>

                                <td>
                                    <?= htmlspecialchars($user['name']); ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($user['username']); ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($user['email'] ?? '-'); ?>
                                </td>

                                <td>
                                    <span class="badge-role">
                                        <?= htmlspecialchars($user['role']); ?>
                                    </span>
                                </td>

                                <td>
                                    <?php if ($user['is_active']): ?>
                                        <span class="badge bg-success">Aktif</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Nonaktif</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($user['created_at']); ?>
                                </td>

                                <td>
                                    <div class="d-flex gap-1">

                                        <a href="edit_user.php?id=<?= $user['id']; ?>"
                                           class="btn btn-warning btn-sm">
                                            <i class="bi bi-pencil-square"></i> Edit
                                        </a>

                                        <form action="proses_hapus_user.php"
                                              method="POST"
                                              class="d-inline"
                                              onsubmit="return confirm('Yakin ingin menghapus pengguna ini?');">

                                            <input type="hidden" name="id"
                                                   value="<?= $user['id']; ?>">

                                            <button type="submit"
                                                    class="btn btn-danger btn-sm">
                                                <i class="bi bi-trash"></i> Hapus
                                            </button>

                                        </form>

                                    </div>
                                </td>

                            </tr>

                        <?php endwhile; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">
                                <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                Belum ada data pengguna.
                            </td>
                        </tr>

                    <?php endif; ?>

                </tbody>

            </table>

        </div>

        <a href="dashboard.php" class="btn-back d-inline-block mt-4">
            <i class="bi bi-arrow-left me-1"></i>
            Kembali ke Dashboard
        </a>

    </div>

    <div class="copyright">
        © 2026 FEBMart. All Rights Reserved.
    </div>

</div>

</body>
</html>

<?php
$result->free();
$conn->close();
?>
