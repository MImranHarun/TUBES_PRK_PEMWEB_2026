<?php
require_once __DIR__ . '/../config/auth_check.php';

cekRole(['admin']);

$pageTitle = 'Manajemen Akun Pengguna — Admin OneBlood';
$errors    = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string)($_POST['action'] ?? '');

    if ($action === 'create') {
        $nama      = trim((string)($_POST['nama'] ?? ''));
        $email     = trim((string)($_POST['email'] ?? ''));
        $password  = (string)($_POST['password'] ?? '');
        $role      = (string)($_POST['role'] ?? 'petugas');
        $no_hp     = trim((string)($_POST['no_hp'] ?? ''));
        $gol_darah = (string)($_POST['gol_darah'] ?? '');
        $rhesus    = (string)($_POST['rhesus'] ?? '');
        $alamat    = trim((string)($_POST['alamat'] ?? ''));

        if ($nama === '') {
            $errors[] = 'Nama lengkap wajib diisi.';
        }
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Format email tidak valid.';
        }
        if (strlen($password) < 6) {
            $errors[] = 'Password minimal 6 karakter.';
        }
        if (!in_array($role, ['admin', 'petugas', 'pendonor'], true)) {
            $errors[] = 'Role pengguna tidak valid.';
        }

        if (empty($errors)) {
            try {
                $stmtCek = $db->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
                $stmtCek->execute([':email' => $email]);

                if ($stmtCek->fetch()) {
                    $errors[] = 'Email "' . $email . '" sudah terdaftar di sistem.';
                } else {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $stmtIns = $db->prepare("
                        INSERT INTO users (nama, email, password, no_hp, alamat, role, gol_darah, rhesus, status_aktif)
                        VALUES (:nama, :email, :password, :no_hp, :alamat, :role, :gol_darah, :rhesus, 1)
                    ");
                    $stmtIns->execute([
                        ':nama'      => $nama,
                        ':email'     => $email,
                        ':password'  => $hash,
                        ':no_hp'     => $no_hp !== '' ? $no_hp : null,
                        ':alamat'    => $alamat !== '' ? $alamat : null,
                        ':role'      => $role,
                        ':gol_darah' => in_array($gol_darah, ['A', 'B', 'AB', 'O'], true) ? $gol_darah : null,
                        ':rhesus'    => in_array($rhesus, ['+', '-'], true) ? $rhesus : null,
                    ]);

                    setFlash('success', 'Akun pengguna "' . $nama . '" (' . strtoupper($role) . ') berhasil ditambahkan!');
                    header('Location: ' . base_url('admin/users.php'));
                    exit;
                }
            } catch (PDOException $e) {
                $errors[] = 'Gagal menambah pengguna: ' . $e->getMessage();
            }
        }
    }

    elseif ($action === 'update') {
        $id        = (int)($_POST['id'] ?? 0);
        $nama      = trim((string)($_POST['nama'] ?? ''));
        $email     = trim((string)($_POST['email'] ?? ''));
        $password  = (string)($_POST['password'] ?? '');
        $role      = (string)($_POST['role'] ?? 'pendonor');
        $no_hp     = trim((string)($_POST['no_hp'] ?? ''));
        $gol_darah = (string)($_POST['gol_darah'] ?? '');
        $rhesus    = (string)($_POST['rhesus'] ?? '');
        $alamat    = trim((string)($_POST['alamat'] ?? ''));

        if ($id <= 0 || $nama === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Data pembaruan tidak lengkap atau format email salah.';
        }

        if (empty($errors)) {
            try {
                $stmtCek = $db->prepare("SELECT id FROM users WHERE email = :email AND id != :id LIMIT 1");
                $stmtCek->execute([':email' => $email, ':id' => $id]);

                if ($stmtCek->fetch()) {
                    $errors[] = 'Email tersebut sudah digunakan oleh pengguna lain.';
                } else {
                    if ($password !== '') {
                        if (strlen($password) < 6) {
                            $errors[] = 'Password baru minimal 6 karakter.';
                        } else {
                            $hash = password_hash($password, PASSWORD_DEFAULT);
                            $stmtUpd = $db->prepare("
                                UPDATE users 
                                SET nama = :nama, email = :email, password = :password, role = :role,
                                    no_hp = :no_hp, alamat = :alamat, gol_darah = :gol_darah, rhesus = :rhesus
                                WHERE id = :id
                            ");
                            $stmtUpd->execute([
                                ':nama'      => $nama,
                                ':email'     => $email,
                                ':password'  => $hash,
                                ':role'      => $role,
                                ':no_hp'     => $no_hp !== '' ? $no_hp : null,
                                ':alamat'    => $alamat !== '' ? $alamat : null,
                                ':gol_darah' => in_array($gol_darah, ['A', 'B', 'AB', 'O'], true) ? $gol_darah : null,
                                ':rhesus'    => in_array($rhesus, ['+', '-'], true) ? $rhesus : null,
                                ':id'        => $id,
                            ]);
                        }
                    } else {
                        $stmtUpd = $db->prepare("
                            UPDATE users 
                            SET nama = :nama, email = :email, role = :role,
                                no_hp = :no_hp, alamat = :alamat, gol_darah = :gol_darah, rhesus = :rhesus
                            WHERE id = :id
                        ");
                        $stmtUpd->execute([
                            ':nama'      => $nama,
                            ':email'     => $email,
                            ':role'      => $role,
                            ':no_hp'     => $no_hp !== '' ? $no_hp : null,
                            ':alamat'    => $alamat !== '' ? $alamat : null,
                            ':gol_darah' => in_array($gol_darah, ['A', 'B', 'AB', 'O'], true) ? $gol_darah : null,
                            ':rhesus'    => in_array($rhesus, ['+', '-'], true) ? $rhesus : null,
                            ':id'        => $id,
                        ]);
                    }

                    if (empty($errors)) {
                        setFlash('success', 'Data pengguna "' . $nama . '" berhasil diperbarui!');
                        header('Location: ' . base_url('admin/users.php'));
                        exit;
                    }
                }
            } catch (PDOException $e) {
                $errors[] = 'Gagal memperbarui pengguna: ' . $e->getMessage();
            }
        }
    }

    elseif ($action === 'toggle_status') {
        $id = (int)($_POST['id'] ?? 0);
        $currentAdminId = (int)($_SESSION['user_id'] ?? 0);

        if ($id === $currentAdminId) {
            setFlash('error', 'Tidak dapat menonaktifkan akun Anda sendiri yang sedang aktif login!');
        } elseif ($id > 0) {
            try {
                $stmtToggle = $db->prepare("
                    UPDATE users 
                    SET status_aktif = IF(status_aktif = 1, 0, 1) 
                    WHERE id = :id
                ");
                $stmtToggle->execute([':id' => $id]);
                setFlash('success', 'Status aktif akun pengguna berhasil diubah.');
            } catch (PDOException $e) {
                setFlash('error', 'Gagal mengubah status akun: ' . $e->getMessage());
            }
        }
        header('Location: ' . base_url('admin/users.php'));
        exit;
    }
}

$editUser = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $stmtEdit = $db->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
    $stmtEdit->execute([':id' => (int)$_GET['edit']]);
    $editUser = $stmtEdit->fetch() ?: null;
}

$filterRole = trim((string)($_GET['role'] ?? ''));
$keyword    = trim((string)($_GET['q'] ?? ''));

$whereClauses = [];
$params       = [];

if (in_array($filterRole, ['admin', 'petugas', 'pendonor'], true)) {
    $whereClauses[] = "role = :role";
    $params[':role'] = $filterRole;
}
if ($keyword !== '') {
    $whereClauses[] = "(nama LIKE :kw OR email LIKE :kw OR no_hp LIKE :kw)";
    $params[':kw']  = '%' . $keyword . '%';
}

$whereSql = !empty($whereClauses) ? 'WHERE ' . implode(' AND ', $whereClauses) : '';

$stmtUsers = $db->prepare("SELECT * FROM users {$whereSql} ORDER BY role ASC, created_at DESC");
$stmtUsers->execute($params);
$userList = $stmtUsers->fetchAll();

// Hitung ringkasan per role
$stats = ['total' => 0, 'admin' => 0, 'petugas' => 0, 'pendonor' => 0];
try {
    $stmtCount = $db->query("SELECT role, COUNT(*) AS jml FROM users GROUP BY role");
    foreach ($stmtCount->fetchAll() as $r) {
        $roleKey = (string)$r['role'];
        $jml     = (int)$r['jml'];
        if (isset($stats[$roleKey])) {
            $stats[$roleKey] = $jml;
            $stats['total'] += $jml;
        }
    }
} catch (PDOException $e) {
    error_log('Gagal menghitung ringkasan akun pengguna: ' . $e->getMessage());
    $errors[] = 'Ringkasan pengguna sementara tidak dapat dimuat.';
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="users-page">
<main class="users-content">
    <section class="users-hero">
        <div class="users-hero-copy">
            <span class="users-eyebrow">RUANG ADMINISTRATOR · ONEBLOOD</span>
            <h1>Kelola akun,<br><span>jaga setiap akses.</span></h1>
            <p>Atur akun Administrator, Petugas Medis, dan Pendonor dalam satu tempat yang mudah dipantau.</p>
            <div class="users-hero-note">
                <span class="users-hero-mark" aria-hidden="true">+</span>
                <span>Data pengguna terorganisir, layanan donor lebih terkoordinasi.</span>
            </div>
        </div>
        <div class="users-hero-art">
            <img src="<?= e(base_url('assets/donor1.jpg')) ?>" alt="Ilustrasi kegiatan donor darah">
            <span class="users-art-label">Bersama selamatkan sesama</span>
        </div>
        <span class="users-hero-orb users-hero-orb-one" aria-hidden="true"></span>
        <span class="users-hero-orb users-hero-orb-two" aria-hidden="true"></span>
    </section>

    <div class="users-section-heading">
        <div>
            <span class="users-eyebrow">RINGKASAN</span>
            <h2>Ikhtisar pengguna</h2>
        </div>
        <span class="users-live"><span></span> Data akun</span>
    </div>

<?php if (!empty($errors)): ?>
    <div class="users-error">
        <div>
            <?php foreach ($errors as $err): ?>
                <div>&bull; <?= e($err) ?></div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<div class="users-stats">
    <div class="users-stat-card users-stat-total">
        <span class="users-stat-icon" aria-hidden="true">
            <img src="<?= e(base_url('assets/icon/user.svg')) ?>" alt="">
        </span>
        <div><small>Total seluruh akun</small><strong><?= $stats['total'] ?></strong><span>Akun terdaftar</span></div>
    </div>
    <div class="users-stat-card users-stat-admin">
        <span class="users-stat-icon" aria-hidden="true">
            <img src="<?= e(base_url('assets/icon/admin.svg')) ?>" alt="">
        </span>
        <div><small>Administrator</small><strong><?= $stats['admin'] ?></strong><span>Pengelola sistem</span></div>
    </div>
    <div class="users-stat-card users-stat-staff">
        <span class="users-stat-icon" aria-hidden="true">
            <img src="<?= e(base_url('assets/icon/medic.svg')) ?>" alt="">
        </span>
        <div><small>Petugas medis &amp; lab</small><strong><?= $stats['petugas'] ?></strong><span>Tim pelayanan</span></div>
    </div>
    <div class="users-stat-card users-stat-donor">
        <span class="users-stat-icon" aria-hidden="true">
            <img src="<?= e(base_url('assets/icon/care.svg')) ?>" alt="">
        </span>
        <div><small>Pendonor terdaftar</small><strong><?= $stats['pendonor'] ?></strong><span>Calon penyelamat</span></div>
    </div>
</div>

<div class="users-layout">

    <section class="users-panel">
        <div class="users-panel-header">
            <div class="users-panel-title">
                <span class="users-panel-icon" aria-hidden="true"><?= $editUser !== null ? '✎' : '+' ?></span>
                <div>
                    <span class="users-eyebrow"><?= $editUser !== null ? 'PEMBARUAN DATA' : 'AKUN BARU' ?></span>
                    <h2>
                <?php if ($editUser !== null): ?>
                    Edit Pengguna
                <?php else: ?>
                    Tambah pengguna
                <?php endif; ?>
                    </h2>
                </div>
            </div>
            <?php if ($editUser !== null): ?>
                <a href="<?= e(base_url('admin/users.php')) ?>" class="users-button users-button-light">Batal</a>
            <?php endif; ?>
        </div>

        <form action="<?= e(base_url('admin/users.php')) ?>" method="POST" class="users-create-form" autocomplete="off">
            <input type="hidden" name="action" value="<?= $editUser !== null ? 'update' : 'create' ?>">
            <?php if ($editUser !== null): ?>
                <input type="hidden" name="id" value="<?= (int)$editUser['id'] ?>">
            <?php endif; ?>

            <div class="users-field">
                <label for="user-nama">Nama Lengkap &amp; Gelar</label>
                <input id="user-nama" type="text" name="nama" class="users-control" placeholder="Contoh: dr. Siti Rahmawati"
                       value="<?= e($editUser['nama'] ?? '') ?>" required>
            </div>

            <div class="users-form-grid">
                <div class="users-field">
                    <label for="user-email">Alamat Email</label>
                    <input id="user-email" type="email" name="email" class="users-control" placeholder="nama@oneblood.id"
                           value="<?= e($editUser['email'] ?? '') ?>" required>
                </div>

                <div class="users-field">
                    <label for="user-role">Role Akses Sistem</label>
                    <?php $selRole = $editUser['role'] ?? 'petugas'; ?>
                    <div class="users-select" data-custom-select data-required="true">
                        <input type="hidden" name="role" value="<?= e($selRole) ?>">
                        <button id="user-role" class="users-select-trigger" type="button" aria-haspopup="menu" aria-expanded="false" aria-required="true">
                            <span class="users-select-value"><?= $selRole === 'admin' ? 'Administrator' : ($selRole === 'pendonor' ? 'Pendonor Darah' : 'Petugas Medis / PMI') ?></span>
                            <span class="users-select-chevron" aria-hidden="true">⌄</span>
                        </button>
                        <div class="users-select-menu" role="menu" aria-label="Pilihan role akses" hidden>
                            <button class="users-select-option" type="button" role="menuitemradio" aria-checked="<?= $selRole === 'petugas' ? 'true' : 'false' ?>" data-value="petugas">Petugas Medis / PMI</button>
                            <button class="users-select-option" type="button" role="menuitemradio" aria-checked="<?= $selRole === 'admin' ? 'true' : 'false' ?>" data-value="admin">Administrator</button>
                            <button class="users-select-option" type="button" role="menuitemradio" aria-checked="<?= $selRole === 'pendonor' ? 'true' : 'false' ?>" data-value="pendonor">Pendonor Darah</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="users-form-grid">
                <div class="users-field">
                    <label for="user-password">
                        Password <?= $editUser !== null ? '<small class="text-muted">(Kosongkan jika tetap)</small>' : '' ?>
                    </label>
                    <input id="user-password" type="password" name="password" class="users-control"
                           placeholder="<?= $editUser !== null ? 'Isi hanya jika ganti password' : 'Minimal 6 karakter' ?>"
                           <?= $editUser === null ? 'required' : '' ?>>
                </div>

                <div class="users-field">
                    <label for="user-phone">Nomor HP / WhatsApp</label>
                    <input id="user-phone" type="text" name="no_hp" class="users-control" placeholder="0812xxxxxxxx"
                           value="<?= e($editUser['no_hp'] ?? '') ?>">
                </div>
            </div>

            <div class="users-form-grid">
                <div class="users-field">
                    <label for="user-blood">Golongan Darah</label>
                    <?php $selGol = $editUser['gol_darah'] ?? ''; ?>
                    <div class="users-select" data-custom-select>
                        <input type="hidden" name="gol_darah" value="<?= e($selGol) ?>">
                        <button id="user-blood" class="users-select-trigger<?= $selGol === '' ? ' is-placeholder' : '' ?>" type="button" aria-haspopup="menu" aria-expanded="false">
                            <span class="users-select-value"><?= $selGol !== '' ? 'Golongan ' . e($selGol) : '-- Pilih Gol. Darah --' ?></span>
                            <span class="users-select-chevron" aria-hidden="true">⌄</span>
                        </button>
                        <div class="users-select-menu" role="menu" aria-label="Pilihan golongan darah" hidden>
                            <button class="users-select-option" type="button" role="menuitemradio" aria-checked="<?= $selGol === 'A' ? 'true' : 'false' ?>" data-value="A">Golongan A</button>
                            <button class="users-select-option" type="button" role="menuitemradio" aria-checked="<?= $selGol === 'B' ? 'true' : 'false' ?>" data-value="B">Golongan B</button>
                            <button class="users-select-option" type="button" role="menuitemradio" aria-checked="<?= $selGol === 'AB' ? 'true' : 'false' ?>" data-value="AB">Golongan AB</button>
                            <button class="users-select-option" type="button" role="menuitemradio" aria-checked="<?= $selGol === 'O' ? 'true' : 'false' ?>" data-value="O">Golongan O</button>
                        </div>
                    </div>
                </div>

                <div class="users-field">
                    <label for="user-rhesus">Rhesus</label>
                    <?php $selRh = $editUser['rhesus'] ?? ''; ?>
                    <div class="users-select" data-custom-select>
                        <input type="hidden" name="rhesus" value="<?= e($selRh) ?>">
                        <button id="user-rhesus" class="users-select-trigger<?= $selRh === '' ? ' is-placeholder' : '' ?>" type="button" aria-haspopup="menu" aria-expanded="false">
                            <span class="users-select-value"><?= $selRh !== '' ? ($selRh === '+' ? 'Positif (+)' : 'Negatif (-)') : '-- Pilih Rhesus --' ?></span>
                            <span class="users-select-chevron" aria-hidden="true">⌄</span>
                        </button>
                        <div class="users-select-menu" role="menu" aria-label="Pilihan rhesus" hidden>
                            <button class="users-select-option" type="button" role="menuitemradio" aria-checked="<?= $selRh === '+' ? 'true' : 'false' ?>" data-value="+">Positif (+)</button>
                            <button class="users-select-option" type="button" role="menuitemradio" aria-checked="<?= $selRh === '-' ? 'true' : 'false' ?>" data-value="-">Negatif (-)</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="users-field">
                <label for="user-address">Unit Kerja / Alamat Domisili</label>
                <input id="user-address" type="text" name="alamat" class="users-control" placeholder="Poliklinik FK Unila / Alamat Rumah"
                       value="<?= e($editUser['alamat'] ?? '') ?>">
            </div>

            <button type="submit" class="users-button users-button-primary users-submit">
                <?= $editUser !== null ? 'Simpan Perubahan Data' : 'Tambahkan Akun Pengguna' ?>
            </button>
        </form>
    </section>

    <section class="users-panel users-directory">
        <div class="users-panel-header">
            <div class="users-panel-title">
                <span class="users-panel-icon" aria-hidden="true">≡</span>
                <div>
                    <span class="users-eyebrow">DIREKTORI ONEBLOOD</span>
                    <h2>Daftar pengguna</h2>
                </div>
            </div>
            <span class="users-result-count"><?= count($userList) ?> data</span>
        </div>

        <!-- Bar Pencarian & Filter Role -->
        <form action="<?= e(base_url('admin/users.php')) ?>" method="GET" class="users-filters">
            <label class="users-search">
                <span class="users-search-icon" aria-hidden="true">⌕</span>
                <input type="text" name="q" class="users-control"
                   placeholder="Cari nama, email, atau no HP..." value="<?= e($keyword) ?>"
                   aria-label="Cari nama, email, atau nomor HP">
            </label>
            <div class="users-select users-role-filter" data-custom-select>
                <input type="hidden" name="role" value="<?= e($filterRole) ?>">
                <button id="filter-role" class="users-select-trigger" type="button" aria-haspopup="menu" aria-expanded="false" aria-label="Filter berdasarkan role">
                    <span class="users-select-value"><?= $filterRole === 'admin' ? 'Admin' : ($filterRole === 'petugas' ? 'Petugas Medis' : ($filterRole === 'pendonor' ? 'Pendonor' : 'Semua Role')) ?></span>
                    <span class="users-select-chevron" aria-hidden="true">⌄</span>
                </button>
                <div class="users-select-menu" role="menu" aria-label="Filter berdasarkan role" hidden>
                    <button class="users-select-option" type="button" role="menuitemradio" aria-checked="<?= $filterRole === '' ? 'true' : 'false' ?>" data-value="">Semua Role</button>
                    <button class="users-select-option" type="button" role="menuitemradio" aria-checked="<?= $filterRole === 'admin' ? 'true' : 'false' ?>" data-value="admin">Admin</button>
                    <button class="users-select-option" type="button" role="menuitemradio" aria-checked="<?= $filterRole === 'petugas' ? 'true' : 'false' ?>" data-value="petugas">Petugas Medis</button>
                    <button class="users-select-option" type="button" role="menuitemradio" aria-checked="<?= $filterRole === 'pendonor' ? 'true' : 'false' ?>" data-value="pendonor">Pendonor</button>
                </div>
            </div>
            <button type="submit" class="users-button users-button-primary">Cari</button>
            <?php if ($keyword !== '' || $filterRole !== ''): ?>
                <a href="<?= e(base_url('admin/users.php')) ?>" class="users-button users-button-light">Reset</a>
            <?php endif; ?>
        </form>

        <div class="table-responsive">
            <table class="users-table">
                <thead>
                    <tr>
                        <th scope="col">Pengguna</th>
                        <th scope="col">Role</th>
                        <th scope="col">Darah</th>
                        <th scope="col">Status</th>
                        <th scope="col">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($userList)): ?>
                        <tr>
                            <td colspan="5" style="text-align:center; padding:1.5rem;" class="text-muted">
                                Tidak ada data pengguna yang cocok dengan pencarian.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($userList as $u): ?>
                            <tr>
                                <td>
                                    <strong><?= e($u['nama']) ?></strong><br>
                                    <small class="text-muted"><?= e($u['email']) ?></small>
                                    <?php if (!empty($u['no_hp'])): ?>
                                        <br><small class="text-muted"><i class="fa-solid fa-phone"></i> <?= e($u['no_hp']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge badge-role"><?= e(strtoupper($u['role'])) ?></span>
                                </td>
                                <td>
                                    <?php if (!empty($u['gol_darah'])): ?>
                                        <span class="badge badge-blood"><?= e($u['gol_darah'] . $u['rhesus']) ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ((int)$u['status_aktif'] === 1): ?>
                                        <span class="badge badge-aman">Aktif</span>
                                    <?php else: ?>
                                        <span class="badge badge-kritis">Nonaktif</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div style="display:flex; gap:0.35rem; flex-wrap:wrap;">
                                        <a href="<?= base_url('admin/users.php?edit=' . (int)$u['id']) ?>"
                                           class="users-button users-button-light users-action" title="Edit Data">
                                            Edit
                                        </a>

                                        <?php if ((int)$u['id'] !== (int)($_SESSION['user_id'] ?? 0)): ?>
                                            <form action="<?= e(base_url('admin/users.php')) ?>" method="POST" class="users-inline-form">
                                                <input type="hidden" name="action" value="toggle_status">
                                                <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                                                <?php if ((int)$u['status_aktif'] === 1): ?>
                                                    <button type="submit" class="users-button users-button-danger users-action" title="Nonaktifkan Akun">
                                                        Nonaktifkan
                                                    </button>
                                                <?php else: ?>
                                                    <button type="submit" class="users-button users-button-primary users-action" title="Aktifkan Kembali">
                                                        Aktifkan
                                                    </button>
                                                <?php endif; ?>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

</div>

<script>
document.querySelectorAll('.users-page [data-custom-select]').forEach((select) => {
    const trigger = select.querySelector('.users-select-trigger');
    const menu = select.querySelector('.users-select-menu');
    const valueInput = select.querySelector('input[type="hidden"]');
    const valueLabel = select.querySelector('.users-select-value');
    const options = Array.from(select.querySelectorAll('.users-select-option'));

    const closeMenu = (restoreFocus = false) => {
        menu.hidden = true;
        select.classList.remove('is-open');
        trigger.setAttribute('aria-expanded', 'false');
        if (restoreFocus) trigger.focus();
    };

    const openMenu = (optionIndex = 0) => {
        document.querySelectorAll('.users-page [data-custom-select]').forEach((item) => {
            if (item !== select) {
                item.querySelector('.users-select-menu').hidden = true;
                item.classList.remove('is-open');
                item.querySelector('.users-select-trigger').setAttribute('aria-expanded', 'false');
            }
        });
        menu.hidden = false;
        select.classList.add('is-open');
        trigger.setAttribute('aria-expanded', 'true');
        options[Math.max(0, Math.min(optionIndex, options.length - 1))].focus();
    };

    const chooseOption = (option) => {
        valueInput.value = option.dataset.value;
        valueLabel.textContent = option.textContent.trim();
        options.forEach((item) => item.setAttribute('aria-checked', String(item === option)));
        trigger.classList.remove('is-placeholder');
        trigger.removeAttribute('aria-invalid');
        closeMenu(true);
    };

    trigger.addEventListener('click', () => {
        if (menu.hidden) {
            const selectedIndex = options.findIndex((option) => option.dataset.value === valueInput.value);
            openMenu(selectedIndex >= 0 ? selectedIndex : 0);
        } else {
            closeMenu();
        }
    });

    trigger.addEventListener('keydown', (event) => {
        if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
            event.preventDefault();
            const selectedIndex = options.findIndex((option) => option.dataset.value === valueInput.value);
            openMenu(selectedIndex >= 0 ? selectedIndex : 0);
        }
    });

    options.forEach((option, index) => {
        option.addEventListener('click', () => chooseOption(option));
        option.addEventListener('keydown', (event) => {
            let nextIndex = index;
            if (event.key === 'ArrowDown') nextIndex = (index + 1) % options.length;
            else if (event.key === 'ArrowUp') nextIndex = (index - 1 + options.length) % options.length;
            else if (event.key === 'Home') nextIndex = 0;
            else if (event.key === 'End') nextIndex = options.length - 1;
            else if (event.key === 'Escape') {
                event.preventDefault();
                closeMenu(true);
                return;
            } else if (event.key === 'Tab') {
                closeMenu();
                return;
            } else if (event.key !== 'Enter' && event.key !== ' ') {
                return;
            }

            event.preventDefault();
            if (event.key === 'Enter' || event.key === ' ') chooseOption(option);
            else options[nextIndex].focus();
        });
    });
});

document.addEventListener('click', (event) => {
    if (event.target.closest('.users-page [data-custom-select]')) return;
    document.querySelectorAll('.users-page [data-custom-select]').forEach((select) => {
        const menu = select.querySelector('.users-select-menu');
        if (!menu.hidden) {
            menu.hidden = true;
            select.classList.remove('is-open');
            select.querySelector('.users-select-trigger').setAttribute('aria-expanded', 'false');
        }
    });
});

document.querySelector('.users-create-form')?.addEventListener('submit', (event) => {
    const incomplete = Array.from(event.currentTarget.querySelectorAll('[data-required]')).find((select) => {
        return select.querySelector('input[type="hidden"]').value === '';
    });

    if (incomplete) {
        event.preventDefault();
        const trigger = incomplete.querySelector('.users-select-trigger');
        trigger.setAttribute('aria-invalid', 'true');
        trigger.focus();
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>