<?php
// Handle download backup - HARUS DIEKSEKUSI PERTAMA sebelum include header.php
// karena header.php mengeluarkan HTML output yang akan mengganggu download
if (isset($_GET['download'])) {
    // Bersihkan semua output buffer SEBELUM apapun
    while (ob_get_level()) {
        ob_end_clean();
    }

    // Pastikan tidak ada output sebelumnya
    if (headers_sent($file, $line)) {
        die("Error: Headers already sent. Cannot download file.");
    }

    // Include config dan koneksi database terlebih dahulu
    // PASTIKAN tidak ada output dari file-file ini
    ob_start();
    require_once '../config/config.php';
    require_once '../libs/backup_restore.php';
    ob_end_clean();

    $backup_restore = new BackupRestore($koneksi);
    $filename = basename($_GET['download']);

    // Validasi filename untuk keamanan
    if (preg_match('/[^a-zA-Z0-9._-]/', $filename)) {
        die("Invalid filename");
    }

    // Gunakan getBackupDirectory() untuk mendapatkan path yang konsisten
    $backup_dir = $backup_restore->getBackupDirectory();
    $filepath = $backup_dir . DIRECTORY_SEPARATOR . $filename;

    // Clear stat cache untuk memastikan file terdeteksi
    clearstatcache(true, $filepath);

    if (file_exists($filepath) && is_readable($filepath)) {
        // Log aktivitas (sebelum output headers) - PASTIKAN tidak ada output
        ob_start();
        require_once '../libs/log_activity.php';
        @log_activity('download', 'backup', 'Download backup: ' . $filename);
        ob_end_clean();

        // Bersihkan output buffer sekali lagi sebelum headers
        while (ob_get_level()) {
            ob_end_clean();
        }

        // Pastikan tidak ada output
        if (ob_get_length()) {
            ob_clean();
        }

        // Pastikan headers belum terkirim
        if (headers_sent($file, $line)) {
            die("Error: Headers already sent. Cannot download file.");
        }

        // Set headers untuk download
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . addslashes($filename) . '"');
        header('Content-Length: ' . filesize($filepath));
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        header('Expires: 0');
        header('X-Content-Type-Options: nosniff');

        // Flush headers
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }

        // Output file dengan chunk untuk file besar
        $chunk_size = 8192; // 8KB chunks
        $handle = fopen($filepath, 'rb');

        if ($handle === false) {
            die("Error: Cannot open file for reading");
        }

        // Output file dalam chunks
        while (!feof($handle)) {
            echo fread($handle, $chunk_size);
            flush();
            if (ob_get_level()) {
                ob_flush();
            }
        }

        fclose($handle);
        exit;
    } else {
        // Jika file tidak ditemukan, bersihkan output dan redirect
        while (ob_get_level()) {
            ob_end_clean();
        }

        // Pastikan headers belum terkirim sebelum redirect
        if (!headers_sent()) {
            header('Location: ' . base_url('admin/backup.php?error=' . urlencode('File backup tidak ditemukan: ' . $filename)));
        }
        exit;
    }
}

// Handler backup sekarang menggunakan AJAX (backup_handler.php)
// Handler POST lama dihapus untuk menghindari masalah redirect/header
// Semua request backup sekarang ditangani oleh backup_handler.php melalui AJAX

