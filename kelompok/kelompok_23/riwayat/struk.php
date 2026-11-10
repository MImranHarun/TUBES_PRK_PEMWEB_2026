<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once __DIR__ . '/../config/database.php';

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function rupiah(float|string $amount): string
{
    return 'Rp ' . number_format((float)$amount, 0, ',', '.');
}

$transaction_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if ($transaction_id === false || $transaction_id === null || $transaction_id < 1) {
    http_response_code(400);
    exit('ID transaksi tidak valid.');
}

$stmt = $conn->prepare("
    SELECT t.id, t.invoice_number, t.transaction_date, t.subtotal, t.total,
           t.amount_paid, t.change_amount, t.payment_method, u.name AS cashier_name
    FROM transactions t
    INNER JOIN users u ON u.id = t.user_id
    WHERE t.id = ?
    LIMIT 1
");
if (!$stmt) {
    throw new RuntimeException("Gagal menyiapkan data struk: " . $conn->error);
}
$stmt->bind_param("i", $transaction_id);
$stmt->execute();
$transaction = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$transaction) {
    http_response_code(404);
    exit('Transaksi tidak ditemukan.');
}

$details_stmt = $conn->prepare("
    SELECT product_name, quantity, unit_price, subtotal
    FROM transaction_details
    WHERE transaction_id = ?
    ORDER BY id
");
if (!$details_stmt) {
    throw new RuntimeException("Gagal menyiapkan detail struk: " . $conn->error);
}
$details_stmt->bind_param("i", $transaction_id);
$details_stmt->execute();
$details = $details_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$details_stmt->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Struk <?= h($transaction['invoice_number']); ?> | FEBMart</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { padding:24px; background:#f3f4f6; font-family:Arial,sans-serif; }
        .receipt { width:100%; max-width:420px; margin:0 auto; padding:28px; background:#fff; box-shadow:0 4px 20px rgba(0,0,0,.08); }
        .receipt-header { padding-bottom:16px; border-bottom:1px dashed #777; text-align:center; }
        .receipt-header h1 { margin:0 0 5px; color:#29245c; font-size:24px; font-weight:bold; }
        .receipt-meta { padding:14px 0; border-bottom:1px dashed #777; font-size:12px; }
        .receipt-items { width:100%; margin:14px 0; font-size:12px; }
        .receipt-items td { padding:4px 0; vertical-align:top; }
        .receipt-totals { padding-top:12px; border-top:1px dashed #777; font-size:13px; }
        .receipt-totals .row-line { display:flex; justify-content:space-between; gap:12px; margin:5px 0; }
        .receipt-footer { padding-top:16px; font-size:12px; text-align:center; }
        @media print {
            @page { size:80mm auto; margin:4mm; }
            body { padding:0; background:#fff; }
            .receipt { width:100%; max-width:none; padding:0; box-shadow:none; }
            .receipt-header, .receipt-meta, .receipt-items, .receipt-totals, .receipt-footer { break-inside:avoid; }
            .no-print { display:none !important; }
        }
    </style>
</head>
<body>
<main class="receipt">
    <header class="receipt-header">
        <h1>FEBMart</h1>
        <div>Struk Pembayaran</div>
    </header>
    <section class="receipt-meta">
        <div class="d-flex justify-content-between gap-2"><span>Invoice</span><strong><?= h($transaction['invoice_number']); ?></strong></div>
        <div class="d-flex justify-content-between gap-2"><span>Tanggal</span><span><?= h(date('d/m/Y H:i', strtotime($transaction['transaction_date']))); ?></span></div>
        <div class="d-flex justify-content-between gap-2"><span>Kasir</span><span><?= h($transaction['cashier_name']); ?></span></div>
    </section>
    <table class="receipt-items">
        <tbody>
        <?php foreach ($details as $detail): ?>
            <tr><td colspan="2"><strong><?= h($detail['product_name']); ?></strong></td></tr>
            <tr>
                <td><?= (int)$detail['quantity']; ?> × <?= rupiah($detail['unit_price']); ?></td>
                <td class="text-end"><?= rupiah($detail['subtotal']); ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <section class="receipt-totals">
        <div class="row-line"><span>Subtotal</span><strong><?= rupiah($transaction['subtotal']); ?></strong></div>
        <div class="row-line"><span>Total</span><strong><?= rupiah($transaction['total']); ?></strong></div>
        <div class="row-line"><span>Metode Pembayaran</span><span><?= h(strtoupper($transaction['payment_method'])); ?></span></div>
        <div class="row-line"><span>Dibayar</span><span><?= rupiah($transaction['amount_paid']); ?></span></div>
        <div class="row-line"><span>Kembalian</span><span><?= rupiah($transaction['change_amount']); ?></span></div>
    </section>
    <footer class="receipt-footer">
        <div>Terima kasih telah berbelanja di FEBMart.</div>
        <div class="no-print d-flex justify-content-center gap-2 mt-4">
            <button type="button" class="btn btn-primary btn-sm" id="print-receipt"><i class="bi bi-printer me-1"></i>Cetak Struk</button>
            <a href="index.php" class="btn btn-outline-secondary btn-sm">Kembali</a>
        </div>
    </footer>
</main>
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<script>
document.getElementById('print-receipt').addEventListener('click', function () {
    window.print();
});
</script>
</body>
</html>
