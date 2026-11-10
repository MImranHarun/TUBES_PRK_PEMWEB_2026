<div class="arunika-wrapper">
    <div class="page-header">
        <div>
            <h2>Klasifikasi Belajar Siswa</h2>
            <p>Pengelompokan capaian nilai ujian akhir dan indeks kedisiplinan pengumpulan tugas.</p>
        </div>
        <div class="total-badge" id="badge-total-siswa">Total: 0 Siswa</div>
    </div>

    <div class="filter-box">
        <div class="filter-item">
            <label for="filter-kelas">Kelas:</label>
            <select id="filter-kelas">
            <option value="0">Semua Kelas</option>
            <option value="1">X IPA 1</option>
            <option value="2">X IPA 2</option>
            <option value="3">XI IPA 1</option>
            </select>
        </div>
        <div class="filter-item">
            <label for="input-kkm">KKM Nilai:</label>
            <input type="number" id="input-kkm" value="75" min="0" max="100">
        </div>
        <button type="button" id="btn-terapkan-filter" class="btn-green">Terapkan Filter</button>
    </div>

    <div class="quadrant-layout">
        <div class="quadrant-card q-bakat">
            <div class="q-card-header">
                <h3>Jarang kumpul & nilai tinggi</h3>
                <span class="q-badge" id="count-bakat">0</span>
            </div>
            <p class="q-note">Jarang kumpul tugas, namun nilai ujian tinggi.</p>
            <div class="student-container" id="list-bakat"></div>
        </div>

        <div class="quadrant-card q-bintang">
            <div class="q-card-header">
                <h3>Rajin & nilai tinggi</h3>
                <span class="q-badge" id="count-bintang">0</span>
            </div>
            <p class="q-note">Rajin kerjakan tugas dan nilai ujian sangat baik.</p>
            <div class="student-container" id="list-bintang"></div>
        </div>

        <div class="quadrant-card q-bahaya">
            <div class="q-card-header">
                <h3>Jarang kumpul & nilai rendah</h3>
                <span class="q-badge" id="count-bahaya">0</span>
            </div>
            <p class="q-note">Jarang kerjakan tugas dan nilai ujian rendah.</p>
            <div class="student-container" id="list-bahaya"></div>
        </div>

        <div class="quadrant-card q-butuh">
            <div class="q-card-header">
                <h3>Rajin & nilai rendah</h3>
                <span class="q-badge" id="count-butuh">0</span>
            </div>
            <p class="q-note">Sangat rajin ngumpul tugas, tapi nilai ujian rendah.</p>
            <div class="student-container" id="list-butuh"></div>
        </div>
    </div>
</div>

<dialog id="studentModal" class="clean-modal">
    <div class="modal-inner">
        <div class="modal-top">
            <h3 id="modalNamaSiswa">Nama Siswa</h3>
            <button type="button" id="btnCloseModal" class="modal-close-btn">&times;</button>
        </div>
        <div class="modal-body">
            <div class="status-pill" id="modalBadgeKuadran">Status Klasifikasi</div>
            <div class="stat-group">
                <div class="stat-row">
                    <span>Kedisiplinan Tugas:</span>
                    <strong id="modalNilaiTugas">0%</strong>
                </div>
                <div class="stat-row">
                    <span>Nilai Ujian Akhir:</span>
                    <strong id="modalNilaiUjian">0</strong>
                </div>
            </div>
        </div>
        <div class="modal-bottom">
            <button type="button" id="btnTutupModalFooter" class="btn-secondary-native">Tutup</button>
        </div>
    </div>
</dialog>