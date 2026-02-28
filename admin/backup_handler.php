<?php
/**
 * Backup Handler - Endpoint terpisah untuk handle backup
 * Sederhana dan bersih, sama persis seperti test script yang berhasil
 */

// Bersihkan semua output buffer SEBELUM apapun
while (ob_get_level()) {
    ob_end_clean();
}

// Pastikan tidak ada output sebelumnya
if (headers_sent($file, $line)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Headers already sent']);
    exit;
}

// Include config dan backup_restore - PASTIKAN tidak ada output
ob_start();
require_once '../config/config.php';
require_once '../libs/backup_restore.php';
ob_end_clean();

// Security Check
if (!isset($_SESSION['level']) || $_SESSION['level'] != 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

// Set header untuk JSON response
header('Content-Type: application/json');

// Hanya terima POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Cek apakah request untuk backup
if (!isset($_POST['backup'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

// Buat instance BackupRestore - SAMA PERSIS seperti test script
$backup_restore = new BackupRestore($koneksi);

// Panggil backup() - SAMA PERSIS seperti test script yang berhasil
$result = $backup_restore->backup();

// Handle result - SAMA PERSIS seperti test script
if ($result['success']) {
    // Verifikasi file dengan retry mechanism (lebih cepat)
    $max_retries = 5;
    $retry_delay = 0.5; // 500ms per retry
    $file_verified = false;

    for ($i = 0; $i < $max_retries; $i++) {
        clearstatcache(true, $result['filepath']);

        // Verifikasi file exists dan readable
        if (file_exists($result['filepath']) && is_readable($result['filepath']) && filesize($result['filepath']) > 0) {
            // Baca file untuk verifikasi konten
            $file_content = @file_get_contents($result['filepath']);
            if ($file_content !== false && strpos($file_content, '-- Database Backup') === 0) {
                $file_verified = true;
                break;
            }
        }

        // Tunggu sebentar sebelum retry (kecuali retry terakhir)
        if ($i < $max_retries - 1) {
            usleep($retry_delay * 1000000); // Convert to microseconds
        }
    }

    if ($file_verified) {
        // File valid - Log aktivitas - PASTIKAN tidak ada output
        ob_start();
        require_once '../libs/log_activity.php';
        @log_activity('backup', 'database', 'Membuat backup database: ' . $result['filename'] . ' (' . $result['size_formatted'] . ')');
        ob_end_clean();

        // Bersihkan output buffer sebelum return JSON
        while (ob_get_level()) {
            ob_end_clean();
        }

        // Return success dengan JSON
        echo json_encode([
            'success' => true,
            'message' => 'Backup berhasil dibuat',
            'filename' => $result['filename'],
            'size_formatted' => $result['size_formatted']
        ]);
        exit;
    }
}

// Jika sampai sini berarti gagal
$error_msg = isset($result['message']) ? $result['message'] : 'Backup gagal. Silakan coba lagi.';

// Bersihkan output buffer sebelum return JSON
while (ob_get_level()) {
    ob_end_clean();
}

http_response_code(500);
echo json_encode([
    'success' => false,
    'message' => $error_msg
]);
exit;





