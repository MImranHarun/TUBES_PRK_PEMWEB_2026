document.addEventListener('DOMContentLoaded', function () {
  // 1. Ambil tema yang sedang aktif dari atribut tag <html>
  var temaAktif = document.documentElement.getAttribute('data-tema') || 'sky-bubblegum';

  // 2. Ambil semua tombol swatch tema
  var tombolSwatch = document.querySelectorAll('.swatch-tema');

  // Fungsi untuk memperbarui tanda aktif (aria-pressed) pada tombol tema
  function perbaruiTampilanSwatch(temaTerpilih) {
    tombolSwatch.forEach(function (tombol) {
      var temaTombol = tombol.getAttribute('data-pilih-tema');
      if (temaTombol === temaTerpilih) {
        tombol.setAttribute('aria-pressed', 'true');
      } else {
        tombol.setAttribute('aria-pressed', 'false');
      }
    });
  }

  // Set status awal saat halaman selesai dimuat
  perbaruiTampilanSwatch(temaAktif);

  // 3. Tambahkan event klik ke setiap tombol swatch
  tombolSwatch.forEach(function (tombol) {
    tombol.addEventListener('click', function () {
      var temaBaru = tombol.getAttribute('data-pilih-tema');
      if (!temaBaru) return;

      // Ubah atribut data-tema di tag <html>
      document.documentElement.setAttribute('data-tema', temaBaru);

      // Simpan preferensi tema ke localStorage agar tidak hilang saat refresh
      try {
        localStorage.setItem('dq-tema', temaBaru);
      } catch (e) {
        console.warn('Gagal menyimpan tema ke localStorage:', e);
      }

      // Perbarui tampilan tombol swatch aktif
      perbaruiTampilanSwatch(temaBaru);
    });
  });
});
