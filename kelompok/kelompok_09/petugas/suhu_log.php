<?php
require_once __DIR__ . '/../config/auth_check.php';

$pageTitle = 'Monitoring Suhu Kulkas';
$userLogin = currentUser();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kode    = trim($_POST['kode_kulkas'] ?? '');
    $suhu    = $_POST['suhu_celcius'] ?? '';
    $catatan = trim($_POST['catatan'] ?? '');

    if ($kode === '' || !is_numeric($suhu) || $suhu < -30 || $suhu > 40) {
        $error = 'Kode kulkas wajib diisi dan suhu harus angka antara -30 sampai 40 °C.';
    } else {
        $status = ($suhu >= 2 && $suhu <= 6) ? 'Normal' : 'Peringatan';
        $db->prepare("INSERT INTO log_suhu (petugas_id, kode_kulkas, suhu_celcius, status_aman, catatan)
                      VALUES (?, ?, ?, ?, ?)")
           ->execute([$userLogin['id'], $kode, (float) $suhu, $status, $catatan ?: null]);
        header('Location: suhu_log.php');
        exit;
    }
}

$riwayatSuhu = $db->query("SELECT log_suhu.*, users.nama FROM log_suhu
                           JOIN users ON log_suhu.petugas_id = users.id
                           ORDER BY waktu_catat ASC")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<main class="inv-wrapper">
    <div class="inv-head">
        <div>
            <h1>Monitoring Suhu Kulkas</h1>
            <p>Pantau suhu tempat penyimpanan kantong darah.</p>
        </div>
        <button class="inv-btn" onclick="dialogCatat.showModal()">+ Catat Suhu</button>
    </div>

    <div class="inv-card inv-card-auto">
        <table>
            <thead>
                <tr><th>No</th><th>Kode Kulkas</th><th>Suhu</th><th>Status</th><th>Catatan</th><th>Petugas</th><th>Waktu Catat</th></tr>
            </thead>
            <tbody>
                <?php foreach ($riwayatSuhu as $i => $r): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><?= e($r['kode_kulkas']) ?></td>
                        <td><?= e($r['suhu_celcius']) ?> °C</td>
                        <td><span class="inv-kondisi <?= $r['status_aman'] === 'Normal' ? 'aman' : 'expired' ?>"><?= e($r['status_aman']) ?></span></td>
                        <td><?= e($r['catatan'] ?? '-') ?></td>
                        <td><?= e($r['nama']) ?></td>
                        <td><?= e($r['waktu_catat']) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$riwayatSuhu): ?>
                    <tr><td colspan="7" class="inv-empty">Belum ada catatan suhu.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>

<dialog class="inv-dialog" id="dialogCatat" <?= $error ? 'open' : '' ?>>
    <h2>Catat Suhu Kulkas</h2>
    <?php if ($error): ?><div class="inv-error"><?= e($error) ?></div><?php endif; ?>

    <form method="POST">
        <div class="inv-row">
            <div>
                <label>Kode Kulkas</label>
                <input type="text" name="kode_kulkas" value="<?= e($_POST['kode_kulkas'] ?? 'Cooler-A1') ?>" required>
            </div>
            <div>
                <label>Suhu (°C)</label>
                <input type="number" name="suhu_celcius" step="0.1" placeholder="Contoh: 4.5" value="<?= e($_POST['suhu_celcius'] ?? '') ?>" required>
            </div>
        </div>
        <label>Catatan</label>
        <input type="text" name="catatan" placeholder="Opsional" value="<?= e($_POST['catatan'] ?? '') ?>">

        <button type="submit" class="inv-btn full">Simpan Catatan Suhu</button>
        <button type="button" class="inv-btn full batal" onclick="dialogCatat.close()">Batal</button>
    </form>
</dialog>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>