<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$pageTitle = 'OneBlood - Sistem Informasi Donor Darah';
require_once __DIR__ . '/includes/header.php';
?>

<main>
    <section class="hero-wrapper">
        <div class="hero-container">
            <div class="hero-content">
                <h1 class="hero-title">Selamat Datang di <span class="highlight-text">One</span>Blood<br>
                    Sistem Donor Darah Terpadu</h1>
                <p class="hero-desc">Sistem Donor Darah Terpadu OneBlood merupakan Sistem Donor Darah
                    yang memudahkan masyarakat untuk mendapatkan informasi terkait donor darah,
                    stok darah, dan kegiatan donor darah di sekitar mereka.</p>
                <div class="hero-buttons">
                    <a href="<?= e(base_url('pendonor/dashboard.php')) ?>" class="btn-pink">Donor Sekarang</a>
                    <a href="#" class="btn-pink">Selengkapnya</a>
                </div>
            </div>
            <div class="hero-image">
                <img src="<?= e(base_url('assets/donor1.jpg')) ?>" alt="Ilustrasi Donor Darah">
            </div>
        </div>
        <div class="sliders-dots">
            <span class="dot active"></span>
            <span class="dot"></span>
        </div>
    </section>
    <section class="stokdarah" id="stokdarah">
        <div class="stokdarah-container">
            <div class="stokdarah-title">
                <h1>UPDATE STOK DARAH</h1>
                <p id="stokdarah-update" role="status" aria-live="polite">Memuat stok terbaru...</p>
            </div>
            <div class="stokdarah-content">
                <div class="blood-grid">
                    <div class="blood-card" data-golongan="A">
                        <div class="blood-icon">A</div>
                        <div class="blood-info">
                            <h4>Stok Darah A</h4>
                            <p class="status-loading" data-stok-status>Memuat...</p>
                        </div>
                    </div>
                    <div class="blood-card" data-golongan="B">
                        <div class="blood-icon">B</div>
                        <div class="blood-info">
                            <h4>Stok Darah B</h4>
                            <p class="status-loading" data-stok-status>Memuat...</p>
                        </div>
                    </div>
                    <div class="blood-card" data-golongan="O">
                        <div class="blood-icon">O</div>
                        <div class="blood-info">
                            <h4>Stok Darah O</h4>
                            <p class="status-loading" data-stok-status>Memuat...</p>
                        </div>
                    </div>
                    <div class="blood-card" data-golongan="AB">
                        <div class="blood-icon">AB</div>
                        <div class="blood-info">
                            <h4>Stok Darah AB</h4>
                            <p class="status-loading" data-stok-status>Memuat...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <section class="butuhdarah" id="butuhdarah">
        <div class="butuhdarah-title">
            <h1>Kebutuhan Darah <span class="butuhdarah-title-highlight"> Urgent !</span></h1>
            <p>Mohon bantuan kepada seluruh Pendonor dan Relawan untuk dapat memenuhi kebutuhan darah mendesak. Silahkan menghubungi kontak person yang tersedia untuk berkoordinasi langsung dengan pihak Keluarga.</p>
            <button class="butuhdarah-ajukan" type="button" id="open-blood-request">Ajukan Darah Darurat</button>
        </div>
        <?php if (($_GET['pengajuan'] ?? '') === 'terkirim'): ?>
            <p class="butuhdarah-feedback" role="status">Pengajuan berhasil dikirim dan akan ditinjau petugas sebelum ditampilkan.</p>
        <?php elseif (($_GET['pengajuan'] ?? '') === 'gagal'): ?>
            <p class="butuhdarah-feedback error" role="alert">Pengajuan belum berhasil dikirim. Silakan periksa data dan coba lagi.</p>
        <?php endif; ?>
        <div class="butuhdarah-carousel" id="butuhdarah-carousel">
            <div class="butuhdarah-track" id="butuhdarah-track" aria-live="polite">
                <p class="butuhdarah-message">Memuat kebutuhan darah...</p>
            </div>
            <div class="butuhdarah-dots" id="butuhdarah-dots" aria-label="Navigasi kebutuhan darah"></div>
        </div>
        <dialog class="blood-request-dialog" id="blood-request-dialog" aria-labelledby="blood-request-title">
            <form class="blood-request-form" action="<?= e(base_url('api/ajukan_darah_darurat.php')) ?>" method="post">
                <div class="blood-request-heading">
                    <div>
                        <p class="emergency-eyebrow">PENGAJUAN PUBLIK</p>
                        <h2 id="blood-request-title">Ajukan Darah Darurat</h2>
                        <p>Pengajuan akan ditinjau petugas sebelum dipublikasikan.</p>
                    </div>
                    <button class="blood-request-close" type="button" id="close-blood-request" aria-label="Tutup form">&times;</button>
                </div>
                <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">

                <div class="blood-request-field">
                    <label for="request-title">Judul pengumuman</label>
                    <input id="request-title" name="judul_pesan" maxlength="150" placeholder="Contoh: Dibutuhkan pendonor darah segera" required>
                </div>
                <div class="blood-request-field">
                    <label for="request-blood-type">Golongan darah dan Rhesus</label>
                    <div class="blood-request-type-fields">
                        <select id="request-blood-type" name="gol_darah" required>
                            <option value="">Pilih golongan darah</option>
                            <option value="A">A</option>
                            <option value="B">B</option>
                            <option value="AB">AB</option>
                            <option value="O">O</option>
                        </select>
                        <select name="rhesus" aria-label="Rhesus" required>
                            <option value="+">Positif (+)</option>
                            <option value="-">Negatif (-)</option>
                        </select>
                    </div>
                </div>
                <div class="blood-request-field">
                    <label for="request-hospital">Rumah sakit peminta</label>
                    <input id="request-hospital" name="rs_peminta" maxlength="150" required>
                </div>
                <div class="blood-request-field">
                    <label for="request-units">Jumlah kantong</label>
                    <input id="request-units" type="number" name="kebutuhan_kantong" min="1" max="100000" required>
                </div>
                <div class="blood-request-field">
                    <label for="request-contact">Kontak darurat</label>
                    <input id="request-contact" name="kontak_darurat" maxlength="30" placeholder="Nomor telepon yang bisa dihubungi" required>
                </div>
                <div class="blood-request-actions">
                    <button class="blood-request-cancel" type="button" id="cancel-blood-request">Batal</button>
                    <button class="blood-request-submit" type="submit">Kirim Pengajuan</button>
                </div>
            </form>
        </dialog>
    </section>
    <section class="kelayakan" id="kelayakan">
        <div class="eligibility-container">
            <div class="eligibility-intro">
                <span class="eligibility-eyebrow">LANGKAH KECIL, DAMPAK BESAR</span>
                <h2>Cek kesiapan donor darah</h2>
                <p>Jawab beberapa pertanyaan singkat untuk mengetahui apakah kamu tampak siap untuk melanjutkan ke pemeriksaan donor.</p>
                <div class="eligibility-disclaimer">
                    <span aria-hidden="true">i</span>
                    <p>Hasil ini hanya panduan awal, bukan keputusan medis. Petugas donor tetap menentukan kelayakan setelah pemeriksaan langsung.</p>
                </div>
            </div>

            <form class="eligibility-card" id="eligibility-form">
                <div class="eligibility-field-grid">
                    <div class="eligibility-field">
                        <label for="eligibility-age">Usia</label>
                        <div class="eligibility-input-wrap">
                            <input id="eligibility-age" name="age" type="number" min="1" max="120" step="1" placeholder="Contoh: 20" required>
                            <span>tahun</span>
                        </div>
                    </div>
                    <div class="eligibility-field">
                        <label for="eligibility-weight">Berat badan</label>
                        <div class="eligibility-input-wrap">
                            <input id="eligibility-weight" name="weight" type="number" min="1" max="300" step="0.1" placeholder="Contoh: 55" required>
                            <span>kg</span>
                        </div>
                    </div>
                </div>

                <fieldset class="eligibility-choice">
                    <legend>Kapan terakhir kali kamu donor?</legend>
                    <div class="eligibility-radio-list">
                        <label><input type="radio" name="last-donation" value="never" required><span>Belum pernah donor</span></label>
                        <label><input type="radio" name="last-donation" value="under-two-months"><span>Kurang dari 2 bulan lalu</span></label>
                        <label><input type="radio" name="last-donation" value="two-months-or-more"><span>2 bulan lalu atau lebih</span></label>
                    </div>
                </fieldset>

                <fieldset class="eligibility-choice">
                    <legend>Bagaimana kondisi tubuhmu hari ini?</legend>
                    <div class="eligibility-radio-list eligibility-radio-inline">
                        <label><input type="radio" name="feeling-well" value="yes" required><span>Sehat dan merasa bugar</span></label>
                        <label><input type="radio" name="feeling-well" value="no"><span>Sedang kurang sehat</span></label>
                    </div>
                </fieldset>

                <button class="eligibility-submit" type="submit">Periksa kesiapan saya</button>
                <div class="eligibility-result" id="eligibility-result" role="status" aria-live="polite" tabindex="-1" hidden></div>
            </form>
        </div>
    </section>
    <script>
        const eligibilityForm = document.getElementById('eligibility-form');
        const eligibilityResult = document.getElementById('eligibility-result');

        eligibilityForm.addEventListener('submit', (event) => {
            event.preventDefault();
            if (!eligibilityForm.reportValidity()) {
                return;
            }

            const formData = new FormData(eligibilityForm);
            const age = Number(formData.get('age'));
            const weight = Number(formData.get('weight'));
            const lastDonation = formData.get('last-donation');
            const feelingWell = formData.get('feeling-well');
            const reasons = [];

            if (age < 17 || age > 60) {
                reasons.push('Panduan umum donor darah mencantumkan rentang usia 17–60 tahun.');
            }
            if (weight < 45) {
                reasons.push('Berat badan umumnya perlu minimal 45 kg.');
            }
            if (lastDonation === 'under-two-months') {
                reasons.push('Jarak donor berikutnya umumnya minimal 2 bulan.');
            }
            if (feelingWell !== 'yes') {
                reasons.push('Tunda donor saat sedang kurang sehat dan konsultasikan dengan petugas.');
            }

            eligibilityResult.replaceChildren();
            eligibilityResult.className = `eligibility-result ${reasons.length === 0 ? 'is-ready' : 'needs-attention'}`;

            const heading = document.createElement('strong');
            heading.textContent = reasons.length === 0
                ? 'Kamu tampak siap untuk pemeriksaan lanjutan.'
                : 'Sebaiknya tunda dulu atau konsultasikan dengan petugas.';
            eligibilityResult.append(heading);

            const explanation = document.createElement('p');
            explanation.textContent = reasons.length === 0
                ? 'Jawabanmu memenuhi beberapa panduan umum. Ini belum memastikan kamu dapat mendonor; petugas tetap akan memeriksa kondisi dan kesehatanmu.'
                : 'Perhatikan hal berikut berdasarkan jawabanmu:';
            eligibilityResult.append(explanation);

            if (reasons.length > 0) {
                const list = document.createElement('ul');
                reasons.forEach((reason) => {
                    const item = document.createElement('li');
                    item.textContent = reason;
                    list.append(item);
                });
                eligibilityResult.append(list);
            }

            eligibilityResult.hidden = false;
            eligibilityResult.focus();
        });

        const stokEndpoint = <?= json_encode(
            base_url('api/stok_darah.php'),
            JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
        ) ?>;
        const stokCards = document.querySelectorAll('.blood-card[data-golongan]');
        const stokUpdate = document.getElementById('stokdarah-update');
        let stokRequestAktif = false;
        let stokPernahDimuat = false;

        async function muatStokDarah() {
            if (stokRequestAktif) {
                return;
            }

            stokRequestAktif = true;
            try {
                const response = await fetch(stokEndpoint, {
                    cache: 'no-store',
                    headers: { Accept: 'application/json' }
                });
                if (!response.ok) {
                    throw new Error(`Permintaan stok gagal (${response.status}).`);
                }

                const result = await response.json();
                if (!result.success || !result.data) {
                    throw new Error(result.message || 'Respons stok tidak valid.');
                }

                const stok = Array.from(stokCards, (card) => {
                    const golongan = card.dataset.golongan;
                    const jumlah = result.data[golongan];
                    if (!Number.isInteger(jumlah) || jumlah < 0) {
                        throw new Error(`Jumlah stok golongan ${golongan} tidak valid.`);
                    }
                    return { card, jumlah };
                });

                stok.forEach(({ card, jumlah }) => {
                    const status = card.querySelector('[data-stok-status]');
                    status.textContent = jumlah > 0
                        ? `${jumlah} kantong tersedia`
                        : 'Belum tersedia';
                    status.classList.toggle('status-available', jumlah > 0);
                    status.classList.toggle('status-empty', jumlah === 0);
                    status.classList.remove('status-loading', 'status-error');
                });

                const waktuUpdate = new Intl.DateTimeFormat('id-ID', {
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit'
                }).format(new Date());
                stokUpdate.textContent = `Diperbarui pukul ${waktuUpdate}`;
                stokPernahDimuat = true;
            } catch (error) {
                console.error('Gagal memperbarui stok darah.', error);
                stokUpdate.textContent = 'Stok gagal dimuat. Akan mencoba kembali.';
                if (!stokPernahDimuat) {
                    stokCards.forEach((card) => {
                        const status = card.querySelector('[data-stok-status]');
                        status.textContent = 'Gagal dimuat';
                        status.classList.remove('status-loading');
                        status.classList.add('status-error');
                    });
                }
            } finally {
                stokRequestAktif = false;
            }
        }

        muatStokDarah();
        window.setInterval(muatStokDarah, 30000);

        const kebutuhanEndpoint = <?= json_encode(
            base_url('api/kebutuhan_darah.php'),
            JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
        ) ?>;
        const kebutuhanAssetBase = <?= json_encode(
            base_url('assets/tipedarah/'),
            JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
        ) ?>;
        const ikonGolonganDarah = {
            'A+': 'a+.svg',
            'A-': 'a-.svg',
            'B+': 'b+.svg',
            'B-': 'b-.svg',
            'AB+': 'ab+.svg',
            'AB-': 'ab-.svg',
            'O+': 'o+.svg',
            'O-': 'o-.svg'
        };
        const kebutuhanTrack = document.getElementById('butuhdarah-track');
        const kebutuhanDots = document.getElementById('butuhdarah-dots');
        const bloodRequestDialog = document.getElementById('blood-request-dialog');
        document.getElementById('open-blood-request').addEventListener('click', () => bloodRequestDialog.showModal());
        document.getElementById('close-blood-request').addEventListener('click', () => bloodRequestDialog.close());
        document.getElementById('cancel-blood-request').addEventListener('click', () => bloodRequestDialog.close());
        let kebutuhanHalaman = 0;
        let kebutuhanRequestAktif = false;

        function buatKartuKebutuhan(kebutuhan) {
            const kartu = document.createElement('article');
            kartu.className = 'butuhdarah-card';

            const golongan = document.createElement('div');
            golongan.className = 'butuhdarah-icon';
            const tipeDarah = `${String(kebutuhan.gol_darah || '').trim().toUpperCase()}${String(kebutuhan.rhesus || '').trim()}`;
            const namaIkon = ikonGolonganDarah[tipeDarah];
            if (namaIkon) {
                const gambarGolongan = document.createElement('img');
                gambarGolongan.src = `${kebutuhanAssetBase}${namaIkon}`;
                gambarGolongan.alt = `Golongan darah ${tipeDarah}`;
                gambarGolongan.loading = 'lazy';
                golongan.append(gambarGolongan);
            } else {
                golongan.textContent = tipeDarah || 'Golongan darah';
            }

            const info = document.createElement('div');
            info.className = 'butuhdarah-info';

            const judul = document.createElement('h2');
            judul.textContent = kebutuhan.judul_pesan;

            const rumahSakit = document.createElement('p');
            rumahSakit.className = 'butuhdarah-rumah-sakit';
            rumahSakit.textContent = kebutuhan.rs_peminta;

            const jumlah = document.createElement('p');
            jumlah.className = 'butuhdarah-jumlah';
            jumlah.textContent = `${kebutuhan.kebutuhan_kantong} Kantong`;

            const nomorTampil = document.createElement('p');
            nomorTampil.className = 'butuhdarah-nomor';
            nomorTampil.textContent = kebutuhan.kontak_darurat;

            const tombolKontak = document.createElement('div');
            tombolKontak.className = 'butuhdarah-kontak';

            const nomorTelepon = kebutuhan.kontak_darurat.replace(/[^\d+]/g, '');
            const nomorWhatsapp = kebutuhan.kontak_darurat.replace(/\D/g, '');

            const telepon = document.createElement('a');
            telepon.className = 'butuhdarah-telepon';
            telepon.href = `tel:${nomorTelepon}`;
            telepon.textContent = 'Telepon';
            telepon.setAttribute('aria-label', `Telepon ${kebutuhan.judul_pesan}`);

            const whatsapp = document.createElement('a');
            whatsapp.className = 'butuhdarah-whatsapp';
            whatsapp.href = `https://wa.me/${nomorWhatsapp}`;
            whatsapp.target = '_blank';
            whatsapp.rel = 'noopener noreferrer';
            whatsapp.textContent = 'WhatsApp';
            whatsapp.setAttribute('aria-label', `WhatsApp ${kebutuhan.judul_pesan}`);

            tombolKontak.append(telepon, whatsapp);
            info.append(judul, rumahSakit, jumlah, nomorTampil, tombolKontak);
            kartu.append(golongan, info);
            return kartu;
        }

        function tampilkanKebutuhan(data) {
            kebutuhanTrack.replaceChildren();
            kebutuhanDots.replaceChildren();

            if (data.length === 0) {
                const pesan = document.createElement('p');
                pesan.className = 'butuhdarah-message';
                pesan.textContent = 'Belum ada kebutuhan darah aktif.';
                kebutuhanTrack.append(pesan);
                return;
            }

            const jumlahPerHalaman = 2;
            const halaman = [];
            for (let i = 0; i < data.length; i += jumlahPerHalaman) {
                halaman.push(data.slice(i, i + jumlahPerHalaman));
            }

            halaman.forEach((items, index) => {
                const slide = document.createElement('div');
                slide.className = 'butuhdarah-slide';
                items.forEach((item) => slide.append(buatKartuKebutuhan(item)));
                kebutuhanTrack.append(slide);

                const dot = document.createElement('button');
                dot.className = 'butuhdarah-dot';
                dot.type = 'button';
                dot.setAttribute('aria-label', `Tampilkan halaman ${index + 1}`);
                dot.addEventListener('click', () => {
                    kebutuhanHalaman = index;
                    perbaruiHalamanKebutuhan();
                });
                kebutuhanDots.append(dot);
            });

            kebutuhanHalaman = Math.min(kebutuhanHalaman, halaman.length - 1);
            perbaruiHalamanKebutuhan();
        }

        function perbaruiHalamanKebutuhan() {
            const slide = kebutuhanTrack.querySelector('.butuhdarah-slide');
            if (!slide) {
                return;
            }

            kebutuhanTrack.style.transform = `translateX(-${kebutuhanHalaman * 100}%)`;
            kebutuhanDots.querySelectorAll('.butuhdarah-dot').forEach((dot, index) => {
                const aktif = index === kebutuhanHalaman;
                dot.classList.toggle('active', aktif);
                dot.setAttribute('aria-current', aktif ? 'true' : 'false');
            });
        }

        async function muatKebutuhanDarah() {
            if (kebutuhanRequestAktif) {
                return;
            }

            kebutuhanRequestAktif = true;
            try {
                const response = await fetch(kebutuhanEndpoint, {
                    cache: 'no-store',
                    headers: { Accept: 'application/json' }
                });
                if (!response.ok) {
                    throw new Error(`Permintaan kebutuhan darah gagal (${response.status}).`);
                }

                const result = await response.json();
                if (!result.success || !Array.isArray(result.data)) {
                    throw new Error(result.message || 'Respons kebutuhan darah tidak valid.');
                }

                tampilkanKebutuhan(result.data);
            } catch (error) {
                console.error('Gagal memperbarui kebutuhan darah.', error);
                if (!kebutuhanTrack.querySelector('.butuhdarah-slide')) {
                    const pesan = document.createElement('p');
                    pesan.className = 'butuhdarah-message';
                    pesan.textContent = 'Kebutuhan darah gagal dimuat. Silakan coba beberapa saat lagi.';
                    kebutuhanTrack.replaceChildren(pesan);
                }
            } finally {
                kebutuhanRequestAktif = false;
            }
        }

        muatKebutuhanDarah();
        window.setInterval(muatKebutuhanDarah, 30000);
    </script>
</main>

<?php
$hideSidebar = true;
require_once __DIR__ . '/includes/footer.php';
?>
