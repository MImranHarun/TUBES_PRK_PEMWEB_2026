<?php
header('Content-Type: application/json; charset=utf-8');

// Hanya izinkan metode GET
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($method !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Metode request tidak diizinkan. Hanya menerima GET.'
    ]);
    exit;
}

require_once __DIR__ . '/../config/database.php';

try {
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';

    // Query hanya mengambil laporan publik (is_public = 1)
    $sql = "
        SELECT l.id, l.judul, l.isi, l.lokasi, l.jenis, l.status, l.created_at,
               k.nama_kategori,
               u.nama AS nama_pelapor,
               (
                   SELECT isi_tanggapan 
                   FROM tanggapan 
                   WHERE laporan_id = l.id 
                   ORDER BY created_at DESC 
                   LIMIT 1
               ) AS tanggapan_terakhir
        FROM laporan l
        JOIN users u ON l.user_id = u.id
        LEFT JOIN kategori k ON l.kategori_id = k.id
        WHERE l.is_public = 1
    ";

    $params = [];

    // Filter pencarian jika ada kata kunci
    if ($search !== '') {
        $sql .= " AND (l.judul LIKE :s1 OR l.isi LIKE :s2 OR l.lokasi LIKE :s3)";
        $searchTerm = '%' . $search . '%';
        $params[':s1'] = $searchTerm;
        $params[':s2'] = $searchTerm;
        $params[':s3'] = $searchTerm;
    }

    $sql .= " ORDER BY l.created_at DESC, l.id DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $laporan = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format response JSON sukses (HTTP 200)
    http_response_code(200);
    echo json_encode([
        'status'  => 'success',
        'message' => 'Data berhasil diambil',
        'data'    => $laporan
    ], JSON_UNESCAPED_UNICODE);
    exit;

} catch (PDOException $e) {
    // Sesuai SEC-10: tidak menampilkan raw error database ke client
    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Terjadi kesalahan pada server saat mengambil data laporan.'
    ]);
    exit;
}
