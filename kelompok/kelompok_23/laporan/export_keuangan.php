<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once __DIR__ . '/../config/database.php';

function spreadsheet_text(string $value): string
{
    return preg_match('/^[\s\x00-\x1F]*[=+\-@]/', $value) === 1 ? "'" . $value : $value;
}

// Ringkasan total
$row = $conn->query(
    "SELECT COUNT(*) AS n, COALESCE(SUM(total), 0) AS omzet, COALESCE(AVG(total), 0) AS rata
     FROM transactions"
)->fetch_assoc();
$total_transaksi = (int)$row['n'];
$total_omzet = (float)$row['omzet'];
$total_rata = (float)$row['rata'];
$barang = $conn->query("SELECT COALESCE(SUM(quantity), 0) AS qty FROM transaction_details")->fetch_assoc();
$total_barang = (int)$barang['qty'];

// Rekap per bulan
$bulanan = $conn->query("
    SELECT DATE_FORMAT(t.transaction_date, '%Y-%m') AS periode,
           COUNT(DISTINCT t.id) AS jumlah_transaksi,
           COALESCE(SUM(td.quantity), 0) AS barang_terjual,
           COALESCE(SUM(t.total), 0) AS omzet,
           ROUND(COALESCE(AVG(t.total), 0), 2) AS rata_rata
    FROM transactions t
    LEFT JOIN transaction_details td ON td.transaction_id = t.id
    GROUP BY periode
    ORDER BY periode DESC
");

// Rekap per kasir
$kasir = $conn->query("
    SELECT u.name AS kasir, COUNT(t.id) AS jumlah_transaksi, COALESCE(SUM(t.total), 0) AS omzet
    FROM transactions t
    INNER JOIN users u ON u.id = t.user_id
    GROUP BY u.id, u.name
    ORDER BY omzet DESC
");

// Rekap per metode pembayaran
$metode = $conn->query("
    SELECT payment_method, COUNT(*) AS jumlah_transaksi, COALESCE(SUM(total), 0) AS omzet
    FROM transactions
    GROUP BY payment_method
    ORDER BY omzet DESC
");

$filename = 'laporan-keuangan-febmart-' . date('Ymd-His') . '.csv';
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: no-store, no-cache, must-revalidate');

$output = fopen('php://output', 'w');
if ($output === false) {
    throw new RuntimeException('Gagal membuat berkas ekspor.');
}
fwrite($output, "\xEF\xBB\xBF");

fputcsv($output, ['LAPORAN KEUANGAN FEBMART'], ';');
fputcsv($output, ['Dibuat pada', date('d/m/Y H:i')], ';');
fputcsv($output, [], ';');
fputcsv($output, ['RINGKASAN TOTAL'], ';');
fputcsv($output, ['Total Omzet', 'Jumlah Transaksi', 'Barang Terjual', 'Rata-rata per Transaksi'], ';');
fputcsv($output, [$total_omzet, $total_transaksi, $total_barang, $total_rata], ';');
fputcsv($output, [], ';');

fputcsv($output, ['REKAP PER BULAN'], ';');
fputcsv($output, ['Periode', 'Transaksi', 'Barang Terjual', 'Omzet', 'Rata-rata'], ';');
while ($row = $bulanan->fetch_assoc()) {
    fputcsv($output, [
        $row['periode'],
        (int)$row['jumlah_transaksi'],
        (int)$row['barang_terjual'],
        (float)$row['omzet'],
        (float)$row['rata_rata'],
    ], ';');
}
fputcsv($output, [], ';');

fputcsv($output, ['REKAP PER KASIR'], ';');
fputcsv($output, ['Kasir', 'Transaksi', 'Omzet'], ';');
while ($row = $kasir->fetch_assoc()) {
    fputcsv($output, [
        spreadsheet_text($row['kasir']),
        (int)$row['jumlah_transaksi'],
        (float)$row['omzet'],
    ], ';');
}
fputcsv($output, [], ';');

fputcsv($output, ['REKAP PER METODE PEMBAYARAN'], ';');
fputcsv($output, ['Metode', 'Transaksi', 'Omzet'], ';');
while ($row = $metode->fetch_assoc()) {
    fputcsv($output, [
        spreadsheet_text(strtoupper($row['payment_method'])),
        (int)$row['jumlah_transaksi'],
        (float)$row['omzet'],
    ], ';');
}

fclose($output);
$conn->close();
