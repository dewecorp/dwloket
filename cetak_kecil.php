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
    <style>
        * {
            color: #000 !important;
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Arial', 'Helvetica', sans-serif;
            font-size: 12px;
            padding: 10px;
            background: #fff;
        }
        .receipt-container {
            max-width: 80mm;
            margin: 0 auto;
            background: #fff;
            padding: 15px 10px;
        }
        .receipt-header {
            text-align: center;
            border-bottom: 1px solid #000;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        .receipt-title {
            font-size: 16px;
            font-weight: bold;
            letter-spacing: 1px;
            margin: 0 0 5px 0;
            text-transform: uppercase;
        }
        .receipt-date {
            font-size: 10px;
            color: #000;
            margin: 0;
        }
        .detail-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            font-size: 11px;
        }
        .detail-table td {
            padding: 6px 0;
            border-bottom: 1px dotted #ccc;
        }
        .detail-table td:first-child {
            width: 35%;
            font-weight: 600;
        }
        .detail-table td:nth-child(2) {
            width: 5%;
            text-align: center;
        }
        .detail-table td:last-child {
            width: 60%;
        }
        .detail-table tr:last-child td {
            border-bottom: none;
        }
        .price-highlight {
            font-size: 13px;
            font-weight: bold;
        }
        .terbilang-section {
            text-align: center;
            margin: 15px 0;
            padding: 10px;
            background: #f8f9fa;
            border: 1px solid #e0e0e0;
            font-size: 10px;
        }
        .terbilang-section h4 {
            margin: 0 0 5px 0;
            font-size: 11px;
            font-weight: 600;
        }
        .terbilang-section i {
            font-size: 10px;
            font-style: italic;
        }
        .thank-you {
            text-align: center;
            font-size: 11px;
            font-weight: 600;
            margin: 15px 0;
        }
        .receipt-footer {
            margin-top: 20px;
            padding-top: 10px;
            border-top: 1px dashed #000;
            text-align: center;
        }
        .receipt-footer p {
            font-size: 9px;
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
                padding: 0;
            }
            .receipt-container {
                padding: 10px 5px;
            }
            @page {
                size: 80mm auto;
                margin: 0;
            }
        }
    </style>
</head>

<body>
    <div class="receipt-container">
        <div class="receipt-header">
            <h1 class="receipt-title">STRUK TRANSAKSI</h1>
            <p class="receipt-date"><?=date('d/m/Y H:i:s');?></p>
        </div>

        <table class="detail-table">
            <tr>
                <td>ID Trx</td>
                <td>:</td>
                <td><strong><?=$data['id_transaksi'];?></strong></td>
            </tr>
            <tr>
                <td>Tanggal</td>
                <td>:</td>
                <td><?=date('d/m/Y', strtotime($tgl));?></td>
            </tr>
            <tr>
                <td>ID Pel</td>
                <td>:</td>
                <td><strong><?=$data['idpel'];?></strong></td>
            </tr>
            <tr>
                <td>Nama</td>
                <td>:</td>
                <td><?=htmlspecialchars($data['nama'] ?: '-');?></td>
            </tr>
            <tr>
                <td>Jenis Bayar</td>
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
            <h4>Terbilang:</h4>
            <i><?=terbilang($harga);?> Rupiah</i>
        </div>

        <div class="thank-you">
            Terima Kasih Atas Kepercayaan Anda
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
