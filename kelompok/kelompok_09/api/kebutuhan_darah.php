<?php

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

try {
    require_once __DIR__ . '/../config/database.php';

    $stmt = $db->query(
        "SELECT judul_pesan, gol_darah, rhesus, rs_peminta, kebutuhan_kantong, kontak_darurat
         FROM sos_broadcast
         WHERE status_aktif = 1
         ORDER BY created_at DESC, id DESC"
    );

    $kebutuhan = $stmt->fetchAll();
    foreach ($kebutuhan as &$item) {
        $item['kebutuhan_kantong'] = (int)$item['kebutuhan_kantong'];
    }
    unset($item);

    echo json_encode(['success' => true, 'data' => $kebutuhan], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    error_log('Gagal mengambil kebutuhan darah: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Kebutuhan darah sedang tidak dapat dimuat.',
    ], JSON_UNESCAPED_UNICODE);
}
