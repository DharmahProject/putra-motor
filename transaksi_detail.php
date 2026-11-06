<?php
$pageTitle = 'Transaksi Servis Baru | Putra Motor';
$pageDescription = 'Form transaksi servis baru Putra Motor';
$pageKey = 'transactions';
$extraCss = ['https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css'];
$scripts = [
    'https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js',
    'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js',
    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js',
    'sidebar.js',
    'script.js',
];
$beforeScripts = <<<'HTML'
  <div class="toast-container position-fixed top-0 end-0 p-3"><div id="appToast" class="toast" role="status"><div class="toast-body"><i class="bi bi-check-circle-fill"></i><span>Berhasil</span></div></div></div>

  <div class="modal fade" id="profileEditModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered"><form class="modal-content" id="profileEditForm">
      <div class="modal-header"><h2 class="modal-title fs-5">Edit Data</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div>
      <div class="modal-body">
        <input type="hidden" id="profileEditType">
        <div class="mb-3"><label for="profilePrimary" class="form-label" id="profilePrimaryLabel">Nama</label><input id="profilePrimary" class="form-control" type="text" required></div>
        <div><label for="profileSecondary" class="form-label" id="profileSecondaryLabel">Nomor telepon</label><input id="profileSecondary" class="form-control" type="text" required></div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Simpan Perubahan</button></div>
    </form></div>
  </div>
HTML;

