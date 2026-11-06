(() => {
  'use strict';

  const isParts = document.body.dataset.masterType === 'parts';
  const entityName = isParts ? 'Sparepart' : 'Jasa';
  const endpoint = isParts ? 'API/public/api/master-sparepart' : 'API/public/api/master-jasa';
  const rupiah = value => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(value);
  const parseNumber = value => Number(String(value).replace(/[^0-9]/g, '')) || 0;
  const formatNumber = value => {
    const digits = String(value).replace(/[^0-9]/g, '');
    return digits ? new Intl.NumberFormat('id-ID').format(Number(digits)) : '';
  };
  const escapeHTML = value => String(value ?? '').replace(/[&<>'"]/g, char => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;' }[char]));

  const pageSize = 10;
  let currentPage = 1;
  let editId = null;
  let items = [];

  const rowsHost = document.getElementById('masterRows');
  const search = document.getElementById('masterSearch');
  const toast = document.getElementById('masterToast');

  const actions = id => `<div class="master-actions"><button class="master-action edit" type="button" data-edit="${id}" data-bs-toggle="modal" data-bs-target="#masterModal" aria-label="Edit"><i class="bi bi-pencil-fill"></i></button><button class="master-action delete" type="button" data-delete="${id}" aria-label="Hapus"><i class="bi bi-trash3"></i></button></div>`;

  function showToast(message) {
    toast.querySelector('span').textContent = message;
    bootstrap.Toast.getOrCreateInstance(toast, { delay: 1800 }).show();
  }

  async function request(url, options = {}) {
    const response = await fetch(url, {
      headers: { Accept: 'application/json', 'Content-Type': 'application/json', ...(options.headers || {}) },
      ...options
    });
    const payload = await response.json().catch(() => ({}));
    if (!response.ok) {
      const validation = payload.errors ? Object.values(payload.errors).flat()[0] : '';
      throw new Error(validation || payload.message || 'Permintaan gagal diproses');
    }
    return payload;
  }

  function filteredItems() {
    const query = search.value.trim().toLowerCase();
    return items.filter(item => String(item.nama).toLowerCase().includes(query));
  }

  function pageList(totalPages) {
    if (totalPages <= 7) return Array.from({ length: totalPages }, (_, index) => index + 1);
    const pages = [1, totalPages, currentPage - 1, currentPage, currentPage + 1].filter(page => page >= 1 && page <= totalPages);
    return [...new Set(pages)].sort((a, b) => a - b);
  }

  function renderPagination(totalPages) {
    const pages = pageList(totalPages);
    const buttons = [`<button type="button" data-page="prev" ${currentPage === 1 ? 'disabled' : ''} aria-label="Halaman sebelumnya"><i class="bi bi-chevron-left"></i></button>`];
    pages.forEach((page, index) => {
      if (index > 0 && page - pages[index - 1] > 1) buttons.push('<span class="page-gap">…</span>');
      buttons.push(`<button type="button" data-page="${page}" class="${page === currentPage ? 'active' : ''}" ${page === currentPage ? 'aria-current="page"' : ''}>${page}</button>`);
    });
    buttons.push(`<button type="button" data-page="next" ${currentPage === totalPages ? 'disabled' : ''} aria-label="Halaman berikutnya"><i class="bi bi-chevron-right"></i></button>`);
    document.getElementById('masterPagination').innerHTML = buttons.join('');
  }

  function render() {
    const visible = filteredItems();
    const totalPages = Math.max(1, Math.ceil(visible.length / pageSize));
    currentPage = Math.min(Math.max(currentPage, 1), totalPages);
    const start = (currentPage - 1) * pageSize;
    const pageItems = visible.slice(start, start + pageSize);
    rowsHost.innerHTML = pageItems.length
      ? pageItems.map((item, rowIndex) => isParts
        ? `<tr><td>${start + rowIndex + 1}</td><td class="item-title">${escapeHTML(item.nama)}</td><td><span class="stock-value ${Number(item.stok) === 0 ? 'zero' : ''}">${item.stok}</span></td><td class="money-cell">${rupiah(item.harga)}</td><td>${actions(item.id)}</td></tr>`
        : `<tr><td>${start + rowIndex + 1}</td><td class="item-title">${escapeHTML(item.nama)}</td><td class="money-cell">${rupiah(item.harga)}</td><td>${actions(item.id)}</td></tr>`).join('')
      : `<tr class="master-empty"><td colspan="${isParts ? 5 : 4}"><i class="bi bi-inbox"></i> Tidak ada data yang sesuai pencarian</td></tr>`;
    const from = pageItems.length ? start + 1 : 0;
    const to = start + pageItems.length;
    document.getElementById('masterResultText').textContent = pageItems.length ? `Menampilkan ${from}–${to} dari ${visible.length} data` : 'Tidak ada data ditemukan';
    renderPagination(totalPages);
  }

  async function loadItems() {
    const payload = await request(endpoint, { method: 'GET' });
    items = payload.data || [];
    render();
  }

  document.getElementById('masterModal').addEventListener('show.bs.modal', event => {
    editId = event.relatedTarget?.dataset.edit || null;
    const item = editId ? items.find(row => String(row.id) === String(editId)) : { nama: '', stok: 0, harga: 0 };
    document.querySelector('#masterModal .modal-title').textContent = `${editId ? 'Edit' : 'Tambah'} ${entityName}`;
    document.getElementById('masterFields').innerHTML = `
      <div class="field-full"><label class="form-label">Nama ${entityName}</label><input class="form-control" name="nama" required value="${escapeHTML(item?.nama || '')}"></div>
      ${isParts ? `<div class="field-full"><label class="form-label">Stok</label><input class="form-control" name="stok" type="number" min="0" required value="${item?.stok ?? 0}"></div>` : ''}
      <div class="field-full"><label class="form-label">Harga</label><div class="input-group"><span class="input-group-text">Rp</span><input class="form-control price-field" name="harga" inputmode="numeric" required value="${formatNumber(item?.harga || 0)}"></div></div>`;
  });

  document.getElementById('masterFields').addEventListener('input', event => {
    if (event.target.matches('.price-field')) event.target.value = formatNumber(event.target.value);
  });

  document.getElementById('masterForm').addEventListener('submit', async event => {
    event.preventDefault();
    const formData = new FormData(event.target);
    const body = { nama: String(formData.get('nama') || '').trim(), harga: parseNumber(formData.get('harga')) };
    if (isParts) body.stok = Math.max(0, parseNumber(formData.get('stok')));
    const submitButton = event.target.querySelector('[type="submit"]');
    submitButton.disabled = true;
    try {
      const payload = await request(editId ? `${endpoint}/${editId}` : endpoint, {
        method: editId ? 'PUT' : 'POST',
        body: JSON.stringify(body)
      });
      bootstrap.Modal.getInstance(document.getElementById('masterModal')).hide();
      if (!editId) currentPage = Number.MAX_SAFE_INTEGER;
      await loadItems();
      showToast(payload.message || 'Data berhasil disimpan');
    } catch (error) {
      alert(error.message);
    } finally {
      submitButton.disabled = false;
    }
  });

  document.addEventListener('click', async event => {
    const button = event.target.closest('[data-delete]');
    if (!button) return;
    const confirmation = await Swal.fire({
      title: 'Hapus data?',
      text: 'Data yang sudah dihapus tidak dapat dikembalikan.',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Ya, hapus',
      cancelButtonText: 'Batal',
      confirmButtonColor: '#e6374b',
      cancelButtonColor: '#806432',
      reverseButtons: true,
      focusCancel: true
    });
    if (!confirmation.isConfirmed) return;
    button.disabled = true;
    try {
      const payload = await request(`${endpoint}/${button.dataset.delete}`, { method: 'DELETE' });
      await loadItems();
      showToast(payload.message || 'Data berhasil dihapus');
    } catch (error) {
      alert(error.message);
      button.disabled = false;
    }
  });

  document.getElementById('masterPagination').addEventListener('click', event => {
    const button = event.target.closest('button');
    if (!button || button.disabled) return;
    const totalPages = Math.max(1, Math.ceil(filteredItems().length / pageSize));
    if (button.dataset.page === 'prev') currentPage = Math.max(1, currentPage - 1);
    else if (button.dataset.page === 'next') currentPage = Math.min(totalPages, currentPage + 1);
    else currentPage = Number(button.dataset.page);
    render();
  });

  search.addEventListener('input', () => { currentPage = 1; render(); });
  document.getElementById('resetMasterFilters').addEventListener('click', () => { search.value = ''; currentPage = 1; render(); });
  loadItems().catch(error => {
    rowsHost.innerHTML = `<tr class="master-empty"><td colspan="${isParts ? 5 : 4}">${escapeHTML(error.message)}</td></tr>`;
  });
})();
