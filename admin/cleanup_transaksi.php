<?php
$page_title = 'Cleanup Transaksi Lama';
include_once('../header.php');
require_once '../libs/transaksi_cleanup.php';
require_once '../libs/log_activity.php';

// Handle manual cleanup
$cleanup_result = null;
if (isset($_POST['cleanup']) && $_POST['cleanup'] == 'manual') {
    $dry_run = isset($_POST['dry_run']) && $_POST['dry_run'] == '1';
    $cleanup_result = cleanup_old_transactions($koneksi, $dry_run);

    if (!$dry_run && $cleanup_result['success'] && $cleanup_result['deleted_count'] > 0) {
        @log_activity('delete', 'transaksi', 'Manual cleanup: ' . $cleanup_result['message']);
    }
}

// Get statistics
$stats = get_old_transactions_stats($koneksi);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?=$page_title?></title>
    <script src="<?=base_url()?>/files/dist/js/sweetalert2.all.min.js"></script>
</head>
<body>
    <div class="page-breadcrumb">
        <div class="row">
            <div class="col-7 align-self-center">
                <h4 class="page-title text-truncate text-dark font-weight-medium mb-1"><?=$page_title?></h4>
                <div class="d-flex align-items-center">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb m-0 p-0">
                            <li class="breadcrumb-item"><a href="<?=base_url('home')?>" class="text-muted">Home</a></li>
                            <li class="breadcrumb-item"><a href="<?=base_url('admin/backup.php')?>" class="text-muted">Admin</a></li>
                            <li class="breadcrumb-item text-muted active" aria-current="page"><?=$page_title?></li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="modern-card">
                    <div class="modern-card-header">
                        <h4>
                            <i class="fa fa-trash-alt"></i> Cleanup Transaksi Lama
                        </h4>
                    </div>
                    <div class="modern-card-body">
                        <?php if ($cleanup_result): ?>
                            <div class="alert alert-<?=$cleanup_result['success'] ? 'success' : 'danger'?> alert-dismissible fade show" role="alert">
                                <strong><?=$cleanup_result['success'] ? 'Berhasil!' : 'Error!'?></strong> <?=$cleanup_result['message']?>
                                <?php if ($cleanup_result['deleted_count'] > 0): ?>
                                    <br><small>Jumlah transaksi yang dihapus: <strong><?=$cleanup_result['deleted_count']?></strong></small>
                                <?php endif; ?>
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                        <?php endif; ?>

                        <div class="alert alert-info">
                            <h5><i class="fa fa-info-circle"></i> Informasi</h5>
                            <p>Sistem akan menghapus transaksi yang <strong>lebih dari 1 tahun</strong> secara otomatis untuk menghemat ruang database.</p>
                            <ul>
                                <li>Transaksi yang dihapus adalah transaksi dengan tanggal lebih dari 1 tahun dari sekarang</li>
                                <li>Proses ini tidak dapat dibatalkan setelah dijalankan</li>
                                <li>Disarankan untuk melakukan backup database sebelum menjalankan cleanup</li>
                                <li>Cleanup otomatis akan berjalan maksimal 1 kali per hari</li>
                            </ul>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="card border-left-primary shadow-sm">
                                    <div class="card-body">
                                        <h5 class="card-title"><i class="fa fa-chart-bar"></i> Statistik Transaksi Lama</h5>
                                        <div class="mt-3">
                                            <p class="mb-2"><strong>Total Transaksi Lama:</strong> <span class="badge badge-primary badge-lg"><?=$stats['total']?></span></p>
                                            <?php if ($stats['oldest_date']): ?>
                                                <p class="mb-2"><strong>Tanggal Tertua:</strong> <?=date('d/m/Y', strtotime($stats['oldest_date']))?></p>
                                            <?php endif; ?>
                                            <?php if ($stats['newest_old_date']): ?>
                                                <p class="mb-0"><strong>Tanggal Terbaru (Lama):</strong> <?=date('d/m/Y', strtotime($stats['newest_old_date']))?></p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card border-left-warning shadow-sm">
                                    <div class="card-body">
                                        <h5 class="card-title"><i class="fa fa-cog"></i> Aksi</h5>
                                        <form method="POST" class="mt-3" onsubmit="return confirmCleanup(event)">
                                            <div class="form-check mb-3">
                                                <input class="form-check-input" type="checkbox" name="dry_run" value="1" id="dryRun">
                                                <label class="form-check-label" for="dryRun">
                                                    Dry Run (hanya hitung, tidak menghapus)
                                                </label>
                                            </div>
                                            <button type="submit" name="cleanup" value="manual" class="btn btn-danger">
                                                <i class="fa fa-trash-alt"></i> Jalankan Cleanup Manual
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-warning">
                            <h5><i class="fa fa-exclamation-triangle"></i> Peringatan</h5>
                            <p>Pastikan Anda sudah melakukan backup database sebelum menjalankan cleanup. Data yang dihapus tidak dapat dikembalikan.</p>
                            <a href="<?=base_url('admin/backup.php')?>" class="btn btn-sm btn-primary">
                                <i class="fa fa-download"></i> Buat Backup Sekarang
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    function confirmCleanup(event) {
        const dryRun = document.getElementById('dryRun').checked;
        const message = dryRun
            ? 'Apakah Anda yakin ingin menjalankan Dry Run? (hanya menghitung, tidak menghapus)'
            : 'PERINGATAN: Proses ini akan menghapus transaksi yang lebih dari 1 tahun secara permanen!\n\nApakah Anda yakin ingin melanjutkan?';

        if (!confirm(message)) {
            event.preventDefault();
            return false;
        }

        if (!dryRun) {
            const confirm2 = confirm('Apakah Anda sudah melakukan backup database? Data yang dihapus tidak dapat dikembalikan!');
            if (!confirm2) {
                event.preventDefault();
                return false;
            }
        }

        return true;
    }
    </script>
</body>
</html>

