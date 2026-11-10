/**
 * =================================================================
 * JAVASCRIPT DOM & AJAX FETCH: Modul Rekap Nilai & Pengaturan Bobot
 * Modul       : Perhitungan Nilai & Laporan Akademik
 * Dikerjakan oleh: Jehan Reza Pahlevi (NPM. 2415061067)
 * =================================================================
 *
 * Fitur yang ditangani:
 * 1. Fetch & populasi dropdown penugasan guru yang sedang login
 * 2. Fetch & tampilkan pengaturan bobot komponen penilaian aktif
 * 3. Validasi DOM interaktif real-time: total bobot wajib tepat 100%
 * 4. AJAX POST/PUT simpan pembaruan bobot komponen penilaian
 * 5. AJAX POST kalkulasi nilai akhir otomatis di backend (trigger hitung)
 * 6. Fetch & render tabel rekapitulasi nilai siswa beserta predikat & kuadran
 * 7. Live search/filter siswa berdasarkan nama atau NISN tanpa me-reload
 * 8. Render ringkasan statistik kelas (rata-rata, tertinggi, terendah, % lengkap)
 * 9. Cetak laporan via window.print() dengan populasi kop & tanda tangan resmi
 * =================================================================
 */

// Dikerjakan oleh: Jehan Reza Pahlevi (NPM. 2415061067)
(function () {
    'use strict';

    // Dapatkan token CSRF dari meta tag
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

    // State Modul Laporan
    let daftarPenugasan = [];
    let penugasanAktif  = null;
    let dataSiswaRekap  = [];
    let bobotAktif      = { bobot_tugas: 25, bobot_kuis: 25, bobot_uts: 25, bobot_uas: 25 };

    // Elemen DOM
    const selectPenugasan       = document.getElementById('select-penugasan');
    const badgeStatusRekap      = document.getElementById('badge-status-rekap');
    const formBobot             = document.getElementById('form-bobot');
    const inputBobotTugas       = document.getElementById('bobot-tugas');
    const inputBobotKuis        = document.getElementById('bobot-kuis');
    const inputBobotUts         = document.getElementById('bobot-uts');
    const inputBobotUas         = document.getElementById('bobot-uas');
    const badgeTotalBobot       = document.getElementById('badge-total-bobot');
    const pesanValidasiBobot    = document.getElementById('bobot-pesan-validasi');
    const btnSimpanBobot        = document.getElementById('btn-simpan-bobot');
    const btnHitungUlang        = document.getElementById('btn-hitung-ulang');
    const btnCetakLaporan       = document.getElementById('btn-cetak-laporan');
    const inputSearchSiswa      = document.getElementById('search-siswa-laporan');
    const countSiswaLaporan     = document.getElementById('count-siswa-laporan');
    const bodyRekapNilai        = document.getElementById('body-rekap-nilai');
    const laporanNotice         = document.getElementById('laporan-notice');

    // Elemen Statistik
    const statRataKelas         = document.getElementById('stat-rata-kelas');
    const statPredikatKelas     = document.getElementById('stat-predikat-kelas');
    const statNilaiMax          = document.getElementById('stat-nilai-max');
    const statNilaiMin          = document.getElementById('stat-nilai-min');
    const statKelengkapan       = document.getElementById('stat-kelengkapan');
    const statSiswaLengkap      = document.getElementById('stat-siswa-lengkap');

    // Elemen Kop Cetak
    const printMapel            = document.getElementById('print-mapel');
    const printKelas            = document.getElementById('print-kelas');
    const printPeriode          = document.getElementById('print-periode');
    const printGuru             = document.getElementById('print-guru');
    const printBobot            = document.getElementById('print-bobot');
    const printTanggalTtd       = document.getElementById('print-tanggal-ttd');
    const printNamaTtd          = document.getElementById('print-nama-ttd');
    const printNipTtd           = document.getElementById('print-nip-ttd');

    // =================================================================
    // FUNGSI BANTU: AJAX Fetch Wrapper Aman dengan CSRF Header
    // Dikerjakan oleh: Jehan Reza Pahlevi (NPM. 2415061067)
    // =================================================================
    async function apiRequest(url, method = 'GET', body = null) {
        const options = {
            method: method,
            headers: {
                'X-CSRF-Token': csrfToken
            }
        };

        if (body !== null) {
            options.headers['Content-Type'] = 'application/json';
            options.body = JSON.stringify(body);
        }

        const response = await fetch(url, options);

        if (response.status === 401) {
            window.location.href = 'login.php';
            throw new Error('Sesi telah berakhir. Silakan masuk kembali.');
        }

        let result;
        try {
            result = await response.json();
        } catch (e) {
            throw new Error('Respons server tidak valid.');
        }

        if (!response.ok || result.status !== 'success') {
            throw new Error(result.message || 'Terjadi kesalahan pada permintaan data.');
        }

        return result.data;
    }

    // =================================================================
    // FUNGSI BANTU: Tampilkan Notifikasi Banner
    // Dikerjakan oleh: Jehan Reza Pahlevi (NPM. 2415061067)
    // =================================================================
    function tampilkanNotifikasi(pesan, isError = false) {
        if (!laporanNotice) return;
        laporanNotice.hidden = false;
        laporanNotice.className = 'message ' + (isError ? 'error' : 'success');
        laporanNotice.textContent = pesan;

        // Auto hide setelah 5 detik jika sukses
        if (!isError) {
            setTimeout(() => {
                laporanNotice.hidden = true;
            }, 5000);
        }
    }

    // =================================================================
    // FUNGSI 1: Inisialisasi Modul & Pemuatan Daftar Penugasan
    // Dikerjakan oleh: Jehan Reza Pahlevi (NPM. 2415061067)
    // =================================================================
    async function muatDaftarPenugasan() {
        try {
            daftarPenugasan = await apiRequest('api/kalkulasi.php?action=penugasan');
            selectPenugasan.innerHTML = '';

            if (!daftarPenugasan || daftarPenugasan.length === 0) {
                selectPenugasan.innerHTML = '<option value="">Tidak ada penugasan mengajar aktif</option>';
                selectPenugasan.disabled = true;
                btnHitungUlang.disabled = true;
                btnSimpanBobot.disabled = true;
                tampilkanNotifikasi('Anda belum memiliki penugasan mengajar aktif.', true);
                return;
            }

            const promptOption = document.createElement('option');
            promptOption.value = '';
            promptOption.textContent = '-- Pilih Kelas & Mata Pelajaran --';
            selectPenugasan.appendChild(promptOption);

            daftarPenugasan.forEach((p, idx) => {
                const opt = document.createElement('option');
                opt.value = p.id_penugasan;
                opt.textContent = `${p.nama_kelas} (${p.tingkat}) — ${p.nama_mapel} [${p.tahun_ajaran} ${p.semester}]`;
                selectPenugasan.appendChild(opt);
            });

            // Pilih otomatis penugasan pertama jika ada
            if (daftarPenugasan.length > 0) {
                selectPenugasan.selectedIndex = 1;
                handlePilihPenugasan();
            }
        } catch (error) {
            tampilkanNotifikasi(error.message, true);
        }
    }

    // =================================================================
    // FUNGSI 2: Penanganan Pergantian Penugasan Terpilih
    // Dikerjakan oleh: Jehan Reza Pahlevi (NPM. 2415061067)
    // =================================================================
    async function handlePilihPenugasan() {
        const idPenugasan = selectPenugasan.value;
        if (!idPenugasan) {
            penugasanAktif = null;
            badgeStatusRekap.textContent = 'Pilih kelas';
            resetTabel();
            return;
        }

        penugasanAktif = daftarPenugasan.find(p => String(p.id_penugasan) === String(idPenugasan));
        badgeStatusRekap.textContent = penugasanAktif ? `${penugasanAktif.nama_kelas} • ${penugasanAktif.kode_mapel}` : 'Memuat…';

        // Update header cetak
        if (penugasanAktif) {
            if (printMapel)   printMapel.textContent   = `${penugasanAktif.nama_mapel} (${penugasanAktif.kode_mapel})`;
            if (printKelas)   printKelas.textContent   = `${penugasanAktif.nama_kelas} (Tingkat ${penugasanAktif.tingkat})`;
            if (printPeriode) printPeriode.textContent = `${penugasanAktif.tahun_ajaran} • Semester ${penugasanAktif.semester}`;
            if (printGuru)    printGuru.textContent    = penugasanAktif.nama_guru;
            if (printNamaTtd) printNamaTtd.textContent = penugasanAktif.nama_guru;
        }

        // Muat bobot dan data rekap secara paralel
        await Promise.all([
            muatBobot(idPenugasan),
            muatRekapNilai(idPenugasan),
            muatRingkasanKelas(idPenugasan)
        ]);
    }

    // =================================================================
    // FUNGSI 3: Muat Data Bobot Aktif
    // Dikerjakan oleh: Jehan Reza Pahlevi (NPM. 2415061067)
    // =================================================================
    async function muatBobot(idPenugasan) {
        try {
            const dataBobot = await apiRequest(`api/kalkulasi.php?action=bobot&id_penugasan=${idPenugasan}`);
            if (dataBobot) {
                bobotAktif = {
                    bobot_tugas: parseFloat(dataBobot.bobot_tugas) || 25,
                    bobot_kuis:  parseFloat(dataBobot.bobot_kuis)  || 25,
                    bobot_uts:   parseFloat(dataBobot.bobot_uts)   || 25,
                    bobot_uas:   parseFloat(dataBobot.bobot_uas)   || 25
                };
                inputBobotTugas.value = bobotAktif.bobot_tugas;
                inputBobotKuis.value  = bobotAktif.bobot_kuis;
                inputBobotUts.value   = bobotAktif.bobot_uts;
                inputBobotUas.value   = bobotAktif.bobot_uas;

                validasiTotalBobot();
                updatePrintBobot();
            }
        } catch (error) {
            tampilkanNotifikasi('Gagal memuat pengaturan bobot: ' + error.message, true);
        }
    }

    // =================================================================
    // FUNGSI 4: Validasi Real-Time Total Bobot (Wajib 100%)
    // Dikerjakan oleh: Jehan Reza Pahlevi (NPM. 2415061067)
    // =================================================================
    function validasiTotalBobot() {
        const bt = parseFloat(inputBobotTugas.value) || 0;
        const bk = parseFloat(inputBobotKuis.value)  || 0;
        const bu = parseFloat(inputBobotUts.value)   || 0;
        const ba = parseFloat(inputBobotUas.value)   || 0;

        const total = Math.round((bt + bk + bu + ba) * 100) / 100;

        badgeTotalBobot.textContent = `Total: ${total}%`;

        const isNegatif = (bt < 0 || bk < 0 || bu < 0 || ba < 0);
        const isPas100  = (Math.abs(total - 100) <= 0.01) && !isNegatif;

        if (isPas100) {
            badgeTotalBobot.style.background = '#e1f0e5';
            badgeTotalBobot.style.color = '#215c38';
            pesanValidasiBobot.textContent = '✓ Total bobot pas 100%';
            pesanValidasiBobot.style.color = '#215c38';
            btnSimpanBobot.disabled = false;
        } else {
            badgeTotalBobot.style.background = '#fae8e4';
            badgeTotalBobot.style.color = '#983f37';
            if (isNegatif) {
                pesanValidasiBobot.textContent = '✕ Bobot tidak boleh bernilai negatif';
            } else if (total < 100) {
                pesanValidasiBobot.textContent = `✕ Total bobot kurang ${(100 - total).toFixed(1)}% (harus 100%)`;
            } else {
                pesanValidasiBobot.textContent = `✕ Total bobot kelebihan ${(total - 100).toFixed(1)}% (harus 100%)`;
            }
            pesanValidasiBobot.style.color = '#983f37';
            btnSimpanBobot.disabled = true;
        }

        return isPas100;
    }

    function updatePrintBobot() {
        if (printBobot) {
            printBobot.textContent = `Tugas ${bobotAktif.bobot_tugas}%, Kuis ${bobotAktif.bobot_kuis}%, UTS ${bobotAktif.bobot_uts}%, UAS ${bobotAktif.bobot_uas}%`;
        }
    }

    // =================================================================
    // FUNGSI 5: Simpan Pembaruan Bobot Komponen Penilaian (AJAX POST)
    // Dikerjakan oleh: Jehan Reza Pahlevi (NPM. 2415061067)
    // =================================================================
    async function handleSimpanBobot(e) {
        e.preventDefault();
        if (!penugasanAktif) {
            tampilkanNotifikasi('Silakan pilih penugasan mengajar terlebih dahulu.', true);
            return;
        }

        if (!validasiTotalBobot()) {
            tampilkanNotifikasi('Total bobot harus tepat 100% sebelum disimpan.', true);
            return;
        }

        btnSimpanBobot.disabled = true;
        btnSimpanBobot.textContent = 'Menyimpan…';

        try {
            const payload = {
                bobot_tugas: parseFloat(inputBobotTugas.value),
                bobot_kuis:  parseFloat(inputBobotKuis.value),
                bobot_uts:   parseFloat(inputBobotUts.value),
                bobot_uas:   parseFloat(inputBobotUas.value)
            };

            await apiRequest(
                `api/kalkulasi.php?action=bobot&id_penugasan=${penugasanAktif.id_penugasan}`,
                'POST',
                payload
            );

            bobotAktif = payload;
            updatePrintBobot();
            tampilkanNotifikasi('Pengaturan bobot berhasil disimpan.');

            // Refresh tabel rekap & ringkasan untuk merefleksikan bobot baru
            await Promise.all([
                muatRekapNilai(penugasanAktif.id_penugasan),
                muatRingkasanKelas(penugasanAktif.id_penugasan)
            ]);
        } catch (error) {
            tampilkanNotifikasi(error.message, true);
        } finally {
            btnSimpanBobot.disabled = false;
            btnSimpanBobot.textContent = 'Simpan pengaturan bobot';
        }
    }

    // =================================================================
    // FUNGSI 6: Kalkulasi Nilai Akhir Otomatis (Trigger Backend Hitung)
    // Dikerjakan oleh: Jehan Reza Pahlevi (NPM. 2415061067)
    // =================================================================
    async function handleHitungUlang() {
        if (!penugasanAktif) {
            tampilkanNotifikasi('Silakan pilih penugasan mengajar terlebih dahulu.', true);
            return;
        }

        btnHitungUlang.disabled = true;
        btnHitungUlang.textContent = 'Menghitung…';

        try {
            const hasil = await apiRequest(
                `api/kalkulasi.php?action=hitung&id_penugasan=${penugasanAktif.id_penugasan}`,
                'POST'
            );

            tampilkanNotifikasi(`Kalkulasi nilai akhir berhasil diperbarui untuk ${hasil.length} siswa.`);

            // Muat ulang tabel rekap & statistik kelas
            await Promise.all([
                muatRekapNilai(penugasanAktif.id_penugasan),
                muatRingkasanKelas(penugasanAktif.id_penugasan)
            ]);
        } catch (error) {
            tampilkanNotifikasi(error.message, true);
        } finally {
            btnHitungUlang.disabled = false;
            btnHitungUlang.innerHTML = '<span>⚡</span> Hitung nilai akhir';
        }
    }

    // =================================================================
    // FUNGSI 7: Muat & Render Data Rekapitulasi Nilai Siswa
    // Dikerjakan oleh: Jehan Reza Pahlevi (NPM. 2415061067)
    // =================================================================
    async function muatRekapNilai(idPenugasan) {
        bodyRekapNilai.innerHTML = '<tr><td colspan="11" class="empty">Memuat data rekapitulasi nilai…</td></tr>';

        try {
            const response = await apiRequest(`api/kalkulasi.php?action=rekap&id_penugasan=${idPenugasan}`);
            dataSiswaRekap = response.siswa || [];
            renderTabelRekap(dataSiswaRekap);
        } catch (error) {
            bodyRekapNilai.innerHTML = `<tr><td colspan="11" class="empty" style="color: var(--muted);">${error.message}</td></tr>`;
            tampilkanNotifikasi(error.message, true);
        }
    }

    // =================================================================
    // FUNGSI 8: Render Baris Tabel Nilai ke DOM
    // Dikerjakan oleh: Jehan Reza Pahlevi (NPM. 2415061067)
    // =================================================================
    function renderTabelRekap(siswaList) {
        bodyRekapNilai.innerHTML = '';
        countSiswaLaporan.textContent = `${siswaList.length} siswa`;

        if (!siswaList || siswaList.length === 0) {
            const emptyRow = document.createElement('tr');
            const emptyCell = document.createElement('td');
            emptyCell.colSpan = 11;
            emptyCell.className = 'empty';
            emptyCell.textContent = inputSearchSiswa.value
                ? 'Tidak ada siswa yang cocok dengan kata kunci pencarian.'
                : 'Belum ada data siswa terdaftar di kelas ini.';
            emptyRow.appendChild(emptyCell);
            bodyRekapNilai.appendChild(emptyRow);
            return;
        }

        siswaList.forEach((s, idx) => {
            const row = document.createElement('tr');

            // No
            const tdNo = document.createElement('td');
            tdNo.textContent = String(idx + 1);
            row.appendChild(tdNo);

            // NISN
            const tdNisn = document.createElement('td');
            tdNisn.textContent = s.nisn;
            row.appendChild(tdNisn);

            // Nama Siswa
            const tdNama = document.createElement('td');
            tdNama.style.fontWeight = '600';
            tdNama.textContent = s.nama_siswa;
            row.appendChild(tdNama);

            // Rata Tugas
            const tdTugas = document.createElement('td');
            tdTugas.textContent = s.rata_tugas !== null ? Number(s.rata_tugas).toFixed(1) : '—';
            row.appendChild(tdTugas);

            // Rata Kuis
            const tdKuis = document.createElement('td');
            tdKuis.textContent = s.rata_kuis !== null ? Number(s.rata_kuis).toFixed(1) : '—';
            row.appendChild(tdKuis);

            // Rata UTS
            const tdUts = document.createElement('td');
            tdUts.textContent = s.rata_uts !== null ? Number(s.rata_uts).toFixed(1) : '—';
            row.appendChild(tdUts);

            // Rata UAS
            const tdUas = document.createElement('td');
            tdUas.textContent = s.rata_uas !== null ? Number(s.rata_uas).toFixed(1) : '—';
            row.appendChild(tdUas);

            // Nilai Akhir
            const tdAkhir = document.createElement('td');
            tdAkhir.style.fontWeight = 'bold';
            tdAkhir.textContent = s.nilai_akhir !== null ? Number(s.nilai_akhir).toFixed(2) : '—';
            row.appendChild(tdAkhir);

            // Predikat
            const tdPredikat = document.createElement('td');
            if (s.predikat && s.predikat !== '-') {
                const pill = document.createElement('span');
                pill.className = 'predikat-pill';
                pill.textContent = s.predikat;
                tdPredikat.appendChild(pill);
            } else {
                tdPredikat.textContent = '—';
            }
            row.appendChild(tdPredikat);

            // Kelengkapan
            const tdLengkap = document.createElement('td');
            const badgeLengkap = document.createElement('span');
            badgeLengkap.className = s.status_kelengkapan_nilai === 'Lengkap' ? 'badge-lengkap' : 'badge-belum';
            badgeLengkap.textContent = s.status_kelengkapan_nilai || 'Belum lengkap';
            tdLengkap.appendChild(badgeLengkap);
            row.appendChild(tdLengkap);

            // Kuadran Belajar (no-print)
            const tdKuadran = document.createElement('td');
            tdKuadran.className = 'no-print';
            if (s.kategori_kuadran) {
                const tag = document.createElement('span');
                tag.className = 'kuadran-tag';
                tag.textContent = s.kategori_kuadran;
                tdKuadran.appendChild(tag);
            } else {
                tdKuadran.textContent = 'Belum dihitung';
                tdKuadran.style.color = 'var(--muted)';
                tdKuadran.style.fontSize = '12px';
            }
            row.appendChild(tdKuadran);

            bodyRekapNilai.appendChild(row);
        });
    }

    // =================================================================
    // FUNGSI 9: Muat & Render Statistik Ringkasan Kelas
    // Dikerjakan oleh: Jehan Reza Pahlevi (NPM. 2415061067)
    // =================================================================
    async function muatRingkasanKelas(idPenugasan) {
        try {
            const data = await apiRequest(`api/kalkulasi.php?action=ringkasan&id_penugasan=${idPenugasan}`);

            if (data && data.sudah_dihitung) {
                statRataKelas.textContent     = Number(data.rata_rata_kelas).toFixed(2);
                statNilaiMax.textContent      = Number(data.nilai_tertinggi).toFixed(2);
                statNilaiMin.textContent      = Number(data.nilai_terendah).toFixed(2);
                statKelengkapan.textContent   = `${data.persen_lengkap}%`;

                // Hitung predikat rata-rata
                let predikatRata = 'E';
                if (data.rata_rata_kelas >= 85) predikatRata = 'A';
                else if (data.rata_rata_kelas >= 75) predikatRata = 'B';
                else if (data.rata_rata_kelas >= 65) predikatRata = 'C';
                else if (data.rata_rata_kelas >= 55) predikatRata = 'D';

                statPredikatKelas.textContent = `Predikat rata-rata: ${predikatRata}`;
                statSiswaLengkap.textContent  = `${data.persen_lengkap}% siswa nilai lengkap`;
            } else {
                statRataKelas.textContent     = '—';
                statNilaiMax.textContent      = '—';
                statNilaiMin.textContent      = '—';
                statKelengkapan.textContent   = '—';
                statPredikatKelas.textContent = 'Klik "Hitung nilai akhir"';
                statSiswaLengkap.textContent  = 'Belum ada rekap tersimpan';
            }
        } catch (error) {
            // Abaikan error non-kritis ringkasan
            statRataKelas.textContent = '—';
        }
    }

    // =================================================================
    // FUNGSI 10: Live Filter Pencarian Siswa (Real-time DOM)
    // Dikerjakan oleh: Jehan Reza Pahlevi (NPM. 2415061067)
    // =================================================================
    function handlePencarianSiswa() {
        const keyword = inputSearchSiswa.value.toLowerCase().trim();
        if (!keyword) {
            renderTabelRekap(dataSiswaRekap);
            return;
        }

        const filtered = dataSiswaRekap.filter(s =>
            (s.nama_siswa && s.nama_siswa.toLowerCase().includes(keyword)) ||
            (s.nisn && s.nisn.toLowerCase().includes(keyword))
        );

        renderTabelRekap(filtered);
    }

    // =================================================================
    // FUNGSI 11: Cetak Laporan (window.print())
    // Dikerjakan oleh: Jehan Reza Pahlevi (NPM. 2415061067)
    // =================================================================
    function handleCetakLaporan() {
        if (!penugasanAktif) {
            tampilkanNotifikasi('Silakan pilih penugasan mengajar sebelum mencetak laporan.', true);
            return;
        }

        // Tanggal TTD otomatis hari ini dalam bahasa Indonesia
        if (printTanggalTtd) {
            const options = { year: 'numeric', month: 'long', day: 'numeric' };
            printTanggalTtd.textContent = new Date().toLocaleDateString('id-ID', options);
        }

        // Tampilkan semua siswa (batalkan filter pencarian sebelum cetak)
        inputSearchSiswa.value = '';
        renderTabelRekap(dataSiswaRekap);

        // Panggil fungsi print bawaan browser
        window.print();
    }

    // Reset Tampilan Tabel
    function resetTabel() {
        bodyRekapNilai.innerHTML = '<tr><td colspan="11" class="empty">Silakan pilih penugasan mengajar terlebih dahulu.</td></tr>';
        countSiswaLaporan.textContent = '0 siswa';
        statRataKelas.textContent   = '—';
        statNilaiMax.textContent    = '—';
        statNilaiMin.textContent    = '—';
        statKelengkapan.textContent = '—';
    }

    // =================================================================
    // EVENT LISTENERS
    // Dikerjakan oleh: Jehan Reza Pahlevi (NPM. 2415061067)
    // =================================================================
    function pasangEventListener() {
        if (selectPenugasan) {
            selectPenugasan.addEventListener('change', handlePilihPenugasan);
        }

        // Live validation pada semua input bobot
        [inputBobotTugas, inputBobotKuis, inputBobotUts, inputBobotUas].forEach(inp => {
            if (inp) {
                inp.addEventListener('input', validasiTotalBobot);
            }
        });

        if (formBobot) {
            formBobot.addEventListener('submit', handleSimpanBobot);
        }

        if (btnHitungUlang) {
            btnHitungUlang.addEventListener('click', handleHitungUlang);
        }

        if (btnCetakLaporan) {
            btnCetakLaporan.addEventListener('click', handleCetakLaporan);
        }

        if (inputSearchSiswa) {
            inputSearchSiswa.addEventListener('input', handlePencarianSiswa);
        }
    }

    // Inisialisasi awal saat DOM siap
    document.addEventListener('DOMContentLoaded', () => {
        pasangEventListener();
        muatDaftarPenugasan();
    });

    // Jika script dimuat secara defer atau setelah DOMContentLoaded
    if (document.readyState === 'interactive' || document.readyState === 'complete') {
        pasangEventListener();
        muatDaftarPenugasan();
    }
})();

