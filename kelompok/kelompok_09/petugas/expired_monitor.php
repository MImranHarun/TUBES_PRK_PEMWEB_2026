<?php
require_once __DIR__ . '/../config/auth_check.php';

$pageTitle = 'Monitoring Kedaluwarsa';
$userLogin = currentUser();

$stmt = $db->prepare("SELECT * FROM kantong_darah WHERE status = 'tersedia' ORDER BY tgl_expired ASC");
$stmt->execute();
$kantongDarah = $stmt->fetchAll();

$hariIni = date('Y-m-d');
$batas   = date('Y-m-d', strtotime('+30 days'));
$hitung  = ['expired' => 0, 'segera' => 0, 'aman' => 0];
$ringkas = ['expired' => 'Sudah kedaluwarsa', 'segera' => 'Kedaluwarsa dalam 30 hari', 'aman' => 'Masih aman'];
$label   = ['expired' => 'Kedaluwarsa', 'segera' => 'Segera Kedaluwarsa', 'aman' => 'Aman'];

foreach ($kantongDarah as &$k) {
    $k['kondisi'] = $k['tgl_expired'] < $hariIni ? 'expired' : ($k['tgl_expired'] <= $batas ? 'segera' : 'aman');
    $hitung[$k['kondisi']]++;
}
unset($k);

require_once __DIR__ . '/../includes/header.php';
?>

<main class="inv-wrapper">
    <div class="inv-head">
        <div>
            <h1>Monitoring Kedaluwarsa</h1>
            <p>Pantau masa berlaku stok kantong darah.</p>
            <a class="staff-service-back" href="<?= e(base_url('petugas/dashboard.php')) ?>">Kembali ke layanan</a>
        </div>
    </div>

    <div class="inv-stats">
        <?php foreach ($ringkas as $key => $teks): ?>
            <div class="inv-stat <?= $key ?>"><span><?= $hitung[$key] ?></span><p><?= $teks ?></p></div>
        <?php endforeach; ?>
    </div>

    <div class="inv-card inv-card-auto">
        <table>
            <thead>
                <tr><th>No</th><th>Kode Kantong</th><th>Golongan Darah</th><th>Komponen</th><th>Tanggal Kedaluwarsa</th><th>Kondisi</th></tr>
            </thead>
            <tbody>
                <?php foreach ($kantongDarah as $i => $k): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><?= e($k['kode_kantong']) ?></td>
                        <td><span class="inv-badge"><?= e($k['gol_darah'] . $k['rhesus']) ?></span></td>
                        <td><?= e($k['komponen']) ?></td>
                        <td><?= e($k['tgl_expired']) ?></td>
                        <td><span class="inv-kondisi <?= $k['kondisi'] ?>"><?= $label[$k['kondisi']] ?></span></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$kantongDarah): ?>
                    <tr><td colspan="6" class="inv-empty">Belum ada data kantong darah.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>