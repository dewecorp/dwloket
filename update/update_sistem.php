<?php
require_once __DIR__ . '/../config/config.php';

/**
 * SECURITY: Update sistem dengan proteksi backdoor
 * - SSL verify: ON (cegah MITM)
 * - CSRF token: wajib
 * - Rate limit: max 1x per 5 menit
 * - Scan backdoor: deteksi eval, system, exec, dll di file PHP
 * - Blokir ekstensi berbahaya: .htaccess, .phtml, .php5, .shtml, dll
 * - Log aktivitas: catat siapa, kapan, hasil update
 */

// -- CSRF Token ----------------------------------------------------------
if (!isset($_POST['_token']) || $_POST['_token'] !== ($_SESSION['update_token'] ?? '')) {
	http_response_code(403);
	echo json_encode(['success' => false, 'message' => 'Token tidak valid. Silakan refresh halaman.']);
	exit;
}

// -- Method check --------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	http_response_code(405);
	echo json_encode(['success' => false, 'message' => 'Method not allowed']);
	exit;
}

// -- Session check -------------------------------------------------------
if (!isset($_SESSION['level']) || $_SESSION['level'] !== 'admin') {
	http_response_code(403);
	echo json_encode(['success' => false, 'message' => 'Akses ditolak']);
	exit;
}

// -- Rate limit: max 1x per 5 menit --------------------------------------
$rate_key = 'update_last_time';
$min_interval = 300; // 5 menit
$last_update = $_SESSION[$rate_key] ?? 0;
if (time() - $last_update < $min_interval) {
	$sisa = $min_interval - (time() - $last_update);
	echo json_encode([
		'success' => false,
		'message' => 'Mohon tunggu ' . ceil($sisa / 60) . ' menit sebelum update lagi.',
	]);
	exit;
}
$_SESSION[$rate_key] = time();

// -- Sumber update -------------------------------------------------------
define('UPDATE_SOURCE', 'https://github.com/dewecorp/dwloket/archive/refs/heads/main.zip');

// -- Setup ---------------------------------------------------------------
$temp_dir = sys_get_temp_dir() . '/dwloket_update_' . uniqid();
$zip_file = $temp_dir . '/update.zip';

set_time_limit(300);

// -- Helper: hapus direktori rekursif ------------------------------------
function rrmdir($dir) {
	if (!is_dir($dir)) return;
	$files = array_diff(scandir($dir), ['.', '..']);
	foreach ($files as $file) {
		$path = $dir . '/' . $file;
		is_dir($path) ? rrmdir($path) : unlink($path);
	}
	rmdir($dir);
}

// -- Helper: log aktivitas -----------------------------------------------
function log_update($koneksi, $status, $pesan) {
	$log_file = __DIR__ . '/../logs/update.log';
	$time = date('Y-m-d H:i:s');
	$user = $_SESSION['username'] ?? 'unknown';
	$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
	$line = "[$time] [$ip] [$user] [$status] $pesan" . PHP_EOL;
	@file_put_contents($log_file, $line, FILE_APPEND | LOCK_EX);
}

// -- Helper: scan backdoor di file PHP -----------------------------------
function scan_backdoor($filepath) {
	$dangerous = [
		// Eksekusi kode
		'/\beval\s*\(/i',
		'/\bassert\s*\(/i',
		'/\bcreate_function\s*\(/i',
		'/\bpreg_replace\s*\(\s*[\'"\/][^"\']*[eems]\s*[\'"\/]\s*[,)]/i',
		// Eksekusi shell
		'/\bsystem\s*\(/i',
		'/\bexec\s*\(/i',
		'/\bshell_exec\s*\(/i',
		'/\bpassthru\s*\(/i',
		'/\bpopen\s*\(/i',
		'/\bproc_open\s*\(/i',
		'/\bpcntl_exec\s*\(/i',
		// Manipulasi file berbahaya
		'/\bchmod\s*\(\s*\$_(?:FILES|SERVER|GET|POST|REQUEST|COOKIE)/i',
		'/\bmove_uploaded_file\s*\(\s*\$_(?:FILES|SERVER|GET|POST|REQUEST|COOKIE)/i',
		// Include dari remote/user input
		'/\binclude\s*\(\s*\$_(?:GET|POST|REQUEST|COOKIE|SERVER)\b/i',
		'/\brequire\s*\(\s*\$_(?:GET|POST|REQUEST|COOKIE|SERVER)\b/i',
		'/\binclude_once\s*\(\s*\$_(?:GET|POST|REQUEST|COOKIE|SERVER)\b/i',
		'/\brequire_once\s*\(\s*\$_(?:GET|POST|REQUEST|COOKIE|SERVER)\b/i',
		// Kombinasi base64 terenkripsi untuk eval
		'/(?:eval|assert)\s*\(\s*(?:base64_decode|gzinflate|gzuncompress|str_rot13)\s*\(/i',
		'/\$[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*\s*\(\s*\$[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*\s*\)\s*\(\s*\$_(?:GET|POST|REQUEST|COOKIE)/i',
		// Webshell signatures
		'/c99\s*shell|r57\s*shell|webshell|backdoor/i',
	];

	$content = file_get_contents($filepath);
	if ($content === false) return 'Tidak bisa membaca file';

	$found = [];
	foreach ($dangerous as $i => $pattern) {
		if (preg_match($pattern, $content)) {
			$found[] = "Pola #" . ($i + 1);
		}
	}
	return $found;
}

