(() => {
  'use strict';

  const views = {
    login: document.getElementById('loginView'),
    reset: document.getElementById('resetView')
  };

  function showView(name, updateHash = true) {
    Object.entries(views).forEach(([key, view]) => {
      const active = key === name;
      view.classList.toggle('active', active);
      view.hidden = !active;
    });
    if (updateHash) history.replaceState(null, '', name === 'reset' ? '#reset-password' : location.pathname);
    document.title = name === 'reset' ? 'Reset Password | Putra Motor' : 'Login | Putra Motor';
  }

  function validate(form) {
    let valid = true;
    form.querySelectorAll('input[required]').forEach(input => {
      const field = input.closest('.auth-field');
      const inputValid = input.checkValidity();
      field?.classList.toggle('invalid', !inputValid);
      if (!inputValid) valid = false;
    });
    return valid;
  }

  document.querySelectorAll('[data-auth-view]').forEach(button => {
    button.addEventListener('click', () => showView(button.dataset.authView));
  });

  document.querySelectorAll('[data-password-toggle]').forEach(button => {
    button.addEventListener('click', () => {
      const input = document.getElementById(button.dataset.passwordToggle);
      const visible = input.type === 'text';
      input.type = visible ? 'password' : 'text';
      button.innerHTML = `<i class="bi bi-eye${visible ? '' : '-slash'}"></i>`;
      button.setAttribute('aria-label', visible ? 'Tampilkan password' : 'Sembunyikan password');
    });
  });

  document.querySelectorAll('.auth-field input').forEach(input => {
    input.addEventListener('input', () => input.closest('.auth-field')?.classList.remove('invalid'));
  });

  const captchaImage = document.getElementById('captchaImage');
  function refreshCaptcha() {
    captchaImage.src = `auth_captcha.php?t=${Date.now()}`;
    const input = document.getElementById('loginCaptcha');
    if (input) input.value = '';
  }
  document.getElementById('refreshCaptcha').addEventListener('click', refreshCaptcha);

  document.getElementById('loginForm').addEventListener('submit', async event => {
    event.preventDefault();
    if (!validate(event.currentTarget)) return;
    const form = event.currentTarget;
    const submitButton = form.querySelector('[type="submit"]');
    const originalContent = submitButton.innerHTML;
    submitButton.disabled = true;
    submitButton.innerHTML = '<span>Memproses...</span><i class="bi bi-arrow-repeat"></i>';
    try {
      const response = await fetch('auth_login.php', {
        method: 'POST',
        headers: { Accept: 'application/json', 'Content-Type': 'application/json' },
        body: JSON.stringify({
          email: form.elements.email.value.trim(),
          password: form.elements.password.value,
          captcha: form.elements.captcha.value.trim(),
          remember: form.elements.remember.checked
        })
      });
      const payload = await response.json().catch(() => ({}));
      if (!response.ok) throw new Error(payload.message || 'Login gagal diproses');
      window.location.href = payload.redirect || 'dashboard.php';
    } catch (error) {
      refreshCaptcha();
      await Swal.fire({ icon: 'error', title: 'Login gagal', text: error.message, confirmButtonText: 'Coba lagi' });
    } finally {
      submitButton.disabled = false;
      submitButton.innerHTML = originalContent;
    }
  });

  document.getElementById('resetForm').addEventListener('submit', async event => {
    event.preventDefault();
    if (!validate(event.currentTarget)) return;
    const form = event.currentTarget;
    const submitButton = form.querySelector('[type="submit"]');
    const originalContent = submitButton.innerHTML;
    submitButton.disabled = true;
    submitButton.innerHTML = '<span>Mengirim...</span><i class="bi bi-arrow-repeat"></i>';
    try {
      const response = await fetch('auth_reset_request.php', {
        method: 'POST',
        headers: { Accept: 'application/json', 'Content-Type': 'application/json' },
        body: JSON.stringify({ phone: form.elements.phone.value.trim() })
      });
      const payload = await response.json().catch(() => ({}));
      if (!response.ok) throw new Error(payload.message || 'Permintaan reset password gagal diproses.');
      const result = await Swal.fire({
        icon: 'success',
        title: 'Periksa WhatsApp Anda',
        text: payload.message || 'Jika nomor terdaftar, tautan reset password akan segera dikirimkan.',
        confirmButtonText: 'Kembali ke login'
      });
      if (result.isConfirmed) {
        form.reset();
        showView('login');
      }
    } catch (error) {
      await Swal.fire({ icon: 'error', title: 'Gagal mengirim tautan', text: error.message, confirmButtonText: 'Coba lagi' });
    } finally {
      submitButton.disabled = false;
      submitButton.innerHTML = originalContent;
    }
  });

  showView(location.hash === '#reset-password' ? 'reset' : 'login', false);
})();
