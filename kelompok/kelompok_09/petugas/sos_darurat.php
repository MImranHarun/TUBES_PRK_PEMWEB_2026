<?php
require_once '../config/database.php';
require_once '../config/auth_check.php';

/** @var PDO $pdo */

// Proteksi Halaman: Hanya Petugas Medis dan Admin yang bisa mengakses
cekRole(['petugas', 'admin']);

$pesan_sukses = "";
$pesan_gagal  = "";

// ==========================================
// 1. PROSES FORM: TAMBAH BROADCAST SOS BARU
// ==========================================
if (isset($_POST['tambah_sos'])) {
    $judul_pesan      = trim($_POST['judul_pesan'] ?? '');
    $gol_darah        = trim($_POST['gol_darah']);
    $rhesus           = trim($_POST['rhesus']);
    $rs_peminta       = trim($_POST['rs_peminta']);
    $kebutuhan_kantong= (int)$_POST['kebutuhan_kantong'];
    $kontak_darurat   = trim($_POST['kontak_darurat']);

    if (!empty($judul_pesan) && !empty($gol_darah) && !empty($rs_peminta) && $kebutuhan_kantong > 0 && !empty($kontak_darurat)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO sos_broadcast (judul_pesan, gol_darah, rhesus, rs_peminta, kebutuhan_kantong, kontak_darurat, status_aktif, created_at)
                                   VALUES (:judul_pesan, :gol_darah, :rhesus, :rs_peminta, :kebutuhan_kantong, :kontak_darurat, 1, NOW())");
            $stmt->execute([
                ':judul_pesan'      => $judul_pesan,
                ':gol_darah'        => $gol_darah,
                ':rhesus'           => $rhesus,
                ':rs_peminta'       => $rs_peminta,
                ':kebutuhan_kantong'=> $kebutuhan_kantong,
                ':kontak_darurat'   => $kontak_darurat
            ]);
            $pesan_sukses = "Pengumuman SOS Darurat berhasil dipublikasikan!";
        } catch (PDOException $e) {
            $pesan_gagal = "Gagal menyimpan data: " . $e->getMessage();
        }
    } else {
        $pesan_gagal = "Mohon isi semua kolom dengan benar!";
    }
}

// ==========================================
// 2. PROSES UPDATE: UBAH STATUS (AKTIF / SELESAI)
// ==========================================
if (isset($_GET['action']) && $_GET['action'] === 'toggle_status' && isset($_GET['id'])) {
    $id_sos = (int)$_GET['id'];

    try {
        $stmt = $pdo->prepare("UPDATE sos_broadcast SET status_aktif = IF(status_aktif = 1, 0, 1) WHERE id = :id AND status_aktif IN (0, 1)");
        $stmt->execute([':id' => $id_sos]);
        header("Location: sos_darurat.php?msg=updated");
        exit();
    } catch (PDOException $e) {
        $pesan_gagal = "Gagal memperbarui status: " . $e->getMessage();
    }
}

if (isset($_GET['action']) && $_GET['action'] === 'approve' && isset($_GET['id'])) {
    $id_sos = (int) $_GET['id'];

    try {
        $stmt = $pdo->prepare("UPDATE sos_broadcast SET status_aktif = 1 WHERE id = :id AND status_aktif = 2");
        $stmt->execute([':id' => $id_sos]);
        header("Location: sos_darurat.php?msg=approved");
        exit();
    } catch (PDOException $e) {
        $pesan_gagal = "Gagal menyetujui pengajuan: " . $e->getMessage();
    }
}

// ==========================================
// 3. PROSES HAPUS: HAPUS BROADCAST SOS
// ==========================================
if (isset($_GET['action']) && $_GET['action'] === 'hapus' && isset($_GET['id'])) {
    $id_sos = (int)$_GET['id'];

    try {
        $stmt = $pdo->prepare("DELETE FROM sos_broadcast WHERE id = :id");
        $stmt->execute([':id' => $id_sos]);
        header("Location: sos_darurat.php?msg=deleted");
        exit();
    } catch (PDOException $e) {
        $pesan_gagal = "Gagal menghapus data: " . $e->getMessage();
    }
}

// Tangkap pesan dari redirect GET
if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'updated') $pesan_sukses = "Status pengumuman SOS berhasil diubah!";
    if ($_GET['msg'] === 'approved') $pesan_sukses = "Pengajuan darah darurat berhasil disetujui dan dipublikasikan!";
    if ($_GET['msg'] === 'deleted') $pesan_sukses = "Pengumuman SOS berhasil dihapus!";
}

// ==========================================
// 4. QUERY READ: AMBIL DAFTAR BROADCAST SOS
// ==========================================
try {
    $stmt = $pdo->query("SELECT * FROM sos_broadcast ORDER BY status_aktif DESC, created_at DESC");
    $daftar_sos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $daftar_sos = [];
    $pesan_gagal = "Gagal mengambil data: " . $e->getMessage();
}
?>

<?php
$pageTitle = 'Broadcast Kebutuhan Darah Darurat - OneBlood';
require_once __DIR__ . '/../includes/header.php';
?>

