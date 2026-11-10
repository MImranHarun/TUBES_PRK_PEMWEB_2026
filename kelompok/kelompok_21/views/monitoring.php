<?php ?>
<div id="mon-root">
    <div class="page-heading">
        <div>
            <p class="eyebrow">MONITORING TUGAS</p>
            <h1>Pengumpulan tugas</h1>
            <p>Pantau siapa yang tepat waktu, terlambat, atau belum mengumpulkan.</p>
        </div>
    </div>
    <div id="mon-pesan" class="message" hidden></div>
    <section class="panel">
        <div class="table-toolbar">
            <label class="search-label">Penugasan <select id="mon-penugasan"></select></label>
            <label class="search-label">Tugas <select id="mon-tugas"></select></label>
            <span id="mon-ringkasan" class="pill">Memuat…</span>
        </div>
        <div class="table-scroll">
            <table>
                <thead>
                    <tr><th>Siswa</th><th>Waktu kumpul</th><th>Status</th><th>Indeks</th><th>Aksi</th></tr>
                </thead>
                <tbody id="mon-body"><tr><td colspan="5" class="empty">Memuat data…</td></tr></tbody>
            </table>
        </div>
        <p class="table-note">Indeks kedisiplinan: tepat waktu 100, terlambat 50, belum mengumpulkan 0. Tugas yang belum jatuh tempo tidak dihitung.</p>
    </section>
    <dialog id="mon-dialog">
        <div class="dialog-heading">
            <h2 id="mon-dialog-judul">Riwayat</h2>
            <button type="button" id="mon-x" aria-label="Tutup">×</button>
        </div>
        <div id="mon-dialog-isi"></div>
        <div class="form-actions"><button type="button" id="mon-tutup">Tutup</button></div>
    </dialog>
</div>
<style>
    #mon-root .search-label { margin-bottom: 0; }
    #mon-root td input { margin-top: 0; padding: 7px 9px; width: auto; min-width: 190px; }
    #mon-root .mon-tepat { background: #e1f0e5; color: #215c38; }
    #mon-root .mon-terlambat { background: #f8eccb; color: #7a5a14; }
    #mon-root .mon-belum { background: #fae8e4; color: #983f37; }
    #mon-root .mon-tempo { background: #edf3e8; color: #587150; }
    #mon-root ul { padding-left: 18px; margin: 0; }
    #mon-root li { margin-bottom: 8px; }
</style>