<?php
require_once __DIR__ . '/../config/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	http_response_code(405);
	echo json_encode(['success' => false, 'message' => 'Method not allowed']);
	exit;
}

if (!isset($_SESSION['level']) || $_SESSION['level'] !== 'admin') {
	http_response_code(403);
	echo json_encode(['success' => false, 'message' => 'Akses ditolak']);
	exit;
}

define('UPDATE_SOURCE', 'https://github.com/dewecorp/dwloket/archive/refs/heads/main.zip');

$temp_dir = sys_get_temp_dir() . '/dwloket_update_' . uniqid();
$zip_file = $temp_dir . '/update.zip';

set_time_limit(300);

function rrmdir($dir) {
	if (!is_dir($dir)) return;
	$files = array_diff(scandir($dir), ['.', '..']);
	foreach ($files as $file) {
		$path = $dir . '/' . $file;
		is_dir($path) ? rrmdir($path) : unlink($path);
	}
	rmdir($dir);
}

try {
	if (!mkdir($temp_dir, 0777, true) && !is_dir($temp_dir)) {
		throw new Exception('Gagal membuat direktori temporary');
	}

	$ch = curl_init(UPDATE_SOURCE);
	$fp = fopen($zip_file, 'w');
	if (!$ch || !$fp) {
		throw new Exception('Gagal menginisialisasi unduhan');
	}
	curl_setopt_array($ch, [
		CURLOPT_FILE => $fp,
		CURLOPT_FOLLOWLOCATION => true,
		CURLOPT_TIMEOUT => 120,
		CURLOPT_SSL_VERIFYPEER => false,
		CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
	]);
	curl_exec($ch);
	$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	curl_close($ch);
	fclose($fp);

	if ($http_code !== 200) {
		throw new Exception('Gagal mengunduh pembaruan (HTTP ' . $http_code . ')');
	}

	$zip = new ZipArchive();
	if ($zip->open($zip_file) !== true) {
		throw new Exception('Gagal membuka file pembaruan');
	}

	$extract_to = $temp_dir . '/extract';
	mkdir($extract_to, 0777, true);
	$zip->extractTo($extract_to);
	$zip->close();

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

	$exclude_patterns = [
		'/\.bat$/i',
		'/\.sql$/i',
		'/\.zip$/i',
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

	$iterator = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator($repo_root, RecursiveDirectoryIterator::SKIP_DOTS),
		RecursiveIteratorIterator::SELF_FIRST
	);

	foreach ($iterator as $file) {
		$relative_path = substr($file->getPathname(), strlen($repo_root) + 1);
		$relative_path = str_replace('\\', '/', $relative_path);

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

	rrmdir($temp_dir);

	echo json_encode([
		'success' => true,
		'message' => 'Pembaruan selesai! ' . $copied . ' file diperbarui.',
	]);

} catch (Exception $e) {
	if (is_dir($temp_dir)) {
		rrmdir($temp_dir);
	}

	echo json_encode([
		'success' => false,
		'message' => 'Gagal: ' . $e->getMessage(),
	]);
}
