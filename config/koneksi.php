<?php
$koneksi = mysqli_connect("localhost","root","","dwloket");

// Check connection
if (mysqli_connect_errno()){
	// Jangan echo, gunakan error_log saja
	error_log("Koneksi database gagal : " . mysqli_connect_error());
	// Set koneksi ke null jika gagal
	$koneksi = null;
}
