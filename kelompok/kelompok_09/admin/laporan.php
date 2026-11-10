<?php
session_start();
require_once '../config/database.php';
require_once '../config/auth_check.php';

/** @var PDO **/

cekRole(['admin', 'petugas']);

$bulan_pilihan = isset($_GET['bulan']) ? (int)$_GET['bulan'] : (int)date('m');
$tahun_pilihan = isset($_GET['tahun']) ? (int)$_GET['tahun'] : (int)date('Y');

try {
    $stmt_masuk = $pdo->prepare("SELECT COUNT(*) FROM kantong_darah WHERE MONTH(tgl_ambil) = :bulan AND YEAR(tgl_ambil) = :tahun");
    $stmt_masuk->execute([':bulan' => $bulan_pilihan, ':tahun' => $tahun_pilihan]);
    $total_masuk = $stmt_masuk->fetchColumn();

    $stmt_keluar = $pdo->prepare("SELECT COUNT(*) FROM distribusi_darah WHERE MONTH(tgl_keluar) = :bulan AND YEAR(tgl_keluar) = :tahun");
    $stmt_keluar->execute([':bulan' => $bulan_pilihan, ':tahun' => $tahun_pilihan]);
    $total_keluar = $stmt_keluar->fetchColumn();

    $stmt_stok = $pdo->query("SELECT COUNT(*) FROM kantong_darah WHERE status = 'tersedia' AND tgl_expired > CURDATE()");
    $total_tersedia = $stmt_stok->fetchColumn();

    $stmt_gol = $pdo->query("SELECT gol_darah, rhesus, COUNT(*) AS jumlah 
                             FROM kantong_darah 
                             WHERE status = 'tersedia' AND tgl_expired > CURDATE() 
                             GROUP BY gol_darah, rhesus");
    $rekap_gol = $stmt_gol->fetchAll(PDO::FETCH_ASSOC);

    $stok_per_gol = [];
    foreach ($rekap_gol as $row) {
        $stok_per_gol[$row['gol_darah'] . $row['rhesus']] = $row['jumlah'];
    }

} catch (PDOException $e) {
    $total_masuk = $total_keluar = $total_tersedia = 0;
    $stok_per_gol = [];
}

try {
    $stmt_laporan = $pdo->prepare("SELECT d.*, k.kode_kantong, k.gol_darah, k.rhesus, k.komponen, u.nama AS nama_petugas
                                   FROM distribusi_darah d
                                   JOIN kantong_darah k ON d.kantong_id = k.id
                                   LEFT JOIN users u ON d.petugas_id = u.id
                                   WHERE MONTH(d.tgl_keluar) = :bulan AND YEAR(d.tgl_keluar) = :tahun
                                   ORDER BY d.tgl_keluar DESC");
    $stmt_laporan->execute([':bulan' => $bulan_pilihan, ':tahun' => $tahun_pilihan]);
    $laporan_distribusi = $stmt_laporan->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $laporan_distribusi = [];
}

$nama_bulan = [
    1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
];
?>

<?php
$pageTitle = 'Laporan Distribusi & Stok Darah | OneBlood';
$bodyClass = 'laporan-page';
require_once __DIR__ . '/../includes/header.php';
?>
<main class="laporan-page">
    <div class="laporan-container">
        <section class="laporan-print-header" aria-label="Kop laporan">
            <h1>Laporan Distribusi &amp; Stok Darah OneBlood</h1>
            <p>Unit Donor Darah — Fakultas Kedokteran | Periode: <?= e($nama_bulan[$bulan_pilihan] ?? '') ?> <?= $tahun_pilihan ?></p>
            <p>Dicetak pada: <?= date('d/m/Y H:i:s') ?></p>
        </section>

        <section class="laporan-heading no-print">
            <div>
                <p class="laporan-eyebrow">PUSAT DATA ONEBLOOD</p>
                <h1>Laporan &amp; Analitik</h1>
                <p>Ringkasan sirkulasi kantong darah dan distribusi untuk <?= e($nama_bulan[$bulan_pilihan] ?? '') ?> <?= $tahun_pilihan ?>.</p>
            </div>
            <div class="laporan-actions">
                <form action="laporan.php" method="get" class="laporan-filter">
                    <label>
                        <span>Bulan</span>
                        <select name="bulan" aria-label="Pilih bulan laporan">
                            <?php foreach ($nama_bulan as $k => $v): ?>
                                <option value="<?= $k ?>" <?= $k === $bulan_pilihan ? 'selected' : '' ?>><?= e($v) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>
                        <span>Tahun</span>
                        <select name="tahun" aria-label="Pilih tahun laporan">
                            <?php for ($y = (int)date('Y'); $y >= (int)date('Y') - 3; $y--): ?>
                                <option value="<?= $y ?>" <?= $y === $tahun_pilihan ? 'selected' : '' ?>><?= $y ?></option>
                            <?php endfor; ?>
                        </select>
                    </label>
                    <button class="laporan-button laporan-button-secondary" type="submit">Terapkan</button>
                </form>
                <button class="laporan-button laporan-button-primary" type="button" onclick="window.print()">Cetak Laporan</button>
            </div>
        </section>

        <section class="laporan-stat-grid" aria-label="Ringkasan sirkulasi darah">
            <article class="laporan-stat laporan-stat-in">
                <span class="laporan-stat-label">Darah masuk</span>
                <strong><?= (int)$total_masuk ?></strong>
                <span class="laporan-stat-unit">kantong · periode terpilih</span>
                <span class="laporan-stat-mark" aria-hidden="true">IN</span>
            </article>
            <article class="laporan-stat laporan-stat-out">
                <span class="laporan-stat-label">Darah keluar</span>
                <strong><?= (int)$total_keluar ?></strong>
                <span class="laporan-stat-unit">kantong · periode terpilih</span>
                <span class="laporan-stat-mark" aria-hidden="true">OUT</span>
            </article>
            <article class="laporan-stat laporan-stat-stock">
                <span class="laporan-stat-label">Stok aktif tersedia</span>
                <strong><?= (int)$total_tersedia ?></strong>
                <span class="laporan-stat-unit">kantong · saat ini</span>
                <span class="laporan-stat-mark" aria-hidden="true">STOK</span>
            </article>
        </section>

        <section class="laporan-panel">
            <div class="laporan-panel-heading">
                <div>
                    <p class="laporan-eyebrow">PEMANTAUAN STOK</p>
                    <h2>Ketersediaan golongan darah</h2>
                </div>
                <span class="laporan-panel-note">Stok aktif dan belum kedaluwarsa</span>
            </div>
            <div class="laporan-golongan-grid">
                <?php
                $gol_list = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
                foreach ($gol_list as $g):
                    $jumlah = (int)($stok_per_gol[$g] ?? 0);
                    $status_class = $jumlah > 10 ? 'laporan-stock-safe' : ($jumlah > 3 ? 'laporan-stock-low' : 'laporan-stock-critical');
                    $status_text = $jumlah > 10 ? 'Aman' : ($jumlah > 3 ? 'Menipis' : 'Kritis');
                ?>
                    <article class="laporan-golongan <?= $status_class ?>">
                        <strong><?= e($g) ?></strong>
                        <span class="laporan-golongan-count"><?= $jumlah ?></span>
                        <span class="laporan-golongan-status"><?= $status_text ?></span>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="laporan-panel laporan-distribution">
            <div class="laporan-panel-heading">
                <div>
                    <p class="laporan-eyebrow">CATATAN TRANSAKSI</p>
                    <h2>Rincian distribusi darah</h2>
                </div>
                <span class="laporan-period"><?= e($nama_bulan[$bulan_pilihan] ?? '') ?> <?= $tahun_pilihan ?></span>
            </div>
            <div class="laporan-table-wrap">
                <table class="laporan-table">
                    <thead>
                        <tr>
                            <th scope="col">No</th>
                            <th scope="col">Kode kantong</th>
                            <th scope="col">Golongan</th>
                            <th scope="col">Nama pasien</th>
                            <th scope="col">RS tujuan</th>
                            <th scope="col">Dokter pengirim</th>
                            <th scope="col">Petugas</th>
                            <th scope="col">Tanggal keluar</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($laporan_distribusi): ?>
                            <?php $no = 1; foreach ($laporan_distribusi as $row): ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td><strong class="laporan-code"><?= e($row['kode_kantong']) ?></strong></td>
                                    <td><strong><?= e($row['gol_darah'] . $row['rhesus']) ?></strong> <span class="laporan-component"><?= e($row['komponen']) ?></span></td>
                                    <td><?= e($row['nama_pasien']) ?></td>
                                    <td><?= e($row['rs_tujuan']) ?></td>
                                    <td><?= e($row['dokter_pengirim']) ?></td>
                                    <td><?= e($row['nama_petugas'] ?? 'Admin') ?></td>
                                    <td><?= e(date('d/m/Y H:i', strtotime($row['tgl_keluar']))) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td class="laporan-empty" colspan="8">Belum ada transaksi distribusi pada periode ini.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="laporan-signatures" aria-label="Pengesahan laporan">
            <div>
                <p>Mengetahui,</p>
                <strong>Kepala Unit Donor Darah</strong>
                <span class="laporan-signature-space"></span>
                <strong>( ______________________ )</strong>
            </div>
            <div>
                <p>Bandar Lampung, <?= e(date('d F Y')) ?></p>
                <strong>Petugas Penanggung Jawab</strong>
                <span class="laporan-signature-space"></span>
                <strong>( <?= e($_SESSION['nama'] ?? 'Petugas OneBlood') ?> )</strong>
            </div>
        </section>
    </div>
</main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>