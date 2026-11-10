    </main>

    <footer class="site-footer">
        <div class="container footer-content">
            <p>&copy; <?= date('Y') ?> <strong>Aspiraku</strong> - Portal Pengaduan & Aspirasi Mahasiswa dan Warga Kampus FISIP.</p>
            <p class="footer-sub">Dikembangkan oleh Kelompok 20 Praktikum Pemrograman Web.</p>
        </div>
    </footer>

    <?php if (file_exists(__DIR__ . '/../assets/js/ui.js')): ?>
        <script src="/assets/js/ui.js" defer></script>
    <?php endif; ?>
</body>
</html>

