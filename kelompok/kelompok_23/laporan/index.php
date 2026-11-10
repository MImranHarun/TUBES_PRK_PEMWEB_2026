<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once __DIR__ . '/../config/database.php';

function parse_date(string $value): ?DateTimeImmutable
{
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    $errors = DateTimeImmutable::getLastErrors();
    if (!$date || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0)) || $date->format('Y-m-d') !== $value) {
        return null;
    }
    return $date;
}

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function rupiah(float|string $amount): string
{
    return 'Rp ' . number_format((float)$amount, 0, ',', '.');
}

$today = new DateTimeImmutable('today');
$start_input = is_string($_GET['tanggal_mulai'] ?? null) ? trim($_GET['tanggal_mulai']) : $today->modify('first day of this month')->format('Y-m-d');
$end_input = is_string($_GET['tanggal_akhir'] ?? null) ? trim($_GET['tanggal_akhir']) : $today->format('Y-m-d');
$start_date = parse_date($start_input);
$end_date = parse_date($end_input);
$error = '';

if (!$start_date || !$end_date) {
    $error = 'Format tanggal tidak valid. Gunakan tanggal mulai dan akhir yang benar.';
} elseif ($start_date > $end_date) {
    $error = 'Tanggal mulai tidak boleh melewati tanggal akhir.';
}

