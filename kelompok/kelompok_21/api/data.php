<?php
require __DIR__ . '/bootstrap.php';
$modules = require __DIR__ . '/../config/modules.php';
$module = $_GET['modul'] ?? '';
if (!isset($modules[$module])) respond(null, 'Modul tidak ditemukan.', 404);
$config = $modules[$module];
$table = $config['table'];
$pk = $config['pk'];
$idGuru = $_SESSION['guru']['id_guru'];

if ($method === 'GET') {
    $where = ''; $params = [];
    if (!isAdmin() && in_array($module, ['kelas', 'siswa', 'penugasan'])) {
        if ($module === 'penugasan') {
            $where = ' WHERE id_guru = ?'; $params = [$idGuru];
        } else {
            $where = ' WHERE id_kelas IN (SELECT k.id_kelas FROM KELAS k
                WHERE k.id_wali_kelas = ? OR EXISTS (SELECT 1 FROM PENUGASAN_MENGAJAR p
                WHERE p.id_kelas = k.id_kelas AND p.id_guru = ?))';
            $params = [$idGuru, $idGuru];
        }
    }
    $stmt = $pdo->prepare("SELECT * FROM $table $where ORDER BY $pk DESC");
    $stmt->execute($params);
    respond($stmt->fetchAll());
}
if (!in_array($method, ['POST', 'PUT', 'DELETE'])) respond(null, 'Metode tidak didukung.', 405);
if (!isAdmin() && in_array($module, ['mapel', 'periode', 'penugasan'])) {
    respond(null, 'Data bersama hanya dapat diubah admin.', 403);
}
$id = null;
if ($method !== 'POST') {
    $id = positiveId($_GET['id'] ?? 0);
    $stmt = $pdo->prepare("SELECT * FROM $table WHERE $pk = ?");
    $stmt->execute([$id]);
    $old = $stmt->fetch();
    if (!$old) respond(null, 'Data tidak ditemukan.', 404);
    if (in_array($module, ['kelas', 'siswa'])) requireClass($pdo, $old['id_kelas']);
}
if ($method === 'DELETE') {
    $stmt = $pdo->prepare("DELETE FROM $table WHERE $pk = ?");
    $stmt->execute([$id]);
    respond(null, 'Data berhasil dihapus.');
}
$input = inputData();
$values = [];
foreach ($config['fields'] as $field => $limit) {
    $value = trim((string)($input[$field] ?? ''));
    if ($value === '' || ($limit > 0 && preg_match_all('/./us', $value) > $limit)) {
        respond(null, "Isian $field wajib diisi, maksimal $limit karakter untuk teks.", 422);
    }
    if ($limit === 0) $value = positiveId($value);
    if ($limit === -1 && !in_array($value, ['0', '1'], true)) respond(null, 'Status tidak valid.', 422);
    $values[$field] = $value;
}
if ($module === 'kelas' && !isAdmin()) {
    // Guru hanya dapat membuat kelas dengan dirinya sebagai wali.
    if (($method === 'POST' && (int)$values['id_wali_kelas'] !== (int)$idGuru)
        || ($method === 'PUT' && (int)$values['id_wali_kelas'] !== (int)$old['id_wali_kelas'])) {
        respond(null, 'Penggantian wali kelas dilakukan oleh admin.', 403);
    }
}
if ($module === 'siswa') {
    requireClass($pdo, $values['id_kelas']);
    if (!in_array($values['jenis_kelamin'], ['Laki-laki', 'Perempuan'])) respond(null, 'Jenis kelamin tidak valid.', 422);
    if (!preg_match('/^[0-9]{10}$/', $values['nisn'])) respond(null, 'NISN harus terdiri dari 10 digit.', 422);
}
if ($module === 'periode') {
    if (!preg_match('/^(\d{4})\/(\d{4})$/', $values['tahun_ajaran'], $year)
        || (int)$year[2] !== (int)$year[1] + 1) respond(null, 'Tahun ajaran harus berurutan, contoh 2026/2027.', 422);
    if (!in_array($values['semester'], ['Ganjil', 'Genap'])) respond(null, 'Semester tidak valid.', 422);
}
// Tolak duplikasi sebelum menyimpan (indeks unik juga melindungi permintaan bersamaan).
$unique = ['kelas' => ['nama_kelas'], 'siswa' => ['nisn'], 'mapel' => ['kode_mapel'],
    'periode' => ['tahun_ajaran', 'semester'], 'penugasan' => ['id_guru','id_kelas','id_mapel','id_periode']];
$conditions = []; $params = [];
foreach ($unique[$module] as $field) { $conditions[] = "$field = ?"; $params[] = $values[$field]; }
$params[] = $id ?? 0;
$stmt = $pdo->prepare("SELECT $pk FROM $table WHERE " . implode(' AND ', $conditions) . " AND $pk <> ?");
$stmt->execute($params);
if ($stmt->fetch()) respond(null, 'Data yang sama sudah terdaftar.', 409);
// Hanya satu periode aktif; kunci baris periode agar perubahan konsisten.
$pdo->beginTransaction();
if ($module === 'periode' && $values['status_aktif'] === '1') {
    $pdo->query('SELECT id_periode FROM PERIODE_PENILAIAN FOR UPDATE')->fetchAll();
    $pdo->exec('UPDATE PERIODE_PENILAIAN SET status_aktif = 0');
}
if ($method === 'POST') {
    $columns = implode(', ', array_keys($values));
    $marks = implode(', ', array_fill(0, count($values), '?'));
    $stmt = $pdo->prepare("INSERT INTO $table ($columns) VALUES ($marks)");
    $stmt->execute(array_values($values));
    $id = (int)$pdo->lastInsertId();
} else {
    $sets = implode(', ', array_map(function ($field) { return "$field = ?"; }, array_keys($values)));
    $stmt = $pdo->prepare("UPDATE $table SET $sets WHERE $pk = ?");
    $stmt->execute(array_merge(array_values($values), [$id]));
}
$pdo->commit();
respond([$pk => $id], 'Data berhasil disimpan.', $method === 'POST' ? 201 : 200);


