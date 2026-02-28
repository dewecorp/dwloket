<?php
/**
 * Backup & Restore Database
 * Sistem backup dan restore database yang modern, andal, dan stabil
 */

class BackupRestore {
    private $koneksi;
    private $backup_dir;
    private $max_backup_size = 100 * 1024 * 1024; // 100MB

    public function __construct($koneksi) {
        $this->koneksi = $koneksi;

        // Gunakan path absolut untuk konsistensi - sama seperti test_direct.php
        $backup_dir = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'backups';

        // Normalisasi path (untuk Windows)
        $backup_dir = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $backup_dir);

        // Buat directory backup jika belum ada
        if (!is_dir($backup_dir)) {
            if (!@mkdir($backup_dir, 0755, true)) {
                $this->backup_dir = $backup_dir;
            } else {
                // Setelah dibuat, dapatkan realpath jika ada
                $realpath = @realpath($backup_dir);
                $this->backup_dir = $realpath ?: $backup_dir;
            }
        } else {
            // Pastikan menggunakan realpath jika ada (sama seperti test_direct.php)
            $realpath = @realpath($backup_dir);
            $this->backup_dir = $realpath ?: $backup_dir;
        }

        // Normalisasi path lagi untuk memastikan konsistensi
        $this->backup_dir = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $this->backup_dir);

        // Pastikan folder writable - coba beberapa kali jika perlu
        if (is_dir($this->backup_dir)) {
            if (!is_writable($this->backup_dir)) {
                // Coba ubah permission
                @chmod($this->backup_dir, 0755);

                // Tunggu sebentar untuk Windows
                usleep(100000); // 100ms

                // Cek lagi
                if (!is_writable($this->backup_dir)) {
                }
            }
        }

        // Log untuk debugging
    }

    /**
     * Get backup directory path (normalized)
     */
    private function getBackupDir() {
        // Pastikan menggunakan $this->backup_dir yang sudah diset di constructor
        $dir = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $this->backup_dir);

        // Coba realpath, jika gagal gunakan path asli
        $realpath = @realpath($dir);
        if ($realpath) {
            return $realpath;
        }

        // Jika realpath gagal, pastikan path sudah normalisasi
        return $dir;
    }

    /**
     * Get absolute backup directory path
     */
    public function getBackupDirectory() {
        return $this->getBackupDir();
    }

    /**
     * Parse memory limit string to bytes
     */
    private function parseMemoryLimit($limit) {
        $limit = trim($limit);
        $last = strtolower($limit[strlen($limit)-1]);
        $value = (int) $limit;

        switch($last) {
            case 'g':
                $value *= 1024;
            case 'm':
                $value *= 1024;
            case 'k':
                $value *= 1024;
        }

        return $value;
    }

    /**
     * Backup database
     */
    public function backup($filename = null) {
        $filepath = null;
        $backup_content = ''; // Buffer untuk menyimpan semua konten backup

        // Set memory limit lebih tinggi untuk database besar
        $current_memory_limit = ini_get('memory_limit');
        $memory_limit_bytes = $this->parseMemoryLimit($current_memory_limit);
        if ($memory_limit_bytes < 256 * 1024 * 1024) { // Jika kurang dari 256MB
            @ini_set('memory_limit', '512M');
        }

        // Set max execution time lebih lama
        @set_time_limit(300); // 5 menit

        try {
            // Gunakan method helper untuk mendapatkan path yang konsisten
            $backup_dir = $this->getBackupDir();

            // Pastikan folder backup ada
            if (!is_dir($backup_dir)) {
                if (!@mkdir($backup_dir, 0755, true)) {
                    return [
                        'success' => false,
                        'message' => 'Gagal membuat folder backups. Pastikan permission folder benar. Path: ' . $backup_dir
                    ];
                }
                // Update path setelah dibuat
                clearstatcache(true, $backup_dir);
                $backup_dir = $this->getBackupDir();
            }

            // Pastikan folder writable - cek dengan lebih detail
            clearstatcache(true, $backup_dir);
            if (!is_writable($backup_dir)) {
                // Coba ubah permission
                @chmod($backup_dir, 0777); // Coba 0777 untuk Windows
                sleep(1); // Tunggu 1 detik
                clearstatcache(true, $backup_dir);

                if (!is_writable($backup_dir)) {
                    return [
                        'success' => false,
                        'message' => 'Folder backups tidak dapat ditulis. Pastikan permission folder benar. Path: ' . $backup_dir
                    ];
                }
            }

            if ($filename === null) {
                $filename = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
            }

            // Validasi filename
            $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
            if (empty($filename) || !preg_match('/\.sql$/', $filename)) {
                $filename = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
            }

            // Gunakan DIRECTORY_SEPARATOR untuk konsistensi cross-platform
            $filepath = $backup_dir . DIRECTORY_SEPARATOR . $filename;


            // TEST WRITE: Coba tulis file test kecil dulu untuk memastikan folder benar-benar writable
            $test_file = $backup_dir . DIRECTORY_SEPARATOR . 'test_write_' . time() . '.txt';
            $test_content = 'Test write at ' . date('Y-m-d H:i:s');
            $test_write = @file_put_contents($test_file, $test_content);

            if ($test_write === false) {
                return [
                    'success' => false,
                    'message' => 'Folder backups tidak dapat ditulis. Silakan cek permission folder. Path: ' . $backup_dir
                ];
            }

            // Verifikasi test file benar-benar tersimpan
            clearstatcache(true, $test_file);
            sleep(1);
            if (!file_exists($test_file) || filesize($test_file) == 0) {
                return [
                    'success' => false,
                    'message' => 'File test tidak tersimpan. Silakan cek permission folder. Path: ' . $backup_dir
                ];
            }

            // Hapus test file
            @unlink($test_file);
            clearstatcache(true, $test_file);

            // Hapus file backup lama jika sudah ada
            if (file_exists($filepath)) {
                @unlink($filepath);
                clearstatcache(true, $filepath);
                sleep(1); // Tunggu file benar-benar terhapus
            }

            // Kumpulkan semua konten backup ke string
            // Menggunakan file_put_contents sebagai metode utama (lebih reliable untuk Windows)
            $backup_content = '';

            // Write header
            $db_name = '';
            try {
                $db_result = $this->koneksi->query("SELECT DATABASE()");
                if ($db_result && $db_result->num_rows > 0) {
                    $db_row = $db_result->fetch_row();
                    $db_name = $db_row ? $db_row[0] : 'unknown';
                }
            } catch (Exception $e) {
                $db_name = 'unknown';
            }

            $backup_content .= "-- Database Backup\n";
            $backup_content .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
            $backup_content .= "-- Database: " . $db_name . "\n\n";
            $backup_content .= "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
            $backup_content .= "SET AUTOCOMMIT = 0;\n";
            $backup_content .= "START TRANSACTION;\n";
            $backup_content .= "SET time_zone = \"+00:00\";\n\n";

            // Get all tables
            $tables_result = $this->koneksi->query("SHOW TABLES");
            if (!$tables_result) {
                return [
                    'success' => false,
                    'message' => 'Gagal mengambil daftar tabel: ' . $this->koneksi->error
                ];
            }

            $tables = $tables_result;
            $table_count = 0;

            while ($row = $tables->fetch_row()) {
                $table = $row[0];
                $table_count++;

                // Drop table
                $backup_content .= "\n-- --------------------------------------------------------\n";
                $backup_content .= "-- Table structure for table `$table`\n";
                $backup_content .= "-- --------------------------------------------------------\n\n";
                $backup_content .= "DROP TABLE IF EXISTS `$table`;\n\n";

                // Create table
                $create_table = $this->koneksi->query("SHOW CREATE TABLE `$table`");
                $create_row = $create_table->fetch_row();
                $backup_content .= $create_row[1] . ";\n\n";

                // Table data
                $backup_content .= "-- Dumping data for table `$table`\n\n";

                $data = $this->koneksi->query("SELECT * FROM `$table`");
                $total_rows = $data->num_rows;
                $keys = null;
                $insert_values = [];

                if ($total_rows > 0) {
                    while ($row_data = $data->fetch_assoc()) {
                        // Get keys from first row
                        if ($keys === null) {
                            $keys = array_keys($row_data);
                        }

                        $values = array_values($row_data);
                        $escaped_values = [];
                        foreach ($values as $value) {
                            if ($value === null) {
                                $escaped_values[] = 'NULL';
                            } else {
                                $escaped_values[] = "'" . mysqli_real_escape_string($this->koneksi, $value) . "'";
                            }
                        }

                        $insert_values[] = "(" . implode(', ', $escaped_values) . ")";
                    }

                    // Write INSERT statement
                    if (count($insert_values) > 0) {
                        $backup_content .= "INSERT INTO `$table` (`" . implode('`, `', $keys) . "`) VALUES\n";
                        $backup_content .= implode(",\n", $insert_values) . ";\n\n";
                    }
                } else {
                    $backup_content .= "-- No data in table `$table`\n\n";
                }
            }

            // Write footer
            $backup_content .= "COMMIT;\n";

            $content_size = strlen($backup_content);

            // Tulis semua konten sekaligus dengan file_put_contents
            // Ini adalah metode yang lebih reliable untuk Windows
            $write_result = @file_put_contents($filepath, $backup_content, LOCK_EX);

            if ($write_result === false) {
                $last_error = error_get_last();
                return [
                    'success' => false,
                    'message' => 'Gagal menulis file backup. Path: ' . $filepath . '. Error: ' . ($last_error ? $last_error['message'] : 'Unknown error')
                ];
            }


            // Verifikasi langsung dengan membaca file kembali
            // Ini memastikan file benar-benar tersimpan
            $file_verified = false;
            $file_size_on_disk = 0;

            for ($verify_attempt = 0; $verify_attempt < 20; $verify_attempt++) {
                // Tunggu untuk Windows file system sync
                sleep(1);
                clearstatcache(true, $filepath);
                clearstatcache(true, $backup_dir);

                if (!file_exists($filepath)) {
                    // Jika file tidak ada setelah beberapa attempt, coba tulis ulang
                    if ($verify_attempt >= 5) {
                        $rewrite_result = @file_put_contents($filepath, $backup_content, LOCK_EX);
                        if ($rewrite_result !== false) {
                        }
                    }
                    continue;
                }

                $file_size = @filesize($filepath);
                if ($file_size === false || $file_size == 0) {
                    // Jika file kosong setelah beberapa attempt, coba tulis ulang
                    if ($verify_attempt >= 5) {
                        $rewrite_result = @file_put_contents($filepath, $backup_content, LOCK_EX);
                        if ($rewrite_result !== false) {
                        }
                    }
                    continue;
                }

                // Verifikasi konten file valid (baca header dan sedikit konten)
                $file_header = @file_get_contents($filepath, false, null, 0, 50);
                if ($file_header === false || strpos($file_header, '-- Database Backup') !== 0) {
                    // Jika header invalid setelah beberapa attempt, coba tulis ulang
                    if ($verify_attempt >= 5) {
                        $rewrite_result = @file_put_contents($filepath, $backup_content, LOCK_EX);
                        if ($rewrite_result !== false) {
                        }
                    }
                    continue;
                }

                // Verifikasi ukuran file sesuai dengan konten yang ditulis
                if (abs($file_size - $content_size) > 100) { // Allow 100 bytes difference
                    // Jika size mismatch setelah beberapa attempt, coba tulis ulang
                    if ($verify_attempt >= 5) {
                        $rewrite_result = @file_put_contents($filepath, $backup_content, LOCK_EX);
                        if ($rewrite_result !== false) {
                        }
                    }
                    continue;
                }

                // Semua verifikasi passed
                $file_verified = true;
                $file_size_on_disk = $file_size;
                break;
            }

            if (!$file_verified) {
                if (file_exists($filepath)) {
                    // Jangan hapus file, biarkan untuk debugging
                }
                return [
                    'success' => false,
                    'message' => 'File backup tidak berhasil diverifikasi setelah ditulis. Path: ' . $filepath . '. Silakan cek error log untuk detail.'
                ];
            }


            // Verifikasi tambahan: pastikan file dapat dibaca oleh getBackups()
            // Tunggu lagi untuk memastikan file system sync
            usleep(500000); // 500ms lagi
            clearstatcache(true, $filepath);
            clearstatcache(true, $backup_dir);

            // Test dengan scandir (sama seperti getBackups())
            $test_files_scandir = [];
            $dir_handle_test = @opendir($backup_dir);
            if ($dir_handle_test) {
                while (($entry = readdir($dir_handle_test)) !== false) {
                    if ($entry != '.' && $entry != '..' && $entry == $filename) {
                        $test_files_scandir[] = $entry;
                        break;
                    }
                }
                closedir($dir_handle_test);
            }

            // Test dengan glob juga
            $test_files_glob = @glob($backup_dir . DIRECTORY_SEPARATOR . '*.sql');
            $found_in_glob = false;
            if ($test_files_glob) {
                foreach ($test_files_glob as $test_file) {
                    if (basename($test_file) == $filename) {
                        $found_in_glob = true;
                        break;
                    }
                }
            }


            // Final check sebelum return - Dikurangi delay untuk mempercepat proses
            sleep(1); // 1 detik untuk memastikan file benar-benar tersimpan di Windows
            clearstatcache(true, $filepath);
            clearstatcache(true, $backup_dir);

            // Baca file sekali lagi untuk memastikan benar-benar tersimpan
            $final_read = @file_get_contents($filepath);
            if ($final_read === false || strlen($final_read) == 0) {
                // Coba tulis ulang sekali lagi
                $final_write = @file_put_contents($filepath, $backup_content, LOCK_EX);
                if ($final_write === false) {
                    return [
                        'success' => false,
                        'message' => 'File backup tidak berhasil disimpan. Silakan cek permission folder backups.'
                    ];
                }
                sleep(1); // Dikurangi dari 2 detik menjadi 1 detik
                clearstatcache(true, $filepath);
                // Baca lagi setelah rewrite
                $final_read = @file_get_contents($filepath);
            }

            // Verifikasi final - pastikan file bisa dibaca, ukurannya benar, dan kontennya valid
            if (!file_exists($filepath)) {
                return [
                    'success' => false,
                    'message' => 'File backup tidak berhasil disimpan. Silakan cek permission folder backups.'
                ];
            }

            $final_size = @filesize($filepath);
            if ($final_size === false || $final_size == 0) {
                return [
                    'success' => false,
                    'message' => 'File backup kosong. Silakan coba lagi.'
                ];
            }

            if ($final_read === false || strlen($final_read) == 0) {
                return [
                    'success' => false,
                    'message' => 'File backup tidak dapat dibaca. Silakan coba lagi.'
                ];
            }

            if (strpos($final_read, '-- Database Backup') !== 0) {
                return [
                    'success' => false,
                    'message' => 'File backup tidak valid. Silakan coba lagi.'
                ];
            }

            // Verifikasi ukuran file sesuai dengan konten yang ditulis
            if (abs(strlen($final_read) - $content_size) > 100) {
                // Tulis ulang jika size mismatch terlalu besar
                $final_write = @file_put_contents($filepath, $backup_content, LOCK_EX);
                if ($final_write !== false) {
                    sleep(1);
                    clearstatcache(true, $filepath);
                }
            }


            // Gunakan file_size_on_disk untuk return
            $final_file_size = $file_size_on_disk;

            return [
                'success' => true,
                'message' => 'Backup berhasil dibuat',
                'filename' => $filename,
                'filepath' => $filepath,
                'size' => $final_file_size,
                'size_formatted' => $this->formatBytes($final_file_size),
                'table_count' => $table_count,
                'created_at' => date('Y-m-d H:i:s'),
                'backup_dir' => $backup_dir
            ];

        } catch (Exception $e) {
            // Cleanup jika error
            if (isset($filepath) && $filepath && file_exists($filepath)) {
                @unlink($filepath);
            }

            return [
                'success' => false,
                'message' => 'Error saat membuat backup: ' . $e->getMessage() . ' (Line: ' . $e->getLine() . ')'
            ];
        } catch (Error $e) {
            // Cleanup jika fatal error
            if (isset($filepath) && $filepath && file_exists($filepath)) {
                @unlink($filepath);
            }

            return [
                'success' => false,
                'message' => 'Fatal Error saat membuat backup: ' . $e->getMessage() . ' (Line: ' . $e->getLine() . ')'
            ];
        }
    }

    /**
     * Restore database dari file SQL
     */
    public function restore($filepath) {
        try {
            // Validasi file
            if (!file_exists($filepath)) {
                return [
                    'success' => false,
                    'message' => 'File backup tidak ditemukan'
                ];
            }

            if (!is_readable($filepath)) {
                return [
                    'success' => false,
                    'message' => 'File backup tidak dapat dibaca'
                ];
            }

            // Baca file SQL
            $sql = file_get_contents($filepath);

            if ($sql === false) {
                return [
                    'success' => false,
                    'message' => 'Gagal membaca file backup'
                ];
            }

            // Disable foreign key checks
            $this->koneksi->query("SET FOREIGN_KEY_CHECKS = 0");
            $this->koneksi->query("SET AUTOCOMMIT = 0");
            $this->koneksi->query("START TRANSACTION");

            // Remove comments
            $sql = preg_replace('/--.*$/m', '', $sql);
            $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);

            // Split SQL by semicolon (handle multi-line statements)
            // Gunakan pendekatan yang lebih sederhana dan reliable
            $queries = [];

            // Split berdasarkan semicolon, tapi handle string dengan benar
            // Gunakan pendekatan yang lebih aman dengan explode dan validasi
            $lines = explode("\n", $sql);
            $current_query = '';

            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line)) {
                    continue;
                }

                $current_query .= $line . "\n";

                // Jika line berakhir dengan semicolon (di luar string), simpan query
                if (preg_match('/;\s*$/', $line)) {
                    $query = trim($current_query);
                    if (!empty($query) && strlen($query) > 5) {
                        // Skip SET, START, COMMIT, DROP TABLE IF EXISTS statements
                        if (!preg_match('/^(SET|START|COMMIT|DROP\s+TABLE\s+IF\s+EXISTS)/i', $query)) {
                            $queries[] = $query;
                        }
                    }
                    $current_query = '';
                }
            }

            // Add remaining query if any
            if (!empty(trim($current_query))) {
                $query = trim($current_query);
                if (strlen($query) > 5 && !preg_match('/^(SET|START|COMMIT|DROP\s+TABLE\s+IF\s+EXISTS)/i', $query)) {
                    $queries[] = $query;
                }
            }

            $executed = 0;
            $errors = [];

            // Execute queries
            foreach ($queries as $index => $query) {
                $query = trim($query);
                if (empty($query) || strlen($query) < 5) {
                    continue;
                }

                // Skip query yang sudah di-skip sebelumnya
                if (preg_match('/^(SET|START|COMMIT|DROP\s+TABLE\s+IF\s+EXISTS)/i', $query)) {
                    continue;
                }

                if (!$this->koneksi->query($query)) {
                    $error_msg = $this->koneksi->error;
                    // Skip error yang tidak kritis (seperti table already exists, dll)
                    if (stripos($error_msg, 'already exists') !== false ||
                        stripos($error_msg, 'duplicate') !== false) {
                        // Error tidak kritis, tetap lanjutkan
                        $executed++;
                        continue;
                    }
                    $errors[] = "Query #" . ($index + 1) . ": " . $error_msg . " (Query: " . substr($query, 0, 100) . "...)";
                } else {
                    $executed++;
                }
            }

            // Commit transaction
            if (empty($errors)) {
                $this->koneksi->query("COMMIT");
            } else {
                $this->koneksi->query("ROLLBACK");
            }

            // Enable foreign key checks
            $this->koneksi->query("SET FOREIGN_KEY_CHECKS = 1");
            $this->koneksi->query("SET AUTOCOMMIT = 1");

            if (!empty($errors)) {
                $error_count = count($errors);
                $error_summary = $error_count > 3 ?
                    implode("\n", array_slice($errors, 0, 3)) . "\n... dan " . ($error_count - 3) . " error lainnya" :
                    implode("\n", $errors);

                return [
                    'success' => false,
                    'message' => "Restore selesai dengan $error_count error. $executed query berhasil dieksekusi.",
                    'executed' => $executed,
                    'errors' => $errors,
                    'error_summary' => $error_summary
                ];
            }

            return [
                'success' => true,
                'message' => 'Restore berhasil',
                'executed' => $executed
            ];

        } catch (Exception $e) {
            // Rollback dan enable foreign key checks jika error
            $this->koneksi->query("ROLLBACK");
            $this->koneksi->query("SET FOREIGN_KEY_CHECKS = 1");
            $this->koneksi->query("SET AUTOCOMMIT = 1");

            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get list backups
     */
    public function getBackups() {
        $backups = [];

        // Gunakan method helper untuk mendapatkan path yang konsisten
        $backup_dir = $this->getBackupDir();


        if (is_dir($backup_dir)) {
            // Clear stat cache untuk memastikan data terbaru
            clearstatcache(true);

            // Gunakan scandir sebagai metode utama (lebih reliable) - sama seperti test_direct.php
            $files = [];
            $dir_handle = @opendir($backup_dir);
            if ($dir_handle) {
                while (($entry = readdir($dir_handle)) !== false) {
                    if ($entry != '.' && $entry != '..') {
                        $entry_path = $backup_dir . DIRECTORY_SEPARATOR . $entry;
                        if (is_file($entry_path) &&
                            strtolower(pathinfo($entry, PATHINFO_EXTENSION)) == 'sql') {
                            $files[] = $entry_path;
                        }
                    }
                }
                closedir($dir_handle);
            }


            // Jika scandir tidak menemukan file, coba glob sebagai fallback dengan retry
            if (empty($files)) {
                $pattern = $backup_dir . DIRECTORY_SEPARATOR . '*.sql';

                // Retry glob beberapa kali untuk Windows file system sync
                for ($glob_retry = 0; $glob_retry < 5; $glob_retry++) {
                    clearstatcache(true);
                    $glob_files = @glob($pattern);
                    if (is_array($glob_files) && !empty($glob_files)) {
                        $files = $glob_files;
                        break;
                    }
                    if ($glob_retry < 4) {
                        sleep(1); // 1 detik per retry
                    }
                }

                if (empty($files)) {
                }
            }

            foreach ($files as $file) {
                // Normalisasi path
                $file = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $file);

                // Clear stat cache untuk file ini
                clearstatcache(true, $file);

                // Log setiap file yang diproses

                // Skip jika bukan file atau tidak readable
                if (!is_file($file) || !is_readable($file)) {
                    continue;
                }

                $file_size = @filesize($file);
                $file_mtime = @filemtime($file);


                // Skip jika file size atau mtime tidak valid
                if ($file_size === false || $file_mtime === false || $file_size == 0) {
                    continue;
                }


                $backups[] = [
                    'filename' => basename($file),
                    'filepath' => $file,
                    'size' => $file_size,
                    'size_formatted' => $this->formatBytes($file_size),
                    'created_at' => date('Y-m-d H:i:s', $file_mtime),
                    'modified_at' => $file_mtime
                ];
            }

            // Sort by modified time (newest first)
            usort($backups, function($a, $b) {
                return $b['modified_at'] - $a['modified_at'];
            });

        } else {
        }

        return $backups;
    }

    /**
     * Delete backup file
     */
    public function deleteBackup($filename) {
        $filepath = $this->backup_dir . DIRECTORY_SEPARATOR . $filename;

        if (file_exists($filepath)) {
            if (unlink($filepath)) {
                return [
                    'success' => true,
                    'message' => 'Backup berhasil dihapus'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Gagal menghapus file backup'
                ];
            }
        }

        return [
            'success' => false,
            'message' => 'File backup tidak ditemukan'
        ];
    }

    /**
     * Get backup directory size
     */
    public function getBackupDirSize() {
        $size = 0;

        // Normalisasi path
        $backup_dir = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $this->backup_dir);
        $realpath = @realpath($backup_dir);
        if ($realpath) {
            $backup_dir = $realpath;
        }

        if (is_dir($backup_dir)) {
            $pattern = $backup_dir . DIRECTORY_SEPARATOR . '*.sql';
            $files = @glob($pattern);

            if ($files === false) {
                // Fallback ke scandir
                $files = [];
                if (is_dir($backup_dir)) {
                    $dir_handle = @opendir($backup_dir);
                    if ($dir_handle) {
                        while (($entry = readdir($dir_handle)) !== false) {
                            if ($entry != '.' && $entry != '..' &&
                                is_file($backup_dir . DIRECTORY_SEPARATOR . $entry) &&
                                strtolower(pathinfo($entry, PATHINFO_EXTENSION)) == 'sql') {
                                $files[] = $backup_dir . DIRECTORY_SEPARATOR . $entry;
                            }
                        }
                        closedir($dir_handle);
                    }
                }
            }

            foreach ($files as $file) {
                if (is_file($file)) {
                    $size += filesize($file);
                }
            }
        }

        return [
            'size' => $size,
            'size_formatted' => $this->formatBytes($size),
            'file_count' => count($files ?: [])
        ];
    }

    /**
     * Format bytes to human readable
     */
    private function formatBytes($bytes, $precision = 2) {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= pow(1024, $pow);

        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    /**
     * Clean old backups (keep only last N backups)
     */
    public function cleanOldBackups($keep = 10) {
        $backups = $this->getBackups();

        if (count($backups) <= $keep) {
            return [
                'success' => true,
                'message' => 'Tidak ada backup lama yang perlu dihapus',
                'deleted' => 0
            ];
        }

        $to_delete = array_slice($backups, $keep);
        $deleted = 0;

        foreach ($to_delete as $backup) {
            if ($this->deleteBackup($backup['filename'])['success']) {
                $deleted++;
            }
        }

        return [
            'success' => true,
            'message' => "Berhasil menghapus $deleted backup lama",
            'deleted' => $deleted
        ];
    }
}
