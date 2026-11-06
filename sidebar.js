(() => {
  'use strict';

  const host = document.getElementById('appSidebar');
  if (!host) return;

  const activePage = document.body.dataset.page || '';
  const permissions = (() => {
    try { return JSON.parse(document.body.dataset.permissions || '[]'); }
    catch { return []; }
  })();
  const links = [
    { key: 'dashboard', permission: 'Dashboard', href: 'dashboard.php', icon: 'bi-grid', label: 'Dashboard' },
    { key: 'transactions', permission: 'Transaksi', href: 'transaksi.php', icon: 'bi-clipboard2-check', label: 'Transaksi Servis' },
    { key: 'parts', permission: 'Sparepart', href: 'master_sparepart.php', icon: 'bi-box-seam', label: 'Master Sparepart' },
    { key: 'services', permission: 'Jasa', href: 'master_jasa.php', icon: 'bi-wrench-adjustable', label: 'Master Jasa' },
    { key: 'settings', permission: 'Pengaturan', href: 'settings.php', icon: 'bi-gear', label: 'Pengaturan' }
  ].filter(link => permissions.includes(link.permission));
  const homePage = document.body.dataset.homePage || links[0]?.href || 'login.php';

  host.innerHTML = `
    <a class="brand" href="${homePage}" aria-label="Putra Motor">
      <span class="brand-logo-wrap"><img src="logo.png?v=20261105-2230" alt="Logo Putra Motor" class="brand-logo"></span>
      <span class="brand-copy"><strong>Putra<span>Motor</span></strong><small>Sistem POS Bengkel Otomotif</small></span>
    </a>
    <nav class="sidebar-nav" aria-label="Navigasi utama">
      ${links.map(link => `<a href="${link.href}" class="${link.key === activePage ? 'active' : ''}"><i class="bi ${link.icon}"></i><span>${link.label}</span>${link.chevron ? '<i class="bi bi-chevron-down ms-auto nav-chevron"></i>' : ''}</a>`).join('')}
    </nav>
    <div class="sidebar-glow" aria-hidden="true"></div>`;

  const primaryMobileLinks = links.length <= 4 ? links : links.slice(0, 4);
  const secondaryMobileLinks = links.slice(4);
  const moreIsActive = secondaryMobileLinks.some(link => link.key === activePage);
  const mobileNavigation = document.createElement('div');
  mobileNavigation.className = 'mobile-navigation';
  mobileNavigation.innerHTML = `
    ${secondaryMobileLinks.length ? `<div class="mobile-more-backdrop" data-mobile-more-close aria-hidden="true"></div>
    <div class="mobile-more-panel" id="mobileMorePanel" aria-hidden="true">
      <span class="mobile-more-title">Menu lainnya</span>
      ${secondaryMobileLinks.map(link => `<a href="${link.href}" class="${link.key === activePage ? 'active' : ''}"><i class="bi ${link.icon}"></i><span>${link.label}</span></a>`).join('')}
    </div>` : ''}
    <nav class="mobile-bottom-nav" aria-label="Navigasi utama mobile" style="--mobile-nav-count:${primaryMobileLinks.length + (secondaryMobileLinks.length ? 1 : 0)}">
      ${primaryMobileLinks.map(link => `<a href="${link.href}" class="mobile-nav-item ${link.key === activePage ? 'active' : ''}"${link.key === activePage ? ' aria-current="page"' : ''}><i class="bi ${link.icon}"></i><span>${link.label === 'Transaksi Servis' ? 'Transaksi' : link.label === 'Master Sparepart' ? 'Sparepart' : link.label === 'Master Jasa' ? 'Jasa' : link.label}</span></a>`).join('')}
      ${secondaryMobileLinks.length ? `<button type="button" class="mobile-nav-item mobile-more-button ${moreIsActive ? 'active' : ''}" aria-expanded="false" aria-controls="mobileMorePanel">
        <i class="bi bi-grid-3x3-gap"></i><span>Lainnya</span>
      </button>` : ''}
    </nav>`;
  document.body.appendChild(mobileNavigation);

  host.id = 'sidebar';

  const overlay = document.getElementById('sidebarOverlay');
  const menuButton = document.getElementById('menuButton');
  const setOpen = open => {
    host.classList.toggle('open', open);
    overlay?.classList.toggle('show', open);
  };
  menuButton?.addEventListener('click', () => setOpen(true));
  overlay?.addEventListener('click', () => setOpen(false));
  window.addEventListener('resize', () => { if (window.innerWidth >= 900) setOpen(false); });

  const moreButton = mobileNavigation.querySelector('.mobile-more-button');
  const morePanel = mobileNavigation.querySelector('.mobile-more-panel');
  const moreBackdrop = mobileNavigation.querySelector('.mobile-more-backdrop');
  const setMoreOpen = open => {
    mobileNavigation.classList.toggle('more-open', open);
    moreButton?.setAttribute('aria-expanded', String(open));
    morePanel?.setAttribute('aria-hidden', String(!open));
  };

  moreButton?.addEventListener('click', () => setMoreOpen(!mobileNavigation.classList.contains('more-open')));
  moreBackdrop?.addEventListener('click', () => setMoreOpen(false));
  morePanel?.querySelectorAll('a').forEach(link => link.addEventListener('click', () => setMoreOpen(false)));
  document.addEventListener('keydown', event => {
    if (event.key === 'Escape') setMoreOpen(false);
  });
  window.addEventListener('resize', () => {
    if (window.innerWidth >= 900) setMoreOpen(false);
  });
})();
