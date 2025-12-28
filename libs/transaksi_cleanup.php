<?php
/**
 * Helper functions untuk cleanup transaksi lama
 */

/**
 * Hapus transaksi yang lebih dari 1 tahun secara otomatis
 * Fungsi ini akan menghapus transaksi yang tanggalnya lebih dari 1 tahun dari sekarang
 *
 * @param mysqli $koneksi Koneksi database
 * @param bool $dry_run Jika true, hanya menghitung tanpa menghapus (default: false)
 * @return array ['success' => bool, 'message' => string, 'deleted_count' => int, 'total_count' => int]
 */
function cleanup_old_transactions($koneksi, $dry_run = false) {
    if (!$koneksi) {
        return [
            'success' => false,
            'message' => 'Koneksi database tidak valid',
            'deleted_count' => 0,
            'total_count' => 0
        ];
    }

    try {
        // Hitung jumlah transaksi yang akan dihapus (lebih dari 1 tahun)
        $count_query = "SELECT COUNT(*) as total FROM transaksi
                       WHERE tgl < DATE_SUB(NOW(), INTERVAL 1 YEAR)";
        $count_result = $koneksi->query($count_query);
        $total_count = 0;

        if ($count_result && $count_result->num_rows > 0) {
            $count_row = $count_result->fetch_assoc();
            $total_count = (int)$count_row['total'];
        }

        if ($total_count == 0) {
            return [
                'success' => true,
                'message' => 'Tidak ada transaksi yang perlu dihapus (semua transaksi kurang dari 1 tahun)',
                'deleted_count' => 0,
                'total_count' => 0
            ];
        }

        // Jika dry_run, hanya return count tanpa menghapus
        if ($dry_run) {
            return [
                'success' => true,
                'message' => "Ditemukan $total_count transaksi yang akan dihapus (lebih dari 1 tahun)",
                'deleted_count' => 0,
                'total_count' => $total_count
            ];
        }

        // Ambil data transaksi yang akan dihapus untuk log (opsional)
        $select_query = "SELECT id_transaksi, tgl, nama, harga, status FROM transaksi
                        WHERE tgl < DATE_SUB(NOW(), INTERVAL 1 YEAR)
                        LIMIT 100"; // Ambil sample untuk log
        $select_result = $koneksi->query($select_query);
        $sample_transactions = [];
        if ($select_result && $select_result->num_rows > 0) {
            while ($row = $select_result->fetch_assoc()) {
                $sample_transactions[] = $row;
            }
        }

        // Hapus transaksi yang lebih dari 1 tahun
        $delete_query = "DELETE FROM transaksi
                        WHERE tgl < DATE_SUB(NOW(), INTERVAL 1 YEAR)";
        $delete_result = $koneksi->query($delete_query);

        if ($delete_result) {
            $deleted_count = $koneksi->affected_rows;

            // Log aktivitas
            require_once __DIR__ . '/log_activity.php';
            $log_message = "Cleanup transaksi otomatis: Menghapus $deleted_count transaksi yang lebih dari 1 tahun";
            @log_activity('delete', 'transaksi', $log_message);

            $message = "Berhasil menghapus $deleted_count transaksi yang lebih dari 1 tahun";
            if ($deleted_count > 0 && count($sample_transactions) > 0) {
                $message .= ". Contoh transaksi yang dihapus: " .
                           implode(', ', array_map(function($t) {
                               return "ID {$t['id_transaksi']} ({$t['tgl']})";
                           }, array_slice($sample_transactions, 0, 5)));
            }

            return [
                'success' => true,
                'message' => $message,
                'deleted_count' => $deleted_count,
                'total_count' => $total_count
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Gagal menghapus transaksi: ' . $koneksi->error,
                'deleted_count' => 0,
                'total_count' => $total_count
            ];
        }

    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Error: ' . $e->getMessage(),
            'deleted_count' => 0,
            'total_count' => 0
        ];
    }
}

/**
 * Jalankan cleanup transaksi lama secara otomatis
 * Fungsi ini bisa dipanggil dari cron job atau saat akses halaman tertentu
 *
 * @param mysqli $koneksi Koneksi database
 * @return array Hasil cleanup
 */
function auto_cleanup_old_transactions($koneksi) {
    // Cek apakah sudah pernah dijalankan hari ini (untuk menghindari multiple execution)
    $last_cleanup_file = __DIR__ . '/../logs/last_transaksi_cleanup.txt';
    $today = date('Y-m-d');

    if (file_exists($last_cleanup_file)) {
        $last_cleanup_date = trim(file_get_contents($last_cleanup_file));
        if ($last_cleanup_date === $today) {
            // Sudah dijalankan hari ini, skip
            return [
                'success' => true,
                'message' => 'Cleanup sudah dijalankan hari ini',
                'deleted_count' => 0,
                'total_count' => 0,
                'skipped' => true
            ];
        }
    }

    // Jalankan cleanup
    $result = cleanup_old_transactions($koneksi, false);

    // Simpan tanggal cleanup terakhir
    if ($result['success'] && !isset($result['skipped'])) {
        $logs_dir = dirname($last_cleanup_file);
        if (!is_dir($logs_dir)) {
            @mkdir($logs_dir, 0755, true);
        }
        @file_put_contents($last_cleanup_file, $today);
    }

    return $result;
}

/**
 * Dapatkan statistik transaksi lama
 *
 * @param mysqli $koneksi Koneksi database
 * @return array Statistik transaksi lama
 */
function get_old_transactions_stats($koneksi) {
    if (!$koneksi) {
        return [
            'total' => 0,
            'oldest_date' => null,
            'newest_old_date' => null
        ];
    }

    try {
        // Hitung total transaksi lebih dari 1 tahun
        $count_query = "SELECT COUNT(*) as total FROM transaksi
                       WHERE tgl < DATE_SUB(NOW(), INTERVAL 1 YEAR)";
        $count_result = $koneksi->query($count_query);
        $total = 0;
        if ($count_result && $count_result->num_rows > 0) {
            $row = $count_result->fetch_assoc();
            $total = (int)$row['total'];
        }

        // Ambil tanggal tertua dan terbaru dari transaksi lama
        $date_query = "SELECT MIN(tgl) as oldest_date, MAX(tgl) as newest_old_date
                      FROM transaksi
                      WHERE tgl < DATE_SUB(NOW(), INTERVAL 1 YEAR)";
        $date_result = $koneksi->query($date_query);
        $oldest_date = null;
        $newest_old_date = null;

        if ($date_result && $date_result->num_rows > 0) {
            $row = $date_result->fetch_assoc();
            $oldest_date = $row['oldest_date'];
            $newest_old_date = $row['newest_old_date'];
        }

        return [
            'total' => $total,
            'oldest_date' => $oldest_date,
            'newest_old_date' => $newest_old_date
        ];

    } catch (Exception $e) {
        return [
            'total' => 0,
            'oldest_date' => null,
            'newest_old_date' => null,
            'error' => $e->getMessage()
        ];
    }
}

