<?php
require __DIR__ . '/includes/auth.php';
requireLogin();
$menus = ['beranda' => 'Beranda', 'kelas' => 'Data kelas', 'siswa' => 'Data siswa',
    'mapel' => 'Mata pelajaran', 'periode' => 'Periode penilaian', 'penugasan' => 'Penugasan guru', 'klasifikasi' => 'Klasifikasi siswa', 'laporan' => 'Rekap & laporan', 'profil' => 'Profil saya'];
$page = $_GET['halaman'] ?? 'beranda';
if (!isset($menus[$page])) { http_response_code(404); exit('Halaman tidak ditemukan.'); }
?>
<!doctype html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="<?= e($_SESSION['csrf']) ?>">
        <title><?= e($menus[$page]) ?> • Arunika</title>
        <link rel="stylesheet" href="css/style.css">
        <?php if ($page === 'klasifikasi'): ?>
            <script src="js/klasifikasi.js" defer></script>
        <?php elseif ($page === 'laporan'): ?>
            <!-- Dikerjakan oleh: Jehan Reza Pahlevi (NPM. 2415061067) -->
            <script src="js/kalkulasi.js" defer></script>
        <?php else: ?>
            <script src="js/app.js" defer></script>
        <?php endif; ?>
    </head>
    <body data-page="<?= e($page) ?>" data-role="<?= e($_SESSION['guru']['peran']) ?>" data-guru="<?= e($_SESSION['guru']['id_guru']) ?>">
        <aside class="sidebar">
            <a class="brand" href="index.php">
                <span class="logo">A</span> arunika<span class="dot">.</span>
            </a>
            <p class="nav-label">RUANG AKADEMIK</p>
            <nav aria-label="Menu utama"><?php $n=0; foreach ($menus as $key => $label): ?><a href="index.php?halaman=<?= e($key) ?>" class="<?= $page === $key ? 'active' : '' ?>" <?= $page === $key ? 'aria-current="page"' : '' ?>>
                    <span class="nav-number"><?= str_pad(++$n, 2, '0', STR_PAD_LEFT) ?></span><?= e($label) ?></a><?php endforeach; ?></nav>
            <div class="sidebar-note">
                <strong>Belajar, tumbuh, bersama.</strong>
                <p>Data yang tertata membantu langkah belajar yang lebih terarah.</p>
            </div>
            <form action="logout.php" method="post">
                <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
                <button class="logout" type="submit">Keluar dari akun ↗</button>
            </form>
        </aside>
        <div class="workspace">
            <header class="topbar">
                <span>Akademik <span class="muted">/ <?= e($menus[$page]) ?></span>
                </span>
                <a class="account" href="index.php?halaman=profil">
                    <span class="avatar"><?= e(strtoupper(substr($_SESSION['guru']['nama_guru'], 0, 1))) ?></span>
                    <span><?= e($_SESSION['guru']['nama_guru']) ?><small><?= isAdmin() ? 'Administrator' : 'Guru' ?></small>
                    </span>
                </a>
            </header>
            <main>
                <div id="notice" role="status" aria-live="polite" hidden>
                </div>
                <?php if ($page === 'beranda'): ?>
                    <section class="hero">
                        <div>
                            <p class="eyebrow">SELAMAT DATANG DI ARUNIKA</p>
                            <h1>Ruang kelas tertata,<br>potensi siswa terjaga.</h1>
                            <p>Halo, <?= e($_SESSION['guru']['nama_guru']) ?>. Siapkan data akademik<br class="desktop"> dan dampingi perjalanan belajar siswa Anda.</p>
                            <a class="light-button" href="index.php?halaman=kelas">Kelola kelas saya <span>↗</span>
                            </a>
                        </div>
                        <div class="hero-art" aria-hidden="true">
                            <div class="sun">✦</div>
                            <div class="book">
                                <span>ARUNIKA</span>
                                <b>Ilmu untuk<br>bertumbuh.</b>
                                <i>────────</i>
                            </div>
                            <div class="art-tag">Setiap siswa punya potensi.</div>
                        </div>
                    </section>
                    <section class="stats" aria-label="Ringkasan data">
                        <article>
                            <span>Kelas dalam akses</span>
                            <strong id="count-kelas">—</strong>
                            <small>Ruang belajar yang dikelola</small>
                        </article>
                        <article>
                            <span>Siswa dalam akses</span>
                            <strong id="count-siswa">—</strong>
                            <small>Bagian dari perjalanan belajar</small>
                        </article>
                        <article>
                            <span>Mata pelajaran</span>
                            <strong id="count-mapel">—</strong>
                            <small>Terdaftar di sekolah</small>
                        </article>
                        <article>
                            <span>Penugasan mengajar</span>
                            <strong id="count-penugasan">—</strong>
                            <small>Sesuai hak akses akun</small>
                        </article>
                    </section>
                    <div class="dashboard-grid">
                        <section class="panel">
                            <div class="section-title">
                                <div>
                                    <p class="eyebrow">MULAI DARI SINI</p>
                                    <h2>Persiapan akademik</h2>
                                </div>
                                <span class="pill">Data utama</span>
                            </div>
                            <a class="step" href="index.php?halaman=kelas">
                                <span>01</span>
                                <div>
                                    <strong>Siapkan ruang kelas</strong>
                                    <p>Atur kelas dan wali kelas yang mendampingi.</p>
                                </div>
                                <b>→</b>
                            </a>
                            <a class="step" href="index.php?halaman=siswa">
                                <span>02</span>
                                <div>
                                    <strong>Lengkapi data siswa</strong>
                                    <p>Catat identitas siswa dan kelompok kelasnya.</p>
                                </div>
                                <b>→</b>
                            </a>
                            <a class="step" href="index.php?halaman=penugasan">
                                <span>03</span>
                                <div>
                                    <strong>Periksa penugasan guru</strong>
                                    <p>Hubungkan guru, kelas, mapel, dan periode.</p>
                                </div>
                                <b>→</b>
                            </a>
                        </section>
                        <section class="panel period-panel">
                            <p class="eyebrow">KALENDER AKADEMIK</p>
                            <div class="calendar-icon">▦</div>
                            <h2>Periode aktif</h2>
                            <p id="active-period">Memuat periode…</p>
                            <a href="index.php?halaman=periode">Lihat periode penilaian →</a>
                            <hr>
                            <small>Data pada ringkasan mengikuti hak akses akun yang sedang masuk.</small>
                        </section>
                    </div>
                <?php elseif ($page === 'klasifikasi'): ?>
                <?php include __DIR__ . '/views/klasifikasi.php'; ?>
                <?php elseif ($page === 'laporan'): ?>
                <!-- Dikerjakan oleh: Jehan Reza Pahlevi (NPM. 2415061067) -->
                <?php include __DIR__ . '/views/laporan.php'; ?>

                <?php elseif ($page === 'profil'): ?>
                    <div class="page-heading">
                        <div>
                            <p class="eyebrow">AKUN GURU</p>
                            <h1>Profil saya</h1>
                            <p>Perbarui identitas dan keamanan akun Anda.</p>
                        </div>
                    </div>
                    <section class="panel narrow">
                        <form id="profile-form">
                            <label>Nama lengkap<input name="nama_guru" required maxlength="100">
                            </label>
                            <label>NIP<input name="nip" required maxlength="50">
                            </label>
                            <label>Email<input name="email" type="email" required maxlength="100">
                            </label>
                            <label>Password saat ini<input name="password_lama" type="password" required autocomplete="current-password">
                            </label>
                            <label>Password baru (opsional)<input name="password_baru" type="password" minlength="8" maxlength="72" autocomplete="new-password">
                            </label>
                            <small>Kosongkan password baru jika tidak ingin menggantinya.</small>
                            <button class="primary" type="submit">Simpan profil</button>
                        </form>
                    </section>
                <?php else: ?>
                    <div class="page-heading">
                        <div>
                            <p class="eyebrow">MANAJEMEN AKADEMIK</p>
                            <h1><?= e($menus[$page]) ?></h1>
                            <p id="module-description">Kelola informasi akademik sekolah.</p>
                        </div>
                        <button id="add-button" class="primary" type="button" hidden>+ Tambah data</button>
                    </div>
                    <section class="panel">
                        <div class="table-toolbar">
                            <label class="search-label">Cari data<input type="search" id="search" placeholder="Ketik kata kunci…">
                            </label>
                            <span id="row-count" class="pill">Memuat…</span>
                        </div>
                        <div class="table-scroll">
                            <table>
                                <thead id="table-head">
                                </thead>
                                <tbody id="table-body">
                                    <tr>
                                        <td>Memuat data…</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <p class="table-note" id="access-note">
                        </p>
                    </section>
                    <dialog id="data-dialog">
                        <div class="dialog-heading">
                            <h2 id="dialog-title">Tambah data</h2>
                            <button type="button" id="close-dialog" aria-label="Tutup formulir">×</button>
                        </div>
                        <form id="data-form">
                            <div id="form-fields">
                            </div>
                            <p id="form-error" class="error-text" role="alert">
                            </p>
                            <div class="form-actions">
                                <button id="cancel-dialog" type="button">Batal</button>
                                <button class="primary" type="submit">Simpan data</button>
                            </div>
                        </form>
                    </dialog>
                <?php endif; ?>
                <footer>Arunika <span>•</span> Aplikasi Rekapitulasi Nilai dan Kedisiplinan Kelas</footer>
            </main>
        </div>
    </body>
</html>

