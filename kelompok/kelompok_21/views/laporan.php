<?php
/**
 * =================================================================
 * VIEW HTML5: Laporan Rekapitulasi Nilai & Pengaturan Bobot
 * Modul     : Perhitungan Nilai & Laporan Akademik
 * Dikerjakan oleh: Jehan Reza Pahlevi (NPM. 2415061067)
 * =================================================================
 */
?>
<!-- Dikerjakan oleh: Jehan Reza Pahlevi (NPM. 2415061067) -->
<div id="laporan-root">
    <!-- Header Halaman -->
    <div class="page-heading">
        <div>
            <p class="eyebrow">EVALUASI AKADEMIK & REKAP KELAS</p>
            <h1>Rekap nilai & laporan</h1>
            <p>Pengaturan bobot evaluasi, kalkulasi nilai akhir otomatis, dan rekapitulasi capaian belajar siswa per kelas.</p>
        </div>
        <div class="heading-actions no-print">
            <button id="btn-hitung-ulang" class="primary" type="button">
                <span>⚡</span> Hitung nilai akhir
            </button>
            <button id="btn-cetak-laporan" type="button">
                <span>🖨️</span> Cetak rekap (PDF/Print)
            </button>
        </div>
    </div>

    <!-- Notifikasi Modul Laporan -->
    <div id="laporan-notice" class="message" hidden role="alert"></div>

    <!-- Kop Cetak Resmi (Hanya Tampil Saat Dicetak / window.print()) -->
    <section class="print-header only-print" aria-hidden="true">
        <div class="print-brand">
            <h2>ARUNIKA — LAPORAN REKAPITULASI NILAI AKADEMIK</h2>
            <p>Sistem Informasi Evaluasi Akademik dan Kedisiplinan Kelas • LabKom TI Unila</p>
            <hr class="print-divider">
        </div>
        <table class="print-meta-table">
            <tr>
                <td style="width: 18%;"><strong>Mata Pelajaran</strong></td>
                <td style="width: 32%;">: <span id="print-mapel">—</span></td>
                <td style="width: 18%;"><strong>Tahun / Semester</strong></td>
                <td style="width: 32%;">: <span id="print-periode">—</span></td>
            </tr>
            <tr>
                <td><strong>Kelas</strong></td>
                <td>: <span id="print-kelas">—</span></td>
                <td><strong>Guru Pengampu</strong></td>
                <td>: <span id="print-guru">—</span></td>
            </tr>
            <tr>
                <td><strong>Komposisi Bobot</strong></td>
                <td colspan="3">: <span id="print-bobot">—</span></td>
            </tr>
        </table>
    </section>

    <!-- Toolbar Pemilihan Penugasan & Ringkasan Cepat -->
    <section class="panel no-print" style="margin-bottom: 20px;">
        <div class="table-toolbar" style="flex-wrap: wrap; gap: 14px;">
            <label class="search-label" style="min-width: 280px; margin-bottom: 0;">
                Pilih penugasan mengajar:
                <select id="select-penugasan" aria-label="Pilih penugasan mengajar">
                    <option value="">Memuat daftar penugasan…</option>
                </select>
            </label>
            <span id="badge-status-rekap" class="pill" style="align-self: center;">Pilih kelas</span>
        </div>
    </section>

    <!-- Panel Pengaturan Bobot Komponen Penilaian -->
    <section class="panel no-print" style="margin-bottom: 24px;">
        <div class="section-title">
            <div>
                <p class="eyebrow">KOMPOSISI PENILAIAN</p>
                <h2 style="font-size: 18px; margin-bottom: 4px;">Pengaturan bobot komponen nilai</h2>
                <small class="muted">Tentukan persentase kontribusi setiap komponen terhadap nilai akhir. Total wajib tepat 100%.</small>
            </div>
            <span id="badge-total-bobot" class="pill" style="font-weight: bold;">Total: 100%</span>
        </div>

        <form id="form-bobot" style="margin-top: 14px;">
            <div class="bobot-grid">
                <div class="bobot-card">
                    <label for="bobot-tugas">Bobot Tugas (%)</label>
                    <input type="number" id="bobot-tugas" name="bobot_tugas" min="0" max="100" step="0.5" value="25" required>
                    <small>Latihan, PR & proyek berkala</small>
                </div>
                <div class="bobot-card">
                    <label for="bobot-kuis">Bobot Kuis (%)</label>
                    <input type="number" id="bobot-kuis" name="bobot_kuis" min="0" max="100" step="0.5" value="25" required>
                    <small>Tes formatif & kuis harian</small>
                </div>
                <div class="bobot-card">
                    <label for="bobot-uts">Bobot UTS (%)</label>
                    <input type="number" id="bobot-uts" name="bobot_uts" min="0" max="100" step="0.5" value="25" required>
                    <small>Ujian tengah semester</small>
                </div>
                <div class="bobot-card">
                    <label for="bobot-uas">Bobot UAS (%)</label>
                    <input type="number" id="bobot-uas" name="bobot_uas" min="0" max="100" step="0.5" value="25" required>
                    <small>Ujian akhir semester</small>
                </div>
            </div>

            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 16px; flex-wrap: wrap; gap: 10px;">
                <span id="bobot-pesan-validasi" style="font-size: 13px; color: var(--green);">✓ Total bobot pas 100%</span>
                <button type="submit" id="btn-simpan-bobot" class="primary">Simpan pengaturan bobot</button>
            </div>
        </form>
    </section>

    <!-- Ringkasan Statistik Kelas (Cards) -->
    <section class="stats" aria-label="Statistik capaian kelas" style="margin-bottom: 24px;">
        <article>
            <span>Rata-rata nilai kelas</span>
            <strong id="stat-rata-kelas">—</strong>
            <small id="stat-predikat-kelas">Predikat rata-rata: —</small>
        </article>
        <article>
            <span>Nilai tertinggi</span>
            <strong id="stat-nilai-max">—</strong>
            <small>Capaian puncak siswa</small>
        </article>
        <article>
            <span>Nilai terendah</span>
            <strong id="stat-nilai-min">—</strong>
            <small>Batas bawah capaian</small>
        </article>
        <article>
            <span>Kelengkapan nilai</span>
            <strong id="stat-kelengkapan">—</strong>
            <small id="stat-siswa-lengkap">0 dari 0 siswa lengkap</small>
        </article>
    </section>

    <!-- Tabel Rekapitulasi Nilai Siswa -->
    <section class="panel">
        <div class="table-toolbar no-print">
            <label class="search-label" style="margin-bottom: 0;">
                Cari siswa di kelas:
                <input type="search" id="search-siswa-laporan" placeholder="Ketik NISN atau nama siswa…">
            </label>
            <span id="count-siswa-laporan" class="pill">0 siswa</span>
        </div>

        <div class="table-scroll">
            <table id="tabel-rekap-nilai">
                <thead>
                    <tr>
                        <th style="width: 48px;">No.</th>
                        <th>NISN</th>
                        <th>Nama siswa</th>
                        <th>Rata tugas</th>
                        <th>Rata kuis</th>
                        <th>UTS</th>
                        <th>UAS</th>
                        <th>Nilai akhir</th>
                        <th>Predikat</th>
                        <th>Kelengkapan</th>
                        <th class="no-print">Kuadran belajar</th>
                    </tr>
                </thead>
                <tbody id="body-rekap-nilai">
                    <tr>
                        <td colspan="11" class="empty">Silakan pilih penugasan mengajar terlebih dahulu.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <p class="table-note no-print" id="catatan-tabel-rekap">
            Nilai akhir dihitung secara otomatis berdasarkan rata-rata komponen terbobot. Siswa berstatus <em>Belum lengkap</em> masih memiliki komponen penilaian yang belum dinilai.
        </p>

        <!-- Tanda Tangan Cetak (Hanya Tampil Saat Dicetak) -->
        <div class="print-signature only-print">
            <div style="float: right; width: 260px; text-align: center; margin-top: 30px;">
                <p>Bandar Lampung, <span id="print-tanggal-ttd">—</span></p>
                <p style="margin-bottom: 70px;">Guru Pengampu,</p>
                <strong id="print-nama-ttd" style="text-decoration: underline;">—</strong>
                <p id="print-nip-ttd" style="font-size: 11px; margin-top: 4px;">NIP. —</p>
            </div>
            <div style="clear: both;"></div>
        </div>
    </section>
