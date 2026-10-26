<?php
/**
 * Section grid menu khusus tampilan mobile (pengganti sidebar).
 * Dipakai di header.php (halaman selain dashboard) dan di home/index.php
 * agar posisinya bisa diletakkan setelah kartu & box pendapatan.
 */

if (!isset($current_dir)) {
    $script_path = $_SERVER['SCRIPT_NAME'] ?? '';
    $current_dir = basename(dirname($script_path));
}

$mobile_menu_items = require __DIR__ . '/mobile_menu_items.php';
?>
<div class="mobile-menu-section d-md-none" id="mobileMenuSection">
    <div class="mobile-menu-head">
        <i class="fa fa-th-large"></i>
        <span>Menu</span>
    </div>
    <div class="mobile-menu-grid">
        <?php foreach ($mobile_menu_items as $mi): ?>
        <a class="mobile-menu-tile<?= $current_dir === $mi['dir'] ? ' active' : '' ?>" href="<?= $mi['url'] ?>">
            <i data-feather="<?= $mi['icon'] ?>" class="mobile-menu-icon"></i>
            <span><?= htmlspecialchars($mi['label']) ?></span>
        </a>
        <?php endforeach; ?>
        <a class="mobile-menu-tile" href="javascript:void(0)" onclick="confirmLogout(); return false;">
            <i data-feather="log-out" class="mobile-menu-icon"></i>
            <span>Log Out</span>
        </a>
    </div>
</div>
