<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

$pesan_sukses = $_SESSION['flash_success'] ?? '';
$pesan_error = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash_success'], $_SESSION['flash_error']);




if (!empty($_SESSION['cart'])) {
    $stmt_refresh = $conn->prepare(
        "SELECT id, sku, name, price, stock FROM products WHERE id = ?"
    );

    foreach ($_SESSION['cart'] as $cart_id => &$cart_item) {
        $product_id = (int)$cart_id;
        $stmt_refresh->bind_param("i", $product_id);
        $stmt_refresh->execute();
        $fresh_product = $stmt_refresh->get_result()->fetch_assoc();

        if (!$fresh_product || (int)$fresh_product['stock'] <= 0) {
            unset($_SESSION['cart'][$cart_id]);
            $pesan_error = 'Sebagian produk di keranjang sudah tidak tersedia.';
            continue;
        }

        $cart_item['id'] = (int)$fresh_product['id'];
        $cart_item['sku'] = $fresh_product['sku'];
        $cart_item['name'] = $fresh_product['name'];
        $cart_item['price'] = (float)$fresh_product['price'];
        $cart_item['stock'] = (int)$fresh_product['stock'];
        $cart_item['qty'] = max(1, (int)$cart_item['qty']);

        if ($cart_item['qty'] > $cart_item['stock']) {
            $cart_item['qty'] = $cart_item['stock'];
            $pesan_error = 'Jumlah keranjang disesuaikan dengan stok terbaru.';
        }
    }
    unset($cart_item);
    $stmt_refresh->close();
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submitted_token = $_POST['csrf_token'] ?? '';

    if (!is_string($submitted_token) || !hash_equals($csrf_token, $submitted_token)) {
        $_SESSION['flash_error'] = 'Sesi formulir tidak valid. Silakan coba lagi.';
        header("Location: index.php");
        exit;
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'tambah') {
    $product_id = (int)($_POST['product_id'] ?? 0);

    if ($product_id > 0) {
        $stmt = $conn->prepare(
            "SELECT id, sku, name, price, stock FROM products WHERE id = ?"
        );
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $product = $stmt->get_result()->fetch_assoc();

        if ($product) {
            $current_qty = (int)($_SESSION['cart'][$product_id]['qty'] ?? 0);

            if ((int)$product['stock'] > $current_qty) {
                if (isset($_SESSION['cart'][$product_id])) {
                    $_SESSION['cart'][$product_id]['qty']++;
                } else {
                    $_SESSION['cart'][$product_id] = [
                        'id' => (int)$product['id'],
                        'sku' => $product['sku'],
                        'name' => $product['name'],
                        'price' => (float)$product['price'],
                        'stock' => (int)$product['stock'],
                        'qty' => 1
                    ];
                }
                $_SESSION['flash_success'] = 'Produk berhasil ditambahkan ke keranjang.';
            } else {
                $_SESSION['flash_error'] = 'Stok produk tidak mencukupi.';
            }
        } else {
            $_SESSION['flash_error'] = 'Produk tidak ditemukan.';
        }
    }

    header("Location: index.php");
    exit;
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update') {
    $product_id = (int)($_POST['product_id'] ?? 0);
    $qty = (int)($_POST['qty'] ?? 1);

    if ($product_id > 0 && isset($_SESSION['cart'][$product_id])) {
        $stmt = $conn->prepare("SELECT stock FROM products WHERE id = ?");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $product = $stmt->get_result()->fetch_assoc();
        $max_stock = $product ? (int)$product['stock'] : 0;

        if ($qty < 1) {
            $_SESSION['flash_error'] = 'Jumlah minimal 1.';
        } elseif ($qty > $max_stock) {
            $_SESSION['flash_error'] = "Jumlah melebihi stok yang tersedia (stok: $max_stock).";
        } else {
            $_SESSION['cart'][$product_id]['qty'] = $qty;
            $_SESSION['flash_success'] = 'Jumlah produk berhasil diperbarui.';
        }
    }

    header("Location: index.php");
    exit;
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'hapus') {
    $product_id = (int)($_POST['product_id'] ?? 0);

    if (isset($_SESSION['cart'][$product_id])) {
        unset($_SESSION['cart'][$product_id]);
        $_SESSION['flash_success'] = 'Produk berhasil dihapus dari keranjang.';
    }

    header("Location: index.php");
    exit;
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'kosongkan') {
    $_SESSION['cart'] = [];
    $_SESSION['flash_success'] = 'Keranjang berhasil dikosongkan.';
    header("Location: index.php");
    exit;
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'checkout') {
    if (empty($_SESSION['cart'])) {
        $_SESSION['flash_error'] = 'Keranjang belanja masih kosong.';
        header("Location: index.php");
        exit;
    }


    $user_id = (int)$_SESSION['user_id'];

    if ($user_id <= 0) {
        $_SESSION['flash_error'] = 'ID pengguna tidak valid. Silakan login kembali.';
        header("Location: ../auth/login.php");
        exit;
    }


    $stmt_user = $conn->prepare("SELECT id FROM users WHERE id = ? AND is_active = 1");
    $stmt_user->bind_param("i", $user_id);
    $stmt_user->execute();
    $logged_user = $stmt_user->get_result()->fetch_assoc();

    if (!$logged_user) {
        session_destroy();
        header("Location: ../auth/login.php");
        exit;
    }

    $paid_input = trim((string)($_POST['nominal_bayar'] ?? ''));

    if ($paid_input === '' || !ctype_digit($paid_input)) {
        $_SESSION['flash_error'] = 'Nominal pembayaran harus berupa angka bulat yang valid.';
        header("Location: index.php");
        exit;
    }

    $amount_paid = (float)$paid_input;
    $subtotal_transaksi = 0;

    foreach ($_SESSION['cart'] as $item) {
        $subtotal_transaksi += (float)$item['price'] * (int)$item['qty'];
    }

    $total = $subtotal_transaksi;

    if ($amount_paid < $total) {
        $kurang = $total - $amount_paid;
        $_SESSION['flash_error'] = 'Pembayaran kurang Rp ' . number_format($kurang, 0, ',', '.') . '.';
        header("Location: index.php");
        exit;
    }

    $change_amount = $amount_paid - $total;
    $payment_method = 'cash';
    $invoice_number = 'INV-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));

    $conn->begin_transaction();

    try {

        $stmt_check_stock = $conn->prepare(
            "SELECT id, name, stock FROM products WHERE id = ? FOR UPDATE"
        );
        $stmt_update_stock = $conn->prepare(
            "UPDATE products SET stock = stock - ? WHERE id = ?"
        );

        foreach ($_SESSION['cart'] as $item) {
            $product_id = (int)$item['id'];
            $qty = (int)$item['qty'];

            $stmt_check_stock->bind_param("i", $product_id);
            $stmt_check_stock->execute();
            $db_product = $stmt_check_stock->get_result()->fetch_assoc();

            if (!$db_product) {
                throw new Exception("Produk '{$item['name']}' tidak ditemukan.");
            }

            if ((int)$db_product['stock'] < $qty) {
                throw new Exception(
                    "Stok '{$db_product['name']}' tidak mencukupi. Stok tersedia: {$db_product['stock']}."
                );
            }

            $stmt_update_stock->bind_param("ii", $qty, $product_id);
            if (!$stmt_update_stock->execute()) {
                throw new Exception("Gagal memperbarui stok produk.");
            }
        }






        $stmt_trans = $conn->prepare("
            INSERT INTO transactions
            (invoice_number, user_id, transaction_date, subtotal, total,
             amount_paid, change_amount, payment_method)
            VALUES (?, ?, NOW(), ?, ?, ?, ?, ?)
        ");

        $stmt_trans->bind_param(
            "sidddds",
            $invoice_number,
            $user_id,
            $subtotal_transaksi,
            $total,
            $amount_paid,
            $change_amount,
            $payment_method
        );

        if (!$stmt_trans->execute()) {
            throw new Exception("Gagal menyimpan transaksi: " . $stmt_trans->error);
        }

        $transaction_id = $conn->insert_id;






        $stmt_detail = $conn->prepare("
            INSERT INTO transaction_details
            (transaction_id, product_id, product_name, quantity, unit_price, subtotal)
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        foreach ($_SESSION['cart'] as $item) {
            $product_id = (int)$item['id'];
            $product_name = (string)$item['name'];
            $quantity = (int)$item['qty'];
            $unit_price = (float)$item['price'];
            $detail_subtotal = $unit_price * $quantity;

            $stmt_detail->bind_param(
                "iisidd",
                $transaction_id,
                $product_id,
                $product_name,
                $quantity,
                $unit_price,
                $detail_subtotal
            );

            if (!$stmt_detail->execute()) {
                throw new Exception("Gagal menyimpan detail transaksi: " . $stmt_detail->error);
            }
        }

        $conn->commit();
        $_SESSION['cart'] = [];
        $_SESSION['flash_success'] =
            "Transaksi $invoice_number berhasil diproses. Stok berhasil dipotong. Kembalian: Rp " .
            number_format($change_amount, 0, ',', '.');
    } catch (Throwable $e) {
        $conn->rollback();
        error_log("Checkout FEBMart: " . $e->getMessage());
        $_SESSION['flash_error'] = "Transaksi dibatalkan: " . $e->getMessage();
    }

    header("Location: index.php");
    exit;
}


$search = trim($_GET['cari'] ?? '');

if ($search !== '') {
    $stmt_produk = $conn->prepare("
        SELECT products.*, categories.name AS nama_kategori
        FROM products
        LEFT JOIN categories ON products.category_id = categories.id
        WHERE products.name LIKE ? OR products.sku LIKE ?
        ORDER BY products.name ASC
    ");
    $keyword = "%$search%";
    $stmt_produk->bind_param("ss", $keyword, $keyword);
    $stmt_produk->execute();
    $produk = $stmt_produk->get_result();
} else {
    $produk = $conn->query("
        SELECT products.*, categories.name AS nama_kategori
        FROM products
        LEFT JOIN categories ON products.category_id = categories.id
        ORDER BY products.name ASC
    ");
}

$total_subtotal = 0;
$total_qty = 0;

foreach ($_SESSION['cart'] as $cart_item) {
    $total_subtotal += (float)$cart_item['price'] * (int)$cart_item['qty'];
    $total_qty += (int)$cart_item['qty'];
}

$total_bayar = $total_subtotal;
?>

<?php
$pageTitle = 'Transaksi Kasir';
require_once __DIR__ . '/../includes/header.php';
?>

<?php include __DIR__ . '/../includes/navbar.php'; ?>

<div class="app-layout d-flex flex-grow-1">

    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="main-content flex-grow-1 p-4">

        <div class="mb-4">
            <h3 class="fw-bold">
                <i class="bi bi-cash-register me-2"></i>Transaksi Kasir
            </h3>
            <p class="text-muted">
                Kelola transaksi penjualan FEBMart.
            </p>
        </div>

        <?php if ($pesan_sukses): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="bi bi-check-circle me-1"></i>
                <?= htmlspecialchars($pesan_sukses); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($pesan_error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="bi bi-exclamation-triangle me-1"></i>
                <?= htmlspecialchars($pesan_error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row g-4">

            <!-- KATALOG PRODUK -->
            <div class="col-lg-7">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-4">

                        <h5 class="fw-bold mb-1">Katalog Produk</h5>
                        <p class="text-muted small">
                            Pilih produk untuk ditambahkan ke transaksi.
                        </p>

                        <form method="GET" class="mb-3">
                            <div class="input-group">
                                <input type="text" name="cari"
                                    class="form-control"
                                    placeholder="Cari nama produk atau SKU..."
                                    value="<?= htmlspecialchars($search); ?>">

                                <button type="submit" class="btn btn-outline-primary">
                                    <i class="bi bi-search"></i>
                                </button>

                                <?php if ($search !== ''): ?>
                                    <a href="index.php" class="btn btn-outline-danger">
                                        <i class="bi bi-x-lg"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </form>

                        <div style="max-height:520px;overflow-y:auto;">

                            <?php if ($produk && $produk->num_rows > 0): ?>

                                <?php while ($item = $produk->fetch_assoc()): ?>

                                    <div class="d-flex align-items-center gap-3 border rounded p-3 mb-2">

                                        <?php if (!empty($item['image']) && file_exists(__DIR__ . '/../assets/img/' . $item['image'])): ?>

                                            <img src="../assets/img/<?= htmlspecialchars($item['image']); ?>"
                                                width="55" height="55"
                                                style="object-fit:cover;border-radius:8px;"
                                                alt="<?= htmlspecialchars($item['name']); ?>">

                                        <?php else: ?>

                                            <div class="bg-light rounded d-flex align-items-center justify-content-center"
                                                style="width:55px;height:55px;">
                                                <i class="bi bi-image fs-4 text-muted"></i>
                                            </div>

                                        <?php endif; ?>

                                        <div class="flex-grow-1 overflow-hidden">
                                            <div class="fw-bold text-truncate">
                                                <?= htmlspecialchars($item['name']); ?>
                                            </div>

                                            <small class="text-muted">
                                                <?= htmlspecialchars($item['sku']); ?>
                                                | Stok: <?= (int)$item['stock']; ?>
                                            </small>

                                            <div class="fw-bold text-primary">
                                                Rp <?= number_format((float)$item['price'], 0, ',', '.'); ?>
                                            </div>
                                        </div>

                                        <form method="POST" action="index.php" class="m-0">
                                            <input type="hidden" name="csrf_token"
                                                value="<?= htmlspecialchars($csrf_token); ?>">
                                            <input type="hidden" name="action" value="tambah">
                                            <input type="hidden" name="product_id"
                                                value="<?= (int)$item['id']; ?>">

                                            <button type="submit"
                                                class="btn btn-sm btn-primary"
                                                <?= (int)$item['stock'] <= 0 ? 'disabled' : ''; ?>>

                                                <i class="bi bi-cart-plus"></i>
                                                <?= (int)$item['stock'] <= 0 ? 'Habis' : 'Pilih'; ?>

                                            </button>
                                        </form>

                                    </div>

                                <?php endwhile; ?>

                            <?php else: ?>

                                <div class="text-center text-muted py-5">
                                    <i class="bi bi-box-seam fs-1 d-block mb-2"></i>
                                    Produk tidak ditemukan.
                                </div>

                            <?php endif; ?>

                        </div>
                    </div>
                </div>
            </div>

            <!-- KERANJANG -->
            <div class="col-lg-5">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">

                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="fw-bold mb-0">Keranjang Belanja</h5>

                            <span class="badge bg-primary">
                                <?= count($_SESSION['cart']); ?> Item
                            </span>
                        </div>

                        <?php if (!empty($_SESSION['cart'])): ?>

                            <form method="POST" class="mb-3"
                                onsubmit="return confirm('Kosongkan semua keranjang?');">

                                <input type="hidden" name="csrf_token"
                                    value="<?= htmlspecialchars($csrf_token); ?>">
                                <input type="hidden" name="action" value="kosongkan">

                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                    <i class="bi bi-trash"></i> Kosongkan Keranjang
                                </button>
                            </form>

                        <?php endif; ?>

                        <?php if (empty($_SESSION['cart'])): ?>

                            <div class="text-center text-muted py-5 border rounded mb-3">
                                <i class="bi bi-cart fs-1 d-block mb-2"></i>
                                Keranjang masih kosong.
                            </div>

                        <?php else: ?>

                            <div style="max-height:320px;overflow-y:auto;">

                                <?php foreach ($_SESSION['cart'] as $id => $cart_item): ?>

                                    <div class="border rounded p-3 mb-2">

                                        <div class="fw-bold">
                                            <?= htmlspecialchars($cart_item['name']); ?>
                                        </div>

                                        <small class="text-muted">
                                            <?= htmlspecialchars($cart_item['sku']); ?>
                                            <br>
                                            Rp <?= number_format((float)$cart_item['price'], 0, ',', '.'); ?>
                                            Ã— <?= (int)$cart_item['qty']; ?>
                                        </small>

                                        <div class="d-flex justify-content-between align-items-center mt-2">

                                            <strong class="text-primary">
                                                Rp <?= number_format(
                                                    (float)$cart_item['price'] * (int)$cart_item['qty'],
                                                    0, ',', '.'
                                                ); ?>
                                            </strong>

                                            <div class="d-flex gap-1">

                                                <form method="POST" class="d-flex gap-1">
                                                    <input type="hidden" name="csrf_token"
                                                        value="<?= htmlspecialchars($csrf_token); ?>">
                                                    <input type="hidden" name="action" value="update">
                                                    <input type="hidden" name="product_id"
                                                        value="<?= (int)$id; ?>">

                                                    <input type="number" name="qty"
                                                        value="<?= (int)$cart_item['qty']; ?>"
                                                        min="1"
                                                        max="<?= (int)$cart_item['stock']; ?>"
                                                        class="form-control form-control-sm"
                                                        style="width:65px;">

                                                    <button class="btn btn-sm btn-outline-primary"
                                                        type="submit">
                                                        <i class="bi bi-arrow-repeat"></i>
                                                    </button>
                                                </form>

                                                <form method="POST">
                                                    <input type="hidden" name="csrf_token"
                                                        value="<?= htmlspecialchars($csrf_token); ?>">
                                                    <input type="hidden" name="action" value="hapus">
                                                    <input type="hidden" name="product_id"
                                                        value="<?= (int)$id; ?>">

                                                    <button class="btn btn-sm btn-outline-danger"
                                                        type="submit">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>

                                            </div>
                                        </div>
                                    </div>

                                <?php endforeach; ?>

                            </div>

                        <?php endif; ?>

                        <!-- RINGKASAN -->
                        <div class="bg-light rounded p-3 my-3">

                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Total Item</span>
                                <strong>
                                    <?= count($_SESSION['cart']); ?>
                                    jenis (<?= $total_qty; ?> pcs)
                                </strong>
                            </div>

                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Subtotal</span>
                                <strong>
                                    Rp <?= number_format($total_subtotal, 0, ',', '.'); ?>
                                </strong>
                            </div>

                            <hr>

                            <div class="d-flex justify-content-between align-items-center">
                                <strong>Total Bayar</strong>
                                <h5 class="fw-bold text-primary mb-0">
                                    Rp <?= number_format($total_bayar, 0, ',', '.'); ?>
                                </h5>
                            </div>

                        </div>

                        <!-- PEMBAYARAN -->
                        <form method="POST" action="index.php" id="form-transaksi">

                            <input type="hidden" name="csrf_token"
                                value="<?= htmlspecialchars($csrf_token); ?>">
                            <input type="hidden" name="action" value="checkout">

                            <div class="mb-3">
                                <label class="form-label fw-bold">
                                    Nominal Pembayaran (Cash)
                                </label>

                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" name="nominal_bayar"
                                        id="nominal-bayar"
                                        class="form-control"
                                        placeholder="Masukkan nominal"
                                        min="<?= (int)$total_bayar; ?>"
                                        step="1" required
                                        <?= empty($_SESSION['cart']) ? 'disabled' : ''; ?>>
                                </div>

                                <?php if (!empty($_SESSION['cart'])): ?>

                                    <div class="d-flex flex-wrap gap-2 mt-2">
                                        <button type="button"
                                            class="btn btn-sm btn-outline-secondary quick-payment"
                                            data-amount="<?= (int)$total_bayar; ?>">
                                            Uang Pas
                                        </button>

                                        <button type="button"
                                            class="btn btn-sm btn-outline-secondary quick-payment"
                                            data-amount="<?= (int)($total_bayar + 10000); ?>">
                                            +10.000
                                        </button>

                                        <button type="button"
                                            class="btn btn-sm btn-outline-secondary quick-payment"
                                            data-amount="<?= (int)($total_bayar + 20000); ?>">
                                            +20.000
                                        </button>

                                        <button type="button"
                                            class="btn btn-sm btn-outline-secondary quick-payment"
                                            data-amount="<?= (int)($total_bayar + 50000); ?>">
                                            +50.000
                                        </button>
                                    </div>

                                <?php endif; ?>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">Kembalian</label>

                                <div class="bg-light rounded p-3 d-flex justify-content-between">
                                    <span class="text-muted">Uang Kembali</span>
                                    <strong id="display-kembalian" class="text-success">
                                        Rp 0
                                    </strong>
                                </div>
                            </div>

                            <button type="submit"
                                id="btn-submit-transaksi"
                                class="btn btn-primary w-100 py-2 fw-bold"
                                <?= empty($_SESSION['cart']) ? 'disabled' : ''; ?>>

                                <i class="bi bi-check-circle me-1"></i>
                                Selesaikan Transaksi

                            </button>

                        </form>

                    </div>
                </div>
            </div>

        </div>

    </main>

</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<script>
const inputBayar = document.getElementById('nominal-bayar');
const displayKembalian = document.getElementById('display-kembalian');
const totalBayar = <?= (int)$total_bayar; ?>;
const btnSubmit = document.getElementById('btn-submit-transaksi');

function hitungKembalian() {
    if (!inputBayar || !displayKembalian) return;

    const bayar = Number(inputBayar.value) || 0;
    const kembalian = bayar - totalBayar;

    if (totalBayar === 0) {
        displayKembalian.textContent = 'Rp 0';
        if (btnSubmit) btnSubmit.disabled = true;
        return;
    }

    if (kembalian >= 0) {
        displayKembalian.textContent =
            'Rp ' + Math.floor(kembalian).toLocaleString('id-ID');

        displayKembalian.className = 'fw-bold text-success';

        if (btnSubmit) btnSubmit.disabled = false;
    } else {
        displayKembalian.textContent =
            'Kurang Rp ' + Math.floor(Math.abs(kembalian)).toLocaleString('id-ID');

        displayKembalian.className = 'fw-bold text-danger';

        if (btnSubmit) btnSubmit.disabled = true;
    }
}

if (inputBayar) {
    inputBayar.addEventListener('input', hitungKembalian);
    hitungKembalian();
}

document.querySelectorAll('.quick-payment').forEach(button => {
    button.addEventListener('click', () => {
        inputBayar.value = button.dataset.amount;
        hitungKembalian();
    });
});
</script>

</body>
</html>
