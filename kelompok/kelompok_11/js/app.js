function loadPage(page) {
    const c = document.getElementById('app-content');
    c.innerHTML = '';
    c.className = 'fade-in';
    setTimeout(() => c.classList.remove('fade-in'), 400);
    if (page === 'beranda') renderBeranda(c);
    else if (page === 'hoaks') renderHoaks(c);
    else if (page === 'aspirasi') renderAspirasi(c);
    else if (page === 'petisi') renderPetisi(c);
}

document.addEventListener('DOMContentLoaded', () => loadPage('beranda'));

function renderBeranda(contentArea) {
    contentArea.innerHTML = `
        <header class="mb-6 pb-4 border-b-2 border-slate-900 flex flex-col md:flex-row md:items-end justify-between gap-4">
            <div>
                <span class="text-xs font-semibold text-blue-900 uppercase tracking-widest block mb-1">🏛️ Pusat Pemantauan Isu Publik</span>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Kabar Kebijakan & Isu Pemerintahan</h1>
                <p class="text-slate-600 text-xs mt-1">Update dinamika regulasi dan kebijakan publik dari kantor berita resmi nasional.</p>
            </div>
            <div class="flex items-center gap-3 bg-slate-100 px-3.5 py-2 rounded-lg border border-slate-200 text-xs text-slate-700">
                <div><span class="block text-slate-500">Total Berita</span><span id="total-isu-badge" class="font-bold text-slate-900">50 Berita</span></div>
                <div class="h-5 w-px bg-slate-300"></div>
                <div><span class="block text-slate-500">Portal Media</span><span id="total-sumber-badge" class="font-bold text-slate-900">4 Sumber</span></div>
            </div>
        </header>

        <section class="mb-6">
            <form id="form-search" class="bg-white p-3.5 rounded-lg border border-slate-200 shadow-sm space-y-2.5">
                <div class="flex flex-col md:flex-row gap-2">
                    <input type="text" id="input-search" placeholder="Ketik topik (contoh: DPR, Mahkamah, Bansos, IKN)..." class="flex-1 px-3 py-1.5 rounded-md border border-slate-300 text-sm focus:outline-none focus:border-blue-900" autocomplete="off">
                    <select id="select-source" class="w-full md:w-48 px-3 py-1.5 rounded-md border border-slate-300 text-sm bg-white focus:outline-none focus:border-blue-900">
                        <option value="all">Semua Portal Berita</option>
                        <option value="Antara News">Antara News</option>
                        <option value="CNN Indonesia">CNN Indonesia</option>
                        <option value="CNBC Indonesia">CNBC Indonesia</option>
                    </select>
                    <div class="flex gap-1.5">
                        <button type="submit" id="btn-search" class="bg-blue-900 hover:bg-blue-800 text-white font-medium px-4 py-1.5 rounded-md text-sm transition">Cari</button>
                        <button type="button" id="btn-reset" class="bg-slate-100 hover:bg-slate-200 text-slate-700 font-medium px-3 py-1.5 rounded-md text-sm transition">Reset</button>
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-1.5 pt-2 border-t border-slate-100 text-xs">
                    <span class="text-slate-500 font-medium mr-1">Topik:</span>
                    <button type="button" data-category="Politik" class="category-pill px-2.5 py-1 rounded-md font-medium bg-slate-100 hover:bg-slate-200 text-slate-700 transition">Politik</button>
                    <button type="button" data-category="Hukum & HAM" class="category-pill px-2.5 py-1 rounded-md font-medium bg-slate-100 hover:bg-slate-200 text-slate-700 transition">Hukum & HAM</button>
                    <button type="button" data-category="Kebijakan Publik" class="category-pill px-2.5 py-1 rounded-md font-medium bg-slate-100 hover:bg-slate-200 text-slate-700 transition">Kebijakan Publik</button>
                    <button type="button" data-category="Nasional" class="category-pill px-2.5 py-1 rounded-md font-medium bg-slate-100 hover:bg-slate-200 text-slate-700 transition">Nasional</button>
                </div>
            </form>
        </section>

        <div id="alert-error" class="hidden mb-6 p-3 rounded-md bg-red-50 border border-red-200 text-red-800 text-xs flex items-center justify-between">
            <span id="error-message">Gagal memuat berita dari server.</span>
            <button type="button" id="btn-retry" class="underline font-semibold">Coba Lagi</button>
        </div>

        <section id="section-featured" class="hidden mb-8">
            <div class="border-b border-slate-200 pb-1.5 mb-3 flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-red-700">
                <span class="w-2 h-2 rounded-full bg-red-600"></span> Sorotan Utama
            </div>
            <div id="featured-card" class="bg-white rounded-lg border border-slate-200 overflow-hidden grid grid-cols-1 md:grid-cols-12 shadow-sm"></div>
        </section>

        <div class="flex items-center justify-between pb-2 mb-4 border-b border-slate-200">
            <h2 class="text-base font-bold text-slate-900">Feed Berita Terkini</h2>
            <span id="info-status" class="text-xs text-slate-500">Memuat data...</span>
        </div>

        <div id="loading-state" class="py-12 text-center">
            <div class="inline-block w-7 h-7 border-2 border-slate-300 border-t-blue-900 rounded-full animate-spin mb-2"></div>
            <p class="text-xs text-slate-500">Menghubungkan ke portal berita...</p>
        </div>

        <div id="news-grid" class="hidden grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-6"></div>

        <div id="container-load-more" class="hidden text-center mb-8">
            <button type="button" id="btn-load-more" class="text-sm font-semibold text-blue-900 hover:underline">Lihat Semua Berita</button>
        </div>

        <div id="empty-state" class="hidden bg-white rounded-lg border border-slate-200 p-6 text-center mb-8">
            <p class="text-sm font-semibold text-slate-800">Tidak ada artikel yang cocok</p>
            <p class="text-xs text-slate-500 mt-1 mb-3">Coba ubah kata kunci atau ganti topik kategori.</p>
            <button type="button" id="btn-clear-empty" class="text-xs font-semibold text-blue-900 hover:underline">Tampilkan Semua Berita</button>
        </div>
    `;

    const el = id => document.getElementById(id);
    const formSearch = el('form-search'), inputSearch = el('input-search'), selectSource = el('select-source');
    const btnReset = el('btn-reset'), btnRetry = el('btn-retry'), btnClearEmpty = el('btn-clear-empty');
    const categoryPills = document.querySelectorAll('.category-pill');
    const loadingState = el('loading-state'), alertError = el('alert-error'), errorMessage = el('error-message');
    const newsGrid = el('news-grid'), emptyState = el('empty-state'), sectionFeatured = el('section-featured'), featuredCard = el('featured-card');
    const totalBadge = el('total-isu-badge'), totalSumberBadge = el('total-sumber-badge'), infoStatus = el('info-status');
    const containerLoadMore = el('container-load-more'), btnLoadMore = el('btn-load-more');

    const INITIAL_LIMIT = 12;
    let isShowingAll = false, cachedNewsList = [], currentCategory = 'all', currentSource = 'all', currentSearch = '', debounceTimer = null, sourcesLoaded = false;

    const sanitizeHTML = str => {
        const d = document.createElement('div');
        d.textContent = str || '';
        return d.innerHTML;
    };

    function populateSourceDropdown(sourcesList) {
        if (sourcesLoaded || !sourcesList?.length) return;
        selectSource.innerHTML = '<option value="all">Semua Portal Berita</option>';
        sourcesList.forEach(name => {
            const opt = document.createElement('option');
            opt.value = opt.textContent = name;
            selectSource.appendChild(opt);
        });
        sourcesLoaded = true;
    }

    async function loadNews() {
        loadingState.classList.remove('hidden');
        [alertError, newsGrid, emptyState, sectionFeatured].forEach(el => el.classList.add('hidden'));

        try {
            const url = `api/rss_fetcher.php?category=${encodeURIComponent(currentCategory)}&source=${encodeURIComponent(currentSource)}&search=${encodeURIComponent(currentSearch)}&limit=50`;
            const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
            if (!res.ok) throw new Error(`HTTP ${res.status}`);
            const result = await res.json();

            if (totalSumberBadge && result.total_sources) totalSumberBadge.textContent = `${result.total_sources} Sumber`;
            populateSourceDropdown(result.available_sources);
            renderNews(result.data || []);

            const total = result.total || 0;
            if (totalBadge) totalBadge.textContent = `${total} Berita`;
            if (infoStatus) infoStatus.textContent = currentSearch ? `Menemukan ${total} artikel untuk "${sanitizeHTML(currentSearch)}"` : `Menampilkan ${total} artikel terkini`;
        } catch (err) {
            errorMessage.textContent = 'Gagal memuat feed berita. Pastikan server lokal aktif.';
            alertError.classList.remove('hidden');
        } finally {
            loadingState.classList.add('hidden');
        }
    }

    function renderNews(newsItems) {
        cachedNewsList = newsItems || [];
        if (!newsItems?.length) {
            emptyState.classList.remove('hidden');
            containerLoadMore.classList.add('hidden');
            return;
        }

        let items = [...newsItems];
        if (!currentSearch && currentCategory === 'all' && currentSource === 'all' && items.length > 0) {
            const head = items.shift();
            sectionFeatured.classList.remove('hidden');
            featuredCard.innerHTML = `
                <div class="md:col-span-7 h-52 md:h-full min-h-[200px] bg-slate-100">
                    <img src="${sanitizeHTML(head.image)}" alt="${sanitizeHTML(head.title)}" class="w-full h-full object-cover" onerror="this.src='https://images.unsplash.com/photo-1541872703-74c5e44368f9?auto=format&fit=crop&w=600&q=80'">
                </div>
                <div class="md:col-span-5 p-4 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center gap-1.5 text-xs mb-1.5">
                            <span class="font-bold text-blue-900 uppercase tracking-wider">${sanitizeHTML(head.category)}</span>
                            <span class="text-slate-300">•</span>
                            <span class="text-slate-400">${sanitizeHTML(head.formattedDate)}</span>
                        </div>
                        <h3 class="text-base md:text-lg font-bold text-slate-900 mb-1.5 leading-snug hover:text-blue-900">
                            <a href="${sanitizeHTML(head.link)}" target="_blank" rel="noopener noreferrer">${sanitizeHTML(head.title)}</a>
                        </h3>
                        <p class="text-slate-600 text-xs leading-relaxed mb-3">${sanitizeHTML(head.description)}</p>
                    </div>
                    <div class="pt-2.5 border-t border-slate-100 flex items-center justify-between text-xs">
                        <span class="font-semibold text-slate-700">${sanitizeHTML(head.source)}</span>
                        <a href="${sanitizeHTML(head.link)}" target="_blank" rel="noopener noreferrer" class="font-semibold text-blue-900 hover:underline">Baca Lengkap</a>
                    </div>
                </div>
            `;
        }

        const toDisplay = isShowingAll ? items : items.slice(0, INITIAL_LIMIT);
        newsGrid.innerHTML = '';
        toDisplay.forEach(item => {
            const card = document.createElement('article');
            card.className = 'bg-white rounded-lg border border-slate-200 hover:border-slate-300 transition flex flex-col overflow-hidden';
            card.innerHTML = `
                <div class="h-40 w-full bg-slate-100">
                    <img src="${sanitizeHTML(item.image)}" alt="${sanitizeHTML(item.title)}" loading="lazy" class="w-full h-full object-cover" onerror="this.src='https://images.unsplash.com/photo-1541872703-74c5e44368f9?auto=format&fit=crop&w=600&q=80'">
                </div>
                <div class="p-3.5 flex-1 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center gap-1.5 text-[11px] mb-1">
                            <span class="font-bold text-blue-900 uppercase tracking-wider">${sanitizeHTML(item.category)}</span>
                            <span class="text-slate-300">•</span>
                            <span class="text-slate-400">${sanitizeHTML(item.formattedDate)}</span>
                        </div>
                        <h3 class="font-bold text-slate-900 text-xs leading-snug mb-1.5 hover:text-blue-900">
                            <a href="${sanitizeHTML(item.link)}" target="_blank" rel="noopener noreferrer">${sanitizeHTML(item.title)}</a>
                        </h3>
                        <p class="text-slate-600 text-[11px] leading-relaxed line-clamp-2 mb-2">${sanitizeHTML(item.description)}</p>
                    </div>
                    <div class="pt-2 border-t border-slate-100 flex items-center justify-between text-[11px] mt-auto">
                        <span class="font-semibold text-slate-700">${sanitizeHTML(item.source)}</span>
                        <a href="${sanitizeHTML(item.link)}" target="_blank" rel="noopener noreferrer" class="font-semibold text-blue-900 hover:underline">Baca Sumber</a>
                    </div>
                </div>
            `;
            newsGrid.appendChild(card);
        });

        newsGrid.classList.remove('hidden');
        containerLoadMore.classList.toggle('hidden', isShowingAll || items.length <= INITIAL_LIMIT);
    }

    btnLoadMore.addEventListener('click', () => { isShowingAll = true; renderNews(cachedNewsList); });
    formSearch.addEventListener('submit', e => { e.preventDefault(); isShowingAll = false; currentSearch = inputSearch.value.trim(); loadNews(); });
    inputSearch.addEventListener('input', () => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => { isShowingAll = false; currentSearch = inputSearch.value.trim(); loadNews(); }, 300);
    });
    selectSource.addEventListener('change', e => { isShowingAll = false; currentSource = e.target.value; loadNews(); });

    categoryPills.forEach(pill => {
        pill.addEventListener('click', function () {
            isShowingAll = false;
            const clicked = this.getAttribute('data-category');
            currentCategory = (currentCategory === clicked) ? 'all' : clicked;
            categoryPills.forEach(p => {
                const active = p.getAttribute('data-category') === currentCategory;
                p.className = `category-pill px-2.5 py-1 rounded-md font-medium transition ${active ? 'bg-blue-900 text-white' : 'bg-slate-100 hover:bg-slate-200 text-slate-700'}`;
            });
            loadNews();
        });
    });

    btnReset.addEventListener('click', () => {
        isShowingAll = false; inputSearch.value = ''; selectSource.value = 'all'; currentSearch = ''; currentSource = 'all'; currentCategory = 'all';
        categoryPills.forEach(p => { p.className = 'category-pill px-2.5 py-1 rounded-md font-medium transition bg-slate-100 hover:bg-slate-200 text-slate-700'; });
        loadNews();
    });
    btnRetry.addEventListener('click', loadNews);
    btnClearEmpty.addEventListener('click', () => btnReset.click());

    loadNews();
}
