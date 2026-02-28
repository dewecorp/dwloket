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
                                <input type="text" name="password" class="form-control" placeholder="Password" required>
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
    $username = $_POST['username'];
    // Hash password sebelum disimpan
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $nama = $_POST['nama'];
    $email = $_POST['email'];
    $level = $_POST['level'];

    $sumber = $_FILES['foto']['tmp_name'];
    $nama_foto = "";
    $upload_success = true;

    // Handle File Upload
    if (!empty($sumber)) {
        $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['foto']['name'];
        $ext_parts = explode(".", $filename);
        $ext = strtolower(end($ext_parts));

        // Validasi Ekstensi dan MIME Type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $sumber);
        $allowed_mime = ['image/jpeg', 'image/png', 'image/gif'];

        if (!in_array($ext, $allowed_ext) || !in_array($mime, $allowed_mime)) {
            ?>
            <script type="text/javascript">
            Swal.fire({
                icon: 'error',
                title: 'Gagal!',
                text: 'Format file tidak valid! Hanya JPG, JPEG, PNG, GIF.',
                confirmButtonColor: '#dc3545'
            });
            </script>
            <?php
            $upload_success = false;
        } else {
            $nama_foto = "foto-" . round(microtime(true)) . "." . $ext;
            $target_path = "files/assets/images/" . $nama_foto;

            if (move_uploaded_file($sumber, $target_path)) {
                // Success
            } else {
                 ?>
                <script type="text/javascript">
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal!',
                    text: 'Gagal upload file ke server.',
                    confirmButtonColor: '#dc3545'
                });
                </script>
                <?php
                $upload_success = false;
            }
        }
    }

    if ($upload_success) {
        // Gunakan Prepared Statement
        $stmt = $koneksi->prepare("INSERT INTO tb_user (username, password, nama, email, level, foto) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $username, $password, $nama, $email, $level, $nama_foto);

        if ($stmt->execute()) {
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
                text: 'Terjadi kesalahan database: <?=addslashes($stmt->error)?>',
                confirmButtonColor: '#dc3545'
            });
            </script>
            <?php
        }
        $stmt->close();
    }
}
?>
