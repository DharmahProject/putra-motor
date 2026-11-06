<?php
$token = (string) ($_GET['token'] ?? '');
$email = (string) ($_GET['email'] ?? '');
$linkValid = preg_match('/^[a-f0-9]{64}$/', $token) && filter_var($email, FILTER_VALIDATE_EMAIL);
$cssVersion = is_file(__DIR__ . '/auth.css') ? filemtime(__DIR__ . '/auth.css') : time();
$jsVersion = is_file(__DIR__ . '/reset_password.js') ? filemtime(__DIR__ . '/reset_password.js') : time();
$logoVersion = is_file(__DIR__ . '/logo.png') ? filemtime(__DIR__ . '/logo.png') : time();
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="Buat password baru akun Putra Motor">
  <title>Buat Password Baru | Putra Motor</title>
  <link rel="preconnect" href="https://cdn.jsdelivr.net">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link rel="stylesheet" href="auth.css?v=<?= $cssVersion ?>">
</head>
<body>
  <main class="auth-shell">
    <section class="auth-showcase" aria-label="Putra Motor">
      <div class="showcase-orbit orbit-one" aria-hidden="true"></div><div class="showcase-orbit orbit-two" aria-hidden="true"></div><div class="showcase-grid" aria-hidden="true"></div>
      <a class="auth-logo" href="login.php" aria-label="Putra Motor"><img src="logo.png?v=<?= $logoVersion ?>" alt="Logo Putra Motor"></a>
      <div class="showcase-copy"><span class="showcase-kicker"><i class="bi bi-shield-lock"></i> Keamanan Akun</span><h1>Buat password baru.</h1><p>Gunakan password yang kuat dan berbeda dari password sebelumnya untuk menjaga keamanan akun Anda.</p></div>
      <div class="showcase-footer"><span>Putra Motor</span><span>Bengkel Mobil</span></div>
    </section>

    <section class="auth-panel">
      <div class="mobile-auth-brand"><img src="logo.png?v=<?= $logoVersion ?>" alt="Logo Putra Motor"></div>
      <div class="auth-card">
<?php if ($linkValid): ?>
        <div class="auth-form-view active">
          <a class="back-to-login" href="login.php"><i class="bi bi-arrow-left"></i> Kembali ke login</a>
          <div class="reset-icon"><i class="bi bi-shield-lock"></i></div>
          <div class="auth-heading"><span class="auth-eyebrow">Pemulihan akun</span><h2>Buat password baru</h2><p>Masukkan password baru dan ulangi untuk konfirmasi.</p></div>
          <form id="newPasswordForm" novalidate>
            <input type="hidden" name="token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="email" value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>">
            <label class="auth-field" for="newPassword"><span>Password baru</span><div class="auth-input"><i class="bi bi-lock"></i><input id="newPassword" name="password" type="password" autocomplete="new-password" placeholder="Minimal 8 karakter" minlength="8" required><button class="password-toggle" type="button" data-password-toggle="newPassword" aria-label="Tampilkan password"><i class="bi bi-eye"></i></button></div><small>Password minimal 8 karakter.</small></label>
            <label class="auth-field" for="passwordConfirmation"><span>Konfirmasi password</span><div class="auth-input"><i class="bi bi-lock-fill"></i><input id="passwordConfirmation" name="password_confirmation" type="password" autocomplete="new-password" placeholder="Ulangi password baru" minlength="8" required><button class="password-toggle" type="button" data-password-toggle="passwordConfirmation" aria-label="Tampilkan password"><i class="bi bi-eye"></i></button></div><small>Kedua password harus sama.</small></label>
            <button class="auth-submit" type="submit"><span>Simpan password baru</span><i class="bi bi-check-lg"></i></button>
          </form>
        </div>
<?php else: ?>
        <div class="auth-form-view active"><div class="reset-icon"><i class="bi bi-link-45deg"></i></div><div class="auth-heading"><span class="auth-eyebrow">Tautan tidak valid</span><h2>Link tidak dapat digunakan</h2><p>Tautan reset password tidak lengkap atau sudah rusak. Silakan minta tautan baru dari halaman login.</p></div><a class="auth-submit auth-submit-link" href="login.php#reset-password"><span>Minta tautan baru</span><i class="bi bi-arrow-right"></i></a></div>
<?php endif; ?>
      </div>
      <footer class="auth-copyright">&copy; <?= date('Y') ?> Putra Motor. Sistem POS Bengkel.</footer>
    </section>
  </main>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<?php if ($linkValid): ?><script src="reset_password.js?v=<?= $jsVersion ?>"></script><?php endif; ?>
</body>
</html>
