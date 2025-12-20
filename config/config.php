<?php
date_default_timezone_set('Asia/Jakarta');
session_start();
include_once "koneksi.php";

function base_url($url = null) {
	// Deteksi base URL secara dinamis
	$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
	$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
	$script_name = $_SERVER['SCRIPT_NAME'] ?? '';
	$path = dirname(dirname($script_name));

	// Jika path adalah root, gunakan host saja
	if ($path == '/' || $path == '\\') {
		$base_url = $protocol . $host;
	} else {
		$base_url = $protocol . $host . $path;
	}

	// Normalisasi path (hapus trailing slash)
	$base_url = rtrim($base_url, '/');

	if($url != null) {
		$url = ltrim($url, '/');
		return $base_url . "/" . $url;
	} else {
		return $base_url;
	}
}

function tgl_indo($tgl) {
$tanggal = substr($tgl, 8, 2);
$bulan = substr($tgl, 5, 2);
$tahun = substr($tgl, 0, 4);
return $tanggal."/".$bulan."/".$tahun;
}

function format_hari_tanggal($waktu)
{
$hari_array = array(
'Minggu',
'Senin',
'Selasa',
'Rabu',
'Kamis',
'Jumat',
'Sabtu'
);
$hr = date('w', strtotime($waktu));
$hari = $hari_array[$hr];
$tanggal = date('j', strtotime($waktu));
$bulan_array = array(
1 => 'Januari',
2 => 'Februari',
3 => 'Maret',
4 => 'April',
5 => 'Mei',
6 => 'Juni',
7 => 'Juli',
8 => 'Agustus',
9 => 'September',
10 => 'Oktober',
11 => 'November',
12 => 'Desember',
);
$bl = date('n', strtotime($waktu));
$bulan = $bulan_array[$bl];
$tahun = date('Y', strtotime($waktu));
$jam = date( 'H:i:s', strtotime($waktu));

//untuk menampilkan hari, tanggal bulan tahun jam
//return "$hari, $tanggal $bulan $tahun $jam";
//untuk menampilkan hari, tanggal bulan tahun
return "$hari, $tanggal $bulan $tahun";
}

function penyebut($nilai) {
		$nilai = abs($nilai);
		$huruf = array("", "Satu", "Dua", "Tiga", "Empat", "Lima", "Enam", "Tujuh", "Delapan", "Sembilan", "Sepuluh", "Sebelas");
		$temp = "";
		if ($nilai < 12) {
			$temp = " ". $huruf[$nilai];
		} else if ($nilai <20) {
			$temp = penyebut($nilai - 10). " Belas";
		} else if ($nilai < 100) {
			$temp = penyebut($nilai/10)." Puluh". penyebut($nilai % 10);
		} else if ($nilai < 200) {
			$temp = " Seratus" . penyebut($nilai - 100);
		} else if ($nilai < 1000) {
			$temp = penyebut($nilai/100) . " Ratus" . penyebut($nilai % 100);
		} else if ($nilai < 2000) {
			$temp = " Seribu" . penyebut($nilai - 1000);
		} else if ($nilai < 1000000) {
			$temp = penyebut($nilai/1000) . " Ribu" . penyebut($nilai % 1000);
		} else if ($nilai < 1000000000) {
			$temp = penyebut($nilai/1000000) . " Juta" . penyebut($nilai % 1000000);
		} else if ($nilai < 1000000000000) {
			$temp = penyebut($nilai/1000000000) . " Milyar" . penyebut(fmod($nilai,1000000000));
		} else if ($nilai < 1000000000000000) {
			$temp = penyebut($nilai/1000000000000) . " Trilyun" . penyebut(fmod($nilai,1000000000000));
		}
		return $temp;
	}

	function terbilang($nilai) {
		if($nilai<0) {
			$hasil = "minus ". trim(penyebut($nilai));
		} else {
			$hasil = trim(penyebut($nilai));
				}
		return $hasil;
	}

/**
 * Get page title berdasarkan halaman yang aktif
 * @return string
 */
