(() => {
  'use strict';

  const endpoint = 'API/public/api/transaksi';
  const pageSize = 10;
  const formatRupiah = value => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(value);
  const escapeHTML = value => String(value ?? '').replace(/[&<>'"]/g, char => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;' }[char]));
  const formatDate = value => {
    const date = new Date(`${value}T00:00:00`);
    if (Number.isNaN(date.getTime())) return value || '';
    return date.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
  };

  const rowsHost = document.getElementById('transactionRows');
  const searchInput = document.getElementById('transactionSearch');
  const statusFilter = document.getElementById('statusFilter');
  const paymentStatusFilter = document.getElementById('paymentStatusFilter');
  const paymentFilter = document.getElementById('paymentFilter');
  const pagination = document.getElementById('transactionPagination');
  const dateRangeButton = document.getElementById('dateRangeButton');
  const dateRangePanel = document.getElementById('dateRangePanel');
  const dateRangeLabel = document.getElementById('dateRangeLabel');
  const dateFrom = document.getElementById('dateFrom');
  const dateTo = document.getElementById('dateTo');
  const isoDate = date => `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
  const currentMonthRange = () => {
    const today = new Date();
    return {
      from: isoDate(new Date(today.getFullYear(), today.getMonth(), 1)),
      to: isoDate(new Date(today.getFullYear(), today.getMonth() + 1, 0)),
    };
  };
  let dateRange = currentMonthRange();
  let currentPage = 1;
  let lastPage = 1;
  let totalRows = 0;
  let sortKey = '';
  let sortDirection = 'desc';
  let rows = [];
  let searchTimer = 0;
  let selectedActionItem = null;
  const actionMenu = document.createElement('div');
  actionMenu.className = 'transaction-action-menu';
  actionMenu.setAttribute('role', 'menu');
  actionMenu.hidden = true;
  document.body.appendChild(actionMenu);

  const statusMeta = status => {
    if (status === 'Selesai') return { className: 'lunas', icon: 'bi-check-circle' };
    return { className: 'proses', icon: 'bi-clock' };
  };
  const paymentMeta = status => {
    if (status === 'Lunas') return { className: 'lunas', icon: 'bi-check-circle' };
    return { className: 'belum', icon: 'bi-credit-card' };
  };

  async function request(url, options = {}) {
    const response = await fetch(url, {
      ...options,
      headers: { Accept: 'application/json', 'Content-Type': 'application/json', ...(options.headers || {}) }
    });
    const payload = await response.json().catch(() => ({}));
    if (!response.ok) {
      const validation = payload.errors ? Object.values(payload.errors).flat()[0] : '';
      throw new Error(validation || payload.message || 'Permintaan gagal diproses');
    }
    return payload;
  }

  function showToast(message) {
    const toast = document.getElementById('listToast');
    toast.querySelector('span').textContent = message;
    bootstrap.Toast.getOrCreateInstance(toast, { delay: 2200 }).show();
  }

  function pageList() {
    if (lastPage <= 7) return Array.from({ length: lastPage }, (_, index) => index + 1);
    const pages = [1, lastPage, currentPage - 1, currentPage, currentPage + 1].filter(page => page >= 1 && page <= lastPage);
    return [...new Set(pages)].sort((a, b) => a - b);
  }

  function renderPagination() {
    const pages = pageList();
    const buttons = [`<button type="button" data-page="prev" ${currentPage === 1 ? 'disabled' : ''} aria-label="Halaman sebelumnya"><i class="bi bi-chevron-left"></i></button>`];
    pages.forEach((page, index) => {
      if (index > 0 && page - pages[index - 1] > 1) buttons.push('<span class="page-gap">…</span>');
      buttons.push(`<button type="button" data-page="${page}" class="${page === currentPage ? 'active' : ''}" ${page === currentPage ? 'aria-current="page"' : ''}>${page}</button>`);
    });
    buttons.push(`<button type="button" data-page="next" ${currentPage === lastPage ? 'disabled' : ''} aria-label="Halaman berikutnya"><i class="bi bi-chevron-right"></i></button>`);
    pagination.innerHTML = buttons.join('');
  }

  function renderRows() {
    rowsHost.innerHTML = rows.length ? rows.map(item => {
      const status = statusMeta(item.status);
      const payment = paymentMeta(item.status_pembayaran);
      const href = `transaksi_detail.php?mode=view&id=${encodeURIComponent(item.nomor)}`;
      return `<tr>
        <td><a class="transaction-id" href="${href}">${escapeHTML(item.nomor)}</a></td>
        <td class="transaction-date">${escapeHTML(formatDate(item.tanggal))}</td>
        <td><div class="customer-vehicle"><strong><i class="bi bi-person-fill"></i>${escapeHTML(item.pelanggan)}</strong><span><i class="bi bi-car-front-fill"></i>${escapeHTML(item.kendaraan)} / ${escapeHTML(item.plat)}</span></div></td>
        <td><span class="service-text">${escapeHTML(item.jenis_servis)}</span></td><td class="money">${formatRupiah(item.total)}</td>
        <td><div class="status-stack"><span class="status-pill ${status.className}"><i class="bi ${status.icon}"></i>${escapeHTML(item.status)}</span><span class="status-pill ${payment.className}"><i class="bi ${payment.icon}"></i>${escapeHTML(item.status_pembayaran || 'Belum Bayar')}</span></div></td>
        <td class="action-cell"><button class="action-menu-button" type="button" data-transaction="${escapeHTML(item.nomor)}" aria-label="Buka aksi transaksi ${escapeHTML(item.nomor)}" aria-haspopup="menu" aria-expanded="false"><i class="bi bi-three-dots-vertical"></i></button></td>
      </tr>`;
    }).join('') : '<tr class="empty-list"><td colspan="7"><i class="bi bi-inbox"></i> Tidak ada transaksi yang sesuai filter</td></tr>';

    const from = rows.length ? (currentPage - 1) * pageSize + 1 : 0;
    const to = (currentPage - 1) * pageSize + rows.length;
    document.getElementById('tableResultText').textContent = rows.length ? `Menampilkan ${from}–${to} dari ${totalRows} transaksi` : 'Tidak ada transaksi ditemukan';
    renderPagination();
  }

  function listUrl(page, size = pageSize) {
    const params = new URLSearchParams();
    params.set('page', String(page));
    params.set('per_page', String(size));
    const search = searchInput.value.trim();
    if (search) params.set('search', search);
    if (statusFilter.value) params.set('status', statusFilter.value);
    if (paymentStatusFilter.value) params.set('status_pembayaran', paymentStatusFilter.value);
    if (paymentFilter.value) params.set('metode_pembayaran', paymentFilter.value);
    if (dateRange.from) params.set('dari', dateRange.from);
    if (dateRange.to) params.set('sampai', dateRange.to);
    if (sortKey) {
      params.set('sort', sortKey);
      params.set('direction', sortDirection);
    }
    return `${endpoint}?${params.toString()}`;
  }

  async function loadRows() {
    const payload = await request(listUrl(currentPage));
    const paginationInfo = payload.pagination || { page: 1, total: 0, last_page: 1 };
    if (currentPage > paginationInfo.last_page) {
      currentPage = paginationInfo.last_page || 1;
      return loadRows();
    }
    rows = payload.data || [];
    currentPage = paginationInfo.page || 1;
    lastPage = paginationInfo.last_page || 1;
    totalRows = paginationInfo.total || 0;
    const summary = payload.summary || {};
    document.getElementById('statTotal').textContent = summary.total ?? 0;
    document.getElementById('statUnpaid').textContent = summary.dalam_pengerjaan ?? 0;
    document.getElementById('statDone').textContent = summary.selesai ?? 0;
    renderRows();
  }

  function closeActionMenu() {
    actionMenu.hidden = true;
    document.querySelectorAll('.action-menu-button[aria-expanded="true"]').forEach(button => button.setAttribute('aria-expanded', 'false'));
    selectedActionItem = null;
  }

  function openActionMenu(button, item) {
    closeActionMenu();
    selectedActionItem = item;
    const encodedNumber = encodeURIComponent(item.nomor);
    actionMenu.innerHTML = `
      <a role="menuitem" href="transaksi_detail.php?mode=view&id=${encodedNumber}"><i class="bi bi-eye"></i><span>View</span></a>
      <a role="menuitem" href="transaksi_detail.php?mode=edit&id=${encodedNumber}"><i class="bi bi-pencil-square"></i><span>Edit</span></a>
      <a role="menuitem" href="API/public/api/transaksi/${encodedNumber}/invoice" target="_blank" rel="noopener"><i class="bi bi-printer"></i><span>Print</span></a>
      <hr>
      <button role="menuitem" type="button" data-action="payment"><i class="bi bi-credit-card"></i><span>Ubah status pembayaran</span></button>
      <button role="menuitem" type="button" data-action="work"><i class="bi bi-wrench-adjustable"></i><span>Ubah status pengerjaan</span></button>`;
    actionMenu.hidden = false;
    button.setAttribute('aria-expanded', 'true');
    const buttonBox = button.getBoundingClientRect();
    const menuBox = actionMenu.getBoundingClientRect();
    const left = Math.max(8, Math.min(buttonBox.right - menuBox.width, window.innerWidth - menuBox.width - 8));
    const spaceBelow = window.innerHeight - buttonBox.bottom;
    const top = spaceBelow >= menuBox.height + 8 ? buttonBox.bottom + 6 : Math.max(8, buttonBox.top - menuBox.height - 6);
    actionMenu.style.left = `${left}px`;
    actionMenu.style.top = `${top}px`;
  }

  function popupOption(name, value, label, icon, checked) {
    return `<label class="transaction-popup-option"><input type="radio" name="${name}" value="${escapeHTML(value)}" ${checked ? 'checked' : ''}><span><i class="bi ${icon}"></i><b>${escapeHTML(label)}</b><i class="bi bi-check-circle-fill option-check"></i></span></label>`;
  }

  async function changePaymentStatus(item) {
    const result = await Swal.fire({
      title: 'Ubah Status Pembayaran',
      html: `<div class="transaction-popup-form">
        <p class="transaction-popup-number">${escapeHTML(item.nomor)}</p>
        <label class="transaction-popup-label">Status pembayaran</label>
        <div class="transaction-popup-options two-columns">
          ${popupOption('popup_payment_status', 'Belum Bayar', 'Belum Bayar', 'bi-clock', item.status_pembayaran !== 'Lunas')}
          ${popupOption('popup_payment_status', 'Lunas', 'Lunas', 'bi-check-circle', item.status_pembayaran === 'Lunas')}
        </div>
        <label class="transaction-popup-label">Metode pembayaran</label>
        <div class="transaction-popup-options three-columns">
          ${popupOption('popup_payment_method', 'Tunai', 'Tunai', 'bi-cash-stack', item.metode_pembayaran === 'Tunai')}
          ${popupOption('popup_payment_method', 'Transfer', 'Transfer', 'bi-bank', item.metode_pembayaran === 'Transfer')}
          ${popupOption('popup_payment_method', 'QRIS', 'QRIS', 'bi-qr-code', item.metode_pembayaran === 'QRIS')}
        </div>
      </div>`,
      customClass: { popup: 'transaction-status-popup' },
      showCancelButton: true,
      confirmButtonText: 'Simpan perubahan',
      cancelButtonText: 'Batal',
      confirmButtonColor: '#A8874A',
      focusConfirm: false,
      preConfirm: () => ({
        status_pembayaran: document.querySelector('input[name="popup_payment_status"]:checked')?.value,
        metode_pembayaran: document.querySelector('input[name="popup_payment_method"]:checked')?.value
      })
    });
    if (!result.isConfirmed) return;
    try {
      const payload = await request(`${endpoint}/${encodeURIComponent(item.nomor)}/payment-status`, {
        method: 'PATCH',
        body: JSON.stringify(result.value)
      });
      showToast(payload.message || 'Status pembayaran berhasil diperbarui');
      await loadRows();
    } catch (error) {
      await Swal.fire({ icon: 'error', title: 'Perubahan gagal disimpan', text: error.message, confirmButtonColor: '#A8874A' });
    }
  }

  async function changeWorkStatus(item) {
    const result = await Swal.fire({
      title: 'Ubah Status Pengerjaan',
      html: `<div class="transaction-popup-form">
        <p class="transaction-popup-number">${escapeHTML(item.nomor)}</p>
        <label class="transaction-popup-label">Status pengerjaan</label>
        <div class="transaction-popup-options two-columns">
          ${popupOption('popup_work_status', 'Dalam Pengerjaan', 'Dalam Pengerjaan', 'bi-wrench-adjustable', item.status !== 'Selesai')}
          ${popupOption('popup_work_status', 'Selesai', 'Selesai', 'bi-check-circle', item.status === 'Selesai')}
        </div>
      </div>`,
      customClass: { popup: 'transaction-status-popup' },
      showCancelButton: true,
      confirmButtonText: 'Simpan perubahan',
      cancelButtonText: 'Batal',
      confirmButtonColor: '#A8874A',
      focusConfirm: false,
      preConfirm: () => ({ status: document.querySelector('input[name="popup_work_status"]:checked')?.value })
    });
    if (!result.isConfirmed) return;
    try {
      const payload = await request(`${endpoint}/${encodeURIComponent(item.nomor)}/work-status`, {
        method: 'PATCH',
        body: JSON.stringify(result.value)
      });
      showToast(payload.message || 'Status pengerjaan berhasil diperbarui');
      await loadRows();
    } catch (error) {
      await Swal.fire({ icon: 'error', title: 'Perubahan gagal disimpan', text: error.message, confirmButtonColor: '#A8874A' });
    }
  }

  async function exportRows() {
    const first = await request(listUrl(1, 100));
    const exported = [...(first.data || [])];
    const pages = first.pagination?.last_page || 1;
    for (let page = 2; page <= pages; page += 1) {
      const next = await request(listUrl(page, 100));
      exported.push(...(next.data || []));
    }
    const header = 'No. Transaksi,Tanggal,Pelanggan,Kendaraan,Plat,Jenis Servis,Total,Status,Status Pembayaran,Metode Pembayaran';
    const csv = [header, ...exported.map(item => [item.nomor, formatDate(item.tanggal), item.pelanggan, item.kendaraan, item.plat, item.jenis_servis, item.total, item.status, item.status_pembayaran, item.metode_pembayaran].map(value => `"${String(value ?? '').replaceAll('"', '""')}"`).join(','))].join('\n');
    const link = document.createElement('a');
    link.href = URL.createObjectURL(new Blob([csv], { type: 'text/csv;charset=utf-8' }));
    link.download = 'transaksi-servis.csv';
    link.click();
    URL.revokeObjectURL(link.href);
  }

  rowsHost.addEventListener('click', event => {
    const button = event.target.closest('.action-menu-button');
    if (!button) return;
    event.stopPropagation();
    const item = rows.find(row => String(row.nomor) === button.dataset.transaction);
    if (!item) return;
    if (!actionMenu.hidden && selectedActionItem?.nomor === item.nomor) {
      closeActionMenu();
      return;
    }
    openActionMenu(button, item);
  });

  actionMenu.addEventListener('click', event => {
    const actionButton = event.target.closest('[data-action]');
    if (!actionButton || !selectedActionItem) return;
    event.stopPropagation();
    const item = selectedActionItem;
    const action = actionButton.dataset.action;
    closeActionMenu();
    if (action === 'payment') changePaymentStatus(item);
    if (action === 'work') changeWorkStatus(item);
  });
  document.addEventListener('click', closeActionMenu);
  document.addEventListener('keydown', event => { if (event.key === 'Escape') closeActionMenu(); });
  window.addEventListener('resize', closeActionMenu);
  window.addEventListener('scroll', closeActionMenu, true);

  searchInput.addEventListener('input', () => {
    window.clearTimeout(searchTimer);
    searchTimer = window.setTimeout(() => { currentPage = 1; loadRows().catch(showError); }, 300);
  });
  [statusFilter, paymentStatusFilter, paymentFilter].forEach(control => control.addEventListener('change', () => { currentPage = 1; loadRows().catch(showError); }));
  function setDateRange(from, to, reload) {
    if (from > to) [from, to] = [to, from];
    dateRange = { from, to };
    dateFrom.value = from;
    dateTo.value = to;
    dateRangeLabel.textContent = `${formatDate(from)} – ${formatDate(to)}`;
    if (reload) {
      currentPage = 1;
      loadRows().catch(showError);
    }
  }

  function closeDateRange() {
    dateRangePanel.hidden = true;
    dateRangeButton.setAttribute('aria-expanded', 'false');
  }

  dateRangeButton.addEventListener('click', event => {
    event.stopPropagation();
    const open = dateRangePanel.hidden;
    dateRangePanel.hidden = !open;
    dateRangeButton.setAttribute('aria-expanded', String(open));
  });
  dateRangePanel.addEventListener('click', event => event.stopPropagation());
  document.addEventListener('click', closeDateRange);
  document.getElementById('applyDateRange').addEventListener('click', () => {
    if (!dateFrom.value || !dateTo.value) return;
    closeDateRange();
    setDateRange(dateFrom.value, dateTo.value, true);
  });
  document.getElementById('resetFilters').addEventListener('click', () => {
    searchInput.value = '';
    statusFilter.value = '';
    paymentStatusFilter.value = '';
    paymentFilter.value = '';
    sortKey = '';
    sortDirection = 'desc';
    const month = currentMonthRange();
    setDateRange(month.from, month.to, true);
  });
  document.querySelectorAll('.sort-btn').forEach(button => button.addEventListener('click', () => {
    const nextKey = { id: 'nomor', date: 'tanggal', total: 'total' }[button.dataset.sort];
    if (!nextKey) return;
    sortDirection = sortKey === nextKey && sortDirection === 'asc' ? 'desc' : 'asc';
    sortKey = nextKey;
    currentPage = 1;
    loadRows().catch(showError);
  }));
  document.getElementById('exportButton').addEventListener('click', () => {
    exportRows().catch(error => alert(error.message));
  });
  const serviceTip = document.createElement('div');
  serviceTip.className = 'service-hover';
  serviceTip.hidden = true;
  document.body.appendChild(serviceTip);
  rowsHost.addEventListener('mouseover', event => {
    const target = event.target.closest('.service-text');
    if (!target || target.scrollWidth <= target.clientWidth) {
      serviceTip.hidden = true;
      return;
    }
    const box = target.getBoundingClientRect();
    serviceTip.textContent = target.textContent;
    serviceTip.hidden = false;
    serviceTip.style.left = `${Math.min(box.left, window.innerWidth - serviceTip.offsetWidth - 12)}px`;
    serviceTip.style.top = `${box.bottom + 6}px`;
  });
  rowsHost.addEventListener('mouseout', event => {
    if (event.target.closest('.service-text')) serviceTip.hidden = true;
  });
  window.addEventListener('scroll', () => { serviceTip.hidden = true; }, true);

  pagination.addEventListener('click', event => {
    const button = event.target.closest('button');
    if (!button || button.disabled) return;
    if (button.dataset.page === 'prev') currentPage = Math.max(1, currentPage - 1);
    else if (button.dataset.page === 'next') currentPage = Math.min(lastPage, currentPage + 1);
    else currentPage = Number(button.dataset.page);
    loadRows().catch(showError);
  });

  function showError(error) {
    rowsHost.innerHTML = `<tr class="empty-list"><td colspan="7">${escapeHTML(error.message)}</td></tr>`;
    document.getElementById('tableResultText').textContent = 'Tidak ada transaksi ditemukan';
  }

  setDateRange(dateRange.from, dateRange.to, false);
  loadRows().catch(showError);
})();
