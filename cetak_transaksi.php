<?php
include_once('config/config.php');
setlocale(LC_ALL, 'id-ID', 'id_ID');
date_default_timezone_set('Asia/Jakarta');

$id = @$_GET['id'];
$sql = $koneksi->query("SELECT transaksi.*, tb_jenisbayar.jenis_bayar FROM transaksi LEFT JOIN tb_jenisbayar ON transaksi.id_bayar = tb_jenisbayar.id_bayar WHERE id_transaksi = '$id'");
$data = $sql->fetch_assoc();
$tgl = $data['tgl'];
$harga = $data['harga'];
// Ambil produk dari kolom produk, jika kosong ambil dari jenis_bayar sebagai fallback
$produk_display = !empty($data['produk']) ? $data['produk'] : (!empty($data['jenis_bayar']) ? $data['jenis_bayar'] : '-');?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>Struk Transaksi</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <link rel="icon" type="image/png" sizes="16x16" href="files/assets/images/dwloket_icon.png">
    <style>
        * {
            color: #000 !important;
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Courier New', 'Courier', 'Lucida Console', monospace;
            margin: 0;
            padding: 0;
            font-size: 14px;
        }
        .receipt-container {
            max-width: 100%;
            margin: 0 auto;
            background: #fff;
            padding: 8px 12px;
            page-break-inside: avoid;
        }
        .receipt-header {
            margin-bottom: 8px;
        }
        .receipt-header-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 4px;
        }
        .receipt-logo {
            font-weight: bold;
            font-size: 14px;
        }
        .receipt-title {
            font-size: 18px;
            font-weight: bold;
            text-align: center;
            text-transform: uppercase;
            margin: 4px 0;
            letter-spacing: 1px;
        }
        .receipt-date {
            font-size: 12px;
            text-align: right;
            margin: 0;
        }
        .receipt-content {
            margin-bottom: 8px;
        }
        .data-container {
            margin-bottom: 6px;
        }
        .data-row {
            margin-bottom: 3px;
            font-size: 13px;
            line-height: 1.4;
        }
        .data-label {
            font-weight: 600;
            display: inline-block;
            width: 35%;
        }
        .data-separator {
            display: inline-block;
            margin: 0 4px;
        }
        .data-value {
            display: inline-block;
            text-align: left;
        }
        .total-section {
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            padding: 6px 0;
            margin: 8px 0;
        }
        .total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 4px;
            font-size: 14px;
        }
        .total-label {
            font-weight: 600;
        }
        .total-value {
            font-weight: bold;
            font-size: 16px;
        }
        .serial-section {
            text-align: center;
            margin: 8px 0;
            padding: 6px;
            font-size: 11px;
            word-break: break-all;
        }
        .receipt-footer {
            text-align: center;
            margin-top: 8px;
            padding-top: 6px;
            border-top: 1px dashed #000;
            font-size: 12px;
            line-height: 1.5;
        }
        .receipt-footer p {
            margin: 2px 0;
        }
        @media print {
            * {
                color: #000 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            body {
                color: #000 !important;
                padding: 0;
                margin: 0;
                font-family: 'Courier New', 'Courier', 'Lucida Console', monospace !important;
                font-size: 14px !important;
            }
            .receipt-container {
                padding: 8px 12px;
            }
            @page {
                size: F4;
                margin: 5mm;
            }
        }
    </style>
</head>

<body>
    <div class="receipt-container">
        <div class="receipt-header">
            <div class="receipt-header-top">
                <div class="receipt-logo">DW LOKET</div>
                <div class="receipt-date"><?=date('d/m/Y H:i:s');?></div>
            </div>
            <h1 class="receipt-title">STRUK TRANSAKSI</h1>
        </div>

        <div class="receipt-content">
            <div class="data-container">
                <div class="data-row">
                    <span class="data-label">ID Transaksi</span>
                    <span class="data-separator">:</span>
                    <span class="data-value"><strong><?=$data['id_transaksi'];?></strong></span>
                </div>
                <div class="data-row">
                    <span class="data-label">ID Pelanggan</span>
                    <span class="data-separator">:</span>
                    <span class="data-value"><strong><?=$data['idpel'];?></strong></span>
                </div>
                <div class="data-row">
                    <span class="data-label">Nama</span>
                    <span class="data-separator">:</span>
                    <span class="data-value"><?=htmlspecialchars($data['nama'] ?: '-');?></span>
                </div>
                <div class="data-row">
                    <span class="data-label">Produk</span>
                    <span class="data-separator">:</span>
                    <span class="data-value"><?=htmlspecialchars($produk_display);?></span>
                </div>
                <?php if (!empty($data['ket'])): ?>
                <div class="data-row">
                    <span class="data-label">Keterangan</span>
                    <span class="data-separator">:</span>
                    <span class="data-value"><?=htmlspecialchars($data['ket']);?></span>
                </div>
                <?php endif; ?>
            </div>

            <div class="total-section">
                <div class="total-row">
                    <span class="total-label">TOTAL BAYAR</span>
                    <span class="total-value">Rp <?=number_format($data['harga'], 0, ",", ".");?></span>
                </div>
            </div>
        </div>

        <div class="receipt-footer">
            <p>Terima kasih atas pembayarannya</p>
            <p>Informasi Hubungi Call Center atau Agen Terdekat</p>
            <p><strong>Supported by OrderKuota</strong></p>
        </div>
    </div>

</body>

</html>
<script>
window.print();
</script>
