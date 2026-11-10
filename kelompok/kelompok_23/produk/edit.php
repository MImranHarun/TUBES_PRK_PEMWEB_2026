
<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once __DIR__ . '/../config/database.php';

$error = '';

// Validasi ID produk
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || $id <= 0) {
    header("Location: index.php");
    exit;
}

// Ambil data produk
$stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();

if (!$data) {
    header("Location: index.php");
    exit;
}

// Ambil data kategori
$kategori = $conn->query(
    "SELECT * FROM categories ORDER BY name ASC"
);

// Proses update produk
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {

    $category_id = (int)($_POST['category_id'] ?? 0);
    $sku         = trim($_POST['sku'] ?? '');
    $name        = trim($_POST['name'] ?? '');
    $price       = $_POST['price'] ?? '';
    $stock       = $_POST['stock'] ?? '';
    $min_stock   = $_POST['min_stock'] ?? '';

    $imageName = $data['image'];

    if (
        $category_id <= 0 ||
        $sku === '' ||
        $name === '' ||
        !is_numeric($price) ||
        !is_numeric($stock) ||
        !is_numeric($min_stock) ||
        (float)$price < 0 ||
        (int)$stock < 0 ||
        (int)$min_stock < 0
    ) {
        $error = "Pastikan semua data wajib diisi dengan benar!";
    } else {

        // Periksa apakah kategori tersedia
        $stmt_kategori = $conn->prepare(
            "SELECT id FROM categories WHERE id = ?"
        );
        $stmt_kategori->bind_param("i", $category_id);
        $stmt_kategori->execute();

        if (!$stmt_kategori->get_result()->fetch_assoc()) {
            $error = "Kategori yang dipilih tidak ditemukan.";
        }

        // Proses upload gambar jika ada
        $uploadedImage = null;

        if (
            $error === '' &&
            isset($_FILES['image']) &&
            $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE
        ) {
            if ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {
                $error = "Gagal mengunggah gambar.";
            } else {

                $allowedTypes = [
                    'image/jpeg' => 'jpg',
                    'image/png'  => 'png',
                    'image/webp' => 'webp'
                ];

                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime = $finfo->file($_FILES['image']['tmp_name']);

                if (!isset($allowedTypes[$mime])) {
                    $error = "Format gambar harus JPG, PNG, atau WEBP.";
                } elseif ($_FILES['image']['size'] > 2 * 1024 * 1024) {
                    $error = "Ukuran gambar maksimal 2 MB.";
                } else {

                    $extension = $allowedTypes[$mime];

                    $uploadedImage = bin2hex(random_bytes(16))
                        . '.' . $extension;

                    $uploadPath = __DIR__ . '/../assets/img/'
                        . $uploadedImage;

                    if (!move_uploaded_file(
                        $_FILES['image']['tmp_name'],
                        $uploadPath
                    )) {
                        $error = "Gagal menyimpan gambar.";
                        $uploadedImage = null;
                    }
                }
            }
        }

        // Simpan perubahan ke database
        if ($error === '') {

            if ($uploadedImage !== null) {
                $imageName = $uploadedImage;
            }

            $price = (float)$price;
            $stock = (int)$stock;
            $min_stock = (int)$min_stock;

            $stmt_update = $conn->prepare("
                UPDATE products
                SET category_id = ?,
                    sku = ?,
                    name = ?,
                    price = ?,
                    stock = ?,
                    min_stock = ?,
                    image = ?
                WHERE id = ?
            ");

            $stmt_update->bind_param(
                "issdiisi",
                $category_id,
                $sku,
                $name,
                $price,
                $stock,
                $min_stock,
                $imageName,
                $id
            );

            if ($stmt_update->execute()) {

                // Hapus gambar lama setelah gambar baru berhasil disimpan
                if (
                    $uploadedImage !== null &&
                    !empty($data['image'])
                ) {
                    $oldImage = __DIR__ . '/../assets/img/'
                        . basename($data['image']);

                    if (is_file($oldImage)) {
                        unlink($oldImage);
                    }
                }

                header("Location: index.php?status=edit_berhasil");
                exit;

            } else {
                if ($uploadedImage !== null) {
                    $newImagePath = __DIR__ . '/../assets/img/'
                        . $uploadedImage;

                    if (is_file($newImagePath)) {
                        unlink($newImagePath);
                    }
                }

                $error = "Gagal memperbarui produk.";
            }
        }
    }
}

$pageTitle = 'Edit Produk';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/helper.php';
?>

<?php include __DIR__ . '/../includes/navbar.php'; ?>

<div class="app-layout d-flex flex-grow-1">

    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="main-content flex-grow-1 p-4">

        <div class="mb-4">
            <h3 class="fw-bold">
                <i class="bi bi-pencil-square me-2"></i>
                Edit Produk
            </h3>
            <p class="text-muted">
                Perbarui informasi produk FEBMart.
            </p>
        </div>

        <?php showFlash(); ?>

        <?php if ($error !== ''): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="bi bi-exclamation-circle me-2"></i>
                <?= htmlspecialchars($error); ?>

                <button type="button"
                        class="btn-close"
                        data-bs-dismiss="alert">
                </button>
            </div>
        <?php endif; ?>

        <div class="card border-0 shadow-sm">

            <div class="card-header bg-white py-3">
                <h5 class="fw-bold mb-0">
                    Informasi Produk
                </h5>
            </div>

            <div class="card-body p-4">

                <form method="POST" enctype="multipart/form-data">

                    <div class="row">

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">
                                Kategori <span class="text-danger">*</span>
                            </label>

                            <select name="category_id"
                                    class="form-select"
                                    required>

                                <option value="">Pilih Kategori</option>

                                <?php while ($kat = $kategori->fetch_assoc()): ?>

                                    <option
                                        value="<?= (int)$kat['id']; ?>"
                                        <?= (
                                            $kat['id'] == ($_POST['category_id'] ?? $data['category_id'])
                                        ) ? 'selected' : ''; ?>>

                                        <?= htmlspecialchars($kat['name']); ?>

                                    </option>

                                <?php endwhile; ?>

                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">
                                SKU <span class="text-danger">*</span>
                            </label>

                            <input type="text"
                                   name="sku"
                                   class="form-control"
                                   value="<?= htmlspecialchars($_POST['sku'] ?? $data['sku']); ?>"
                                   required>
                        </div>

                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            Nama Produk <span class="text-danger">*</span>
                        </label>

                        <input type="text"
                               name="name"
                               class="form-control"
                               value="<?= htmlspecialchars($_POST['name'] ?? $data['name']); ?>"
                               required>
                    </div>

                    <div class="row">

                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-semibold">
                                Harga <span class="text-danger">*</span>
                            </label>

                            <input type="number"
                                   name="price"
                                   class="form-control"
                                   min="0"
                                   step="1"
                                   value="<?= htmlspecialchars($_POST['price'] ?? $data['price']); ?>"
                                   required>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-semibold">
                                Stok <span class="text-danger">*</span>
                            </label>

                            <input type="number"
                                   name="stock"
                                   class="form-control"
                                   min="0"
                                   value="<?= htmlspecialchars($_POST['stock'] ?? $data['stock']); ?>"
                                   required>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-semibold">
                                Minimal Stok
                            </label>

                            <input type="number"
                                   name="min_stock"
                                   class="form-control"
                                   min="0"
                                   value="<?= htmlspecialchars($_POST['min_stock'] ?? $data['min_stock']); ?>"
                                   required>
                        </div>

                    </div>

                    <div class="mb-4">

                        <label class="form-label fw-semibold">
                            Gambar Saat Ini
                        </label>

                        <div class="mb-3">

                            <?php
                            $currentImage = $data['image'] ?? '';
                            $imagePath = __DIR__ . '/../assets/img/'
                                . basename($currentImage);
                            ?>

                            <?php if (
                                $currentImage !== '' &&
                                is_file($imagePath)
                            ): ?>

                                <img
                                    src="../assets/img/<?= rawurlencode(basename($currentImage)); ?>"
                                    alt="Gambar produk"
                                    class="img-thumbnail"
                                    style="width:120px;height:120px;object-fit:cover;">

                            <?php else: ?>

                                <p class="text-muted mb-0">
                                    Belum ada gambar produk.
                                </p>

                            <?php endif; ?>

                        </div>

                        <label class="form-label fw-semibold">
                            Ganti Gambar (Opsional)
                        </label>

                        <input type="file"
                            name="image"
                            class="form-control"
                            accept=".jpg,.jpeg,.png,.webp"
                            data-image-preview="previewGambar">
                        <img
                            id="previewGambar"
                            src=""
                            alt="Preview gambar baru"
                            class="product-image-preview d-none mt-3">

                        <small class="text-muted">
                            Format JPG, PNG, atau WEBP. Maksimal 2 MB.
                            Kosongkan jika tidak ingin mengganti gambar.
                        </small>

                    </div>

                    <hr>

                    <div class="d-flex justify-content-between flex-wrap gap-2">

                        <a href="index.php"
                           class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i>
                            Kembali
                        </a>

                        <button type="submit"
                                name="update"
                                class="btn btn-primary">
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
