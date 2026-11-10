<?php
require_once __DIR__ . '/../config/auth_check.php';

if (isLoggedIn()) {
    redirectByRole();
}

$pageTitle = 'OneBlood Register';
$errors    = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama        = trim((string)($_POST['nama'] ?? ''));
    $email       = trim((string)($_POST['email'] ?? ''));
    $no_hp       = trim((string)($_POST['no_hp'] ?? ''));
    $password    = (string)($_POST['password'] ?? '');
    $konfirmasi  = (string)($_POST['konfirmasi_password'] ?? '');
    $gol_darah   = (string)($_POST['gol_darah'] ?? '');
    $rhesus      = (string)($_POST['rhesus'] ?? '');
    $tgl_lahir   = (string)($_POST['tgl_lahir'] ?? '');
    $berat_badan = (float)($_POST['berat_badan'] ?? 0);
    $alamat      = trim((string)($_POST['alamat'] ?? ''));

    if ($nama === '') {
        $errors[] = 'Nama lengkap wajib diisi.';
    }
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Format alamat email tidak valid.';
    }
    if (strlen($password) < 6) {
        $errors[] = 'Password minimal terdiri dari 6 karakter.';
    }
    if ($password !== $konfirmasi) {
        $errors[] = 'Konfirmasi password tidak cocok.';
    }
    if (!in_array($gol_darah, ['A', 'B', 'AB', 'O'], true)) {
        $errors[] = 'Silakan pilih golongan darah (A, B, AB, atau O).';
    }
    if (!in_array($rhesus, ['+', '-'], true)) {
        $errors[] = 'Silakan pilih rhesus darah (+ atau -).';
    }
    if ($berat_badan > 0 && $berat_badan < 45) {
        $errors[] = 'Berat badan minimal untuk mendaftar sebagai pendonor adalah 45 kg.';
    }

    if (empty($errors)) {
        try {
            $stmtCheck = $db->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
            $stmtCheck->execute([':email' => $email]);

            if ($stmtCheck->fetch()) {
                $errors[] = 'Email tersebut sudah terdaftar. Silakan gunakan email lain atau langsung login.';
            } else {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

                $stmtInsert = $db->prepare("
                    INSERT INTO users 
                        (nama, email, password, no_hp, alamat, role, gol_darah, rhesus, tgl_lahir, berat_badan, status_aktif)
                    VALUES 
                        (:nama, :email, :password, :no_hp, :alamat, 'pendonor', :gol_darah, :rhesus, :tgl_lahir, :berat_badan, 1)
                ");

                $stmtInsert->execute([
                    ':nama'        => $nama,
                    ':email'       => $email,
                    ':password'    => $hashedPassword,
                    ':no_hp'       => $no_hp !== '' ? $no_hp : null,
                    ':alamat'      => $alamat !== '' ? $alamat : null,
                    ':gol_darah'   => $gol_darah,
                    ':rhesus'      => $rhesus,
                    ':tgl_lahir'   => $tgl_lahir !== '' ? $tgl_lahir : null,
                    ':berat_badan' => $berat_badan > 0 ? $berat_badan : null,
                ]);

                setFlash('success', 'Pendaftaran berhasil! Silakan masuk menggunakan akun pendonor Anda.');
                header('Location: ' . base_url('auth/login.php'));
                exit;
            }
        } catch (PDOException $e) {
            $errors[] = 'Gagal menyimpan pendaftaran: ' . $e->getMessage();
        }
    }
}
require_once __DIR__ . '/../includes/header.php';
?>

