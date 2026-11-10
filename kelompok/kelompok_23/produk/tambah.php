
<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helper.php';

$kategori = $conn->query("SELECT * FROM categories ORDER BY name ASC");
$error = '';

if (isset($_POST['simpan'])) {
    $category_id = $_POST['category_id'];
    $sku = trim($_POST['sku']);
    $name = trim($_POST['name']);
    $price = $_POST['price'];
    $stock = $_POST['stock'];
    $min_stock = $_POST['min_stock'];

    $image = $_FILES['image']['name'];
    $tmp = $_FILES['image']['tmp_name'];

    if (empty($category_id) || empty($sku) || empty($name) || empty($price)) {
        $error = "Semua kolom bertanda (*) wajib diisi!";
    } else {
        if ($image != "") {
            move_uploaded_file($tmp, "../assets/img/" . $image);
        }

        $stmt = $conn->prepare(
            "INSERT INTO products
            (category_id, sku, name, price, stock, min_stock, image)
            VALUES (?, ?, ?, ?, ?, ?, ?)"
        );

        $stmt->bind_param(
            "issdiis",
            $category_id,
            $sku,
            $name,
            $price,
            $stock,
            $min_stock,
            $image
        );

        if ($stmt->execute()) {
            header("Location: index.php?status=berhasil");
            exit;
        } else {
            $error = "Gagal menyimpan produk: " . $conn->error;
        }
    }
}

$pageTitle = 'Tambah Produk';

require_once __DIR__ . '/../includes/header.php';
?>

<?php require_once __DIR__ . '/../includes/navbar.php'; ?>

<div class="app-layout">

    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="main-content">

        <div class="mb-4">
            <h3 class="fw-bold" style="color: #29245C;">
                <i class="bi bi-plus-circle me-2"></i>
                Tambah Produk
            </h3>

            <p class="text-muted">
                Tambahkan produk baru ke dalam sistem FEBMart.
            </p>
        </div>

        <div class="card shadow-sm">

            <div class="card-header p-3 border-bottom">
                <h5 class="mb-0 fw-bold" style="color: #29245C;">
                    <i class="bi bi-box-seam me-2"></i>
                    Informasi Produk
                </h5>
            </div>

            <div class="card-body p-4">

                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-circle me-2"></i>
                        <?= htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data">

                    <div class="row">

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">
                                Kategori <span class="text-danger">*</span>
                            </label>

                            <select name="category_id"
                                    class="form-select" required>
                                <option value="">-- Pilih Kategori --</option>

                                <?php while ($kat = $kategori->fetch_assoc()): ?>
                                    <option value="<?= $kat['id']; ?>"
                                        <?= (($_POST['category_id'] ?? '') == $kat['id']) ? 'selected' : ''; ?>>
                                        <?= htmlspecialchars($kat['name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">
                                SKU <span class="text-danger">*</span>
                            </label>

                            <input type="text" name="sku"
                                   class="form-control"
                                   placeholder="Contoh: PRD-001"
                                   value="<?= htmlspecialchars($_POST['sku'] ?? ''); ?>"
                                   required>
                        </div>

                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            Nama Produk <span class="text-danger">*</span>
                        </label>

                        <input type="text" name="name"
                               class="form-control"
                               placeholder="Masukkan nama produk"
                               value="<?= htmlspecialchars($_POST['name'] ?? ''); ?>"
                               required>
                    </div>

                    <div class="row">

                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-semibold">
                                Harga <span class="text-danger">*</span>
                            </label>

                            <input type="number" name="price"
                                   class="form-control"
                                   min="0"
                                   placeholder="Contoh: 15000"
                                   value="<?= htmlspecialchars($_POST['price'] ?? ''); ?>"
                                   required>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-semibold">
                                Stok <span class="text-danger">*</span>
                            </label>

                            <input type="number" name="stock"
                                   class="form-control"
                                   min="0"
                                   value="<?= htmlspecialchars($_POST['stock'] ?? ''); ?>"
                                   required>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-semibold">
                                Minimal Stok
                            </label>

                            <input type="number" name="min_stock"
                                   class="form-control"
                                   min="0"
                                   value="<?= htmlspecialchars($_POST['min_stock'] ?? '5'); ?>"
                                   required>
                        </div>

                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">
                            Gambar Produk
                        </label>

                        <input type="file"
                               name="image"
                               class="form-control"
                               accept="image/jpeg,image/png,image/webp"
                               data-image-preview="previewGambar">
                        <img
                               id="previewGambar"
                               src=""
                               alt="Preview gambar produk"
                               class="product-image-preview d-none mt-3">
                        <small class="text-muted">
                            Biarkan kosong jika tidak ingin mengunggah gambar.
                        </small>
                    </div>

                    <div class="d-flex justify-content-between
                                align-items-center flex-wrap gap-2
                                pt-3 border-top">

                        <a href="index.php" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i>
                            Kembali
                        </a>

                        <button type="submit" name="simpan"
                                class="btn btn-primary">
                            <i class="bi bi-save me-1"></i>
                            Simpan Produk
                        </button>

                    </div>

                </form>

            </div>
        </div>

    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
