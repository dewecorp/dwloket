<?php
$page_title = 'Detail OrderKuota';
include_once('../header.php');
include_once('../config/config.php');
require_once '../libs/orderkuota_api.php';
require_once '../libs/log_activity.php';

// Get transaksi detail
$transaksi_id = $_GET['id'] ?? 0;
$transaksi = null;
$status_detail = null;

if ($transaksi_id) {
    $transaksi_query = $koneksi->query("SELECT t.*, j.jenis_bayar
                                        FROM transaksi t
                                        LEFT JOIN tb_jenisbayar j ON t.id_bayar = j.id_bayar
                                        WHERE t.id_transaksi = " . (int)$transaksi_id . "
                                        AND t.ket LIKE '%OrderKuota%'");

    if ($transaksi_query && $transaksi_query->num_rows > 0) {
        $transaksi = $transaksi_query->fetch_assoc();

        // Extract ref_id
        preg_match('/Ref: ([A-Z0-9_]+)/', $transaksi['ket'], $matches);
        $ref_id = $matches[1] ?? '';

        // Extract product info
        preg_match('/OrderKuota: ([^-]+)/', $transaksi['ket'], $product_matches);
        $transaksi['product_name'] = trim($product_matches[1] ?? '');
        $transaksi['ref_id'] = $ref_id;

        // Cek status dari API jika ada ref_id
        if ($ref_id) {
            $api = new OrderKuotaAPI();
            $status_result = $api->checkStatus($ref_id);
            $status_detail = $api->formatResponse($status_result);
        }
    }
}

if (!$transaksi) {
    header('Location: ' . base_url('orderkuota/history.php'));
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Transaksi OrderKuota</title>
</head>
<body>
    <div class="page-breadcrumb">
        <div class="row">
            <div class="col-7 align-self-center">
                <h4 class="page-title text-truncate text-dark font-weight-medium mb-1">Detail Transaksi OrderKuota</h4>
                <div class="d-flex align-items-center">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb m-0 p-0">
                            <li class="breadcrumb-item"><a href="<?=base_url('home')?>" class="text-muted">Home</a></li>
                            <li class="breadcrumb-item"><a href="<?=base_url('orderkuota')?>" class="text-muted">OrderKuota</a></li>
                            <li class="breadcrumb-item"><a href="<?=base_url('orderkuota/history.php')?>" class="text-muted">History</a></li>
                            <li class="breadcrumb-item text-muted active" aria-current="page">Detail</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-8">
                <!-- Detail Transaksi -->
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">
                            <i class="fa fa-file-text"></i> Detail Transaksi
                        </h4>

                        <div class="row">
                            <div class="col-md-6">
                                <table class="table table-borderless">
                                    <tr>
                                        <th width="40%">ID Transaksi</th>
                                        <td>#<?=$transaksi['id_transaksi']?></td>
                                    </tr>
                                    <tr>
                                        <th>Tanggal</th>
                                        <td><?=date('d/m/Y H:i:s', strtotime($transaksi['tgl']))?></td>
                                    </tr>
                                    <tr>
                                        <th>Produk</th>
                                        <td><strong><?=htmlspecialchars($transaksi['product_name'] ?: 'N/A')?></strong></td>
                                    </tr>
                                    <tr>
                                        <th>Jenis Pembayaran</th>
                                        <td><?=htmlspecialchars($transaksi['jenis_bayar'] ?? 'N/A')?></td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <table class="table table-borderless">
                                    <tr>
                                        <th width="40%">Nama Pelanggan</th>
                                        <td><?=htmlspecialchars($transaksi['nama'] ?: '-')?></td>
                                    </tr>
                                    <tr>
                                        <th>ID Pelanggan</th>
                                        <td><code><?=htmlspecialchars($transaksi['idpel'])?></code></td>
                                    </tr>
                                    <tr>
                                        <th>Harga</th>
                                        <td><h4 class="text-success mb-0">Rp <?=number_format($transaksi['harga'], 0, ',', '.')?></h4></td>
                                    </tr>
                                    <tr>
                                        <th>Status</th>
                                        <td>
                                            <span class="badge badge-<?=$transaksi['status'] == 'Lunas' ? 'success' : 'warning'?> badge-pill">
                                                <?=$transaksi['status']?>
                                            </span>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        <hr>

                        <div class="row">
                            <div class="col-12">
                                <h5>Informasi OrderKuota</h5>
                                <table class="table table-borderless">
                                    <tr>
                                        <th width="30%">Reference ID</th>
                                        <td>
                                            <code><?=htmlspecialchars($transaksi['ref_id'] ?: 'N/A')?></code>
                                            <?php if ($transaksi['ref_id']): ?>
                                            <button class="btn btn-sm btn-outline-secondary ml-2" onclick="copyToClipboard('<?=$transaksi['ref_id']?>')">
                                                <i class="fa fa-copy"></i> Copy
                                            </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php
                                    // Extract token dari keterangan jika ada
                                    $token = '';
                                    if (preg_match('/Token:\s*([0-9]+)/i', $transaksi['ket'], $token_matches)) {
                                        $token = $token_matches[1];
                                    }
                                    ?>
                                    <?php if ($token): ?>
                                    <tr>
                                        <th>Kode Token PLN</th>
                                        <td>
                                            <div class="token-display" style="background: #fff; padding: 15px; border-radius: 5px; border: 2px solid #ffc107; margin-top: 5px;">
                                                <div class="token-code" style="font-size: 20px; font-weight: bold; letter-spacing: 2px; color: #ffc107; text-align: center; padding: 10px; background: #fff3cd; border-radius: 5px; font-family: 'Courier New', monospace;">
                                                    <?=htmlspecialchars($token)?>
                                                </div>
                                                <button class="btn btn-sm btn-warning mt-2" onclick="copyToken('<?=htmlspecialchars($token)?>')" style="width: 100%;">
                                                    <i class="fa fa-copy"></i> Copy Token
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                    <tr>
                                        <th>Keterangan</th>
                                        <td><?=htmlspecialchars($transaksi['ket'])?></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Status dari API -->
                <?php if ($status_detail): ?>
                <div class="card mt-3">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="fa fa-info-circle"></i> Status dari OrderKuota API
                        </h5>
                        <div class="alert alert-<?=$status_detail['success'] ? 'success' : 'warning'?>">
                            <?php if ($status_detail['success']): ?>
                                <strong>Status:</strong> <?=htmlspecialchars($status_detail['data']['status'] ?? 'Success')?>
                                <br><strong>Message:</strong> <?=htmlspecialchars($status_detail['message'] ?? '')?>
                                <?php if (isset($status_detail['data']['transaction_id'])): ?>
                                <br><strong>Transaction ID:</strong> <?=htmlspecialchars($status_detail['data']['transaction_id'])?>
                                <?php endif; ?>
                            <?php else: ?>
                                <strong>Error:</strong> <?=htmlspecialchars($status_detail['message'] ?? 'Unknown error')?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Aksi -->
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="fa fa-cog"></i> Aksi
                        </h5>
                        <div class="d-grid gap-2">
                            <?php if ($transaksi['ref_id']): ?>
                            <a href="<?=base_url('orderkuota/history.php?cek_status=1&ref_id=' . urlencode($transaksi['ref_id']))?>"
                               class="btn btn-info btn-block">
                                <i class="fa fa-search"></i> Cek Status
                            </a>
                            <?php endif; ?>

                            <?php if ($transaksi['status'] == 'Belum' && $transaksi['ref_id']): ?>
                            <a href="<?=base_url('orderkuota/history.php?retry=1&ref_id=' . urlencode($transaksi['ref_id']))?>"
                               class="btn btn-warning btn-block"
                               onclick="return swalConfirmRetry()">
                                <i class="fa fa-refresh"></i> Retry Payment
                            </a>
                            <?php endif; ?>

                            <button onclick="window.print()" class="btn btn-secondary btn-block">
                                <i class="fa fa-print"></i> Print
                            </button>

                            <a href="<?=base_url('orderkuota/history.php')?>" class="btn btn-outline-secondary btn-block">
                                <i class="fa fa-arrow-left"></i> Kembali ke History
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Info -->
                <div class="card mt-3">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="fa fa-info-circle"></i> Informasi
                        </h5>
                        <ul class="list-unstyled small">
                            <li><i class="fa fa-check text-success"></i> Transaksi tersimpan di database</li>
                            <li><i class="fa fa-check text-success"></i> Reference ID untuk tracking</li>
                            <li><i class="fa fa-check text-success"></i> Status dapat dicek real-time</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    function copyToClipboard(text) {
        navigator.clipboard.writeText(text).then(function() {
            Swal.fire({
                icon: 'success',
                title: 'Copied!',
                text: 'Reference ID telah disalin',
                timer: 2000,
                showConfirmButton: false
            });
        });
    }

    function copyToken(token) {
        navigator.clipboard.writeText(token).then(function() {
            Swal.fire({
                icon: 'success',
                title: 'Token Disalin!',
                text: 'Kode token telah disalin ke clipboard',
                timer: 2000,
                showConfirmButton: false
            });
        }).catch(function(err) {
            // Fallback untuk browser lama
            const textArea = document.createElement('textarea');
            textArea.value = token;
            textArea.style.position = 'fixed';
            textArea.style.opacity = '0';
            document.body.appendChild(textArea);
            textArea.select();
            try {
                document.execCommand('copy');
                Swal.fire({
                    icon: 'success',
                    title: 'Token Disalin!',
                    text: 'Kode token telah disalin ke clipboard',
                    timer: 2000,
                    showConfirmButton: false
                });
            } catch (err) {
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal Menyalin',
                    text: 'Silakan salin manual: ' + token
                });
            }
            document.body.removeChild(textArea);
        });
    }

    function swalConfirmRetry() {
        Swal.fire({
            title: 'Yakin Retry Payment?',
            text: 'Transaksi ini akan di-retry melalui OrderKuota',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#ffc107',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, Retry!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                return true;
            }
            return false;
        });
        return false;
    }
    </script>

    <?php
    include_once('../footer.php');
    ?>





