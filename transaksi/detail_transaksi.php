<?php
$page_title = 'Detail Transaksi';
include_once('../header.php');
include_once('../config/config.php');
$id = @$_GET['id'];
$sql = $koneksi->query("SELECT * FROM transaksi JOIN tb_jenisbayar ON transaksi.id_bayar = tb_jenisbayar.id_bayar WHERE id_transaksi ='$id'");
$data = $sql->fetch_assoc();
$status = ($data['status'] == 'Lunas')? "Lunas" : "Belum Bayar";
$tgl = $data['tgl'];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Transaksi</title>
    <style>
        .detail-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            overflow: hidden;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .detail-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        .detail-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px;
            border-radius: 15px 15px 0 0;
        }
        .detail-item {
            padding: 18px 25px;
            border-bottom: 1px solid #f0f0f0;
            transition: background 0.2s;
        }
        .detail-item:hover {
            background: #f8f9fa;
        }
        .detail-item:last-child {
            border-bottom: none;
        }
        .detail-label {
            font-weight: 600;
            color: #495057;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .detail-label i {
            margin-right: 10px;
            color: #667eea;
            width: 20px;
            font-size: 1rem;
        }
        .detail-value {
            color: #212529;
            font-size: 1rem;
            margin-left: 30px;
        }
        .status-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 25px;
            font-weight: 600;
            font-size: 0.9rem;
        }
        .status-lunas {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .status-belum {
            background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        .action-buttons {
            padding: 25px;
            background: linear-gradient(to bottom, #f8f9fa 0%, #ffffff 100%);
            border-top: 1px solid #e9ecef;
        }
        .info-box {
            background: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 15px;
            border-radius: 5px;
            margin-top: 5px;
            line-height: 1.6;
        }
        .copy-btn-detail {
            padding: 4px 10px;
            font-size: 0.75rem;
            margin-left: 10px;
            border-radius: 5px;
        }
        .quick-action-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 3px 15px rgba(0,0,0,0.08);
            transition: transform 0.2s;
        }
        .quick-action-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.12);
        }
        .info-list {
            list-style: none;
            padding: 0;
        }
        .info-list li {
            padding: 10px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        .info-list li:last-child {
            border-bottom: none;
        }
        .price-display {
            font-size: 1.8rem;
            font-weight: 700;
            color: #28a745;
            letter-spacing: 1px;
        }
    </style>
</head>
<body>
    <div class="page-breadcrumb">
        <div class="row">
            <div class="col-7 align-self-center">
                <h4 class="page-title text-truncate text-dark font-weight-medium mb-1">Detail Transaksi</h4>
                <div class="d-flex align-items-center">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb m-0 p-0">
                            <li class="breadcrumb-item"><a href="<?=base_url('home')?>" class="text-muted">Home</a></li>
                            <li class="breadcrumb-item"><a href="<?=base_url('transaksi/transaksi.php')?>" class="text-muted">Transaksi</a></li>
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
                <div class="card detail-card">
                    <div class="detail-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h4 class="mb-1">
                                    <i class="fa fa-file-text"></i> Detail Transaksi
                                </h4>
                                <small class="opacity-75">ID Transaksi: #<?=$data['id_transaksi']?></small>
                            </div>
                            <div>
                                <span class="status-badge <?=$status == 'Lunas' ? 'status-lunas' : 'status-belum'?>" style="background: rgba(255,255,255,0.2); color: white; border: 1px solid rgba(255,255,255,0.3);">
                                    <i class="fa fa-<?=$status == 'Lunas' ? 'check-circle' : 'clock'?>"></i> <?=$status?>
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="detail-item">
                            <div class="detail-label">
                                <i class="fa fa-calendar-alt"></i> Tanggal Transaksi
                            </div>
                            <div class="detail-value">
                                <strong><?=date('d F Y', strtotime($tgl))?></strong>
                                <small class="text-muted ml-2"><?=date('H:i:s', strtotime($tgl))?></small>
                            </div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">
                                <i class="fa fa-user"></i> Nama Pelanggan
                            </div>
                            <div class="detail-value">
                                <strong><?=htmlspecialchars($data['nama'] ?: '-')?></strong>
                            </div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">
                                <i class="fa fa-id-card"></i> ID Pelanggan
                            </div>
                            <div class="detail-value">
                                <code style="font-size: 1.1rem; background: #f8f9fa; padding: 8px 15px; border-radius: 8px; font-weight: 600; color: #495057;"><?=htmlspecialchars($data['idpel'])?></code>
                                <button class="btn btn-xs btn-outline-primary copy-btn-detail"
                                        onclick="copyIdPel('<?=htmlspecialchars($data['idpel'], ENT_QUOTES)?>', this)"
                                        data-toggle="tooltip"
                                        data-placement="top"
                                        title="Copy ID/PEL"
                                        id="copyBtnDetail">
                                    <i class="fa fa-copy"></i>
                                </button>
                                <span class="copy-feedback ml-2" id="copyFeedbackDetail" style="display: none; color: #28a745; font-size: 0.85rem; font-weight: 500;">
                                    <i class="fa fa-check"></i> Sudah Disalin
                                </span>
                            </div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">
                                <i class="fa fa-credit-card"></i> Jenis Pembayaran
                            </div>
                            <div class="detail-value">
                                <span class="badge badge-info" style="padding: 8px 15px; font-size: 0.9rem;">
                                    <?=htmlspecialchars($data['jenis_bayar'])?>
                                </span>
                            </div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">
                                <i class="fas fa-money"></i> Harga
                            </div>
                            <div class="detail-value">
                                <div class="price-display">
                                    Rp <?=number_format($data['harga'], 0, ",", ".")?>
                                </div>
                            </div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">
                                <i class="fa fa-info-circle"></i> Keterangan
                            </div>
                            <div class="detail-value">
                                <div class="info-box">
                                    <?=htmlspecialchars($data['ket'] ?: '-')?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="action-buttons">
                        <h6 class="mb-3">
                            <i class="fa fa-cog"></i> Aksi
                        </h6>
                        <div class="d-flex flex-wrap gap-2">
                            <a href="../cetak_transaksi.php?&id=<?=$data['id_transaksi'];?>" target="_blank"
                                class="btn btn-primary">
                                <i class="fa fa-print"></i> Cetak A4/F4
                            </a>
                            <a href="../cetak_kecil.php?&id=<?=$data['id_transaksi'];?>" target="_blank"
                                class="btn btn-info">
                                <i class="fa fa-print"></i> Cetak POS
                            </a>
                            <a href="<?=base_url('transaksi/transaksi.php')?>" class="btn btn-secondary">
                                <i class="fa fa-arrow-left"></i> Kembali
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <!-- Quick Actions -->
                <div class="card quick-action-card mb-3">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="fa fa-bolt text-warning"></i> Quick Actions
                        </h5>
                        <div class="d-grid gap-2">
                            <a href="<?=base_url('transaksi/edit.php?id=' . $data['id_transaksi'])?>" class="btn btn-warning">
                                <i class="fa fa-edit"></i> Edit Transaksi
                            </a>
                            <?php if (strpos($data['ket'], 'OrderKuota') !== false):
                                // Extract ref_id if exists
                                preg_match('/Ref: ([A-Z0-9_]+)/', $data['ket'], $ref_matches);
                                $ref_id = $ref_matches[1] ?? '';
                            ?>
                            <a href="<?=base_url('orderkuota/detail.php?id=' . $data['id_transaksi'])?>" class="btn btn-info">
                                <i class="fa fa-external-link"></i> Detail OrderKuota
                            </a>
                            <?php if ($ref_id): ?>
                            <a href="<?=base_url('orderkuota/print_receipt.php?ref_id=' . urlencode($ref_id))?>" target="_blank" class="btn btn-success">
                                <i class="fa fa-print"></i> Print Struk OrderKuota
                            </a>
                            <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Info Card -->
                <div class="card quick-action-card">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="fa fa-info-circle text-info"></i> Informasi
                        </h5>
                        <ul class="info-list">
                            <li>
                                <i class="fa fa-check-circle text-success"></i>
                                <strong>Transaksi tersimpan</strong>
                                <br><small class="text-muted">Data tersimpan di database</small>
                            </li>
                            <li>
                                <i class="fa fa-print text-primary"></i>
                                <strong>Dapat dicetak</strong>
                                <br><small class="text-muted">Cetak kapan saja yang diperlukan</small>
                            </li>
                            <li>
                                <i class="fa fa-edit text-warning"></i>
                                <strong>Dapat diedit</strong>
                                <br><small class="text-muted">Edit data jika diperlukan</small>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Function to copy ID/PEL to clipboard
        function copyIdPel(idpel, buttonElement) {
            var buttonId = buttonElement ? buttonElement.id : '';
            var feedbackElement = document.getElementById('copyFeedbackDetail');

            function doCopy() {
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(idpel).then(function() {
                        showCopySuccess(buttonElement, feedbackElement);
                    }).catch(function(err) {
                        fallbackCopy(idpel, buttonElement, feedbackElement);
                    });
                } else {
                    fallbackCopy(idpel, buttonElement, feedbackElement);
                }
            }
            doCopy();
        }

        function showCopySuccess(buttonElement, feedbackElement) {
            if (!buttonElement) return;

            var originalTitle = buttonElement.getAttribute('title') || 'Copy ID/PEL';

            if (typeof $ !== 'undefined' && $(buttonElement).data('bs.tooltip')) {
                $(buttonElement).tooltip('hide');
                $(buttonElement).tooltip('disable');
            }

            buttonElement.removeAttribute('data-toggle');
            buttonElement.removeAttribute('title');
            buttonElement.removeAttribute('data-placement');

            buttonElement.classList.remove('btn-outline-primary');
            buttonElement.classList.add('btn-success');
            var originalHTML = buttonElement.innerHTML;
            buttonElement.innerHTML = '<i class="fa fa-check"></i>';

            if (feedbackElement) {
                feedbackElement.style.display = 'inline';
            }

            setTimeout(function() {
                if (buttonElement) {
                    buttonElement.classList.remove('btn-success');
                    buttonElement.classList.add('btn-outline-primary');
                    buttonElement.innerHTML = originalHTML;
                    buttonElement.setAttribute('data-toggle', 'tooltip');
                    buttonElement.setAttribute('data-placement', 'top');
                    buttonElement.setAttribute('title', originalTitle);
                    if (typeof $ !== 'undefined') {
                        $(buttonElement).tooltip('dispose');
                        $(buttonElement).tooltip();
                    }
                }
                if (feedbackElement) {
                    feedbackElement.style.display = 'none';
                }
            }, 2000);
        }

        function fallbackCopy(idpel, buttonElement, feedbackElement) {
            const textArea = document.createElement('textarea');
            textArea.value = idpel;
            textArea.style.position = 'fixed';
            textArea.style.top = '0';
            textArea.style.left = '0';
            textArea.style.width = '2em';
            textArea.style.height = '2em';
            textArea.style.padding = '0';
            textArea.style.border = 'none';
            textArea.style.outline = 'none';
            textArea.style.boxShadow = 'none';
            textArea.style.background = 'transparent';
            textArea.style.opacity = '0';
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();

            try {
                var successful = document.execCommand('copy');
                if (successful) {
                    showCopySuccess(buttonElement, feedbackElement);
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal!',
                        text: 'Gagal menyalin. Silakan salin manual: ' + idpel,
                        confirmButtonColor: '#dc3545',
                        confirmButtonText: 'OK'
                    });
                }
            } catch (err) {
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal!',
                    text: 'Gagal menyalin. Silakan salin manual: ' + idpel,
                    confirmButtonColor: '#dc3545',
                    confirmButtonText: 'OK'
                });
            }
            document.body.removeChild(textArea);
        }

        $(document).ready(function() {
            $('[data-toggle="tooltip"]').tooltip();
        });
    </script>

    <?php
    include_once('../footer.php');
    ?>
</body>

</html>