// Handle restore - HARUS SEBELUM include header.php
if (isset($_POST['restore']) && isset($_FILES['backup_file'])) {
    // Bersihkan semua output buffer SEBELUM apapun
    while (ob_get_level()) {
        ob_end_clean();
    }

    // Pastikan tidak ada output sebelumnya
    if (headers_sent($file, $line)) {
        die("Error: Headers already sent. Cannot proceed with restore.");
    }

    // Include config dan backup_restore
    require_once '../config/config.php';
    require_once '../libs/backup_restore.php';

    $backup_restore = new BackupRestore($koneksi);
    $upload_dir = __DIR__ . '/../backups/temp/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    $uploaded_file = $upload_dir . basename($_FILES['backup_file']['name']);

    // Validate file
    $file_ext = strtolower(pathinfo($_FILES['backup_file']['name'], PATHINFO_EXTENSION));
    if ($file_ext !== 'sql') {
        while (ob_get_level()) {
            ob_end_clean();
        }
        if (!headers_sent()) {
            header('Location: ' . base_url('admin/backup.php?error=' . urlencode('File harus berformat .sql')));
        }
        exit;
    } else {
        if (move_uploaded_file($_FILES['backup_file']['tmp_name'], $uploaded_file)) {
            $result = $backup_restore->restore($uploaded_file);

            if ($result['success']) {
                // Log aktivitas
                require_once '../libs/log_activity.php';
                log_activity('restore', 'database', 'Restore database dari file: ' . $_FILES['backup_file']['name']);

                // Delete temp file
                @unlink($uploaded_file);

                // Bersihkan output buffer sebelum redirect
                while (ob_get_level()) {
                    ob_end_clean();
                }

                // Redirect dengan success
                if (!headers_sent()) {
                    header('Location: ' . base_url('admin/backup.php?restore_success=1'));
                }
                exit;
            } else {
                @unlink($uploaded_file);
                while (ob_get_level()) {
                    ob_end_clean();
                }
                if (!headers_sent()) {
                    header('Location: ' . base_url('admin/backup.php?error=' . urlencode($result['message'])));
                }
                exit;
            }
        } else {
            while (ob_get_level()) {
                ob_end_clean();
            }
            if (!headers_sent()) {
                header('Location: ' . base_url('admin/backup.php?error=' . urlencode('Gagal mengupload file')));
            }
            exit;
        }
    }
}

// Handle restore from file list - HARUS SEBELUM include header.php
if (isset($_POST['restore_file'])) {
    // Bersihkan semua output buffer SEBELUM apapun
    while (ob_get_level()) {
        ob_end_clean();
    }

    // Pastikan tidak ada output sebelumnya
    if (headers_sent($file, $line)) {
        die("Error: Headers already sent. Cannot proceed with restore.");
    }

    // Include config dan backup_restore
    require_once '../config/config.php';
    require_once '../libs/backup_restore.php';

    $backup_restore = new BackupRestore($koneksi);
    $filename = basename($_POST['restore_file']);

    // Validasi filename untuk keamanan
    if (preg_match('/[^a-zA-Z0-9._-]/', $filename)) {
        while (ob_get_level()) {
            ob_end_clean();
        }
        if (!headers_sent()) {
            header('Location: ' . base_url('admin/backup.php?error=' . urlencode('Invalid filename')));
        }
        exit;
    }

    // Gunakan getBackupDirectory() untuk mendapatkan path yang konsisten
    $backup_dir = $backup_restore->getBackupDirectory();
    $filepath = $backup_dir . DIRECTORY_SEPARATOR . $filename;

    // Clear stat cache untuk memastikan file terdeteksi
    clearstatcache(true, $filepath);

    if (file_exists($filepath) && is_readable($filepath)) {
        $result = $backup_restore->restore($filepath);

        if ($result['success']) {
            // Log aktivitas
            require_once '../libs/log_activity.php';
            log_activity('restore', 'database', 'Restore database dari file: ' . $filename);

            // Bersihkan output buffer sebelum redirect
            while (ob_get_level()) {
                ob_end_clean();
            }

            // Redirect dengan success
            if (!headers_sent()) {
                header('Location: ' . base_url('admin/backup.php?restore_success=1'));
            }
            exit;
        } else {
            while (ob_get_level()) {
                ob_end_clean();
            }
            if (!headers_sent()) {
                header('Location: ' . base_url('admin/backup.php?error=' . urlencode($result['message'])));
            }
            exit;
        }
    } else {
        while (ob_get_level()) {
            ob_end_clean();
        }
        if (!headers_sent()) {
            header('Location: ' . base_url('admin/backup.php?error=' . urlencode('File backup tidak ditemukan: ' . $filename)));
        }
        exit;
    }
}

// Handler delete sekarang menggunakan AJAX (delete_backup_handler.php)
// Handler GET delete lama dihapus untuk menghindari masalah redirect/header
// Semua request delete sekarang ditangani oleh delete_backup_handler.php melalui AJAX

// Include header SETELAH handle backup selesai
$page_title = 'Backup & Restore';
include_once('../header.php');
require_once '../libs/backup_restore.php';

// Load backups list SETELAH semua operasi selesai (untuk memastikan data terbaru)
// Buat instance baru untuk memastikan path konsisten
$backup_restore = new BackupRestore($koneksi);

// Ambil error message dari session jika ada (untuk error yang terjadi sebelum header.php)
if (isset($_SESSION['backup_error'])) {
    $error_message = $_SESSION['backup_error'];
    unset($_SESSION['backup_error']);
}

