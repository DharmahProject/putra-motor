(() => {
  'use strict';

  const endpoints = { users: 'API/public/api/users', employees: 'API/public/api/karyawan', profile: 'API/public/api/profile' };
  const modules = ['Dashboard', 'Transaksi', 'Sparepart', 'Jasa', 'Pengaturan'];
  let users = [];
  let employees = [];
  let profile = {};
  let activeTab = 'users';
  let editId = null;
  const escapeHTML = value => String(value ?? '').replace(/[&<>'"]/g, char => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;' }[char]));
  const initials = name => String(name).split(/\s+/).filter(Boolean).slice(0, 2).map(word => word[0]).join('').toUpperCase();
  const status = active => `<span class="status-pill ${active ? 'active' : 'inactive'}">${active ? 'Aktif' : 'Nonaktif'}</span>`;
  const actions = (type, id) => `<div class="settings-actions"><button class="settings-action edit" type="button" data-edit-type="${type}" data-edit-id="${id}" aria-label="Edit"><i class="bi bi-pencil-fill"></i></button><button class="settings-action delete" type="button" data-delete-type="${type}" data-delete-id="${id}" aria-label="Hapus"><i class="bi bi-trash3"></i></button></div>`;
  const person = item => `<div class="person-cell"><span class="person-avatar">${escapeHTML(initials(item.name))}</span><span class="person-copy"><strong>${escapeHTML(item.name)}</strong><small>${escapeHTML(item.email)}</small></span></div>`;
  const permissionList = permissions => `<div class="permission-list">${permissions.map(item => `<span class="permission-chip">${escapeHTML(item)}</span>`).join('')}</div>`;

  async function request(url, options = {}) {
    const headers = { Accept: 'application/json', ...(options.headers || {}) };
    if (!(options.body instanceof FormData)) headers['Content-Type'] = 'application/json';
    const response = await fetch(url, {
      headers,
      ...options
    });
    const payload = await response.json().catch(() => ({}));
    if (!response.ok) {
      const validation = payload.errors ? Object.values(payload.errors).flat()[0] : '';
      throw new Error(validation || payload.message || 'Permintaan gagal diproses');
    }
    return payload;
  }

  async function loadData() {
    const [userPayload, employeePayload, profilePayload] = await Promise.all([
      request(endpoints.users),
      request(endpoints.employees),
      request(endpoints.profile)
    ]);
    users = (userPayload.data || []).map(item => ({ ...item, permissions: item.permissions || [], active: Boolean(item.active) }));
    employees = (employeePayload.data || []).map(item => ({ ...item, employeeId: item.employee_id, active: Boolean(item.active) }));
    profile = profilePayload.data || {};
    fillProfile();
    render();
  }

  function setLogoPreview(url) {
    const preview = document.getElementById('profileLogoPreview');
    preview.innerHTML = url ? `<img src="${escapeHTML(url)}" alt="Preview logo bengkel">` : '<i class="bi bi-image"></i><span>Belum ada logo</span>';
  }

  function fillProfile() {
    const form = document.getElementById('profileForm');
    form.elements.address.value = profile.address || '';
    form.elements.bank_1_name.value = profile.bank_1_name || '';
    form.elements.bank_1_account_name.value = profile.bank_1_account_name || '';
    form.elements.bank_1_account.value = profile.bank_1_account || '';
    form.elements.bank_2_name.value = profile.bank_2_name || '';
    form.elements.bank_2_account_name.value = profile.bank_2_account_name || '';
    form.elements.bank_2_account.value = profile.bank_2_account || '';
    setLogoPreview(profile.logo_url || '');
  }

  function showToast(message) {
    const toast = document.getElementById('settingsToast');
    toast.querySelector('span').textContent = message;
    bootstrap.Toast.getOrCreateInstance(toast, { delay: 1800 }).show();
  }

  function filtered(type) {
    const query = document.getElementById('settingsSearch').value.trim().toLowerCase();
    const rows = type === 'users' ? users : employees;
    return rows.filter(item => Object.values(item).some(value => String(value).toLowerCase().includes(query)));
  }

  function renderUsers() {
    const rows = filtered('users');
    document.getElementById('userRows').innerHTML = rows.length ? rows.map(item => `<tr><td>${person(item)}</td><td><span class="role-badge">${escapeHTML(item.role)}</span></td><td>${permissionList(item.permissions)}</td><td>${status(item.active)}</td><td>${actions('users', item.id)}</td></tr>`).join('') : '<tr class="settings-empty"><td colspan="5">Tidak ada user yang sesuai pencarian</td></tr>';
    document.getElementById('userCards').innerHTML = rows.length ? rows.map(item => `<article class="settings-mobile-card"><header>${person(item)}${status(item.active)}</header><dl><dt>Peran</dt><dd>${escapeHTML(item.role)}</dd><dt>Hak akses</dt><dd>${permissionList(item.permissions)}</dd></dl>${actions('users', item.id)}</article>`).join('') : '<div class="settings-mobile-card">Tidak ada user yang sesuai pencarian</div>';
  }

  function renderEmployees() {
    const rows = filtered('employees');
    document.getElementById('employeeRows').innerHTML = rows.length ? rows.map(item => `<tr><td>${person(item)}</td><td><strong>${escapeHTML(item.employeeId)}</strong></td><td>${escapeHTML(item.phone)}</td><td><span class="shift-badge">${escapeHTML(item.shift)}</span></td><td>${status(item.active)}</td><td>${actions('employees', item.id)}</td></tr>`).join('') : '<tr class="settings-empty"><td colspan="6">Tidak ada service advisor yang sesuai pencarian</td></tr>';
    document.getElementById('employeeCards').innerHTML = rows.length ? rows.map(item => `<article class="settings-mobile-card"><header>${person(item)}${status(item.active)}</header><dl><dt>ID Karyawan</dt><dd>${escapeHTML(item.employeeId)}</dd><dt>Telepon</dt><dd>${escapeHTML(item.phone)}</dd><dt>Shift</dt><dd>${escapeHTML(item.shift)}</dd></dl>${actions('employees', item.id)}</article>`).join('') : '<div class="settings-mobile-card">Tidak ada service advisor yang sesuai pencarian</div>';
  }

  function render() {
    renderUsers(); renderEmployees();
    document.getElementById('activeUserCount').textContent = users.filter(item => item.active).length;
    document.getElementById('advisorCount').textContent = employees.filter(item => item.active).length;
  }

  function switchTab(type) {
    activeTab = type;
    document.querySelectorAll('[data-settings-tab]').forEach(button => {
      const active = button.dataset.settingsTab === type;
      button.classList.toggle('active', active);
      button.setAttribute('aria-selected', String(active));
    });
    document.getElementById('usersPanel').classList.toggle('active', type === 'users');
    document.getElementById('usersPanel').hidden = type !== 'users';
    document.getElementById('employeesPanel').classList.toggle('active', type === 'employees');
    document.getElementById('employeesPanel').hidden = type !== 'employees';
    document.getElementById('profilePanel').classList.toggle('active', type === 'profile');
    document.getElementById('profilePanel').hidden = type !== 'profile';
    document.getElementById('settingsAddButton').hidden = type === 'profile';
    document.getElementById('settingsToolbar').hidden = type === 'profile';
    if (type === 'profile') return;
    const addLabel = type === 'users' ? 'Tambah User' : 'Tambah Karyawan';
    document.querySelector('#settingsAddButton span').textContent = addLabel;
    document.getElementById('settingsSearch').placeholder = type === 'users' ? 'Cari nama atau email user...' : 'Cari service advisor...';
    document.getElementById('settingsSearch').value = '';
    render();
  }

  function openEditor(type, id = null) {
    editId = id === null ? null : Number(id);
    const item = editId === null ? null : (type === 'users' ? users : employees).find(row => row.id === editId);
    const title = `${editId === null ? 'Tambah' : 'Edit'} ${type === 'users' ? 'User' : 'Service Advisor'}`;
    document.querySelector('#settingsModal .modal-title').textContent = title;
    document.getElementById('settingsForm').dataset.type = type;
    const assignedEmployeeIds = new Set(users.filter(user => user.id !== item?.id).map(user => Number(user.karyawan_id)).filter(Boolean));
    const employeeOptions = employees
      .filter(employee => !assignedEmployeeIds.has(Number(employee.id)))
      .map(employee => `<option value="${employee.id}" data-email="${escapeHTML(employee.email)}" ${Number(item?.karyawan_id) === Number(employee.id) ? 'selected' : ''}>${escapeHTML(employee.name)} — ${escapeHTML(employee.employeeId)}</option>`)
      .join('');
    document.getElementById('settingsFields').innerHTML = type === 'users' ? `
      <div class="field-full"><label class="form-label">Nama User / Karyawan</label><select class="form-select employee-select" id="userEmployee" name="karyawanId" required><option value=""></option>${employeeOptions}</select></div>
      <div class="field-full"><label class="form-label">Email</label><input class="form-control" id="userEmail" type="email" disabled value="${escapeHTML(item?.email || '')}" placeholder="Email otomatis dari data karyawan"></div>
      <div><label class="form-label">Password${item ? ' <small class="text-muted">(opsional)</small>' : ''}</label><input class="form-control" name="password" type="password" minlength="6" autocomplete="new-password" ${item ? 'placeholder="Kosongkan jika tidak diubah"' : 'required'}></div>
      <div><label class="form-label">Konfirmasi Password</label><input class="form-control" name="passwordConfirmation" type="password" minlength="6" autocomplete="new-password" ${item ? 'placeholder="Ulangi password baru"' : 'required'}></div>
      <div><label class="form-label">Peran</label><select class="form-select" name="role"><option>Administrator</option><option>Kasir</option><option>Staf Gudang</option><option>Service Advisor</option></select></div>
      <div><label class="form-label">Status</label><select class="form-select" name="active"><option value="1">Aktif</option><option value="0">Nonaktif</option></select></div>
      <div class="field-full"><label class="form-label">Hak akses modul</label><div class="permission-grid">${modules.map(module => `<label class="permission-option"><input type="checkbox" name="permissions" value="${module}" ${(item?.permissions || []).includes(module) ? 'checked' : ''}><span>${module}</span></label>`).join('')}</div></div>` : `
      <div class="field-full auto-id-note"><i class="bi bi-magic"></i><span>ID karyawan dibuat otomatis oleh sistem dengan format <strong>ID-PM-001</strong>.</span></div>
      <div><label class="form-label">Nama lengkap</label><input class="form-control" name="name" required value="${escapeHTML(item?.name || '')}"></div>
      <div><label class="form-label">Nomor telepon</label><input class="form-control" name="phone" required value="${escapeHTML(item?.phone || '')}"></div>
      <div><label class="form-label">Email</label><input class="form-control" name="email" type="email" required value="${escapeHTML(item?.email || '')}"></div>
      <div><label class="form-label">Shift</label><select class="form-select" name="shift"><option>Pagi</option><option>Siang</option><option>Full Day</option></select></div>
      <div><label class="form-label">Status</label><select class="form-select" name="active"><option value="1">Aktif</option><option value="0">Nonaktif</option></select></div>`;
    if (item) {
      document.querySelector('[name="active"]').value = item.active ? '1' : '0';
      const select = document.querySelector(type === 'users' ? '[name="role"]' : '[name="shift"]');
      select.value = type === 'users' ? item.role : item.shift;
    }
    if (type === 'users') {
      const employeeSelect = document.getElementById('userEmployee');
      const syncEmail = () => {
        const employee = employees.find(row => Number(row.id) === Number(employeeSelect.value));
        document.getElementById('userEmail').value = employee?.email || '';
      };
      employeeSelect.addEventListener('change', syncEmail);
      if (window.jQuery?.fn?.select2) {
        window.jQuery(employeeSelect).select2({
          dropdownParent: window.jQuery('#settingsModal'),
          placeholder: 'Pilih karyawan',
          allowClear: true,
          width: '100%'
        }).on('change', syncEmail);
      }
      syncEmail();
    }
    bootstrap.Modal.getOrCreateInstance(document.getElementById('settingsModal')).show();
  }

  document.querySelectorAll('[data-settings-tab]').forEach(button => button.addEventListener('click', () => switchTab(button.dataset.settingsTab)));
  document.getElementById('settingsSearch').addEventListener('input', render);
  document.getElementById('settingsAddButton').addEventListener('click', () => openEditor(activeTab));
  document.getElementById('profileLogo').addEventListener('change', event => {
    const [file] = event.target.files;
    if (file) setLogoPreview(URL.createObjectURL(file));
    else setLogoPreview(profile.logo_url || '');
  });

  document.getElementById('profileForm').addEventListener('submit', async event => {
    event.preventDefault();
    const form = event.currentTarget;
    const submitButton = form.querySelector('[type="submit"]');
    submitButton.disabled = true;
    try {
      const payload = await request(endpoints.profile, { method: 'POST', body: new FormData(form) });
      profile = payload.data || {};
      form.elements.logo.value = '';
      fillProfile();
      showToast(payload.message || 'Profil bengkel berhasil disimpan');
    } catch (error) {
      alert(error.message);
    } finally {
      submitButton.disabled = false;
    }
  });

  document.addEventListener('click', async event => {
    const edit = event.target.closest('[data-edit-type]');
    if (edit) return openEditor(edit.dataset.editType, edit.dataset.editId);
    const remove = event.target.closest('[data-delete-type]');
    if (!remove) return;
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
    const type = remove.dataset.deleteType;
    const id = Number(remove.dataset.deleteId);
    remove.disabled = true;
    try {
      const payload = await request(`${endpoints[type]}/${id}`, { method: 'DELETE' });
      await loadData();
      showToast(payload.message || 'Data berhasil dihapus');
    } catch (error) {
      alert(error.message);
      remove.disabled = false;
    }
  });

  document.getElementById('settingsForm').addEventListener('submit', async event => {
    event.preventDefault();
    const type = event.currentTarget.dataset.type;
    const data = new FormData(event.currentTarget);
    const current = editId === null ? null : (type === 'users' ? users : employees).find(item => item.id === editId);
    const base = { active: data.get('active') === '1' };
    let record;
    if (type === 'users') {
      const password = String(data.get('password') || '');
      const confirmation = String(data.get('passwordConfirmation') || '');
      if (password !== confirmation) {
        const confirmationInput = event.currentTarget.elements.passwordConfirmation;
        confirmationInput.setCustomValidity('Konfirmasi password tidak sama');
        confirmationInput.reportValidity();
        confirmationInput.addEventListener('input', () => confirmationInput.setCustomValidity(''), { once: true });
        return;
      }
      record = { ...base, karyawan_id: Number(data.get('karyawanId')), role: String(data.get('role')), permissions: data.getAll('permissions').map(String), password };
    } else {
      record = { ...base, name: String(data.get('name')).trim(), email: String(data.get('email')).trim(), phone: String(data.get('phone')).trim(), shift: String(data.get('shift')) };
    }
    const submitButton = event.currentTarget.querySelector('[type="submit"]');
    submitButton.disabled = true;
    try {
      const payload = await request(editId === null ? endpoints[type] : `${endpoints[type]}/${editId}`, {
        method: editId === null ? 'POST' : 'PUT',
        body: JSON.stringify(record)
      });
      bootstrap.Modal.getInstance(document.getElementById('settingsModal')).hide();
      await loadData();
      showToast(payload.message || 'Perubahan berhasil disimpan');
    } catch (error) {
      alert(error.message);
    } finally {
      submitButton.disabled = false;
    }
  });

  render();
  loadData().catch(error => {
    document.getElementById('userRows').innerHTML = `<tr class="settings-empty"><td colspan="5">${escapeHTML(error.message)}</td></tr>`;
    document.getElementById('employeeRows').innerHTML = `<tr class="settings-empty"><td colspan="6">${escapeHTML(error.message)}</td></tr>`;
  });
})();
