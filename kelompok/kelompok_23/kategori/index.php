
<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once __DIR__ . '/../config/database.php';

$query = $conn->query("SELECT * FROM categories ORDER BY id DESC");

$pageTitle = 'Kategori';

require_once __DIR__ . '/../includes/header.php';
?>

<?php include __DIR__ . '/../includes/navbar.php'; ?>

<div class="app-layout d-flex flex-grow-1">

    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="main-content flex-grow-1 p-4">

        <div class="mb-4">
            <h3 class="fw-bold">
                <i class="bi bi-tags me-2"></i>Daftar Kategori
            </h3>
            <p class="text-muted">
                Kelola dan pantau seluruh kategori produk FEBMart.
            </p>
        </div>

        <?php if (isset($_GET['status'])): ?>

            <?php
            $status = $_GET['status'];

            $pesan = [
                'berhasil' => 'Kategori baru berhasil ditambahkan!',
                'edit_berhasil' => 'Kategori berhasil diperbarui!',
                'hapus_berhasil' => 'Kategori berhasil dihapus!',
                'gagal_hapus' => 'Kategori gagal dihapus!'
            ];
            ?>

            <?php if (isset($pesan[$status])): ?>
                <div class="alert <?= $status === 'gagal_hapus' ? 'alert-danger' : 'alert-success'; ?> alert-dismissible fade show">
                    <i class="bi bi-info-circle me-2"></i>
                    <?= htmlspecialchars($pesan[$status]); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

        <?php endif; ?>

        <div class="card border-0 shadow-sm">

            <div class="card-body p-4">

                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">

                    <div>
                        <h5 class="fw-bold mb-1">Data Kategori</h5>
                        <p class="text-muted small mb-0">
                            Informasi kategori produk FEBMart.
                        </p>
                    </div>

                    <div class="d-flex align-items-center gap-2">

                        <span class="badge bg-light text-primary border p-2">
                            <i class="bi bi-tags me-1"></i>
                            Total: <?= $query->num_rows; ?> Kategori
                        </span>

                        <a href="tambah.php" class="btn btn-primary">
                            <i class="bi bi-plus-lg me-1"></i>
                            Tambah Kategori
                        </a>

                    </div>
                </div>

                <div class="table-responsive">

                    <table class="table table-hover table-bordered align-middle">

                        <thead class="table-light">
                            <tr>
                                <th width="70">No</th>
                                <th>Nama Kategori</th>
                                <th>Deskripsi</th>
                                <th width="180">Aksi</th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php if ($query && $query->num_rows > 0): ?>

                                <?php $no = 1; ?>

                                <?php while ($data = $query->fetch_assoc()): ?>

                                    <tr>
                                        <td><?= $no++; ?></td>

                                        <td class="fw-semibold">
                                            <?= htmlspecialchars($data['name']); ?>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars($data['description'] ?? '-'); ?>
                                        </td>

                                        <td>
                                            <div class="d-flex gap-1">

                                                <a href="edit.php?id=<?= (int)$data['id']; ?>"
                                                    class="btn btn-warning btn-sm">
                                                    <i class="bi bi-pencil-square"></i> Edit
                                                </a>

                                                <a href="hapus.php?id=<?= (int)$data['id']; ?>"
                                                    class="btn btn-danger btn-sm"
                                                    data-confirm="Yakin ingin menghapus kategori ini?"
                                                >
                                                    <i class="bi bi-trash"></i> Hapus
                                                </a>

                                            </div>
                                        </td>
                                    </tr>

                                <?php endwhile; ?>

                            <?php else: ?>

                                <tr>
                                    <td colspan="4" class="text-center text-muted py-5">
                                        <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                        Belum ada data kategori.
                                    </td>
                                </tr>

                            <?php endif; ?>

                        </tbody>

                    </table>

                </div>

            </div>
        </div>

    </main>

</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
