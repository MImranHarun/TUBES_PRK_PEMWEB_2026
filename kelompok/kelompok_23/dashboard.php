<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit;
}

$pageTitle = 'Dashboard';

require_once __DIR__ . '/config/database.php';

$totalProduk = null;
$errorTotalProduk = '';

try {
    $hasilProduk = $conn->query(
        "SELECT COUNT(*) AS total_produk FROM products"
    );

    if ($hasilProduk === false) {
        throw new RuntimeException($conn->error);
    }

    $dataProduk = $hasilProduk->fetch_assoc();
    $totalProduk = (int) $dataProduk['total_produk'];

    $hasilProduk->free();
} catch (Exception $e) {
    error_log('Query total produk gagal: ' . $e->getMessage());
    $errorTotalProduk = 'Jumlah produk belum dapat dimuat.';
}

$totalTransaksiHariIni = null;
$errorTransaksiHariIni = '';

try {
    $hasilTransaksi = $conn->query(
        "SELECT COUNT(*) AS total_transaksi
         FROM transactions
         WHERE transaction_date >= CURDATE()
           AND transaction_date < CURDATE() + INTERVAL 1 DAY"
    );

    if ($hasilTransaksi === false) {
        throw new RuntimeException($conn->error);
    }

    $dataTransaksi = $hasilTransaksi->fetch_assoc();
    $totalTransaksiHariIni = (int) $dataTransaksi['total_transaksi'];

    $hasilTransaksi->free();
} catch (Exception $e) {
    error_log('Query transaksi hari ini gagal: ' . $e->getMessage());
    $errorTransaksiHariIni = 'Jumlah transaksi belum dapat dimuat.';
}

$omzetHariIni = null;
$errorOmzetHariIni = '';

try {
    $hasilOmzet = $conn->query(
        "SELECT COALESCE(SUM(total), 0) AS total_omzet
         FROM transactions
         WHERE transaction_date >= CURDATE()
           AND transaction_date < CURDATE() + INTERVAL 1 DAY"
    );

    if ($hasilOmzet === false) {
        throw new RuntimeException($conn->error);
    }

    $dataOmzet = $hasilOmzet->fetch_assoc();
    $omzetHariIni = $dataOmzet['total_omzet'];

    $hasilOmzet->free();
} catch (Exception $e) {
    error_log('Query omzet hari ini gagal: ' . $e->getMessage());
    $errorOmzetHariIni = 'Omzet hari ini belum dapat dimuat.';
}

$produkStokMenipis = [];
$errorStokMenipis = '';