<main class="inv-wrapper emergency-page">
    <div class="inv-head emergency-head">
        <div>
            <p class="emergency-eyebrow">LAYANAN PETUGAS</p>
            <h1>Broadcast Kebutuhan Darah Darurat</h1>
            <p>Kelola pengumuman stok darah kritis yang ditampilkan di halaman utama dan dashboard pendonor.</p>
        </div>
        <a class="staff-service-back" href="<?= e(base_url('petugas/dashboard.php')) ?>">Kembali ke layanan</a>
    </div>

    <?php if ($pesan_sukses !== ''): ?>
        <div class="emergency-alert success" role="status">
            <span><?= e($pesan_sukses) ?></span>
            <button type="button" aria-label="Tutup pesan" onclick="this.parentElement.remove()">&times;</button>
        </div>
    <?php endif; ?>

    <?php if ($pesan_gagal !== ''): ?>
        <div class="emergency-alert error" role="alert">
            <span><?= e($pesan_gagal) ?></span>
            <button type="button" aria-label="Tutup pesan" onclick="this.parentElement.remove()">&times;</button>
        </div>
    <?php endif; ?>

    <div class="emergency-grid">
        <section class="inv-card emergency-form-card">
            <div class="emergency-card-heading">
                <span class="emergency-icon" aria-hidden="true">!</span>
                <div>
                    <h2>Buat Pengumuman SOS</h2>
                    <p>Pastikan data kontak dapat dihubungi.</p>
                </div>
            </div>

            <form action="<?= e(base_url('petugas/sos_darurat.php')) ?>" method="POST" class="emergency-form">
                <div class="emergency-field">
                    <label for="judul-pesan">Judul Pengumuman</label>
                    <input id="judul-pesan" type="text" name="judul_pesan" maxlength="150" placeholder="Contoh: Dibutuhkan pendonor darah segera" required>
                </div>

                <div class="emergency-field">
                    <label for="gol-darah">Golongan Darah &amp; Rhesus</label>
                    <div class="emergency-blood-fields">
                        <select id="gol-darah" name="gol_darah" required>
                            <option value="">-- Gol. Darah --</option>
                            <option value="A">A</option>
                            <option value="B">B</option>
                            <option value="AB">AB</option>
                            <option value="O">O</option>
                        </select>
                        <select name="rhesus" aria-label="Rhesus" required>
                            <option value="+">Positif (+)</option>
                            <option value="-">Negatif (-)</option>
                        </select>
                    </div>
                </div>

                <div class="emergency-field">
                    <label for="rs-peminta">Rumah Sakit Peminta</label>
                    <input id="rs-peminta" type="text" name="rs_peminta" placeholder="Contoh: RSUD Dr. H. Abdul Moeloek" required>
                </div>

                <div class="emergency-field">
                    <label for="kebutuhan-kantong">Kebutuhan (Jumlah Kantong)</label>
                    <input id="kebutuhan-kantong" type="number" name="kebutuhan_kantong" min="1" placeholder="Contoh: 5" required>
                </div>

                <div class="emergency-field">
                    <label for="kontak-darurat">Kontak Darurat / Penanggung Jawab</label>
                    <input id="kontak-darurat" type="text" name="kontak_darurat" placeholder="Contoh: 0812-3456-7890 (Dr. Ahmad)" required>
                </div>

                <button type="submit" name="tambah_sos" class="emergency-submit">
                    Publikasikan SOS
                </button>
            </form>
        </section>

        <section class="inv-card emergency-list-card">
            <div class="emergency-list-heading">
                <div>
                    <h2>Daftar Broadcast SOS</h2>
                    <p>Pengumuman aktif dan riwayat kebutuhan darurat.</p>
                </div>
                <span class="emergency-total"><?= count($daftar_sos) ?> pengumuman</span>
            </div>

            <div class="inv-table-wrap emergency-table-wrap">
                <table class="emergency-table">
                    <thead>
                        <tr>
                            <th>Pengumuman</th>
                            <th>Gol. Darah</th>
                            <th>RS Peminta</th>
                            <th>Kebutuhan</th>
                            <th>Kontak</th>
                            <th>Status</th>
                            <th class="emergency-actions-heading">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($daftar_sos): ?>
                            <?php foreach ($daftar_sos as $sos): ?>
                                <tr>
                                    <td class="emergency-hospital"><?= e($sos['judul_pesan']) ?></td>
                                    <td><span class="emergency-blood-type"><?= e($sos['gol_darah'] . $sos['rhesus']) ?></span></td>
                                    <td class="emergency-hospital"><?= e($sos['rs_peminta']) ?></td>
                                    <td><?= e($sos['kebutuhan_kantong']) ?> kantong</td>
                                    <td><?= e($sos['kontak_darurat']) ?></td>
                                    <td>
                                        <span class="emergency-status <?= (int) $sos['status_aktif'] === 1 ? 'is-active' : ((int) $sos['status_aktif'] === 2 ? 'is-pending' : 'is-complete') ?>">
                                            <span aria-hidden="true"></span>
                                            <?= (int) $sos['status_aktif'] === 1 ? 'Aktif' : ((int) $sos['status_aktif'] === 2 ? 'Menunggu' : 'Selesai') ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="emergency-actions">
                                            <?php if ((int) $sos['status_aktif'] === 2): ?>
                                                <a class="emergency-action approve" href="<?= e(base_url('petugas/sos_darurat.php?action=approve&id=' . (int) $sos['id'])) ?>" title="Setujui dan publikasikan pengajuan">Setujui</a>
                                            <?php else: ?>
                                                <a class="emergency-action toggle" href="<?= e(base_url('petugas/sos_darurat.php?action=toggle_status&id=' . (int) $sos['id'])) ?>" title="Ubah status pengumuman" aria-label="Ubah status pengumuman">
                                                    <?= (int) $sos['status_aktif'] === 1 ? 'Selesai' : 'Aktifkan' ?>
                                                </a>
                                            <?php endif; ?>
                                            <a class="emergency-action delete" href="<?= e(base_url('petugas/sos_darurat.php?action=hapus&id=' . (int) $sos['id'])) ?>" onclick="return confirm('Apakah Anda yakin ingin menghapus pengumuman SOS ini?');" title="Hapus pengumuman" aria-label="Hapus pengumuman">
                                                Hapus
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="emergency-empty">Belum ada pengumuman SOS darurat yang dipublikasikan.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</main>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>