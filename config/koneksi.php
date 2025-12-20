<?php
/**
 * Database Connection
 *
 * Untuk production, buat file database.php dari database.example.php
 * dan update dengan credentials yang sesuai
 */

// Cek apakah ada file database.php (untuk production)
if (file_exists(__DIR__ . '/database.php')) {
	$db_config = require __DIR__ . '/database.php';
	$db_host = $db_config['host'] ?? 'localhost';
	$db_user = $db_config['username'] ?? 'root';
	$db_pass = $db_config['password'] ?? '';
	$db_name = $db_config['database'] ?? 'dwloket';
} else {
	// Default untuk development (backward compatibility)
	$db_host = "localhost";
	$db_user = "root";
	$db_pass = "";
	$db_name = "dwloket";
}

$koneksi = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

// Check connection
if (mysqli_connect_errno()){
	// Jangan echo, gunakan error_log saja
	error_log("Koneksi database gagal : " . mysqli_connect_error());
	// Set koneksi ke null jika gagal
	$koneksi = null;
} else {
	// Set charset untuk mencegah encoding issues
	mysqli_set_charset($koneksi, "utf8mb4");
}
