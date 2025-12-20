<?php
// Output buffering untuk mencegah blank screen
while (ob_get_level()) {
	ob_end_clean();
}
ob_start();

// Include config untuk fungsi base_url()
require_once '../config/config.php';

// Log aktivitas sebelum logout
if (isset($_SESSION['id_user'])) {
	require_once '../libs/log_activity.php';
	@log_activity('logout', 'system', 'User logout dari sistem');
}

// Destroy session
session_destroy();

// Clear output buffer sebelum output HTML
ob_end_clean();
ob_start();
?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Logout</title>
	<script src="<?=base_url()?>/files/dist/js/sweetalert2.all.min.js"></script>
</head>
<body>
<script type="text/javascript">
	Swal.fire({
		icon: 'success',
		title: 'Logout Berhasil!',
		text: 'Anda telah logout dari sistem',
		confirmButtonColor: '#28a745',
		timer: 2000,
		timerProgressBar: true,
		showConfirmButton: false,
		allowOutsideClick: false,
		allowEscapeKey: false
	}).then(() => {
		window.location.href = "<?=base_url('auth/login.php')?>";
	});
</script>
</body>
</html>
<?php
ob_end_flush();
?>
