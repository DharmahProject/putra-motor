(() => {
  'use strict';

  const form = document.getElementById('newPasswordForm');
  if (!form) return;

  document.querySelectorAll('[data-password-toggle]').forEach(button => {
    button.addEventListener('click', () => {
      const input = document.getElementById(button.dataset.passwordToggle);
      const visible = input.type === 'text';
      input.type = visible ? 'password' : 'text';
      button.innerHTML = `<i class="bi bi-eye${visible ? '' : '-slash'}"></i>`;
      button.setAttribute('aria-label', visible ? 'Tampilkan password' : 'Sembunyikan password');
    });
  });

  form.querySelectorAll('input[required]').forEach(input => {
    input.addEventListener('input', () => input.closest('.auth-field')?.classList.remove('invalid'));
  });

  form.addEventListener('submit', async event => {
    event.preventDefault();
    const password = form.elements.password;
    const confirmation = form.elements.password_confirmation;
    const valid = password.checkValidity() && confirmation.checkValidity() && password.value === confirmation.value;
    password.closest('.auth-field')?.classList.toggle('invalid', !password.checkValidity());
    confirmation.closest('.auth-field')?.classList.toggle('invalid', !confirmation.checkValidity() || password.value !== confirmation.value);
    if (!valid) return;

    const submitButton = form.querySelector('[type="submit"]');
    const originalContent = submitButton.innerHTML;
    submitButton.disabled = true;
    submitButton.innerHTML = '<span>Menyimpan...</span><i class="bi bi-arrow-repeat"></i>';
    try {
      const response = await fetch('auth_reset_password.php', {
        method: 'POST',
        headers: { Accept: 'application/json', 'Content-Type': 'application/json' },
        body: JSON.stringify({
          token: form.elements.token.value,
          email: form.elements.email.value,
          password: password.value,
          password_confirmation: confirmation.value
        })
      });
      const payload = await response.json().catch(() => ({}));
      if (!response.ok) throw new Error(payload.message || 'Password baru gagal disimpan.');
      await Swal.fire({ icon: 'success', title: 'Password berhasil diubah', text: payload.message, confirmButtonText: 'Masuk sekarang' });
      window.location.replace('login.php');
    } catch (error) {
      await Swal.fire({ icon: 'error', title: 'Gagal mengubah password', text: error.message, confirmButtonText: 'Tutup' });
    } finally {
      submitButton.disabled = false;
      submitButton.innerHTML = originalContent;
    }
  });
})();
