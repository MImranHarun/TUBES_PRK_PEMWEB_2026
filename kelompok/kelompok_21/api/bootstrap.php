<?php
require_once __DIR__ . '/../includes/auth.php';
header('Content-Type: application/json; charset=utf-8');
function respond($data = null, $message = '', $code = 200) {
    http_response_code($code);
    echo json_encode(['status' => $code < 400 ? 'success' : 'error',
        'message' => $message, 'data' => $data]);
    exit;
}
set_exception_handler(function ($error) {
    error_log($error->getMessage());
    if ($error instanceof PDOException && $error->getCode() == '23000') {
        respond(null, 'Data duplikat atau masih dipakai oleh data lain. Periksa relasinya.', 409);
    }
    respond(null, 'Terjadi gangguan server. Periksa koneksi database dan log PHP.', 500);
});
if (empty($_SESSION['guru'])) respond(null, 'Silakan masuk terlebih dahulu.', 401);
$method = $_SERVER['REQUEST_METHOD'];
if ($method !== 'GET' && !hash_equals($_SESSION['csrf'], $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '')) {
    respond(null, 'Token tidak valid. Muat ulang halaman.', 403);
}
require __DIR__ . '/../config/database.php';
function inputData() {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) respond(null, 'Format JSON tidak valid.', 422);
    foreach ($input as $value) {
        if (!is_scalar($value) && $value !== null) respond(null, 'Isian tidak valid.', 422);
    }
    return $input;
}
function positiveId($value) {
    if (!filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])) {
        respond(null, 'ID harus berupa angka positif.', 422);
    }
    return (int)$value;
}
function requireClass(PDO $pdo, $id) {
    if (!canAccessClass($pdo, positiveId($id))) respond(null, 'Anda tidak memiliki akses ke kelas ini.', 403);
}
