<div id="modaltambah" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="myModalLabel">
                    <i class="fa fa-user-plus"></i> INPUT DATA USER
                </h4>
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-lg-6">
                        <form action="" method="POST" enctype="multipart/form-data">
                            <div class="form-group">
                                <label for="username">Username</label>
                                <input type="text" name="username" class="form-control" placeholder="Username" autofocus
                                required>
                            </div>
                            <div class="form-group">
                                <label for="password">Password</label>
                                <input type="password" name="password" class="form-control" placeholder="Password" required>
                            </div>
                            <div class="form-group">
                                <label for="nama">Nama</label>
                                <input type="text" name="nama" class="form-control" placeholder="Nama" required>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="form-group">
                                <label for="email">Email</label>
                                <input type="text" name="email" class="form-control" placeholder="Email" required>
                            </div>
                            <div class="form-group">
                                <label for="level">Level</label>
                                <select class="form-control" name="level" required>
                                    <option value="">- Plilih Level -</option>
                                    <option value="admin">Admin</option>
                                    <option value="user">User</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="foto">Foto</label>
                                <input type="file" name="foto" class="form-control">
                                <span>
                                    <font color="red"><i>*Abaikan Jika Foto Tidak Ada</i></font>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <input type="submit" name="simpan" class="btn btn-success btn-sm" value="Simpan">
                    <button type="button" class="btn btn-danger btn-sm" data-dismiss="modal">Tutup</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php
if (@$_POST['simpan']) {
require_once '../libs/password_helper.php';

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';
$nama = trim($_POST['nama'] ?? '');
$email = trim($_POST['email'] ?? '');
$level = $_POST['level'] ?? '';

// Validasi
if (empty($username) || empty($password) || empty($nama) || empty($email) || empty($level)) {
	?>
	<script type="text/javascript">
	Swal.fire({
		icon: 'error',
		title: 'Gagal!',
		text: 'Semua field wajib diisi!',
		confirmButtonColor: '#dc3545',
		confirmButtonText: 'OK'
	});
	</script>
	<?php
	exit;
}

// Hash password
$hashed_password = hash_password($password);

$sumber = $_FILES['foto']['tmp_name'] ?? '';
$nama_foto = 'default.png';

if (!empty($sumber) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
	$ekstensi = explode(".", $_FILES['foto']['name']);
	$nama_foto = "foto-".round(microtime(true)).".".end($ekstensi);
	$upload = move_uploaded_file($sumber, "files/assets/images/".$nama_foto);

	if (!$upload) {
		?>
		<script type="text/javascript">
		Swal.fire({
			icon: 'error',
			title: 'Gagal!',
			text: 'Gagal Mengupload Foto',
			confirmButtonColor: '#dc3545',
			confirmButtonText: 'OK'
		});
		</script>
		<?php
		exit;
	}
}

// Gunakan prepared statement untuk mencegah SQL injection
$stmt = $koneksi->prepare("INSERT INTO tb_user (username, password, nama, email, level, foto) VALUES (?, ?, ?, ?, ?, ?)");
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

$stmt->bind_param("ssssss", $username, $hashed_password, $nama, $email, $level, $nama_foto);
$result = $stmt->execute();
$stmt->close();

if ($result) {
	// Log aktivitas
	require_once '../libs/log_activity.php';
	@log_activity('create', 'user', 'Menambah user: ' . $nama);
	?>
	<script type="text/javascript">
	Swal.fire({
		icon: 'success',
		title: 'Berhasil!',
		text: '<?=addslashes($nama)?> Berhasil Ditambahkan',
		confirmButtonColor: '#28a745',
		timer: 2000,
		timerProgressBar: true,
		showConfirmButton: false
	}).then(() => {
		window.location.href = "<?=base_url('user')?>";
	});
	</script>
	<?php
} else {
	?>
	<script type="text/javascript">
	Swal.fire({
		icon: 'error',
		title: 'Gagal!',
		text: 'Gagal menambahkan user. Username mungkin sudah digunakan.',
		confirmButtonColor: '#dc3545',
		confirmButtonText: 'OK'
	});
	</script>
	<?php
}
}
?>
