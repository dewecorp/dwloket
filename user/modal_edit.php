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
                                <label for="password">Password</label>
                                <input type="text" name="password" class="form-control" value="<?=$data['password'];?>">
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
                                    <option value="admin" <?php if($level == 'admin') {echo "selected";}?>>Admin
                                    </option>
                                    <option value="user" <?php if($level == 'user') {echo "selected";}?>>User</option>
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
$id        = @$_POST['id'];
$username  = @$_POST['username'];
$password  = @$_POST['password'];
$nama      = @$_POST['nama'];
$email     = @$_POST['email'];
$level     = @$_POST['level'];
$sumber    = @$_FILES['foto']['tmp_name'];
$ekstensi  = explode(".", $_FILES['foto']['name']);
$nama_foto = "foto-".round(microtime(true)).".".end($ekstensi);
$upload = move_uploaded_file($sumber, "files/assets/images/".$nama_foto);
if($upload) {
$koneksi->query("UPDATE tb_user SET foto='$nama_foto' WHERE id_user ='$id'");
$foto_lama = $_POST['foto_lama'];
@unlink("files/assets/images/".$foto_lama);
// Log aktivitas
require_once '../libs/log_activity.php';
@log_activity('update', 'user', 'Mengedit user: ' . $nama);
?>
<script>
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
if($sumber == "") {
$koneksi->query("UPDATE tb_user SET username ='$username', password ='$password', nama ='$nama', email ='$email', level='$level' WHERE id_user ='$id'");
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
	text: 'Gagal Mengupload Foto',
	confirmButtonColor: '#dc3545',
	confirmButtonText: 'OK'
});
</script>
<?php
}
}
}
}
?>
