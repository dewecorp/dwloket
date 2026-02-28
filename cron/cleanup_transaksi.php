<?php
/**
 * Cron Job Handler untuk Cleanup Transaksi Lama
 *
 * Cara penggunaan:
 * 1. Tambahkan ke crontab: 0 2 * * * /usr/bin/php /path/to/cron/cleanup_transaksi.php
 *    (Jalankan setiap hari jam 2 pagi)
 *
 * 2. Atau via web browser: http://yourdomain.com/cron/cleanup_transaksi.php
 */

// Set time limit untuk proses yang lama
set_time_limit(300); // 5 menit

// Include config
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../libs/transaksi_cleanup.php';

// Jalankan cleanup
$result = auto_cleanup_old_transactions($koneksi);

// Output hasil (untuk cron log)
if (php_sapi_name() === 'cli') {
    // Running from command line (cron)
    echo date('Y-m-d H:i:s') . " - Cleanup Transaksi Lama\n";
    echo "Status: " . ($result['success'] ? 'SUCCESS' : 'FAILED') . "\n";
    echo "Message: " . $result['message'] . "\n";
    if ($result['deleted_count'] > 0) {
        echo "Deleted: " . $result['deleted_count'] . " transaksi\n";
    }
    echo "Total found: " . $result['total_count'] . " transaksi\n";
    echo "---\n";
} else {
    // Running from web browser
    header('Content-Type: application/json');
    echo json_encode($result, JSON_PRETTY_PRINT);
}

