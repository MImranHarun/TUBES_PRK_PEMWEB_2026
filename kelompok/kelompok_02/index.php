<?php
require_once __DIR__ . '/includes/bootstrap.php';

$pageTitle = 'Katalog Peralatan Laboratorium';
$activeNav = 'home';

$search = trim((string) ($_GET['q'] ?? ''));
$categoryId = max(0, (int) ($_GET['category'] ?? 0));

$categories = $pdo->query('SELECT id, name FROM categories ORDER BY name ASC')->fetchAll();

$sql = "SELECT e.*, c.name AS category_name
        FROM equipment e
        INNER JOIN categories c ON c.id = e.category_id
        WHERE 1=1";
$params = [];

if ($search !== '') {
    $term = '%' . $search . '%';
    $sql .= " AND (
        e.equipment_name LIKE :q_name
        OR e.equipment_code LIKE :q_code
        OR e.laboratory_name LIKE :q_lab
        OR e.brand LIKE :q_brand
        OR e.model LIKE :q_model
    )";
    $params['q_name'] = $term;
    $params['q_code'] = $term;
    $params['q_lab'] = $term;
    $params['q_brand'] = $term;
    $params['q_model'] = $term;
}

if ($categoryId > 0) {
    $sql .= " AND e.category_id = :category_id";
    $params['category_id'] = $categoryId;
}

$sql .= ' ORDER BY e.equipment_name ASC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$equipmentList = $stmt->fetchAll();

require __DIR__ . '/includes/header.php';
?>
<section class="hero">
    <div class="container hero-inner">
        <div class="hero-copy">
            <span class="eyebrow">FAKULTAS MATEMATIKA DAN ILMU PENGETAHUAN ALAM</span>
            <h1>Katalog Peralatan<br>Laboratorium<br>Fakultas MIPA</h1>
            <p>Jelajahi dan temukan informasi lengkap mengenai peralatan laboratorium di lingkungan Fakultas MIPA melalui katalog digital yang modern, informatif, dan mudah diakses.</p>
        </div>
    </div>
</section>

<section class="catalog-section container" id="katalog">
    <div class="section-heading">
        <div>
            <h2><span class="heading-accent"></span>Daftar Alat Laboratorium</h2>
        </div>
    </div>

    <form class="catalog-toolbar" id="catalog-filter-form" method="get" action="<?= url('index.php') ?>#katalog">
        <label class="search-box">
            <span>⌕</span>
            <input id="catalog-search" type="search" name="q" value="<?= e($search) ?>" placeholder="Cari nama atau kode alat..." autocomplete="off">
            <button class="search-submit" type="submit" aria-label="Cari">⌕</button>
        </label>

        <label class="filter-box">
            <span class="sr-only">Filter kategori</span>
            <select id="catalog-category" name="category">
                <option value="0">Semua Kategori</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?= (int) $category['id'] ?>" <?= $categoryId === (int) $category['id'] ? 'selected' : '' ?>><?= e($category['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <span>⌄</span>
        </label>

        <?php if ($search !== '' || $categoryId > 0): ?>
            <a class="btn btn-outline filter-reset" href="<?= url('index.php#katalog') ?>">Reset</a>
        <?php endif; ?>
    </form>

    <div class="equipment-grid" id="equipment-grid">
        <?php foreach ($equipmentList as $item): ?>
            <?php
                $searchIndex = implode(' ', [
                    $item['equipment_name'],
                    $item['equipment_code'],
                    $item['laboratory_name'],
                    $item['brand'] ?? '',
                    $item['model'] ?? '',
                    $item['category_name'] ?? '',
                ]);
            ?>
            <article class="equipment-card"
                     data-search="<?= e(mb_strtolower($searchIndex, 'UTF-8')) ?>"
                     data-category="<?= (int) $item['category_id'] ?>">
                <a class="equipment-image equipment-image-placeholder" href="<?= url('detail.php?id=' . (int) $item['id']) ?>" aria-label="Lihat detail <?= e($item['equipment_name']) ?>">
                    <span class="placeholder-code">LABCAT / EQUIPMENT</span>
                    <svg viewBox="0 0 48 48" aria-hidden="true"><rect x="8" y="8" width="32" height="32" rx="7"/><path d="M18 17h12M18 24h12M18 31h7"/></svg>
                    <span class="placeholder-caption">Foto alat belum ditambahkan</span>
                </a>
                <div class="equipment-card-body">
                    <div class="card-meta"><span class="category-dot"></span><?= e($item['category_name']) ?></div>
                    <h3><a href="<?= url('detail.php?id=' . (int) $item['id']) ?>"><?= e($item['equipment_name']) ?></a></h3>
                    <p class="equipment-location">⌖ <?= e($item['laboratory_name']) ?></p>
                    <div class="card-bottom">
                        <a class="detail-link" href="<?= url('detail.php?id=' . (int) $item['id']) ?>">Lihat Detail <span>→</span></a>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    </div>

    <div class="empty-state" id="catalog-empty" <?= $equipmentList ? 'hidden' : '' ?>>
        <div class="empty-icon">⌕</div>
        <h3><?= $equipmentList ? 'Alat tidak ditemukan' : 'Katalog belum memiliki data' ?></h3>
        <p><?= $equipmentList ? 'Coba gunakan kata kunci lain atau pilih kategori yang berbeda.' : 'Data peralatan laboratorium akan ditampilkan setelah admin menambahkannya.' ?></p>
        <?php if ($search !== '' || $categoryId > 0): ?>
            <a class="btn btn-outline" href="<?= url('index.php#katalog') ?>">Tampilkan Semua Alat</a>
        <?php endif; ?>
    </div>

    <p class="catalog-footnote">Informasi katalog ini digunakan sebagai referensi pengenalan peralatan laboratorium.</p>
</section>

<section class="about-band" id="tentang">
    <div class="container about-inner">
        <div class="about-symbol" aria-hidden="true"><svg viewBox="0 0 48 48"><rect x="7" y="7" width="34" height="34" rx="9"/><path d="M16 17h16M16 24h16M16 31h10"/></svg></div>
        <div>
            <span class="eyebrow eyebrow-blue">TENTANG LABCAT</span>
            <h2>Informasi alat laboratorium dalam satu tempat.</h2>
            <p>LABCAT merupakan sistem informasi katalog digital yang menyajikan identitas, dokumentasi, dan spesifikasi peralatan laboratorium Fakultas MIPA. Pengunjung dapat mengakses informasi alat tanpa harus login.</p>
        </div>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
