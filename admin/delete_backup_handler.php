<?php
/**
 * Delete Backup Handler - Endpoint terpisah untuk handle delete backup
 * Menggunakan AJAX untuk menghindari masalah redirect/header
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

// Set header untuk JSON response
header('Content-Type: application/json');

// Hanya terima POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Cek apakah request untuk delete
if (!isset($_POST['delete']) || empty($_POST['delete'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$filename = basename($_POST['delete']);

// Validasi filename untuk keamanan
if (preg_match('/[^a-zA-Z0-9._-]/', $filename)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid filename']);
    exit;
}

// Buat instance BackupRestore
$backup_restore = new BackupRestore($koneksi);

// Panggil deleteBackup()
$result = $backup_restore->deleteBackup($filename);

if ($result['success']) {
    // Log aktivitas - PASTIKAN tidak ada output
    ob_start();
    require_once '../libs/log_activity.php';
    @log_activity('delete', 'backup', 'Menghapus backup: ' . $filename);
    ob_end_clean();

    // Bersihkan output buffer sebelum return JSON
    while (ob_get_level()) {
        ob_end_clean();
    }

    // Return success dengan JSON
    echo json_encode([
        'success' => true,
        'message' => 'Backup berhasil dihapus'
    ]);
    exit;
} else {
    // Bersihkan output buffer sebelum return JSON
    while (ob_get_level()) {
        ob_end_clean();
    }

    // Return error dengan JSON
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => isset($result['message']) ? $result['message'] : 'Gagal menghapus backup'
    ]);
    exit;
}





