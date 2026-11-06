<?php
$pageTitle = 'Daftar Transaksi Servis | Putra Motor';
$pageDescription = 'Daftar transaksi servis Putra Motor';
$pageKey = 'transactions';
$extraCss = ['transaksi.css'];
$mainClass = 'transaction-main';
$scripts = [
    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js',
    'sidebar.js',
    'transaksi.js',
];
$beforeScripts = <<<'HTML'
  <div class="toast-container position-fixed top-0 end-0 p-3"><div id="listToast" class="toast"><div class="toast-body"><i class="bi bi-check-circle-fill"></i><span>Berhasil</span></div></div></div>
HTML;

require __DIR__ . '/includes/header.php';
?>
      <main class="transaction-page">
        <section class="list-heading">
          <div><h1>Daftar Transaksi Servis</h1><p>Kelola seluruh transaksi dan pekerjaan bengkel dalam satu tempat</p></div>
          <div class="heading-actions">
            <div class="date-range">
              <button class="btn date-range-btn" id="dateRangeButton" type="button" aria-expanded="false" aria-controls="dateRangePanel"><i class="bi bi-calendar3"></i><span id="dateRangeLabel">Pilih tanggal</span><i class="bi bi-chevron-down"></i></button>
              <div class="date-range-panel" id="dateRangePanel" hidden>
                <label>Dari<input id="dateFrom" type="date"></label>
                <label>Sampai<input id="dateTo" type="date"></label>
                <button id="applyDateRange" type="button">Terapkan</button>
              </div>
            </div>
            <button class="btn export-btn" id="exportButton" type="button"><i class="bi bi-download"></i> Export</button>
            <a class="btn btn-primary add-transaction-btn" href="transaksi_detail.php"><i class="bi bi-plus-lg"></i> Tambah Transaksi</a>
          </div>
        </section>

        <section class="stats-grid" aria-label="Ringkasan transaksi">
          <article class="stat-card stat-blue"><span class="stat-icon"><i class="bi bi-clipboard2-check"></i></span><div><small>Total Transaksi</small><strong id="statTotal">0</strong></div></article>
          <article class="stat-card stat-orange"><span class="stat-icon"><i class="bi bi-clock"></i></span><div><small>Dalam Pengerjaan</small><strong id="statUnpaid">0</strong></div></article>
          <article class="stat-card stat-green"><span class="stat-icon"><i class="bi bi-check-circle"></i></span><div><small>Selesai</small><strong id="statDone">0</strong></div></article>
        </section>

        <section class="transactions-card">
          <div class="filter-bar">
            <label class="list-search"><i class="bi bi-search"></i><input id="transactionSearch" type="search" placeholder="Cari nomor transaksi, pelanggan, atau plat nomor..."></label>
            <select id="statusFilter" class="form-select"><option value="">Semua Status</option><option>Dalam Pengerjaan</option><option>Selesai</option></select>
            <select id="paymentStatusFilter" class="form-select"><option value="">Status Pembayaran</option><option>Belum Bayar</option><option>Lunas</option></select>
            <select id="paymentFilter" class="form-select"><option value="">Metode Pembayaran</option><option>Tunai</option><option>Transfer</option><option>QRIS</option></select>
            <button class="filter-button" id="resetFilters" type="button" aria-label="Reset filter" title="Reset filter"><i class="bi bi-arrow-counterclockwise"></i></button>
          </div>

          <div class="transaction-table-wrap">
            <table class="transaction-table">
              <thead><tr><th>No. Transaksi <button class="sort-btn" data-sort="id"><i class="bi bi-arrow-down-up"></i></button></th><th>Tanggal <button class="sort-btn" data-sort="date"><i class="bi bi-arrow-down-up"></i></button></th><th>Pelanggan & Kendaraan</th><th>Jenis Servis</th><th>Total <button class="sort-btn" data-sort="total"><i class="bi bi-arrow-down-up"></i></button></th><th>Status</th><th>Aksi</th></tr></thead>
              <tbody id="transactionRows"></tbody>
            </table>
          </div>

          <footer class="table-footer">
            <span id="tableResultText">Memuat transaksi...</span>
            <nav class="pagination-list" id="transactionPagination" aria-label="Paginasi"></nav>
          </footer>
        </section>
      </main>
<?php
require __DIR__ . '/includes/footer.php';
