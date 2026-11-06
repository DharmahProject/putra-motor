<?php
$pageTitle = 'Master Jasa | Putra Motor';
$pageDescription = 'Master jasa servis Putra Motor';
$pageKey = 'services';
$bodyAttrs = 'data-master-type="services"';
$extraCss = ['master.css'];
$scripts = [
    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js',
    'sidebar.js',
    'master.js',
];
$beforeScripts = <<<'HTML'
  <div class="modal fade" id="masterModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><form class="modal-content" id="masterForm"><div class="modal-header"><h2 class="modal-title fs-5">Tambah Jasa</h2><button class="btn-close" type="button" data-bs-dismiss="modal"></button></div><div class="modal-body" id="masterFields"></div><div class="modal-footer"><button class="btn btn-light" type="button" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary" type="submit"><i class="bi bi-floppy"></i> Simpan</button></div></form></div></div>
  <div class="toast-container position-fixed top-0 end-0 p-3"><div class="toast" id="masterToast"><div class="toast-body"><i class="bi bi-check-circle-fill"></i><span>Data berhasil disimpan</span></div></div></div>
HTML;

require __DIR__ . '/includes/header.php';
?>
      <main class="master-page">
        <section class="master-heading"><div><h1>Master Jasa Servis</h1><p>Kelola nama dan harga jasa bengkel</p></div><button class="btn btn-primary master-add-button" data-bs-toggle="modal" data-bs-target="#masterModal" data-mode="add"><i class="bi bi-plus-lg"></i> Tambah Jasa</button></section>
        <section class="master-card">
          <div class="master-filters simple"><label class="master-search"><i class="bi bi-search"></i><input id="masterSearch" type="search" placeholder="Cari nama jasa..."></label><button id="resetMasterFilters" class="master-reset" aria-label="Reset pencarian" title="Reset pencarian"><i class="bi bi-arrow-counterclockwise"></i></button></div>
          <div class="master-table-wrap"><table class="master-table simple-table service-simple"><thead><tr><th>No.</th><th>Nama Jasa Servis</th><th>Harga</th><th>Aksi</th></tr></thead><tbody id="masterRows"></tbody></table></div>
          <footer class="master-footer"><span id="masterResultText"></span><div class="mini-pagination" id="masterPagination" aria-label="Paginasi"></div></footer>
        </section>
      </main>
<?php
require __DIR__ . '/includes/footer.php';