function get_page_title() {
	$script_name = $_SERVER['SCRIPT_NAME'] ?? '';
	$request_uri = $_SERVER['REQUEST_URI'] ?? '';
	$base_title = 'DW LOKET JEPARA';
	$year = date('Y');

	// Deteksi halaman berdasarkan script name atau request URI
	$page_title = '';

	// Dashboard
	if (strpos($script_name, 'home/index.php') !== false ||
		strpos($request_uri, '/home') !== false ||
		$request_uri == '/' ||
		$request_uri == '/home' ||
		$request_uri == '/home/') {
		$page_title = 'Dashboard';
	}
	// Transaksi
	elseif (strpos($script_name, 'transaksi/transaksi.php') !== false || strpos($request_uri, '/transaksi/transaksi') !== false) {
		$page_title = 'Transaksi';
	}
	elseif (strpos($script_name, 'transaksi/tambah.php') !== false || strpos($request_uri, '/transaksi/tambah') !== false) {
		$page_title = 'Tambah Transaksi';
	}
	elseif (strpos($script_name, 'transaksi/edit.php') !== false || strpos($request_uri, '/transaksi/edit') !== false) {
		$page_title = 'Edit Transaksi';
	}
	elseif (strpos($script_name, 'transaksi/detail') !== false || strpos($request_uri, '/transaksi/detail') !== false) {
		$page_title = 'Detail Transaksi';
	}
	elseif (strpos($script_name, 'transaksi/cari') !== false || strpos($request_uri, '/transaksi/cari') !== false) {
		$page_title = 'Filter Transaksi';
	}
	elseif (strpos($script_name, 'transaksi') !== false || strpos($request_uri, '/transaksi') !== false) {
		$page_title = 'Transaksi';
	}
	// Pelanggan
	elseif (strpos($script_name, 'pelanggan') !== false || strpos($request_uri, '/pelanggan') !== false) {
		$page_title = 'Pelanggan';
	}
	// User
	elseif (strpos($script_name, 'user') !== false || strpos($request_uri, '/user') !== false) {
		$page_title = 'User';
	}
	// Saldo
	elseif (strpos($script_name, 'saldo') !== false || strpos($request_uri, '/saldo') !== false) {
		$page_title = 'Saldo';
	}
	// Jenis Bayar / Produk
	elseif (strpos($script_name, 'jenisbayar/jenis_bayar.php') !== false || strpos($request_uri, '/jenisbayar/jenis_bayar') !== false) {
		$page_title = 'Produk & Harga';
	}
	elseif (strpos($script_name, 'jenisbayar/tambah_produk.php') !== false || strpos($request_uri, '/jenisbayar/tambah_produk') !== false) {
		$page_title = 'Tambah Produk';
	}
	elseif (strpos($script_name, 'jenisbayar/import') !== false || strpos($request_uri, '/jenisbayar/import') !== false) {
		$page_title = 'Import Produk';
	}
	elseif (strpos($script_name, 'jenisbayar') !== false || strpos($request_uri, '/jenisbayar') !== false) {
		$page_title = 'Jenis Pembayaran';
	}
	// OrderKuota
	elseif (strpos($script_name, 'orderkuota/deposit_history.php') !== false || strpos($request_uri, '/orderkuota/deposit_history') !== false) {
		$page_title = 'History Deposit OrderKuota';
	}
	elseif (strpos($script_name, 'orderkuota/deposit.php') !== false || strpos($request_uri, '/orderkuota/deposit') !== false) {
		$page_title = 'Tambah Deposit OrderKuota';
	}
	elseif (strpos($script_name, 'orderkuota/detail.php') !== false || strpos($request_uri, '/orderkuota/detail') !== false) {
		$page_title = 'Detail Transaksi OrderKuota';
	}
	elseif (strpos($script_name, 'orderkuota/history.php') !== false || strpos($request_uri, '/orderkuota/history') !== false) {
		$page_title = 'History Transaksi OrderKuota';
	}
	elseif (strpos($script_name, 'orderkuota/produk.php') !== false || strpos($request_uri, '/orderkuota/produk') !== false) {
		$page_title = 'Daftar Produk OrderKuota';
	}
	elseif (strpos($script_name, 'orderkuota/index.php') !== false || (strpos($request_uri, '/orderkuota') !== false && strpos($request_uri, '/orderkuota/') === false)) {
		$page_title = 'OrderKuota';
	}
	elseif (strpos($script_name, 'orderkuota') !== false || strpos($request_uri, '/orderkuota') !== false) {
		$page_title = 'OrderKuota';
	}
	// Admin
	elseif (strpos($script_name, 'admin/backup') !== false || strpos($request_uri, '/admin/backup') !== false) {
		$page_title = 'Backup Database';
	}
	elseif (strpos($script_name, 'admin') !== false || strpos($request_uri, '/admin') !== false) {
		$page_title = 'Admin';
	}
	// Laporan
	elseif (strpos($script_name, 'laporan/rekap') !== false || strpos($request_uri, '/laporan/rekap') !== false) {
		$page_title = 'Rekap Transaksi';
	}
	elseif (strpos($script_name, 'laporan') !== false || strpos($request_uri, '/laporan') !== false) {
		$page_title = 'Laporan';
	}
	// User Profil
	elseif (strpos($script_name, 'user/profil') !== false || strpos($request_uri, '/user/profil') !== false) {
		$page_title = 'Profil User';
	}
	// Saldo Total
	elseif (strpos($script_name, 'saldo/total') !== false || strpos($request_uri, '/saldo/total') !== false) {
		$page_title = 'Total Saldo';
	}
	// Admin
	elseif (strpos($script_name, 'admin/orderkuota_config') !== false || strpos($request_uri, '/admin/orderkuota_config') !== false) {
		$page_title = 'Konfigurasi OrderKuota';
	}
	// Default
	else {
		$page_title = 'Dashboard';
	}

	return $page_title . ' - ' . $base_title . ' ' . $year;
}
?>