try {
    $hasilStok = $conn->query(
        "SELECT id, sku, name, stock, min_stock
         FROM products
         WHERE stock <= min_stock
         ORDER BY stock ASC, name ASC, id ASC"
    );

    if ($hasilStok === false) {
        throw new RuntimeException($conn->error);
    }

    $produkStokMenipis = $hasilStok->fetch_all(MYSQLI_ASSOC);

    $hasilStok->free();
} catch (Exception $e) {
    error_log('Query stok menipis gagal: ' . $e->getMessage());
    $errorStokMenipis = 'Data stok produk belum dapat dimuat.';
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/helper.php';
?>

<?php include __DIR__ . '/includes/navbar.php'; ?>

<div class="app-layout d-flex flex-grow-1">

    <?php include __DIR__ . '/includes/sidebar.php'; ?>

    <main class="main-content flex-grow-1 p-4">

        <div class="mb-4">
            <h3 class="fw-bold">Dashboard</h3>
            <p class="text-muted">
                Selamat datang di sistem manajemen FEBMart.
            </p>
        </div>

        <?php showFlash(); ?>

        <!-- POIN 11: ALERT NOTIFIKASI STOK MENIPIS -->
        <?php if ($errorStokMenipis === '' && !empty($produkStokMenipis)): ?>

            <?php
                $jumlahStokHabis = 0;

                foreach ($produkStokMenipis as $produkStok) {
                    if ((int) $produkStok['stock'] === 0) {
                        $jumlahStokHabis++;
                    }
                }

                $jumlahStokMenipis = count($produkStokMenipis) - $jumlahStokHabis;
            ?>

            <div class="alert alert-warning mb-4" role="alert">

                <strong>Perhatian stok barang.</strong>

                Terdapat <?= $jumlahStokMenipis; ?> jenis produk
                dengan stok menipis dan
                <?= $jumlahStokHabis; ?> jenis produk yang habis.

                <a href="#judul-stok" class="alert-link">
                    Lihat daftar produk.
                </a>

            </div>

        <?php endif; ?>
        <!-- AKHIR POIN 11 -->

        <section class="mb-4" aria-labelledby="judul-ringkasan">
            <h4 id="judul-ringkasan" class="fw-bold mb-3">
                Ringkasan Toko
            </h4>

            <div class="row g-3">

                <div class="col-12 col-md-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <h5 class="text-muted fs-6">Total Produk</h5>

                            <p class="fs-3 fw-bold mb-1">
                                <?= $totalProduk !== null
                                    ? number_format($totalProduk, 0, ',', '.')
                                    : '&mdash;'; ?>
                            </p>

                            <small class="text-muted">Jumlah jenis produk</small>

                            <?php if ($errorTotalProduk !== ''): ?>
                                <p class="text-danger small mt-2 mb-0" role="alert">
                                    <?= htmlspecialchars(
                                        $errorTotalProduk,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ); ?>
                                </p>
                            <?php endif; ?>

                        </div>
                    </div>
                </div>

                <div class="col-12 col-md-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <h5 class="text-muted fs-6">Transaksi Hari Ini</h5>

                            <p class="fs-3 fw-bold mb-1">
                                <?= $totalTransaksiHariIni !== null
                                    ? number_format($totalTransaksiHariIni, 0, ',', '.')
                                    : '&mdash;'; ?>
                            </p>

                            <small class="text-muted">Jumlah transaksi penjualan</small>

                            <?php if ($errorTransaksiHariIni !== ''): ?>
                                <p class="text-danger small mt-2 mb-0" role="alert">
                                    <?= htmlspecialchars(
                                        $errorTransaksiHariIni,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ); ?>
                                </p>
                            <?php endif; ?>

                        </div>
                    </div>
                </div>

                <div class="col-12 col-md-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <h5 class="text-muted fs-6">Omzet Hari Ini</h5>

                            <p class="fs-3 fw-bold mb-1">
                                <?= $omzetHariIni !== null
                                    ? formatRupiah($omzetHariIni)
                                    : '&mdash;'; ?>
                            </p>

                            <small class="text-muted">Total nilai penjualan</small>

                            <?php if ($errorOmzetHariIni !== ''): ?>
                                <p class="text-danger small mt-2 mb-0" role="alert">
                                    <?= htmlspecialchars(
                                        $errorOmzetHariIni,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ); ?>
                                </p>
                            <?php endif; ?>

                        </div>
                    </div>
                </div>

            </div>
        </section>

        <section class="mb-4" aria-labelledby="judul-stok">

            <div class="card border-0 shadow-sm">
                <div class="card-body">

                    <h4 id="judul-stok" class="fw-bold mb-2">
                        Monitoring Stok
                    </h4>

                    <p class="text-muted">
                        Produk dengan stok mencapai atau di bawah batas minimum.
                    </p>

                    <?php if ($errorStokMenipis !== ''): ?>

                        <p class="text-danger mb-0" role="alert">
                            <?= htmlspecialchars(
                                $errorStokMenipis,
                                ENT_QUOTES,
                                'UTF-8'
                            ); ?>
                        </p>

                    <?php elseif (empty($produkStokMenipis)): ?>

                        <p class="text-muted mb-0">
                            Tidak ada produk dengan stok mencapai
                            atau di bawah batas minimum.
                        </p>

                    <?php else: ?>

                        <div class="table-responsive">

                            <table class="table table-hover align-middle mb-0">

                                <thead>
                                    <tr>
                                        <th scope="col">No.</th>
                                        <th scope="col">SKU</th>
                                        <th scope="col">Nama Produk</th>
                                        <th scope="col" class="text-end">Stok</th>
                                        <th scope="col" class="text-end">
                                            Batas Minimum
                                        </th>
                                        <th scope="col">Status</th>
                                    </tr>
                                </thead>

                                <tbody>

                                    <?php foreach ($produkStokMenipis as $index => $produk): ?>

                                        <tr>
                                            <td><?= $index + 1; ?></td>

                                            <td>
                                                <?= htmlspecialchars(
                                                    $produk['sku'],
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ); ?>
                                            </td>

                                            <td>
                                                <?= htmlspecialchars(
                                                    $produk['name'],
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ); ?>
                                            </td>

                                            <td class="text-end">
                                                <?= (int) $produk['stock']; ?>
                                            </td>

                                            <td class="text-end">
                                                <?= (int) $produk['min_stock']; ?>
                                            </td>

                                            <td>

                                                <?php if ((int) $produk['stock'] === 0): ?>

                                                    <span class="badge bg-danger">
                                                        Habis
                                                    </span>

                                                <?php else: ?>

                                                    <span class="badge bg-warning text-dark">
                                                        Menipis
                                                    </span>

                                                <?php endif; ?>

                                            </td>
                                        </tr>

                                    <?php endforeach; ?>

                                </tbody>

                            </table>

                        </div>

                    <?php endif; ?>

                </div>
            </div>

        </section>

        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">

                <h4 class="fw-bold mb-3">
                    Selamat Datang,
                    <?= htmlspecialchars($_SESSION['name'] ?? 'Admin'); ?>!
                </h4>

                <p class="text-muted">
                    Anda berhasil login ke sistem manajemen FEBMart.
                </p>

                <hr>

                <div class="row">

                    <div class="col-md-6 mb-3">
                        <small class="text-muted">Username</small>
                        <p class="fw-semibold">
                            <?= htmlspecialchars($_SESSION['username'] ?? '-'); ?>
                        </p>
                    </div>

                    <div class="col-md-6 mb-3">
                        <small class="text-muted">Role</small>
                        <p class="fw-semibold">
                            <?= htmlspecialchars($_SESSION['role'] ?? '-'); ?>
                        </p>
                    </div>

                </div>

            </div>
        </div>

    </main>

</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
