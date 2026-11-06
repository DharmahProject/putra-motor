(() => {
  'use strict';

  const account = document.getElementById('topbarAccount');
  const button = document.getElementById('topbarAccountButton');
  const menu = document.getElementById('topbarAccountMenu');
  if (!account || !button || !menu) return;

  const setOpen = open => {
    account.classList.toggle('open', open);
    button.setAttribute('aria-expanded', String(open));
    menu.hidden = !open;
  };

  button.addEventListener('click', () => setOpen(!account.classList.contains('open')));
  document.addEventListener('click', event => {
    if (!account.contains(event.target)) setOpen(false);
  });
  document.addEventListener('keydown', event => {
    if (event.key === 'Escape') setOpen(false);
  });

  document.getElementById('topbarLogoutButton')?.addEventListener('click', async () => {
    const result = await Swal.fire({
      title: 'Keluar dari aplikasi?',
      text: 'Anda perlu login kembali untuk mengakses sistem.',
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Ya, logout',
      cancelButtonText: 'Batal',
      confirmButtonColor: '#e6374b',
      cancelButtonColor: '#806432',
      reverseButtons: true,
      focusCancel: true
    });
    if (!result.isConfirmed) return;
    try {
      await fetch('auth_logout.php', { method: 'POST', headers: { Accept: 'application/json' } });
    } finally {
      window.location.href = 'login.php';
    }
  });

  const url = new URL(window.location.href);
  if (url.searchParams.get('access') === 'denied') {
    url.searchParams.delete('access');
    history.replaceState(null, '', url.pathname + url.search + url.hash);
    Swal.fire({
      icon: 'warning',
      title: 'Akses dibatasi',
      text: 'Akun Anda tidak memiliki hak akses ke modul tersebut.',
      confirmButtonText: 'Mengerti',
      confirmButtonColor: '#A8874A'
    });
  }
})();
