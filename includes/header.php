<?php
$pageTitle = $pageTitle ?? 'Putra Motor';
$pageDescription = $pageDescription ?? 'Sistem POS Bengkel Putra Motor';
$pageKey = $pageKey ?? '';
$bodyAttrs = $bodyAttrs ?? '';
$extraCss = $extraCss ?? [];
$mainClass = $mainClass ?? '';

require_once __DIR__ . '/auth.php';
require_auth_for_page($pageKey);
$authUser = auth_user();
$authPermissions = auth_permissions();
$authInitials = auth_initials((string) ($authUser['name'] ?? 'User'));
$authHome = auth_first_allowed_page($authPermissions) ?? 'login.php';

if (!function_exists('asset_href')) {
  function asset_href(string $href): string {
    if (preg_match('#^(https?:)?//#', $href)) {
      return $href;
    }
    $file = dirname(__DIR__) . '/' . ltrim($href, '/');
    $version = is_file($file) ? filemtime($file) : time();
    $separator = str_contains($href, '?') ? '&' : '?';
    return $href . $separator . 'v=' . $version;
  }
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="<?= htmlspecialchars($pageDescription, ENT_QUOTES, 'UTF-8') ?>">
  <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
  <link rel="preconnect" href="https://cdn.jsdelivr.net">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link rel="stylesheet" href="<?= htmlspecialchars(asset_href('styles.css'), ENT_QUOTES, 'UTF-8') ?>">
<?php foreach ($extraCss as $href): ?>
  <link rel="stylesheet" href="<?= htmlspecialchars(asset_href($href), ENT_QUOTES, 'UTF-8') ?>">
<?php endforeach; ?>
</head>
<body data-page="<?= htmlspecialchars($pageKey, ENT_QUOTES, 'UTF-8') ?>" data-permissions="<?= htmlspecialchars(json_encode($authPermissions), ENT_QUOTES, 'UTF-8') ?>" data-home-page="<?= htmlspecialchars($authHome, ENT_QUOTES, 'UTF-8') ?>"<?= $bodyAttrs !== '' ? ' ' . $bodyAttrs : '' ?>>
  <div class="app-shell">
    <aside class="sidebar" id="appSidebar"></aside>
    <div class="app-main<?= $mainClass !== '' ? ' ' . htmlspecialchars($mainClass, ENT_QUOTES, 'UTF-8') : '' ?>">
      <header class="app-topbar">
        <a class="app-topbar-brand" href="<?= htmlspecialchars($authHome, ENT_QUOTES, 'UTF-8') ?>" aria-label="Putra Motor">
          <img src="<?= htmlspecialchars(asset_href('logo.png'), ENT_QUOTES, 'UTF-8') ?>" alt="Logo Putra Motor">
          <span><strong>Putra Motor</strong><small>Sistem POS Bengkel</small></span>
        </a>
        <div class="topbar-account" id="topbarAccount">
          <button class="topbar-account-button" id="topbarAccountButton" type="button" aria-expanded="false" aria-controls="topbarAccountMenu">
            <b><?= htmlspecialchars($authInitials, ENT_QUOTES, 'UTF-8') ?></b><span><strong><?= htmlspecialchars((string) $authUser['name'], ENT_QUOTES, 'UTF-8') ?></strong><small><?= htmlspecialchars((string) $authUser['role'], ENT_QUOTES, 'UTF-8') ?></small></span><i class="bi bi-chevron-down"></i>
          </button>
          <div class="topbar-account-menu" id="topbarAccountMenu" hidden>
            <div class="topbar-user"><b><?= htmlspecialchars($authInitials, ENT_QUOTES, 'UTF-8') ?></b><div><strong><?= htmlspecialchars((string) $authUser['name'], ENT_QUOTES, 'UTF-8') ?></strong><span><?= htmlspecialchars((string) $authUser['email'], ENT_QUOTES, 'UTF-8') ?></span></div></div>
            <div class="topbar-role"><i class="bi bi-shield-check"></i><span>Login sebagai</span><strong><?= htmlspecialchars((string) $authUser['role'], ENT_QUOTES, 'UTF-8') ?></strong></div>
<?php if (in_array('Pengaturan', $authPermissions, true)): ?>
            <a href="settings.php"><i class="bi bi-gear"></i><span>Pengaturan akun</span></a>
<?php endif; ?>
            <button id="topbarLogoutButton" type="button"><i class="bi bi-box-arrow-right"></i><span>Logout</span></button>
          </div>
        </div>
      </header>
