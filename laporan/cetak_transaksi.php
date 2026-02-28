<?php

include"../config/config.php";

$id = $_GET['id'];
$sql = $koneksi->query("SELECT * FROM transaksi JOIN jenis_bayar ON transaksi.id_bayar = jenis_bayar.id_bayar WHERE id_transaksi = '$id'");

$data = $sql->fetch_assoc();
$tgl = $data['tgl'];
$harga = $data['harga'];

$content = '
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link rel="icon" type="image/png" sizes="16x16" href="files/assets/images/dwloket_icon.png">
	<title>Cetak Transaksi</title>
    
</head>

<body>';


$content .='
<h5 align="center">STRUK TRANSAKSI DW LOKET</h5>
<h6 align="right">'.date('d/m/Y H:i:s').'</h6>
<table>
<tr>
	<td style="width: 80%;">ID Transaksi</td>
	<td style="width: 10%;">:</td>
	<td style="width: 65%;">'.$data['id_transaksi'].'</td>
</tr>
<tr>
	<td style="width: 80%;">Tanggal Transaksi</td>
	<td style="width: 10%;">:</td>
	<td style="width: 65%;">'.date('d/m/Y', strtotime($tgl)).'</td>
</tr>
<tr>
	<td style="width: 80%;">ID Pelanggan</td>
	<td style="width: 10%;">:</td>
	<td style="width: 65%;">'.$data['idpel'].'</td>
</tr>
<tr>
	<td style="width: 80%; vertical-align: top;">Nama Pelanggan</td>
	<td style="width: 10%;">:</td>
	<td style="width: 65%;">'.$data['nama'].'</td>
</tr>
<tr>
	<td style="width: 80%; vertical-align: top;">Jenis Pembayaran</td>
	<td style="width: 10%;">:</td>
	<td style="width: 65%;">'.$data['jenis_bayar'].'</td>
</tr>
<tr>
	<td style="width: 80%;">Harga</td>
	<td style="width: 10%;">:</td>
	<td style="width: 65%;">Rp. '.number_format($data['harga'], 0, ",", ".").'
	</td>
</tr>
</table>
</div>

<h5 style="text-align:center">Terbilang: <i>'.terbilang($harga).' Rupiah</i></h5>

<p style="font-size: 12px; text-align: center;">Struk ini sebagai bukti transaksi di DW LOKET. Informasi
hubungi 082331838221.</p>

<h5 style="text-align:center">Terima Kasih Atas Kepercayaan Anda</h5>
					
</body>
';


require "../files/vendor/autoload.php";
$mpdf = new \Mpdf\Mpdf();
$mpdf = new \Mpdf\Mpdf(['orientation' => 'P', 'A4', 'EN']);
$mpdf->WriteHTML($content);
$mpdf->Output('cetak_transaksi.pdf', \Mpdf\Output\Destination::DOWNLOAD);

?>