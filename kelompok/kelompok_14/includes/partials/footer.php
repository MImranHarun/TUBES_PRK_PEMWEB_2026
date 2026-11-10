<?php
// includes/partials/footer.php
// Bagian penutup halaman dan skrip JavaScript bersama

$root = $root ?? '';
?>
  <footer class="site-footer">
    <div class="container">
      <p>&copy; <?= date('Y') ?> DynaQuiz &bull; Kelompok 14. Pemrograman Web.</p>
    </div>
  </footer>

  <!-- Skrip JavaScript Pemilih Tema & Interaksi UI -->
  <script src="<?= $root ?>assets/js/tema.js"></script>
  <script src="<?= $root ?>assets/js/ui.js"></script>
</body>
</html>
