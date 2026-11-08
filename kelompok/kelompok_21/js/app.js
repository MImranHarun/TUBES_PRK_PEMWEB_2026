// Fetch API menghubungkan halaman dengan endpoint PHP, seperti praktikum 06.
const page = document.body.dataset.page;
const isAdmin = document.body.dataset.role === 'admin';
const currentGuru = document.body.dataset.guru;
const csrf = document.querySelector('meta[name="csrf-token"]').content;
const labels = {
    nama_kelas: 'Nama kelas', tingkat: 'Tingkat', id_wali_kelas: 'Wali kelas',
    nisn: 'NISN', nama_siswa: 'Nama siswa', jenis_kelamin: 'Jenis kelamin', id_kelas: 'Kelas',
    kode_mapel: 'Kode mapel', nama_mapel: 'Mata pelajaran', tahun_ajaran: 'Tahun ajaran',
    semester: 'Semester', status_aktif: 'Status', id_guru: 'Guru', id_mapel: 'Mata pelajaran', id_periode: 'Periode'
};
const modules = {
    kelas: {title: 'kelas', pk: 'id_kelas', fields: ['nama_kelas', 'tingkat', 'id_wali_kelas']},
    siswa: {title: 'siswa', pk: 'id_siswa', fields: ['nisn', 'nama_siswa', 'jenis_kelamin', 'id_kelas']},
    mapel: {title: 'mata pelajaran', pk: 'id_mapel', fields: ['kode_mapel', 'nama_mapel']},
    periode: {title: 'periode', pk: 'id_periode', fields: ['tahun_ajaran', 'semester', 'status_aktif']},
    penugasan: {title: 'penugasan guru', pk: 'id_penugasan', fields: ['id_guru', 'id_kelas', 'id_mapel', 'id_periode']}
};
let rows = [];
let choices = {};
let editingId = null;
const writable = isAdmin || ['kelas', 'siswa'].includes(page);

