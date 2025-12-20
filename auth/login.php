<?php
ob_start();
include "../config/config.php";

if (@$_SESSION['admin'] || @$_SESSION['user']) {
	echo "<script>window.location='".base_url()."';</script>";
} else {
?>


<!DOCTYPE html>
<html dir="ltr">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <!-- Tell the browser to be responsive to screen width -->
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="">
    <!-- Favicon icon -->
    <link rel="icon" type="image/png" sizes="16x16" href="<?=base_url()?>/files/assets/images/dwloket_icon.png">
    <title>Login - DW LOKET JEPARA <?= date('Y') ?></title>
    <!-- Custom CSS -->
    <link href="<?=base_url()?>/files/dist/css/style.min.css" rel="stylesheet">
    <style>
        /* Modern Login Page Styles */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .auth-wrapper {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
            position: relative;
            overflow: hidden;
        }

        .auth-wrapper::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image:
                radial-gradient(circle at 20% 50%, rgba(255,255,255,0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(255,255,255,0.1) 0%, transparent 50%);
            pointer-events: none;
        }

        .auth-box {
            background: white;
            border-radius: 25px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
            max-width: 900px;
            margin: 20px;
            position: relative;
            z-index: 1;
        }

        .modal-bg-img {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px;
            min-height: 500px;
        }

        .modal-bg-img::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 400 400"><circle cx="100" cy="100" r="80" fill="rgba(255,255,255,0.1)"/><circle cx="300" cy="200" r="60" fill="rgba(255,255,255,0.1)"/><circle cx="200" cy="300" r="70" fill="rgba(255,255,255,0.1)"/><path d="M150 150 L250 150 L250 250 L150 250 Z" fill="none" stroke="rgba(255,255,255,0.2)" stroke-width="2"/></svg>');
            background-size: cover;
            opacity: 0.3;
        }

        .payment-illustration {
            position: relative;
            z-index: 2;
            text-align: center;
            color: white;
        }

        .payment-illustration svg {
            width: 100%;
            max-width: 300px;
            height: auto;
            margin-bottom: 30px;
            filter: drop-shadow(0 10px 20px rgba(0,0,0,0.2));
        }

        .payment-illustration h3 {
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 10px;
            text-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }

        .payment-illustration p {
            font-size: 16px;
            opacity: 0.9;
            text-shadow: 0 1px 5px rgba(0,0,0,0.2);
        }

        .login-form-container {
            padding: 50px 40px;
        }

        .login-logo {
            text-align: center;
            margin-bottom: 30px;
        }

        .login-logo img {
            max-width: 120px;
            height: auto;
            margin-bottom: 15px;
            filter: drop-shadow(0 5px 10px rgba(0,0,0,0.1));
        }

        .login-title {
            font-size: 32px;
            font-weight: 700;
            color: #333;
            margin-bottom: 10px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .login-subtitle {
            color: #666;
            font-size: 15px;
            margin-bottom: 35px;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
            font-size: 14px;
            display: block;
        }

        .form-control {
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            padding: 14px 18px;
            font-size: 15px;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }

        .form-control:focus {
            border-color: #667eea;
            background: white;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
            outline: none;
        }

        .btn-login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 12px;
            padding: 14px;
            font-size: 16px;
            font-weight: 600;
            color: white;
            width: 100%;
            margin-top: 10px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.5);
            background: linear-gradient(135deg, #5568d3 0%, #6a3d8f 100%);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        @media (max-width: 768px) {
            .modal-bg-img {
                display: none;
            }

            .login-form-container {
                padding: 40px 30px;
            }
        }
    </style>

</head>

