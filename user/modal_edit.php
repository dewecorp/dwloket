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
                                <input type="text" name="username" class="form-control" value="<?=htmlspecialchars($data['username']);?>">
                            </div>
                            <div class="form-group">
                                <label for="password">Password</label>
                                <input type="text" name="password" class="form-control" value="<?=htmlspecialchars($data['password']);?>">
                            </div>
                            <div class="form-group">
                                <label for="nama">Nama</label>
                                <input type="text" name="nama" class="form-control" value="<?=htmlspecialchars($data['nama']);?>">
                            </div>
                            <div class="form-group">
                                <label for="email">Email</label>
                                <input type="text" name="email" class="form-control" value="<?=htmlspecialchars($data['email']);?>">
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
                                <input type="hidden" name="foto_lama" value="<?=htmlspecialchars($data['foto']);?>">
                                <div><img src="<?=base_url()?>/files/assets/images/<?=htmlspecialchars($data['foto']);?>" alt="" width="100">
                                </div>
                                <input type="file" name="foto" class="form-control" accept="image/png, image/jpeg, image/gif">
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
if(isset($_POST['edit']) && $_POST['id'] == $data['id_user']) {
    $id        = $_POST['id'];
    $username  = $_POST['username'];
    // Jika password diisi, hash password baru. Jika kosong, gunakan password lama.
    if (!empty($_POST['password'])) {
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    } else {
        $password = $data['password'];
    }
    $nama      = $_POST['nama'];
    $email     = $_POST['email'];
    $level     = $_POST['level'];

    // Default success state
    $success = false;
    $message = "";

    // Cek upload foto
    if(isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
        $sumber    = $_FILES['foto']['tmp_name'];
        $original_name = $_FILES['foto']['name'];
        $ekstensi  = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
        $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];

        // Validasi ekstensi dan tipe file
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $sumber);
        // finfo_close($finfo); // Deprecated/Unnecessary in PHP 8+ as finfo object is auto-closed

        $allowed_mime = ['image/jpeg', 'image/png', 'image/gif'];

        if(in_array($ekstensi, $allowed_ext) && in_array($mime, $allowed_mime)) {
            $nama_foto = "foto-" . round(microtime(true)) . "." . $ekstensi;
            $target = __DIR__ . "/../../files/assets/images/" . $nama_foto;

            if(move_uploaded_file($sumber, $target)) {
                // Hapus foto lama jika bukan default
                $foto_lama = $_POST['foto_lama'];
                if($foto_lama != 'default.png' && file_exists(__DIR__ . "/../../files/assets/images/" . $foto_lama)){
                    @unlink(__DIR__ . "/../../files/assets/images/" . $foto_lama);
                }

                // Update dengan foto menggunakan Prepared Statement
                $stmt = $koneksi->prepare("UPDATE tb_user SET username=?, password=?, nama=?, email=?, level=?, foto=? WHERE id_user=?");
                $stmt->bind_param("ssssssi", $username, $password, $nama, $email, $level, $nama_foto, $id);

                if($stmt->execute()) {
                    $success = true;
                    $message = "Berhasil Mengedit " . addslashes($nama);
                } else {
                    $message = "Gagal update database: " . $stmt->error;
                }
                $stmt->close();
            } else {
                $message = "Gagal upload file.";
            }
        } else {
            $message = "Format file tidak valid. Hanya JPG, JPEG, PNG, dan GIF yang diperbolehkan.";
        }
    } else {
        // Update tanpa foto menggunakan Prepared Statement
        $stmt = $koneksi->prepare("UPDATE tb_user SET username=?, password=?, nama=?, email=?, level=? WHERE id_user=?");
        $stmt->bind_param("sssssi", $username, $password, $nama, $email, $level, $id);

        if($stmt->execute()) {
            $success = true;
            $message = "Berhasil Mengedit " . addslashes($nama);
        } else {
            $message = "Gagal update database: " . $stmt->error;
        }
        $stmt->close();
    }

    if($success) {
        // Log aktivitas
        require_once '../libs/log_activity.php';
        @log_activity('update', 'user', 'Mengedit user: ' . $nama);
?>
<script>
Swal.fire({
    icon: 'success',
    title: 'Berhasil!',
    text: '<?=htmlspecialchars($message)?>',
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
    text: '<?=htmlspecialchars($message)?>',
    confirmButtonColor: '#dc3545',
    confirmButtonText: 'OK'
});
</script>
<?php
    }
}
?>
<?php } ?>
