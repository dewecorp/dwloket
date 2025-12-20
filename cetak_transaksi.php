<?php
include_once('config/config.php');
setlocale(LC_ALL, 'id-ID', 'id_ID');
date_default_timezone_set('Asia/Jakarta');

$id = @$_GET['id'];
$sql = $koneksi->query("SELECT * FROM transaksi JOIN tb_jenisbayar ON transaksi.id_bayar = tb_jenisbayar.id_bayar WHERE id_transaksi = '$id'");
$data = $sql->fetch_assoc();
$tgl = $data['tgl'];
$harga = $data['harga'];?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>Struk Transaksi</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <link rel="icon" type="image/png" sizes="16x16" href="files/assets/images/dwloket_icon.png">
    <link href="files/dist/css/style.min.css" rel="stylesheet">
    <style>
        * {
            color: #000 !important;
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Arial', 'Helvetica', sans-serif;
            margin: 0;
            padding: 0;
            font-size: 13px;
        }
        .receipt-container {
            max-width: 100%;
            margin: 0 auto;
            background: #fff;
            padding: 12px 18px;
            min-height: 82.5mm;
            max-height: 82.5mm;
            page-break-inside: avoid;
            border-bottom: 1px dashed #ccc;
        }
        .receipt-container:last-child {
            border-bottom: none;
        }
        .receipt-header {
            text-align: center;
            border-bottom: 1px solid #000;
            padding-bottom: 10px;
            margin-bottom: 12px;
        }
        .receipt-title {
            font-size: 20px;
            font-weight: bold;
            letter-spacing: 1px;
            margin: 0 0 5px 0;
            text-transform: uppercase;
        }
        .receipt-date {
            font-size: 12px;
            color: #000;
            margin: 0;
        }
        .receipt-content {
            margin-bottom: 10px;
        }
        .detail-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
            font-size: 13px;
        }
        .detail-table td {
            padding: 6px 0;
            font-size: 13px;
            border-bottom: 1px solid #e0e0e0;
            line-height: 1.5;
        }
        .detail-table td:first-child {
            width: 35%;
            font-weight: 600;
            color: #000;
        }
        .detail-table td:nth-child(2) {
            width: 5%;
            text-align: center;
            color: #000;
        }
        .detail-table td:last-child {
            width: 60%;
            color: #000;
        }
        .detail-table tr:last-child td {
            border-bottom: none;
        }
        .price-highlight {
            font-size: 15px;
            font-weight: bold;
            color: #000;
        }
        .terbilang-section {
            text-align: center;
            margin: 10px 0;
            padding: 10px;
            background: #f8f9fa;
            border: 1px solid #e0e0e0;
            font-size: 11px;
        }
        .terbilang-section h5 {
            margin: 0 0 4px 0;
            font-size: 12px;
            font-weight: 600;
        }
        .terbilang-section i {
            font-size: 11px;
            font-style: italic;
        }
        .info-message {
            text-align: center;
            font-size: 10px;
            color: #000;
            margin: 8px 0;
            line-height: 1.5;
        }
        .thank-you {
            text-align: center;
            font-size: 13px;
            font-weight: 600;
            margin: 8px 0;
            color: #000;
        }
        .receipt-footer {
            margin-top: 10px;
            padding-top: 8px;
            border-top: 1px dashed #000;
            text-align: center;
        }
        .receipt-footer p {
            font-size: 10px;
            font-style: italic;
            color: #000;
            margin: 0;
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
            }
            .receipt-container {
                padding: 12px 18px;
                min-height: 82.5mm;
                max-height: 82.5mm;
                page-break-inside: avoid;
                border-bottom: 1px dashed #ccc;
            }
            .receipt-container:last-child {
                border-bottom: none;
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
            <h1 class="receipt-title">STRUK TRANSAKSI</h1>
            <p class="receipt-date"><?=date('d F Y, H:i:s');?></p>
        </div>

        <div class="receipt-content">
            <table class="detail-table">
                <tr>
                    <td>ID Transaksi</td>
                    <td>:</td>
                    <td><strong><?=$data['id_transaksi'];?></strong></td>
                </tr>
                <tr>
                    <td>Tanggal Transaksi</td>
                    <td>:</td>
                    <td><?=date('d F Y', strtotime($tgl));?></td>
                </tr>
                <tr>
                    <td>ID Pelanggan</td>
                    <td>:</td>
                    <td><strong><?=$data['idpel'];?></strong></td>
                </tr>
                <tr>
                    <td>Nama Pelanggan</td>
                    <td>:</td>
                    <td><?=htmlspecialchars($data['nama'] ?: '-');?></td>
                </tr>
                <tr>
                    <td>Jenis Pembayaran</td>
                    <td>:</td>
                    <td><?=htmlspecialchars($data['jenis_bayar']);?></td>
                </tr>
                <tr>
                    <td>Harga</td>
                    <td>:</td>
                    <td class="price-highlight">Rp <?=number_format($data['harga'], 0, ",", ".");?></td>
                </tr>
                <?php if (!empty($data['ket'])): ?>
                <tr>
                    <td style="vertical-align: top;">Keterangan</td>
                    <td style="vertical-align: top;">:</td>
                    <td><?=htmlspecialchars($data['ket']);?></td>
                </tr>
                <?php endif; ?>
            </table>

            <div class="terbilang-section">
                <h5>Terbilang:</h5>
                <i><?=terbilang($harga);?> Rupiah</i>
            </div>

            <div class="info-message">
                Struk ini sebagai bukti transaksi di DW LOKET.<br>
                Informasi hubungi 082331838221.
            </div>

            <div class="thank-you">
                Terima Kasih Atas Kepercayaan Anda
            </div>
        </div>

        <div class="receipt-footer">
            <p>Supported by <strong>OrderKuota</strong></p>
        </div>
    </div>

</body>

</html>
<script>
window.print();
</script>