// Clear stat cache sebelum membaca - PENTING untuk Windows
clearstatcache(true);

// Tunggu lebih lama jika baru saja membuat backup (untuk file system sync di Windows)
if (isset($_GET['success']) || isset($_GET['t']) || isset($_GET['refresh'])) {
    // Delay LEBIH LAMA untuk memastikan file system sync di Windows
    sleep(5); // 5 detik untuk Windows file system sync
    clearstatcache(true);
    clearstatcache(true, $backup_restore->getBackupDirectory());

    // Buat instance baru untuk memastikan path konsisten
    $backup_restore = new BackupRestore($koneksi);
}

// Get backups - PASTIKAN menggunakan instance terbaru
$backups = $backup_restore->getBackups();
$backup_info = $backup_restore->getBackupDirSize();

// Jika baru saja membuat backup, pastikan file muncul di list
if (isset($_GET['success'])) {
    $expected_filename = isset($_GET['filename']) ? urldecode($_GET['filename']) : null;

    // Cek apakah file yang baru dibuat muncul di list
    $file_found_in_list = false;
    if ($expected_filename) {
        foreach ($backups as $backup) {
            if ($backup['filename'] == $expected_filename) {
                $file_found_in_list = true;
                break;
            }
        }
    }

    // Jika file belum muncul, tunggu dan coba beberapa kali
    $max_retries = 3;
    $retry_count = 0;
    while (!$file_found_in_list && $expected_filename && $retry_count < $max_retries) {
        sleep(2); // 2 detik per retry
        clearstatcache(true);
        // Buat instance baru untuk memastikan path konsisten
        $backup_restore = new BackupRestore($koneksi);
        $backups = $backup_restore->getBackups();
        $backup_info = $backup_restore->getBackupDirSize();

        // Cek lagi
        $file_found_in_list = false;
        foreach ($backups as $backup) {
            if ($backup['filename'] == $expected_filename) {
                $file_found_in_list = true;
                break;
            }
        }

        $retry_count++;
    }

    // Jika masih belum muncul setelah semua retry, log warning
    if (!$file_found_in_list && $expected_filename) {
        // Cek apakah file benar-benar ada di directory
        $backup_dir = $backup_restore->getBackupDirectory();
        $file_path = $backup_dir . DIRECTORY_SEPARATOR . $expected_filename;
        if (file_exists($file_path)) {
        }
    }
}

// Jika ada parameter found=0, berarti file belum muncul di getBackups()
// Coba sekali lagi dengan delay lebih lama
if (isset($_GET['found']) && $_GET['found'] == '0') {
    sleep(1); // 1 detik lagi
    clearstatcache(true);
    $backups = $backup_restore->getBackups();
}

// Debug logging
if (count($backups) > 0) {
} else {
    // Cek apakah ada file di directory tapi tidak muncul
    $backup_dir_check = $backup_restore->getBackupDirectory();
    $files_check = glob($backup_dir_check . DIRECTORY_SEPARATOR . '*.sql');
    if (is_array($files_check) && count($files_check) > 0) {
        foreach ($files_check as $file) {
        }
    }
}

