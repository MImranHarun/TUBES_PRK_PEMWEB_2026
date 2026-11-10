<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth_check.php';

/** @var PDO $pdo */

// Proteksi Halaman: Hanya Petugas Medis dan Admin yang bisa mengakses
cekRole(['petugas', 'admin']);

$pesan_sukses = "";
$pesan_gagal  = "";

// ==========================================
// 1. PROSES FORM: PROSES DISTRIBUSI & POTONG STOK (TRANSAKSI PDO)
// ==========================================
if (isset($_POST['proses_distribusi'])) {
    $kantong_id      = (int)$_POST['kantong_id'];
    $nama_pasien     = trim($_POST['nama_pasien']);
    $rs_tujuan       = trim($_POST['rs_tujuan']);
    $dokter_pengirim = trim($_POST['dokter_pengirim']);
    $petugas_id      = $_SESSION['user_id'] ?? 1; // Mengambil ID Petugas yang sedang login

    if ($kantong_id > 0 && !empty($nama_pasien) && !empty($rs_tujuan) && !empty($dokter_pengirim)) {
        try {
            // Mulai Database Transaction agar data tersimpan konsisten
            $pdo->beginTransaction();

            // Cek ketersediaan kantong darah terlebih dahulu
            $stmt_check = $pdo->prepare("SELECT id, status FROM kantong_darah WHERE id = :id AND status = 'tersedia' FOR UPDATE");
            $stmt_check->execute([':id' => $kantong_id]);
            $kantong = $stmt_check->fetch();

            if (!$kantong) {
                throw new Exception("Kantong darah yang dipilih tidak tersedia atau sudah distibusikan!");
            }

            // A. Insert data ke tabel distribusi_darah
            $stmt_insert = $pdo->prepare("INSERT INTO distribusi_darah (kantong_id, petugas_id, nama_pasien, rs_tujuan, dokter_pengirim, tgl_keluar) 
                                         VALUES (:kantong_id, :petugas_id, :nama_pasien, :rs_tujuan, :dokter_pengirim, NOW())");
            $stmt_insert->execute([
                ':kantong_id'      => $kantong_id,
                ':petugas_id'      => $petugas_id,
                ':nama_pasien'     => $nama_pasien,
                ':rs_tujuan'       => $rs_tujuan,
                ':dokter_pengirim' => $dokter_pengirim
            ]);

            // B. Update status kantong_darah milik Anggota 4 menjadi 'keluar' (Potong Stok)
            $stmt_update = $pdo->prepare("UPDATE kantong_darah SET status = 'keluar' WHERE id = :id");
            $stmt_update->execute([':id' => $kantong_id]);

            // Commit Transaksi jika semua query berhasil
            $pdo->commit();
            $pesan_sukses = "Pengeluaran darah berhasil diproses dan stok kantong otomatis diperbarui!";

        } catch (Exception $e) {
            // Rollback jika ada kegagalan
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $pesan_gagal = "Gagal memproses distribusi: " . $e->getMessage();
        }
    } else {
        $pesan_gagal = "Mohon lengkapi semua data formulir distribusi!";
    }
}

// ==========================================
// 2. QUERY READ: AMBIL KANTONG DARAH YANG TERSEDIA
// ==========================================
try {
    // Hanya mengambil kantong yang statusnya 'tersedia' dan belum kedaluwarsa
    $stmt_stok = $pdo->query("SELECT id, kode_kantong, gol_darah, rhesus, komponen, tgl_expired 
                             FROM kantong_darah 
                             WHERE status = 'tersedia' AND tgl_expired > CURDATE() 
                             ORDER BY tgl_expired ASC");
    $kantong_tersedia = $stmt_stok->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $kantong_tersedia = [];
}

// ==========================================
// 3. QUERY READ: AMBIL RIWAYAT DISTRIBUSI DARAH
// ==========================================
try {
    $query_riwayat = "SELECT d.*, k.kode_kantong, k.gol_darah, k.rhesus, k.komponen, u.nama AS nama_petugas
                      FROM distribusi_darah d
                      JOIN kantong_darah k ON d.kantong_id = k.id
                      LEFT JOIN users u ON d.petugas_id = u.id
                      ORDER BY d.tgl_keluar DESC";
    $stmt_riwayat = $pdo->query($query_riwayat);
    $riwayat_distribusi = $stmt_riwayat->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $riwayat_distribusi = [];
}
?>

<?php
$pageTitle = 'Distribusi & Pengeluaran Darah - OneBlood';
require_once __DIR__ . '/../includes/header.php';
?>

<main class="inv-wrapper distribution-page">
    <div class="inv-head distribution-head">
        <div>
            <p class="distribution-eyebrow">LAYANAN PETUGAS</p>
            <h1>Distribusi &amp; Pengeluaran Darah</h1>
            <p>Catat darah keluar untuk rumah sakit dan pasien. Stok kantong akan diperbarui otomatis.</p>
        </div>
        <a class="staff-service-back" href="<?= e(base_url('petugas/dashboard.php')) ?>">Kembali ke layanan</a>
    </div>

    <?php if ($pesan_sukses !== ''): ?>
        <div class="distribution-alert success" role="status">
            <span><?= e($pesan_sukses) ?></span>
            <button type="button" aria-label="Tutup pesan" onclick="this.parentElement.remove()">&times;</button>
        </div>
    <?php endif; ?>

    <?php if ($pesan_gagal !== ''): ?>
        <div class="distribution-alert error" role="alert">
            <span><?= e($pesan_gagal) ?></span>
            <button type="button" aria-label="Tutup pesan" onclick="this.parentElement.remove()">&times;</button>
        </div>
    <?php endif; ?>

    <div class="distribution-grid">
        <section class="inv-card distribution-form-card">
            <div class="distribution-card-heading">
                <span class="distribution-card-icon" aria-hidden="true">&rarr;</span>
                <div>
                    <h2>Form Darah Keluar</h2>
                    <p>Isi data penerima dan pilih kantong yang tersedia.</p>
                </div>
            </div>

            <form action="<?= e(base_url('petugas/distribusi.php')) ?>" method="POST" class="distribution-form">
                <div class="distribution-field">
                    <label for="kantong-id">Pilih Kantong Darah (Tersedia)</label>
                    <select id="kantong-id" name="kantong_id" required>
                        <option value="">-- Pilih Kantong Darah --</option>
                        <?php foreach ($kantong_tersedia as $k): ?>
                            <option value="<?= (int) $k['id'] ?>">
                                [<?= e($k['kode_kantong']) ?>] Gol: <?= e($k['gol_darah'] . $k['rhesus']) ?> (<?= e($k['komponen']) ?>) - Exp: <?= e(tgl_indo($k['tgl_expired'])) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (count($kantong_tersedia) === 0): ?>
                        <p class="distribution-empty-stock">Tidak ada kantong darah yang tersedia di gudang saat ini.</p>
                    <?php endif; ?>
                </div>

                <div class="distribution-field">
                    <label for="nama-pasien">Nama Pasien Penerima</label>
                    <input id="nama-pasien" type="text" name="nama_pasien" placeholder="Contoh: Bpk. Supriadi" required>
                </div>

                <div class="distribution-field">
                    <label for="rs-tujuan">Rumah Sakit Tujuan</label>
                    <input id="rs-tujuan" type="text" name="rs_tujuan" placeholder="Contoh: RS Advent Bandar Lampung" required>
                </div>

                <div class="distribution-field">
                    <label for="dokter-pengirim">Dokter Penanggung Jawab / Pengirim</label>
                    <input id="dokter-pengirim" type="text" name="dokter_pengirim" placeholder="Contoh: dr. Rizky, Sp.PD" required>
                </div>

                <button type="submit" name="proses_distribusi" class="distribution-submit" <?= count($kantong_tersedia) === 0 ? 'disabled' : '' ?>>
                    Diproses &amp; Keluarkan Darah
                </button>
            </form>
        </section>

        <section class="inv-card distribution-history-card">
            <div class="distribution-history-heading">
                <div>
                    <h2>Riwayat Darah Keluar</h2>
                    <p>Daftar transaksi distribusi yang telah diproses.</p>
                </div>
                <span class="distribution-total"><?= count($riwayat_distribusi) ?> transaksi</span>
            </div>

            <div class="distribution-table-wrap">
                <table class="distribution-table">
                    <thead>
                        <tr>
                            <th>Kode Kantong</th>
                            <th>Gol. Darah</th>
                            <th>Nama Pasien</th>
                            <th>RS Tujuan</th>
                            <th>Dokter</th>
                            <th>Tanggal Keluar</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($riwayat_distribusi): ?>
                            <?php foreach ($riwayat_distribusi as $row): ?>
                                <tr>
                                    <td class="distribution-code"><?= e($row['kode_kantong']) ?></td>
                                    <td>
                                        <span class="distribution-blood-type"><?= e($row['gol_darah'] . $row['rhesus']) ?></span>
                                        <span class="distribution-component"><?= e($row['komponen']) ?></span>
                                    </td>
                                    <td class="distribution-patient"><?= e($row['nama_pasien']) ?></td>
                                    <td><?= e($row['rs_tujuan']) ?></td>
                                    <td><?= e($row['dokter_pengirim']) ?></td>
                                    <td><?= e(date('d/m/Y H:i', strtotime($row['tgl_keluar']))) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="distribution-empty">Belum ada transaksi distribusi darah keluar.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</main>

<?php
$hideSidebar = true;
require_once __DIR__ . '/../includes/footer.php';
?>