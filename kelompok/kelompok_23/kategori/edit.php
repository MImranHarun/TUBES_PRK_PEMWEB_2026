
<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once __DIR__ . '/../config/database.php';

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    header("Location: index.php");
    exit;
}

$stmt = $conn->prepare("SELECT * FROM categories WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();

$data = $stmt->get_result()->fetch_assoc();

if (!$data) {
    header("Location: index.php");
    exit;
}

$error = '';

if (isset($_POST['update'])) {

    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if (empty($name)) {
        $error = "Nama kategori tidak boleh kosong!";
    } else {

        $stmt = $conn->prepare(
            "UPDATE categories SET name=?, description=? WHERE id=?"
        );

        $stmt->bind_param("ssi", $name, $description, $id);

        if ($stmt->execute()) {
            header("Location: index.php?status=edit_berhasil");
            exit;
        } else {
            $error = "Gagal mengupdate kategori: " . $conn->error;
        }
    }

    $data['name'] = $name;
    $data['description'] = $description;
}

$pageTitle = 'Edit Kategori';

require_once __DIR__ . '/../includes/header.php';
?>

<?php include __DIR__ . '/../includes/navbar.php'; ?>

<div class="app-layout d-flex flex-grow-1">

    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="main-content flex-grow-1 p-4">

        <div class="mb-4">
            <h3 class="fw-bold">
                <i class="bi bi-pencil-square me-2"></i>Edit Kategori
            </h3>

            <p class="text-muted">
                Perbarui informasi kategori produk FEBMart.
            </p>
        </div>

        <div class="card border-0 shadow-sm">

            <div class="card-body p-4">

                <h5 class="fw-bold mb-1">Form Edit Kategori</h5>

                <p class="text-muted small mb-4">
                    Ubah informasi kategori sesuai kebutuhan.
                </p>

                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-circle me-2"></i>
                        <?= htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <form method="POST">

                    <div class="mb-3">

                        <label class="form-label fw-semibold">
                            Nama Kategori <span class="text-danger">*</span>
                        </label>

                        <input type="text"
                            name="name"
                            class="form-control"
                            value="<?= htmlspecialchars($data['name']); ?>"
                            required>

                    </div>

                    <div class="mb-4">

                        <label class="form-label fw-semibold">
                            Deskripsi (Opsional)
                        </label>

                        <textarea name="description"
                            class="form-control"
                            rows="4"><?= htmlspecialchars($data['description'] ?? ''); ?></textarea>

                    </div>

                    <hr>

                    <div class="d-flex justify-content-between flex-wrap gap-2">

                        <a href="index.php" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i>
                            Kembali
                        </a>

                        <button type="submit" name="update" class="btn btn-primary">
                            <i class="bi bi-save me-1"></i>
                            Simpan Perubahan
                        </button>

                    </div>

                </form>

            </div>
        </div>

    </main>

</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
