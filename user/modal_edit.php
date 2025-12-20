<?php
$sql = $koneksi->query("SELECT * FROM tb_user");
while($data = $sql->fetch_assoc()){
?>
<div id="modaledit<?=$data['id_user'];?>" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="myModalLabel">
                    <i class="fa fa-user-edit"></i> EDIT DATA USER
                </h4>
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-lg-6">
                        <form action="" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="id" value="<?=$data['id_user'];?>">
                            <div class="form-group">
                                <label for="username">Username</label>
                                <input type="text" name="username" class="form-control" value="<?=$data['username'];?>">
                            </div>
                            <div class="form-group">
                                <label for="password">Password Baru</label>
                                <input type="password" name="password" class="form-control" placeholder="Kosongkan jika tidak ingin mengubah password">
                                <small class="text-muted">Kosongkan jika tidak ingin mengubah password</small>
                            </div>
                            <div class="form-group">
                                <label for="nama">Nama</label>
                                <input type="text" name="nama" class="form-control" value="<?=$data['nama'];?>">
                            </div>
                            <div class="form-group">
                                <label for="email">Email</label>
                                <input type="text" name="email" class="form-control" value="<?=$data['email'];?>">
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="form-group">
                                <label for="nama">Level</label>
                                <select class="form-control" name="level" id="level" required>
                                    <option value="admin" <?php if($data['level'] == 'admin') {echo "selected";}?>>Admin
                                    </option>
                                    <option value="user" <?php if($data['level'] == 'user') {echo "selected";}?>>User</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="foto">Foto</label>
                                <input type="hidden" name="foto_lama" value="<?=$data['foto'];?>">
                                <div><img src="<?=base_url()?>/files/assets/images/<?=$data['foto'];?>" alt="" width="100">
                                </div>
                                <input type="file" name="foto" class="form-control">
                                <span>
                                    <font color="red"><i>* Abaikan Jika Foto Tidak Diubah</i></font>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <input type="submit" name="edit" class="btn btn-success btn-sm" value="Simpan">
                    <button type="button" class="btn btn-danger btn-sm" data-dismiss="modal">Tutup</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php
if(@$_POST['edit']) {
require_once '../libs/password_helper.php';

$id        = (int)($_POST['id'] ?? 0);
$username  = trim($_POST['username'] ?? '');
$password  = $_POST['password'] ?? '';
$nama      = trim($_POST['nama'] ?? '');
$email     = trim($_POST['email'] ?? '');
$level     = $_POST['level'] ?? '';

if ($id <= 0) {
	?>
	<script type="text/javascript">
	Swal.fire({
		icon: 'error',
		title: 'Error!',
		text: 'ID user tidak valid',
		confirmButtonColor: '#dc3545',
		confirmButtonText: 'OK'
	});
	</script>
	<?php
	exit;
}

$sumber = $_FILES['foto']['tmp_name'] ?? '';
$foto_lama = $_POST['foto_lama'] ?? '';

// Jika ada upload foto baru
if (!empty($sumber) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
	$ekstensi = explode(".", $_FILES['foto']['name']);
	$nama_foto = "foto-".round(microtime(true)).".".end($ekstensi);
	$upload = move_uploaded_file($sumber, "files/assets/images/".$nama_foto);

	if ($upload) {
		// Update foto
		$stmt = $koneksi->prepare("UPDATE tb_user SET foto = ? WHERE id_user = ?");
		$stmt->bind_param("si", $nama_foto, $id);
		$stmt->execute();
		$stmt->close();

		// Hapus foto lama jika bukan default
		if (!empty($foto_lama) && $foto_lama != 'default.png' && file_exists("files/assets/images/".$foto_lama)) {
			@unlink("files/assets/images/".$foto_lama);
		}
	} else {
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

// Update data user
// Cek apakah password diubah (jika password tidak kosong dan berbeda dari yang lama)
$update_password = false;
$hashed_password = '';

if (!empty($password)) {
	// Ambil password lama untuk cek apakah perlu update
	$check_stmt = $koneksi->prepare("SELECT password FROM tb_user WHERE id_user = ?");
	$check_stmt->bind_param("i", $id);
	$check_stmt->execute();
	$check_result = $check_stmt->get_result();
	$old_data = $check_result->fetch_assoc();
	$check_stmt->close();

	if ($old_data) {
		// Jika password baru berbeda dari yang lama, hash password baru
		if (!verify_password($password, $old_data['password'])) {
			$hashed_password = hash_password($password);
			$update_password = true;
		}
	}
}

// Update query
if ($update_password) {
	$stmt = $koneksi->prepare("UPDATE tb_user SET username = ?, password = ?, nama = ?, email = ?, level = ? WHERE id_user = ?");
	$stmt->bind_param("sssssi", $username, $hashed_password, $nama, $email, $level, $id);
} else {
	$stmt = $koneksi->prepare("UPDATE tb_user SET username = ?, nama = ?, email = ?, level = ? WHERE id_user = ?");
	$stmt->bind_param("ssssi", $username, $nama, $email, $level, $id);
}

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

$result = $stmt->execute();
$stmt->close();

if ($result) {
	// Log aktivitas
	require_once '../libs/log_activity.php';
	@log_activity('update', 'user', 'Mengedit user: ' . $nama);
	?>
	<script type="text/javascript">
	Swal.fire({
		icon: 'success',
		title: 'Berhasil!',
		text: 'Berhasil Mengedit <?=addslashes($nama)?>',
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
		text: 'Gagal mengupdate user.',
		confirmButtonColor: '#dc3545',
		confirmButtonText: 'OK'
	});
	</script>
	<?php
}
}
}
?>