</div>

<!-- Styling Khusus Modul Laporan & Print -->
<!-- Dikerjakan oleh: Jehan Reza Pahlevi (NPM. 2415061067) -->
<style>
    #laporan-root .bobot-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 14px;
    }
    #laporan-root .bobot-card {
        background: #fbfdfa;
        border: 1px solid var(--line);
        border-radius: 8px;
        padding: 12px 14px;
    }
    #laporan-root .bobot-card label {
        display: block;
        font-weight: 600;
        font-size: 13px;
        margin-bottom: 6px;
    }
    #laporan-root .bobot-card input {
        width: 100%;
        padding: 8px 10px;
        font-size: 16px;
        font-weight: 600;
        border: 1px solid var(--line);
        border-radius: 6px;
    }
    #laporan-root .bobot-card small {
        display: block;
        margin-top: 6px;
        font-size: 11px;
        color: var(--muted);
    }
    #laporan-root .badge-lengkap {
        display: inline-block;
        padding: 3px 8px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 600;
        background: #e1f0e5;
        color: #215c38;
    }
    #laporan-root .badge-belum {
        display: inline-block;
        padding: 3px 8px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 600;
        background: #fae8e4;
        color: #983f37;
    }
    #laporan-root .predikat-pill {
        display: inline-block;
        font-weight: bold;
        width: 26px;
        height: 26px;
        line-height: 26px;
        text-align: center;
        border-radius: 50%;
        background: var(--mint);
        color: var(--green);
        font-size: 12px;
    }
    #laporan-root .kuadran-tag {
        display: inline-block;
        padding: 2px 8px;
        border-radius: 4px;
        font-size: 11px;
        font-weight: 600;
        background: #edf3e8;
        color: #2b533d;
    }

    /* Utilitas Khusus Cetak */
    .only-print { display: none !important; }

    @media print {
        @page {
            size: A4 landscape;
            margin: 12mm 15mm;
        }
        *, *::before, *::after {
            box-sizing: border-box;
            text-shadow: none !important;
            box-shadow: none !important;
        }
        html, body {
            background: #fff !important;
            color: #000 !important;
            font-size: 10pt !important;
            line-height: 1.4 !important;
        }
        .sidebar, .topbar, footer, .no-print, dialog {
            display: none !important;
        }
        .workspace {
            margin: 0 !important;
            padding: 0 !important;
            width: 100% !important;
            max-width: 100% !important;
        }
        .only-print {
            display: block !important;
        }
        .print-header {
            margin-bottom: 16px;
            text-align: center;
        }
        .print-brand h2 {
            font-size: 14pt;
            font-weight: bold;
            margin: 0 0 4px 0;
            color: #000;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        .print-brand p {
            margin: 0 0 8px 0;
            font-size: 9pt;
            color: #222;
        }
        .print-divider {
            border: 0;
            border-top: 2px solid #000;
            margin: 8px 0 12px 0;
        }
        .print-meta-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9.5pt;
            margin-bottom: 14px;
            text-align: left;
        }
        .print-meta-table td {
            padding: 3px 6px;
            border: none !important;
        }
        .table-scroll {
            overflow: visible !important;
        }
        table {
            width: 100% !important;
            border-collapse: collapse !important;
            page-break-inside: auto;
            margin-top: 6px;
        }
        tr {
            page-break-inside: avoid;
            page-break-after: auto;
        }
        th, td {
            border: 1px solid #333 !important;
            padding: 5px 7px !important;
            font-size: 9pt !important;
            color: #000 !important;
            text-align: center;
        }
        th {
            background: #f0f0f0 !important;
            font-weight: bold;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        /* Rata kiri untuk nama siswa pada cetak */
        td:nth-child(3) {
            text-align: left !important;
        }
        .stats, .panel {
            border: none !important;
            box-shadow: none !important;
            padding: 0 !important;
            background: transparent !important;
            margin: 0 !important;
        }
        .badge-lengkap, .badge-belum {
            background: transparent !important;
            padding: 0 !important;
            font-weight: normal !important;
        }
        .badge-lengkap { color: #000 !important; font-weight: bold !important; }
        .badge-belum { color: #555 !important; font-style: italic !important; }
        .predikat-pill {
            background: transparent !important;
            color: #000 !important;
            font-weight: bold !important;
        }
        .print-signature {
            page-break-inside: avoid;
            margin-top: 24px;
        }
    }
</style>