// Handler restore dan restore_file sudah dipindahkan ke SEBELUM include header.php untuk menghindari layar blank

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Backup & Restore Database</title>
</head>
<body>
    <div class="page-breadcrumb">
        <div class="row">
            <div class="col-7 align-self-center">
                <h4 class="page-title text-truncate text-dark font-weight-medium mb-1">Backup & Restore Database</h4>
                <div class="d-flex align-items-center">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb m-0 p-0">
                            <li class="breadcrumb-item"><a href="<?=base_url('home')?>" class="text-muted">Home</a></li>
                            <li class="breadcrumb-item text-muted active" aria-current="page">Backup & Restore</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid">
        <?php if (isset($_GET['success'])):
            $backup_filename = isset($_GET['filename']) ? htmlspecialchars(urldecode($_GET['filename'])) : '';
        ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fa fa-check-circle"></i>
            <strong>Backup berhasil dibuat!</strong>
            <?php if ($backup_filename): ?>
                <br>File: <strong><?=$backup_filename?></strong>
            <?php endif; ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <?php endif; ?>

        <?php if (isset($_GET['restore_success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fa fa-check-circle"></i> Restore berhasil!
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <?php endif; ?>

        <?php if (isset($_GET['delete_success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fa fa-check-circle"></i> Backup berhasil dihapus!
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fa fa-exclamation-circle"></i> <?=htmlspecialchars(urldecode($_GET['error']))?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <?php endif; ?>

        <?php if (isset($error_message) || isset($_GET['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fa fa-exclamation-circle"></i>
            <strong>Error!</strong>
            <?php
            $display_error = isset($error_message) ? $error_message : (isset($_GET['error']) ? urldecode($_GET['error']) : '');
            echo htmlspecialchars($display_error);
            ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <?php endif; ?>

        <div class="row">
            <!-- Backup Section -->
            <div class="col-lg-6">
                <div class="modern-card">
                    <div class="modern-card-header">
                        <h4>
                            <i class="fa fa-database"></i> Backup Database
                        </h4>
                    </div>
                    <div class="modern-card-body">
                        <p class="text-muted">Buat backup database untuk keamanan data</p>

                        <div class="alert alert-info">
                            <i class="fa fa-info-circle"></i>
                            <strong>Info:</strong> Backup akan menyimpan semua tabel dan data ke file SQL
                        </div>

                        <form method="POST" id="backupForm">
                            <button type="submit" name="backup" class="btn btn-primary btn-lg btn-block" id="btnBackup">
                                <i class="fa fa-download"></i> Buat Backup Sekarang
                            </button>
                        </form>

                        <div class="mt-3">
                            <small class="text-muted">
                                <i class="fa fa-clock-o"></i> Proses backup mungkin memakan waktu beberapa menit tergantung ukuran database
                            </small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Restore Section -->
            <div class="col-lg-6">
                <div class="modern-card">
                    <div class="modern-card-header">
                        <h4>
                            <i class="fa fa-upload"></i> Restore Database
                        </h4>
                    </div>
                    <div class="modern-card-body">
                        <p class="text-muted">Restore database dari file backup</p>

                        <div class="alert alert-warning">
                            <i class="fa fa-exclamation-triangle"></i>
                            <strong>Peringatan:</strong> Restore akan mengganti semua data yang ada dengan data dari backup!
                        </div>

                        <form method="POST" enctype="multipart/form-data" id="restoreForm">
                            <div class="form-group">
                                <label for="backup_file">Pilih File Backup (.sql)</label>
                                <input type="file" name="backup_file" id="backup_file" class="form-control" accept=".sql" required>
                                <small class="form-text text-muted">Hanya file dengan format .sql yang diperbolehkan</small>
                            </div>

                            <button type="button" name="restore" class="btn btn-warning btn-lg btn-block" id="btnRestore" onclick="confirmRestore()">
                                <i class="fa fa-upload"></i> Restore Database
                            </button>
                            <input type="hidden" name="restore" value="1">
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Backup List -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="modern-card">
                    <div class="modern-card-header">
                        <h4>
                            <i class="fa fa-list"></i> Daftar Backup
                            <span class="badge badge-light ml-2"><?=count($backups)?> File</span>
                            <span class="badge badge-light ml-2">Total: <?=$backup_info['size_formatted']?></span>
                        </h4>
                    </div>
                    <div class="modern-card-body">

                        <?php
                        // Debug info (dapat dihapus setelah testing)
                        $backup_dir_debug = realpath(__DIR__ . '/../backups');
                        if (isset($_GET['debug']) && $_SESSION['level'] == 'admin'):
                        ?>
                        <div class="alert alert-secondary">
                            <small>
                                <strong>Debug Info:</strong><br>
                                Backup Directory: <?=htmlspecialchars($backup_dir_debug ?: 'Not found')?><br>
                                Directory Exists: <?=is_dir($backup_dir_debug) ? 'Yes' : 'No'?><br>
                                Directory Writable: <?=is_writable($backup_dir_debug) ? 'Yes' : 'No'?><br>
                                Files Found: <?=count(glob($backup_dir_debug . '/*.sql') ?: [])?>
                            </small>
                        </div>
                        <?php endif; ?>

                        <?php
                        // Get actual backup directory from class
                        $backup_dir_actual = $backup_restore->getBackupDirectory();
                        $files_in_dir = 0;
                        $files_list = [];
                        if (is_dir($backup_dir_actual)) {
                            $files_list = glob($backup_dir_actual . DIRECTORY_SEPARATOR . '*.sql') ?: [];
                            $files_in_dir = count($files_list);
                        }
                        ?>
                        <?php if (empty($backups)): ?>
                        <div class="alert alert-info text-center">
                            <i class="fa fa-info-circle"></i> Belum ada backup yang dibuat
                            <br><small class="text-muted">Directory: <code><?=htmlspecialchars($backup_dir_actual)?></code></small>
                            <br><small class="text-muted">Files in directory (by glob): <strong><?=$files_in_dir?></strong></small>
                            <?php if ($files_in_dir > 0): ?>
                            <div class="alert alert-warning mt-3">
                                <strong>⚠️ Ada <?=$files_in_dir?> file .sql di directory tapi tidak muncul di list!</strong>
                                <br><small>Files:</small>
                                <ul class="text-left" style="display: inline-block;">
                                    <?php foreach ($files_list as $file): ?>
                                    <li><?=htmlspecialchars(basename($file))?> (<?=number_format(filesize($file))?> bytes)</li>
                                    <?php endforeach; ?>
                                </ul>
                                <br><small class="text-muted">Coba refresh halaman atau klik tombol di bawah untuk refresh manual.</small>
                                <br><a href="?refresh=<?=time()?>" class="btn btn-sm btn-primary mt-2">
                                    <i class="fa fa-refresh"></i> Refresh List
                                </a>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="table modern-table">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Nama File</th>
                                        <th>Ukuran</th>
                                        <th>Tanggal Dibuat</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $no = 1; foreach ($backups as $backup): ?>
                                    <tr>
                                        <td><?=$no++?></td>
                                        <td>
                                            <i class="fa fa-file-code-o"></i>
                                            <?=htmlspecialchars($backup['filename'])?>
                                        </td>
                                        <td><?=$backup['size_formatted']?></td>
                                        <td><?=$backup['created_at']?></td>
                                        <td>
                                            <a href="?download=<?=urlencode($backup['filename'])?>" class="btn btn-sm btn-info" title="Download" target="_blank">
                                                <i class="fa fa-download"></i> Download
                                            </a>
                                            <button type="button" class="btn btn-sm btn-warning" onclick="confirmRestoreFile('<?=addslashes($backup['filename'])?>', '<?=urlencode($backup['filename'])?>')" title="Restore">
                                                <i class="fa fa-upload"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-danger" onclick="confirmDeleteBackup('<?=addslashes($backup['filename'])?>', '<?=urlencode($backup['filename'])?>')" title="Hapus">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    // Auto-refresh list jika backup baru saja dibuat
    <?php if (isset($_GET['success'])): ?>
    // Tunggu 2 detik lalu refresh halaman untuk memastikan list ter-update
    setTimeout(function() {
        // Force refresh dengan parameter baru
        window.location.href = '<?=base_url("admin/backup.php")?>';
    }, 2000);
    <?php endif; ?>

    // Jika ada parameter refresh, scroll ke list backup
    <?php if (isset($_GET['refresh'])): ?>
    setTimeout(function() {
        const backupList = document.querySelector('.table-responsive');
        if (backupList) {
            backupList.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }, 500);
    <?php endif; ?>

    // Handle backup form dengan AJAX
    document.getElementById('backupForm').addEventListener('submit', function(e) {
        e.preventDefault();

        Swal.fire({
            title: 'Membuat Backup...',
            html: 'Mohon tunggu, proses backup sedang berjalan<br><small>Jangan tutup halaman ini</small>',
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        // Kirim request dengan AJAX ke handler terpisah
        const formData = new FormData();
        formData.append('backup', '1');

        fetch('backup_handler.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil!',
                    text: data.message,
                    showConfirmButton: false,
                    timer: 2000, // Auto close setelah 2 detik
                    timerProgressBar: true,
                    allowOutsideClick: false,
                    allowEscapeKey: false
                }).then(() => {
                    // Refresh halaman untuk menampilkan backup baru
                    window.location.href = '<?=base_url("admin/backup.php")?>';
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal!',
                    text: data.message || 'Backup gagal. Silakan coba lagi.',
                    showConfirmButton: true,
                    confirmButtonText: 'OK'
                });
            }
        })
        .catch(error => {
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: 'Terjadi kesalahan saat membuat backup. Silakan coba lagi.',
                showConfirmButton: true,
                confirmButtonText: 'OK'
            });
        });
    });

    // Confirm restore
    function confirmRestore() {
        const fileInput = document.getElementById('backup_file');

        if (!fileInput.files || fileInput.files.length === 0) {
            Swal.fire({
                position: 'top-center',
                icon: 'warning',
                title: 'Peringatan!',
                text: 'Pilih file backup terlebih dahulu',
                showConfirmButton: true,
                confirmButtonColor: '#ffc107',
                customClass: {
                    popup: 'animated fadeInDown',
                    title: 'swal2-title-modern',
                    confirmButton: 'swal2-confirm-modern'
                }
            });
            return;
        }

        Swal.fire({
            title: 'Yakin Restore Database?',
            html: '<div style="text-align: left; padding: 10px;">' +
                  '<p><strong>Peringatan!</strong></p>' +
                  '<ul style="text-align: left;">' +
                  '<li>Semua data yang ada akan diganti dengan data dari backup</li>' +
                  '<li>Tindakan ini tidak dapat dibatalkan</li>' +
                  '<li>Pastikan Anda sudah membuat backup terbaru</li>' +
                  '</ul>' +
                  '<p><strong>File:</strong> ' + fileInput.files[0].name + '</p>' +
                  '</div>',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ffc107',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, Restore!',
            cancelButtonText: 'Batal',
            reverseButtons: true,
            customClass: {
                popup: 'animated fadeInDown',
                title: 'swal2-title-modern',
                confirmButton: 'swal2-confirm-modern',
                cancelButton: 'swal2-cancel-modern'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Restore Database...',
                    text: 'Mohon tunggu, proses restore sedang berjalan',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showConfirmButton: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                document.getElementById('restoreForm').submit();
            }
        });
    }

    // Confirm delete backup dengan AJAX
    function confirmDeleteBackup(filename, filepath) {
        Swal.fire({
            title: 'Yakin Hapus Backup?',
            text: 'File backup ' + filename + ' akan dihapus secara permanen!',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal',
            reverseButtons: true,
            customClass: {
                popup: 'animated fadeInDown',
                title: 'swal2-title-modern',
                confirmButton: 'swal2-confirm-modern',
                cancelButton: 'swal2-cancel-modern'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Menghapus Backup...',
                    html: 'Mohon tunggu, proses hapus sedang berjalan',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showConfirmButton: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                // Kirim request dengan AJAX
                const formData = new FormData();
                formData.append('delete', filepath);

                fetch('delete_backup_handler.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil!',
                            text: data.message,
                            showConfirmButton: false,
                            timer: 2000,
                            timerProgressBar: true,
                            allowOutsideClick: false,
                            allowEscapeKey: false
                        }).then(() => {
                            // Refresh halaman untuk menampilkan list terbaru
                            window.location.href = '<?=base_url("admin/backup.php")?>';
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal!',
                            text: data.message || 'Gagal menghapus backup. Silakan coba lagi.',
                            showConfirmButton: true,
                            confirmButtonText: 'OK'
                        });
                    }
                })
                .catch(error => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: 'Terjadi kesalahan saat menghapus backup. Silakan coba lagi.',
                        showConfirmButton: true,
                        confirmButtonText: 'OK'
                    });
                });
            }
        });
    }

    // Confirm restore from file list
    function confirmRestoreFile(filename, filepath) {
        Swal.fire({
            title: 'Yakin Restore Database?',
            html: '<div style="text-align: left; padding: 10px;">' +
                  '<p><strong>Peringatan!</strong></p>' +
                  '<ul style="text-align: left;">' +
                  '<li>Semua data yang ada akan diganti dengan data dari backup</li>' +
                  '<li>Tindakan ini tidak dapat dibatalkan</li>' +
                  '<li>Pastikan Anda sudah membuat backup terbaru</li>' +
                  '</ul>' +
                  '<p><strong>File:</strong> ' + filename + '</p>' +
                  '</div>',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ffc107',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, Restore!',
            cancelButtonText: 'Batal',
            reverseButtons: true,
            customClass: {
                popup: 'animated fadeInDown',
                title: 'swal2-title-modern',
                confirmButton: 'swal2-confirm-modern',
                cancelButton: 'swal2-cancel-modern'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                // Create form and submit
                const form = document.createElement('form');
                form.method = 'POST';

                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'restore_file';
                input.value = filepath;
                form.appendChild(input);

                document.body.appendChild(form);

                Swal.fire({
                    title: 'Restore Database...',
                    text: 'Mohon tunggu, proses restore sedang berjalan',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showConfirmButton: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                form.submit();
            }
        });
    }
    </script>

    <?php
    include_once('../footer.php');
    ?>

