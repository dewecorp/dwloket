<?php
ob_start(); // Buffer output untuk mencegah error headers already sent

require_once "config/config.php";
// Perbaikan: Gunakan header location agar tidak blank screen
if(isset($_SESSION['level']) && !empty($_SESSION['level'])) {
	header("Location: " . base_url('home'));
	exit();
} else {
	header("Location: " . base_url('auth/login.php'));
	exit();
}
?>
