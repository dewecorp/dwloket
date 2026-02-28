<?php
date_default_timezone_set('Asia/Jakarta');

// Environment setting (development/production)
// Ubah ke 'production' saat deploy ke hosting
defined('ENVIRONMENT') or define('ENVIRONMENT', 'development');

if (ENVIRONMENT === 'production') {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/../logs/php_errors.log');
} else {
    // Tampilkan error kecuali Notice dan Deprecated agar tidak mengganggu tampilan
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
    ini_set('display_errors', 1);
}

// Konfigurasi session timeout (24 jam)
ini_set('session.gc_maxlifetime', 86400);
ini_set('session.cookie_lifetime', 86400);

// Gunakan path cookie default '/' agar bisa dibaca di semua folder
// Hapus pengaturan strict untuk debugging
// session_set_cookie_params...

// Pastikan session_start hanya dipanggil sekali
if (session_status() === PHP_SESSION_NONE) {
	session_start();
}

include_once "koneksi.php";

function base_url($url = null) {
    // Deteksi protocol (http/https)
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https://" : "http://";

    // Deteksi host (localhost, 127.0.0.1, atau domain lain)
    $host = $_SERVER['HTTP_HOST'];

    // Deteksi folder project (asumsi folder dwloket_ok ada di root web server)
    // Jika script dijalankan dari h:\laragon\www\dwloket_ok\index.php
    // $_SERVER['SCRIPT_NAME'] biasanya /dwloket_ok/index.php
    $script_name = $_SERVER['SCRIPT_NAME'];
    $script_dir = dirname($script_name);

    // Bersihkan path dari subfolder (misal /auth, /home)
    // Kita asumsikan config.php ada di /config, jadi root adalah parent dari config
    // Tapi fungsi ini dipanggil dari mana saja.
    // Cara paling aman untuk struktur saat ini (h:\laragon\www\dwloket_ok):
    // Hardcode path relatif web server jika di localhost/dwloket_ok

    $base_path = "/dwloket_ok"; // Sesuaikan jika nama folder di htdocs berbeda

    // Perbaikan: Deteksi otomatis jika menggunakan Virtual Host (misal: dwloket_ok.test)
    // Jika host mengandung 'dwloket_ok.test', berarti root web server adalah folder project ini sendiri
    if (strpos($_SERVER['HTTP_HOST'], 'dwloket_ok.test') !== false) {
        $base_path = "";
    }

    $root_url = $protocol . $host . $base_path;

    if($url != null) {
        return $root_url."/".$url;
    } else {
        return $root_url;
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
