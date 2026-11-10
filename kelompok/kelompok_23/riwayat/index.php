<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once __DIR__ . '/../config/database.php';

$per_page = 20;
$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT);
$page = $page !== false && $page !== null ? max(1, $page) : 1;

$count_result = $conn->query("SELECT COUNT(*) AS total FROM transactions");
if (!$count_result) {
    throw new RuntimeException("Gagal menghitung riwayat transaksi: " . $conn->error);
}
$total_transactions = (int)$count_result->fetch_assoc()['total'];
$revenue_result = $conn->query("SELECT COALESCE(SUM(total), 0) AS revenue FROM transactions");
if (!$revenue_result) {
    throw new RuntimeException("Gagal menghitung omzet riwayat transaksi: " . $conn->error);
}
$total_revenue = (float)$revenue_result->fetch_assoc()['revenue'];
$total_pages = max(1, (int)ceil($total_transactions / $per_page));
$page = min($page, $total_pages);
$offset = ($page - 1) * $per_page;

$stmt = $conn->prepare("
    SELECT t.id, t.invoice_number, t.transaction_date, t.subtotal, t.total,
           t.amount_paid, t.change_amount, t.payment_method,
           u.name AS cashier_name,
           (SELECT COUNT(*) FROM transaction_details td WHERE td.transaction_id = t.id) AS item_count
    FROM transactions t
    INNER JOIN users u ON u.id = t.user_id
    ORDER BY t.transaction_date DESC, t.id DESC
    LIMIT ? OFFSET ?
");
if (!$stmt) {
    throw new RuntimeException("Gagal menyiapkan riwayat transaksi: " . $conn->error);
}
$stmt->bind_param("ii", $per_page, $offset);
$stmt->execute();
$transactions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$details_by_transaction = [];
if ($transactions) {
    $transaction_ids = array_map(static fn(array $transaction): int => (int)$transaction['id'], $transactions);
    $id_list = implode(',', $transaction_ids);
    $details_result = $conn->query("
        SELECT transaction_id, product_name, quantity, unit_price, subtotal
        FROM transaction_details
        WHERE transaction_id IN ($id_list)
        ORDER BY transaction_id, id
    ");
    if (!$details_result) {
        throw new RuntimeException("Gagal mengambil detail transaksi: " . $conn->error);
    }
    while ($detail = $details_result->fetch_assoc()) {
        $details_by_transaction[(int)$detail['transaction_id']][] = $detail;
    }
}

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function rupiah(float|string $amount): string
{
    return 'Rp ' . number_format((float)$amount, 0, ',', '.');
}
?>
<?php
$pageTitle = 'Riwayat Transaksi';
require_once __DIR__ . '/../includes/header.php';
?>
<?php include __DIR__ . '/../includes/navbar.php'; ?>

<div class="app-layout d-flex flex-grow-1">

    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="main-content flex-grow-1 p-4">

        <div class="mb-4">
            <h3 class="fw-bold"><i class="bi bi-clock-history me-2"></i>Riwayat Transaksi</h3>
            <p class="text-muted">Telusuri transaksi penjualan dan lihat rincian barang yang dibeli.</p>
        </div>
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
            <div>
                <h4 class="mb-1">Daftar Penjualan</h4>
                <p class="text-muted mb-0">
                    Total <?= number_format($total_transactions); ?> transaksi tercatat
                    dengan omzet <?= rupiah($total_revenue); ?>.
                </p>
            </div>
            <a class="btn btn-primary" href="../laporan/index.php"><i class="bi bi-bar-chart-line me-1"></i>Laporan Penjualan</a>
        </div>
        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle">
                <thead><tr><th>No</th><th>No. Invoice</th><th>Tanggal</th><th>Kasir</th><th>Item</th><th>Pembayaran</th><th>Total</th><th>Aksi</th></tr></thead>
                <tbody>
                <?php if (!$transactions): ?>
                    <tr><td colspan="8" class="py-5 text-center text-muted"><i class="bi bi-inbox fs-3 d-block mb-2"></i>Belum ada transaksi.</td></tr>
                <?php else: ?>
                    <?php foreach ($transactions as $index => $transaction): ?>
                        <?php $transaction_id = (int)$transaction['id']; ?>
                        <tr>
                            <td><?= $offset + $index + 1; ?></td>
                            <td class="invoice-number"><?= h($transaction['invoice_number']); ?></td>
                            <td><?= h(date('d/m/Y H:i', strtotime($transaction['transaction_date']))); ?></td>
                            <td><?= h($transaction['cashier_name']); ?></td>
                            <td><?= (int)$transaction['item_count']; ?></td>
                            <td><span class="badge text-bg-light border"><?= h(strtoupper($transaction['payment_method'])); ?></span></td>
                            <td class="text-nowrap"><?= rupiah($transaction['total']); ?></td>
                            <td class="text-nowrap">
                                <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="modal" data-bs-target="#detail-<?= $transaction_id; ?>">
                                    <i class="bi bi-eye me-1"></i>Detail
                                </button>
                                <a class="btn btn-sm btn-outline-secondary" href="struk.php?id=<?= $transaction_id; ?>" target="_blank" rel="noopener">
                                    <i class="bi bi-receipt me-1"></i>Struk
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php foreach ($transactions as $transaction): ?>
            <?php $transaction_id = (int)$transaction['id']; ?>
            <div class="modal fade" id="detail-<?= $transaction_id; ?>" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <div><h5 class="modal-title mb-1">Detail Transaksi</h5><small class="text-muted"><?= h($transaction['invoice_number']); ?></small></div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row g-2 small mb-3">
                                <div class="col-sm-6"><span class="text-muted">Tanggal:</span> <?= h(date('d/m/Y H:i', strtotime($transaction['transaction_date']))); ?></div>
                                <div class="col-sm-6"><span class="text-muted">Kasir:</span> <?= h($transaction['cashier_name']); ?></div>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered mb-3">
                                    <thead><tr><th>Nama Produk</th><th>Harga</th><th>Qty</th><th>Subtotal</th></tr></thead>
                                    <tbody>
                                    <?php foreach ($details_by_transaction[$transaction_id] ?? [] as $detail): ?>
                                        <tr>
                                            <td><?= h($detail['product_name']); ?></td>
                                            <td><?= rupiah($detail['unit_price']); ?></td>
                                            <td><?= (int)$detail['quantity']; ?></td>
                                            <td><?= rupiah($detail['subtotal']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="ms-auto" style="max-width:260px">
                                <div class="d-flex justify-content-between"><span>Subtotal</span><span><?= rupiah($transaction['subtotal']); ?></span></div>
                                <div class="d-flex justify-content-between fw-bold"><span>Total</span><span><?= rupiah($transaction['total']); ?></span></div>
                                <div class="d-flex justify-content-between"><span>Dibayar</span><span><?= rupiah($transaction['amount_paid']); ?></span></div>
                                <div class="d-flex justify-content-between"><span>Kembalian</span><span><?= rupiah($transaction['change_amount']); ?></span></div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <a class="btn btn-primary" href="struk.php?id=<?= $transaction_id; ?>" target="_blank" rel="noopener"><i class="bi bi-receipt me-1"></i>Buka Struk</a>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        <?php if ($total_pages > 1): ?>
            <nav aria-label="Navigasi riwayat transaksi">
                <ul class="pagination justify-content-end">
                    <li class="page-item <?= $page <= 1 ? 'disabled' : ''; ?>"><a class="page-link" href="?page=<?= max(1, $page - 1); ?>">Sebelumnya</a></li>
                    <li class="page-item disabled"><span class="page-link">Halaman <?= $page; ?> dari <?= $total_pages; ?></span></li>
                    <li class="page-item <?= $page >= $total_pages ? 'disabled' : ''; ?>"><a class="page-link" href="?page=<?= min($total_pages, $page + 1); ?>">Berikutnya</a></li>
                </ul>
            </nav>
        <?php endif; ?>
    </main>

</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

