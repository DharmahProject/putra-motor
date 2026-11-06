<?php
$pageTitle = 'Pengaturan | Putra Motor';
$pageDescription = 'Pengaturan user, hak akses, dan service advisor Putra Motor';
$pageKey = 'settings';
$extraCss = [
    'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css',
    'settings.css',
];
$scripts = [
    'https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js',
    'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js',
    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js',
    'sidebar.js',
    'settings.js',
];
$beforeScripts = <<<'HTML'
  <div class="modal fade" id="settingsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <form class="modal-content" id="settingsForm">
        <div class="modal-header"><h2 class="modal-title fs-5">Tambah User</h2><button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Tutup"></button></div>
        <div class="modal-body"><div class="settings-form-grid" id="settingsFields"></div></div>
        <div class="modal-footer"><button class="btn btn-light" type="button" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary" type="submit"><i class="bi bi-floppy me-2"></i>Simpan</button></div>
      </form>
    </div>
  </div>
  <div class="toast-container position-fixed top-0 end-0 p-3"><div class="toast" id="settingsToast"><div class="toast-body"><i class="bi bi-check-circle-fill"></i><span>Perubahan berhasil disimpan</span></div></div></div>
HTML;

require __DIR__ . '/includes/header.php';
?>
      <main class="settings-page">
        <section class="settings-heading">
          <div><h1>Pengaturan</h1><p>Kelola pengguna, hak akses, dan data service advisor</p></div>
          <button class="btn btn-primary settings-add-button" id="settingsAddButton" type="button"><i class="bi bi-plus-lg"></i><span>Tambah User</span></button>
        </section>

        <section class="settings-overview" aria-label="Ringkasan pengaturan">
          <article><span class="settings-stat-icon"><i class="bi bi-people"></i></span><div><small>User Aktif</small><strong id="activeUserCount">0</strong></div></article>
          <article><span class="settings-stat-icon advisor"><i class="bi bi-person-badge"></i></span><div><small>Service Advisor</small><strong id="advisorCount">0</strong></div></article>
        </section>

        <section class="settings-card">
          <div class="settings-tabs" role="tablist" aria-label="Jenis pengaturan">
            <button class="settings-tab active" type="button" role="tab" aria-selected="true" data-settings-tab="users"><i class="bi bi-shield-lock"></i><span>User & Hak Akses</span></button>
            <button class="settings-tab" type="button" role="tab" aria-selected="false" data-settings-tab="employees"><i class="bi bi-person-vcard"></i><span>Karyawan</span></button>
            <button class="settings-tab" type="button" role="tab" aria-selected="false" data-settings-tab="profile"><i class="bi bi-shop"></i><span>Profil Bengkel</span></button>
          </div>

          <div class="settings-toolbar" id="settingsToolbar"><label class="settings-search"><i class="bi bi-search"></i><input id="settingsSearch" type="search" placeholder="Cari nama atau email user..."></label></div>

          <div class="settings-panel active" id="usersPanel" role="tabpanel">
            <div class="settings-table-wrap"><table class="settings-table"><thead><tr><th>User</th><th>Peran</th><th>Hak Akses</th><th>Status</th><th>Aksi</th></tr></thead><tbody id="userRows"></tbody></table></div>
            <div class="settings-mobile-list" id="userCards"></div>
          </div>

          <div class="settings-panel" id="employeesPanel" role="tabpanel" hidden>
            <div class="settings-table-wrap"><table class="settings-table"><thead><tr><th>Service Advisor</th><th>ID Karyawan</th><th>Kontak</th><th>Shift</th><th>Status</th><th>Aksi</th></tr></thead><tbody id="employeeRows"></tbody></table></div>
            <div class="settings-mobile-list" id="employeeCards"></div>
          </div>

          <div class="settings-panel profile-panel" id="profilePanel" role="tabpanel" hidden>
            <form id="profileForm" enctype="multipart/form-data">
              <div class="profile-layout">
                <section class="profile-fields">
                  <div class="profile-section-title"><i class="bi bi-geo-alt"></i><div><h2>Informasi Bengkel</h2><p>Alamat yang digunakan pada dokumen dan informasi bengkel.</p></div></div>
                  <label class="form-label" for="profileAddress">Alamat Bengkel</label>
                  <textarea class="form-control" id="profileAddress" name="address" rows="4" placeholder="Masukkan alamat lengkap bengkel"></textarea>

                  <div class="profile-section-title bank-title"><i class="bi bi-bank"></i><div><h2>Informasi Rekening</h2><p>Tambahkan maksimal dua rekening pembayaran.</p></div></div>
                  <div class="bank-grid">
                    <fieldset><legend>Rekening 1</legend><label class="form-label">Nama Bank</label><input class="form-control" name="bank_1_name" placeholder="Contoh: BCA"><label class="form-label mt-3">Nama Rekening</label><input class="form-control" name="bank_1_account_name" placeholder="Nama pemilik rekening"><label class="form-label mt-3">Nomor Rekening</label><input class="form-control" name="bank_1_account" inputmode="numeric" placeholder="Masukkan nomor rekening"></fieldset>
                    <fieldset><legend>Rekening 2</legend><label class="form-label">Nama Bank</label><input class="form-control" name="bank_2_name" placeholder="Contoh: Mandiri"><label class="form-label mt-3">Nama Rekening</label><input class="form-control" name="bank_2_account_name" placeholder="Nama pemilik rekening"><label class="form-label mt-3">Nomor Rekening</label><input class="form-control" name="bank_2_account" inputmode="numeric" placeholder="Masukkan nomor rekening"></fieldset>
                  </div>
                </section>

                <aside class="profile-logo-card">
                  <h2>Logo Bengkel</h2><p>PNG, JPG, atau WebP. Maksimal 2 MB.</p>
                  <div class="profile-logo-preview" id="profileLogoPreview"><i class="bi bi-image"></i><span>Belum ada logo</span></div>
                  <label class="btn btn-outline-primary profile-upload-button" for="profileLogo"><i class="bi bi-upload"></i>Pilih Logo</label>
                  <input class="visually-hidden" id="profileLogo" name="logo" type="file" accept="image/png,image/jpeg,image/webp">
                </aside>
              </div>
              <div class="profile-save-bar"><button class="btn btn-primary" type="submit"><i class="bi bi-floppy"></i>Simpan Profil</button></div>
            </form>
          </div>
        </section>
      </main>
<?php require __DIR__ . '/includes/footer.php'; ?>
