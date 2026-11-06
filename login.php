<?php
require_once __DIR__ . '/includes/auth.php';
if (auth_refresh_user()) {
    header('Location: ' . (auth_first_allowed_page(auth_permissions()) ?? 'dashboard.php'));
    exit;
}

$cssVersion = is_file(__DIR__ . '/auth.css') ? filemtime(__DIR__ . '/auth.css') : time();
$jsVersion = is_file(__DIR__ . '/auth.js') ? filemtime(__DIR__ . '/auth.js') : time();
$logoVersion = is_file(__DIR__ . '/logo.png') ? filemtime(__DIR__ . '/logo.png') : time();
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="Masuk ke sistem bengkel Putra Motor">
  <title>Login | Putra Motor</title>
  <link rel="preconnect" href="https://cdn.jsdelivr.net">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link rel="stylesheet" href="auth.css?v=<?= $cssVersion ?>">
</head>
<body>
  <main class="auth-shell">
    <section class="auth-showcase" aria-label="Putra Motor">
      <div class="showcase-orbit orbit-one" aria-hidden="true"></div>
      <div class="showcase-orbit orbit-two" aria-hidden="true"></div>
      <div class="showcase-grid" aria-hidden="true"></div>

      <a class="auth-logo" href="login.php" aria-label="Putra Motor">
        <img src="logo.png?v=<?= $logoVersion ?>" alt="Logo Putra Motor">
      </a>

      <div class="showcase-copy">
        <span class="showcase-kicker"><i class="bi bi-stars"></i> Sistem Operasional Bengkel</span>
        <h1>Kelola bengkel dalam satu tempat.</h1>
        <p>Transaksi, sparepart, jasa servis, dan laporan operasional tersusun rapi agar pekerjaan tim lebih cepat.</p>
        <div class="showcase-points">
          <span><i class="bi bi-check2"></i> Data terpusat</span>
          <span><i class="bi bi-check2"></i> Akses sesuai peran</span>
          <span><i class="bi bi-check2"></i> Mudah digunakan</span>
        </div>
      </div>

      <div class="showcase-footer"><span>Putra Motor</span><span>Bengkel Mobil</span></div>
    </section>

    <section class="auth-panel">
      <div class="mobile-auth-brand"><img src="logo.png?v=<?= $logoVersion ?>" alt="Logo Putra Motor"></div>
      <div class="auth-card">
        <div class="auth-form-view active" id="loginView">
          <div class="auth-heading">
            <span class="auth-eyebrow">Selamat datang kembali</span>
            <h2>Masuk ke akun Anda</h2>
            <p>Gunakan email dan password yang telah terdaftar.</p>
          </div>

          <form id="loginForm" novalidate>
            <label class="auth-field" for="loginEmail">
              <span>Email</span>
              <div class="auth-input"><i class="bi bi-envelope"></i><input id="loginEmail" name="email" type="email" autocomplete="username" placeholder="nama@putramotor.id" required></div>
              <small>Masukkan alamat email yang valid.</small>
            </label>

            <label class="auth-field" for="loginPassword">
              <span>Password</span>
              <div class="auth-input"><i class="bi bi-lock"></i><input id="loginPassword" name="password" type="password" autocomplete="current-password" placeholder="Masukkan password" minlength="6" required><button class="password-toggle" type="button" data-password-toggle="loginPassword" aria-label="Tampilkan password"><i class="bi bi-eye"></i></button></div>
              <small>Password minimal 6 karakter.</small>
            </label>

            <label class="auth-field" for="loginCaptcha">
              <span>Kode captcha</span>
              <div class="captcha-row">
                <img id="captchaImage" src="auth_captcha.php" alt="Kode captcha" width="168" height="52">
                <button class="captcha-refresh" id="refreshCaptcha" type="button" aria-label="Muat ulang captcha"><i class="bi bi-arrow-clockwise"></i></button>
                <div class="auth-input"><i class="bi bi-shield-lock"></i><input id="loginCaptcha" name="captcha" type="text" autocomplete="off" maxlength="8" placeholder="Ketik kode" required></div>
              </div>
              <small>Kode captcha belum sesuai.</small>
            </label>

            <div class="auth-options">
              <label class="remember-check"><input type="checkbox" name="remember"><span>Ingat saya</span></label>
              <button class="text-button" type="button" data-auth-view="reset">Lupa password?</button>
            </div>

            <button class="auth-submit" type="submit"><span>Masuk</span><i class="bi bi-arrow-right"></i></button>
          </form>

          <p class="auth-help"><i class="bi bi-shield-check"></i> Akses dilindungi dan hanya untuk pengguna terdaftar.</p>
        </div>

        <div class="auth-form-view" id="resetView" hidden>
          <button class="back-to-login" type="button" data-auth-view="login"><i class="bi bi-arrow-left"></i> Kembali ke login</button>
          <div class="reset-icon"><i class="bi bi-key"></i></div>
          <div class="auth-heading">
            <span class="auth-eyebrow">Pemulihan akun</span>
            <h2>Reset password</h2>
            <p>Masukkan nomor WhatsApp yang terdaftar. Tautan untuk membuat password baru akan dikirimkan melalui WhatsApp.</p>
          </div>

          <form id="resetForm" novalidate>
            <label class="auth-field" for="resetPhone">
              <span>Nomor WhatsApp terdaftar</span>
              <div class="auth-input"><i class="bi bi-whatsapp"></i><input id="resetPhone" name="phone" type="tel" autocomplete="tel" inputmode="tel" placeholder="Contoh: 0812 3456 7890" minlength="9" maxlength="20" pattern="[0-9+() .-]{9,20}" required></div>
              <small>Gunakan nomor WhatsApp yang tersimpan pada data karyawan.</small>
            </label>
            <button class="auth-submit" type="submit"><span>Kirim tautan reset</span><i class="bi bi-send"></i></button>
          </form>

          <div class="reset-note"><i class="bi bi-info-circle"></i><span>Tautan berlaku selama 30 menit. Jika pesan belum diterima, periksa kembali nomor atau hubungi administrator.</span></div>
        </div>
      </div>
      <footer class="auth-copyright">&copy; <?= date('Y') ?> Putra Motor. Sistem POS Bengkel.</footer>
    </section>
  </main>

  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="auth.js?v=<?= $jsVersion ?>"></script>
</body>
</html>
