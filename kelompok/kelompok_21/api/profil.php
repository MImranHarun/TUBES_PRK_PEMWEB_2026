<?php
require __DIR__ . '/bootstrap.php';
$id = $_SESSION['guru']['id_guru'];
if ($method === 'GET') {
    $stmt = $pdo->prepare('SELECT id_guru, nip, nama_guru, email, peran FROM GURU WHERE id_guru = ?');
    $stmt->execute([$id]); respond($stmt->fetch());
}
if ($method !== 'PUT') respond(null, 'Metode tidak didukung.', 405);
$input = inputData();
$nama = trim($input['nama_guru'] ?? ''); $nip = trim($input['nip'] ?? '');
$email = trim($input['email'] ?? ''); $password = $input['password_baru'] ?? '';
if (!$nama || preg_match_all('/./us', $nama) > 100 || !$nip || strlen($nip) > 50
    || strlen($email) > 100 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    respond(null, 'Nama, NIP, dan email harus diisi dengan benar.', 422);
}
$stmt = $pdo->prepare('SELECT password FROM GURU WHERE id_guru = ?'); $stmt->execute([$id]);
if (!password_verify($input['password_lama'] ?? '', $stmt->fetchColumn())) {
    respond(null, 'Password saat ini tidak sesuai.', 422);
}
if ($password !== '' && (strlen($password) < 8 || strlen($password) > 72)) {
    respond(null, 'Password baru harus 8–72 karakter.', 422);
}
$stmt = $pdo->prepare('UPDATE GURU SET nip = ?, nama_guru = ?, email = ? WHERE id_guru = ?');
$stmt->execute([$nip, $nama, $email, $id]);
if ($password !== '') {
    $stmt = $pdo->prepare('UPDATE GURU SET password = ? WHERE id_guru = ?');
    $stmt->execute([password_hash($password, PASSWORD_DEFAULT), $id]);
}
$_SESSION['guru']['nama_guru'] = $nama;
respond(null, 'Profil berhasil diperbarui.');
