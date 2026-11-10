/* =========================================
   FEBMART - CUSTOM JAVASCRIPT
========================================= */

document.addEventListener("DOMContentLoaded", function () {
  // =====================================
  // 1. KONFIRMASI HAPUS DATA
  // =====================================

  document.querySelectorAll("[data-confirm]").forEach(function (element) {
    element.addEventListener("click", function (event) {
      const message = element.dataset.confirm;

      if (!confirm(message)) {
        event.preventDefault();
      }
    });
  });

  // =====================================
  // 2. PREVIEW GAMBAR SEBELUM UPLOAD
  // =====================================

  document.querySelectorAll("[data-image-preview]").forEach(function (input) {
    input.addEventListener("change", function () {
      const previewId = input.dataset.imagePreview;
      const preview = document.getElementById(previewId);

      if (!preview || !input.files || !input.files[0]) {
        return;
      }

      const file = input.files[0];

      if (!file.type.startsWith("image/")) {
        alert("Silakan pilih file gambar.");
        input.value = "";
        return;
      }

      const reader = new FileReader();

      reader.onload = function (event) {
        preview.src = event.target.result;
        preview.classList.remove("d-none");
      };

      reader.readAsDataURL(file);
    });
  });

  // =====================================
  // 3. NOTIFIKASI OTOMATIS
  // =====================================

  document
    .querySelectorAll("[data-auto-dismiss]")
    .forEach(function (alertElement) {
      const duration = parseInt(alertElement.dataset.autoDismiss, 10) || 4000;

      setTimeout(function () {
        if (typeof bootstrap !== "undefined") {
          const alertInstance =
            bootstrap.Alert.getOrCreateInstance(alertElement);

          alertInstance.close();
        }
      }, duration);
    });
});