async function api(url, method = 'GET', data = null) {
    const options = {method, headers: {'X-CSRF-Token': csrf}};
    if (data !== null) {
        options.headers['Content-Type'] = 'application/json';
        options.body = JSON.stringify(data);
    }
    const response = await fetch(url, options);
    if (response.status === 401) { location.href = 'login.php'; throw new Error('Sesi berakhir.'); }
    let result;
    try { result = await response.json(); }
    catch { throw new Error('Respons server tidak valid. Periksa instalasi PHP dan database.'); }
    if (!response.ok) throw new Error(result.message || 'Permintaan gagal.');
    return result.data;
}
function notice(message, error = false) {
    const box = document.getElementById('notice');
    box.hidden = false;
    box.className = 'message ' + (error ? 'error' : 'success');
    box.textContent = message;
}
function dataUrl(module) { return 'api/data.php?modul=' + module; }
function fieldText(field, value) {
    if (choices[field]) return choices[field].find(item => String(item.value) === String(value))?.label || 'Belum ditentukan';
    return value ?? '—';
}
async function loadChoices() {
    const [guru, kelas, mapel, periode] = await Promise.all([
        api('api/guru.php'), api(dataUrl('kelas')), api(dataUrl('mapel')), api(dataUrl('periode'))
    ]);
    choices = {
        id_guru: guru.map(row => ({value: row.id_guru, label: row.nama_guru})),
        id_wali_kelas: guru.map(row => ({value: row.id_guru, label: row.nama_guru})),
        id_kelas: kelas.map(row => ({value: row.id_kelas, label: row.nama_kelas})),
        id_mapel: mapel.map(row => ({value: row.id_mapel, label: row.nama_mapel})),
        id_periode: periode.map(row => ({value: row.id_periode, label: row.tahun_ajaran + ' · ' + row.semester})),
        jenis_kelamin: ['Laki-laki', 'Perempuan'].map(value => ({value, label: value})),
        semester: ['Ganjil', 'Genap'].map(value => ({value, label: value})),
        status_aktif: [{value: 1, label: 'Aktif'}, {value: 0, label: 'Tidak aktif'}]
    };
}
async function loadRows() {
    rows = await api(dataUrl(page));
    renderTable();
}
function renderTable() {
    const config = modules[page];
    const keyword = document.getElementById('search').value.toLowerCase();
    const filtered = rows.filter(row => config.fields.some(field => String(fieldText(field, row[field])).toLowerCase().includes(keyword)));
    const body = document.getElementById('table-body');
    body.replaceChildren();
    document.getElementById('row-count').textContent = filtered.length + ' data';
    if (!filtered.length) {
        const cell = document.createElement('td'); cell.colSpan = config.fields.length + 2;
        cell.className = 'empty'; cell.textContent = keyword ? 'Tidak ada data yang cocok.' : 'Belum ada data. Mulai dengan menambahkan data baru.';
        const row = document.createElement('tr'); row.append(cell); body.append(row);
    }
    filtered.forEach((item, index) => {
        const row = document.createElement('tr');
        [index + 1, ...config.fields.map(field => fieldText(field, item[field]))].forEach(value => {
            const cell = document.createElement('td'); cell.textContent = value; row.append(cell);
        });
        if (writable) {
            const cell = document.createElement('td'); cell.className = 'actions';
            const edit = document.createElement('button'); edit.textContent = 'Edit'; edit.onclick = () => openForm(item);
            const remove = document.createElement('button'); remove.textContent = 'Hapus'; remove.className = 'danger';
            remove.onclick = async () => {
                if (!confirm('Hapus data ini? Data yang masih digunakan tidak dapat dihapus.')) return;
                remove.disabled = true;
                try { await api(dataUrl(page) + '&id=' + item[config.pk], 'DELETE'); await loadChoices(); await loadRows(); notice('Data berhasil dihapus.'); }
                catch (error) { notice(error.message, true); remove.disabled = false; }
            };
            cell.append(edit, remove); row.append(cell);
        }
        body.append(row);
    });
}
function openForm(item = null) {
    const config = modules[page];
    editingId = item ? item[config.pk] : null;
    document.getElementById('dialog-title').textContent = (item ? 'Edit ' : 'Tambah ') + config.title;
    document.getElementById('form-error').textContent = '';
    const fields = document.getElementById('form-fields'); fields.replaceChildren();
    config.fields.forEach(field => {
        const label = document.createElement('label'); label.textContent = labels[field];
        let input;
        if (choices[field]) {
            input = document.createElement('select');
            const prompt = new Option('Pilih ' + labels[field].toLowerCase(), ''); input.add(prompt);
            let options = choices[field];
            // Wali lama tetap terlihat saat guru mapel mengedit kelas; backend menjaga kepemilikannya.
            if (field === 'id_wali_kelas' && !isAdmin && !item) options = options.filter(option => String(option.value) === currentGuru);
            options.forEach(option => input.add(new Option(option.label, option.value)));
        } else {
            input = document.createElement('input');
            input.maxLength = {nama_kelas: 50, tingkat: 20, nisn: 10, nama_siswa: 100, kode_mapel: 20, nama_mapel: 100, tahun_ajaran: 20}[field];
            if (field === 'nisn') { input.pattern = '[0-9]{10}'; input.inputMode = 'numeric'; }
            if (field === 'tahun_ajaran') input.placeholder = '2026/2027';
        }
        input.name = field; input.required = true;
        if (item) input.value = item[field] ?? '';
        else if (field === 'id_wali_kelas' && !isAdmin) input.value = currentGuru;
        label.append(input); fields.append(label);
    });
    document.getElementById('data-dialog').showModal();
}
async function initData() {
    await loadChoices();
    const config = modules[page];
    const head = document.createElement('tr');
    ['No.', ...config.fields.map(field => labels[field]), ...(writable ? ['Aksi'] : [])].forEach(text => {
        const cell = document.createElement('th'); cell.textContent = text; head.append(cell);
    });
    document.getElementById('table-head').append(head);
    document.getElementById('module-description').textContent = 'Daftar ' + config.title + ' yang terhubung dengan kegiatan akademik.';
    document.getElementById('access-note').textContent = writable ? 'Data tersimpan di database sekolah. Gunakan Edit untuk memperbarui informasi.' : 'Anda memiliki akses baca. Hubungi admin untuk perubahan data bersama.';
    const add = document.getElementById('add-button'); add.hidden = !writable; add.onclick = () => openForm();
    document.getElementById('search').addEventListener('input', renderTable);
    ['close-dialog', 'cancel-dialog'].forEach(id => document.getElementById(id).onclick = () => document.getElementById('data-dialog').close());
    document.getElementById('data-form').onsubmit = async event => {
        event.preventDefault();
        const button = event.target.querySelector('[type="submit"]'); button.disabled = true;
        try {
            const data = Object.fromEntries(new FormData(event.target));
            const url = dataUrl(page) + (editingId ? '&id=' + editingId : '');
            await api(url, editingId ? 'PUT' : 'POST', data);
            document.getElementById('data-dialog').close();
            await loadChoices(); await loadRows(); notice('Data berhasil disimpan.');
        } catch (error) { document.getElementById('form-error').textContent = error.message; }
        finally { button.disabled = false; }
    };
    await loadRows();
}
async function initDashboard() {
    await Promise.all(['kelas', 'siswa', 'mapel', 'penugasan'].map(async module => {
        const data = await api(dataUrl(module)); document.getElementById('count-' + module).textContent = data.length;
    }));
    const periods = await api(dataUrl('periode')); const active = periods.find(row => Number(row.status_aktif) === 1);
    document.getElementById('active-period').textContent = active ? active.tahun_ajaran + ' · Semester ' + active.semester : 'Belum ada periode aktif.';
}
async function initProfile() {
    const profile = await api('api/profil.php'); const form = document.getElementById('profile-form');
    ['nama_guru', 'nip', 'email'].forEach(field => form.elements[field].value = profile[field] ?? '');
    form.onsubmit = async event => {
        event.preventDefault(); const button = form.querySelector('button'); button.disabled = true;
        try {
            await api('api/profil.php', 'PUT', Object.fromEntries(new FormData(form)));
            form.elements.password_lama.value = ''; form.elements.password_baru.value = '';
            notice('Profil berhasil disimpan. Nama pada header diperbarui saat halaman dimuat ulang.');
        } catch (error) { notice(error.message, true); }
        finally { button.disabled = false; }
    };
}
(async function () {
    try {
        if (page === 'beranda') await initDashboard();
        else if (page === 'profil') await initProfile();
        else await initData();
    } catch (error) {
        notice(error.message, true);
        const body = document.getElementById('table-body');
        if (body) body.textContent = 'Data gagal dimuat. Muat ulang setelah memperbaiki koneksi.';
    }
})();