require __DIR__ . '/includes/header.php';
?>
      <main class="page-content">
        <section class="page-heading">
          <div class="heading-left">
            <a class="back-button" href="transaksi.php" aria-label="Kembali"><i class="bi bi-arrow-left"></i></a>
            <div>
              <h1 id="detailPageTitle">Transaksi Servis Baru</h1>
              <div class="transaction-meta">
                <span>No. Transaksi:</span>
                <strong id="transactionNumber">Otomatis</strong>
                <span>Tanggal:</span>
                <strong id="transactionDate">-</strong>
                <span class="status-badge" id="transactionStatus"><i class="bi bi-circle-fill"></i> Dalam Pengerjaan</span>
                <span class="status-badge" id="paymentStatusBadge"><i class="bi bi-credit-card"></i> Belum Bayar</span>
              </div>
            </div>
          </div>
        </section>

        <section class="customer-grid">
          <article class="info-card customer-card">
            <div class="info-icon"><i class="bi bi-person"></i></div>
            <div class="info-copy"><span>Pelanggan</span><strong id="customerNameDisplay">Budi Santoso</strong><small><i class="bi bi-telephone"></i> <span id="customerPhoneDisplay">0812 3456 7890</span></small></div>
            <button class="edit-button" aria-label="Edit pelanggan" data-bs-toggle="modal" data-bs-target="#profileEditModal" data-edit-type="customer"><i class="bi bi-pencil"></i></button>
          </article>
          <article class="info-card vehicle-card">
            <div class="info-icon"><i class="bi bi-car-front-fill"></i></div>
            <div class="info-copy"><span>Kendaraan</span><strong id="vehicleNameDisplay">Toyota Avanza Veloz</strong><small id="vehiclePlateDisplay">B 1234 SJK</small></div>
            <button class="edit-button" aria-label="Edit kendaraan" data-bs-toggle="modal" data-bs-target="#profileEditModal" data-edit-type="vehicle"><i class="bi bi-pencil"></i></button>
          </article>
          <article class="info-card field-card">
            <label for="advisor"><i class="bi bi-person-badge-fill"></i> Service Advisor</label>
            <select class="form-select" id="advisor">
              <option value="" selected>Pilih Service Advisor</option><option>Andi Pratama</option><option>Rizky Hidayat</option><option>Doni Saputra</option>
            </select>
          </article>
          <article class="info-card field-card">
            <label for="odometer"><i class="bi bi-speedometer2"></i> Odometer</label>
            <div class="input-group"><input type="text" class="form-control" id="odometer" value="68.520" inputmode="numeric" pattern="[0-9.]*" aria-label="Odometer"><span class="input-group-text">km</span></div>
          </article>
        </section>

        <div class="workspace-grid">
          <section class="transaction-panel">
            <ul class="nav transaction-tabs" id="transactionTabs" role="tablist">
              <li class="nav-item"><button class="nav-link active" data-target="sparepart-section" type="button"><i class="bi bi-box-seam"></i> Sparepart</button></li>
              <li class="nav-item"><button class="nav-link" data-target="service-section" type="button"><i class="bi bi-wrench-adjustable"></i> Jasa Servis</button></li>
              <li class="nav-item"><button class="nav-link" data-target="other-section" type="button"><i class="bi bi-stack"></i> Biaya Lainnya</button></li>
            </ul>

            <div class="panel-body">
              <div class="search-toolbar single-search">
                <div class="search-box"><i class="bi bi-search"></i><input id="partSearch" type="search" placeholder="Cari sparepart (nama/kode)..." aria-label="Cari sparepart"></div>
              </div>

              <article class="line-section" id="sparepart-section">
                <header class="section-header">
                  <h2><i class="bi bi-box-seam"></i> Sparepart</h2>
                  <div><span id="partCount">0 Item</span><b>|</b><strong>Subtotal: <span id="partSubtotal">Rp 0</span></strong></div>
                </header>
                <div class="table-responsive">
                  <table class="data-table" id="partsTable">
                    <colgroup><col class="col-no"><col class="col-part-name"><col class="col-stock"><col class="col-qty"><col class="col-unit"><col class="col-price"><col class="col-total"><col class="col-action"></colgroup>
                    <thead><tr><th>No.</th><th>Nama Sparepart</th><th>Stok</th><th>Qty</th><th>Satuan</th><th>Harga Satuan</th><th>Total</th><th>Aksi</th></tr></thead>
                    <tbody><tr class="empty-row"><td colspan="8"><i class="bi bi-inbox"></i><span>Tidak ada sparepart</span></td></tr></tbody>
                  </table>
                </div>
                <div class="section-actions">
                  <button class="btn btn-primary add-row-btn" type="button" data-add-master="sparepart"><i class="bi bi-database-add"></i> Tambah dari Master Sparepart</button>
                </div>
              </article>

              <article class="line-section" id="service-section">
                <header class="section-header">
                  <h2><i class="bi bi-wrench-adjustable"></i> Jasa Servis</h2>
                  <div><span id="serviceCount">0 Item</span><b>|</b><strong>Subtotal: <span id="serviceSubtotal">Rp 0</span></strong></div>
                </header>
                <div class="table-responsive">
                  <table class="data-table" id="serviceTable">
                    <colgroup><col class="col-no"><col class="col-service-name"><col class="col-qty"><col class="col-price"><col class="col-total"><col class="col-action"></colgroup>
                    <thead><tr><th>No.</th><th>Nama Jasa Servis</th><th>Qty</th><th>Harga Satuan</th><th>Total</th><th>Aksi</th></tr></thead>
                    <tbody><tr class="empty-row"><td colspan="6"><i class="bi bi-inbox"></i><span>Tidak ada jasa servis</span></td></tr></tbody>
                  </table>
                </div>
                <div class="section-actions">
                  <button class="btn btn-primary add-row-btn" type="button" data-add-master="service"><i class="bi bi-database-add"></i> Tambah dari Master Jasa</button>
                  <button class="btn btn-outline-primary add-row-btn" type="button" data-add-inline="service"><i class="bi bi-plus-lg"></i> Tambah Jasa Manual</button>
                </div>
              </article>

              <article class="line-section" id="other-section">
                <header class="section-header">
                  <h2><i class="bi bi-stack"></i> Biaya Lainnya</h2>
                  <div><span id="otherCount">0 Item</span><b>|</b><strong>Subtotal: <span id="otherSubtotal">Rp 0</span></strong></div>
                </header>
                <p class="info-note"><i class="bi bi-info-circle-fill"></i> Biaya lainnya adalah biaya/jasa manual (bebas) yang tidak memerlukan data master. Gunakan untuk layanan khusus seperti bubut, press, dll.</p>
                <div class="table-responsive">
                  <table class="data-table" id="otherTable">
                    <colgroup><col class="col-no"><col class="col-other-name"><col class="col-note"><col class="col-qty"><col class="col-price"><col class="col-total"><col class="col-action"></colgroup>
                    <thead><tr><th>No.</th><th>Nama Biaya / Jasa Manual</th><th>Keterangan</th><th>Qty</th><th>Harga Satuan</th><th>Total</th><th>Aksi</th></tr></thead>
                    <tbody><tr class="empty-row"><td colspan="7"><i class="bi bi-inbox"></i><span>Tidak ada biaya lain</span></td></tr></tbody>
                  </table>
                </div>
                <button class="btn btn-outline-primary add-row-btn" type="button" data-add-inline="other"><i class="bi bi-plus-lg"></i> Tambah Biaya Lainnya</button>
              </article>
            </div>
          </section>

          <aside class="summary-card">
            <h2><i class="bi bi-receipt"></i> Ringkasan Transaksi</h2>
            <div class="summary-lines">
              <div><span>Subtotal Sparepart (<span id="summaryPartCount">0</span> item)</span><strong id="summaryParts">Rp 0</strong></div>
              <div><span>Subtotal Jasa Servis (<span id="summaryServiceCount">0</span> item)</span><strong id="summaryService">Rp 0</strong></div>
              <div><span>Subtotal Biaya Lainnya (<span id="summaryOtherCount">0</span> item)</span><strong id="summaryOther">Rp 0</strong></div>
            </div>
            <div class="discount-row">
              <label for="discount">Diskon <i class="bi bi-info-circle"></i></label>
              <div class="input-group"><span class="input-group-text">Rp</span><input id="discount" class="form-control" type="text" value="0" inputmode="numeric" aria-label="Diskon"></div>
            </div>
            <div class="grand-total"><span>Total Pembayaran</span><strong id="grandTotal">Rp 0</strong></div>
            <hr>
            <button class="btn btn-outline-primary summary-action" type="button" data-save="save"><i class="bi bi-floppy"></i> Simpan Transaksi</button>
          </aside>
        </div>
      </main>
<?php
require __DIR__ . '/includes/footer.php';
