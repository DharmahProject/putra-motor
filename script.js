(() => {
    'use strict';

    const endpoint = 'API/public/api/transaksi';
    const rupiah = new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    });
    const escapeHTML = value => String(value ?? '').replace(/[&<>'"]/g, char => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;' }[char]));

    const tableConfig = [
        { table: 'partsTable', subtotal: 'partSubtotal', count: 'partCount', summary: 'summaryParts', summaryCount: 'summaryPartCount', emptyText: 'Tidak ada sparepart', colspan: 8 },
        { table: 'serviceTable', subtotal: 'serviceSubtotal', count: 'serviceCount', summary: 'summaryService', summaryCount: 'summaryServiceCount', emptyText: 'Tidak ada jasa servis', colspan: 6 },
        { table: 'otherTable', subtotal: 'otherSubtotal', count: 'otherCount', summary: 'summaryOther', summaryCount: 'summaryOtherCount', emptyText: 'Tidak ada biaya lain', colspan: 7 }
    ];

    const masterCatalog = { sparepart: [], service: [] };
    let currentNomor = null;
    let currentStatus = 'Dalam Pengerjaan';
    let currentPaymentStatus = 'Belum Bayar';
    let currentPaymentMethod = 'Tunai';
    const today = () => {
        const now = new Date();
        return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`;
    };
    let currentTanggal = today();
    let saving = false;

    const parseNumber = value => Number(String(value).replace(/[^0-9]/g, '')) || 0;
    const formatDate = value => {
        const date = new Date(`${value}T00:00:00`);
        if (Number.isNaN(date.getTime())) return value || '-';
        return date.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
    };
    const numberFormat = new Intl.NumberFormat('id-ID');
    const formatNumberInput = value => {
        const digits = String(value).replace(/[^0-9]/g, '');
        return digits ? numberFormat.format(Number(digits)) : '';
    };

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

    function showToast(message) {
        const toastEl = document.getElementById('appToast');
        toastEl.querySelector('span').textContent = message;
        bootstrap.Toast.getOrCreateInstance(toastEl, { delay: 2200 }).show();
    }

    function updateTotals() {
        let total = 0;

        tableConfig.forEach(config => {
            const table = document.getElementById(config.table);
            const tbody = table.tBodies[0];
            const allRows = [...tbody.rows].filter(row => !row.classList.contains('empty-row'));
            let subtotal = 0;

            if (!allRows.length && !tbody.querySelector('.empty-row')) {
                tbody.insertAdjacentHTML('beforeend', `<tr class="empty-row"><td colspan="${config.colspan}"><i class="bi bi-inbox"></i><span>${config.emptyText}</span></td></tr>`);
            }

            allRows.forEach((row, index) => {
                row.cells[0].textContent = index + 1;
                const qty = Math.max(1, parseInt(row.querySelector('.qty-input')?.value, 10) || 1);
                const priceInput = row.querySelector('.price-input');
                if (priceInput) row.dataset.price = parseNumber(priceInput.value);
                const rowTotal = qty * Number(row.dataset.price || 0);
                row.querySelector('.row-total').textContent = rupiah.format(rowTotal);
                subtotal += rowTotal;
            });

            total += subtotal;
            document.getElementById(config.subtotal).textContent = rupiah.format(subtotal);
            document.getElementById(config.summary).textContent = rupiah.format(subtotal);
            document.getElementById(config.count).textContent = `${allRows.length} Item`;
            document.getElementById(config.summaryCount).textContent = allRows.length;
        });

        const discount = parseNumber(document.getElementById('discount').value);
        document.getElementById('grandTotal').textContent = rupiah.format(Math.max(0, total - discount));
    }

    document.addEventListener('input', event => {
        if (event.target.matches('.currency-input, .price-input, #discount, #odometer')) {
            event.target.value = formatNumberInput(event.target.value);
        }
        if (event.target.matches('.qty-input, .price-input')) updateTotals();
        if (event.target.matches('.inline-name')) event.target.closest('tr').dataset.name = event.target.value;
        if (event.target.id === 'discount') updateTotals();
    });

    function addInlineRow(type, values = null) {
        const row = document.createElement('tr');
        row.className = 'inline-edit-row';
        row.dataset.name = values?.nama || '';
        row.dataset.price = String(values?.harga || 0);

        if (type === 'service') {
            row.innerHTML = `
        <td></td>
        <td class="item-name"><input class="inline-input inline-name" type="text" placeholder="Nama Jasa" aria-label="Nama jasa"></td>
        <td><input class="qty-input" type="number" min="1" value="1" aria-label="Jumlah jasa"></td>
        <td><div class="money-input"><span>Rp</span><input class="price-input currency-input" type="text" inputmode="numeric" pattern="[0-9.]*" placeholder="0" aria-label="Harga jasa"></div></td>
        <td class="row-total">Rp 0</td>
        <td><button class="delete-btn" type="button" aria-label="Hapus jasa"><i class="bi bi-trash3"></i></button></td>`;
            const serviceBody = document.querySelector('#serviceTable tbody');
            serviceBody.querySelector('.empty-row')?.remove();
            serviceBody.appendChild(row);
        } else {
            row.innerHTML = `
        <td></td>
        <td class="item-name"><div class="inline-name-wrap"><input class="inline-input inline-name" type="text" placeholder="Nama Biaya" aria-label="Nama biaya"><span class="manual-badge">Manual</span></div></td>
        <td><input class="inline-input note-input" type="text" placeholder="Keterangan" aria-label="Keterangan biaya"></td>
        <td><input class="qty-input" type="number" min="1" value="1" aria-label="Jumlah biaya"></td>
        <td><div class="money-input"><span>Rp</span><input class="price-input currency-input" type="text" inputmode="numeric" pattern="[0-9.]*" placeholder="0" aria-label="Harga biaya"></div></td>
        <td class="row-total">Rp 0</td>
        <td><button class="delete-btn" type="button" aria-label="Hapus biaya"><i class="bi bi-trash3"></i></button></td>`;
            const otherBody = document.querySelector('#otherTable tbody');
            otherBody.querySelector('.empty-row')?.remove();
            otherBody.appendChild(row);
        }

        if (values) {
            row.querySelector('.inline-name').value = values.nama || '';
            row.querySelector('.qty-input').value = values.qty || 1;
            row.querySelector('.price-input').value = formatNumberInput(values.harga || 0);
            const note = row.querySelector('.note-input');
            if (note) note.value = values.keterangan || '';
        }

        updateTotals();
        if (!values) {
            row.querySelector('.inline-name').focus();
            row.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }

    function initializeMasterSelect(row, type, values = null) {
        const select = row.querySelector('.master-select');
        const catalog = masterCatalog[type];
        catalog.forEach(item => {
            select.appendChild(new Option(item.text, item.id, false, false));
        });
        if (values?.masterId && ![...select.options].some(option => option.value === String(values.masterId))) {
            select.appendChild(new Option(values.nama || 'Item', String(values.masterId), false, false));
        }

        const applySelection = (value, overrides = null) => {
            const item = catalog.find(entry => String(entry.id) === String(value));
            row.dataset.name = overrides?.nama || item?.text || '';
            row.dataset.price = overrides?.harga ?? item?.price ?? 0;
            row.querySelector('.unit-price').textContent = rupiah.format(Number(row.dataset.price) || 0);
            if (type === 'sparepart') {
                row.querySelector('.stock-badge').innerHTML = item
                    ? `<i class="bi bi-box-seam"></i> Stok: ${escapeHTML(item.stock)}`
                    : '<i class="bi bi-box-seam"></i> Pilih sparepart';
                const unit = overrides?.satuan || item?.unit || 'Pcs';
                row.querySelector('.unit-select').innerHTML = `<option>${escapeHTML(unit)}</option>`;
            }
            if (overrides?.qty) row.querySelector('.qty-input').value = overrides.qty;
            updateTotals();
        };

        if (window.jQuery?.fn?.select2) {
            const $select = window.jQuery(select);
            $select.select2({
                placeholder: type === 'sparepart' ? 'Pilih sparepart' : 'Pilih jasa servis',
                allowClear: true,
                width: '100%'
            }).on('change', event => applySelection(event.target.value));
            if (values?.masterId) {
                $select.val(String(values.masterId)).trigger('change');
                applySelection(String(values.masterId), values);
            }
        } else {
            select.addEventListener('change', event => applySelection(event.target.value));
            if (values?.masterId) {
                select.value = String(values.masterId);
                applySelection(String(values.masterId), values);
            }
        }
        if (!values) select.focus();
    }

    function addMasterRow(type, values = null) {
        const row = document.createElement('tr');
        row.className = 'inline-edit-row master-row';
        row.dataset.name = '';
        row.dataset.price = '0';

        if (type === 'sparepart') {
            row.innerHTML = `
                <td></td>
                <td class="item-name"><select class="master-select" aria-label="Pilih sparepart"><option></option></select></td>
                <td><span class="stock-badge"><i class="bi bi-box-seam"></i> Pilih sparepart</span></td>
                <td><input class="qty-input" type="number" min="1" value="1" aria-label="Jumlah sparepart"></td>
                <td><select class="unit-select"><option>Pcs</option></select></td>
                <td class="unit-price">Rp 0</td><td class="row-total">Rp 0</td>
                <td><button class="delete-btn" type="button" aria-label="Hapus sparepart"><i class="bi bi-trash3"></i></button></td>`;
            const body = document.querySelector('#partsTable tbody');
            body.querySelector('.empty-row')?.remove();
            body.appendChild(row);
        } else {
            row.innerHTML = `
                <td></td>
                <td class="item-name"><select class="master-select" aria-label="Pilih jasa servis"><option></option></select></td>
                <td><input class="qty-input" type="number" min="1" value="1" aria-label="Jumlah jasa"></td>
                <td class="unit-price">Rp 0</td><td class="row-total">Rp 0</td>
                <td><button class="delete-btn" type="button" aria-label="Hapus jasa"><i class="bi bi-trash3"></i></button></td>`;
            const body = document.querySelector('#serviceTable tbody');
            body.querySelector('.empty-row')?.remove();
            body.appendChild(row);
        }

        initializeMasterSelect(row, type, values);
        updateTotals();
        if (!values) row.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    function renderItems(items) {
        ['partsTable', 'serviceTable', 'otherTable'].forEach(id => {
            document.querySelector(`#${id} tbody`).innerHTML = '';
        });
        (items || []).forEach(item => {
            const values = {
                masterId: item.masterpart_id || item.masterjasa_id || '',
                nama: item.nama,
                harga: item.harga,
                qty: item.qty,
                satuan: item.satuan || 'Pcs',
                keterangan: item.keterangan || ''
            };
            if (item.jenis === 'sparepart') addMasterRow('sparepart', values);
            else if (item.jenis === 'jasa' && item.masterjasa_id) addMasterRow('service', values);
            else if (item.jenis === 'jasa') addInlineRow('service', values);
            else addInlineRow('other', values);
        });
        updateTotals();
    }

    function profileText(id) {
        const value = document.getElementById(id).textContent.trim();
        return value === 'Belum diisi' ? '' : value;
    }

    function collectItems() {
        const items = [];
        let incomplete = false;
        const pushRow = (row, item) => {
            if (!item.nama) {
                incomplete = true;
                return;
            }
            items.push(item);
        };

        document.querySelectorAll('#partsTable tbody tr').forEach(row => {
            if (row.classList.contains('empty-row')) return;
            const masterId = row.querySelector('.master-select')?.value || '';
            pushRow(row, {
                jenis: 'sparepart',
                masterpart_id: masterId ? Number(masterId) : null,
                nama: (row.dataset.name || '').trim(),
                satuan: row.querySelector('.unit-select')?.value || 'Pcs',
                qty: Math.max(1, parseInt(row.querySelector('.qty-input')?.value, 10) || 1),
                harga: Number(row.dataset.price || 0)
            });
        });
        document.querySelectorAll('#serviceTable tbody tr').forEach(row => {
            if (row.classList.contains('empty-row')) return;
            const masterId = row.querySelector('.master-select')?.value || '';
            pushRow(row, {
                jenis: 'jasa',
                masterjasa_id: masterId ? Number(masterId) : null,
                nama: (row.dataset.name || row.querySelector('.inline-name')?.value || '').trim(),
                qty: Math.max(1, parseInt(row.querySelector('.qty-input')?.value, 10) || 1),
                harga: Number(row.dataset.price || 0)
            });
        });
        document.querySelectorAll('#otherTable tbody tr').forEach(row => {
            if (row.classList.contains('empty-row')) return;
            pushRow(row, {
                jenis: 'lainnya',
                nama: (row.dataset.name || row.querySelector('.inline-name')?.value || '').trim(),
                keterangan: row.querySelector('.note-input')?.value.trim() || null,
                qty: Math.max(1, parseInt(row.querySelector('.qty-input')?.value, 10) || 1),
                harga: Number(row.dataset.price || 0)
            });
        });

        return { items, incomplete };
    }

    function statusFor() {
        return currentStatus === 'Selesai' ? 'Selesai' : 'Dalam Pengerjaan';
    }

    async function saveTransaction(button) {
        if (saving || document.body.dataset.detailMode === 'view') return;
        const pelanggan = profileText('customerNameDisplay');
        const telepon = profileText('customerPhoneDisplay');
        const kendaraan = profileText('vehicleNameDisplay');
        const plat = profileText('vehiclePlateDisplay');
        if (!pelanggan || !telepon || !kendaraan || !plat) {
            alert('Lengkapi nama pelanggan, telepon, kendaraan, dan plat.');
            return;
        }
        const collected = collectItems();
        if (collected.incomplete) {
            alert('Lengkapi nama pada setiap baris sparepart, jasa, atau biaya.');
            return;
        }

        const body = {
            tanggal: currentTanggal,
            pelanggan,
            telepon,
            kendaraan,
            plat,
            advisor: document.getElementById('advisor').value || null,
            odometer: document.getElementById('odometer').value,
            diskon: parseNumber(document.getElementById('discount').value),
            status: statusFor(),
            status_pembayaran: currentPaymentStatus,
            metode_pembayaran: currentPaymentMethod,
            items: collected.items
        };
        saving = true;
        button.disabled = true;
        try {
            const payload = await request(currentNomor ? `${endpoint}/${encodeURIComponent(currentNomor)}` : endpoint, {
                method: currentNomor ? 'PUT' : 'POST',
                body: JSON.stringify(body)
            });
            showHeader(payload.data, 'edit');
            renderItems(payload.data.items || []);
            const url = new URL(window.location.href);
            url.searchParams.set('mode', 'edit');
            url.searchParams.set('id', payload.data.nomor);
            history.replaceState({}, '', url);
            showToast(payload.message || 'Transaksi berhasil disimpan');
        } catch (error) {
            alert(error.message);
        } finally {
            saving = false;
            if (document.body.dataset.detailMode !== 'view') button.disabled = false;
        }
    }

    document.addEventListener('click', event => {
        const masterButton = event.target.closest('[data-add-master]');
        if (masterButton && !masterButton.disabled) addMasterRow(masterButton.dataset.addMaster);

        const inlineButton = event.target.closest('[data-add-inline]');
        if (inlineButton && !inlineButton.disabled) addInlineRow(inlineButton.dataset.addInline);

        const deleteButton = event.target.closest('.delete-btn');
        if (deleteButton && !deleteButton.disabled) {
            deleteButton.closest('tr').remove();
            updateTotals();
        }

        const saveButton = event.target.closest('[data-save]');
        if (saveButton && !saveButton.disabled) saveTransaction(saveButton);
    });

    document.querySelectorAll('.transaction-tabs .nav-link').forEach(button => {
        button.addEventListener('click', () => {
            document.querySelectorAll('.transaction-tabs .nav-link').forEach(tab => tab.classList.remove('active'));
            button.classList.add('active');
            document.getElementById(button.dataset.target).scrollIntoView({ behavior: 'smooth', block: 'center' });
        });
    });

    document.getElementById('partSearch').addEventListener('input', event => {
        const query = event.target.value.trim().toLowerCase();
        [...document.querySelectorAll('#partsTable tbody tr')].forEach(row => {
            if (row.classList.contains('empty-row')) {
                row.hidden = false;
                return;
            }
            row.hidden = !(row.dataset.name || '').toLowerCase().includes(query);
        });
    });

    const profileModal = document.getElementById('profileEditModal');
    profileModal.addEventListener('show.bs.modal', event => {
        const type = event.relatedTarget?.dataset.editType || 'customer';
        const isCustomer = type === 'customer';
        document.getElementById('profileEditType').value = type;
        profileModal.querySelector('.modal-title').textContent = isCustomer ? 'Edit Pelanggan' : 'Edit Kendaraan';
        document.getElementById('profilePrimaryLabel').textContent = isCustomer ? 'Nama pelanggan' : 'Nama kendaraan';
        document.getElementById('profileSecondaryLabel').textContent = isCustomer ? 'Nomor telepon' : 'Nomor plat';
        document.getElementById('profilePrimary').value = profileText(isCustomer ? 'customerNameDisplay' : 'vehicleNameDisplay');
        document.getElementById('profileSecondary').value = profileText(isCustomer ? 'customerPhoneDisplay' : 'vehiclePlateDisplay');
        document.getElementById('profileSecondary').setAttribute('inputmode', isCustomer ? 'tel' : 'text');
    });

    document.getElementById('profileEditForm').addEventListener('submit', event => {
        event.preventDefault();
        const type = document.getElementById('profileEditType').value;
        const isCustomer = type === 'customer';
        document.getElementById(isCustomer ? 'customerNameDisplay' : 'vehicleNameDisplay').textContent = document.getElementById('profilePrimary').value.trim();
        document.getElementById(isCustomer ? 'customerPhoneDisplay' : 'vehiclePlateDisplay').textContent = document.getElementById('profileSecondary').value.trim().toUpperCase();
        bootstrap.Modal.getInstance(profileModal).hide();
    });

    function showHeader(record, nextMode) {
        currentNomor = record?.nomor || null;
        currentStatus = record?.status === 'Selesai' ? 'Selesai' : 'Dalam Pengerjaan';
        currentTanggal = record?.tanggal || currentTanggal;
        document.body.dataset.detailMode = nextMode;
        document.body.classList.toggle('view-mode', nextMode === 'view');
        document.getElementById('detailPageTitle').textContent = nextMode === 'view' ? 'Detail Transaksi Servis' : nextMode === 'edit' ? 'Edit Transaksi Servis' : 'Transaksi Servis Baru';
        document.getElementById('transactionNumber').textContent = record?.nomor || 'Otomatis';
        document.getElementById('transactionDate').textContent = formatDate(currentTanggal);
        const paymentStatus = record?.status_pembayaran === 'Lunas' ? 'Lunas' : 'Belum Bayar';
        currentPaymentStatus = paymentStatus;
        currentPaymentMethod = record?.metode_pembayaran || 'Tunai';
        document.getElementById('transactionStatus').innerHTML = `<i class="bi bi-circle-fill"></i> ${escapeHTML(currentStatus)}`;
        document.getElementById('paymentStatusBadge').innerHTML = `<i class="bi bi-credit-card"></i> ${escapeHTML(paymentStatus)}`;
        document.getElementById('customerNameDisplay').textContent = record?.pelanggan || 'Belum diisi';
        document.getElementById('customerPhoneDisplay').textContent = record?.telepon || 'Belum diisi';
        document.getElementById('vehicleNameDisplay').textContent = record?.kendaraan || 'Belum diisi';
        document.getElementById('vehiclePlateDisplay').textContent = record?.plat || 'Belum diisi';
        document.getElementById('odometer').value = record ? formatNumberInput(record.odometer || '') : '';
        document.getElementById('discount').value = formatNumberInput(record?.diskon || 0);

        const advisor = document.getElementById('advisor');
        const advisorName = record?.advisor || '';
        if (advisorName && ![...advisor.options].some(option => option.value === advisorName)) {
            advisor.appendChild(new Option(advisorName, advisorName));
        }
        advisor.value = advisorName;

    }

    function lockView() {
        document.querySelectorAll('.page-content input, .page-content select, .page-content button').forEach(control => {
            control.disabled = true;
            control.setAttribute('aria-disabled', 'true');
        });
        document.querySelectorAll('.edit-button, .add-row-btn, .toolbar-btn, .action-btn, .summary-action').forEach(control => control.classList.add('disabled-control'));
        if (window.jQuery?.fn?.select2) window.jQuery('.master-select').prop('disabled', true);
    }

    async function loadCatalog() {
        const [parts, services] = await Promise.all([
            request('API/public/api/master-sparepart'),
            request('API/public/api/master-jasa')
        ]);
        masterCatalog.sparepart = (parts.data || []).map(item => ({
            id: String(item.id),
            text: item.nama,
            price: Number(item.harga),
            stock: item.stok,
            unit: 'Pcs'
        }));
        masterCatalog.service = (services.data || []).map(item => ({
            id: String(item.id),
            text: item.nama,
            price: Number(item.harga)
        }));
    }

    async function initializeDetailPage() {
        const params = new URLSearchParams(window.location.search);
        const requestedMode = params.get('mode') === 'view' || params.get('mode') === 'edit' ? params.get('mode') : 'new';
        showHeader(null, requestedMode);
        try {
            await loadCatalog();
            if (requestedMode !== 'new' && params.get('id')) {
                const payload = await request(`${endpoint}/${encodeURIComponent(params.get('id'))}`);
                showHeader(payload.data, requestedMode);
                renderItems(payload.data.items || []);
                if (requestedMode === 'view') lockView();
            } else {
                showHeader(null, 'new');
                renderItems([]);
            }
        } catch (error) {
            document.getElementById('detailPageTitle').textContent = 'Transaksi tidak ditemukan';
            document.querySelectorAll('[data-save]').forEach(button => { button.disabled = true; });
            alert(error.message);
        }
    }

    initializeDetailPage();
})();
