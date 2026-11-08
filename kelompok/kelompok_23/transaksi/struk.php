<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once __DIR__ . '/../config/database.php';

$invoice = isset($_GET['invoice']) ? trim($_GET['invoice']) : '';

if (empty($invoice)) {
    die("Nomor invoice tidak ditemukan. <a href='index.php'>Kembali ke Kasir</a>");
}

$stmt = $conn->prepare("
    SELECT t.*, u.name AS nama_kasir 
    FROM transactions t 
    LEFT JOIN users u ON t.user_id = u.id 
    WHERE t.invoice_number = ?
    LIMIT 1
");
$stmt->bind_param("s", $invoice);
$stmt->execute();
$transaksi = $stmt->get_result()->fetch_assoc();

if (!$transaksi) {
    die("Data transaksi tidak ditemukan. <a href='index.php'>Kembali ke Kasir</a>");
}

$stmt_items = $conn->prepare("
    SELECT * FROM transaction_details 
    WHERE transaction_id = ?
    ORDER BY id ASC
");
$stmt_items->bind_param("i", $transaksi['id']);
$stmt_items->execute();
$items = $stmt_items->get_result();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struk Pembayaran <?= htmlspecialchars($transaksi['invoice_number']); ?> | FEBMart</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: #f3f4f6;
            font-family: 'Courier New', Courier, monospace;
            padding: 30px 15px;
            color: #333;
        }

        .receipt-card {
            max-width: 360px;
            margin: 0 auto;
            background: #fff;
            padding: 24px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            border: 1px dashed #ccc;
        }

        .receipt-header {
            text-align: center;
            border-bottom: 1px dashed #333;
            padding-bottom: 12px;
            margin-bottom: 12px;
        }

        .receipt-title {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 2px;
            letter-spacing: 1px;
        }

        .receipt-sub {
            font-size: 11px;
            color: #666;
            margin-bottom: 0;
        }

        .receipt-meta {
            font-size: 11px;
            border-bottom: 1px dashed #333;
            padding-bottom: 10px;
            margin-bottom: 10px;
        }

        .receipt-table {
            width: 100%;
            font-size: 11px;
            margin-bottom: 12px;
        }

        .receipt-table td {
            padding: 4px 0;
            vertical-align: top;
        }

        .receipt-totals {
            font-size: 12px;
            border-top: 1px dashed #333;
            padding-top: 8px;
            margin-bottom: 12px;
        }

        .receipt-footer {
            text-align: center;
            border-top: 1px dashed #333;
            padding-top: 12px;
            font-size: 11px;
            color: #555;
        }

        .actions-bar {
            max-width: 360px;
            margin: 20px auto 0;
            display: flex;
            gap: 10px;
        }

        @media print {
            body {
                background: #fff;
                padding: 0;
            }

            .receipt-card {
                box-shadow: none;
                border: none;
                width: 100%;
                max-width: 100%;
                padding: 0;
            }

            .actions-bar {
                display: none !important;
            }
        }
    </style>
</head>
<body>
    <div class="receipt-card">
        <div class="receipt-header">
            <div class="receipt-title">FEBMART POS</div>
            <p class="receipt-sub">Fakultas Ekonomi dan Bisnis</p>
            <p class="receipt-sub">Universitas Lampung</p>
        </div>

        <div class="receipt-meta">
            <div class="d-flex justify-content-between">
                <span>No. Nota:</span>
                <strong><?= htmlspecialchars($transaksi['invoice_number']); ?></strong>
            </div>
            <div class="d-flex justify-content-between">
                <span>Tanggal:</span>
                <span><?= date('d/m/Y H:i', strtotime($transaksi['created_at'])); ?></span>
            </div>
            <div class="d-flex justify-content-between">
                <span>Kasir:</span>
                <span><?= htmlspecialchars($transaksi['nama_kasir'] ?? 'Kasir'); ?></span>
            </div>
            <div class="d-flex justify-content-between">
                <span>Pelanggan:</span>
                <span><?= htmlspecialchars($transaksi['customer_name']); ?></span>
            </div>
        </div>

        <table class="receipt-table">
            <tbody>
                <?php while ($it = $items->fetch_assoc()): ?>
                    <tr>
                        <td colspan="2">
                            <strong><?= htmlspecialchars($it['product_name']); ?></strong>
                        </td>
                    </tr>
                    <tr>
                        <td class="text-muted ps-2">
                            <?= $it['qty']; ?> x Rp <?= number_format($it['price'], 0, ',', '.'); ?>
                        </td>
                        <td class="text-end fw-bold">
                            Rp <?= number_format($it['subtotal'], 0, ',', '.'); ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <div class="receipt-totals">
            <div class="d-flex justify-content-between mb-1">
                <span>Total:</span>
                <strong>Rp <?= number_format($transaksi['total_amount'], 0, ',', '.'); ?></strong>
            </div>
            <div class="d-flex justify-content-between mb-1">
                <span>Tunai (Cash):</span>
                <span>Rp <?= number_format($transaksi['paid_amount'], 0, ',', '.'); ?></span>
            </div>
            <div class="d-flex justify-content-between">
                <span>Kembalian:</span>
                <strong>Rp <?= number_format($transaksi['change_amount'], 0, ',', '.'); ?></strong>
            </div>
        </div>

        <div class="receipt-footer">
            <p class="mb-1">*** TERIMA KASIH ***</p>
            <p class="mb-0">Barang yang sudah dibeli tidak dapat ditukar kembali.</p>
        </div>
    </div>

    <div class="actions-bar">
        <a href="index.php" class="btn btn-outline-secondary flex-fill">
            <i class="bi bi-arrow-left me-1"></i>Kasir
        </a>
        <button type="button" onclick="window.print()" class="btn btn-primary flex-fill" style="background: #29245c; border-color: #29245c;">
            <i class="bi bi-printer me-1"></i>Cetak Struk
        </button>
    </div>
</body>
</html>
