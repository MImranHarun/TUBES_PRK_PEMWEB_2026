// assets/js/ui.js
// Skrip sederhana untuk interaksi antarmuka (UI) DynaQuiz

document.addEventListener('DOMContentLoaded', function () {
  // 1. Ambil tombol menu hamburger dan menu navigasi
  var tombolMenu = document.getElementById('tombolMenu');
  var menuNav = document.getElementById('menuNav');

  // 2. Tambahkan aksi klik pada tombol hamburger
  if (tombolMenu && menuNav) {
    tombolMenu.addEventListener('click', function () {
      // Toggle class 'buka' agar menu tampil di layar HP
      menuNav.classList.toggle('buka');
      
      // Update status aksesibilitas aria-expanded
      var statusBuka = menuNav.classList.contains('buka');
      tombolMenu.setAttribute('aria-expanded', statusBuka ? 'true' : 'false');
    });
  }
});
