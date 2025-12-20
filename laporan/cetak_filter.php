<?php

include"../config/config.php";
$content = '
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Filter Transaksi</title>
</head>
<style type="text/css">
.table{padding: 40px; border-collapse: collapse;}
.table th{padding: 8px 5px; background-color: #cccccc;}
.table td{padding: 8px 5px;}
</style>

<body>
	<table>
		<tr>
			<td>
				<img src="../files/assets/images/dwloket_icon.png" width="100">
			</td>
			<td>
				<h2>Rekap Transaksi</h2>
				<h2>DW LOKET JEPARA '.date('Y').'</h2>
			</td>	
		</tr>
	</table><br>

	
	<table border="1" class="table">
		<tr>
			<th style="padding: 8px 5px; width: 15px">No.</th>
			<th style="padding: 8px 5px;">Tanggal</th>
			<th style="padding: 8px 5px;">ID Pelanggan</th>
			<th style="padding: 8px 5px;">Nama Pelanggan</th>
			<th style="padding: 8px 5px;">Jenis Pembayaran</th>
			<th style="padding: 8px 5px; width: 10px">Harga</th>
			<th style="padding: 8px 5px;">Status</th>
		</tr>';
        if(isset($_GET['cari'])){
            //menangkap nilai form
            $tgl1 = $_GET['tgl1'];
            $tgl2 = $_GET['tgl2'];

            $content .= ';
        
            <i><b>Informasi : </b> Hasil pencarian data berdasarkan periode Tanggal
									<b>'.$_GET['tgl1'].'</b> s/d <b>'.$_GET['tgl2'].'</b></i>';
		
		$no = 1;
        $sql = $koneksi->query("SELECT * FROM transaksi JOIN jenis_bayar ON transaksi.id_bayar = tb_jenisbayar.id_bayar");
        if(isset($tgl1) AND isset($tgl2)) {
            $sql.= $koneksi->query("WHERE tgl BETWEEN tgl1 AND tgl2");
        }
		while($data = $sql->fetch_assoc()) {
		$tgl = $data['tgl'];
		$status = ($data['status'] == 'Lunas')? 'Lunas' : 'Belum Bayar';
		$filename = "laporan_transaksi-".date('d-m-Y').".pdf";
		
		$content .='
		
		<tr>
			<td>'.$no++.'.'.'</td>
			<td>'.$data['tgl'].'</td>
			<td>'.$data['idpel'].'</td>
			<td>'.$data['nama'].'</td>
			<td>'.$data['jenis_bayar'].'</td>
			<td>'.$data['harga'].'</td>
			<td>'.$status.'</td>
			
		</tr>
		'; }
        }
		
	$content .='
		
	</table>
	</body>
	</html>
';
require "../files/vendor/autoload.php";
$mpdf = new \Mpdf\Mpdf();
$mpdf = new \Mpdf\Mpdf(['orientation' => 'P', 'A4', 'EN']);
$mpdf->WriteHTML($content);
$mpdf->Output('cetak_filter.pdf', \Mpdf\Output\Destination::DOWNLOAD);?>