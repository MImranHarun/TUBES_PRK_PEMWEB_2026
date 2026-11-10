<?php
require_once __DIR__ . '/includes/bootstrap.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id || $id < 1) {
    http_response_code(404);
    exit('Data alat tidak ditemukan.');
}

$stmt = $pdo->prepare(
    'SELECT e.*, c.name AS category_name
     FROM equipment e
     INNER JOIN categories c ON c.id = e.category_id
     WHERE e.id = :id LIMIT 1'
);
$stmt->execute(['id' => $id]);
$item = $stmt->fetch();

if (!$item) {
    http_response_code(404);
    exit('Data alat tidak ditemukan.');
}

$galleryStmt = $pdo->prepare(
    'SELECT id, image_path
     FROM equipment_images
     WHERE equipment_id = :equipment_id
     ORDER BY sort_order ASC, id ASC'
);
$galleryStmt->execute(['equipment_id' => $id]);
$galleryImages = $galleryStmt->fetchAll();

if (!empty($item['image'])) {
    $coverExists = false;
    foreach ($galleryImages as $galleryImage) {
        if ($galleryImage['image_path'] === $item['image']) {
            $coverExists = true;
            break;
        }
    }
    if (!$coverExists) {
        array_unshift($galleryImages, ['id' => 0, 'image_path' => $item['image']]);
    }
}

$mainImagePath = $item['image'] ?: ($galleryImages[0]['image_path'] ?? null);
$pageTitle = $item['equipment_name'];
$activeNav = 'catalog';
require __DIR__ . '/includes/header.php';
?>
<section class="container detail-page">
    <div class="breadcrumb"><a href="<?= url('index.php') ?>">Beranda</a><span>›</span><a href="<?= url('index.php#katalog') ?>">Katalog</a><span>›</span><strong>Detail Alat</strong></div>
    <a class="back-link" href="<?= url('index.php#katalog') ?>">← Kembali ke Katalog</a>

    <div class="detail-layout">
        <div class="detail-visual">
            <div class="detail-image detail-image-placeholder">
                <?php if ($mainImagePath): ?>
                    <img id="equipment-main-image" src="<?= e(image_url($mainImagePath)) ?>" alt="Foto utama <?= e($item['equipment_name']) ?>">
                <?php else: ?>
                    <div class="photo-empty-state"><svg viewBox="0 0 48 48" aria-hidden="true"><rect x="6" y="9" width="36" height="30" rx="5"/><circle cx="18" cy="19" r="3"/><path d="m9 34 10-9 7 6 5-5 8 8"/></svg><strong>Foto belum tersedia</strong><span>Admin dapat menambahkan foto alat melalui halaman kelola datasheet.</span></div>
                <?php endif; ?>
            </div>
            <?php if (count($galleryImages) > 1): ?>
                <div class="detail-gallery" aria-label="Galeri foto <?= e($item['equipment_name']) ?>">
                    <?php foreach ($galleryImages as $index => $galleryImage): ?>
                        <button
                            class="detail-gallery-thumb <?= $galleryImage['image_path'] === $mainImagePath ? 'active' : '' ?>"
                            type="button"
                            data-gallery-thumb
                            data-gallery-src="<?= e(image_url($galleryImage['image_path'])) ?>"
                            data-gallery-alt="<?= e('Foto ' . $item['equipment_name'] . ' ' . ($index + 1)) ?>"
                            aria-label="Tampilkan foto <?= $index + 1 ?>"
                            aria-pressed="<?= $galleryImage['image_path'] === $mainImagePath ? 'true' : 'false' ?>">
                            <img src="<?= e(image_url($galleryImage['image_path'])) ?>" alt="">
                        </button>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <div class="image-caption"><span>LABCAT EQUIPMENT DATASHEET</span><strong><?= e($item['equipment_code']) ?></strong></div>
        </div>

        <div class="detail-content">
            <div class="detail-title-row">
                <div>
                    <span class="eyebrow eyebrow-blue"><?= e($item['category_name']) ?></span>
                    <h1><?= e($item['equipment_name']) ?></h1>
                </div>
            </div>

            <div class="info-panel">
                <h2>Informasi Alat</h2>
                <dl class="info-list">
                    <div><dt>Kode Inventaris</dt><dd><?= e($item['equipment_code']) ?></dd></div>
                    <div><dt>Kategori</dt><dd><?= e($item['category_name']) ?></dd></div>
                    <div><dt>Laboratorium</dt><dd><?= e($item['laboratory_name']) ?></dd></div>
                    <div><dt>Merek</dt><dd><?= e($item['brand'] ?: '-') ?></dd></div>
                    <div><dt>Tipe / Model</dt><dd><?= e($item['model'] ?: '-') ?></dd></div>
                </dl>
            </div>

            <div class="info-panel">
                <h2><span class="heading-icon">i</span> Deskripsi dan Fungsi</h2>
                <p class="detail-paragraph"><?= nl2br(e($item['description'] ?: 'Belum ada deskripsi untuk alat ini.')) ?></p>
            </div>

            <div class="info-panel datasheet-panel">
                <h2><span class="heading-icon">{ }</span> Spesifikasi Teknis</h2>
                <p class="detail-paragraph"><?= nl2br(e($item['specifications'] ?: 'Spesifikasi teknis belum dicantumkan.')) ?></p>
            </div>
            <p class="detail-disclaimer">Halaman ini menyajikan informasi teknis dan identitas alat sebagai referensi katalog laboratorium Fakultas MIPA.</p>
        </div>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
