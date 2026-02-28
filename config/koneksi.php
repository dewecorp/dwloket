<?php
// Konfigurasi default
$host = "localhost";
$user = "root";
$pass = "";
$db   = "dwloket";

// Override dengan konfigurasi eksternal jika ada (untuk production)
if (file_exists(__DIR__ . '/database.php')) {
    include __DIR__ . '/database.php';
}

$koneksi = mysqli_connect($host, $user, $pass, $db);

// Check connection
if (mysqli_connect_errno()){
    // Cek environment
    if (defined('ENVIRONMENT') && ENVIRONMENT === 'production') {
        // Di production, error dicatat di log server
        error_log("Database connection failed: " . mysqli_connect_error());
        die("Koneksi database gagal. Silakan hubungi administrator.");
    } else {
        // Di development, tampilkan error asli
        die("Failed to connect to MySQL: " . mysqli_connect_error());
    }
}
?>
