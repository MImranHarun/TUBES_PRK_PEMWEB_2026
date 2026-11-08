<?php

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/koneksi.php';

$judul = 'Beranda';
$statistik = ['alat' => 0, 'ruang' => 0, 'kategori' => 0];
$alatTerbaru = [];
$pesanError = null;

try {
    $statistik = db()->query(
        "SELECT
            (SELECT COUNT(*) FROM alat WHERE jenis = 'alat') AS alat,
            (SELECT COUNT(*) FROM alat WHERE jenis = 'ruang') AS ruang,
            (SELECT COUNT(*) FROM kategori) AS kategori"
    )->fetch();

    $statement = db()->prepare(
        'SELECT a.kode, a.nama, a.jenis, a.stok, a.lokasi, a.foto, k.nama AS kategori
         FROM alat a
         JOIN kategori k ON k.id = a.kategori_id
         ORDER BY a.created_at DESC, a.id DESC
         LIMIT :batas'
    );
    $statement->bindValue(':batas', 6, PDO::PARAM_INT);
    $statement->execute();
    $alatTerbaru = $statement->fetchAll();
} catch (PDOException $exception) {
    $pesanError = 'Database belum terhubung. Pastikan database labloan sudah di-import dan pengaturan di config/koneksi.php sudah benar.';
}

$ringkasan = [
    ['label' => 'Jenis Alat', 'nilai' => $statistik['alat']],
    ['label' => 'Ruang Laboratorium', 'nilai' => $statistik['ruang']],
    ['label' => 'Kategori', 'nilai' => $statistik['kategori']],
];

require __DIR__ . '/includes/header.php';
?>
<section class="hero">
    <h1>Pinjam alat dan ruang lab tanpa ribet</h1>
    <p>LabLoan membantu mahasiswa mengajukan peminjaman alat dan ruang laboratorium secara online, dan membantu admin memproses persetujuan serta pengembalian dengan rapi.</p>
<?php if (!is_logged_in()): ?>
    <a href="<?= e(url('auth/login.php')) ?>" class="btn btn-light btn-lg">Masuk</a>
    <a href="<?= e(url('alat/index.php')) ?>" class="btn btn-outline btn-lg">Lihat Daftar Alat</a>
<?php elseif (has_role('admin')): ?>
    <a href="<?= e(url('admin/dashboard.php')) ?>" class="btn btn-light btn-lg">Buka Dashboard</a>
<?php else: ?>
    <a href="<?= e(url('peminjaman/ajukan.php')) ?>" class="btn btn-light btn-lg">Ajukan Peminjaman</a>
    <a href="<?= e(url('peminjaman/riwayat.php')) ?>" class="btn btn-outline btn-lg">Riwayat Saya</a>
<?php endif; ?>
</section>

<?php if ($pesanError !== null): ?>
<div class="alert alert-warning"><?= e($pesanError) ?></div>
<?php endif; ?>

<section class="grid grid-3">
<?php foreach ($ringkasan as $item): ?>
    <div class="card stat-card">
        <div class="stat-value"><?= (int) $item['nilai'] ?></div>
        <div class="stat-label"><?= e($item['label']) ?></div>
    </div>
<?php endforeach; ?>
</section>

<section class="mt-6">
    <div class="section-title">
        <h2>Alat &amp; Ruang Terbaru</h2>
        <a href="<?= e(url('alat/index.php')) ?>" class="btn btn-outline btn-sm">Lihat Semua</a>
    </div>
<?php if ($alatTerbaru === []): ?>
    <div class="card empty-state">Belum ada data alat.</div>
<?php else: ?>
    <div class="grid grid-3">
<?php foreach ($alatTerbaru as $alat): ?>
        <article class="card item-card">
            <div class="item-photo">
<?php if (!empty($alat['foto'])): ?>
                <img src="<?= e(url('uploads/alat/' . $alat['foto'])) ?>" alt="<?= e($alat['nama']) ?>">
<?php else: ?>
                <?= e($alat['kode']) ?>
<?php endif; ?>
            </div>
            <div class="card-body">
                <div class="item-meta">
                    <span class="badge badge-info"><?= e($alat['jenis']) ?></span>
                    <span class="badge badge-neutral"><?= e($alat['kategori']) ?></span>
                </div>
                <h3><?= e($alat['nama']) ?></h3>
                <p class="text-muted mb-0">Stok <?= (int) $alat['stok'] ?> &middot; <?= e($alat['lokasi']) ?></p>
            </div>
        </article>
<?php endforeach; ?>
    </div>
<?php endif; ?>
</section>

<section class="card mt-6">
    <div class="card-header">
        <h3>Contoh Status Peminjaman</h3>
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Status</th>
                    <th>Keterangan</th>
                </tr>
            </thead>
            <tbody>
                <tr><td><span class="badge badge-menunggu">menunggu</span></td><td>Pengajuan belum diproses admin</td></tr>
                <tr><td><span class="badge badge-disetujui">disetujui</span></td><td>Alat boleh diambil sesuai jadwal</td></tr>
                <tr><td><span class="badge badge-ditolak">ditolak</span></td><td>Pengajuan tidak dapat dipenuhi</td></tr>
                <tr><td><span class="badge badge-dikembalikan">dikembalikan</span></td><td>Alat sudah diterima kembali oleh admin</td></tr>
            </tbody>
        </table>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
