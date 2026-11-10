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

$start_input = is_string($_GET['tanggal_mulai'] ?? null) ? trim($_GET['tanggal_mulai']) : '';
$end_input = is_string($_GET['tanggal_akhir'] ?? null) ? trim($_GET['tanggal_akhir']) : '';
$start_date = parse_date($start_input);
$end_date = parse_date($end_input);

if (!$start_date || !$end_date || $start_date > $end_date) {
    http_response_code(400);
    header('Content-Type: text/plain; charset=UTF-8');
    exit('Rentang tanggal tidak valid.');
}

$start_bound = $start_date->format('Y-m-d 00:00:00');
$end_bound = $end_date->modify('+1 day')->format('Y-m-d 00:00:00');
$stmt = $conn->prepare("
    SELECT t.invoice_number, t.transaction_date, u.name AS cashier_name,
           t.payment_method, td.product_name, td.quantity, td.unit_price, td.subtotal, t.total
    FROM transactions t
    INNER JOIN users u ON u.id = t.user_id
    INNER JOIN transaction_details td ON td.transaction_id = t.id
    WHERE t.transaction_date >= ? AND t.transaction_date < ?
    ORDER BY t.transaction_date ASC, t.id ASC, td.id ASC
");
if (!$stmt) {
    throw new RuntimeException("Gagal menyiapkan ekspor laporan: " . $conn->error);
}
$stmt->bind_param("ss", $start_bound, $end_bound);
$stmt->execute();
$result = $stmt->get_result();

function spreadsheet_text(string $value): string
{
    return preg_match('/^[\s\x00-\x1F]*[=+\-@]/', $value) === 1 ? "'" . $value : $value;
}

$filename = 'laporan-penjualan-' . $start_input . '-sampai-' . $end_input . '.csv';
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: no-store, no-cache, must-revalidate');

$output = fopen('php://output', 'w');
if ($output === false) {
    throw new RuntimeException('Gagal membuat berkas ekspor.');
}
fwrite($output, "\xEF\xBB\xBF");
fputcsv($output, ['No. Invoice', 'Tanggal', 'Kasir', 'Metode Pembayaran', 'Nama Produk', 'Jumlah', 'Harga Satuan', 'Subtotal Item', 'Total Transaksi'], ';');
while ($row = $result->fetch_assoc()) {
    fputcsv($output, [
        spreadsheet_text($row['invoice_number']),
        $row['transaction_date'],
        spreadsheet_text($row['cashier_name']),
        spreadsheet_text(strtoupper($row['payment_method'])),
        spreadsheet_text($row['product_name']),
        (int)$row['quantity'],
        (float)$row['unit_price'],
        (float)$row['subtotal'],
        (float)$row['total'],
    ], ';');
}
fclose($output);
$stmt->close();
