
document.addEventListener('DOMContentLoaded', function () {
    function tampilkanError(form, daftarPesan) {
        var alertLama = form.querySelector('.alert-js-error');
        if (alertLama) {
            alertLama.remove();
        }

        if (daftarPesan.length === 0) {
            return;
        }

        var alertDiv = document.createElement('div');
        alertDiv.className = 'alert alert-danger alert-js-error';
        alertDiv.style.marginBottom = '1rem';

        var ul = document.createElement('ul');
        ul.style.margin = '0';
        ul.style.paddingLeft = '1.25rem';

        daftarPesan.forEach(function (pesan) {
            var li = document.createElement('li');
            li.textContent = pesan;
            ul.appendChild(li);
        });

        alertDiv.appendChild(ul);
        form.insertBefore(alertDiv, form.firstChild);
        alertDiv.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    function isEmailValid(email) {
        var polaEmail = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return polaEmail.test(email);
    }

    var formRegister = document.querySelector('form[action="/register.php"]');
    if (formRegister) {
        formRegister.addEventListener('submit', function (e) {
            var errors = [];

            var inputNama = formRegister.querySelector('#nama');
            var inputEmail = formRegister.querySelector('#email');
            var inputPassword = formRegister.querySelector('#password');
            var inputKonfirmasi = formRegister.querySelector('#konfirmasi_password');

            var nama = inputNama ? inputNama.value.trim() : '';
            var email = inputEmail ? inputEmail.value.trim() : '';
            var password = inputPassword ? inputPassword.value : '';
            var konfirmasi = inputKonfirmasi ? inputKonfirmasi.value : '';

            if (nama === '') {
                errors.push('Nama lengkap wajib diisi.');
            } else if (nama.length > 100) {
                errors.push('Nama lengkap maksimal 100 karakter.');
            }

            if (email === '') {
                errors.push('Alamat email wajib diisi.');
            } else if (!isEmailValid(email)) {
                errors.push('Format alamat email tidak valid (contoh: user@kampus.id).');
            }

            if (password === '') {
                errors.push('Kata sandi wajib diisi.');
            } else if (password.length < 6) {
                errors.push('Kata sandi minimal 6 karakter.');
            }

            if (password !== konfirmasi) {
                errors.push('Konfirmasi kata sandi tidak cocok dengan kata sandi.');
            }

            if (errors.length > 0) {
                e.preventDefault();
                tampilkanError(formRegister, errors);

                if (nama === '' && inputNama) {
                    inputNama.focus();
                } else if ((email === '' || !isEmailValid(email)) && inputEmail) {
                    inputEmail.focus();
                } else if ((password === '' || password.length < 6) && inputPassword) {
                    inputPassword.focus();
                } else if (password !== konfirmasi && inputKonfirmasi) {
                    inputKonfirmasi.focus();
                }
            }
        });
    }

    var formLogin = document.querySelector('form[action="/login.php"]');
    if (formLogin) {
        formLogin.addEventListener('submit', function (e) {
            var errors = [];

            var inputEmail = formLogin.querySelector('#email');
            var inputPassword = formLogin.querySelector('#password');

            var email = inputEmail ? inputEmail.value.trim() : '';
            var password = inputPassword ? inputPassword.value : '';

            if (email === '') {
                errors.push('Alamat email wajib diisi.');
            } else if (!isEmailValid(email)) {
                errors.push('Format alamat email tidak valid.');
            }

            if (password === '') {
                errors.push('Kata sandi wajib diisi.');
            }

            if (errors.length > 0) {
                e.preventDefault();
                tampilkanError(formLogin, errors);

                if (email === '' && inputEmail) {
                    inputEmail.focus();
                } else if (password === '' && inputPassword) {
                    inputPassword.focus();
                }
            }
        });
    }
});
