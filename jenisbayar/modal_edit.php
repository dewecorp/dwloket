<?php
$sql = $koneksi->query("SELECT * FROM tb_jenisbayar");
while($data = $sql->fetch_assoc()){
?>
<div id="modaledit<?=$data['id_bayar'];?>" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="myModalLabel">
                    <i class="fa fa-edit"></i> EDIT JENIS BAYAR
                </h4>
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
            </div>
            <form action="" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="id" value="<?=$data['id_bayar'];?>">
                    <div class="form-group">
                        <label for="nama">Jenis Pembayaran</label>
                        <input type="text" name="jenis" class="form-control" value="<?=$data['jenis_bayar'];?>">
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
    $id    = @$_POST['id'];
    $jenis = @$_POST['jenis'];
    
    $stmt = $koneksi->prepare("UPDATE tb_jenisbayar SET jenis_bayar = ? WHERE id_bayar = ?");
    $stmt->bind_param("si", $jenis, $id);
    $sql = $stmt->execute();
    $stmt->close();

    if ($sql) {
        // Script SweetAlert dan redirect setelah sukses
        // SweetAlert library sudah dimuat di footer.php
?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    Swal.fire({
        position: 'top-center',
        icon: 'success',
        title: '<?=htmlspecialchars($jenis, ENT_QUOTES)?>',
        text: 'Berhasil Diedit',
        showConfirmButton: true,
        confirmButtonColor: '#28a745',
        timer: 3000,
        timerProgressBar: true
    }).then(function() {
        window.location.href = '<?=base_url('jenisbayar/jenis_bayar.php')?>';
    });

    // Fallback redirect setelah 3.5 detik
    setTimeout(function() {
        window.location.href = '<?=base_url('jenisbayar/jenis_bayar.php')?>';
    }, 3500);
});
</script>
<?php
    } else {
        // Jika gagal update
        // SweetAlert library sudah dimuat di footer.php
?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    Swal.fire({
        icon: 'error',
        title: 'Gagal!',
        text: 'Gagal mengedit jenis pembayaran. Silakan coba lagi.',
        confirmButtonColor: '#dc3545'
    });
});
</script>
<?php
    }
}
}
?>
