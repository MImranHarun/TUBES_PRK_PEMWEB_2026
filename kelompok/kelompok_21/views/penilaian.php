<?php
?>
<div class="page-heading">
    <div>
        <p class="eyebrow">MANAJEMEN PENILAIAN TERPADU</p>
        <h1>Penilaian</h1>
        <p>Kelola Tugas, Kuis, UTS, dan UAS, lalu isi nilai siswa dalam satu kelas (0–100).</p>
    </div>
    <button id="pn-add" class="primary" type="button" hidden>+ Tambah penilaian</button>
</div>

<section class="panel">
    <div class="table-toolbar" style="margin-bottom:0">
        <label class="search-label" style="max-width:560px;margin-bottom:0">Kelas · Mata pelajaran · Periode
            <select id="pn-konteks"><option value="">Memuat…</option></select>
        </label>
        <span id="pn-count" class="pill">—</span>
    </div>
</section>

<section class="panel" id="pn-daftar-panel" style="margin-top:22px" hidden>
    <div class="section-title">
        <div>
            <p class="eyebrow">DAFTAR PENILAIAN</p>
            <h2>Komponen penilaian</h2>
        </div>
    </div>
    <div class="table-scroll">
        <table>
            <thead>
                <tr>
                    <th>No.</th><th>Judul</th><th>Jenis</th><th>Terisi</th><th>Rata-rata</th><th>Aksi</th>
                </tr>
            </thead>
            <tbody id="pn-body"></tbody>
        </table>
    </div>
    <p class="table-note">Gunakan "Input nilai" untuk mengisi atau mengubah nilai seluruh siswa pada kelas ini.</p>
</section>

<section class="panel" id="pn-nilai-panel" style="margin-top:22px" hidden>
    <div class="section-title">
        <div>
            <p class="eyebrow">INPUT NILAI</p>
            <h2 id="pn-nilai-title">Input nilai</h2>
        </div>
        <span class="pill">Rentang 0–100</span>
    </div>
    <form id="pn-nilai-form">
        <div class="table-scroll">
            <table>
                <thead>
                    <tr><th>No.</th><th>NISN</th><th>Nama siswa</th><th>Nilai</th></tr>
                </thead>
                <tbody id="pn-nilai-body"></tbody>
            </table>
        </div>
        <p id="pn-nilai-error" class="error-text" role="alert"></p>
        <div class="form-actions">
            <button type="button" id="pn-nilai-tutup">Tutup</button>
            <button type="submit" class="primary">Simpan nilai</button>
        </div>
    </form>
</section>

<section class="panel" id="pn-rekap-panel" style="margin-top:22px" hidden>
    <div class="section-title">
        <div>
            <p class="eyebrow">REKAP KELAS</p>
            <h2>Rata-rata nilai per komponen</h2>
        </div>
    </div>
    <div class="table-scroll">
        <table>
            <thead>
                <tr><th>No.</th><th>NISN</th><th>Nama siswa</th><th>Tugas</th><th>Kuis</th><th>UTS</th><th>UAS</th></tr>
            </thead>
            <tbody id="pn-rekap-body"></tbody>
        </table>
    </div>
</section>

<dialog id="pn-dialog">
    <div class="dialog-heading">
        <h2 id="pn-dialog-title">Tambah penilaian</h2>
        <button type="button" id="pn-dialog-x" aria-label="Tutup formulir">×</button>
    </div>
    <form id="pn-form">
        <div>
            <label>Judul penilaian
                <input name="judul_penilaian" required maxlength="100" placeholder="Contoh: Kuis Bab 1">
            </label>
            <label>Jenis komponen
                <select name="jenis_komponen" required>
                    <option value="">Pilih jenis komponen</option>
                    <option>Tugas</option>
                    <option>Kuis</option>
                    <option>UTS</option>
                    <option>UAS</option>
                </select>
            </label>
        </div>
        <p id="pn-form-error" class="error-text" role="alert"></p>
        <div class="form-actions">
            <button type="button" id="pn-dialog-batal">Batal</button>
            <button type="submit" class="primary">Simpan data</button>
        </div>
    </form>
</dialog>