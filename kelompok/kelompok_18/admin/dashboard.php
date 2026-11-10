<?php

declare(strict_types=1);

require_once __DIR__ . '/_init.php';
require_once __DIR__ . '/../config/database.php';

// Jumlah guru
$statement = $pdo->query('SELECT COUNT(*) FROM guru');
$guruCount = (int) $statement->fetchColumn();

// Jumlah siswa
$statement = $pdo->query('SELECT COUNT(*) FROM siswa');
$siswaCount = (int) $statement->fetchColumn();

// Jumlah kelas
$statement = $pdo->query('SELECT COUNT(*) FROM kelas');
$kelasCount = (int) $statement->fetchColumn();

// Semester aktif
$statement = $pdo->prepare('SELECT nama FROM semester WHERE status = :status LIMIT 1');
$statement->execute(['status' => 'aktif']);
$semesterRow = $statement->fetch();
$semesterAktif = $semesterRow ? $semesterRow['nama'] : '-';

// Kelas aktif dengan jumlah siswa pada semester aktif
$statement = $pdo->prepare('
    SELECT k.nama AS nama_kelas,
           s.nama AS nama_semester,
           COUNT(ak.siswa_id) AS jumlah_siswa
    FROM kelas AS k
    LEFT JOIN anggota_kelas AS ak ON ak.kelas_id = k.id
    LEFT JOIN semester AS s ON ak.semester_id = s.id AND s.status = :status
    WHERE k.is_active = TRUE
    GROUP BY k.id, k.nama, s.nama
    ORDER BY k.nama
');
$statement->execute(['status' => 'aktif']);
$kelasAktif = $statement->fetchAll();

admin_page_start('Dashboard', 'dashboard.php');
?>
<section aria-label="Ringkasan administrasi" class="grid gap-4 sm:grid-cols-2 2xl:grid-cols-4">
    <?php
    attendara_ui_stat_card('Total Guru', (string) $guruCount, 'Guru terdaftar', 'users');
    attendara_ui_stat_card('Total Siswa', (string) $siswaCount, 'Siswa terdaftar', 'users');
    attendara_ui_stat_card('Total Kelas', (string) $kelasCount, 'Kelas terdaftar', 'school');
    attendara_ui_stat_card('Semester Aktif', $semesterAktif, 'Status data: aktif', 'calendar');
    ?>
</section>

<section class="space-y-4">
    <div>
        <h2 class="text-lg font-semibold text-[#20392a]">Kelas Aktif</h2>
        <p class="mt-1 text-sm text-slate-600">Jumlah siswa pada setiap kelas.</p>
    </div>
    <div class="grid gap-4 md:grid-cols-2 2xl:grid-cols-3">
        <?php foreach ($kelasAktif as $row): ?>
            <?php
            $nama = (string) ($row['nama_kelas'] ?? '-');
            $semester = (string) ($row['nama_semester'] ?? '-');
            $jumlah = (int) ($row['jumlah_siswa'] ?? 0);
            ?>
            <article class="rounded-2xl border border-[#e2e9e2] bg-white p-5 shadow-[0_2px_8px_rgba(30,55,38,0.04)]">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h3 class="font-semibold text-[#20392a]"><?= attendara_ui_escape($nama) ?></h3>
                        <p class="mt-1 text-sm text-slate-500"><?= attendara_ui_escape($semester) ?></p>
                    </div>
                    <span class="rounded-xl bg-[#e9f0e8] p-2 text-[#28583b]"><?php attendara_ui_icon('school', 'h-5 w-5'); ?></span>
                </div>
                <p class="mt-5 text-sm font-medium text-[#31583c]"><?= attendara_ui_escape((string) $jumlah) ?></p>
            </article>
        <?php endforeach; ?>
    </div>
</section>
<?php admin_page_end(); ?>
