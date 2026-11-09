<?php
require_once __DIR__ . '/../config/auth_check.php';
$pageTitle = 'Inventaris Kantong Darah';
$userLogin = currentUser();
$error = '';
$pilihan = [
    'gol_darah' => ['Golongan Darah', ['A', 'B', 'AB', 'O']], 'rhesus' => ['Rhesus', ['+', '-']],
    'komponen' => ['Komponen', ['WB', 'PRC', 'TC']],
    'status' => ['Status', ['tersedia', 'keluar', 'discard']],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $f = ['kode_kantong', 'gol_darah', 'rhesus', 'komponen', 'volume_ml', 'lokasi_rak', 'tgl_ambil', 'tgl_expired', 'status'];
    $d = array_map(fn($k) => trim($_POST[$k] ?? ''), array_combine($f, $f));
    if (in_array('', $d, true)) {
        $error = 'Semua kolom wajib diisi.';
    } elseif ($d['tgl_expired'] < $d['tgl_ambil']) {
        $error = 'Tanggal kedaluwarsa tidak boleh sebelum tanggal ambil.';
    } else {
        try {
            $db->prepare("INSERT INTO kantong_darah (" . implode(',', $f) . ") VALUES (:" . implode(',:', $f) . ")")->execute($d);
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        } catch (PDOException $ex) {
            $error = 'Gagal menyimpan: ' . $ex->getMessage(); // hapus getMessage() kalau sudah production
        }
    }
}
$stmt = $db->prepare("SELECT * FROM kantong_darah ORDER BY id ASC");
$stmt->execute();
$kantongDarah = $stmt->fetchAll();
require_once __DIR__ . '/../includes/header.php';
?>
<main class="inv-wrapper">
    <div class="inv-head">
        <div><h1>Inventaris Kantong Darah</h1><p>Kelola data stok kantong darah OneBlood.</p></div>
        <button class="inv-btn" onclick="tambah.showModal()">+ Tambah Kantong</button></div>

    <div class="inv-card">
        <table>
            <thead><tr><th>No</th><th>Kode Kantong</th><th>Golongan</th><th>Rhesus</th><th>Komponen</th><th>Volume (ml)</th><th>Lokasi Rak</th><th>Tanggal Ambil</th><th>Tanggal Kedaluwarsa</th><th>Status</th></tr></thead>
            <tbody>
                <?php foreach ($kantongDarah as $i => $k): ?>
                    <tr>
                        <td><?= $i + 1 ?></td><td><?= e($k['kode_kantong']) ?></td>
                        <td><span class="inv-badge"><?= e($k['gol_darah']) ?></span></td>
                        <td><?= e($k['rhesus']) ?></td><td><?= e($k['komponen']) ?></td>
                        <td><?= e((string)$k['volume_ml']) ?></td><td><?= e($k['lokasi_rak']) ?></td><td><?= e($k['tgl_ambil']) ?></td>
                        <td><?= e($k['tgl_expired']) ?></td><td><?= e($k['status']) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$kantongDarah): ?>
                    <tr><td colspan="10" class="inv-empty">Belum ada data kantong darah.</td></tr>
                <?php endif; ?>
            </tbody>
        </table></div>
    <dialog id="tambah" class="inv-dialog">
        <h2>Tambah Kantong Darah</h2>
        <?php if ($error): ?><div class="inv-error"><?= e($error) ?></div><?php endif; ?>
        <form method="post">
            <label>Kode Kantong</label><input name="kode_kantong" value="<?= e($_POST['kode_kantong'] ?? '') ?>" required>
            <div class="inv-row">
                <?php foreach ($pilihan as $n => [$label, $opsi]): ?>
                    <div><label><?= $label ?></label><select name="<?= $n ?>" required>
                         <option value="">Pilih <?= e($label) ?></option>
                            <?php foreach ($opsi as $o): ?>
                          <option value="<?= e($o) ?>" <?= ($_POST[$n] ?? '') === $o ? 'selected' : '' ?>> <?= e($o) ?>
                         </option>
                         <?php endforeach; ?>
                    </select>
                    </select></div>
                <?php endforeach; ?>
                <div><label>Volume (ml)</label><input type="number" name="volume_ml" min="1" value="<?= e($_POST['volume_ml'] ?? '350') ?>" required></div>
                <div><label>Tanggal Ambil</label><input type="date" name="tgl_ambil" value="<?= e($_POST['tgl_ambil'] ?? '') ?>" required></div>
                <div><label>Tanggal Kedaluwarsa</label><input type="date" name="tgl_expired" value="<?= e($_POST['tgl_expired'] ?? '') ?>" required></div>
                <div><label>Lokasi Rak</label><input name="lokasi_rak" value="<?= e($_POST['lokasi_rak'] ?? '') ?>" required></div>
            </div>
            <button class="inv-btn full">Simpan</button>
            <button type="button" class="inv-btn full batal" onclick="tambah.close()">Batal</button>
        </form>
    </dialog>
    <?php if ($error): ?><script>tambah.showModal();</script><?php endif; ?>
</main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>