<?php
$pageTitle = 'Dashboard | Putra Motor';
$pageDescription = 'Dashboard bengkel Putra Motor';
$pageKey = 'dashboard';
$extraCss = ['dashboard.css'];
$scripts = ['sidebar.js', 'dashboard.js'];

require __DIR__ . '/includes/header.php';
?>
<main class="dashboard-page" id="dashboardPage" aria-busy="true">
  <section class="dashboard-heading">
    <div>
      <h1>Dashboard Overview</h1>
      <p>Pantau performa bengkel secara real-time</p>
    </div>
    <div class="dashboard-tools">
      <label class="month-picker"><i class="bi bi-calendar3"></i><select id="dashboardPeriod" aria-label="Periode dashboard">
          <option value="">Memuat periode...</option>
        </select></label>
      <button class="notification-button" type="button" aria-label="Notifikasi"><i class="bi bi-bell"></i><span></span></button>
    </div>
  </section>

  <section class="kpi-grid" aria-label="Ringkasan dashboard">
    <article class="kpi-card blue">
      <div class="kpi-top"><span class="kpi-icon"><i class="bi bi-coin"></i></span><span>Pendapatan Bulan Ini</span></div><strong id="revenueTotal">Rp 0</strong>
      <div class="kpi-bottom"><em id="revenueChange" class="neutral"><i class="bi bi-dash"></i> 0%</em><svg viewBox="0 0 100 38" aria-hidden="true">
          <polyline points="2,34 17,19 31,25 45,10 60,17 76,12 98,2" />
        </svg></div>
    </article>
    <article class="kpi-card cyan">
      <div class="kpi-top"><span class="kpi-icon"><i class="bi bi-wrench-adjustable"></i></span><span>Total Transaksi</span></div><strong id="transactionTotal">0</strong>
      <div class="kpi-bottom"><em id="transactionChange" class="neutral"><i class="bi bi-dash"></i> 0%</em><svg viewBox="0 0 100 38" aria-hidden="true">
          <polyline points="2,32 18,18 32,24 47,12 60,15 75,8 88,11 98,3" />
        </svg></div>
    </article>
    <article class="kpi-card indigo">
      <div class="kpi-top"><span class="kpi-icon"><i class="bi bi-credit-card"></i></span><span>Dalam Pengerjaan</span></div><strong id="activeServiceTotal">0</strong>
      <div class="kpi-bottom"><em class="info" id="activeServiceInfo"><i class="bi bi-clock"></i> Sedang dikerjakan</em></div>
    </article>
    <article class="kpi-card cyan">
      <div class="kpi-top"><span class="kpi-icon"><i class="bi bi-receipt"></i></span><span>Rata-rata Transaksi</span></div><strong id="averageTotal">Rp 0</strong>
      <div class="kpi-bottom"><em id="averageChange" class="neutral"><i class="bi bi-dash"></i> 0%</em><svg viewBox="0 0 100 38" aria-hidden="true">
          <polyline points="2,34 15,25 28,27 43,16 56,12 70,18 84,9 98,3" />
        </svg></div>
    </article>
  </section>

  <section class="dashboard-main-grid">
    <article class="dash-card revenue-card">
      <header>
        <h2><i class="bi bi-bar-chart-fill"></i> Pendapatan Bulanan</h2><span class="growth-pill neutral" id="revenueGrowth"><i class="bi bi-dash"></i> 0% dari tahun lalu</span><select id="revenueYear" aria-label="Tahun grafik pendapatan"></select>
      </header>
      <div class="chart-shell">
        <div class="y-labels" id="revenueYLabels"><span>Rp 0</span><span>Rp 0</span><span>Rp 0</span><span>Rp 0</span><span>Rp 0</span><span>Rp 0</span><span>Rp 0</span></div>
        <svg class="revenue-chart" viewBox="0 0 900 250" preserveAspectRatio="none" aria-label="Grafik pendapatan bulanan">
          <defs>
            <linearGradient id="area" x1="0" y1="0" x2="0" y2="1">
              <stop offset="0" stop-color="#A8874A" stop-opacity=".52" />
              <stop offset="1" stop-color="#A8874A" stop-opacity=".08" />
            </linearGradient>
          </defs>
          <g class="grid-lines">
            <path d="M0 12H900M0 48.7H900M0 85.3H900M0 122H900M0 158.7H900M0 195.3H900M0 232H900" />
            <path d="M10 0V240M90 0V240M170 0V240M250 0V240M330 0V240M410 0V240M490 0V240M570 0V240M650 0V240M730 0V240M810 0V240M890 0V240" />
          </g>
          <path class="area" id="revenueArea" d="M10 232 L890 232 L890 232 L10 232 Z" />
          <polyline class="line" id="revenueLine" points="10,232 890,232" />
          <g class="dots" id="revenueDots"></g>
        </svg>
        <div class="chart-tooltip" id="revenueTooltip" role="tooltip" hidden><strong></strong><span></span></div>
        <div class="x-labels"><span>Jan</span><span>Feb</span><span>Mar</span><span>Apr</span><span>Mei</span><span>Jun</span><span>Jul</span><span>Agu</span><span>Sep</span><span>Okt</span><span>Nov</span><span>Des</span></div>
      </div>
    </article>

    <article class="dash-card status-card">
      <header>
        <h2><i class="bi bi-pie-chart"></i> Status Pembayaran</h2>
      </header>
      <div class="status-content">
        <div class="donut" id="statusDonut">
          <div><strong id="statusPercent">0%</strong><span>Lunas</span></div>
        </div>
        <div class="status-legend" id="statusLegend">
          <p class="dashboard-empty">Memuat status...</p>
        </div>
      </div>
    </article>
  </section>

  <section class="bottom-grid">
    <article class="dash-card today-card">
      <header>
        <h2><i class="bi bi-calendar2-check"></i> Servis Hari Ini</h2><b id="todaySummary">0 dari 0 selesai</b>
      </header>
      <div class="today-progress"><span id="todayProgressBar" style="width:0%"></span><b id="todayPercent">0%</b></div>
      <div class="today-stats" id="todayStats">
        <div class="green"><i class="bi bi-check-circle"></i><span>Selesai</span><strong>0</strong></div>
        <div class="orange"><i class="bi bi-clock"></i><span>Dalam Pengerjaan</span><strong>0</strong></div>
      </div>
    </article>
    <article class="dash-card payment-card">
      <header>
        <h2><i class="bi bi-wallet2"></i> Metode Pembayaran</h2>
      </header>
      <div class="payment-bars" id="paymentBars">
        <p class="dashboard-empty">Memuat pembayaran...</p>
      </div>
    </article>
    <article class="dash-card stock-card">
      <header>
        <h2><i class="bi bi-exclamation-triangle"></i> Stok Sparepart Menipis</h2>
      </header>
      <div class="stock-list" id="lowStockList">
        <p class="dashboard-empty">Memuat stok...</p>
      </div>
    </article>
  </section>
</main>
<?php
require __DIR__ . '/includes/footer.php';