$transactions = [];
$summary = ['transaction_count' => 0, 'revenue' => 0, 'items_sold' => 0];
if ($error === '') {
    $start_bound = $start_date->format('Y-m-d 00:00:00');
    $end_bound = $end_date->modify('+1 day')->format('Y-m-d 00:00:00');

    $summary_stmt = $conn->prepare("
        SELECT COUNT(*) AS transaction_count, COALESCE(SUM(total), 0) AS revenue
        FROM transactions
        WHERE transaction_date >= ? AND transaction_date < ?
    ");
    if (!$summary_stmt) {
        throw new RuntimeException("Gagal menyiapkan ringkasan laporan: " . $conn->error);
    }
    $summary_stmt->bind_param("ss", $start_bound, $end_bound);
    $summary_stmt->execute();
    $summary = array_merge($summary, $summary_stmt->get_result()->fetch_assoc());
    $summary_stmt->close();
    $summary['average_transaction'] = (int)$summary['transaction_count'] > 0
        ? (float)$summary['revenue'] / (int)$summary['transaction_count']
        : 0;

    $items_stmt = $conn->prepare("
        SELECT COALESCE(SUM(td.quantity), 0) AS items_sold
        FROM transaction_details td
        INNER JOIN transactions t ON t.id = td.transaction_id
        WHERE t.transaction_date >= ? AND t.transaction_date < ?
    ");
    if (!$items_stmt) {
        throw new RuntimeException("Gagal menyiapkan jumlah barang laporan: " . $conn->error);
    }
    $items_stmt->bind_param("ss", $start_bound, $end_bound);
    $items_stmt->execute();
    $summary['items_sold'] = (int)$items_stmt->get_result()->fetch_assoc()['items_sold'];
    $items_stmt->close();

    $transactions_stmt = $conn->prepare("
        SELECT t.id, t.invoice_number, t.transaction_date, t.total, t.payment_method,
               u.name AS cashier_name,
               (SELECT COALESCE(SUM(td.quantity), 0)
                FROM transaction_details td
                WHERE td.transaction_id = t.id) AS items_sold
        FROM transactions t
        INNER JOIN users u ON u.id = t.user_id
        WHERE t.transaction_date >= ? AND t.transaction_date < ?
        ORDER BY t.transaction_date DESC, t.id DESC
    ");
    if (!$transactions_stmt) {
        throw new RuntimeException("Gagal menyiapkan daftar laporan: " . $conn->error);
    }
    $transactions_stmt->bind_param("ss", $start_bound, $end_bound);
    $transactions_stmt->execute();
    $transactions = $transactions_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $transactions_stmt->close();
}
?>
<?php
$pageTitle = 'Laporan Penjualan';
require_once __DIR__ . '/../includes/header.php';
?>
<?php include __DIR__ . '/../includes/navbar.php'; ?>

<div class="app-layout d-flex flex-grow-1">

    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="main-content flex-grow-1 p-4">

        <div class="mb-4">
            <h3 class="fw-bold"><i class="bi bi-bar-chart-line me-2"></i>Laporan Penjualan</h3>
            <p class="text-muted">Ringkasan dan daftar penjualan berdasarkan periode tanggal.</p>
        </div>
        <form method="GET" class="row g-3 align-items-end mb-4">
            <div class="col-sm-5 col-lg-3">
                <label for="tanggal_mulai" class="form-label">Tanggal Mulai</label>
                <input type="date" class="form-control" id="tanggal_mulai" name="tanggal_mulai" value="<?= h($start_input); ?>" required>
            </div>
            <div class="col-sm-5 col-lg-3">
                <label for="tanggal_akhir" class="form-label">Tanggal Akhir</label>
                <input type="date" class="form-control" id="tanggal_akhir" name="tanggal_akhir" value="<?= h($end_input); ?>" required>
            </div>
            <div class="col-sm-2">
                <button class="btn btn-primary w-100" type="submit"><i class="bi bi-funnel me-1"></i>Filter</button>
            </div>
            <div class="col-sm-2 col-lg-auto">
                <a class="btn btn-outline-secondary w-100" href="index.php"><i class="bi bi-arrow-counterclockwise me-1"></i>Reset</a>
            </div>
            <div class="col-lg-auto ms-lg-auto">
                <?php if ($error === ''): ?>
                    <a class="btn btn-success" href="export.php?tanggal_mulai=<?= rawurlencode($start_input); ?>&amp;tanggal_akhir=<?= rawurlencode($end_input); ?>">
                        <i class="bi bi-file-earmark-excel me-1"></i>Ekspor Excel
                    </a>
                <?php endif; ?>
            </div>
        </form>

        <?php if ($error !== ''): ?>
            <div class="alert alert-danger" role="alert"><i class="bi bi-exclamation-triangle me-2"></i><?= h($error); ?></div>
        <?php else: ?>
            <p class="text-muted mb-3">Periode <?= h($start_date->format('d/m/Y')); ?> – <?= h($end_date->format('d/m/Y')); ?></p>
            <div class="row g-3 mb-4">
                <div class="col-md-6 col-xl-3"><div class="card border-0 shadow-sm h-100"><div class="card-body"><div class="text-muted small"><i class="bi bi-cash-stack me-1"></i>Total Omzet</div><div class="fs-5 fw-bold text-primary mt-1"><?= rupiah($summary['revenue']); ?></div></div></div></div>
                <div class="col-md-6 col-xl-3"><div class="card border-0 shadow-sm h-100"><div class="card-body"><div class="text-muted small"><i class="bi bi-receipt me-1"></i>Jumlah Transaksi</div><div class="fs-5 fw-bold text-primary mt-1"><?= number_format((int)$summary['transaction_count']); ?></div></div></div></div>
                <div class="col-md-6 col-xl-3"><div class="card border-0 shadow-sm h-100"><div class="card-body"><div class="text-muted small"><i class="bi bi-box-seam me-1"></i>Barang Terjual</div><div class="fs-5 fw-bold text-primary mt-1"><?= number_format((int)$summary['items_sold']); ?></div></div></div></div>
                <div class="col-md-6 col-xl-3"><div class="card border-0 shadow-sm h-100"><div class="card-body"><div class="text-muted small"><i class="bi bi-calculator me-1"></i>Rata-rata Transaksi</div><div class="fs-5 fw-bold text-primary mt-1"><?= rupiah($summary['average_transaction']); ?></div></div></div></div>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle">
                    <thead><tr><th>No</th><th>No. Invoice</th><th>Tanggal</th><th>Kasir</th><th>Barang</th><th>Pembayaran</th><th>Total Penjualan</th></tr></thead>
                    <tbody>
                    <?php if (!$transactions): ?>
                        <tr><td colspan="7" class="py-5 text-center text-muted"><i class="bi bi-inbox fs-3 d-block mb-2"></i>Tidak ada transaksi pada periode ini.</td></tr>
                    <?php else: ?>
                        <?php foreach ($transactions as $index => $transaction): ?>
                            <tr>
                                <td><?= $index + 1; ?></td>
                                <td><?= h($transaction['invoice_number']); ?></td>
                                <td><?= h(date('d/m/Y H:i', strtotime($transaction['transaction_date']))); ?></td>
                                <td><?= h($transaction['cashier_name']); ?></td>
                                <td><?= (int)$transaction['items_sold']; ?></td>
                                <td><?= h(strtoupper($transaction['payment_method'])); ?></td>
                                <td class="text-nowrap"><?= rupiah($transaction['total']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </main>

</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

