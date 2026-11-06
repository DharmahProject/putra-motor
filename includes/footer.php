<?php
$scripts = $scripts ?? ['sidebar.js'];
$beforeScripts = $beforeScripts ?? '';
?>
    </div>
  </div>
  <div class="sidebar-overlay" id="sidebarOverlay"></div>
<?= $beforeScripts ?>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="<?= htmlspecialchars(asset_href('topbar.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
<?php foreach ($scripts as $src): ?>
  <script src="<?= htmlspecialchars(asset_href($src), ENT_QUOTES, 'UTF-8') ?>"></script>
<?php endforeach; ?>
</body>
</html>
