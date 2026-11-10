<?php
// includes/partials/footer-panel.php
// Wrapper template footer untuk Panel Sidebar (Pengajar, Admin, Profil)

$root = $root ?? '';
?>
    </main>

    <!-- Footer di dalam area panel -->
    <footer class="site-footer">
      <p>&copy; <?= date('Y') ?> DynaQuiz &bull; Kelompok 14. Panel Pengelolaan.</p>
    </footer>
  </div>
</div>

<!-- Skrip JavaScript Pemilih Tema & Interaksi UI -->
<script src="<?= $root ?>assets/js/tema.js"></script>
<script src="<?= $root ?>assets/js/ui.js"></script>
</body>
</html>