try {
	// Buat direktori temp
	if (!mkdir($temp_dir, 0777, true) && !is_dir($temp_dir)) {
		throw new Exception('Gagal membuat direktori temporary');
	}

	// Download ZIP dengan SSL verification
	$ch = curl_init();
	$fp = fopen($zip_file, 'w');
	if (!$ch || !$fp) {
		throw new Exception('Gagal menginisialisasi unduhan');
	}

	// Verifikasi URL hanya dari repo yang diizinkan
	$allowed_host = 'github.com';
	$parsed = parse_url(UPDATE_SOURCE);
	if (!isset($parsed['host']) || $parsed['host'] !== $allowed_host) {
		throw new Exception('Sumber update tidak valid');
	}

	curl_setopt_array($ch, [
		CURLOPT_URL => UPDATE_SOURCE,
		CURLOPT_FILE => $fp,
		CURLOPT_FOLLOWLOCATION => true,
		CURLOPT_MAXREDIRS => 3,
		CURLOPT_TIMEOUT => 120,
		CURLOPT_SSL_VERIFYPEER => true,
		CURLOPT_SSL_VERIFYHOST => 2,
		CURLOPT_USERAGENT => 'DWLOKET-Update/1.0',
		CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
	]);
	$ok = curl_exec($ch);
	$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	$error = curl_error($ch);
	curl_close($ch);
	fclose($fp);

	if (!$ok || $http_code !== 200) {
		$err_msg = $error ?: "HTTP $http_code";
		throw new Exception('Gagal mengunduh pembaruan (' . $err_msg . ')');
	}

	// Validasi file ZIP
	$finfo = new finfo(FILEINFO_MIME_TYPE);
	$mime = $finfo->file($zip_file);
	if (strpos($mime, 'zip') === false && strpos($mime, 'octet-stream') === false) {
		throw new Exception('File unduhan bukan ZIP yang valid (' . $mime . ')');
	}

	// Ekstrak ZIP
	$zip = new ZipArchive();
	if ($zip->open($zip_file) !== true) {
		throw new Exception('Gagal membuka file pembaruan');
	}

	$extract_to = $temp_dir . '/extract';
	mkdir($extract_to, 0777, true);
	$zip->extractTo($extract_to);
	$zip->close();

	// Cari root folder repo
	$items = scandir($extract_to);
	$repo_root = null;
	foreach ($items as $item) {
		if ($item !== '.' && $item !== '..' && is_dir($extract_to . '/' . $item)) {
			$repo_root = $extract_to . '/' . $item;
			break;
		}
	}

	if (!$repo_root) {
		throw new Exception('Struktur file pembaruan tidak valid');
	}

	// -- EKSKLUSI FILE BERBAHAYA -----------------------------------------
	$exclude_patterns = [
		'/\.bat$/i',
		'/\.sql$/i',
		'/\.zip$/i',
		'/\.htaccess$/i',
		'/\.htpasswd$/i',
		'/\.phtml$/i',
		'/\.php5$/i',
		'/\.php7$/i',
		'/\.php8$/i',
		'/\.shtml$/i',
		'/\.shtm$/i',
		'/\.pht$/i',
		'/\.pgif$/i',
		'/\.phar$/i',
		'/\.php\./i',
		'/\.suspected$/i',
		'/^config\//',
		'/^backups\//',
		'/^logs\//',
		'/^vendor\//',
		'/^database\//',
		'/^\.git\//',
	];

	$project_root = realpath(__DIR__ . '/..');
	$copied = 0;
	$skipped = 0;
	$backdoors = [];

	$iterator = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator($repo_root, RecursiveDirectoryIterator::SKIP_DOTS),
		RecursiveIteratorIterator::SELF_FIRST
	);

	foreach ($iterator as $file) {
		$relative_path = substr($file->getPathname(), strlen($repo_root) + 1);
		$relative_path = str_replace('\\', '/', $relative_path);

		// Cek ekstensi dilarang
		$exclude = false;
		foreach ($exclude_patterns as $pattern) {
			if (preg_match($pattern, $relative_path)) {
				$exclude = true;
				break;
			}
		}

		if ($exclude) {
			$skipped++;
			continue;
		}

		// -- SCAN BACKDOOR UNTUK FILE PHP ---------------------------------
		if ($file->isFile() && preg_match('/\.php$/i', $file->getFilename())) {
			$found = scan_backdoor($file->getPathname());
			if (!empty($found)) {
				$backdoors[] = [
					'file' => $relative_path,
					'pola' => $found,
				];
			}
		}

		$target = $project_root . '/' . $relative_path;

		if ($file->isDir()) {
			if (!is_dir($target)) {
				mkdir($target, 0777, true);
			}
		} else {
			$target_dir = dirname($target);
			if (!is_dir($target_dir)) {
				mkdir($target_dir, 0777, true);
			}
			copy($file->getPathname(), $target);
			$copied++;
		}
	}

	// -- HASIL AKHIR ------------------------------------------------------
	rrmdir($temp_dir);

	$message = 'Pembaruan selesai! ' . $copied . ' file diperbarui.';

	if (!empty($backdoors)) {
		$message .= ' PERINGATAN: Terdeteksi ' . count($backdoors) . ' file mencurigakan yang TIDAK disalin: ';
		$details = [];
		foreach ($backdoors as $b) {
			$details[] = $b['file'] . ' (' . implode(', ', $b['pola']) . ')';
		}
		$message .= implode('; ', $details);
	}

	log_update($koneksi, 'SUKSES', $message);
	$_SESSION['update_token'] = bin2hex(random_bytes(32));

	echo json_encode([
		'success' => true,
		'message' => $message,
	]);

} catch (Exception $e) {
	if (is_dir($temp_dir)) {
		rrmdir($temp_dir);
	}

	log_update($koneksi, 'GAGAL', $e->getMessage());
	$_SESSION['update_token'] = bin2hex(random_bytes(32));

	echo json_encode([
		'success' => false,
		'message' => 'Gagal: ' . $e->getMessage(),
	]);
}
