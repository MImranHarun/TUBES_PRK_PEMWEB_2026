<?php

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

try {
    require_once __DIR__ . '/../config/database.php';

    $stmt = $db->query(
        "SELECT gol_darah, COUNT(*) AS jumlah
         FROM kantong_darah
         WHERE status = 'tersedia' AND tgl_expired >= CURDATE()
         GROUP BY gol_darah"
    );

    $stok = ['A' => 0, 'B' => 0, 'O' => 0, 'AB' => 0];
    foreach ($stmt->fetchAll() as $row) {
        $stok[$row['gol_darah']] = (int)$row['jumlah'];
    }

    echo json_encode(['success' => true, 'data' => $stok], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    error_log('Gagal mengambil stok darah: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Stok darah sedang tidak dapat dimuat.',
    ], JSON_UNESCAPED_UNICODE);
}
