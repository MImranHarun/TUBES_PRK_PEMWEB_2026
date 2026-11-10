// Modul 3: Monitoring Pengumpulan Tugas (Dwi Septy). Berdiri sendiri, tidak memakai fungsi app.js.
(function () {
    const API = 'api/monitoring.php';
    const root = document.getElementById('mon-root');
    if (!root) return;

    const $ = (id) => document.getElementById(id);
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    let bisaUbah = false;

    const esc = (t) => String(t ?? '').replace(/[&<>"']/g,
        (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const kelasBadge = (s) => ({ 'Tepat waktu': 'mon-tepat', 'Terlambat': 'mon-terlambat',
        'Belum mengumpulkan': 'mon-belum', 'Belum jatuh tempo': 'mon-tempo' }[s] || 'mon-tempo');
    const keInput = (w) => (w ? w.replace(' ', 'T').slice(0, 16) : '');

    async function panggil(url, opsi = {}) {
        const res = await fetch(url, {
            ...opsi,
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
        });
        let json = null;
        try { json = await res.json(); } catch (e) { /* bukan JSON */ }
        if (res.status === 401) { window.location.href = 'login.php'; throw new Error('Sesi habis.'); }
        if (!res.ok || !json || json.status !== 'success') {
            throw new Error((json && json.message) || 'Terjadi kesalahan.');
        }
        return json.data;
    }

    let timerPesan;
    function pesan(teks, jenis = 'error') {
        const el = $('mon-pesan');
        el.className = 'message ' + (jenis === 'success' ? 'success' : 'error');
        el.textContent = teks;
        el.hidden = false;
        clearTimeout(timerPesan);
        timerPesan = setTimeout(() => { el.hidden = true; }, 4000);
    }

    async function muatPenugasan() {
        const data = await panggil(`${API}?aksi=penugasan`);
        const sel = $('mon-penugasan');
        sel.innerHTML = data.length
            ? data.map((p) => `<option value="${p.id_penugasan}">${esc(p.nama_kelas)} · ${esc(p.nama_mapel)} (${esc(p.tahun_ajaran)} ${esc(p.semester)})</option>`).join('')
            : '<option value="">Tidak ada penugasan</option>';
        if (data.length) await muatTugas();
        else $('mon-body').innerHTML = '<tr><td colspan="5" class="empty">Anda belum punya penugasan mengajar.</td></tr>';
    }

    async function muatTugas() {
        const id = $('mon-penugasan').value;
        const data = await panggil(`${API}?aksi=tugas&id_penugasan=${encodeURIComponent(id)}`);
        const sel = $('mon-tugas');
        sel.innerHTML = data.length
            ? data.map((t) => `<option value="${t.id_penilaian}">${esc(t.judul_penilaian)} (tenggat ${esc(t.tenggat_waktu)})</option>`).join('')
            : '<option value="">Belum ada tugas</option>';
        if (data.length) await muatDaftar();
        else {
            $('mon-body').innerHTML = '<tr><td colspan="5" class="empty">Belum ada tugas pada penugasan ini.</td></tr>';
            $('mon-ringkasan').textContent = '0 tugas';
        }
    }

    async function muatDaftar() {
        const id = $('mon-tugas').value;
        const d = await panggil(`${API}?aksi=daftar&id_penilaian=${encodeURIComponent(id)}`);
        bisaUbah = d.bisa_ubah;
        const r = d.ringkasan;
        $('mon-ringkasan').textContent =
            `Tepat ${r['Tepat waktu']} · Terlambat ${r['Terlambat']} · Belum ${r['Belum mengumpulkan']} · Belum jatuh tempo ${r['Belum jatuh tempo']}`;
        $('mon-body').innerHTML = d.siswa.map((s) => `
            <tr data-id="${s.id_siswa}">
                <td>${esc(s.nama_siswa)}<br><small>${esc(s.nisn)}</small></td>
                <td><input type="datetime-local" value="${keInput(s.waktu_pengumpulan)}" ${bisaUbah ? '' : 'disabled'}></td>
                <td><span class="pill ${kelasBadge(s.status)}">${esc(s.status)}</span></td>
                <td>${s.indeks === null ? '-' : s.indeks}</td>
                <td><div class="actions">
                    ${bisaUbah ? '<button type="button" data-aksi="simpan">Simpan</button><button type="button" class="danger" data-aksi="kosong">Kosongkan</button>' : ''}
                    <button type="button" data-aksi="riwayat">Riwayat</button>
                </div></td>
            </tr>`).join('');
    }

    async function simpan(idSiswa, nilaiInput) {
        const waktu = nilaiInput ? nilaiInput.replace('T', ' ') + ':00' : '';
        await panggil(API + '?aksi=catat', {
            method: 'POST',
            body: JSON.stringify({ id_penilaian: Number($('mon-tugas').value), id_siswa: idSiswa, waktu_pengumpulan: waktu }),
        });
        await muatDaftar();
        pesan('Pengumpulan tersimpan.', 'success');
    }

    async function lihatRiwayat(idSiswa, nama) {
        const data = await panggil(`${API}?aksi=riwayat&id_penilaian=${encodeURIComponent($('mon-tugas').value)}&id_siswa=${idSiswa}`);
        $('mon-dialog-judul').textContent = 'Riwayat: ' + nama;
        $('mon-dialog-isi').innerHTML = data.length
            ? '<ul>' + data.map((h) => `<li>${esc(h.waktu_ubah)}: ${esc(h.status_lama || '-')} → ${esc(h.status_baru)} (oleh ${esc(h.nama_guru || '-')})</li>`).join('') + '</ul>'
            : '<p>Belum ada perubahan tercatat.</p>';
        $('mon-dialog').showModal();
    }

    $('mon-penugasan').addEventListener('change', () => muatTugas().catch((e) => pesan(e.message)));
    $('mon-tugas').addEventListener('change', () => muatDaftar().catch((e) => pesan(e.message)));
    $('mon-tutup').addEventListener('click', () => $('mon-dialog').close());
    $('mon-x').addEventListener('click', () => $('mon-dialog').close());
    $('mon-body').addEventListener('click', (ev) => {
        const tombol = ev.target.closest('button[data-aksi]');
        if (!tombol) return;
        const baris = tombol.closest('tr');
        const idSiswa = Number(baris.dataset.id);
        const aksi = tombol.dataset.aksi;
        const jalankan = aksi === 'simpan' ? simpan(idSiswa, baris.querySelector('input').value)
            : aksi === 'kosong' ? simpan(idSiswa, '')
            : lihatRiwayat(idSiswa, baris.querySelector('td').firstChild.textContent);
        jalankan.catch((e) => pesan(e.message));
    });

    muatPenugasan().catch((e) => {
        $('mon-body').innerHTML = `<tr><td colspan="5" class="empty">${esc(e.message)}</td></tr>`;
    });
})();