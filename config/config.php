<?php
date_default_timezone_set('Asia/Jakarta');

// Konfigurasi session timeout - perpanjang waktu idle menjadi 2 jam (7200 detik)
// Default PHP session timeout adalah 24 menit, ini diperpanjang untuk mengurangi masalah setelah idle
ini_set('session.gc_maxlifetime', 7200); // 2 jam dalam detik
ini_set('session.cookie_lifetime', 7200); // Cookie session juga 2 jam

// Pengaturan keamanan session untuk mencegah session hijacking dan fixation
ini_set('session.cookie_httponly', 1); // Mencegah akses cookie melalui JavaScript
ini_set('session.cookie_secure', 0); // Set ke 1 jika menggunakan HTTPS
ini_set('session.use_strict_mode', 1); // Mencegah session fixation
ini_set('session.cookie_samesite', 'Strict'); // CSRF protection

// Pastikan session_start hanya dipanggil sekali
if (session_status() === PHP_SESSION_NONE) {
	session_start();
}

include_once "koneksi.php";

function base_url($url = null) {
$base_url = "http://localhost/dwloket_ok";
if($url != null) {
return $base_url."/".$url;
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
?>
