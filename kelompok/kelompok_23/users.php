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

$query = "SELECT id, name, username, email, role, photo, is_active, created_at
          FROM users
          ORDER BY id DESC";

$result = $conn->query($query);

if (!$result) {
    die("Gagal mengambil data pengguna: " . $conn->error);
}
?>

<?php
$pageTitle = 'Kelola User';
require_once __DIR__ . '/includes/header.php';
?>
<?php include __DIR__ . '/includes/navbar.php'; ?>

<div class="app-layout d-flex flex-grow-1">

    <?php include __DIR__ . '/includes/sidebar.php'; ?>

    <main class="main-content flex-grow-1 p-4">

        <div class="mb-4">
            <h3 class="fw-bold"><i class="bi bi-people me-2"></i>Kelola User</h3>
            <p class="text-muted">Kelola dan pantau seluruh pengguna yang terdaftar dalam sistem manajemen FEBMart.</p>
        </div>

        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">

            <div>
                <h4>Data Pengguna</h4>

                <p class="content-description mb-0">
                    Informasi akun pengguna FEBMart.
                </p>
            </div>

            <div class="d-flex align-items-center flex-wrap gap-2">

                <span class="badge text-bg-light border py-2 px-3">
                    <i class="bi bi-person-check me-1"></i>
                    Total: <?= $result->num_rows; ?> Pengguna
                </span>

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
                                    <?php if (!empty($user['photo'])): ?>
                                        <img src="assets/img/<?= htmlspecialchars($user['photo']); ?>"
                                             width="30" height="30"
                                             class="me-2"
                                             alt="Foto pengguna"
                                             style="border-radius:50%;object-fit:cover;">
                                    <?php endif; ?>
                                    <?= htmlspecialchars($user['name']); ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($user['username']); ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($user['email'] ?? '-'); ?>
                                </td>

                                <td>
                                    <span class="badge text-bg-light border text-primary">
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

    </main>

</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

<?php
$result->free();
$conn->close();
?>
