(() => {
  const endpoint = 'API/public/api/dashboard';
  const page = document.getElementById('dashboardPage');
  if (!page) return;

  const months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
  const shortMonths = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
  const currentDate = new Date();
  const currentYear = currentDate.getFullYear();
  const currentMonth = currentDate.getMonth() + 1;
  const periodSelect = document.getElementById('dashboardPeriod');
  const yearSelect = document.getElementById('revenueYear');
  let requestSequence = 0;

  const currency = new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0
  });
  const number = new Intl.NumberFormat('id-ID');

  function escapeHTML(value) {
    return String(value ?? '').replace(/[&<>'"]/g, character => ({
      '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;'
    })[character]);
  }

  function initializePeriods() {
    const periodOptions = [];
    const yearOptions = [];
    for (let year = currentYear; year >= currentYear - 4; year -= 1) {
      yearOptions.push(`<option value="${year}">${year}</option>`);
      for (let month = 12; month >= 1; month -= 1) {
        const value = `${year}-${String(month).padStart(2, '0')}`;
        periodOptions.push(`<option value="${value}">${months[month - 1]} ${year}</option>`);
      }
    }
    periodSelect.innerHTML = periodOptions.join('');
    yearSelect.innerHTML = yearOptions.join('');
    periodSelect.value = `${currentYear}-${String(currentMonth).padStart(2, '0')}`;
    yearSelect.value = String(currentYear);
  }

  function setTrend(element, value, suffix = '') {
    const numericValue = value === null || value === undefined ? null : Number(value);
    element.classList.remove('negative', 'neutral');
    if (numericValue === null || Number.isNaN(numericValue)) {
      element.classList.add('neutral');
      element.innerHTML = `<i class="bi bi-dash"></i> Belum ada pembanding${suffix}`;
      return;
    }
    if (numericValue === 0) element.classList.add('neutral');
    if (numericValue < 0) element.classList.add('negative');
    const icon = numericValue > 0 ? 'bi-arrow-up-right' : numericValue < 0 ? 'bi-arrow-down-right' : 'bi-dash';
    const sign = numericValue > 0 ? '+' : '';
    element.innerHTML = `<i class="bi ${icon}"></i> ${sign}${number.format(numericValue)}%${suffix}`;
  }

  function renderSummary(data) {
    document.getElementById('revenueTotal').textContent = currency.format(Number(data.pendapatan?.total || 0));
    document.getElementById('transactionTotal').textContent = number.format(Number(data.transaksi?.total || 0));
    document.getElementById('activeServiceTotal').textContent = number.format(Number(data.servis_berlangsung?.total || 0));
    document.getElementById('averageTotal').textContent = currency.format(Number(data.rata_rata?.total || 0));
    setTrend(document.getElementById('revenueChange'), data.pendapatan?.perubahan_persen);
    setTrend(document.getElementById('transactionChange'), data.transaksi?.perubahan_persen);
    setTrend(document.getElementById('averageChange'), data.rata_rata?.perubahan_persen);
  }

  function niceMaximum(value) {
    if (!value || value <= 0) return 1000000;
    const magnitude = 10 ** Math.floor(Math.log10(value));
    const normalized = value / magnitude;
    const multiplier = normalized <= 1 ? 1 : normalized <= 2 ? 2 : normalized <= 5 ? 5 : 10;
    return multiplier * magnitude;
  }

  function formatAxis(value) {
    if (value >= 1000000000) return `Rp ${number.format(value / 1000000000)} M`;
    if (value >= 1000000) return `Rp ${number.format(value / 1000000)} jt`;
    if (value >= 1000) return `Rp ${number.format(value / 1000)} rb`;
    return `Rp ${number.format(value)}`;
  }

  function bindRevenueTooltips(points) {
    const chart = document.querySelector('.revenue-chart');
    const shell = chart.closest('.chart-shell');
    const tooltip = document.getElementById('revenueTooltip');
    const tooltipTitle = tooltip.querySelector('strong');
    const tooltipValue = tooltip.querySelector('span');

    const show = (circle, index) => {
      const point = points[index];
      const chartBox = chart.getBoundingClientRect();
      const shellBox = shell.getBoundingClientRect();
      tooltipTitle.textContent = months[index];
      tooltipValue.textContent = currency.format(point.value);
      tooltip.style.left = `${chartBox.left - shellBox.left + (point.x / 900 * chartBox.width)}px`;
      tooltip.style.top = `${chartBox.top - shellBox.top + (point.y / 250 * chartBox.height)}px`;
      tooltip.hidden = false;
      circle.classList.add('is-active');
    };
    const hide = circle => {
      tooltip.hidden = true;
      circle.classList.remove('is-active');
    };

    document.querySelectorAll('#revenueDots circle').forEach((circle, index) => {
      circle.addEventListener('mouseenter', () => show(circle, index));
      circle.addEventListener('mouseleave', () => hide(circle));
      circle.addEventListener('focus', () => show(circle, index));
      circle.addEventListener('blur', () => hide(circle));
    });
  }

  function renderRevenue(revenue) {
    const valuesByMonth = new Map((revenue?.bulan || []).map(item => [Number(item.bulan), Number(item.total || 0)]));
    const values = Array.from({ length: 12 }, (_, index) => valuesByMonth.get(index + 1) || 0);
    const maximum = niceMaximum(Math.max(...values));
    const labels = Array.from({ length: 7 }, (_, index) => maximum - (maximum / 6 * index));
    document.getElementById('revenueYLabels').innerHTML = labels.map(value => `<span>${formatAxis(Math.round(value))}</span>`).join('');

    const points = values.map((value, index) => {
      const x = 10 + (880 / 11 * index);
      const y = 232 - (Math.min(value, maximum) / maximum * 220);
      return { x: Number(x.toFixed(2)), y: Number(y.toFixed(2)), value };
    });
    const pointString = points.map(point => `${point.x},${point.y}`).join(' ');
    document.getElementById('revenueLine').setAttribute('points', pointString);
    document.getElementById('revenueArea').setAttribute('d', `M${points[0].x} ${points[0].y} L${points.slice(1).map(point => `${point.x} ${point.y}`).join(' L')} L${points[points.length - 1].x} 232 L${points[0].x} 232 Z`);
    document.getElementById('revenueDots').innerHTML = points.map((point, index) => `<circle cx="${point.x}" cy="${point.y}" r="5" tabindex="0" role="img" aria-label="${shortMonths[index]}: ${escapeHTML(currency.format(point.value))}"></circle>`).join('');
    bindRevenueTooltips(points);
    setTrend(document.getElementById('revenueGrowth'), revenue?.perubahan_persen, ' dari tahun lalu');
  }

  function renderStatuses(statuses) {
    const configuration = {
      'Lunas': { label: 'Lunas', className: 'done', color: '#A8874A' },
      'Belum Bayar': { label: 'Belum Bayar', className: 'waiting', color: '#ffc72f' }
    };
    const rows = (statuses || []).map(item => ({ ...item, ...(configuration[item.status] || { label: item.status, className: 'waiting', color: '#C09A58' }) }));
    const total = rows.reduce((sum, item) => sum + Number(item.jumlah || 0), 0);
    const completed = rows.find(item => item.status === 'Lunas');
    document.getElementById('statusPercent').textContent = `${number.format(Number(completed?.persen || 0))}%`;
    document.getElementById('statusLegend').innerHTML = rows.length ? rows.map(item => `<p><i class="${item.className}"></i><span>${escapeHTML(item.label)}</span><strong>${number.format(Number(item.jumlah || 0))}</strong><small>${number.format(Number(item.persen || 0))}%</small></p>`).join('') : '<p class="dashboard-empty">Belum ada transaksi pada periode ini.</p>';

    if (!total) {
      document.getElementById('statusDonut').style.background = '#eadfcd';
      return;
    }
    let cursor = 0;
    const segments = rows.map(item => {
      const start = cursor;
      cursor += Number(item.persen || 0);
      return `${item.color} ${start}% ${cursor}%`;
    });
    document.getElementById('statusDonut').style.background = `conic-gradient(${segments.join(',')})`;
  }

  function renderToday(today) {
    const percentage = Math.max(0, Math.min(100, Number(today?.persen_selesai || 0)));
    document.getElementById('todaySummary').textContent = `${number.format(Number(today?.selesai || 0))} dari ${number.format(Number(today?.total || 0))} selesai`;
    document.getElementById('todayProgressBar').style.width = `${percentage}%`;
    document.getElementById('todayPercent').textContent = `${number.format(percentage)}%`;
    const stats = [
      ['green', 'bi-check-circle', 'Selesai', today?.selesai],
      ['orange', 'bi-clock', 'Dalam Pengerjaan', today?.menunggu]
    ];
    document.getElementById('todayStats').innerHTML = stats.map(([className, icon, label, value]) => `<div class="${className}"><i class="bi ${icon}"></i><span>${label}</span><strong>${number.format(Number(value || 0))}</strong></div>`).join('');
  }

  function renderPayments(payments) {
    const icons = { Tunai: 'bi-cash', Transfer: 'bi-bank', QRIS: 'bi-qr-code' };
    const host = document.getElementById('paymentBars');
    if (!payments?.length) {
      host.innerHTML = '<p class="dashboard-empty">Belum ada data pembayaran.</p>';
      return;
    }
    host.innerHTML = payments.map(item => {
      const percentage = Math.max(0, Math.min(100, Number(item.persen || 0)));
      return `<div><i class="bi ${icons[item.metode] || 'bi-wallet2'}"></i><span>${escapeHTML(item.metode)}</span><p><b style="width:${percentage}%"></b></p><strong>${number.format(percentage)}%</strong></div>`;
    }).join('');
  }

  function renderLowStock(items) {
    const host = document.getElementById('lowStockList');
    if (!items?.length) {
      host.innerHTML = '<p class="dashboard-empty dashboard-success"><i class="bi bi-check-circle"></i> Semua stok berada di atas batas minimum.</p>';
      return;
    }
    host.innerHTML = items.map(item => `<div><i class="bi bi-box-seam"></i><span><b>${escapeHTML(item.nama)}</b><small>Stok minimum: ${number.format(Number(item.stok_minimum || 0))} pcs</small></span><strong>${number.format(Number(item.stok || 0))} pcs</strong></div>`).join('');
  }

  function renderDashboard(data) {
    renderSummary(data);
    renderRevenue(data.pendapatan_bulanan);
    renderStatuses(data.status_pembayaran);
    renderToday(data.servis_hari_ini);
    renderPayments(data.metode_pembayaran);
    renderLowStock(data.stok_menipis);
  }

  async function loadDashboard(year, month) {
    const sequence = ++requestSequence;
    page.classList.add('is-loading');
    page.setAttribute('aria-busy', 'true');
    try {
      const parameters = new URLSearchParams({ tahun: String(year), bulan: String(month) });
      const response = await fetch(`${endpoint}?${parameters}`, { headers: { Accept: 'application/json' } });
      const payload = await response.json().catch(() => ({}));
      if (!response.ok) throw new Error(payload.message || 'Dashboard tidak dapat dimuat.');
      if (sequence !== requestSequence) return;
      renderDashboard(payload.data || {});
    } catch (error) {
      if (sequence !== requestSequence) return;
      if (window.Swal) {
        Swal.fire({ icon: 'error', title: 'Dashboard gagal dimuat', text: error.message, confirmButtonColor: '#A8874A' });
      }
      console.error(error);
    } finally {
      if (sequence === requestSequence) {
        page.classList.remove('is-loading');
        page.setAttribute('aria-busy', 'false');
      }
    }
  }

  function selectedPeriod() {
    const [year, month] = periodSelect.value.split('-').map(Number);
    return { year, month };
  }

  periodSelect.addEventListener('change', () => {
    const { year, month } = selectedPeriod();
    yearSelect.value = String(year);
    loadDashboard(year, month);
  });

  yearSelect.addEventListener('change', () => {
    const { month } = selectedPeriod();
    const year = Number(yearSelect.value);
    periodSelect.value = `${year}-${String(month).padStart(2, '0')}`;
    loadDashboard(year, month);
  });

  initializePeriods();
  loadDashboard(currentYear, currentMonth);
})();
