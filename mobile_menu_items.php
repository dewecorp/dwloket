<?php
/**
 * Daftar menu utama.
 * Dipakai oleh grid menu mobile (mobile_menu.php) dan bottom navigation (footer.php).
 */

if (!isset($current_dir)) {
    $current_dir = basename(dirname($_SERVER['SCRIPT_NAME'] ?? ''));
}

$mobile_items = [
    ['dir' => 'home',       'url' => base_url('home'),                       'icon' => 'home',          'label' => 'Dashboard',        'short' => 'Home'],
    ['dir' => 'pelanggan',  'url' => base_url('pelanggan/pelanggan'),        'icon' => 'users',         'label' => 'Pelanggan',        'short' => 'Pelanggan'],
    ['dir' => 'jenisbayar', 'url' => base_url('jenisbayar/jenis_bayar.php'), 'icon' => 'dollar-sign',   'label' => 'Jenis Pembayaran', 'short' => 'Bayar'],
    ['dir' => 'saldo',      'url' => base_url('saldo/saldo.php'),            'icon' => 'credit-card',   'label' => 'Saldo',            'short' => 'Saldo'],
    ['dir' => 'transaksi',  'url' => base_url('transaksi/transaksi.php'),    'icon' => 'shopping-cart', 'label' => 'Transaksi',        'short' => 'Transaksi'],
];

if (isset($_SESSION['level']) && $_SESSION['level'] == 'admin') {
    $mobile_items[] = ['dir' => 'admin', 'url' => base_url('admin/backup.php'), 'icon' => 'database', 'label' => 'Backup & Restore', 'short' => 'Backup'];
}

$mobile_items[] = ['dir' => 'user', 'url' => base_url('user/user.php'), 'icon' => 'user', 'label' => 'User', 'short' => 'User'];

return $mobile_items;