<body>
    <div class="main-wrapper">
        <!-- ============================================================== -->
        <!-- ============================================================== -->
        <!-- Login box.scss -->
        <!-- ============================================================== -->
        <div class="auth-wrapper d-flex no-block justify-content-center align-items-center position-relative">
            <div class="auth-box row">
                <div class="col-lg-7 col-md-5 modal-bg-img">
                    <div class="payment-illustration">
                        <svg viewBox="0 0 400 400" xmlns="http://www.w3.org/2000/svg">
                            <!-- Credit Card -->
                            <rect x="50" y="150" width="300" height="180" rx="15" fill="white" opacity="0.9"/>
                            <rect x="50" y="150" width="300" height="50" rx="15" fill="url(#gradient1)"/>
                            <rect x="80" y="230" width="60" height="40" rx="5" fill="#ccc"/>
                            <rect x="80" y="285" width="200" height="25" rx="5" fill="#ddd"/>
                            <circle cx="280" cy="270" r="15" fill="#ff6b6b"/>
                            <circle cx="310" cy="270" r="15" fill="#ffa500" opacity="0.7"/>
                            <text x="200" y="180" font-family="Arial" font-size="14" fill="white" text-anchor="middle" font-weight="bold">DW LOKET</text>

                            <!-- Money/Coins -->
                            <circle cx="320" cy="80" r="35" fill="#ffd700" opacity="0.9"/>
                            <circle cx="320" cy="80" r="25" fill="#ffed4e"/>
                            <text x="320" y="90" font-family="Arial" font-size="20" fill="#333" text-anchor="middle" font-weight="bold">₿</text>

                            <!-- Payment Arrow -->
                            <path d="M 250 200 L 280 200 L 275 190 M 280 200 L 275 210" stroke="#667eea" stroke-width="4" fill="none" stroke-linecap="round"/>

                            <!-- Mobile Phone -->
                            <rect x="200" y="80" width="80" height="130" rx="12" fill="#333"/>
                            <rect x="210" y="95" width="60" height="100" rx="5" fill="#4CAF50"/>
                            <circle cx="240" cy="205" r="8" fill="#fff"/>

                            <defs>
                                <linearGradient id="gradient1" x1="0%" y1="0%" x2="100%" y2="0%">
                                    <stop offset="0%" style="stop-color:#667eea;stop-opacity:1" />
                                    <stop offset="100%" style="stop-color:#764ba2;stop-opacity:1" />
                                </linearGradient>
                            </defs>
                        </svg>
                        <h3>Sistem Pembayaran Digital</h3>
                        <p>Platform terpercaya untuk transaksi pembayaran</p>
                    </div>
                </div>
                <div class="col-lg-5 col-md-7">
                    <div class="login-form-container">
                        <div class="login-logo">
                            <img src="<?=base_url()?>/files/assets/images/dwloket_icon.png" alt="DW Loket Logo">
                            <h2 class="login-title">DW LOKET JEPARA</h2>
                            <p class="login-subtitle">Masukkan Username dan Password Anda</p>
                        </div>
                        <form method="POST" id="loginForm">
                            <div class="form-group">
                                <label for="uname">Username</label>
                                <input class="form-control" name="user" id="uname" type="text" placeholder="Masukkan username" required autocomplete="username">
                            </div>
                            <div class="form-group">
                                <label for="pwd">Password</label>
                                <input class="form-control" name="pass" id="pwd" type="password" placeholder="Masukkan password" required autocomplete="current-password">
                            </div>
                            <button type="submit" name="login" class="btn-login">
                                Masuk ke Sistem
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <!-- ============================================================== -->
        <!-- Login box.scss -->
        <!-- ============================================================== -->
    </div>
    <!-- ============================================================== -->
    <!-- All Required js -->
    <!-- ============================================================== -->
    <script src="<?=base_url()?>/files/assets/libs/jquery/dist/jquery.min.js "></script>
    <!-- Bootstrap tether Core JavaScript -->
    <script src="<?=base_url()?>/files/assets/libs/popper.js/dist/umd/popper.min.js "></script>
    <script src="<?=base_url()?>/files/assets/libs/bootstrap/dist/js/bootstrap.min.js "></script>
    <script src="<?=base_url()?>/files/dist/js/sweetalert2.all.min.js"></script>
    <!-- ============================================================== -->
    <!-- This page plugin js -->
    <!-- ============================================================== -->
</body>

</html>
<?php
if (isset($_POST['login'])) {
	require_once '../libs/password_helper.php';

	$username = trim($_POST['user'] ?? '');
	$pass = $_POST['pass'] ?? '';

	// Validasi input
	if (empty($username) || empty($pass)) {
		?>
		<script type="text/javascript">
			Swal.fire({
				icon: 'error',
				title: 'Login Gagal!',
				text: 'Username dan Password harus diisi!',
				confirmButtonColor: '#dc3545',
				confirmButtonText: 'OK'
			});
		</script>
		<?php
		exit;
	}

	// Gunakan prepared statement untuk mencegah SQL injection
	$stmt = $koneksi->prepare("SELECT * FROM tb_user WHERE username = ? LIMIT 1");
	if (!$stmt) {
		error_log("Prepare failed: " . $koneksi->error);
		?>
		<script type="text/javascript">
			Swal.fire({
				icon: 'error',
				title: 'Error!',
				text: 'Terjadi kesalahan sistem. Silakan coba lagi.',
				confirmButtonColor: '#dc3545',
				confirmButtonText: 'OK'
			});
		</script>
		<?php
		exit;
	}

	$stmt->bind_param("s", $username);
	$stmt->execute();
	$result = $stmt->get_result();
	$data = $result->fetch_assoc();
	$stmt->close();

	if ($data && verify_password($pass, $data['password'])) {
		// Password cocok, buat session
		session_start();

		$_SESSION['level'] = $data['level'];
		$_SESSION['id_user'] = $data['id_user'];
		$_SESSION['username'] = $data['username'];
		$_SESSION['nama'] = $data['nama'];

		// Update password ke hash jika masih plain text (migrasi otomatis)
		if (password_needs_rehash($data['password'])) {
			$hashed_password = hash_password($pass);
			$update_stmt = $koneksi->prepare("UPDATE tb_user SET password = ? WHERE id_user = ?");
			$update_stmt->bind_param("si", $hashed_password, $data['id_user']);
			$update_stmt->execute();
			$update_stmt->close();
		}

		// Log aktivitas
		require_once '../libs/log_activity.php';
		@log_activity('login', 'system', 'User berhasil login ke sistem');
		?>
		<script type="text/javascript">
			Swal.fire({
				icon: 'success',
				title: 'Login Berhasil!',
				text: 'Selamat datang di DW Loket Jepara',
				confirmButtonColor: '#28a745',
				timer: 2000,
				timerProgressBar: true,
				showConfirmButton: false,
				allowOutsideClick: false,
				allowEscapeKey: false
			}).then(() => {
				window.location.href = "<?=base_url('home')?>";
			});
		</script>
		<?php
	} else {
		// Username atau password salah
		?>
		<script type="text/javascript">
			Swal.fire({
				icon: 'error',
				title: 'Login Gagal!',
				text: 'Username atau Password salah!',
				confirmButtonColor: '#dc3545',
				confirmButtonText: 'OK'
			});
		</script>
		<?php
	}
}
} // Menutup blok else yang dibuka di baris 7
?>