<link rel="stylesheet" href="<?= base_url('css/style.css') ?>">
  <div class="login-wrapper">
    <div class="login-illustration">
      <img src="<?= e(base_url('assets/donor2.jpg')) ?>" alt="Ilustrasi OneBlood">
    </div>
    <div class="register-page">
      <div class="login-right">
        <div class="login-card">
          <h2 class="login-title">Daftar Akun Pendonor</h2>

          <?php if (!empty($errors)): ?>
            <div class="login-alert">
              <?php foreach ($errors as $err): ?>
                <div><?= e($err) ?></div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>

          <form action="" method="POST" autocomplete="off">
            <div class="input-group">
              <i class="fa-solid fa-user"></i>
              <input type="text" name="nama" placeholder="Nama Lengkap" required>
            </div>

            <div class="input-group">
              <i class="fa-solid fa-envelope"></i>
              <input type="email" name="email" placeholder="Alamat Email Aktif" required>
            </div>

            <div class="input-group">
              <i class="fa-solid fa-phone"></i>
              <input type="text" name="no_hp" placeholder="Nomor Telepon" required>
            </div>

            <div class="grid-2">
              <div class="input-group custom-select" data-custom-select>
                <i class="fa-solid fa-droplet"></i>
                <input type="hidden" name="gol_darah" value="">
                <button class="select-trigger" type="button" aria-haspopup="menu" aria-expanded="false" aria-label="Golongan darah">
                  <span class="select-value">Gol. Darah</span>
                  <i class="fa-solid fa-chevron-down select-chevron" aria-hidden="true"></i>
                </button>
                <div class="select-menu" role="menu" aria-label="Pilihan golongan darah" hidden>
                  <button class="select-option" type="button" role="menuitemradio" aria-checked="false" data-value="A">Golongan A</button>
                  <button class="select-option" type="button" role="menuitemradio" aria-checked="false" data-value="B">Golongan B</button>
                  <button class="select-option" type="button" role="menuitemradio" aria-checked="false" data-value="AB">Golongan AB</button>
                  <button class="select-option" type="button" role="menuitemradio" aria-checked="false" data-value="O">Golongan O</button>
                </div>
              </div>

              <div class="input-group custom-select" data-custom-select>
                <i class="fa-solid fa-plus-minus"></i>
                <input type="hidden" name="rhesus" value="">
                <button class="select-trigger" type="button" aria-haspopup="menu" aria-expanded="false" aria-label="Rhesus">
                  <span class="select-value">Rhesus</span>
                  <i class="fa-solid fa-chevron-down select-chevron" aria-hidden="true"></i>
                </button>
                <div class="select-menu" role="menu" aria-label="Pilihan rhesus" hidden>
                  <button class="select-option" type="button" role="menuitemradio" aria-checked="false" data-value="+">Positif (+)</button>
                  <button class="select-option" type="button" role="menuitemradio" aria-checked="false" data-value="-">Negatif (-)</button>
                </div>
              </div>
            </div>

            <div class="grid-2">
              <div class="input-group">
                <i class="fa-solid fa-calendar-day"></i>
                <input type="date" name="tgl_lahir" title="Tanggal Lahir" required>
              </div>

              <div class="input-group">
                <i class="fa-solid fa-weight-scale"></i>
                <input type="number" name="berat_badan" placeholder="Berat Badan (kg)" min="45" max="200" step="0.5" required>
              </div>
            </div>

            <div class="input-group">
              <i class="fa-solid fa-location-dot"></i>
              <input type="text" name="alamat" placeholder="Alamat Domisili Saat Ini" required>
            </div>

            <div class="grid-2">
              <div class="input-group">
                <i class="fa-solid fa-lock"></i>
                <input type="password" name="password" placeholder="Password" required>
              </div>

              <div class="input-group">
                <i class="fa-solid fa-shield-halved"></i>
                <input type="password" name="konfirmasi_password" placeholder="Ulangi Password" required>
              </div>
            </div>

            <div class="form-options">
              <span class="remember-me">Sudah punya akun?</span>
              <a href="login.php" class="forget-link">Masuk di sini</a>
            </div>

            <button type="submit" class="btn-login">Daftar Sekarang</button>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>

  <script>
    document.querySelectorAll('[data-custom-select]').forEach((select) => {
      const trigger = select.querySelector('.select-trigger');
      const menu = select.querySelector('.select-menu');
      const valueInput = select.querySelector('input[type="hidden"]');
      const valueLabel = select.querySelector('.select-value');
      const options = Array.from(select.querySelectorAll('.select-option'));

      const closeMenu = (restoreFocus = false) => {
        menu.hidden = true;
        select.classList.remove('is-open');
        trigger.setAttribute('aria-expanded', 'false');
        if (restoreFocus) trigger.focus();
      };

      const openMenu = (optionIndex = 0) => {
        document.querySelectorAll('[data-custom-select]').forEach((item) => {
          if (item !== select) {
            item.querySelector('.select-menu').hidden = true;
            item.classList.remove('is-open');
            item.querySelector('.select-trigger').setAttribute('aria-expanded', 'false');
          }
        });
        menu.hidden = false;
        select.classList.add('is-open');
        trigger.setAttribute('aria-expanded', 'true');
        options[Math.max(0, Math.min(optionIndex, options.length - 1))].focus();
      };

      const chooseOption = (option) => {
        valueInput.value = option.dataset.value;
        valueLabel.textContent = option.textContent;
        options.forEach((item) => item.setAttribute('aria-checked', String(item === option)));
        trigger.removeAttribute('aria-invalid');
        closeMenu(true);
      };

      trigger.addEventListener('click', () => {
        if (menu.hidden) {
          const selectedIndex = options.findIndex((option) => option.dataset.value === valueInput.value);
          openMenu(selectedIndex >= 0 ? selectedIndex : 0);
        } else {
          closeMenu();
        }
      });

      trigger.addEventListener('keydown', (event) => {
        if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
          event.preventDefault();
          const selectedIndex = options.findIndex((option) => option.dataset.value === valueInput.value);
          openMenu(selectedIndex >= 0 ? selectedIndex : 0);
        }
      });

      options.forEach((option, index) => {
        option.addEventListener('click', () => chooseOption(option));
        option.addEventListener('keydown', (event) => {
          let nextIndex = index;
          if (event.key === 'ArrowDown') nextIndex = (index + 1) % options.length;
          else if (event.key === 'ArrowUp') nextIndex = (index - 1 + options.length) % options.length;
          else if (event.key === 'Home') nextIndex = 0;
          else if (event.key === 'End') nextIndex = options.length - 1;
          else if (event.key === 'Escape') {
            event.preventDefault();
            closeMenu(true);
            return;
          } else if (event.key === 'Tab') {
            closeMenu();
            return;
          } else if (event.key !== 'Enter' && event.key !== ' ') {
            return;
          }

          event.preventDefault();
          if (event.key === 'Enter' || event.key === ' ') chooseOption(option);
          else options[nextIndex].focus();
        });
      });
    });

    document.addEventListener('click', (event) => {
      if (event.target.closest('[data-custom-select]')) return;
      document.querySelectorAll('[data-custom-select]').forEach((select) => {
        const menu = select.querySelector('.select-menu');
        if (!menu.hidden) {
          menu.hidden = true;
          select.classList.remove('is-open');
          select.querySelector('.select-trigger').setAttribute('aria-expanded', 'false');
        }
      });
    });

    document.querySelector('form').addEventListener('submit', (event) => {
      const incomplete = Array.from(document.querySelectorAll('[data-custom-select]')).find((select) => {
        return select.querySelector('input[type="hidden"]').value === '';
      });

      if (incomplete) {
        event.preventDefault();
        const trigger = incomplete.querySelector('.select-trigger');
        trigger.setAttribute('aria-invalid', 'true');
        trigger.focus();
        incomplete.scrollIntoView({ behavior: 'smooth', block: 'center' });
      }
    });
  </script>